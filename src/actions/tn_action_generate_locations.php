<?php
/**
 * ==============================================================================
 * tn_action_generate_locations.php - Akcja generowania lokalizacji magazynowych
 * tnApp
 * ==============================================================================
 * Wersja: 1.0.0
 *
 * Odbiera dane POST z formularza generowania lokalizacji, waliduje je,
 * tworzy nowe wpisy lokalizacji w pliku stanu magazynu (stan_magazynu.json)
 * i przekierowuje z powrotem do widoku magazynu z komunikatem.
 */

declare(strict_types=1);

// --- Wymagane pliki ---
require_once '../../config/tn_config.php'; // Konfiguracja i sesja
// Załaduj funkcje pomocnicze (zakładamy, że są dostępne)
if (!function_exists('tn_generuj_url')) { require_once '../functions/tn_url_helpers.php'; }
if (!function_exists('tn_ustaw_komunikat_flash')) { require_once '../functions/tn_flash_messages.php'; }
if (!function_exists('tn_laduj_magazyn')) { require_once '../functions/tn_data_helpers.php'; }
if (!function_exists('tn_zapisz_dane_do_json')) { require_once '../functions/tn_data_helpers.php'; }
if (!function_exists('tn_laduj_regaly')) { require_once '../functions/tn_data_helpers.php'; }
if (!function_exists('tn_waliduj_token_csrf')) { require_once '../functions/tn_security_helpers.php'; }

// --- Sprawdzenie metody i tokenu CSRF ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    tn_ustaw_komunikat_flash('Nieprawidłowa metoda żądania.', 'danger');
    header('Location: ' . tn_generuj_url('warehouse_view'));
    exit;
}

$submitted_token = $_POST['tn_csrf_token'] ?? null;
if (!tn_waliduj_token_csrf($submitted_token)) {
    tn_ustaw_komunikat_flash('Błąd zabezpieczeń (CSRF). Spróbuj ponownie.', 'danger');
    header('Location: ' . tn_generuj_url('warehouse_view'));
    exit;
}

