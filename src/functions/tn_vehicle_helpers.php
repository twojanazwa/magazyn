<?php
/**
 * ==============================================================================
 * index.php - Główny plik aplikacji (Router/Kontroler Frontowy)
 * tnApp
 * ==============================================================================
 * Wersja: 1.6.1 (Implementacja strony bazy pojazdów, poprawki routingu i błędów)
 *
 * Odpowiada za:
 * 1. Inicjalizację (konfiguracja, sesja)
 * 2. Ładowanie podstawowych funkcji pomocniczych
 * 3. Sprawdzenie autoryzacji i wstępny routing (logowanie vs aplikacja)
 * 4. Ładowanie pozostałych funkcji i głównych danych aplikacji (warunkowo)
 * 5. Obsługę akcji użytkownika (POST/GET) z walidacją CSRF (usunięta)
 * 6. Szczegółowy routing (mapowanie URL na widoki i parametry)
 * 7. Przygotowanie danych specyficznych dla wybranego widoku
 * 8. Renderowanie odpowiedniego widoku (strona logowania lub pełny layout aplikacji)
 */

// Wymusza ścisłą kontrolę typów - zalecane dla lepszej jakości kodu
declare(strict_types=1); // Ustawiono na 1

// Ustawienie raportowania błędów (dostosuj w środowisku produkcyjnym)
ini_set('display_errors', '1'); // Tymczasowo włącz wyświetlanie błędów na stronie do debugowania
ini_set('log_errors', '1');    // Włącz logowanie błędów
error_reporting(E_ALL);        // Raportuj wszystkie błędy PHP

// --- 1. Konfiguracja i Inicjalizacja Sesji ---
require_once 'config/tn_config.php'; // Ładuje konfigurację, stałe i uruchamia sesję

// Sprawdź, czy podstawowe stałe konfiguracyjne są zdefiniowane
if (!defined('TN_KORZEN_APLIKACJI') || !defined('TN_SCIEZKA_TEMPLATEK') || !defined('TN_SCIEZKA_SRC')) {
    http_response_code(500);
    error_log("Błąd krytyczny: Brak podstawowych stałych konfiguracyjnych (np. TN_KORZEN_APLIKACJI) w config/tn_config.php");
    die("Wystąpił błąd serwera podczas inicjalizacji aplikacji.");
}


// --- 2. Ładowanie WSZYSTKICH Funkcji Pomocniczych (/src/functions/) ---
// Ładujemy wszystkie funkcje na początku, aby były dostępne globalnie.
$tn_required_functions = [
    'tn_security_helpers.php',   // tn_generuj_token_csrf, tn_waliduj_token_csrf, tn_generuj_link_akcji_get
    'tn_flash_messages.php',     // tn_ustaw_komunikat_flash, tn_pobierz_i_wyczysc_komunikaty_flash
    'tn_url_helpers.php',        // tn_generuj_url
    'tn_data_helpers.php',       // tn_laduj_ustawienia, tn_laduj_uzytkownikow, tn_laduj_produkty, etc.
    'tn_image_helpers.php',      // tn_pobierz_sciezke_obrazka, tn_get_avatar_path
    'tn_warehouse_helpers.php',
    'tn_view_helpers.php'        // tn_generuj_link_sortowania, etc.
];
foreach ($tn_required_functions as $tn_file) {
    // Użyj stałej TN_KORZEN_APLIKACJI do budowania ścieżki
    $full_path = TN_KORZEN_APLIKACJI . '/src/functions/' . $tn_file;
    if (file_exists($full_path)) {
        require_once $full_path;
    } else {
        http_response_code(500);
        error_log("Błąd krytyczny: Brak pliku funkcji '{$full_path}'");
        die("Błąd serwera podczas ładowania funkcji aplikacji.");
    }
}

// --- 3. Ładowanie Ustawień Globalnych ---
// Upewnij się, że $tn_domyslne_ustawienia jest zdefiniowane w config.php
$tn_domyslne_ustawienia = $tn_domyslne_ustawienia ?? []; // Upewnij się, że zmienna istnieje
$tn_ustawienia_globalne = tn_laduj_ustawienia(TN_PLIK_USTAWIENIA ?? null, $tn_domyslne_ustawienia); // Dodano ?? null
$tn_app_name = htmlspecialchars($tn_ustawienia_globalne['nazwa_strony'] ?? 'tnApp', ENT_QUOTES, 'UTF-8');

// --- 4. Sprawdzenie Autoryzacji ---
$tn_is_logged_in = isset($_SESSION['tn_user_id']);

// --- 5. Ustalenie Żądanej Ścieżki URL ---
$tn_request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$tn_uri_path = parse_url($tn_request_uri, PHP_URL_PATH);
$tn_script_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

// Usuń katalog skryptu z początku ścieżki URI, aby uzyskać ścieżkę względną
$tn_relative_path = $tn_uri_path;
if (!empty($tn_script_dir) && str_starts_with($tn_uri_path, $tn_script_dir)) {
    $tn_relative_path = substr($tn_uri_path, strlen($tn_script_dir));
}
// Podziel ścieżkę względną na części
$tn_path_parts = explode('/', trim($tn_relative_path, '/'));

// Pobierz żądaną akcję (POST lub GET)
$tn_akcja = $_POST['action'] ?? $_GET['action'] ?? null;

