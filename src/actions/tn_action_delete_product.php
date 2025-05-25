<?php
// src/actions/tn_action_delete_product.php

// Ta akcja jest wywoływana przez GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete_product') {

    // --- Walidacja tokenu CSRF (przekazanego przez GET) ---
    // UWAGA: Mniej bezpieczne niż POST.
    if (!tn_waliduj_token_csrf($_GET['tn_csrf_token'] ?? null)) {
        tn_ustaw_komunikat_flash('Błąd bezpieczeństwa (nieprawidłowy lub brakujący token). Akcja usuwania produktu anulowana.', 'danger');
        header('Location: /produkty'); // Wróć do listy produktów
        exit;
    }

    // Potrzebujemy dostępu do danych produktów
    global $tn_produkty;
    $tn_plik_produkty = TN_PLIK_PRODUKTY;

    // --- Pobranie i walidacja ID produktu ---
    $tn_id_do_usuniecia = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $tn_blad_wiadomosc = '';
    $tn_produkt_usunieto = false;
    $tn_stary_obrazek = null;
    $tn_klucz_do_usuniecia = -1;

    if ($tn_id_do_usuniecia === false) {
        $tn_blad_wiadomosc = "Nieprawidłowe ID produktu do usunięcia.";
    } else {
        // Znajdź klucz i obrazek produktu do usunięcia
        foreach ($tn_produkty as $tn_klucz => $tn_p) {
            if (($tn_p['id'] ?? null) === $tn_id_do_usuniecia) {
                $tn_klucz_do_usuniecia = $tn_klucz;
                $tn_stary_obrazek = $tn_p['image'] ?? null;
                break;
            }
        }

        if ($tn_klucz_do_usuniecia === -1) {
            $tn_blad_wiadomosc = "Nie znaleziono produktu o ID: {$tn_id_do_usuniecia}.";
        } else {
            // Usuń produkt z tablicy
            unset($tn_produkty[$tn_klucz_do_usuniecia]);
            // Zresetuj klucze, aby mieć ciągłą tablicę dla json_encode
            $tn_produkty = array_values($tn_produkty);
            $tn_produkt_usunieto = true;

            // --- Zapis pliku produktów ---
            if (!tn_zapisz_produkty($tn_plik_produkty, $tn_produkty)) {
                $tn_blad_wiadomosc = "Błąd zapisu pliku produktów po usunięciu.";
                error_log($tn_blad_wiadomosc . " ID: " . $tn_id_do_usuniecia);
                $tn_produkt_usunieto = false; // Cofnij status, bo zapis się nie udał
            } else {
                 // Jeśli zapis się udał, usuń stary obrazek (jeśli był plikiem)
                 if (!empty($tn_stary_obrazek) && !filter_var($tn_stary_obrazek, FILTER_VALIDATE_URL) && file_exists(TN_SCIEZKA_UPLOAD . $tn_stary_obrazek)) {
                    @unlink(TN_SCIEZKA_UPLOAD . $tn_stary_obrazek);
                 }
            }
        }
    }

    // --- Ustaw komunikaty flash ---
    if ($tn_produkt_usunieto && empty($tn_blad_wiadomosc)) {
        tn_ustaw_komunikat_flash("Produkt został pomyślnie usunięty.", 'success');
    } else {
        tn_ustaw_komunikat_flash("Błąd podczas usuwania produktu: " . $tn_blad_wiadomosc, 'danger');
    }

    // --- Przekierowanie ---
    header("Location: /produkty"); // Zawsze wracaj do listy produktów
    exit;

} // Koniec if ($_SERVER['REQUEST_METHOD'] === 'GET'...)
?>