// --- Pobranie i walidacja danych z formularza ---
$regal_id = trim($_POST['regal_id'] ?? '');
$level_prefix = trim($_POST['level_prefix'] ?? 'P'); // Domyślny prefix 'P'
$level_start = filter_input(INPUT_POST, 'level_start', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$level_end = filter_input(INPUT_POST, 'level_end', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$slot_prefix = trim($_POST['slot_prefix'] ?? 'M'); // Domyślny prefix 'M'
$slot_start = filter_input(INPUT_POST, 'slot_start', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$slot_end = filter_input(INPUT_POST, 'slot_end', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$overwrite = isset($_POST['overwrite']) && $_POST['overwrite'] === 'true';

// Podstawowa walidacja
$bledy = [];
if (empty($regal_id)) {
    $bledy[] = 'Nie wybrano regału.';
}
if ($level_start === false || $level_end === false || $slot_start === false || $slot_end === false) {
    $bledy[] = 'Zakresy poziomów i miejsc muszą być liczbami całkowitymi większymi od 0.';
}
if ($level_start && $level_end && $level_end < $level_start) {
    $bledy[] = 'Końcowy numer poziomu nie może być mniejszy niż początkowy.';
}
if ($slot_start && $slot_end && $slot_end < $slot_start) {
    $bledy[] = 'Końcowy numer miejsca nie może być mniejszy niż początkowy.';
}

// Sprawdzenie, czy regał istnieje
if (!empty($regal_id)) {
    $regaly = tn_laduj_regaly(TN_PLIK_REGALY);
    $regal_znaleziony = false;
    foreach ($regaly as $regal) {
        if (($regal['tn_id_regalu'] ?? null) === $regal_id) {
            $regal_znaleziony = true;
            break;
        }
    }
    if (!$regal_znaleziony) {
        $bledy[] = 'Wybrany regał nie istnieje.';
    }
}

if (!empty($bledy)) {
    tn_ustaw_komunikat_flash(implode('<br>', $bledy), 'danger');
    header('Location: ' . tn_generuj_url('warehouse_view'));
    exit;
}

// --- Logika generowania lokalizacji ---
$stan_magazynu = tn_laduj_magazyn(TN_PLIK_MAGAZYN);
$stan_magazynu_mapa = []; // Mapa ID lokalizacji dla szybkiego sprawdzania istnienia
foreach ($stan_magazynu as $index => $miejsce) {
    if (isset($miejsce['id'])) {
        $stan_magazynu_mapa[$miejsce['id']] = $index; // Zapisujemy indeks w oryginalnej tablicy
    }
}

$licznik_dodanych = 0;
$licznik_nadpisanych = 0;
$licznik_pominietych = 0;

// Pętle generujące ID lokalizacji
for ($poziom = $level_start; $poziom <= $level_end; $poziom++) {
    for ($miejsce_nr = $slot_start; $miejsce_nr <= $slot_end; $miejsce_nr++) {
        // Formatowanie numerów z wiodącymi zerami (opcjonalnie, np. M01, M02... M10)
        // $poziom_str = sprintf('%02d', $poziom); // Dla dwucyfrowych poziomów
        // $miejsce_str = sprintf('%03d', $miejsce_nr); // Dla trzycyfrowych miejsc
        $poziom_str = (string)$poziom;
        $miejsce_str = (string)$miejsce_nr;

        // Tworzenie ID lokalizacji
        $id_lokalizacji = $regal_id . '-' . $level_prefix . $poziom_str . '-' . $slot_prefix . $miejsce_str;

        // Sprawdzenie, czy lokalizacja już istnieje
        if (isset($stan_magazynu_mapa[$id_lokalizacji])) {
            // Lokalizacja istnieje
            if ($overwrite) {
                // Nadpisz istniejącą lokalizację (ustaw jako pustą)
                $index_do_aktualizacji = $stan_magazynu_mapa[$id_lokalizacji];
                $stan_magazynu[$index_do_aktualizacji] = [
                    'id' => $id_lokalizacji,
                    'tn_id_regalu' => $regal_id,
                    'status' => 'empty',
                    'product_id' => null,
                    'quantity' => 0,
                    'data_przyjecia' => null // Wyzeruj datę przyjęcia przy nadpisywaniu
                ];
                $licznik_nadpisanych++;
            } else {
                // Pomiń istniejącą lokalizację
                $licznik_pominietych++;
            }
        } else {
            // Dodaj nową lokalizację
            $nowa_lokalizacja = [
                'id' => $id_lokalizacji,
                'tn_id_regalu' => $regal_id,
                'status' => 'empty', // Nowe lokalizacje są zawsze puste
                'product_id' => null,
                'quantity' => 0,
                'data_przyjecia' => null
            ];
            $stan_magazynu[] = $nowa_lokalizacja;
            // Dodaj do mapy, aby uniknąć duplikatów w tej samej sesji generowania
            $stan_magazynu_mapa[$id_lokalizacji] = count($stan_magazynu) - 1;
            $licznik_dodanych++;
        }
    }
}

// --- Zapis danych ---
if ($licznik_dodanych > 0 || $licznik_nadpisanych > 0) {
    if (tn_zapisz_dane_do_json(TN_PLIK_MAGAZYN, $stan_magazynu)) {
        $komunikat = "Pomyślnie wygenerowano lokalizacje.<br>";
        if ($licznik_dodanych > 0) $komunikat .= "Dodano nowych: {$licznik_dodanych}<br>";
        if ($licznik_nadpisanych > 0) $komunikat .= "Nadpisano istniejących: {$licznik_nadpisanych}<br>";
        if ($licznik_pominietych > 0) $komunikat .= "Pominięto istniejących (bez nadpisywania): {$licznik_pominietych}";
        tn_ustaw_komunikat_flash(trim($komunikat), 'success');
    } else {
        tn_ustaw_komunikat_flash('Błąd podczas zapisywania stanu magazynu.', 'danger');
    }
} else {
    tn_ustaw_komunikat_flash('Nie dodano żadnych nowych lokalizacji.' . ($licznik_pominietych > 0 ? " Pominięto {$licznik_pominietych} istniejących." : ''), 'info');
}

// --- Przekierowanie ---
header('Location: ' . tn_generuj_url('warehouse_view'));
exit;

?>
