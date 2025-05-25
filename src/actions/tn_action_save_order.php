<?php
// src/actions/tn_action_save_order.php

// Podstawowe zabezpieczenia i załadowanie configu
if (!defined('TN_CONFIG_LOADED')) require_once __DIR__ . '/../../config/tn_config.php';
// Załadowanie helperów
require_once TN_SCIEZKA_SRC . 'functions/tn_security_helpers.php';
require_once TN_SCIEZKA_SRC . 'functions/tn_flash_messages.php';
require_once TN_SCIEZKA_SRC . 'functions/tn_data_helpers.php';
require_once TN_SCIEZKA_SRC . 'functions/tn_url_helpers.php';

// Sprawdzenie metody i akcji
if (!($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_order')) {
    error_log("Ostrzeżenie: tn_action_save_order.php wywołany niepoprawnie.");
    $redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('orders') : 'index.php?page=orders';
    header("Location: " . $redirect_url);
    exit;
}

// Sprawdzenie logowania
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['tn_user_id'])) {
     tn_ustaw_komunikat_flash("Brak autoryzacji.", 'danger');
     $redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('login_page') : 'login.php';
     header("Location: " . $redirect_url);
     exit;
}

// Walidacja tokenu CSRF
if (!tn_waliduj_token_csrf($_POST['tn_csrf_token'] ?? null)) {
    tn_ustaw_komunikat_flash("Nieprawidłowy token bezpieczeństwa (CSRF). Zapis zamówienia anulowany.", 'danger');
    $redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('orders') : 'index.php?page=orders';
    header("Location: " . $redirect_url);
    exit;
}

// Załaduj aktualne dane
$tn_zamowienia = tn_laduj_zamowienia(TN_PLIK_ZAMOWIENIA);
$tn_produkty = tn_laduj_produkty(TN_PLIK_PRODUKTY);
$tn_ustawienia_globalne = tn_laduj_ustawienia(TN_PLIK_USTAWIENIA, $tn_domyslne_ustawienia ?? []); // Załaduj też ustawienia
$tn_kurierzy_lista = tn_laduj_kurierow(TN_PLIK_KURIERZY); // Załaduj kurierów

// Użyj stałych dla statusów
$tn_prawidlowe_statusy = defined('TN_STATUSY_ZAMOWIEN') ? TN_STATUSY_ZAMOWIEN : [];
$tn_prawidlowe_statusy_platnosci = defined('TN_STATUSY_PLATNOSCI') ? TN_STATUSY_PLATNOSCI : [];

$tn_plik_zamowienia = TN_PLIK_ZAMOWIENIA;
$tn_plik_produkty = TN_PLIK_PRODUKTY;

// --- Pobranie i walidacja danych z formularza ---
$tn_id_zamowienia = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$tn_id_produktu = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$tn_ilosc = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$tn_domyslny_status_z_ustawien = $tn_ustawienia_globalne['tn_domyslny_status_zam'] ?? 'Nowe';
$tn_status_raw = $_POST['status'] ?? null;
$tn_pierwotne_zamowienie = null;
$tn_klucz_zamowienia = false;

// Znajdź pierwotne zamówienie, jeśli edytujemy
if ($tn_id_zamowienia !== false && $tn_id_zamowienia > 0) {
    foreach ($tn_zamowienia as $tn_klucz => $tn_o) {
        if (($tn_o['id'] ?? null) == $tn_id_zamowienia) {
            $tn_klucz_zamowienia = $tn_klucz;
            $tn_pierwotne_zamowienie = $tn_o;
            break;
        }
    }
}

// Ustal status realizacji
if ($tn_status_raw === null) {
    $tn_status_raw = ($tn_id_zamowienia === false) // Jeśli dodajemy nowe
                     ? $tn_domyslny_status_z_ustawien
                     : ($tn_pierwotne_zamowienie['status'] ?? $tn_domyslny_status_z_ustawien); // Jeśli edytujemy
}
$tn_status = (is_array($tn_prawidlowe_statusy) && in_array($tn_status_raw, $tn_prawidlowe_statusy))
             ? $tn_status_raw
             : $tn_domyslny_status_z_ustawien; // Fallback na domyślny

// Ustal status płatności
$tn_status_platnosci_raw = $_POST['tn_status_platnosci'] ?? '';
$tn_status_platnosci = (is_array($tn_prawidlowe_statusy_platnosci) && in_array($tn_status_platnosci_raw, $tn_prawidlowe_statusy_platnosci))
                       ? $tn_status_platnosci_raw
                       : ''; // Pusty string, jeśli nieprawidłowy lub brak

