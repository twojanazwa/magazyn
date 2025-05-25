<?php
// src/actions/tn_action_save_settings.php
/**
 * Skrypt akcji do zapisywania globalnych ustawień aplikacji.
 * Odczytuje dane z formularza POST, waliduje je, aktualizuje strukturę
 * ustawień i zapisuje do pliku JSON.
 * Wersja: 1.2 (Obsługa ustawień zwrotów, poprawione parsowanie menu)
 */

// --- Zabezpieczenia i Zależności ---
// Sprawdź, czy skrypt jest wywoływany poprawnie przez POST z odpowiednią akcją
if (!($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings')) {
    // Próba bezpośredniego dostępu lub nieprawidłowej akcji - przekieruj
    // Dostosuj URL przekierowania, jeśli '/logowanie' nie jest odpowiednie
    header('Location: ../../index.php?page=login'); // Przykład przekierowania
    exit;
}

// Rozpocznij sesję, jeśli jeszcze nie jest aktywna (potrzebne dla komunikatów flash i autoryzacji)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Załaduj niezbędne pliki
// Upewnij się, że ścieżki są poprawne względem lokalizacji tego pliku akcji
require_once __DIR__ . '/../../config/tn_config.php'; // Potrzebne dla stałych plików (TN_PLIK_USTAWIENIA) i sesji
require_once __DIR__ . '/../functions/tn_security_helpers.php'; // Dla tn_waliduj_token_csrf
require_once __DIR__ . '/../functions/tn_flash_messages.php'; // Dla tn_ustaw_komunikat_flash
require_once __DIR__ . '/../functions/tn_data_helpers.php';     // Dla tn_laduj_ustawienia, tn_zapisz_ustawienia
require_once __DIR__ . '/../functions/tn_url_helpers.php';      // Dla tn_generuj_url() w przekierowaniu

// Sprawdź, czy użytkownik jest zalogowany
if (!isset($_SESSION['tn_user_id'])) { // Zakładając, że 'tn_user_id' jest kluczem sesji po zalogowaniu
    tn_ustaw_komunikat_flash("Brak autoryzacji. Proszę się zalogować.", 'danger');
    header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('login') : '../../index.php?page=login')); // Przekieruj do logowania
    exit;
}

// Walidacja tokenu CSRF
if (!tn_waliduj_token_csrf($_POST['tn_csrf_token'] ?? null)) {
    tn_ustaw_komunikat_flash("Wystąpił błąd bezpieczeństwa (nieprawidłowy token CSRF). Spróbuj ponownie.", 'danger');
    header('Location: ' . tn_generuj_url('settings')); // Wróć do ustawień
    exit;
}

// Załaduj obecne ustawienia oraz domyślne (jako fallback)
// TN_PLIK_USTAWIENIA powinna być zdefiniowana w tn_config.php
// $tn_domyslne_ustawienia powinna być dostępna globalnie lub przekazana do tn_laduj_ustawienia
global $tn_domyslne_ustawienia; // Jeśli $tn_domyslne_ustawienia jest globalna
if (!isset($tn_domyslne_ustawienia)) {
    $tn_domyslne_ustawienia = []; // Zapewnij pustą tablicę, jeśli nie jest zdefiniowana
    // Możesz tutaj zalogować ostrzeżenie, że domyślne ustawienia nie są dostępne
    error_log("Ostrzeżenie: Tablica \$tn_domyslne_ustawienia nie jest zdefiniowana globalnie w tn_action_save_settings.php");
}
$tn_ustawienia_globalne = tn_laduj_ustawienia(TN_PLIK_USTAWIENIA, $tn_domyslne_ustawienia);

// Udostępnij tablice statusów (zdefiniowane w config.php lub innym centralnym miejscu)
global $tn_prawidlowe_statusy, $tn_prawidlowe_statusy_zwrotow;
// Sprawdzenie, czy globalne tablice statusów są załadowane
if (!isset($tn_prawidlowe_statusy) || !is_array($tn_prawidlowe_statusy)) {
    $tn_prawidlowe_statusy = ['Nowe', 'W realizacji', 'Zakończone', 'Anulowane']; // Fallback
    error_log("Ostrzeżenie: \$tn_prawidlowe_statusy nie jest zdefiniowana lub nie jest tablicą.");
}
if (!isset($tn_prawidlowe_statusy_zwrotow) || !is_array($tn_prawidlowe_statusy_zwrotow)) {
    $tn_prawidlowe_statusy_zwrotow = ['Nowe zgłoszenie', 'W trakcie rozpatrywania', 'Zaakceptowany', 'Odrzucony']; // Fallback
    error_log("Ostrzeżenie: \$tn_prawidlowe_statusy_zwrotow nie jest zdefiniowana lub nie jest tablicą.");
}