// --- 6. Obsługa Akcji (Logowanie/Wylogowanie i inne) ---
// Akcje logowania i wylogowania mogą być dostępne bez pełnego ładowania danych aplikacji
$tn_is_login_action = ($tn_akcja === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST');
$tn_is_logout_action = ($tn_akcja === 'logout' && $_SERVER['REQUEST_METHOD'] === 'GET');

// Mapa akcji do plików obsługujących
$tn_mapa_akcji = [
    'login' => 'src/actions/tn_action_login.php',
    'logout' => 'src/actions/tn_action_logout.php',
    'save' => 'src/actions/tn_action_save_product.php',
    'delete_product' => 'src/actions/tn_action_delete_product.php',
    'save_order' => 'src/actions/tn_action_save_order.php',
    'delete_order' => 'src/actions/tn_action_delete_order.php',
    'import_products' => 'src/actions/tn_action_import_products.php',
    'assign_warehouse' => 'src/actions/tn_action_assign_warehouse.php',
    'clear_slot' => 'src/actions/tn_action_clear_warehouse_slot.php',
    'create_regal' => 'src/actions/tn_action_create_regal.php',
    'delete_regal' => 'src/actions/tn_action_delete_regal.php',
    'create_locations' => 'src/actions/tn_action_create_locations.php',
    'save_settings' => 'src/actions/tn_action_save_settings.php',
    'update_profile' => 'src/actions/tn_action_update_profile.php',
    'save_return' => 'src/actions/tn_action_save_return.php',
    'save_courier' => 'src/actions/tn_action_save_courier.php',
    'delete_courier' => 'src/actions/tn_action_delete_courier.php',
    'save_vehicle_associations' => 'src/actions/tn_action_save_vehicle_associations.php',
];

// Obsłuż akcję, jeśli została zdefiniowana
if ($tn_akcja !== null && isset($tn_mapa_akcji[$tn_akcja])) {
    $tn_plik_akcji = $tn_mapa_akcji[$tn_akcja];

    // --- Usunięto walidację tokenu CSRF ---
    // Walidacja tokenu CSRF dla akcji innych niż login/logout (GET/POST)
    // Akcje POST powinny zawsze wymagać walidacji CSRF. Akcje GET tylko jeśli zmieniają stan.
    // $is_state_changing_get = ($tn_akcja === 'delete_product' || $tn_akcja === 'delete_order' || $tn_akcja === 'delete_regal' || $tn_akcja === 'clear_slot' || $tn_akcja === 'delete_courier');
    // if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($_SERVER['REQUEST_METHOD'] === 'GET' && $is_state_changing_get)) {
    //      $submitted_token = $_POST['tn_csrf_token'] ?? $_GET['tn_csrf_token'] ?? null;
    //      if (!function_exists('tn_waliduj_token_csrf') || !tn_waliduj_token_csrf($submitted_token)) {
    //          error_log("Błąd CSRF: Nieprawidłowy lub brak tokenu dla akcji '{$tn_akcja}'. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
    //          if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash('Błąd bezpieczeństwa: Nieprawidłowy token CSRF.', 'danger');
    //          $redirect_after_csrf_error = $_SERVER['HTTP_REFERER'] ?? (function_exists('tn_generuj_url') ? tn_generuj_url('dashboard') : '/');
    //          header('Location: ' . $redirect_after_csrf_error);
    //          exit;
    //      }
    // }
    // --- Koniec usuniętej walidacji CSRF ---


    // Sprawdź, czy użytkownik jest zalogowany dla akcji wymagających autoryzacji (wszystkie poza login/logout)
    if (!$tn_is_logged_in && !$tn_is_login_action && !$tn_is_logout_action) {
        if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash('Dostęp zabroniony. Proszę się zalogować.', 'warning');
        header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('login_page') : '/logowanie'));
        exit;
    }

    // Dołącz plik akcji
    $full_action_path = TN_KORZEN_APLIKACJI . '/' . $tn_plik_akcji; // Użyj TN_KORZEN_APLIKACJI
    if (file_exists($full_action_path)) {
        require_once $full_action_path;
        // Plik akcji powinien sam obsłużyć przekierowanie po zakończeniu działania
        exit;
    } else {
        // Brak pliku akcji
        error_log("Błąd: Brak pliku akcji '{$full_action_path}' dla akcji '{$tn_akcja}'");
        if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Błąd serwera: Nie można przetworzyć żądania akcji.", 'danger');
        // Przekieruj na pulpit lub stronę, z której przyszło żądanie
        $redirect_after_action_error = $_SERVER['HTTP_REFERER'] ?? (function_exists('tn_generuj_url') ? tn_generuj_url('dashboard') : '/');
        header("Location: " . $redirect_after_action_error);
        exit;
    }
}
// Jeśli akcja była podana, ale nie znaleziono jej w mapie akcji
elseif ($tn_akcja !== null) {
    if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Nieznana akcja: " . htmlspecialchars($tn_akcja), 'warning');
    // Przekieruj na pulpit lub stronę, z której przyszło żądanie
    $redirect_after_unknown_action = $_SERVER['HTTP_REFERER'] ?? (function_exists('tn_generuj_url') ? tn_generuj_url('dashboard') : '/');
    header("Location: " . $redirect_after_unknown_action);
    exit;
}


// --- 7. Ustalenie Bieżącej Strony (Routing Widoku) ---
// Mapa ścieżek URL -> ID stron/widoków
// Klucze powinny być ścieżkami względnymi bez wiodących/końcowych slashy
$tn_page_map = [
    '' => 'dashboard', // Strona główna
    'logowanie' => 'login_page',
    'produkty' => 'products',
    'produkty/podglad' => 'product_preview', // Routing z parametrem ID będzie niżej
    'zamowienia' => 'orders',
    'zamowienia/podglad' => 'order_preview', // Routing z parametrem ID będzie niżej
    'magazyn' => 'warehouse_view',
    'ustawienia' => 'settings',
    'informacje' => 'info',
    'pomoc' => 'help',
    'profil' => 'profile',
    'zwroty' => 'returns_list',
    'zwroty/nowe' => 'return_form_new',
    'zwroty/edytuj' => 'return_form_edit', // Routing z parametrem ID będzie niżej
    'zwroty/podglad' => 'return_preview', // Routing z parametrem ID będzie niżej
    'kurierzy' => 'couriers_list',
    'produkty/zarzadzaj-pojazdami' => 'manage_vehicles', // Routing z parametrem ID będzie niżej
    'pojazdy' => 'vehicles', // Nowa strona bazy pojazdów
];

// Spróbuj dopasować ścieżkę URL do mapy stron
$tn_biezaca_strona_id = $tn_is_logged_in ? 'dashboard' : 'login_page'; // Domyślna strona

$path_string = trim($tn_relative_path, '/'); // Ścieżka bez wiodących/końcowych slashy
$matched_page_id = null;
$tn_id_podgladu = null;
$tn_id_zam_podgladu = null;
$tn_id_zwrotu_do_edycji = null;
$tn_id_zwrotu_do_podgladu = null;
$tn_id_produktu_do_zarzadzania_pojazdami = null;