// Pobierz pozostałe dane
$tn_nazwa_kupujacego = trim(htmlspecialchars($_POST['buyer_name'] ?? '', ENT_QUOTES, 'UTF-8'));
$tn_dane_wysylki_kupujacego = trim(htmlspecialchars($_POST['buyer_daneWysylki'] ?? '', ENT_QUOTES, 'UTF-8'));
$tn_courier_id = trim(htmlspecialchars($_POST['courier_id'] ?? '', ENT_QUOTES, 'UTF-8')); // Zmieniono nazwę pola w modalu
$tn_tracking_number = trim(htmlspecialchars($_POST['tracking_number'] ?? '', ENT_QUOTES, 'UTF-8'));
$tn_filtr_powrotu = $_POST['current_status_filter'] ?? ''; // Usunięto domyślne 'Wszystkie'

// Podstawowa walidacja
$tn_bledy = [];
if ($tn_id_produktu === false) $tn_bledy[] = "Nie wybrano prawidłowego produktu.";
if ($tn_ilosc === false) $tn_bledy[] = "Ilość musi być liczbą całkowitą większą od 0.";
if (empty($tn_nazwa_kupujacego)) $tn_bledy[] = "Nazwa klienta jest wymagana.";
if (empty($tn_dane_wysylki_kupujacego)) $tn_bledy[] = "Dane do wysyłki są wymagane.";

// Znajdź dane produktu
$tn_produkt_dane = null; $tn_produkt_indeks = -1;
if ($tn_id_produktu !== false) {
    foreach ($tn_produkty as $tn_indeks => $tn_p) {
        if (($tn_p['id'] ?? null) === $tn_id_produktu) {
            $tn_produkt_dane = $tn_p;
            $tn_produkt_indeks = $tn_indeks;
            break;
        }
    }
}
if ($tn_produkt_dane === null && empty($tn_bledy)) {
    $tn_bledy[] = "Wybrany produkt (ID: ".($tn_id_produktu ?: 'brak').") nie istnieje lub wystąpił błąd.";
}

// Walidacja kuriera (Poprawiona)
if (!empty($tn_courier_id)) {
    // $tn_kurierzy_lista została załadowana na początku
    if (!isset($tn_kurierzy_lista[$tn_courier_id])) {
         $tn_bledy[] = "Wybrano nieprawidłowego kuriera (ID: ".htmlspecialchars($tn_courier_id).").";
         $tn_courier_id = ''; // Wyczyść ID w razie błędu
    }
}

// Walidacja stanu magazynowego (logika bez zmian)
if ($tn_produkt_indeks !== -1 && $tn_ilosc !== false) {
    $tn_aktualny_stan_prod = $tn_produkty[$tn_produkt_indeks]['stock'] ?? 0;
    $tn_ilosc_wymagana_dodatkowo = $tn_ilosc; // Domyślnie, przy dodawaniu

    // Przy edycji, oblicz różnicę, jeśli zamówienie było już przetworzone
    if ($tn_id_zamowienia !== false && $tn_pierwotne_zamowienie !== null && !empty($tn_pierwotne_zamowienie['processed'])) {
        // Jeśli zmieniono produkt lub zamówienie nie było 'Zrealizowane' wcześniej, wymagana pełna ilość
        if (($tn_pierwotne_zamowienie['product_id'] ?? null) != $tn_id_produktu || $tn_pierwotne_zamowienie['status'] !== 'Zrealizowane') {
            $tn_ilosc_wymagana_dodatkowo = $tn_ilosc;
        } else {
             // Jeśli produkt ten sam, a status nadal 'Zrealizowane', wymagana tylko różnica
             $tn_ilosc_wymagana_dodatkowo = $tn_ilosc - intval($tn_pierwotne_zamowienie['quantity'] ?? 0);
        }
    }

    // Sprawdź stan tylko jeśli zmieniamy na 'Zrealizowane' i potrzebujemy dodatkowych sztuk
    if ($tn_status === 'Zrealizowane' && $tn_ilosc_wymagana_dodatkowo > 0) {
        if ($tn_aktualny_stan_prod < $tn_ilosc_wymagana_dodatkowo) {
             $wymagane_info = ($tn_ilosc_wymagana_dodatkowo === $tn_ilosc) ? $tn_ilosc : "{$tn_ilosc_wymagana_dodatkowo} (dodatkowo)";
             $tn_bledy[] = "Niewystarczający stan magazynowy produktu '{$tn_produkt_dane['name']}'. Dostępne: {$tn_aktualny_stan_prod}, wymagane: {$wymagane_info}.";
        }
    }
}


