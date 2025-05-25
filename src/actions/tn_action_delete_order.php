<?php
// src/actions/tn_action_delete_order.php

// Ta akcja jest wywoływana przez GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete_order') {

    // --- Walidacja tokenu CSRF (przekazanego przez GET) ---
    // UWAGA: To jest mniej bezpieczne niż POST. W produkcji zalecany POST.
    if (!tn_waliduj_token_csrf($_GET['tn_csrf_token'] ?? null)) {
        tn_ustaw_komunikat_flash('Błąd bezpieczeństwa (nieprawidłowy lub brakujący token). Akcja usuwania anulowana.', 'danger');
        header('Location: /zamowienia');
        exit;
    }

    // Potrzebujemy dostępu do danych
    global $tn_zamowienia, $tn_produkty;
    $tn_plik_zamowienia = TN_PLIK_ZAMOWIENIA;
    $tn_plik_produkty = TN_PLIK_PRODUKTY;

    // --- Pobranie i walidacja ID zamówienia ---
    $tn_id_zamowienia_do_usuniecia = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $tn_blad_wiadomosc = '';
    $tn_zamowienie_usuniete = false;
    $tn_stan_zmieniony = false;
    $tn_klucz_do_usuniecia = -1;
    $tn_zamowienie_do_usuniecia_dane = null;

    if ($tn_id_zamowienia_do_usuniecia === false) {
        $tn_blad_wiadomosc = "Nieprawidłowe ID zamówienia do usunięcia.";
    } else {
        // Znajdź klucz zamówienia do usunięcia
        foreach ($tn_zamowienia as $tn_klucz => $tn_o) {
            if (($tn_o['id'] ?? null) === $tn_id_zamowienia_do_usuniecia) {
                $tn_klucz_do_usuniecia = $tn_klucz;
                $tn_zamowienie_do_usuniecia_dane = $tn_o;
                break;
            }
        }

        if ($tn_klucz_do_usuniecia === -1) {
            $tn_blad_wiadomosc = "Nie znaleziono zamówienia o ID: {$tn_id_zamowienia_do_usuniecia}.";
        } else {
            // Sprawdź, czy trzeba przywrócić stan magazynowy
            if (isset($tn_zamowienie_do_usuniecia_dane['status']) && $tn_zamowienie_do_usuniecia_dane['status'] === 'Zrealizowane' && !empty($tn_zamowienie_do_usuniecia_dane['processed'])) {
                $tn_id_produktu_do_zwrotu = intval($tn_zamowienie_do_usuniecia_dane['product_id'] ?? 0);
                $tn_ilosc_do_zwrotu = intval($tn_zamowienie_do_usuniecia_dane['quantity'] ?? 0);

                if ($tn_id_produktu_do_zwrotu > 0 && $tn_ilosc_do_zwrotu > 0) {
                    $tn_produkt_znaleziony_do_zwrotu = false;
                    foreach ($tn_produkty as &$tn_p_ref) {
                        if (($tn_p_ref['id'] ?? null) === $tn_id_produktu_do_zwrotu) {
                            $tn_p_ref['stock'] = ($tn_p_ref['stock'] ?? 0) + $tn_ilosc_do_zwrotu;
                            $tn_stan_zmieniony = true;
                            $tn_produkt_znaleziony_do_zwrotu = true;
                            break;
                        }
                    }
                    unset($tn_p_ref); // Ważne po referencji

                    if (!$tn_produkt_znaleziony_do_zwrotu) {
                         $tn_blad_wiadomosc = "Nie znaleziono produktu (ID: {$tn_id_produktu_do_zwrotu}), aby przywrócić stan magazynowy.";
                         error_log($tn_blad_wiadomosc . " przy usuwaniu zamówienia (ID: {$tn_id_zamowienia_do_usuniecia})");
                         // Można zdecydować, czy kontynuować usuwanie zamówienia mimo to
                    }
                }
            }

            // Usuń zamówienie z tablicy (jeśli nie było krytycznego błędu)
            if (empty($tn_blad_wiadomosc)) {
                unset($tn_zamowienia[$tn_klucz_do_usuniecia]);
                // Zresetuj klucze, aby mieć ciągłą tablicę dla json_encode
                $tn_zamowienia = array_values($tn_zamowienia);
                $tn_zamowienie_usuniete = true;
            }
        }
    }

    // --- Zapis do plików ---
    $tn_zapis_ok = true;
    if ($tn_zamowienie_usuniete && empty($tn_blad_wiadomosc)) {
         if (!tn_zapisz_zamowienia($tn_plik_zamowienia, $tn_zamowienia)) {
             $tn_blad_wiadomosc = "Błąd zapisu pliku zamówień po usunięciu.";
             error_log($tn_blad_wiadomosc . " ID: " . $tn_id_zamowienia_do_usuniecia);
             $tn_zapis_ok = false;
         }
         if ($tn_stan_zmieniony && $tn_zapis_ok) { // Zapisz produkty tylko jeśli zapis zamówień się udał
             if (!tn_zapisz_produkty($tn_plik_produkty, $tn_produkty)) {
                 $tn_blad_wiadomosc = "Błąd zapisu pliku produktów po aktualizacji stanu.";
                 error_log($tn_blad_wiadomosc . " (usunięcie zamówienia ID: {$tn_id_zamowienia_do_usuniecia})");
                 // Rozważ wycofanie zmian w orders.json - skomplikowane
                 $tn_zapis_ok = false;
             }
         }
    } elseif(empty($tn_blad_wiadomosc)) {
        $tn_blad_wiadomosc = "Nie udało się usunąć zamówienia."; // Ogólny błąd, jeśli nie zostało usunięte
    }


    // --- Ustaw komunikaty flash ---
    if ($tn_zapis_ok && $tn_zamowienie_usuniete && empty($tn_blad_wiadomosc)) {
        tn_ustaw_komunikat_flash("Zamówienie zostało pomyślnie usunięte.", 'success');
    } else {
        tn_ustaw_komunikat_flash("Błąd podczas usuwania zamówienia: " . $tn_blad_wiadomosc, 'danger');
    }

    // --- Przekierowanie ---
    header("Location: /zamowienia"); // Zawsze wracaj do listy zamówień
    exit;

} // Koniec if ($_SERVER['REQUEST_METHOD'] === 'GET'...)

?>