<?php
// src/actions/tn_action_create_locations.php

if (!($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_locations')) {
    error_log("Ostrzeżenie: tn_action_create_locations.php wywołany niepoprawnie.");
    header("Location: /magazyn");
    exit;
}

// Walidacja CSRF
if (!tn_waliduj_token_csrf($_POST['tn_csrf_token'] ?? null)) {
    tn_ustaw_komunikat_flash("Nieprawidłowy token bezpieczeństwa (CSRF). Tworzenie lokalizacji anulowane.", 'danger');
    header("Location: /magazyn");
    exit;
}

// Pobierz i załaduj aktualny stan magazynu i regały
$tn_plik_magazyn = TN_PLIK_MAGAZYN;
$tn_stan_magazynu = tn_laduj_magazyn($tn_plik_magazyn);
$tn_regaly = tn_laduj_regaly(TN_PLIK_REGALY); // Potrzebne do walidacji ID regału

// Pobierz dane z formularza
$tn_id_regalu_docelowego = trim($_POST['tn_regal_id'] ?? '');
$tn_liczba_poziomow = filter_input(INPUT_POST, 'tn_liczba_poziomow', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$tn_miejsc_na_poziom = filter_input(INPUT_POST, 'tn_miejsc_na_poziom', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$tn_prefix_poziomu = trim(htmlspecialchars(strtoupper($_POST['tn_prefix_poziomu'] ?? 'S'), ENT_QUOTES, 'UTF-8')); // np. S, POZ
$tn_prefix_miejsca = trim(htmlspecialchars(strtoupper($_POST['tn_prefix_miejsca'] ?? 'P'), ENT_QUOTES, 'UTF-8')); // np. P, M

$tn_bledy = [];

// Walidacja ID regału docelowego
if (empty($tn_id_regalu_docelowego)) {
    $tn_bledy[] = "Nie wybrano regału docelowego.";
} else {
    $tn_regal_istnieje = false;
    foreach ($tn_regaly as $r) {
        if (isset($r['tn_id_regalu']) && $r['tn_id_regalu'] === $tn_id_regalu_docelowego) {
             $tn_regal_istnieje = true;
             break;
        }
    }
    if (!$tn_regal_istnieje) {
         $tn_bledy[] = "Wybrany regał docelowy ('" . htmlspecialchars($tn_id_regalu_docelowego) . "') nie istnieje.";
    }
}

// Walidacja liczby poziomów i miejsc
if ($tn_liczba_poziomow === false || $tn_liczba_poziomow <= 0) $tn_bledy[] = "Liczba poziomów musi być dodatnią liczbą całkowitą.";
if ($tn_miejsc_na_poziom === false || $tn_miejsc_na_poziom <= 0) $tn_bledy[] = "Liczba miejsc na poziomie musi być dodatnią liczbą całkowitą.";
if (empty($tn_prefix_poziomu)) $tn_prefix_poziomu = 'S'; // Domyślny prefix
if (empty($tn_prefix_miejsca)) $tn_prefix_miejsca = 'P'; // Domyślny prefix


// Jeśli brak błędów walidacji, generuj lokalizacje
if (empty($tn_bledy)) {
    $tn_nowe_lokalizacje = [];
    $tn_pominiete_istniejace = 0;
    $tn_dodane_licznik = 0;

    // Pobierz istniejące ID lokalizacji dla szybszego sprawdzania
    $tn_istniejace_ids = array_column($tn_stan_magazynu, 'id');

    for ($poz = 1; $poz <= $tn_liczba_poziomow; $poz++) {
        for ($mie = 1; $mie <= $tn_miejsc_na_poziom; $mie++) {
            // Formatuj ID: REGAL-POZIOM-MIEJSCE (np. R01-S01-P01)
            // Użyj sprintf do formatowania numerów z wiodącymi zerami (np. 2 cyfry)
            $tn_id_nowej_lokalizacji = sprintf('%s-%s%02d-%s%02d',
                $tn_id_regalu_docelowego,
                $tn_prefix_poziomu, $poz,
                $tn_prefix_miejsca, $mie
            );

            // Sprawdź, czy ID już istnieje
            if (in_array($tn_id_nowej_lokalizacji, $tn_istniejace_ids)) {
                $tn_pominiete_istniejace++;
                continue; // Pomiń istniejące
            }

            // Dodaj nową lokalizację do tablicy tymczasowej
            $tn_nowe_lokalizacje[] = [
                'id' => $tn_id_nowej_lokalizacji,
                'tn_id_regalu' => $tn_id_regalu_docelowego, // Dodaj ID regału
                'status' => 'empty',
                'product_id' => null,
                'quantity' => 0
            ];
            $tn_dodane_licznik++;
        }
    }

    // Jeśli dodano nowe lokalizacje, połącz je z istniejącymi i zapisz
    if (!empty($tn_nowe_lokalizacje)) {
        $tn_stan_magazynu = array_merge($tn_stan_magazynu, $tn_nowe_lokalizacje);
        // Opcjonalnie: sortuj $tn_stan_magazynu po ID przed zapisem
         usort($tn_stan_magazynu, function($a, $b){ return strnatcmp($a['id'] ?? '', $b['id'] ?? ''); });

        if (tn_zapisz_magazyn($tn_plik_magazyn, $tn_stan_magazynu)) {
            $tn_wiadomosc = "Dodano {$tn_dodane_licznik} nowych lokalizacji dla regału '" . htmlspecialchars($tn_id_regalu_docelowego) . "'.";
            if ($tn_pominiete_istniejace > 0) {
                $tn_wiadomosc .= " Pominięto {$tn_pominiete_istniejace} już istniejących.";
            }
            tn_ustaw_komunikat_flash($tn_wiadomosc, 'success');
        } else {
            tn_ustaw_komunikat_flash("Błąd podczas zapisywania nowych lokalizacji do pliku magazynu.", 'danger');
        }
    } elseif ($tn_pominiete_istniejace > 0) {
        tn_ustaw_komunikat_flash("Nie dodano nowych lokalizacji. {$tn_pominiete_istniejace} wskazanych lokalizacji już istnieje dla regału '" . htmlspecialchars($tn_id_regalu_docelowego) . "'.", 'info');
    } else {
         tn_ustaw_komunikat_flash("Nie wygenerowano żadnych nowych lokalizacji (sprawdź podane parametry).", 'info');
    }

} else {
    // Wyświetl błędy walidacji
    tn_ustaw_komunikat_flash("Nie można utworzyć lokalizacji: " . implode(' ', $tn_bledy), 'warning');
}

// Przekieruj z powrotem do widoku magazynu
header("Location: /magazyn");
exit;

?>