// --- Przetwarzanie (jeśli brak błędów walidacji) ---
if (empty($tn_bledy)) {
    $tn_stan_zmieniony = false; $tn_zapis_ok = true; $tn_typ_akcji = '';
    $current_time = date('Y-m-d H:i:s'); // Używamy jednolitego formatu

    // --- Edycja istniejącego zamówienia ---
    if ($tn_id_zamowienia !== false && $tn_klucz_zamowienia !== false && $tn_pierwotne_zamowienie !== null) {
        $tn_stary_status = $tn_pierwotne_zamowienie['status'] ?? 'Nowe';
        $tn_stara_ilosc = intval($tn_pierwotne_zamowienie['quantity'] ?? 0);
        $tn_stare_id_produktu = intval($tn_pierwotne_zamowienie['product_id'] ?? 0);
        $tn_bylo_przetworzone = !empty($tn_pierwotne_zamowienie['processed']);
        $tn_jest_przetwarzane = ($tn_status === 'Zrealizowane');

        // 1. Przywróć stan, jeśli zmieniamy status z 'Zrealizowane' lub zmieniamy produkt
        if ($tn_bylo_przetworzone && (!$tn_jest_przetwarzane || $tn_id_produktu != $tn_stare_id_produktu)) {
            $tn_klucz_starego_prod = -1;
            foreach ($tn_produkty as $idx => $p) { if (($p['id'] ?? null) == $tn_stare_id_produktu) { $tn_klucz_starego_prod = $idx; break; } }
            if ($tn_klucz_starego_prod !== -1) {
                $tn_produkty[$tn_klucz_starego_prod]['stock'] = ($tn_produkty[$tn_klucz_starego_prod]['stock'] ?? 0) + $tn_stara_ilosc;
                $tn_stan_zmieniony = true;
            } else { error_log("Nie można przywrócić stanu: brak produktu ID: {$tn_stare_id_produktu}"); }
             $tn_zamowienia[$tn_klucz_zamowienia]['processed'] = false; // Oznacz jako nieprzetworzone
        }

        // 2. Zmniejsz stan, jeśli ustawiamy status 'Zrealizowane'
        if ($tn_jest_przetwarzane) {
             $tn_ilosc_do_odjecia = 0;
             if (!$tn_bylo_przetworzone || $tn_id_produktu != $tn_stare_id_produktu) {
                 // Odjęcie pełnej ilości dla nowego produktu lub jeśli wcześniej nie było przetworzone
                 $tn_ilosc_do_odjecia = $tn_ilosc;
             } elseif ($tn_id_produktu == $tn_stare_id_produktu && $tn_ilosc != $tn_stara_ilosc) {
                 // Odjęcie/Dodanie różnicy, jeśli produkt ten sam, ale ilość się zmieniła
                 $tn_ilosc_do_odjecia = $tn_ilosc - $tn_stara_ilosc;
             }

             if ($tn_ilosc_do_odjecia != 0 && $tn_produkt_indeks !== -1) {
                 $tn_produkty[$tn_produkt_indeks]['stock'] = max(0, ($tn_produkty[$tn_produkt_indeks]['stock'] ?? 0) - $tn_ilosc_do_odjecia);
                 $tn_stan_zmieniony = true;
             }
             $tn_zamowienia[$tn_klucz_zamowienia]['processed'] = true; // Oznacz jako przetworzone
        } elseif ($tn_bylo_przetworzone) {
             // Jeśli zmieniamy status z 'Zrealizowane' na inny, już przywróciliśmy stan, więc tylko oznaczamy
             $tn_zamowienia[$tn_klucz_zamowienia]['processed'] = false;
        }


        // Zaktualizuj dane zamówienia
        $tn_zamowienia[$tn_klucz_zamowienia]['product_id'] = $tn_id_produktu;
        $tn_zamowienia[$tn_klucz_zamowienia]['quantity'] = $tn_ilosc;
        $tn_zamowienia[$tn_klucz_zamowienia]['status'] = $tn_status;
        $tn_zamowienia[$tn_klucz_zamowienia]['tn_status_platnosci'] = $tn_status_platnosci;
        $tn_zamowienia[$tn_klucz_zamowienia]['buyer_name'] = $tn_nazwa_kupujacego;
        $tn_zamowienia[$tn_klucz_zamowienia]['buyer_daneWysylki'] = $tn_dane_wysylki_kupujacego;
        $tn_zamowienia[$tn_klucz_zamowienia]['courier_id'] = $tn_courier_id; // Użyj poprawnej nazwy 'courier_id'
        $tn_zamowienia[$tn_klucz_zamowienia]['tracking_number'] = $tn_tracking_number;
        // Dodajmy aktualizację daty modyfikacji
        $tn_zamowienia[$tn_klucz_zamowienia]['date_updated'] = $current_time;

        $tn_typ_akcji = 'zaktualizowane';

    } elseif ($tn_id_zamowienia !== false && $tn_klucz_zamowienia === false) {
         // Ten błąd nie powinien wystąpić, jeśli formularz jest poprawny
         $tn_bledy[] = "Nie znaleziono zamówienia (ID: ".($tn_id_zamowienia ?: '?').") do edycji.";
         $tn_zapis_ok = false;
    }
    // --- Dodawanie nowego zamówienia ---
    else {
         $tn_nowe_id_zamowienia = 1;
         if (!empty($tn_zamowienia)) {
             $tn_id_zamowien = array_column($tn_zamowienia, 'id');
             $tn_id_numeryczne = array_filter($tn_id_zamowien, 'is_numeric');
             if (!empty($tn_id_numeryczne)) {
                 $tn_nowe_id_zamowienia = max($tn_id_numeryczne) + 1;
             }
         }

         $tn_nowe_zamowienie = [
             'id' => $tn_nowe_id_zamowienia,
             'product_id' => $tn_id_produktu,
             'buyer_name' => $tn_nazwa_kupujacego,
             'buyer_daneWysylki' => $tn_dane_wysylki_kupujacego,
             'status' => $tn_status,
             'tn_status_platnosci' => $tn_status_platnosci,
             'quantity' => $tn_ilosc,
             'courier_id' => $tn_courier_id, // Użyj poprawnej nazwy 'courier_id'
             'tracking_number' => $tn_tracking_number,
             'processed' => false, // Domyślnie
             'order_date' => $current_time, // Data utworzenia
             'date_updated' => $current_time // Data modyfikacji (taka sama przy tworzeniu)
         ];

         // Zmniejsz stan, jeśli od razu dodajemy jako 'Zrealizowane'
         if ($tn_status === 'Zrealizowane' && $tn_produkt_indeks !== -1) {
             $tn_produkty[$tn_produkt_indeks]['stock'] = max(0, ($tn_produkty[$tn_produkt_indeks]['stock'] ?? 0) - $tn_ilosc);
             $tn_stan_zmieniony = true;
             $tn_nowe_zamowienie['processed'] = true;
         }

         $tn_zamowienia[] = $tn_nowe_zamowienie;
         $tn_typ_akcji = 'dodane';
    }

    // --- Zapis do plików ---
    if ($tn_zapis_ok && empty($tn_bledy)) { // Sprawdź $tn_bledy ponownie
        if (!tn_zapisz_zamowienia($tn_plik_zamowienia, $tn_zamowienia)) {
            $tn_bledy[] = "Błąd zapisu pliku zamówień.";
            $tn_zapis_ok = false;
            error_log("Błąd zapisu pliku zamówień: " . $tn_plik_zamowienia);
        }
        if ($tn_stan_zmieniony && $tn_zapis_ok) {
            if (!tn_zapisz_produkty($tn_plik_produkty, $tn_produkty)) {
                $tn_bledy[] = "Błąd zapisu pliku produktów (aktualizacja stanu).";
                $tn_zapis_ok = false;
                error_log("Błąd zapisu pliku produktów: " . $tn_plik_produkty);
                // Rozważ wycofanie zmian w zamówieniach, jeśli to krytyczne
            }
        }
    }

    // --- Komunikaty flash ---
    if ($tn_zapis_ok && empty($tn_bledy)) {
        tn_ustaw_komunikat_flash("Zamówienie zostało pomyślnie {$tn_typ_akcji}.", 'success');
    } elseif (empty($tn_bledy)) {
        // Jeśli nie było błędów walidacji, ale zapis się nie udał
        tn_ustaw_komunikat_flash("Wystąpił błąd podczas zapisu danych zamówienia.", 'danger');
    }
    // Błędy walidacji zostaną obsłużone poniżej

} // Koniec if (empty($tn_bledy))

// --- Przekierowanie ---
if (!empty($tn_bledy)) {
    tn_ustaw_komunikat_flash("Nie zapisano zamówienia. Popraw błędy: <br>- " . implode('<br>- ', $tn_bledy), 'danger');
}

// Przekieruj z powrotem do listy, zachowując filtr statusu
$tn_parametry_powrotu = [];
if (!empty($tn_filtr_powrotu) && $tn_filtr_powrotu !== 'Wszystkie') {
    $tn_parametry_powrotu['status'] = $tn_filtr_powrotu;
}
$redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('orders', $tn_parametry_powrotu) : 'index.php?page=orders';
header("Location: " . $redirect_url);
exit;

?>