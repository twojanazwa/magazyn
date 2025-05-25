<?php
/**
 * ==============================================================================
 * index.php - Główny plik aplikacji (Router/Kontroler Frontowy)
 * tnApp
 * ==============================================================================
 * Wersja: 1.5.0 (Zmodyfikowana o fragmenty do obsługi pojazdów)
 *
 * Odpowiada za:
 * 1. Inicjalizację (konfiguracja, sesja)
 * 2. Ładowanie podstawowych funkcji pomocniczych
 * 3. Sprawdzenie autoryzacji i wstępny routing (logowanie vs aplikacja)
 * 4. Ładowanie pozostałych funkcji i głównych danych aplikacji (warunkowo)
 * 5. Obsługę akcji użytkownika (POST/GET) z walidacją CSRF
 * 6. Szczegółowy routing (mapowanie URL na widoki i parametry)
 * 7. Przygotowanie danych specyficznych dla wybranego widoku
 * 8. Renderowanie odpowiedniego widoku (strona logowania lub pełny layout aplikacji)
 */

declare(strict_types=0); // Oryginalne ustawienie użytkownika

// --- 1. Konfiguracja i Inicjalizacja Sesji ---
require_once 'config/tn_config.php'; // Ładuje konfigurację, stałe i uruchamia sesję

// --- 2. Ładowanie WSZYSTKICH Funkcji Pomocniczych (/src/functions/) ---
$tn_required_functions = [
    'src/functions/tn_security_helpers.php',
    'src/functions/tn_flash_messages.php',
    'src/functions/tn_url_helpers.php',
    'src/functions/tn_data_helpers.php',
    'src/functions/tn_image_helpers.php',
    'src/functions/tn_warehouse_helpers.php',
    'src/functions/tn_view_helpers.php',
    // NOWOŚĆ: Rozważ dodanie helpera dla pojazdów, jeśli logika przetwarzania danych pojazdów stanie się bardziej złożona.
    // np. 'src/functions/tn_vehicle_helpers.php', 
];
foreach ($tn_required_functions as $tn_file) {
    if (file_exists($tn_file)) { require_once $tn_file; }
    else { http_response_code(500); error_log("Błąd krytyczny: Brak pliku funkcji '{$tn_file}'"); die("Błąd serwera."); }
}

// --- 3. Ładowanie Ustawień Globalnych ---
$tn_ustawienia_globalne = tn_laduj_ustawienia(TN_PLIK_USTAWIENIA, $tn_domyslne_ustawienia);
$tn_app_name = $tn_ustawienia_globalne['nazwa_strony'] ?? 'tnApp'; // Użyj domyślnej nazwy, jeśli nie ma w ustawieniach

// --- 4. Sprawdzenie Autoryzacji i Ustalenie Strony ---
$tn_is_logged_in = isset($_SESSION['tn_user_id']);

$tn_request_uri = $_SERVER['REQUEST_URI'] ?? '/'; $tn_uri_path = parse_url($tn_request_uri, PHP_URL_PATH); $tn_script_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); if ($tn_script_dir === '.' || $tn_script_dir === '/') $tn_script_dir = ''; $tn_relative_path = $tn_uri_path; if (!empty($tn_script_dir) && str_starts_with($tn_uri_path, $tn_script_dir)) { $tn_relative_path = substr($tn_uri_path, strlen($tn_script_dir)); } $tn_path_parts = explode('/', trim($tn_relative_path, '/')); $tn_page_candidate = $tn_path_parts[0] ?? '';

// ZMODYFIKOWANE: Mapa ścieżek -> ID stron
$tn_page_map = [ 
    '' => 'dashboard', 
    'logowanie' => 'login_page', 
    'produkty' => 'products', 
    'zamowienia' => 'orders', 
    'magazyn' => 'warehouse_view', 
    'ustawienia' => 'settings', 
    'informacje' => 'info', 
    'pomoc' => 'help', 
    'profil' => 'profile', 
    'zwroty' => 'returns_list', 
    'nowy-zwrot' => 'return_form_new', 
    'edytuj-zwrot' => 'return_form_edit', 
    'podglad-zwrotu' => 'return_preview', 
    'raporty' => 'raport', 
    'kurierzy' => 'couriers_list',
    // NOWOŚĆ: Dodane mapowania dla stron pojazdów
  'vehicles_list_page' => 'pojazdy', 
    'add_vehicle_form_page'  => 'dodaj-pojazd',
        
    
     
        // Stare wpisy dla kompatybilności (jeśli nadal używane w index.php)
        'pojazdy' => 'vehicles',       
        'add_vehicle' => 'dodaj-pojazd',
    
];