// Przygotuj nową tablicę ustawień, zaczynając od obecnych
$tn_nowe_ustawienia = $tn_ustawienia_globalne;
$tn_bledy = []; // Tablica na błędy walidacji

// --- Walidacja i Sanityzacja Danych z Formularza ---

// 1. Sekcja: Ogólne i Regionalne
$tn_pps_val = filter_input(INPUT_POST, 'produkty_na_stronie', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]);
if ($tn_pps_val !== false) $tn_nowe_ustawienia['produkty_na_stronie'] = $tn_pps_val; else $tn_bledy[] = "Nieprawidłowa liczba produktów na stronę (oczekiwano liczby 1-500).";

$tn_ops_val = filter_input(INPUT_POST, 'zamowienia_na_stronie', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]);
if ($tn_ops_val !== false) $tn_nowe_ustawienia['zamowienia_na_stronie'] = $tn_ops_val; else $tn_bledy[] = "Nieprawidłowa liczba zamówień na stronę (oczekiwano liczby 1-500).";

$tn_rps_val = filter_input(INPUT_POST, 'zwroty_na_stronie', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]);
if ($tn_rps_val !== false) $tn_nowe_ustawienia['zwroty_na_stronie'] = $tn_rps_val; else $tn_bledy[] = "Nieprawidłowa liczba zwrotów/reklamacji na stronę (oczekiwano liczby 1-500).";

$tn_waluta_val = trim(strtoupper($_POST['waluta'] ?? ($tn_domyslne_ustawienia['waluta'] ?? 'PLN')));
if (preg_match('/^[A-Z]{3}$/', $tn_waluta_val)) $tn_nowe_ustawienia['waluta'] = $tn_waluta_val; else $tn_bledy[] = "Nieprawidłowy format waluty (oczekiwano 3 dużych liter, np. PLN).";