// Najpierw sprawdź dokładne dopasowania ścieżek
if (isset($tn_page_map[$path_string])) {
    $matched_page_id = $tn_page_map[$path_string];
} else {
    // Sprawdź dopasowania ścieżek z parametrami (np. /produkty/podglad/{id})
    $first_part = $tn_path_parts[0] ?? '';
    $second_part = $tn_path_parts[1] ?? '';
    $third_part = $tn_path_parts[2] ?? ''; // Może być ID

    // Routing dla podglądu produktu
    if ($first_part === 'produkty' && $second_part === 'podglad' && isset($tn_path_parts[2])) {
        $id = filter_var($third_part, FILTER_VALIDATE_INT);
        if ($id !== false) {
            $tn_id_podgladu = $id;
            $matched_page_id = 'product_preview';
        } else {
            if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Błędne ID produktu w URL.", 'warning');
            // Przekierowanie na listę produktów, jeśli ID jest błędne
            header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('products') : '/produkty'));
            exit;
        }
    }
    // Routing dla podglądu zamówienia
    elseif ($first_part === 'zamowienia' && $second_part === 'podglad' && isset($tn_path_parts[2])) {
         $id = filter_var($third_part, FILTER_VALIDATE_INT);
         if ($id !== false) {
             $tn_id_zam_podgladu = $id;
             $matched_page_id = 'order_preview';
         } else {
             if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Błędne ID zamówienia w URL.", 'warning');
             header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('orders') : '/zamowienia'));
             exit;
         }
    }
    // Routing dla edycji zwrotu
    elseif ($first_part === 'zwroty' && $second_part === 'edytuj' && isset($tn_path_parts[2])) {
         $id = filter_var($third_part, FILTER_VALIDATE_INT);
         if ($id !== false) {
             $tn_id_zwrotu_do_edycji = $id;
             $matched_page_id = 'return_form_edit';
         } else {
             if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Błędne ID zgłoszenia zwrotu w URL.", 'warning');
             header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('returns_list') : '/zwroty'));
             exit;
         }
    }
    // Routing dla podglądu zwrotu
    elseif ($first_part === 'zwroty' && $second_part === 'podglad' && isset($tn_path_parts[2])) {
         $id = filter_var($third_part, FILTER_VALIDATE_INT);
         if ($id !== false) {
             $tn_id_zwrotu_do_podgladu = $id;
             $matched_page_id = 'return_preview';
         } else {
             if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Błędne ID zgłoszenia zwrotu w URL.", 'warning');
             header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('returns_list') : '/zwroty'));
             exit;
         }
    }
    // Routing dla zarządzania pojazdami z parametrem ID produktu
    elseif ($first_part === 'produkty' && $second_part === 'zarzadzaj-pojazdami' && isset($tn_path_parts[2])) {
         $id = filter_var($third_part, FILTER_VALIDATE_INT);
         if ($id !== false) {
             $tn_id_produktu_do_zarzadzania_pojazdami = $id;
             $matched_page_id = 'manage_vehicles';
             // Ustawiamy $tn_id_podgladu, ponieważ szablon manage_vehicles go używa do załadowania danych produktu
             $tn_id_podgladu = $tn_id_produktu_do_zarzadzania_pojazdami;
         } else {
             if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Błędne ID produktu do zarządzania pojazdami w URL.", 'warning');
             header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('products') : '/produkty'));
             exit;
         }
    }

    // Można dodać routing dla innych ścieżek z parametrami tutaj...

    // Fallback na podstawie parametru GET 'page' (mniej preferowane, ale zachowane dla kompatybilności)
    // Jeśli używasz czystych URL, ten blok może być usunięty lub używany tylko jako ostateczny fallback.
    elseif (isset($_GET['page']) && is_string($_GET['page']) && in_array($_GET['page'], $tn_page_map)) {
         $matched_page_id = $_GET['page'];
         // Jeśli używasz parametrów ID w $_GET (np. ?page=product_preview&id=123),
         // powinieneś je sparsować tutaj:
         // $tn_id_podgladu = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
         // ... i tak dalej dla innych typów ID.
    }
}

// Ustaw ostateczny ID bieżącej strony
if ($matched_page_id !== null) {
    $tn_biezaca_strona_id = $matched_page_id;
} else {
     // Obsługa nieznanej ścieżki - jeśli nie dopasowano żadnej ścieżki ani parametru 'page'
     $path_was_empty = empty($path_string); // Sprawdź, czy ścieżka była pusta (czyli strona główna)
     $get_page_was_set = isset($_GET['page']); // Sprawdź, czy parametr 'page' był ustawiony

     // Jeśli ścieżka nie była pusta i nie była 'index.php', i nie było parametru 'page',
     // to jest to naprawdę nieznana ścieżka.
     if (!$path_was_empty && $first_part !== 'index.php' && !$get_page_was_set) {
         if ($tn_is_logged_in) {
             if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Nieznana ścieżka: /" . htmlspecialchars($path_string), 'warning');
             header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('dashboard') : '/')); // Przekieruj na pulpit
             exit;
         } else {
             // Niezalogowany użytkownik na nieznanej ścieżce - przekieruj na logowanie
             header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('login_page') : '/logowanie'));
             exit;
         }
     }
     // Jeśli ścieżka była pusta, lub był parametr 'page' (nawet jeśli nie dopasowano),
     // pozostaw domyślną stronę ustaloną na początku ($tn_is_logged_in ? 'dashboard' : 'login_page').
}


// --- 8. Przekierowania Autoryzacji (po ustaleniu strony) ---
// Jeśli użytkownik jest zalogowany, ale próbuje wejść na stronę logowania, przekieruj na pulpit.
if ($tn_is_logged_in && $tn_biezaca_strona_id === 'login_page') {
    header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('dashboard') : '/'));
    exit;
}
// Jeśli użytkownik nie jest zalogowany, ale próbuje wejść na stronę aplikacji (inną niż logowanie), przekieruj na logowanie.
if (!$tn_is_logged_in && $tn_biezaca_strona_id !== 'login_page') {
    // Komunikat flash zostanie ustawiony przy próbie wykonania akcji lub przy wejściu na stronę
    header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('login_page') : '/logowanie'));
    exit;
}
// --- Koniec Przekierowań Autoryzacji ---


// --- 9. Ładowanie Danych Aplikacji (warunkowo dla zalogowanych i stron aplikacji) ---
$tn_produkty = []; $tn_zamowienia = []; $tn_stan_magazynu = []; $tn_regaly = []; $tn_uzytkownicy = []; $tn_zwroty = []; $tn_kurierzy = [];
$load_app_data = $tn_is_logged_in && $tn_biezaca_strona_id !== 'login_page';
$load_users_data = $tn_is_logged_in || $tn_is_login_action || $tn_biezaca_strona_id === 'profile'; // Ładuj dane użytkowników dla logowania i profilu