$tn_biezaca_strona_id = $tn_is_logged_in ? 'dashboard' : 'login_page';
if (isset($tn_page_map[$tn_page_candidate])) { 
    $tn_biezaca_strona_id = $tn_page_map[$tn_page_candidate]; 
} elseif (isset($_GET['page']) && ($page_key_from_get = array_search($_GET['page'], $tn_page_map)) !== false) { // Poprawka dla array_search
    $tn_biezaca_strona_id = $_GET['page']; // Użyj ID strony z GET, jeśli odpowiadający klucz istnieje
} elseif (!empty($tn_page_candidate) && $tn_page_candidate !== 'index.php') { 
    $potential_template = TN_SCIEZKA_TEMPLATEK . 'pages/tn_' . $tn_page_candidate . '.php'; 
    if ($tn_is_logged_in && preg_match('/^[a-z0-9_-]+$/i', $tn_page_candidate) && file_exists($potential_template)) { 
        $tn_biezaca_strona_id = $tn_page_candidate; 
    } else { 
        if ($tn_is_logged_in) tn_ustaw_komunikat_flash("Nieznana ścieżka: " . htmlspecialchars($tn_page_candidate), 'warning'); 
        $tn_biezaca_strona_id = $tn_is_logged_in ? 'dashboard' : 'login_page'; 
    } 
} else { 
    $tn_biezaca_strona_id = $tn_is_logged_in ? 'dashboard' : 'login_page'; 
}