$tn_rabat_val = filter_input(INPUT_POST, 'domyslny_procent_rabatu', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
if ($tn_rabat_val !== false) $tn_nowe_ustawienia['domyslny_procent_rabatu'] = $tn_rabat_val / 100.0; // Zapis jako ułamek (np. 0.05 dla 5%)
else $tn_bledy[] = "Nieprawidłowa wartość domyślnego rabatu (oczekiwano liczby 0-100).";

$tn_format_daty_val = $_POST['tn_format_daty'] ?? ($tn_domyslne_ustawienia['tn_format_daty'] ?? 'd.m.Y');
$dozwolone_formaty_daty = ['d.m.Y', 'Y-m-d', 'm/d/Y', 'd/m/Y', 'd M Y']; // Rozszerzono listę z formularza
if (in_array($tn_format_daty_val, $dozwolone_formaty_daty)) $tn_nowe_ustawienia['tn_format_daty'] = $tn_format_daty_val; else $tn_bledy[] = "Wybrano nieprawidłowy format daty.";

$tn_format_czasu_val = $_POST['tn_format_czasu'] ?? ($tn_domyslne_ustawienia['tn_format_czasu'] ?? 'H:i');
$dozwolone_formaty_czasu = ['H:i', 'H:i:s', 'h:i A']; // Rozszerzono listę z formularza
if (in_array($tn_format_czasu_val, $dozwolone_formaty_czasu)) $tn_nowe_ustawienia['tn_format_czasu'] = $tn_format_czasu_val; else $tn_bledy[] = "Wybrano nieprawidłowy format czasu.";

// Strefa czasowa (z grupy 'ogolne' w formularzu)
if (isset($_POST['ogolne']['strefa_czasowa'])) {
    $strefa_czasowa_val = trim($_POST['ogolne']['strefa_czasowa']);
    if (in_array($strefa_czasowa_val, DateTimeZone::listIdentifiers(DateTimeZone::ALL))) {
        $tn_nowe_ustawienia['ogolne']['strefa_czasowa'] = $strefa_czasowa_val;
    } else {
        $tn_bledy[] = "Wybrano nieprawidłową strefę czasową.";
    }
}


// 2. Sekcja: Dane Firmy/Sklepu
$tn_firma_post_data = $_POST['firma'] ?? [];
if (!isset($tn_nowe_ustawienia['firma'])) $tn_nowe_ustawienia['firma'] = [];
$tn_nowe_ustawienia['firma']['tn_nazwa_firmy'] = htmlspecialchars(trim($tn_firma_post_data['tn_nazwa_firmy'] ?? ''), ENT_QUOTES, 'UTF-8');

$tn_email_kontaktowy_val = filter_var(trim($tn_firma_post_data['tn_email_kontaktowy'] ?? ''), FILTER_SANITIZE_EMAIL);
if (!empty($tn_email_kontaktowy_val)) {
    if (filter_var($tn_email_kontaktowy_val, FILTER_VALIDATE_EMAIL)) {
        $tn_nowe_ustawienia['firma']['tn_email_kontaktowy'] = $tn_email_kontaktowy_val;
    } else {
        $tn_bledy[] = "Nieprawidłowy format e-maila kontaktowego firmy.";
        // Zachowaj poprzednią wartość lub wyczyść, zależnie od preferencji
        // $tn_nowe_ustawienia['firma']['tn_email_kontaktowy'] = $tn_ustawienia_globalne['firma']['tn_email_kontaktowy'] ?? '';
    }
} else {
    $tn_nowe_ustawienia['firma']['tn_email_kontaktowy'] = ''; // Pozwól na pusty email
}

// E-mail administratora (z grupy 'powiadomienia' w formularzu)
if (isset($_POST['powiadomienia']['admin_email'])) {
    $admin_email_val = filter_var(trim($_POST['powiadomienia']['admin_email'] ?? ''), FILTER_SANITIZE_EMAIL);
     if (!isset($tn_nowe_ustawienia['powiadomienia'])) $tn_nowe_ustawienia['powiadomienia'] = [];
    if (!empty($admin_email_val)) {
        if (filter_var($admin_email_val, FILTER_VALIDATE_EMAIL)) {
            $tn_nowe_ustawienia['powiadomienia']['admin_email'] = $admin_email_val;
        } else {
            $tn_bledy[] = "Nieprawidłowy format e-maila administratora.";
        }
    } else {
        $tn_nowe_ustawienia['powiadomienia']['admin_email'] = ''; // Pozwól na pusty email
    }
}


// 3. Sekcja: Wygląd i Nawigacja
$tn_nazwa_strony_val = trim($_POST['nazwa_strony'] ?? '');
if (!empty($tn_nazwa_strony_val)) $tn_nowe_ustawienia['nazwa_strony'] = htmlspecialchars($tn_nazwa_strony_val, ENT_QUOTES, 'UTF-8');
else $tn_bledy[] = "Nazwa strony (tytuł w przeglądarce) jest wymagana.";

// Obsługa logo - ten skrypt oczekuje URL/ścieżki, a nie uploadu pliku.
// Jeśli formularz wysyła plik (name="logo_file"), ta logika musi być dostosowana.
// Poniżej jest logika dla pola tekstowego `site_logo` z poprzedniej wersji skryptu użytkownika.
// Jeśli formularz wysyła `logo_file` (jak w moim sugerowanym formularzu), potrzebna jest obsługa `$_FILES`.
// Zakładam na razie, że formularz wysyła `$_POST['logo_strony_sciezka_input']` lub podobne dla URL.
// Dostosuj `logo_strony_sciezka_input` do faktycznej nazwy pola w formularzu, jeśli to URL.

// Jeśli formularz ma <input type="file" name="logo_file"> (jak w moim sugerowanym formularzu):
define('UPLOAD_DIR_LOGO_ACTION', __DIR__ . '/../../public/uploads/logo/'); // Katalog na logo
define('BASE_URL_LOGO_PATH_ACTION', 'public/uploads/logo/'); // Ścieżka względna

if (isset($_POST['remove_logo']) && $_POST['remove_logo'] == '1') {
    if (!empty($tn_nowe_ustawienia['logo_strony_sciezka']) && file_exists(UPLOAD_DIR_LOGO_ACTION . basename($tn_nowe_ustawienia['logo_strony_sciezka']))) {
        unlink(UPLOAD_DIR_LOGO_ACTION . basename($tn_nowe_ustawienia['logo_strony_sciezka']));
    }
    $tn_nowe_ustawienia['logo_strony_sciezka'] = '';
} elseif (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == UPLOAD_ERR_OK) {
    if (!is_dir(UPLOAD_DIR_LOGO_ACTION)) {
        if (!mkdir(UPLOAD_DIR_LOGO_ACTION, 0755, true)) {
            $tn_bledy[] = "Nie można utworzyć katalogu na logo: " . UPLOAD_DIR_LOGO_ACTION;
        }
    }
    if (is_dir(UPLOAD_DIR_LOGO_ACTION) && is_writable(UPLOAD_DIR_LOGO_ACTION)) {
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/gif'];
        $file_mime_type = mime_content_type($_FILES['logo_file']['tmp_name']);

        if (in_array($file_mime_type, $allowed_mime_types)) {
            if (!empty($tn_nowe_ustawienia['logo_strony_sciezka']) && file_exists(UPLOAD_DIR_LOGO_ACTION . basename($tn_nowe_ustawienia['logo_strony_sciezka']))) {
                 unlink(UPLOAD_DIR_LOGO_ACTION . basename($tn_nowe_ustawienia['logo_strony_sciezka']));
            }
            $file_extension = pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION);
            $new_filename = 'logo_' . time() . '.' . strtolower($file_extension);
            $destination = UPLOAD_DIR_LOGO_ACTION . $new_filename;

            if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $destination)) {
                $tn_nowe_ustawienia['logo_strony_sciezka'] = BASE_URL_LOGO_PATH_ACTION . $new_filename;
            } else {
                $tn_bledy[] = "Błąd: Nie udało się przenieść przesłanego pliku logo.";
            }
        } else {
            $tn_bledy[] = "Błąd: Niedozwolony typ pliku logo. Dozwolone: JPG, PNG, SVG, GIF. Otrzymano: " . $file_mime_type;
        }
    } else {
         $tn_bledy[] = "Katalog na logo nie istnieje lub nie ma uprawnień do zapisu: " . UPLOAD_DIR_LOGO_ACTION;
    }
}
// Koniec obsługi logo z `$_FILES['logo_file']`