// Ładuj pliki logiki przetwarzania danych (jeśli potrzebne dla bieżącej strony)
// Sprawdź istnienie plików przed dołączeniem
if ($load_app_data) {
    if (file_exists(TN_SCIEZKA_SRC . 'logic/tn_product_processing.php')) require_once TN_SCIEZKA_SRC . 'logic/tn_product_processing.php';
    if (file_exists(TN_SCIEZKA_SRC . 'logic/tn_order_processing.php')) require_once TN_SCIEZKA_SRC . 'logic/tn_order_processing.php';
    if (file_exists(TN_SCIEZKA_SRC . 'logic/tn_return_processing.php')) {
        require_once TN_SCIEZKA_SRC . 'logic/tn_return_processing.php';
    } else {
        // Fallback dla funkcji przetwarzania zwrotów, jeśli plik nie istnieje
        if (!function_exists('tn_przetworz_liste_zwrotow')) {
             function tn_przetworz_liste_zwrotow($z, $g, $n) {
                 error_log("Brak pliku src/logic/tn_return_processing.php - użyto fallbacku dla tn_przetworz_liste_zwrotow");
                 return ['zwroty_wyswietlane' => $z, 'ilosc_wszystkich' => count($z), 'biezaca_strona' => 1, 'sortowanie' => 'date_desc'];
             }
        }
    }
}