$tn_akcja = $_POST['action'] ?? $_GET['action'] ?? null; 
$tn_is_login_action = ($tn_akcja === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST'); 
$tn_is_logout_action = ($tn_akcja === 'logout');

if ($tn_is_logged_in && $tn_biezaca_strona_id === 'login_page') { header('Location: ' . tn_generuj_url('dashboard')); exit; }
if (!$tn_is_logged_in && $tn_biezaca_strona_id !== 'login_page' && !$tn_is_login_action && !$tn_is_logout_action) { 
    $_SESSION['tn_redirect_url_after_login'] = $tn_request_uri;
    tn_ustaw_komunikat_flash('Proszę się zalogować, aby uzyskać dostęp.', 'info');
    header('Location: ' . tn_generuj_url('login_page')); exit; 
}

// --- 5. Ładowanie Logiki Biznesowej i Danych Aplikacji ---
$tn_produkty = []; $tn_zamowienia = []; $tn_stan_magazynu = []; $tn_regaly = []; $tn_uzytkownicy = []; $tn_zwroty = []; $tn_kurierzy = [];
$tn_wszystkie_pojazdy_z_json = []; // NOWOŚĆ

$load_app_data = $tn_is_logged_in && $tn_biezaca_strona_id !== 'login_page'; 
$load_users_data = $tn_is_logged_in || $tn_is_login_action || $tn_biezaca_strona_id === 'profile';

if ($load_app_data) { 
    if (!function_exists('tn_przetworz_liste_produktow')) require_once 'src/logic/tn_product_processing.php'; 
    if (!function_exists('tn_przetworz_liste_zamowien')) require_once 'src/logic/tn_order_processing.php'; 
    if (!function_exists('tn_przetworz_liste_zwrotow')) { 
        if(file_exists('src/logic/tn_return_processing.php')) { 
            require_once 'src/logic/tn_return_processing.php'; 
        } else { 
            if(!function_exists('tn_przetworz_liste_zwrotow')) { 
                function tn_przetworz_liste_zwrotow($z, $g, $n) { return ['zwroty_wyswietlane' => $z, 'ilosc_wszystkich' => count($z),  'biezaca_strona' => 1, 'sortowanie' => 'date_desc']; } 
            } 
        } 
    } 
}
if ($load_users_data) { $tn_uzytkownicy = tn_laduj_uzytkownikow(TN_PLIK_UZYTKOWNICY); } 
if ($load_app_data) { 
    $tn_produkty = tn_laduj_produkty(TN_PLIK_PRODUKTY); 
    $tn_zamowienia = tn_laduj_zamowienia(TN_PLIK_ZAMOWIENIA); 
    $tn_stan_magazynu = tn_laduj_magazyn(TN_PLIK_MAGAZYN); 
    $tn_regaly = tn_laduj_regaly(TN_PLIK_REGALY); 
    $tn_zwroty = tn_laduj_zwroty(TN_PLIK_ZWROTY); 
    $tn_kurierzy = tn_laduj_kurierow(TN_PLIK_KURIERZY); 
    if(empty($tn_uzytkownicy) && $tn_is_logged_in) $tn_uzytkownicy = tn_laduj_uzytkownikow(TN_PLIK_UZYTKOWNICY);

    // NOWOŚĆ: Ładowanie danych dla centralnej bazy pojazdów
    if (defined('TN_PLIK_POJAZDY') && file_exists(TN_PLIK_POJAZDY)) {
        if (function_exists('tn_laduj_pojazdy')) { 
            $tn_wszystkie_pojazdy_z_json = tn_laduj_pojazdy(TN_PLIK_POJAZDY);
        } else {
            error_log("Krytyczny błąd: Funkcja tn_laduj_pojazdy() nie jest zdefiniowana (powinna być w tn_data_helpers.php).");
        }
    } elseif (defined('TN_PLIK_POJAZDY')) {
         error_log("Uwaga: Plik bazy pojazdów zdefiniowany w TN_PLIK_POJAZDY ('" . TN_PLIK_POJAZDY . "') nie istnieje.");
    } else {
         error_log("Uwaga: Stała TN_PLIK_POJAZDY nie jest zdefiniowana. Nie można załadować centralnej bazy pojazdów.");
    }
}
$tn_dane_uzytkownika = null; $tn_edytowany_zwrot = null; $tn_produkt_podgladu = null; $tn_zamowienie_podgladu = null; $tn_zwrot_podgladu = null; $tn_produkty_dane = null; $tn_zamowienia_dane = null; $tn_zwroty_dane = null;
$tn_produkt_do_edycji = null; // NOWOŚĆ: Dla tn_manage_vehicles.php (zmieniono nazwę z $tn_produkt_do_edycji_pojazdow dla spójności)
$tn_lista_pojazdow = []; // NOWOŚĆ: Dla tn_vehicles_list.php

// --- 6. Generowanie Tokenu CSRF ---
$tn_token_csrf = function_exists('tn_generuj_token_csrf') ? tn_generuj_token_csrf() : '';

// --- 7. Obsługa Akcji ---
if ($tn_akcja !== null && $tn_akcja !== 'login' && $tn_akcja !== 'logout') { 
    $submitted_token = $_POST['tn_csrf_token'] ?? $_GET['tn_csrf_token'] ?? null; 
    if (!function_exists('tn_waliduj_token_csrf') || !tn_waliduj_token_csrf($submitted_token)) { 
        tn_ustaw_komunikat_flash('Błąd CSRF. Sesja mogła wygasnąć lub żądanie jest nieprawidłowe.', 'danger'); 
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? tn_generuj_url('dashboard'))); exit; 
    } 
}
// ZMODYFIKOWANE: Mapa akcji
$tn_mapa_akcji = [ 
    'save'=>'src/actions/tn_action_save_product.php', 
    'delete_product'=>'src/actions/tn_action_delete_product.php', 
    'delete_order'=>'src/actions/tn_action_delete_order.php', 
    'import_products'=>'src/actions/tn_action_import_products.php', 
    'save_order'=>'src/actions/tn_action_save_order.php', 
    'assign_warehouse'=>'src/actions/tn_action_assign_warehouse.php', 
    'clear_slot'=>'src/actions/tn_action_clear_warehouse_slot.php', 
    'create_regal'=>'src/actions/tn_action_create_regal.php', 
    'delete_regal'=>'src/actions/tn_action_delete_regal.php', 
    'create_locations'=>'src/actions/tn_action_create_locations.php', 
    'save_settings'=>'src/actions/tn_action_save_settings.php', 
    'login'=>'src/actions/tn_action_login.php',
    'logout'=>'src/actions/tn_action_logout.php', 
    'update_profile'=>'src/actions/tn_action_update_profile.php', 
    'save_return'=>'src/actions/tn_action_save_return.php', 
    'save_courier'=>'src/actions/tn_action_save_courier.php', 
    'delete_courier'=>'src/actions/tn_action_delete_courier.php',
    // NOWOŚĆ: Dodane akcje dla pojazdów
    'add_vehicle' => 'src/actions/tn_action_save_vehicle.php', // Ta akcja zapisuje nowy pojazd do bazy (vehicles.json)
    'save_vehicle_associations' => 'src/actions/tn_action_save_vehicle_associations.php', // Ta akcja zapisuje surowy tekst powiązań do produktu
];
if ($tn_akcja !== null && isset($tn_mapa_akcji[$tn_akcja])) { 
    $tn_plik_akcji = $tn_mapa_akcji[$tn_akcja]; 
    if (file_exists($tn_plik_akcji)) { 
        require_once $tn_plik_akcji; 
    } else { 
        error_log("Błąd: Brak pliku akcji '{$tn_plik_akcji}' dla akcji '{$tn_akcja}'"); 
        tn_ustaw_komunikat_flash("Błąd serwera (brak pliku akcji).", 'danger'); 
        header("Location: " . tn_generuj_url('dashboard')); exit; 
    } 
} elseif ($tn_akcja !== null) { 
    tn_ustaw_komunikat_flash("Nieznana akcja: " . htmlspecialchars($tn_akcja), 'warning'); 
}