$tn_nowe_ustawienia['tekst_stopki'] = htmlspecialchars(trim($_POST['tekst_stopki'] ?? ''), ENT_QUOTES, 'UTF-8');

// Linki menu (parsing textarea - max 5 części: Tytul|URL|Ikona|Grupa|ID)
// Nazwa pola w formularzu `linki_menu_textarea`
$tn_linki_menu_surowe_val = trim($_POST['linki_menu_textarea'] ?? '');
$tn_linki_menu_przetworzone_val = [];
$tn_blad_menu_flag = false;
$tn_menu_ids_check = [];

if (!empty($tn_linki_menu_surowe_val)) {
    $tn_linie_menu = explode("\n", str_replace(["\r\n", "\r"], "\n", $tn_linki_menu_surowe_val));
    foreach ($tn_linie_menu as $tn_indeks_menu => $tn_linia_menu) {
        $tn_linia_menu_trim = trim($tn_linia_menu);
        if (empty($tn_linia_menu_trim)) continue;

        $tn_czesci_menu = array_map('trim', explode('|', $tn_linia_menu_trim, 5));
        $tn_tytul_menu = $tn_czesci_menu[0] ?? '';
        $tn_url_menu = $tn_czesci_menu[1] ?? '';
        $tn_ikona_menu = $tn_czesci_menu[2] ?? ''; // Powinna być nazwa ikony Bootstrap np. 'house-fill'
        $tn_grupa_menu = $tn_czesci_menu[3] ?? '';
        $tn_id_menu = $tn_czesci_menu[4] ?? null;

        if (empty($tn_tytul_menu) || empty($tn_url_menu)) {
            $tn_bledy[] = "Błąd w definicji menu (linia " . ($tn_indeks_menu + 1) . "): Tytuł oraz URL są wymagane.";
            $tn_blad_menu_flag = true; break;
        }
        if (!preg_match('/^(#|\/|[a-zA-Z0-9_-]+(\.php)?(\?[^ ]*)?|js:|https?:\/\/)/i', $tn_url_menu)) {
             $tn_bledy[] = "Błąd w definicji menu (linia " . ($tn_indeks_menu + 1) . "): Nieprawidłowy format URL/ścieżki '$tn_url_menu'.";
             $tn_blad_menu_flag = true; break;
        }
        if (!empty($tn_ikona_menu) && !preg_match('/^[a-zA-Z0-9-]+$/i', $tn_ikona_menu)) { // Uproszczona walidacja dla nazw klas ikon
             $tn_bledy[] = "Błąd w definicji menu (linia " . ($tn_indeks_menu + 1) . "): Nieprawidłowy format ikony '$tn_ikona_menu'. Oczekiwano np. 'house-fill'.";
             $tn_blad_menu_flag = true; break;
        }
        if ($tn_id_menu === null || $tn_id_menu === '') {
            $tn_id_menu = preg_replace('/[^a-z0-9]+/', '-', strtolower($tn_tytul_menu)); // Generuj ID jeśli brak
            $tn_id_menu = trim($tn_id_menu, '-'); // Usuń ewentualne myślniki na początku/końcu
        }
        if (!preg_match('/^[a-z0-9_-]+$/i', $tn_id_menu)) {
            $tn_bledy[] = "Błąd w definicji menu (linia " . ($tn_indeks_menu + 1) . "): Nieprawidłowy format ID '$tn_id_menu'. Dozwolone tylko małe litery, cyfry, '_' i '-'.";
            $tn_blad_menu_flag = true; break;
        }
        if (isset($tn_menu_ids_check[$tn_id_menu])) {
            $tn_bledy[] = "Błąd w definicji menu (linia " . ($tn_indeks_menu + 1) . "): ID '$tn_id_menu' nie jest unikalne w menu.";
            $tn_blad_menu_flag = true; break;
        }
        $tn_menu_ids_check[$tn_id_menu] = true;

        $tn_link_dane_item = ['tytul' => htmlspecialchars($tn_tytul_menu), 'url' => htmlspecialchars($tn_url_menu), 'id' => htmlspecialchars($tn_id_menu)];
        if (!empty($tn_ikona_menu)) $tn_link_dane_item['ikona'] = htmlspecialchars($tn_ikona_menu);
        if (!empty($tn_grupa_menu)) $tn_link_dane_item['grupa'] = htmlspecialchars($tn_grupa_menu);
        
        $tn_linki_menu_przetworzone_val[] = $tn_link_dane_item;
    }
}
if (!$tn_blad_menu_flag) {
    $tn_nowe_ustawienia['linki_menu'] = !empty($tn_linki_menu_przetworzone_val) ? $tn_linki_menu_przetworzone_val : ($tn_domyslne_ustawienia['linki_menu'] ?? []);
}