// Ładuj dane JSON (warunkowo)
// Sprawdź istnienie funkcji ładowania danych przed wywołaniem
if ($load_users_data && function_exists('tn_laduj_uzytkownikow')) {
    $tn_uzytkownicy = tn_laduj_uzytkownikow(TN_PLIK_UZYTKOWNICY ?? null);
    if ($tn_uzytkownicy === false) {
         error_log("Błąd ładowania danych użytkowników z " . (TN_PLIK_UZYTKOWNICY ?? 'brak ścieżki'));
         $tn_uzytkownicy = [];
         if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Błąd ładowania danych użytkowników.", 'danger');
    }
}
if ($load_app_data) {
    if (function_exists('tn_laduj_produkty')) {
        $tn_produkty = tn_laduj_produkty(TN_PLIK_PRODUKTY ?? null);
        if ($tn_produkty === false) {
            error_log("Błąd ładowania danych produktów z " . (TN_PLIK_PRODUKTY ?? 'brak ścieżki'));
            $tn_produkty = [];
            if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Błąd ładowania danych produktów.", 'danger');
        }
    } else {
         error_log("Błąd: Funkcja tn_laduj_produkty nie istnieje.");
         $tn_produkty = []; // Ustaw pustą tablicę, jeśli funkcja nie istnieje
         if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Błąd: Funkcja ładowania produktów niedostępna.", 'danger');
    }

    if (function_exists('tn_laduj_zamowienia')) {
        $tn_zamowienia = tn_laduj_zamowienia(TN_PLIK_ZAMOWIENIA ?? null);
        if ($tn_zamowienia === false) { error_log("Błąd ładowania danych zamówień z " . (TN_PLIK_ZAMOWIENIA ?? 'brak ścieżki')); $tn_zamowienia = []; if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Błąd ładowania danych zamówień.", 'danger'); }
    }
    if (function_exists('tn_laduj_magazyn')) {
        $tn_stan_magazynu = tn_laduj_magazyn(TN_PLIK_MAGAZYN ?? null);
        if ($tn_stan_magazynu === false) { error_log("Błąd ładowania danych magazynu z " . (TN_PLIK_MAGAZYN ?? 'brak ścieżki')); $tn_stan_magazynu = []; if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Błąd ładowania danych magazynu.", 'danger'); }
    }
    if (function_exists('tn_laduj_regaly')) {
        $tn_regaly = tn_laduj_regaly(TN_PLIK_REGALY ?? null);
        if ($tn_regaly === false) { error_log("Błąd ładowania danych regałów z " . (TN_PLIK_REGALY ?? 'brak ścieżki')); $tn_regaly = []; if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Błąd ładowania danych regałów.", 'danger'); }
    }
    if (function_exists('tn_laduj_zwroty')) {
        $tn_zwroty = tn_laduj_zwroty(TN_PLIK_ZWROTY ?? null);
        if ($tn_zwroty === false) { error_log("Błąd ładowania danych zwrotów z " . (TN_PLIK_ZWROTY ?? 'brak ścieżki')); $tn_zwroty = []; if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Błąd ładowania danych zwrotów.", 'danger'); }
    }
    if (function_exists('tn_laduj_kurierow')) {
        $tn_kurierzy = tn_laduj_kurierow(TN_PLIK_KURIERZY ?? null);
        if ($tn_kurierzy === false) { error_log("Błąd ładowania danych kurierów z " . (TN_PLIK_KURIERZY ?? 'brak ścieżki')); $tn_kurierzy = []; if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Błąd ładowania danych kurierów.", 'danger'); }
    }

    // Upewnij się, że dane użytkowników są załadowane, jeśli są potrzebne w aplikacji (np. dla wyświetlania nazw użytkowników w zamówieniach/zwrotach)
    if (empty($tn_uzytkownicy) && function_exists('tn_laduj_uzytkownikow')) {
         $tn_uzytkownicy = tn_laduj_uzytkownikow(TN_PLIK_UZYTKOWNICY ?? null);
         if ($tn_uzytkownicy === false) { error_log("Błąd ładowania danych użytkowników (fallback) z " . (TN_PLIK_UZYTKOWNICY ?? 'brak ścieżki')); $tn_uzytkownicy = []; }
    }
}

// Inicjalizacja zmiennych dla widoków specyficznych (ustawiane w sekcji 10)
$tn_dane_uzytkownika = null;
$tn_edytowany_zwrot = null;
$tn_produkt_podgladu = null; // Używane dla product_preview and manage_vehicles
$tn_zamowienie_podgladu = null;
$tn_zwrot_podgladu = null;
$tn_produkty_dane = null; // Dane po przetworzeniu dla listy produktów
$tn_zamowienia_dane = null; // Dane po przetworzeniu dla listy zamówień
$tn_zwroty_dane = null; // Dane po przetworzeniu dla listy zwrotów
$tn_lista_pojazdow = []; // Variable for the vehicles list page


// --- 10. Przygotowanie Danych Specyficznych dla Widoku ---
// Pobierz parametry GET z URL (np. ?p=2&sort=name_asc)
$tn_get_params = $_GET; // $_GET is already parsed

if ($tn_is_logged_in && $tn_biezaca_strona_id !== 'login_page') {
    switch ($tn_biezaca_strona_id) {
        case 'product_preview':
        case 'manage_vehicles': // manage_vehicles also needs product data
            if ($tn_id_podgladu !== null && !empty($tn_produkty)) {
                 // Find product by ID
                 $found_product = false;
                 foreach ($tn_produkty as $p) {
                     if (isset($p['id']) && (int)$p['id'] === $tn_id_podgladu) {
                         $tn_produkt_podgladu = $p;
                         $found_product = true;
                         break;
                     }
                 }
                 // If product not found, redirect
                 if (!$found_product) {
                     if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Nie znaleziono produktu o ID: " . htmlspecialchars((string)$tn_id_podgladu), 'warning');
                     header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('products') : '/produkty'));
                     exit;
                 }
            } else {
                 // Missing ID in URL or no product data - redirect
                 if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Brak ID produktu w URL lub dane produktów są niedostępne.", 'warning');
                 header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('products') : '/produkty'));
                 exit;
            }
            break;

        case 'products':
            // Processing data for the product list (filtering, sorting, pagination)
            // Added debug before processing
            echo "\n";
            // var_dump($tn_produkty); // Uncomment to see full product array content

            $items_per_page = (int)($tn_ustawienia_globalne['produkty_na_stronie'] ?? 10) ?: 10; // Default 10
            if (function_exists('tn_przetworz_liste_produktow')) {
                 $tn_produkty_dane = tn_przetworz_liste_produktow($tn_produkty, $tn_get_params, $items_per_page);

                 // Added debug after processing
                 echo "\n";
                 // var_dump($tn_produkty_dane); // Uncomment to see processed data

            } else {
                 error_log("Błąd: Funkcja tn_przetworz_liste_produktow nie istnieje.");
                 $tn_produkty_dane = ['produkty_wyswietlane' => $tn_produkty, 'ilosc_wszystkich' => count($tn_produkty), 'biezaca_strona' => 1, 'sortowanie' => 'id_asc']; // Fallback
                 if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Błąd przetwarzania listy produktów.", 'danger');
            }
            break;

        case 'order_preview':
            if ($tn_id_zam_podgladu !== null && !empty($tn_zamowienia)) {
                $found_order = false;
                foreach ($tn_zamowienia as $z) {
                    if (isset($z['id']) && (int)$z['id'] === $tn_id_zam_podgladu) {
                        $tn_zamowienie_podgladu = $z;
                        $found_order = true;
                        break;
                    }
                }
                if (!$found_order) {
                    if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Nie znaleziono zamówienia o ID: " . htmlspecialchars((string)$tn_id_zam_podgladu), 'warning');
                    header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('orders') : '/zamowienia'));
                    exit;
                }
            } else {
                 if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Brak ID zamówienia w URL lub dane zamówień są niedostępne.", 'warning');
                 header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('orders') : '/zamowienia'));
                 exit;
            }
            break;

        case 'orders':
            // Processing data for the order list
            $items_per_page = (int)($tn_ustawienia_globalne['zamowienia_na_stronie'] ?? 10) ?: 10; // Default 10
             if (function_exists('tn_przetworz_liste_zamowien')) {
                $tn_zamowienia_dane = tn_przetworz_liste_zamowien($tn_zamowienia, $tn_produkty, $tn_get_params, $items_per_page);
             } else {
                 error_log("Błąd: Funkcja tn_przetworz_liste_zamowien nie istnieje.");
                 $tn_zamowienia_dane = ['zamowienia_wyswietlane' => $tn_zamowienia, 'ilosc_wszystkich' => count($tn_zamowienia), 'biezaca_strona' => 1, 'sortowanie' => 'date_desc']; // Fallback
                 if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Błąd przetwarzania listy zamówień.", 'danger');
             }
            break;

        case 'return_form_edit':
            if ($tn_id_zwrotu_do_edycji !== null && !empty($tn_zwroty)) {
                $found_return = false;
                foreach ($tn_zwroty as $z) {
                    if (isset($z['id']) && (int)$z['id'] === $tn_id_zwrotu_do_edycji) {
                        $tn_edytowany_zwrot = $z;
                        $found_return = true;
                        break;
                    }
                }
                if (!$found_return) {
                    if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Nie znaleziono zgłoszenia zwrotu o ID: " . htmlspecialchars((string)$tn_id_zwrotu_do_edycji), 'warning');
                    header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('returns_list') : '/zwroty'));
                    exit;
                }
            } else {
                 if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Brak ID zgłoszenia zwrotu w URL lub dane zwrotów są niedostępne.", 'warning');
                 header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('returns_list') : '/zwroty'));
                 exit;
            }
            break;

        case 'return_preview':
            if ($tn_id_zwrotu_do_podgladu !== null && !empty($tn_zwroty)) {
                $found_return = false;
                foreach ($tn_zwroty as $z) {
                    if (isset($z['id']) && (int)$z['id'] === $tn_id_zwrotu_do_podgladu) {
                        $tn_zwrot_podgladu = $z;
                        $found_return = true;
                        break;
                    }
                }
                if (!$found_return) {
                    if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Nie znaleziono zgłoszenia zwrotu o ID: " . htmlspecialchars((string)$tn_id_zwrotu_do_podgladu), 'warning');
                    header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('returns_list') : '/zwroty'));
                    exit;
                }
            } else {
                 if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Brak ID zgłoszenia zwrotu w URL lub dane zwrotów są niedostępne.", 'warning');
                 header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('returns_list') : '/zwroty'));
                 exit;
            }
            break;

        case 'returns_list':
            // Processing data for the returns list
            $items_per_page = (int)($tn_ustawienia_globalne['zwroty_na_stronie'] ?? 15) ?: 15; // Default 15
            if (function_exists('tn_przetworz_liste_zwrotow')) {
                 $tn_zwroty_dane = tn_przetworz_liste_zwrotow($tn_zwroty, $tn_get_params, $items_per_page);
            } else {
                 error_log("Błąd: Funkcja tn_przetworz_liste_zwrotow nie istnieje.");
                 $tn_zwroty_dane = ['zwroty_wyswietlane' => $tn_zwroty, 'ilosc_wszystkich' => count($tn_zwroty), 'biezaca_strona' => 1, 'sortowanie' => 'date_desc']; // Fallback
                 if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Błąd przetwarzania listy zwrotów.", 'danger');
            }
            break;

        case 'profile':
            $user_id = $_SESSION['tn_user_id'] ?? null;
            if ($user_id !== null && !empty($tn_uzytkownicy)) {
                $found_user = false;
                foreach ($tn_uzytkownicy as $u) {
                    if (isset($u['id']) && (int)$u['id'] === (int)$user_id) {
                        $tn_dane_uzytkownika = $u;
                        $found_user = true;
                        break;
                    }
                }
            }
            // If user data not found (e.g., deleted user), log out
            if (!$tn_dane_uzytkownika) {
                error_log("Błąd: Dane użytkownika ID '" . htmlspecialchars((string)($user_id ?? 'N/A')) . "' nie znaleziono.");
                session_destroy(); // Destroy session
                // Set flash message before redirect
                if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash('Twoje konto użytkownika nie zostało znalezione. Proszę zalogować się ponownie.', 'danger');
                header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('login_page') : '/logowanie'));
                exit;
            }
            break;

        case 'warehouse_view':
            // Warehouse data is already loaded ($tn_stan_magazynu, $tn_regaly)
            // Add filtering/sorting logic for warehouse view here if needed
            break;

        case 'settings':
            // Settings are already loaded ($tn_ustawienia_globalne)
            break;

        case 'couriers_list':
            // Courier data is already loaded ($tn_kurierzy)
            break;

        case 'vehicles': // Data preparation for the vehicles list page
            $all_vehicles_raw = [];
            if (!empty($tn_produkty)) {
                foreach ($tn_produkty as $product) {
                    if (!empty($product['vehicle'])) {
                        // Collect all entries from the 'vehicle' field from all products
                        $all_vehicles_raw[] = $product['vehicle'];
                    }
                }
            }

            // Combine all raw vehicle data into a single string and parse
            $combined_vehicle_info_raw = implode("\n", $all_vehicles_raw);
            $tn_lista_pojazdow = [];
            $currentMakeModel = null;

            // Regex for parsing vehicle version lines (identical to product preview template)
            $versionRegex = '/^\s*(.+?)\s+\(([^)]+?)\)\s*,\s*(\d*)\s*,\s*(\d*)\s*,\s*(\d*)\s*,\s*(\d{4}\.\d{2})\s*-\s*(.*?)\s*$/';

            $lines = explode("\n", trim($combined_vehicle_info_raw));
            $unique_vehicles_map = []; // Map to store unique vehicles (Make/Model => [versions])

            foreach ($lines as $line) {
                $trimmedLine = trim($line);
                if (empty($trimmedLine)) continue;

                // Check if it's a make/model line
                if (str_ends_with($trimmedLine, ':') && !preg_match('/^\s/', $line)) {
                    $currentMakeModel = htmlspecialchars(trim(substr($trimmedLine, 0, -1)), ENT_QUOTES, 'UTF-8');
                    if (!isset($unique_vehicles_map[$currentMakeModel])) {
                        $unique_vehicles_map[$currentMakeModel] = [];
                    }
                }
                // Check if it's a vehicle version line
                elseif ($currentMakeModel !== null && preg_match('/^\s+/', $line, $matches)) { // Corrected regex match
                    // Sanitize and format version data
                    $version_data = [
                        'name' => htmlspecialchars(trim($matches[1] ?? ''), ENT_QUOTES, 'UTF-8'),
                        'code' => htmlspecialchars(trim($matches[2] ?? ''), ENT_QUOTES, 'UTF-8'),
                        'capacity' => htmlspecialchars(trim($matches[3] ?? ''), ENT_QUOTES, 'UTF-8') ?: '-',
                        'kw' => htmlspecialchars(trim($matches[4] ?? ''), ENT_QUOTES, 'UTF-8') ?: '-',
                        'hp' => htmlspecialchars(trim($matches[5] ?? ''), ENT_QUOTES, 'UTF-8') ?: '-',
                        'year_start' => htmlspecialchars(trim($matches[6] ?? ''), ENT_QUOTES, 'UTF-8'),
                        'year_end' => htmlspecialchars(trim($matches[7] ?? ''), ENT_QUOTES, 'UTF-8') ?: 'nadal',
                    ];

                    // Create a unique key for each vehicle version to avoid duplicates
                    $version_key = implode('|', $version_data);

                    // Add the version only if it's not already present for the given Make/Model
                    $is_duplicate = false;
                    foreach($unique_vehicles_map[$currentMakeModel] as $existing_version) {
                        if (implode('|', $existing_version) === $version_key) {
                            $is_duplicate = true;
                            break;
                        }
                    }
                    if (!$is_duplicate) {
                         $unique_vehicles_map[$currentMakeModel][] = $version_data;
                    }
                }
            }
             // Sort versions within each Make/Model (e.g., alphabetically by name)
             foreach($unique_vehicles_map as &$versions) {
                 usort($versions, fn($a, $b) => strcmp($a['name'], $b['name']));
             }
             unset($versions); // Remove reference

            $tn_lista_pojazdow = $unique_vehicles_map; // Assign unique, parsed data to the view variable

            break;


        default:
            // Pages that do not require specific data processing (e.g., dashboard, info, help, return_form_new)
            break;
    }
}


