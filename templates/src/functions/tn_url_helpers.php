<?php
// src/functions/tn_url_helpers.php
/**
 * Funkcje pomocnicze do generowania adresów URL w aplikacji.
 * Wersja: 1.3 (Zawiera mapowania dla zarządzania pojazdami)
 */

/**
 * Generuje "przyjazny" adres URL na podstawie wewnętrznego identyfikatora strony i parametrów.
 * Używa mapy predefiniowanych ścieżek lub generuje ścieżkę na podstawie ID (dla stron wg konwencji).
 * Obsługuje dodawanie parametrów 'id', 'status', 'p' (paginacja) do ścieżki URL.
 * Pozostałe parametry dodaje jako query string (?param=wartosc).
 *
 * @param string $tn_identyfikator_strony Wewnętrzny ID strony (np. 'products', 'product_preview', 'moj_raport').
 * @param array $tn_parametry Tablica asocjacyjna parametrów do URL (np. ['id'=>1, 'sort'=>'name_asc']).
 * @return string Wygenerowany, przyjazny adres URL (względny do roota aplikacji).
 */
function tn_generuj_url(string $tn_identyfikator_strony, array $tn_parametry = []) : string {

    // 1. Ustal bazową ścieżkę URL aplikacji
    $tn_base_url_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    if ($tn_base_url_path === '.' || $tn_base_url_path === '\\') {
        $tn_base_url_path = '';
    }
function tn_generuj_url(string $pageId, array $params = []): string {
    // Mapa ID stron na ścieżki URL
    $page_paths = [
        'dashboard' => '', // Pusta ścieżka dla strony głównej
        'login_page' => 'logowanie',
        'products' => 'produkty',
        'product_preview' => 'produkty/podglad', // Będzie wymagać ID w parametrach
        'orders' => 'zamowienia',
        'order_preview' => 'zamowienia/podglad', // Będzie wymagać ID w parametrach
        'warehouse_view' => 'magazyn',
        'settings' => 'ustawienia',
        'info' => 'informacje',
        'help' => 'pomoc',
        'profile' => 'profil',
        'returns_list' => 'zwroty',
        'return_form_new' => 'zwroty/nowe',
        'return_form_edit' => 'zwroty/edytuj', // Będzie wymagać ID w parametrach
        'return_preview' => 'zwroty/podglad', // Będzie wymagać ID w parametrach
        'couriers_list' => 'kurierzy',
        'manage_vehicles' => 'produkty/zarzadzaj-pojazdami', // Będzie wymagać ID produktu w parametrach
        'vehicles' => 'pojazdy', // Nowa ścieżka dla bazy pojazdów
    ];

    $path = $page_paths[$pageId] ?? $pageId; // Użyj zmapowanej ścieżki lub ID jako ścieżki

    // Dodaj parametry do ścieżki URL, jeśli strona tego wymaga (np. podgląd, edycja)
    $path_with_params = $path;
    $id_param = null;

    // Sprawdź, czy strona wymaga ID w ścieżce i czy ID jest dostępne w parametrach
    if (in_array($pageId, ['product_preview', 'order_preview', 'return_form_edit', 'return_preview', 'manage_vehicles'])) {
        // Określ klucz parametru ID w zależności od strony
        $id_key = match($pageId) {
            'product_preview', 'manage_vehicles' => 'id', // product_id dla manage_vehicles, ale używamy 'id' w URL
            'order_preview' => 'id', // order_id, ale używamy 'id' w URL
            'return_form_edit', 'return_preview' => 'id', // return_id, ale używamy 'id' w URL
            default => 'id', // Domyślnie 'id'
        };

        if (isset($params[$id_key])) {
            $id_param = $params[$id_key];
            unset($params[$id_key]); // Usuń ID z parametrów GET, bo będzie w ścieżce
            $path_with_params .= '/' . urlencode((string)$id_param);
        } else {
            // Jeśli strona wymaga ID w ścieżce, ale go nie ma, loguj błąd lub zwróć link zastępczy
            error_log("Błąd generowania URL dla strony '{$pageId}': Brak wymaganego parametru ID ('{$id_key}').");
            // Można zwrócić link do listy lub strony głównej
            return function_exists('tn_generuj_url') ? tn_generuj_url(str_replace(['_preview', '_form_edit', '/zarzadzaj-pojazdami'], ['s', 's', ''], $pageId)) : '#'; // Przykładowy fallback do listy
        }
    }


    // Zbuduj ciąg z pozostałymi parametrami GET
    $query_string = '';
    if (!empty($params)) {
        $query_string = '?' . http_build_query($params);
    }

    // Zbuduj pełny URL
    // Zakładamy, że aplikacja jest w katalogu głównym serwera lub obsługa .htaccess kieruje wszystko do index.php
    $baseUrl = '/'; // Dostosuj, jeśli aplikacja jest w podkatalogu

    // Dodaj index.php do URL, jeśli nie używasz czystych URL
    // $baseUrl = '/index.php';
    // $query_string = '?page=' . urlencode($pageId) . '&' . http_build_query($params); // Jeśli używasz ?page=

    $url = $baseUrl . $path_with_params . $query_string;

    return $url;
}
    // 2. Mapa głównych identyfikatorów stron -> podstawowe segmenty ścieżek URL
    // Ta mapa zawiera już wpisy dla pojazdów dodane w poprzednim kroku.
    $tn_mapa_sciezek = [
        'dashboard' => '/', 
        'login_page' => '/logowanie',
        // Produkty
        'products' => '/produkty',
        'product_preview' => '/produkty/podglad', 
        // *** FRAGMENT DLA ZARZĄDZANIA POWIĄZANIAMI PRODUKTU ***
        'manage_product_vehicles_page' => '/produkty/zarzadzaj-pojazdami', // Bazowa ścieżka

        // Zamówienia
        'orders' => '/zamowienia',
        'order_preview' => '/zamowienia/podglad', 
        // Magazyn
        'warehouse_view' => '/magazyn',
        // Ustawienia
        'settings' => '/ustawienia',
        // Profil
        'profile' => '/profil',
        // Zwroty/Reklamacje
        'returns_list' => '/zwroty',
        'return_form_new' => '/nowy-zwrot',
        'return_form_edit' => '/edytuj-zwrot', 
        'return_preview' => '/podglad-zwrotu', 
        // Kurierzy
        'couriers_list' => '/kurierzy',
        // *** FRAGMENTY DLA LISTY I FORMULARZA POJAZDÓW ***
        'vehicles_list_page' => '/pojazdy', 
        'add_vehicle_form_page' => '/dodaj-pojazd', 
        
        // Statyczne
        'info' => '/informacje',
        'help' => '/pomoc',
     
        // Stare wpisy dla kompatybilności (jeśli nadal używane w index.php)
        'vehicles' => '/pojazdy',       
        'add_vehicle' => '/dodaj-pojazd',
    ];

    // 3. Pobierz podstawową ścieżkę z mapy lub wygeneruj wg konwencji
    if (isset($tn_mapa_sciezek[$tn_identyfikator_strony])) {
        $tn_sciezka = $tn_mapa_sciezek[$tn_identyfikator_strony];
    } elseif (preg_match('/^[a-z0-9_-]+$/i', $tn_identyfikator_strony)) {
        $tn_sciezka = '/' . $tn_identyfikator_strony;
    } else {
        error_log("Nieznany lub nieprawidłowy identyfikator strony '{$tn_identyfikator_strony}' w tn_generuj_url.");
        return $tn_base_url_path . '/';
    }

    // 4. Obsługa specjalnych parametrów -> dodanie do ścieżki URL
    $tn_strona_paginacji = null;
    if (isset($tn_parametry['p'])) {
        $page_num = filter_var($tn_parametry['p'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($page_num !== false && $page_num > 1) { $tn_strona_paginacji = $page_num; }
        unset($tn_parametry['p']); 
    }

    $tn_status_zamowienia = null;
    if ($tn_identyfikator_strony === 'orders' && isset($tn_parametry['status'])) {
        if(!empty($tn_parametry['status']) && strcasecmp($tn_parametry['status'], 'Wszystkie') !== 0) {
            $tn_status_zamowienia = urlencode(trim($tn_parametry['status']));
        }
        unset($tn_parametry['status']);
    }

    $tn_id_obiektu = null;
    if (isset($tn_parametry['id'])) {
        $object_id = filter_var($tn_parametry['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($object_id !== false) { $tn_id_obiektu = $object_id; }
        unset($tn_parametry['id']); 
    }

    // 5. Budowanie finalnej ścieżki URL z parametrami
    // *** FRAGMENT DLA URL ZARZĄDZANIA POWIĄZANIAMI PRODUKTU ***
    if ($tn_identyfikator_strony === 'manage_product_vehicles_page' && $tn_id_obiektu !== null) {
        $tn_sciezka .= 'produkty/' . $tn_id_obiektu . '/zarzadzaj-pojazdami'; 
    } elseif ($tn_id_obiektu !== null) {
        if (in_array($tn_identyfikator_strony, ['product_preview', 'order_preview', 'return_form_edit', 'return_preview'])) {
            $tn_sciezka .= '/' . $tn_id_obiektu; 
        } else {
            $tn_parametry['id'] = $tn_id_obiektu; 
        }
    }

    if ($tn_status_zamowienia !== null) { $tn_sciezka .= '/status/' . $tn_status_zamowienia; }
    if ($tn_strona_paginacji !== null) {
        // *** FRAGMENT DLA PAGINACJI LISTY POJAZDÓW ***
        $strony_z_paginacja_w_sciezce = ['orders', 'products', 'returns_list', 'vehicles_list_page']; 
        if (in_array($tn_identyfikator_strony, $strony_z_paginacja_w_sciezce)) {
            if(!empty($tn_sciezka) && $tn_sciezka !== '/') {
                $tn_sciezka .= '/strona/' . $tn_strona_paginacji;
            } else {
                $tn_parametry['p'] = $tn_strona_paginacji; 
            }
        } else { $tn_parametry['p'] = $tn_strona_paginacji; } 
    }

    // 6. Dodaj pozostałe parametry jako query string
    $tn_parametry = array_filter($tn_parametry, fn($value) => $value !== null && (string)$value !== ''); 
    $tn_query_string = !empty($tn_parametry) ? '?' . http_build_query($tn_parametry) : '';

    // 7. Złóż finalny URL i oczyść
    if ($tn_sciezka === '/') { 
        $tn_finalny_url = rtrim($tn_base_url_path, '/') . '/';
        if (!empty($tn_query_string)) {
            $tn_finalny_url .= ltrim($tn_query_string, '?');
        }
        if (!empty($tn_query_string) && $tn_finalny_url !== '/' && str_ends_with($tn_finalny_url, '/')) {
             $tn_finalny_url = rtrim($tn_finalny_url, '/');
        }
        if(empty($tn_finalny_url)) $tn_finalny_url = '/'; 
    } else {
        $tn_finalny_url = rtrim($tn_base_url_path, '/') . '/' . ltrim($tn_sciezka, '/');
        $tn_finalny_url .= $tn_query_string;
    }
    $tn_finalny_url = preg_replace('#(?<!:)//+#', '/', $tn_finalny_url);
    if (str_ends_with($tn_finalny_url, '/?') && strlen($tn_finalny_url) > 2) {
        $tn_finalny_url = rtrim($tn_finalny_url, '/?');
    }
    if ($tn_finalny_url === '/?') $tn_finalny_url = '/';

    return $tn_finalny_url;
}
function tn_generuj_url(string $pageId, array $params = []): string {
    // Mapa ID stron na ścieżki URL
    $page_paths = [
        'dashboard' => '', // Pusta ścieżka dla strony głównej
        'login_page' => 'logowanie',
        'products' => 'produkty',
        'product_preview' => 'produkty/podglad', // Będzie wymagać ID w parametrach
        'orders' => 'zamowienia',
        'order_preview' => 'zamowienia/podglad', // Będzie wymagać ID w parametrach
        'warehouse_view' => 'magazyn',
        'settings' => 'ustawienia',
        'info' => 'informacje',
        'help' => 'pomoc',
        'profile' => 'profil',
        'returns_list' => 'zwroty',
        'return_form_new' => 'zwroty/nowe',
        'return_form_edit' => 'zwroty/edytuj', // Będzie wymagać ID w parametrach
        'return_preview' => 'zwroty/podglad', // Będzie wymagać ID w parametrach
        'couriers_list' => 'kurierzy',
        'manage_vehicles' => 'produkty/zarzadzaj-pojazdami', // Będzie wymagać ID produktu w parametrach
        'vehicles' => 'pojazdy', // Nowa ścieżka dla bazy pojazdów
    ];

    $path = $page_paths[$pageId] ?? $pageId; // Użyj zmapowanej ścieżki lub ID jako ścieżki

    // Dodaj parametry do ścieżki URL, jeśli strona tego wymaga (np. podgląd, edycja)
    $path_with_params = $path;
    $id_param = null;

    // Sprawdź, czy strona wymaga ID w ścieżce i czy ID jest dostępne w parametrach
    if (in_array($pageId, ['product_preview', 'order_preview', 'return_form_edit', 'return_preview', 'manage_vehicles'])) {
        // Określ klucz parametru ID w zależności od strony
        $id_key = match($pageId) {
            'product_preview', 'manage_vehicles' => 'id', // product_id dla manage_vehicles, ale używamy 'id' w URL
            'order_preview' => 'id', // order_id, ale używamy 'id' w URL
            'return_form_edit', 'return_preview' => 'id', // return_id, ale używamy 'id' w URL
            default => 'id', // Domyślnie 'id'
        };

        if (isset($params[$id_key])) {
            $id_param = $params[$id_key];
            unset($params[$id_key]); // Usuń ID z parametrów GET, bo będzie w ścieżce
            $path_with_params .= '/' . urlencode((string)$id_param);
        } else {
            // Jeśli strona wymaga ID w ścieżce, ale go nie ma, loguj błąd lub zwróć link zastępczy
            error_log("Błąd generowania URL dla strony '{$pageId}': Brak wymaganego parametru ID ('{$id_key}').");
            // Można zwrócić link do listy lub strony głównej
            return function_exists('tn_generuj_url') ? tn_generuj_url(str_replace(['_preview', '_form_edit', '/zarzadzaj-pojazdami'], ['s', 's', ''], $pageId)) : '#'; // Przykładowy fallback do listy
        }
    }

/**
 * Generuje bezpieczny link URL dla akcji wykonywanej metodą GET.
 * Automatycznie dodaje parametr akcji i aktualny token CSRF.
 * Używa tn_generuj_url do stworzenia bazowego URL, jeśli ID strony jest podane.
 * Zalecane jest używanie metody POST dla akcji modyfikujących dane.
 *
 * @param string $tn_nazwa_akcji Nazwa akcji (np. 'delete_product', 'logout').
 * @param array $tn_dodatkowe_parametry Dodatkowe parametry GET (np. ['id' => 123]).
 * @param string|null $tn_identyfikator_strony Opcjonalne ID strony bazowej dla tn_generuj_url (np. 'products').
 * Jeśli null, link będzie wskazywał na index.php z parametrami GET.
 * @return string Wygenerowany URL akcji GET z tokenem CSRF.
 */
function tn_generuj_link_akcji_get(string $tn_nazwa_akcji, array $tn_dodatkowe_parametry = [], ?string $tn_identyfikator_strony = null) : string {
    $tn_token_csrf = $_SESSION['tn_csrf_token'] ?? (function_exists('tn_generuj_token_csrf') ? tn_generuj_token_csrf() : '');

    $tn_parametry_akcji = ['action' => $tn_nazwa_akcji, 'tn_csrf_token' => $tn_token_csrf];
    $tn_wszystkie_parametry = array_merge($tn_dodatkowe_parametry, $tn_parametry_akcji);

    if ($tn_identyfikator_strony !== null) {
        return tn_generuj_url($tn_identyfikator_strony, $tn_wszystkie_parametry);
    } else {
        $tn_base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        if ($tn_base_path === '.' || $tn_base_path === '\\') $tn_base_path = '';
        
        $base_index_path = !empty($tn_base_path) ? $tn_base_path . '/' : '/'; 
        return $base_index_path . '?' . http_build_query($tn_wszystkie_parametry);
    }
}
?>