// Ustawienia Wyglądu (z grupy 'wyglad')
$tn_wyglad_post_data = $_POST['wyglad'] ?? [];
if (!isset($tn_nowe_ustawienia['wyglad'])) $tn_nowe_ustawienia['wyglad'] = [];

$tn_motyw_val = $tn_wyglad_post_data['tn_motyw'] ?? ($tn_domyslne_ustawienia['wyglad']['tn_motyw'] ?? 'light');
if (in_array($tn_motyw_val, ['light', 'dark', 'system'])) $tn_nowe_ustawienia['wyglad']['tn_motyw'] = $tn_motyw_val; else $tn_bledy[] = "Wybrano nieprawidłowy motyw globalny.";

$tn_kolor_sidebar_val = $tn_wyglad_post_data['tn_kolor_sidebar'] ?? ($tn_domyslne_ustawienia['wyglad']['tn_kolor_sidebar'] ?? 'ciemny');
if (in_array($tn_kolor_sidebar_val, ['jasny', 'ciemny'])) $tn_nowe_ustawienia['wyglad']['tn_kolor_sidebar'] = $tn_kolor_sidebar_val; else $tn_bledy[] = "Wybrano nieprawidłowy kolor panelu bocznego.";

$tn_kolor_akcentu_val = trim($tn_wyglad_post_data['tn_kolor_akcentu'] ?? ($tn_domyslne_ustawienia['wyglad']['tn_kolor_akcentu'] ?? '#0d6efd'));
if (preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/i', $tn_kolor_akcentu_val)) $tn_nowe_ustawienia['wyglad']['tn_kolor_akcentu'] = $tn_kolor_akcentu_val; else $tn_bledy[] = "Nieprawidłowy format koloru akcentu (oczekiwano np. #RRGGBB).";

$tn_rozmiar_czcionki_val = trim($tn_wyglad_post_data['rozmiar_czcionki'] ?? ($tn_domyslne_ustawienia['wyglad']['rozmiar_czcionki'] ?? '13px'));
if (preg_match('/^\d+(\.\d+)?(px|rem|em|%)$/i', $tn_rozmiar_czcionki_val)) $tn_nowe_ustawienia['wyglad']['rozmiar_czcionki'] = htmlspecialchars($tn_rozmiar_czcionki_val); else $tn_bledy[] = "Nieprawidłowy format rozmiaru czcionki (oczekiwano np. 13px, 0.9rem).";

// Dla checkboxów/switchy, używamy isset() lub sprawdzamy wartość '1' (jeśli jest ukryte pole z '0')
$tn_nowe_ustawienia['wyglad']['tn_tabela_paskowana'] = (isset($tn_wyglad_post_data['tn_tabela_paskowana']) && $tn_wyglad_post_data['tn_tabela_paskowana'] == '1');
$tn_nowe_ustawienia['wyglad']['tn_tabela_krawedzie'] = (isset($tn_wyglad_post_data['tn_tabela_krawedzie']) && $tn_wyglad_post_data['tn_tabela_krawedzie'] == '1');


// 4. Sekcja: Moduły Główne
// Kategorie produktów (nazwa pola w formularzu 'kategorie_produktow_textarea')
$tn_kategorie_surowe_val = trim($_POST['kategorie_produktow_textarea'] ?? '');
$tn_kategorie_przetworzone_val = [];
if (!empty($tn_kategorie_surowe_val)) {
    $tn_linie_kategorii = explode("\n", str_replace(["\r\n", "\r"], "\n", $tn_kategorie_surowe_val));
    $tn_kategorie_przetworzone_val = array_values(array_unique(array_filter(array_map('trim', $tn_linie_kategorii), function($kat) { return !empty($kat); }))));
}
// Kategorie nie mogą być puste, jeśli były jakieś wcześniej lub są domyślne
if (empty($tn_kategorie_przetworzone_val) && (!empty($tn_ustawienia_globalne['kategorie_produktow']) || !empty($tn_domyslne_ustawienia['kategorie_produktow']))) {
    // $tn_bledy[] = "Lista kategorii produktów nie może być całkowicie pusta, jeśli wcześniej istniały kategorie.";
    // Pozwól na wyczyszczenie, jeśli to zamierzone. Jeśli nie, odkomentuj błąd.
    $tn_nowe_ustawienia['kategorie_produktow'] = [];
} elseif (!empty($tn_kategorie_przetworzone_val)) {
     $tn_nowe_ustawienia['kategorie_produktow'] = array_map(function($kat) { return htmlspecialchars($kat, ENT_QUOTES, 'UTF-8'); }, $tn_kategorie_przetworzone_val);
} else {
    $tn_nowe_ustawienia['kategorie_produktow'] = $tn_domyslne_ustawienia['kategorie_produktow'] ?? [];
}