// --- 11. Set Page Title and View File Path ---
$tn_plik_widoku = '';
$tn_tytul_strony = '';
$tn_aktywny_identyfikator_strony = $tn_biezaca_strona_id; // Used to highlight in menu

// Map page ID to view file path and title
$tn_view_config = [
    'dashboard' => ['file' => 'pages/tn_dashboard.php', 'title' => 'Pulpit'],
    'login_page' => ['file' => 'pages/tn_login_page.php', 'title' => 'Logowanie'],
    'products' => ['file' => 'pages/tn_products_list.php', 'title' => 'Produkty'],
    'product_preview' => ['file' => 'pages/tn_product_preview.php', 'title' => ($tn_produkt_podgladu ? htmlspecialchars($tn_produkt_podgladu['name'], ENT_QUOTES, 'UTF-8') : 'Podgląd Produktu')],
    'orders' => ['file' => 'pages/tn_orders_list.php', 'title' => 'Zamówienia'],
    'order_preview' => ['file' => 'pages/tn_order_preview.php', 'title' => ($tn_zamowienie_podgladu ? 'Zam. #' . htmlspecialchars((string)$tn_zamowienie_podgladu['id'], ENT_QUOTES, 'UTF-8') : 'Podgląd Zamówienia')],
    'warehouse_view' => ['file' => 'pages/tn_warehouse_view.php', 'title' => 'Widok Magazynu'],
    'settings' => ['file' => 'pages/tn_settings_form.php', 'title' => 'Ustawienia'],
    'info' => ['file' => 'pages/tn_info_page.php', 'title' => 'Informacje'],
    'help' => ['file' => 'pages/tn_help_page.php', 'title' => 'Pomoc'],
    'profile' => ['file' => 'pages/tn_profile.php', 'title' => 'Mój Profil'],
    'returns_list' => ['file' => 'pages/tn_returns_list.php', 'title' => 'Zwroty i Reklamacje'],
    'return_form_new' => ['file' => 'pages/tn_return_form.php', 'title' => 'Nowe Zgłoszenie Zwrotu'],
    'return_form_edit' => ['file' => 'pages/tn_return_form.php', 'title' => 'Edytuj Zgłoszenie #' . ($tn_edytowany_zwrot['id'] ?? '?')],
    'return_preview' => ['file' => 'pages/tn_return_preview.php', 'title' => ($tn_zwrot_podgladu ? 'Zgłoszenie #' . htmlspecialchars((string)$tn_zwrot_podgladu['id'], ENT_QUOTES, 'UTF-8') : 'Podgląd Zgłoszenia')],
    'couriers_list' => ['file' => 'pages/tn_couriers_list.php', 'title' => 'Kurierzy'],
    'manage_vehicles' => ['file' => 'pages/tn_manage_vehicles.php', 'title' => 'Zarządzaj pojazdami dla ' . ($tn_produkt_podgladu ? htmlspecialchars($tn_produkt_podgladu['name'], ENT_QUOTES, 'UTF-8') : 'Produktu')],
    'vehicles' => ['file' => 'pages/tn_vehicles_list.php', 'title' => 'Baza Pojazdów'], // Configuration for the new page
];