// --- 8. Routing Widoku i Przygotowanie Danych ---
$tn_id_podgladu = null; $tn_id_zam_podgladu = null; $tn_id_zwrotu_do_edycji = null; $tn_id_zwrotu_do_podgladu = null;
$tn_id_produktu_do_zarzadzania_pojazdami = null; // Zmieniono nazwę dla spójności

$tn_get_params = []; $tn_uri_query_string = $_SERVER['QUERY_STRING'] ?? null; 
if (!empty($tn_uri_query_string)) { parse_str($tn_uri_query_string, $tn_get_params); }

if ($tn_is_logged_in && $tn_biezaca_strona_id !== 'login_page') { 
    // ZMODYFIKOWANE: Logika routingu dla ścieżek związanych z produktami
    if ($tn_page_candidate === 'produkty') { // Wszystkie ścieżki /produkty/...
        if (isset($tn_path_parts[1]) && $tn_path_parts[1] === 'podglad' && isset($tn_path_parts[2]) && is_numeric($tn_path_parts[2])) {
            $id = filter_var($tn_path_parts[2], FILTER_VALIDATE_INT); 
            if ($id) { $tn_id_podgladu = $id; $tn_biezaca_strona_id = 'product_preview'; } 
            else tn_ustaw_komunikat_flash("Błędne ID produktu.", 'warning');
        } elseif (isset($tn_path_parts[1]) && is_numeric($tn_path_parts[1]) && isset($tn_path_parts[2]) && $tn_path_parts[2] === 'zarzadzaj-pojazdami') {
            // Obsługa /produkty/{id}/zarzadzaj-pojazdami
            $id_prod = filter_var($tn_path_parts[1], FILTER_VALIDATE_INT);
            if ($id_prod) {
                $tn_id_produktu_do_zarzadzania_pojazdami = $id_prod;
                $tn_biezaca_strona_id = 'manage_product_vehicles_page'; 
            } else {
                tn_ustaw_komunikat_flash("Błędne ID produktu dla zarządzania pojazdami.", 'warning');
                $tn_biezaca_strona_id = 'products';
            }
        } elseif (isset($tn_path_parts[1]) && $tn_path_parts[1] === 'strona' && isset($tn_path_parts[2]) && is_numeric($tn_path_parts[2])) {
            $p = filter_var($tn_path_parts[2], FILTER_VALIDATE_INT); if ($p) $tn_get_params['p'] = $p;
            // $tn_biezaca_strona_id pozostaje 'products'
        }
        // Jeśli $tn_biezaca_strona_id nie zostało zmienione powyżej, a $tn_page_candidate to 'produkty', to $tn_biezaca_strona_id = 'products' (lista produktów)
    } elseif (array_key_exists($tn_biezaca_strona_id, array_flip($tn_page_map))) { 
        // Istniejąca logika dla innych podstawowych stron z $tn_page_map
        $part1 = $tn_path_parts[1] ?? null; $part2 = $tn_path_parts[2] ?? null; $part3 = $tn_path_parts[3] ?? null; $part4 = $tn_path_parts[4] ?? null; 
        switch ($tn_biezaca_strona_id) { 
            // Usunięto case 'products', bo jest obsługiwany wyżej
            case 'orders': 
                if ($part1 === 'podglad' && $part2 !== null) { 
                    $id = filter_var($part2, FILTER_VALIDATE_INT); 
                    if ($id) { $tn_id_zam_podgladu = $id; $tn_biezaca_strona_id = 'order_preview'; } 
                    else tn_ustaw_komunikat_flash("Błędne ID zamówienia.", 'warning'); 
                } elseif ($part1 === 'status' && $part2 !== null) { 
                    $tn_get_params['status'] = urldecode($part2); 
                    if ($part3 === 'strona' && $part4 !== null) { $p = filter_var($part4, FILTER_VALIDATE_INT); if ($p) $tn_get_params['p'] = $p; } 
                } elseif ($part1 === 'strona' && $part2 !== null) { 
                    $p = filter_var($part2, FILTER_VALIDATE_INT); if ($p) $tn_get_params['p'] = $p; 
                } 
                break; 
            case 'returns_list': 
                if ($part1 === 'strona' && $part2 !== null) { $p = filter_var($part2, FILTER_VALIDATE_INT); if ($p) $tn_get_params['p'] = $p; } 
                break; 
            case 'return_form_edit': 
                if ($part1 !== null) { $id = filter_var($part1, FILTER_VALIDATE_INT); if ($id) $tn_id_zwrotu_do_edycji = $id; else { tn_ustaw_komunikat_flash("Błędne ID zgłoszenia.", 'warning'); $tn_biezaca_strona_id = 'returns_list'; } } 
                else { tn_ustaw_komunikat_flash("Brak ID zgłoszenia.", 'warning'); $tn_biezaca_strona_id = 'returns_list'; } 
                break; 
            case 'return_preview': 
                if ($part1 !== null) { $id = filter_var($part1, FILTER_VALIDATE_INT); if ($id) $tn_id_zwrotu_do_podgladu = $id; else { tn_ustaw_komunikat_flash("Błędne ID zgłoszenia.", 'warning'); $tn_biezaca_strona_id = 'returns_list'; } } 
                else { tn_ustaw_komunikat_flash("Brak ID zgłoszenia.", 'warning'); $tn_biezaca_strona_id = 'returns_list'; } 
                break; 
        } 
    } 
}