// Zamówienia
$tn_domyslny_status_zam_val = $_POST['tn_domyslny_status_zam'] ?? ($tn_domyslne_ustawienia['tn_domyslny_status_zam'] ?? 'Nowe');
if (is_array($tn_prawidlowe_statusy) && in_array($tn_domyslny_status_zam_val, $tn_prawidlowe_statusy)) $tn_nowe_ustawienia['tn_domyslny_status_zam'] = $tn_domyslny_status_zam_val;
else $tn_bledy[] = "Wybrano nieprawidłowy domyślny status dla nowych zamówień.";

// Zwroty/Reklamacje (grupa 'zwroty_reklamacje', klucz 'domyslny_status')
$tn_domyslny_status_zwrotu_val = $_POST['zwroty_reklamacje']['domyslny_status'] ?? ($tn_domyslne_ustawienia['zwroty_reklamacje']['domyslny_status'] ?? 'Nowe zgłoszenie');
if (!isset($tn_nowe_ustawienia['zwroty_reklamacje'])) $tn_nowe_ustawienia['zwroty_reklamacje'] = [];
if (is_array($tn_prawidlowe_statusy_zwrotow) && in_array($tn_domyslny_status_zwrotu_val, $tn_prawidlowe_statusy_zwrotow)) {
    $tn_nowe_ustawienia['zwroty_reklamacje']['domyslny_status'] = $tn_domyslny_status_zwrotu_val;
} else $tn_bledy[] = "Wybrano nieprawidłowy domyślny status dla nowych zgłoszeń zwrotów/reklamacji.";