if (isset($tn_view_config[$tn_biezaca_strona_id])) {
    $tn_plik_widoku = $tn_view_config[$tn_biezaca_strona_id]['file'];
    $tn_tytul_strony = $tn_view_config[$tn_biezaca_strona_id]['title'];
} else {
    // Fallback for unknown page IDs (should be handled earlier, but just in case)
    error_log("Fallback: Unknown page ID: '{$tn_biezaca_strona_id}'");
    $tn_biezaca_strona_id = $tn_is_logged_in ? 'dashboard' : 'login_page';
    $tn_plik_widoku = $tn_is_logged_in ? 'pages/tn_dashboard.php' : 'pages/tn_login_page.php';
    $tn_tytul_strony = ($tn_is_logged_in ? 'Pulpit' : 'Logowanie');
    if (function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Wystąpił błąd routingu.", 'danger');
}

// Add app name to page title (if not login page)
if ($tn_biezaca_strona_id !== 'login_page') {
    $tn_tytul_strony .= ' - ' . $tn_app_name;
}

$tn_pelna_sciezka_widoku = TN_SCIEZKA_TEMPLATEK . $tn_plik_widoku;


// --- 12. Render View ---

// Check if view file exists
if (!file_exists($tn_pelna_sciezka_widoku)) {
    http_response_code(500);
    error_log("Błąd: Brak pliku szablonu widoku: '{$tn_pelna_sciezka_widoku}'");
    die("Wystąpił błąd serwera podczas ładowania strony.");
}

// Get and clear flash messages to display
$tn_komunikaty_flash = function_exists('tn_pobierz_i_wyczysc_komunikaty_flash') ? tn_pobierz_i_wyczysc_komunikaty_flash() : [];

// Render login page or full app layout
if ($tn_biezaca_strona_id === 'login_page') {
    // Login page has its own simplified layout
    // Pass app name to login template
    $tn_site_name = $tn_app_name;
    require $tn_pelna_sciezka_widoku;

} elseif ($tn_is_logged_in) {
    // Full app layout for logged-in users
    // Load layout partials
    $header_partial_path = TN_SCIEZKA_TEMPLATEK . 'partials/tn_header.php';
    if (file_exists($header_partial_path)) {
        require $header_partial_path; // Includes <html> and <head> tags
    } else {
        http_response_code(500);
        error_log("Błąd: Brak pliku partiala nagłówka: {$header_partial_path}");
        die("Wystąpił błąd serwera podczas ładowania nagłówka.");
    }

    $sidebar_partial_path = TN_SCIEZKA_TEMPLATEK . 'partials/tn_sidebar.php';
    if (file_exists($sidebar_partial_path)) {
        require $sidebar_partial_path;
    } else {
        error_log("Błąd: Brak pliku partiala paska bocznego: {$sidebar_partial_path}");
        // Continue without sidebar if possible
    }


    echo '<div class="tn-glowna-czesc d-flex flex-column min-vh-100">'; // Main content and footer container

    $topbar_partial_path = TN_SCIEZKA_TEMPLATEK . 'partials/tn_topbar.php';
    if (file_exists($topbar_partial_path)) {
        require $topbar_partial_path;
    } else {
         error_log("Błąd: Brak pliku partiala górnego paska: {$topbar_partial_path}");
         // Continue without top bar if possible
    }


    // Display flash messages in a dedicated container
    if (!empty($tn_komunikaty_flash)) {
        echo '<div class="tn-flash-container position-fixed top-0 end-0 p-3" style="z-index: 1056;">';
        foreach ($tn_komunikaty_flash as $tn_k) {
            $type = htmlspecialchars($tn_k['type'] ?? 'info', ENT_QUOTES, 'UTF-8');
            $message = htmlspecialchars($tn_k['message'] ?? 'Nieznany komunikat.', ENT_QUOTES, 'UTF-8'); // Sanitize message
            $icon_map = [
                'success' => 'bi-check-circle-fill',
                'danger' => 'bi-exclamation-octagon-fill',
                'warning' => 'bi-exclamation-triangle-fill',
                'info' => 'bi-info-circle-fill',
            ];
            $icon_class = $icon_map[$type] ?? 'bi-info-circle-fill';

            echo "<div class='alert alert-{$type} alert-dismissible fade show d-flex align-items-center shadow-sm mb-2' role='alert'>";
            echo "<i class='bi {$icon_class} me-2 fs-6 flex-shrink-0'></i>"; // Icon
            echo "<div class='flex-grow-1'>{$message}</div>"; // Message
            echo "<button type='button' class='btn-close ms-auto p-2' data-bs-dismiss='alert' aria-label='Zamknij'></button>"; // Close button
            echo "</div>";
        }
        echo '</div>';
    }

    // Container for main page content
    echo '<main class="tn-kontener-tresci container-fluid flex-grow-1" id="tnMainContent">';

    // Include the view file for the current page
    require $tn_pelna_sciezka_widoku;

    echo '</main>'; // End main.tn-kontener-tresci

    // Include modals and footer partials
    $modals_partial_path = TN_SCIEZKA_TEMPLATEK . 'partials/tn_modals.php';
    if (file_exists($modals_partial_path)) {
        require $modals_partial_path;
    } else {
        error_log("Błąd: Brak pliku partiala modali: {$modals_partial_path}");
    }

    $footer_partial_path = TN_SCIEZKA_TEMPLATEK . 'partials/tn_footer.php';
    if (file_exists($footer_partial_path)) {
        require $footer_partial_path; // Assume footer closes </body> and </html> tags
    } else {
         error_log("Błąd: Brak pliku partiala stopki: {$footer_partial_path}");
         // If footer doesn't close tags, do it here as a fallback
         echo '</div>'; // Closes tn-glowna-czesc
         echo '</body>';
         echo '</html>';
    }

} else {
    // Logic should have redirected non-logged-in users to the login page earlier.
    // This case should not be reached with correct routing.
    error_log("Błąd logiki routingu: Niezalogowany użytkownik dotarł do sekcji renderowania aplikacji.");
    // Emergency redirect to login
    header('Location: ' . (function_exists('tn_generuj_url') ? tn_generuj_url('login_page') : '/logowanie'));
    exit;
}
// --- End Render View ---

// --- 10. Helper Functions for Views ---
// These functions are defined here for simplicity in this example,
// but ideally should reside in src/functions/tn_view_helpers.php
// and be loaded in section 2.
if (!function_exists('tn_generuj_link_sortowania')) {
    function tn_generuj_link_sortowania(string $tn_sortuj_po, string $tn_etykieta) : string {
        global $tn_produkty_dane;
        $s = $tn_produkty_dane ?? [];
        $p = 'products';
        $c = $s['sortowanie'] ?? 'name_asc';
        $q = $s['zapytanie_szukania'] ?? '';
        $pg = $s['biezaca_strona'] ?? 1;
        $f = $s['kategoria'] ?? '';
        // Determine sort order (asc/desc)
        $k = ($c === $tn_sortuj_po . '_a') ? 'd' : 'a';
        // Handle default sort for date if needed (as in other sort functions)
        // if ($tn_sortuj_po === 'date' && $c === 'date_desc') $k = 'a';
        // elseif ($tn_sortuj_po === 'date' && $c === 'date_asc') $k = 'd';

        $n = $tn_sortuj_po . '_' . $k;
        $i = ''; // Icon
        if ($c === $tn_sortuj_po . '_a') $i = ' <i class="bi bi-sort-up"></i>';
        elseif ($c === $tn_sortuj_po . '_d') $i = ' <i class="bi bi-sort-down"></i>';
        // Add icon for default date sort if needed
        // elseif ($tn_sortuj_po === 'date' && $c === 'date_desc') $i = ' <i class="bi bi-sort-down"></i>';

        $a = ['sort' => $n];
        if ($pg > 1) $a['p'] = $pg;
        if (!empty($q)) $a['search'] = $q;
        if (!empty($f)) $a['category'] = $f;

        $u = function_exists('tn_generuj_url') ? tn_generuj_url($p, $a) : '#'; // Use helper if exists

        return '<a href="' . htmlspecialchars((string)$u, ENT_QUOTES, 'UTF-8') . '" class="text-decoration-none link-body-emphasis">' . htmlspecialchars((string)$tn_etykieta, ENT_QUOTES, 'UTF-8') . $i . '</a>';
    }
}

if (!function_exists('tn_generuj_link_sortowania_zamowien')) {
    function tn_generuj_link_sortowania_zamowien(string $tn_sortuj_po, string $tn_etykieta) : string {
        global $tn_zamowienia_dane;
        $s = $tn_zamowienia_dane ?? [];
        $p = 'orders';
        $c = $s['sortowanie'] ?? 'date_desc';
        $f = $s['status'] ?? '';
        $pg = $s['biezaca_strona'] ?? 1;
        $k = ($c === $tn_sortuj_po . '_a') ? 'd' : 'a';
        if ($tn_sortuj_po === 'date' && $c === 'date_desc') $k = 'a';
        elseif ($tn_sortuj_po === 'date' && $c === 'date_asc') $k = 'd';

        $n = $tn_sortuj_po . '_' . $k;
        $i = '';
        if ($c === $tn_sortuj_po . '_a') $i = ' <i class="bi bi-sort-up"></i>';
        elseif ($c === $tn_sortuj_po . '_d') $i = ' <i class="bi bi-sort-down"></i>';
        elseif ($tn_sortuj_po === 'date' && $c === 'date_desc') $i = ' <i class="bi bi-sort-down"></i>';

        $a = ['sort' => $n];
        if (!empty($f)) $a['status'] = $f;
        if ($pg > 1) $a['p'] = $pg;

        $u = function_exists('tn_generuj_url') ? tn_generuj_url($p, $a) : '#'; // Use helper if exists

        return '<a href="' . htmlspecialchars((string)$u, ENT_QUOTES, 'UTF-8') . '" class="text-decoration-none link-body-emphasis">' . htmlspecialchars((string)$tn_etykieta, ENT_QUOTES, 'UTF-8') . $i . '</a>';
    }
}

if (!function_exists('tn_generuj_link_sortowania_zwrotow')) {
    function tn_generuj_link_sortowania_zwrotow(string $tn_sortuj_po, string $tn_etykieta) : string {
        global $tn_zwroty_dane;
        $s = $tn_zwroty_dane ?? [];
        $p = 'returns_list';
        $c = $s['sortowanie'] ?? 'date_desc';
        $pg = $s['biezaca_strona'] ?? 1;
        $k = ($c === $tn_sortuj_po . '_a') ? 'd' : 'a';
        if ($tn_sortuj_po === 'date' && $c === 'date_desc') $k = 'a';
        elseif ($tn_sortuj_po === 'date' && $c === 'date_asc') $k = 'd';

        $n = $tn_sortuj_po . '_' . $k;
        $i = '';
        if ($c === $tn_sortuj_po . '_a') $i = ' <i class="bi bi-sort-up"></i>';
        elseif ($c === $tn_sortuj_po . '_d') $i = ' <i class="bi bi-sort-down"></i>';
        elseif ($tn_sortuj_po === 'date' && $c === 'date_desc') $i = ' <i class="bi bi-sort-down"></i>';

        $a = ['sort' => $n];
        if ($pg > 1) $a['p'] = $pg;
        /* TODO: Filtry */

        $u = function_exists('tn_generuj_url') ? tn_generuj_url($p, $a) : '#'; // Use helper if exists

        return '<a href="' . htmlspecialchars((string)$u, ENT_QUOTES, 'UTF-8') . '" class="text-decoration-none link-body-emphasis">' . htmlspecialchars((string)$tn_etykieta, ENT_QUOTES, 'UTF-8') . $i . '</a>';
    }
}

?>