// --- Ładowanie danych specyficznych ---
if ($tn_is_logged_in && $tn_biezaca_strona_id !== 'login_page') { 
    if ($tn_id_podgladu && $tn_biezaca_strona_id === 'product_preview') { 
        foreach ($tn_produkty as $p) { if (($p['id'] ?? null) == $tn_id_podgladu) { $tn_produkt_podgladu = $p; break; } } 
        if (!$tn_produkt_podgladu) { $tn_biezaca_strona_id = 'products'; tn_ustaw_komunikat_flash("Nie znaleziono produktu.", 'warning');} 
    } elseif ($tn_id_zam_podgladu && $tn_biezaca_strona_id === 'order_preview') { 
        foreach ($tn_zamowienia as $z) { if (($z['id'] ?? null) == $tn_id_zam_podgladu) { $tn_zamowienie_podgladu = $z; break; } } 
        if (!$tn_zamowienie_podgladu) { $tn_biezaca_strona_id = 'orders'; tn_ustaw_komunikat_flash("Nie znaleziono zamówienia.", 'warning');} 
    } elseif ($tn_id_zwrotu_do_edycji && $tn_biezaca_strona_id === 'return_form_edit') { 
        foreach ($tn_zwroty as $z) { if (($z['id'] ?? null) == $tn_id_zwrotu_do_edycji) { $tn_edytowany_zwrot = $z; break; } } 
        if (!$tn_edytowany_zwrot) { $tn_biezaca_strona_id = 'returns_list'; tn_ustaw_komunikat_flash("Nie znaleziono zgłoszenia.", 'warning');} 
    } elseif ($tn_id_zwrotu_do_podgladu && $tn_biezaca_strona_id === 'return_preview') { 
        foreach ($tn_zwroty as $z) { if (($z['id'] ?? null) == $tn_id_zwrotu_do_podgladu) { $tn_zwrot_podgladu = $z; break; } } 
        if (!$tn_zwrot_podgladu) { $tn_biezaca_strona_id = 'returns_list'; tn_ustaw_komunikat_flash("Nie znaleziono zgłoszenia.", 'warning');} 
    } elseif ($tn_biezaca_strona_id === 'profile') { 
        $user_id = $_SESSION['tn_user_id'] ?? null; 
        if ($user_id) { foreach ($tn_uzytkownicy as $u) { if (($u['id'] ?? null) == $user_id) { $tn_dane_uzytkownika = $u; break; } } } 
        if (!$tn_dane_uzytkownika && $tn_is_logged_in) { session_destroy(); header('Location: ' . tn_generuj_url('login_page')); exit; } 
    }
    // NOWOŚĆ: Ładowanie danych dla tn_manage_vehicles.php
    elseif ($tn_id_produktu_do_zarzadzania_pojazdami && $tn_biezaca_strona_id === 'manage_product_vehicles_page') {
        foreach ($tn_produkty as $p) {
            if (isset($p['id']) && (int)$p['id'] === $tn_id_produktu_do_zarzadzania_pojazdami) {
                $tn_produkt_do_edycji = $p; // Nazwa zmiennej używana w tn_manage_vehicles.php
                break;
            }
        }
        if (!$tn_produkt_do_edycji) {
            $tn_biezaca_strona_id = 'products'; 
            tn_ustaw_komunikat_flash("Nie znaleziono produktu (ID: {$tn_id_produktu_do_zarzadzania_pojazdami}) do zarządzania powiązaniami.", 'warning');
        }
    }
    // NOWOŚĆ: Przygotowanie danych dla tn_vehicles_list.php
    elseif ($tn_biezaca_strona_id === 'vehicles_list_page') {
        $tempGrouped = [];
        if (is_array($tn_wszystkie_pojazdy_z_json)) {
            foreach ($tn_wszystkie_pojazdy_z_json as $vehicle_entry) {
                if (!isset($vehicle_entry['make'], $vehicle_entry['model'])) continue;
                $makeModelKey = htmlspecialchars($vehicle_entry['make'] . ' ' . $vehicle_entry['model'], ENT_QUOTES, 'UTF-8');
                // Można by dodać logikę do grupowania po displayInfo, jeśli vehicles.json to wspiera
                $displayInfo = ['make' => htmlspecialchars($vehicle_entry['make'], ENT_QUOTES, 'UTF-8'), 'modelType' => htmlspecialchars($vehicle_entry['model'], ENT_QUOTES, 'UTF-8')];
                // W tej wersji, kluczem jest 'Marka Model', a displayInfo jest częścią każdej wersji, jeśli to konieczne
                // Lub, jeśli $vehicle_entry ma już 'prodYearStart' i 'prodYearEnd' na poziomie modelu.
                // Dla uproszczenia tutaj, zakładamy, że szablon tn_vehicles_list poradzi sobie z kluczem $makeModelKey

                if (!isset($tempGrouped[$makeModelKey]['displayInfo'])) {
                     // Jeśli chcesz przechowywać ogólne lata produkcji dla modelu, musiałbyś je mieć w $vehicle_entry
                     // $displayInfo['prodYearStart'] = htmlspecialchars($vehicle_entry['model_year_start'] ?? '-', ENT_QUOTES, 'UTF-8');
                     // $displayInfo['prodYearEnd'] = htmlspecialchars($vehicle_entry['model_year_end'] ?? '-', ENT_QUOTES, 'UTF-8');
                    $tempGrouped[$makeModelKey]['displayInfo'] = $displayInfo; // Proste displayInfo
                    $tempGrouped[$makeModelKey]['versions'] = [];
                }
                
                $tempGrouped[$makeModelKey]['versions'][] = [
                    'name' => htmlspecialchars($vehicle_entry['version_name'] ?? '-', ENT_QUOTES, 'UTF-8'),
                    'code' => htmlspecialchars($vehicle_entry['version_code'] ?? '-', ENT_QUOTES, 'UTF-8'),
                    'capacity' => htmlspecialchars((string)($vehicle_entry['capacity'] ?? '-'), ENT_QUOTES, 'UTF-8'),
                    'kw' => htmlspecialchars((string)($vehicle_entry['kw'] ?? '-'), ENT_QUOTES, 'UTF-8'),
                    'hp' => htmlspecialchars((string)($vehicle_entry['hp'] ?? '-'), ENT_QUOTES, 'UTF-8'),
                    'year_start' => htmlspecialchars((string)($vehicle_entry['year_start'] ?? '-'), ENT_QUOTES, 'UTF-8'),
                    'year_end' => htmlspecialchars((string)($vehicle_entry['year_end'] ?? 'nadal'), ENT_QUOTES, 'UTF-8'),
                    'db_id' => $vehicle_entry['id'] ?? null 
                ];
            }
        }
        $tn_lista_pojazdow = $tempGrouped; 
        if(is_array($tn_lista_pojazdow)) ksort($tn_lista_pojazdow, SORT_NATURAL | SORT_FLAG_CASE);
    }
}