// Magazyn
$tn_prog_stanu_val = filter_input(INPUT_POST, 'tn_prog_niskiego_stanu', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($tn_prog_stanu_val !== false) $tn_nowe_ustawienia['tn_prog_niskiego_stanu'] = $tn_prog_stanu_val;
else $tn_bledy[] = "Próg niskiego stanu magazynowego musi być liczbą całkowitą nieujemną.";

$tn_nowe_ustawienia['domyslny_magazyn'] = trim(htmlspecialchars($_POST['domyslny_magazyn'] ?? ($tn_domyslne_ustawienia['domyslny_magazyn'] ?? 'GŁÓWNY'), ENT_QUOTES, 'UTF-8'));

$tn_magazyn_post_data = $_POST['magazyn'] ?? [];
if (!isset($tn_nowe_ustawienia['magazyn'])) $tn_nowe_ustawienia['magazyn'] = [];

$tn_nazwa_wysw_mag_val = trim(htmlspecialchars($tn_magazyn_post_data['nazwa_wyswietlana'] ?? ($tn_domyslne_ustawienia['magazyn']['nazwa_wyswietlana'] ?? 'Główny Magazyn'), ENT_QUOTES, 'UTF-8'));
$tn_nowe_ustawienia['magazyn']['nazwa_wyswietlana'] = $tn_nazwa_wysw_mag_val;


$tn_prefix_p_val = trim(strtoupper($tn_magazyn_post_data['tn_prefix_poziom_domyslny'] ?? ($tn_domyslne_ustawienia['magazyn']['tn_prefix_poziom_domyslny'] ?? 'S')));
if (!empty($tn_prefix_p_val) && preg_match('/^[A-Za-z0-9]+$/', $tn_prefix_p_val)) $tn_nowe_ustawienia['magazyn']['tn_prefix_poziom_domyslny'] = htmlspecialchars($tn_prefix_p_val, ENT_QUOTES, 'UTF-8');
else $tn_bledy[] = "Prefix poziomu w magazynie jest wymagany i może zawierać tylko litery oraz cyfry.";

$tn_prefix_m_val = trim(strtoupper($tn_magazyn_post_data['tn_prefix_miejsca_domyslny'] ?? ($tn_domyslne_ustawienia['magazyn']['tn_prefix_miejsca_domyslny'] ?? 'P')));
if (!empty($tn_prefix_m_val) && preg_match('/^[A-Za-z0-9]+$/', $tn_prefix_m_val)) $tn_nowe_ustawienia['magazyn']['tn_prefix_miejsca_domyslny'] = htmlspecialchars($tn_prefix_m_val, ENT_QUOTES, 'UTF-8');
else $tn_bledy[] = "Prefix miejsca w magazynie jest wymagany i może zawierać tylko litery oraz cyfry.";

$tn_nowe_ustawienia['magazyn']['integracja_skanera_kodow'] = (isset($tn_magazyn_post_data['integracja_skanera_kodow']) && $tn_magazyn_post_data['integracja_skanera_kodow'] == '1');


// 5. Sekcja API i Integracje (grupa 'api_allegro' i 'integracje')
$tn_api_allegro_post = $_POST['api_allegro'] ?? [];
if (!isset($tn_nowe_ustawienia['api_allegro'])) $tn_nowe_ustawienia['api_allegro'] = [];
$tn_nowe_ustawienia['api_allegro']['client_id'] = trim($tn_api_allegro_post['client_id'] ?? '');
$tn_nowe_ustawienia['api_allegro']['client_secret'] = trim($tn_api_allegro_post['client_secret'] ?? ''); // Hasła/sekrety lepiej nie sanitizować htmlspecialchars
$tn_nowe_ustawienia['api_allegro']['sandbox'] = (isset($tn_api_allegro_post['sandbox']) && $tn_api_allegro_post['sandbox'] == '1');

$tn_integracje_post = $_POST['integracje'] ?? [];
if (!isset($tn_nowe_ustawienia['integracje'])) $tn_nowe_ustawienia['integracje'] = [];
$tn_nowe_ustawienia['integracje']['inpost_api_key'] = trim($tn_integracje_post['inpost_api_key'] ?? '');
$tn_nowe_ustawienia['integracje']['inpost_sandbox'] = (isset($tn_integracje_post['inpost_sandbox']) && $tn_integracje_post['inpost_sandbox'] == '1');
$tn_nowe_ustawienia['integracje']['payment_pos_id'] = trim($tn_integracje_post['payment_pos_id'] ?? '');
$tn_nowe_ustawienia['integracje']['payment_api_key'] = trim($tn_integracje_post['payment_api_key'] ?? '');
$tn_nowe_ustawienia['integracje']['payment_sandbox'] = (isset($tn_integracje_post['payment_sandbox']) && $tn_integracje_post['payment_sandbox'] == '1');

// 6. Sekcja Powiadomienia (grupa 'powiadomienia')
$tn_powiadomienia_post = $_POST['powiadomienia'] ?? [];
if (!isset($tn_nowe_ustawienia['powiadomienia'])) $tn_nowe_ustawienia['powiadomienia'] = []; // Już może istnieć od admin_email
$tn_nowe_ustawienia['powiadomienia']['email_nowe_zamowienie'] = (isset($tn_powiadomienia_post['email_nowe_zamowienie']) && $tn_powiadomienia_post['email_nowe_zamowienie'] == '1');
$tn_nowe_ustawienia['powiadomienia']['email_niski_stan'] = (isset($tn_powiadomienia_post['email_niski_stan']) && $tn_powiadomienia_post['email_niski_stan'] == '1');

// 7. Sekcja Bezpieczeństwo (grupa 'bezpieczenstwo')
$tn_bezpieczenstwo_post = $_POST['bezpieczenstwo'] ?? [];
if (!isset($tn_nowe_ustawienia['bezpieczenstwo'])) $tn_nowe_ustawienia['bezpieczenstwo'] = [];
$session_timeout_val = filter_input(INPUT_POST, 'bezpieczenstwo[session_timeout]', FILTER_VALIDATE_INT, ['options' => ['min_range' => 5]]);
if ($session_timeout_val !== false) {
    $tn_nowe_ustawienia['bezpieczenstwo']['session_timeout'] = $session_timeout_val;
} elseif (isset($_POST['bezpieczenstwo']['session_timeout'])) { // Jeśli pole było wysłane, ale nie przeszło walidacji
    $tn_bledy[] = "Czas trwania sesji musi być liczbą całkowitą, minimum 5 minut.";
}
// 2FA jest 'disabled' w formularzu, więc nie będzie przesyłane, chyba że to zmienisz
// $tn_nowe_ustawienia['bezpieczenstwo']['enable_2fa'] = (isset($tn_bezpieczenstwo_post['enable_2fa']) && $tn_bezpieczenstwo_post['enable_2fa'] == '1');


// 8. Sekcja Konserwacja (grupa 'konserwacja')
$tn_konserwacja_post = $_POST['konserwacja'] ?? [];
if (!isset($tn_nowe_ustawienia['konserwacja'])) $tn_nowe_ustawienia['konserwacja'] = [];
$tn_nowe_ustawienia['konserwacja']['maintenance_mode'] = (isset($tn_konserwacja_post['maintenance_mode']) && $tn_konserwacja_post['maintenance_mode'] == '1');
$tn_nowe_ustawienia['konserwacja']['maintenance_message'] = htmlspecialchars(trim($tn_konserwacja_post['maintenance_message'] ?? ''), ENT_QUOTES, 'UTF-8');


// --- Zapis Danych ---
if (empty($tn_bledy)) {
    if (tn_zapisz_ustawienia(TN_PLIK_USTAWIENIA, $tn_nowe_ustawienia)) {
        tn_ustaw_komunikat_flash("Ustawienia zostały pomyślnie zapisane.", 'success');
    } else {
        tn_ustaw_komunikat_flash("Wystąpił krytyczny błąd podczas zapisu ustawień do pliku. Sprawdź uprawnienia serwera.", 'danger');
        error_log("Krytyczny błąd zapisu pliku ustawień: " . TN_PLIK_USTAWIENIA . ". Sprawdź uprawnienia.");
    }
} else {
    // Przygotuj listę błędów do wyświetlenia
    $komunikat_bledu = "Nie udało się zapisać ustawień z powodu następujących błędów:<ul class='list-unstyled mb-0'>";
    foreach ($tn_bledy as $blad) {
        $komunikat_bledu .= "<li><i class='bi bi-exclamation-circle-fill text-danger me-1'></i>" . htmlspecialchars($blad) . "</li>";
    }
    $komunikat_bledu .= "</ul>";
    tn_ustaw_komunikat_flash($komunikat_bledu, 'danger', false); // false - nie escapuj HTML, bo już zawiera znaczniki
}

// --- Przekierowanie ---
// Zawsze wracaj do strony ustawień, aby wyświetlić komunikaty i zaktualizowany formularz
header("Location: " . tn_generuj_url('settings'));
exit;

?>
