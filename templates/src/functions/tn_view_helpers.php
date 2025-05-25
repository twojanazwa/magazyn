<?php
// src/functions/tn_view_helpers.php

declare(strict_types=1);

// Upewnij się, że tn_generuj_url jest dostępne
if (!function_exists('tn_generuj_url')) {
    require_once __DIR__ . '/tn_url_helpers.php';
}

/**
 * Generuje link do sortowania tabeli produktów.
 *
 * @param string $tn_sortuj_po Klucz sortowania (np. 'name', 'price').
 * @param string $tn_etykieta Etykieta linku (np. 'Nazwa', 'Cena').
 * @return string HTML linku sortowania.
 */
function tn_generuj_link_sortowania(string $tn_sortuj_po, string $tn_etykieta) : string {
    // Zakładamy dostęp do $tn_produkty_dane przez global lub przekazanie
    global $tn_produkty_dane;
    if (!isset($tn_produkty_dane)) return htmlspecialchars($tn_etykieta); // Fallback

    $tn_biezace_sortowanie = $tn_produkty_dane['sortowanie'] ?? 'name_asc';
    $tn_zapytanie_szukania = $tn_produkty_dane['zapytanie_szukania'] ?? '';
    $tn_biezaca_strona_pag = $tn_produkty_dane['biezaca_strona'] ?? 1;
    $tn_filtr_kategorii = $tn_produkty_dane['kategoria'] ?? '';

    // Ustal kierunek sortowania i nowy klucz
    $tn_kierunek_sortowania = 'asc'; // Domyślnie rosnąco
    if ($tn_biezace_sortowanie === $tn_sortuj_po . '_asc') {
        $tn_kierunek_sortowania = 'desc'; // Zmień na malejąco
    }
    // Jeśli sortujemy malejąco lub po innej kolumnie, ustaw rosnąco
    // (to zachowanie można zmienić, np. przełączać asc/desc/brak)

    $tn_nowe_sortowanie = $tn_sortuj_po . '_' . $tn_kierunek_sortowania;

    // Ikona sortowania
    $tn_ikona = '';
    if ($tn_biezace_sortowanie === $tn_sortuj_po . '_asc') {
        $tn_ikona = ' <i class="bi bi-sort-up"></i>';
    } elseif ($tn_biezace_sortowanie === $tn_sortuj_po . '_desc') {
        $tn_ikona = ' <i class="bi bi-sort-down"></i>';
    }

    // Parametry URL
    $tn_parametry_url = [];
    if ($tn_biezaca_strona_pag > 1) { $tn_parametry_url['p'] = $tn_biezaca_strona_pag; }
    $tn_parametry_url['sort'] = $tn_nowe_sortowanie;
    if (!empty($tn_zapytanie_szukania)) { $tn_parametry_url['search'] = $tn_zapytanie_szukania; }
    if (!empty($tn_filtr_kategorii)) { $tn_parametry_url['category'] = $tn_filtr_kategorii; }

    // Generuj URL za pomocą helpera
    $tn_url = tn_generuj_url('products', $tn_parametry_url);

    return '<a href="' . htmlspecialchars($tn_url) . '" class="text-decoration-none link-body-emphasis">' . htmlspecialchars($tn_etykieta) . $tn_ikona . '</a>';
}

/**
 * Generuje link do sortowania tabeli zamówień.
 *
 * @param string $tn_sortuj_po Klucz sortowania (np. 'date', 'value').
 * @param string $tn_etykieta Etykieta linku (np. 'Data', 'Wartość').
 * @return string HTML linku sortowania.
 */
function tn_generuj_link_sortowania_zamowien(string $tn_sortuj_po, string $tn_etykieta) : string {
    // Zakładamy dostęp do $tn_zamowienia_dane przez global lub przekazanie
    global $tn_zamowienia_dane;
     if (!isset($tn_zamowienia_dane)) return htmlspecialchars($tn_etykieta); // Fallback

    $tn_biezace_sortowanie = $tn_zamowienia_dane['sortowanie'] ?? 'date_desc';
    $tn_filtr_statusu = $tn_zamowienia_dane['status'] ?? '';
    $tn_biezaca_strona_pag = $tn_zamowienia_dane['biezaca_strona'] ?? 1;

    // Ustal kierunek sortowania
    $tn_kierunek_sortowania = 'asc'; // Domyślnie rosnąco
    if ($tn_biezace_sortowanie === $tn_sortuj_po . '_asc') {
        $tn_kierunek_sortowania = 'desc'; // Zmień na malejąco
    }
    // Wyjątek dla daty - domyślnie desc, pierwsze kliknięcie zmienia na asc
    if ($tn_sortuj_po === 'date' && $tn_biezace_sortowanie === 'date_desc') {
         $tn_kierunek_sortowania = 'asc';
    }

    $tn_nowe_sortowanie = $tn_sortuj_po . '_' . $tn_kierunek_sortowania;

    // Ikona sortowania
    $tn_ikona = '';
    if ($tn_biezace_sortowanie === $tn_sortuj_po . '_asc') { $tn_ikona = ' <i class="bi bi-sort-up"></i>'; }
    elseif ($tn_biezace_sortowanie === $tn_sortuj_po . '_desc') { $tn_ikona = ' <i class="bi bi-sort-down"></i>'; }

    // Parametry URL
    $tn_parametry_url = [];
    if (!empty($tn_filtr_statusu) && $tn_filtr_statusu !== 'Wszystkie') { $tn_parametry_url['status'] = $tn_filtr_statusu; }
    if ($tn_biezaca_strona_pag > 1) { $tn_parametry_url['p'] = $tn_biezaca_strona_pag; }
    $tn_parametry_url['sort'] = $tn_nowe_sortowanie;

    // Generuj URL
    $tn_url = tn_generuj_url('orders', $tn_parametry_url);

    return '<a href="' . htmlspecialchars($tn_url) . '" class="text-decoration-none link-body-emphasis">' . htmlspecialchars($tn_etykieta) . $tn_ikona . '</a>';
}