// --- Ustawienie Tytułu i Pliku Widoku ---
$tn_plik_widoku = ''; $tn_tytul_strony = ''; $is_convention_page = false; 
$is_valid_page_id = array_key_exists($tn_biezaca_strona_id, array_flip($tn_page_map)) || 
                   in_array($tn_biezaca_strona_id, ['product_preview', 'order_preview', 'return_preview', 
                                                'manage_product_vehicles_page', // NOWOŚĆ
                                                'add_vehicle_form_page',      // NOWOŚĆ
                                                'vehicles_list_page'          // NOWOŚĆ
                                                ]);

if ($is_valid_page_id) { 
    switch ($tn_biezaca_strona_id) { 
        case 'dashboard': $tn_tytul_strony = 'Pulpit'; $tn_plik_widoku = 'pages/tn_dashboard.php'; break; 
        case 'product_preview': $tn_tytul_strony = $tn_produkt_podgladu ? htmlspecialchars($tn_produkt_podgladu['name']) : 'Podgląd Produktu'; $tn_plik_widoku = 'pages/tn_product_preview.php'; break; 
        case 'products': $tn_tytul_strony = 'Produkty'; $tn_plik_widoku = 'pages/tn_products_list.php'; if($tn_is_logged_in){$n=(int)($tn_ustawienia_globalne['produkty_na_stronie'] ?? 10) ?: 10; $tn_produkty_dane = tn_przetworz_liste_produktow($tn_produkty, $tn_get_params, $n);} break; 
        case 'order_preview': $tn_tytul_strony = $tn_zamowienie_podgladu ? 'Zam. #' . $tn_zamowienie_podgladu['id'] : 'Podgląd Zamówienia'; $tn_plik_widoku = 'pages/tn_order_preview.php'; break; 
        case 'orders': $tn_tytul_strony = 'Zamówienia'; $tn_plik_widoku = 'pages/tn_orders_list.php'; if($tn_is_logged_in){$n=(int)($tn_ustawienia_globalne['zamowienia_na_stronie'] ?? 10) ?: 10; $tn_zamowienia_dane = tn_przetworz_liste_zamowien($tn_zamowienia, $tn_produkty, $tn_get_params, $n);} break; 
        case 'profile': $tn_tytul_strony = 'Mój Profil'; $tn_plik_widoku = 'pages/tn_profile.php'; break; 
        case 'returns_list': $tn_tytul_strony = 'Zwroty i Reklamacje'; $tn_plik_widoku = 'pages/tn_returns_list.php'; if($tn_is_logged_in){$n=(int)($tn_ustawienia_globalne['zwroty_na_stronie'] ?? 15) ?: 15; $tn_zwroty_dane = tn_przetworz_liste_zwrotow($tn_zwroty, $tn_get_params, $n);} break; 
        case 'return_form_new': $tn_tytul_strony = 'Nowe Zgłoszenie'; $tn_plik_widoku = 'pages/tn_return_form.php'; $tn_edytowany_zwrot = null; break; 
        case 'return_form_edit': $tn_tytul_strony = 'Edytuj Zgłoszenie #' . ($tn_edytowany_zwrot['id'] ?? '?'); $tn_plik_widoku = 'pages/tn_return_form.php'; break; 
        case 'return_preview': $tn_tytul_strony = $tn_zwrot_podgladu ? 'Zgłoszenie #' . $tn_zwrot_podgladu['id'] : 'Podgląd Zgłoszenia'; $tn_plik_widoku = 'pages/tn_return_preview.php'; break; 
        case 'couriers_list': $tn_tytul_strony = 'Kurierzy'; $tn_plik_widoku = 'pages/tn_couriers_list.php'; break; 
        
        // NOWOŚĆ: Definicje dla widoków pojazdów
        case 'vehicles_list_page': $tn_tytul_strony = 'Baza Pojazdów'; $tn_plik_widoku = 'pages/tn_vehicles_list.php'; break;
        case 'add_vehicle_form_page': $tn_tytul_strony = 'Dodaj Nowy Pojazd'; $tn_plik_widoku = 'pages/tn_add_vehicle_form.php'; break;
        case 'manage_product_vehicles_page': 
            $page_title_prod_name = $tn_produkt_do_edycji ? htmlspecialchars($tn_produkt_do_edycji['name']) : 'produktu'; // Użyj $tn_produkt_do_edycji
            $tn_tytul_strony = 'Zarządzaj Pojazdami dla ' . $page_title_prod_name; 
            $tn_plik_widoku = 'pages/tn_manage_vehicles.php'; 
            break; 

        case 'login_page': $tn_tytul_strony = 'Logowanie'; $tn_plik_widoku = 'pages/tn_login_page.php'; break; 
        case 'settings': $tn_tytul_strony = 'Ustawienia'; $tn_plik_widoku = 'pages/tn_settings_form.php'; break; 
        case 'info': $tn_tytul_strony = 'Informacje'; $tn_plik_widoku = 'pages/tn_info_page.php'; break; 
        case 'warehouse_view': $tn_tytul_strony = 'Widok Magazynu'; $tn_plik_widoku = 'pages/tn_warehouse_view.php'; break; 
        case 'help': $tn_tytul_strony = 'Pomoc'; $tn_plik_widoku = 'pages/tn_help_page.php'; break; 
        case 'raport': $tn_tytul_strony = 'Raporty'; $tn_plik_widoku = 'pages/tn_raport.php'; break; 
        default: 
            if ($tn_is_logged_in) {
                $potential_template = TN_SCIEZKA_TEMPLATEK . 'pages/tn_' . $tn_biezaca_strona_id . '.php';
                if (file_exists($potential_template)) {
                    $tn_tytul_strony = ucfirst(str_replace(['_', '-'], ' ', $tn_biezaca_strona_id));
                    $tn_plik_widoku = 'pages/tn_' . $tn_biezaca_strona_id . '.php';
                } else {
                    $tn_biezaca_strona_id = 'dashboard'; 
                    $tn_tytul_strony = 'Pulpit'; 
                    $tn_plik_widoku = 'pages/tn_dashboard.php';
                }
            } else {
                 $tn_biezaca_strona_id = 'login_page'; 
                 $tn_tytul_strony = 'Logowanie'; 
                 $tn_plik_widoku = 'pages/tn_login_page.php';
            }
            break;
    } 
} elseif ($tn_is_logged_in) { 
    $potential_template = TN_SCIEZKA_TEMPLATEK . 'pages/tn_' . $tn_biezaca_strona_id . '.php'; 
    if (file_exists($potential_template)) { 
        $tn_tytul_strony = ucfirst(str_replace(['_', '-'], ' ', $tn_biezaca_strona_id)); 
        $tn_plik_widoku = 'pages/tn_' . $tn_biezaca_strona_id . '.php'; 
    } else { // Jeśli nie znaleziono szablonu wg konwencji, wróć do dashboardu
        $tn_biezaca_strona_id = 'dashboard'; 
        $tn_tytul_strony = 'Pulpit'; 
        $tn_plik_widoku = 'pages/tn_dashboard.php';
    }
}

