<?php
// src/actions/tn_action_create_regal.php
// Wersja 1.1 (Dodano obsługę edycji opisu)

// ... (require_once i sprawdzenia bezpieczeństwa jak poprzednio) ...
if (!($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_regal')) { /*...*/ exit; }
if (!tn_waliduj_token_csrf($_POST['tn_csrf_token'] ?? null)) { /*...*/ exit; }

$tn_plik_regaly = TN_PLIK_REGALY;
$tn_regaly = tn_laduj_regaly($tn_plik_regaly);

$tn_id_regalu_raw = trim($_POST['tn_regal_id'] ?? '');
$tn_opis_regalu_raw = trim($_POST['tn_regal_opis'] ?? '');
$tn_original_id = trim($_POST['original_regal_id'] ?? ''); // Pobierz ID do edycji (jeśli jest)
$edycja = !empty($tn_original_id);

$tn_bledy = [];

if (empty($tn_id_regalu_raw)) {
    $tn_bledy[] = "ID regału jest wymagane.";
} elseif (!preg_match('/^[A-Za-z0-9_-]+$/', $tn_id_regalu_raw)) {
    $tn_bledy[] = "ID regału może zawierać tylko litery, cyfry, myślnik i podkreślnik.";
}

// Sanityzacja opisu
$tn_opis_regalu = htmlspecialchars($tn_opis_regalu_raw, ENT_QUOTES, 'UTF-8');

if (empty($tn_bledy)) {
    $znaleziono_klucz = -1;
    $komunikat_akcji = '';

    if ($edycja) { // --- EDYCJA ---
        if ($tn_id_regalu_raw !== $tn_original_id) {
             $tn_bledy[] = "Nie można zmienić ID istniejącego regału."; // Nie pozwalamy na zmianę ID
        } else {
            foreach ($tn_regaly as $key => &$regal) {
                if (($regal['tn_id_regalu'] ?? null) === $tn_original_id) {
                    $regal['tn_opis_regalu'] = $tn_opis_regalu; // Aktualizuj tylko opis
                    $znaleziono_klucz = $key;
                    break;
                }
            }
            unset($regal);
            if ($znaleziono_klucz === -1) $tn_bledy[] = "Nie znaleziono regału o ID '".htmlspecialchars($tn_original_id)."' do edycji.";
            else $komunikat_akcji = 'zaktualizowano';
        }
    } else { // --- DODAWANIE ---
         // Sprawdź unikalność ID
         foreach ($tn_regaly as $r) { if (isset($r['tn_id_regalu']) && strcasecmp($r['tn_id_regalu'], $tn_id_regalu_raw) === 0) { $tn_bledy[] = "Regał o ID '" . htmlspecialchars($tn_id_regalu_raw) . "' już istnieje."; break; } }

         if (empty($tn_bledy)) {
             $tn_nowy_regal = ['tn_id_regalu' => $tn_id_regalu_raw, 'tn_opis_regalu' => $tn_opis_regalu];
             $tn_regaly[] = $tn_nowy_regal;
             $komunikat_akcji = 'dodano';
         }
    }

    // Zapisz, jeśli nie było błędów
    if (empty($tn_bledy)) {
        if (tn_zapisz_regaly($tn_plik_regaly, $tn_regaly)) {
            tn_ustaw_komunikat_flash("Regał '" . htmlspecialchars($tn_id_regalu_raw) . "' został pomyślnie {$komunikat_akcji}.", 'success');
        } else {
             tn_ustaw_komunikat_flash("Błąd podczas zapisywania danych regałów.", 'danger');
        }
    }
}

// --- Przekierowanie ---
if (!empty($tn_bledy)) {
    tn_ustaw_komunikat_flash("Nie można zapisać regału: " . implode(' ', $tn_bledy), 'warning');
}
header("Location: " . tn_generuj_url('warehouse_view')); // Zawsze wracaj do widoku magazynu
exit;

?>