/**
 * Generuje link do sortowania tabeli zwrotów/reklamacji.
 *
 * @param string $tn_sortuj_po Klucz sortowania (np. 'id', 'date_created').
 * @param string $tn_etykieta Etykieta linku (np. 'ID', 'Data utworzenia').
 * @return string HTML linku sortowania.
 */
function tn_generuj_link_sortowania_zwrotow(string $tn_sortuj_po, string $tn_etykieta): string {
    global $tn_zwroty_dane;
    if (!isset($tn_zwroty_dane)) return htmlspecialchars($tn_etykieta);

    $tn_biezace_sortowanie = $tn_zwroty_dane['sortowanie'] ?? 'date_created_desc';
    $tn_biezaca_strona_pag = $tn_zwroty_dane['biezaca_strona'] ?? 1;
    $tn_filtr_statusu = $tn_zwroty_dane['filtr_status'] ?? '';
    $tn_filtr_typu = $tn_zwroty_dane['filtr_typ'] ?? '';
    $tn_zapytanie_szukania = $tn_zwroty_dane['zapytanie_szukania'] ?? '';

    // Ustal kierunek sortowania
    $tn_kierunek_sortowania = 'asc'; // Domyślnie rosnąco
    if ($tn_biezace_sortowanie === $tn_sortuj_po . '_asc') {
        $tn_kierunek_sortowania = 'desc'; // Zmień na malejąco
    }
    // Wyjątek dla daty - domyślnie desc, pierwsze kliknięcie zmienia na asc
    if ($tn_sortuj_po === 'date_created' && $tn_biezace_sortowanie === 'date_created_desc') {
        $tn_kierunek_sortowania = 'asc';
    }

    $tn_nowe_sortowanie = $tn_sortuj_po . '_' . $tn_kierunek_sortowania;

    // Ikona sortowania
    $tn_ikona = '';
    if ($tn_biezace_sortowanie === $tn_sortuj_po . '_asc') { $tn_ikona = ' <i class="bi bi-sort-up"></i>'; }
    elseif ($tn_biezace_sortowanie === $tn_sortuj_po . '_desc') { $tn_ikona = ' <i class="bi bi-sort-down"></i>'; }

    // Parametry URL
    $tn_parametry_url = [];
    if (!empty($tn_filtr_statusu) && $tn_filtr_statusu !== 'Wszystkie') { $tn_parametry_url['status'] = $tn_filtr_statusu; }
    if (!empty($tn_filtr_typu) && $tn_filtr_typu !== 'Wszystkie') { $tn_parametry_url['type'] = $tn_filtr_typu; }
    if ($tn_biezaca_strona_pag > 1) { $tn_parametry_url['p'] = $tn_biezaca_strona_pag; }
    $tn_parametry_url['sort'] = $tn_nowe_sortowanie;
    if (!empty($tn_zapytanie_szukania)) { $tn_parametry_url['search'] = $tn_zapytanie_szukania; }

    // Generuj URL
    $tn_url = tn_generuj_url('returns_list', $tn_parametry_url);

    return '<a href="' . htmlspecialchars($tn_url) . '" class="text-decoration-none link-body-emphasis">' . htmlspecialchars($tn_etykieta) . $tn_ikona . '</a>';
}


/**
 * Pomocnicza funkcja do generowania atrybutu selected dla <option>.
 */
if (!function_exists('selected')) {
    function selected($a, $b): void {
        if ((string)$a === (string)$b) echo ' selected';
    }
}

/**
 * Pomocnicza funkcja do generowania atrybutu checked dla checkbox/radio.
 */
if (!function_exists('checked')) {
    function checked($a): void {
         if ($a) echo ' checked';
    }
}


?>