if (empty($tn_plik_widoku)) { 
    error_log("Fallback: Nie można ustalić widoku dla ID '{$tn_biezaca_strona_id}'. Ustawianie domyślnego widoku."); 
    $tn_biezaca_strona_id = $tn_is_logged_in ? 'dashboard' : 'login_page'; 
    $tn_tytul_strony = ($tn_is_logged_in ? 'Pulpit' : 'Logowanie'); 
    $tn_plik_widoku = $tn_is_logged_in ? 'pages/tn_dashboard.php' : 'pages/tn_login_page.php';
}
if ($tn_biezaca_strona_id !== 'login_page' && strpos($tn_tytul_strony, $tn_app_name) === false) { 
    $tn_tytul_strony .= ' - ' . $tn_app_name; 
}

// --- 9. Renderowanie Widoku ---
$tn_pelna_sciezka_widoku = TN_SCIEZKA_TEMPLATEK . $tn_plik_widoku;
if ($tn_biezaca_strona_id === 'login_page') { 
    if (file_exists($tn_pelna_sciezka_widoku)) { 
        $tn_site_name = $tn_app_name; 
        $tn_komunikaty_flash = tn_pobierz_i_wyczysc_komunikaty_flash(); 
        require $tn_pelna_sciezka_widoku; 
    } else { 
        http_response_code(500); 
        error_log("Błąd: Brak szablonu: {$tn_pelna_sciezka_widoku}"); 
        die("Błąd serwera."); 
    } 
} elseif ($tn_is_logged_in) { 
    $tn_aktywny_identyfikator_strony = $tn_biezaca_strona_id; 
    require TN_SCIEZKA_TEMPLATEK . 'partials/tn_header.php'; 
    require TN_SCIEZKA_TEMPLATEK . 'partials/tn_sidebar.php'; 
    echo '<div class="tn-glowna-czesc d-flex flex-column min-vh-100">'; 
    require TN_SCIEZKA_TEMPLATEK . 'partials/tn_topbar.php'; 
    $tn_komunikaty_flash = tn_pobierz_i_wyczysc_komunikaty_flash(); 
    if (!empty($tn_komunikaty_flash)) { 
        echo '<div class="tn-flash-container position-fixed top-0 end-0 p-3" style="z-index: 1056;">'; 
        foreach ($tn_komunikaty_flash as $tn_k) { 
            $t=htmlspecialchars($tn_k['type']??'info'); 
            $w=($tn_k['message']??''); 
            $i=match(strtolower($t)){'success'=>'bi-check-circle-fill','danger'=>'bi-exclamation-octagon-fill','warning'=>'bi-exclamation-triangle-fill',default=>'bi-info-circle-fill'}; 
            echo "<div class='alert alert-{$t} alert-dismissible fade show d-flex align-items-center shadow-sm mb-2' role='alert'><i class='bi {$i} me-2 flex-shrink-0'></i><div>{$w}</div><button type='button' class='btn-close ms-auto p-2' data-bs-dismiss='alert' aria-label='Close'></button></div>"; 
        } 
        echo '</div>';
    } 
    echo '<main class="tn-kontener-tresci container-fluid flex-grow-1" id="tnMainContent">'; 
    if (!empty($tn_plik_widoku) && file_exists($tn_pelna_sciezka_widoku)) { 
        require $tn_pelna_sciezka_widoku; 
    } else { 
        echo '<div class="alert alert-danger">Błąd: Nie można załadować widoku: '.htmlspecialchars($tn_plik_widoku ?? 'nieokreślony').'.</div>'; 
        error_log("Brak widoku: {$tn_plik_widoku}"); 
    } 
    echo '</main>'; 
    if (file_exists(TN_SCIEZKA_TEMPLATEK . 'partials/tn_modals.php')) { 
        require TN_SCIEZKA_TEMPLATEK . 'partials/tn_modals.php'; 
    } 
    if (file_exists(TN_SCIEZKA_TEMPLATEK . 'partials/tn_footer.php')) { 
        require TN_SCIEZKA_TEMPLATEK . 'partials/tn_footer.php'; 
    } else { 
        echo '</div></body></html>'; 
    } 
} else { 
    error_log("Błąd logiki: Niezalogowany użytkownik w sekcji renderowania aplikacji, która nie jest stroną logowania. ID strony: '{$tn_biezaca_strona_id}'"); 
    header('Location: ' . tn_generuj_url('login_page')); 
    exit; 
}
// --- Koniec Renderowania Widoku ---
?>