<?php
// src/actions/tn_action_clear_warehouse_slot.php

// Wywoływane przez GET z linku przy karcie lokalizacji
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'clear_slot') {

    // --- Walidacja tokenu CSRF (przekazanego przez GET) ---
    if (!tn_waliduj_token_csrf($_GET['tn_csrf_token'] ?? null)) {
        tn_ustaw_komunikat_flash('Błąd bezpieczeństwa (nieprawidłowy lub brakujący token). Akcja anulowana.', 'danger');
        header('Location: /magazyn');
        exit;
    }

    // Potrzebujemy dostępu do stanu magazynu
    global $tn_stan_magazynu; // Używamy globalnej, bo załadowana w index.php
    $tn_plik_magazyn = TN_PLIK_MAGAZYN;

    // --- Pobranie i walidacja ID lokalizacji ---
    $tn_location_id = trim($_GET['location_id'] ?? '');
    $tn_bledy = [];

    if (empty($tn_location_id)) {
        $tn_bledy[] = "Brak ID lokalizacji do opróżnienia.";
    }

    // --- Przetwarzanie (jeśli brak błędów) ---
    if (empty($tn_bledy)) {
        $tn_znaleziono_klucz = false;

        // Znajdź klucz lokalizacji
        foreach ($tn_stan_magazynu as $tn_klucz => &$tn_miejsce) { // Użyj referencji
            if (isset($tn_miejsce['id']) && $tn_miejsce['id'] === $tn_location_id) {
                // Oznacz jako puste i usuń dane produktu
                $tn_miejsce['status'] = 'empty';
                unset($tn_miejsce['product_id']);
                unset($tn_miejsce['quantity']);
                $tn_znaleziono_klucz = true;
                break;
            }
        }
        unset($tn_miejsce); // Usuń referencję

        if (!$tn_znaleziono_klucz) {
            $tn_bledy[] = "Nie znaleziono lokalizacji o ID '{$tn_location_id}'.";
        } else {
            // --- Zapis pliku magazynu ---
            // UWAGA: Nie modyfikujemy products.json
            if (tn_zapisz_magazyn($tn_plik_magazyn, $tn_stan_magazynu)) {
                tn_ustaw_komunikat_flash("Lokalizacja {$tn_location_id} została oznaczona jako pusta.", 'success');
            } else {
                $tn_bledy[] = "Błąd zapisu stanu magazynu po opróżnieniu lokalizacji {$tn_location_id}.";
            }
        }
    }

    // --- Przekierowanie ---
    if (!empty($tn_bledy)) {
        tn_ustaw_komunikat_flash("Błąd opróżniania lokalizacji: " . implode(' ', $tn_bledy), 'danger');
    }

    header("Location: /magazyn"); // Zawsze wracaj do widoku magazynu
    exit;

} // Koniec if ($_SERVER['REQUEST_METHOD'] === 'GET'...)

?>