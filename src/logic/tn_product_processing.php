<?php
// src/logic/tn_product_processing.php

/**
 * Przetwarza produkty: filtruje (tekst, kategoria), sortuje i przygotowuje do paginacji.
 *
 * @param array $tn_produkty Wejściowa tablica wszystkich produktów.
 * @param array $tn_parametry_get Tablica parametrów GET (np. z $_GET lub sparsowana z URI).
 * @param int $tn_na_stronie Liczba produktów na stronie.
 * @return array Wynikowa tablica z kluczami:
 * 'produkty_wyswietlane' => tablica produktów dla bieżącej strony,
 * 'ilosc_wszystkich' => całkowita liczba produktów po filtracji,
 * 'ilosc_stron' => całkowita liczba stron,
 * 'biezaca_strona' => numer bieżącej strony,
 * 'zapytanie_szukania' => aktywne zapytanie tekstowe,
 * 'sortowanie' => aktywne sortowanie (np. 'name_asc'),
 * 'kategoria' => aktywny filtr kategorii
 */
function tn_przetworz_liste_produktow(array $tn_produkty, array $tn_parametry_get, int $tn_na_stronie) : array {

    // Pobierz parametry z GET (lub przekazanej tablicy)
    $tn_zapytanie_szukania = isset($tn_parametry_get['search']) ? trim(htmlspecialchars($tn_parametry_get['search'], ENT_QUOTES, 'UTF-8')) : '';
    $tn_sortowanie = isset($tn_parametry_get['sort']) ? htmlspecialchars($tn_parametry_get['sort'], ENT_QUOTES, 'UTF-8') : 'name_asc'; // Domyślne sortowanie
    $tn_biezaca_strona = isset($tn_parametry_get['p']) ? filter_var($tn_parametry_get['p'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : 1;
    if ($tn_biezaca_strona === false) $tn_biezaca_strona = 1;
    $tn_filtr_kategorii = isset($tn_parametry_get['category']) ? trim(htmlspecialchars($tn_parametry_get['category'], ENT_QUOTES, 'UTF-8')) : '';

    $tn_filtrowane_produkty = $tn_produkty;

    // 1. Filtrowanie po kategorii (jeśli wybrano)
    if ($tn_filtr_kategorii !== '') {
        $tn_filtrowane_produkty = array_filter($tn_filtrowane_produkty, function ($tn_p) use ($tn_filtr_kategorii) {
            // Porównanie bez uwzględniania wielkości liter
            return isset($tn_p['category']) && strcasecmp($tn_p['category'], $tn_filtr_kategorii) === 0;
        });
    }

    // 2. Filtrowanie tekstowe (po już przefiltrowanych kategoriach)
    if ($tn_zapytanie_szukania !== '') {
        $tn_filtrowane_produkty = array_filter($tn_filtrowane_produkty, function ($tn_p) use ($tn_zapytanie_szukania) {
            $tn_szukaj = mb_strtolower($tn_zapytanie_szukania, 'UTF-8'); // Konwertuj raz, użyj mb_strtolower dla UTF-8
            return (
                   mb_stripos($tn_p['name'] ?? '', $tn_szukaj, 0, 'UTF-8') !== false ||
                   mb_stripos($tn_p['producent'] ?? '', $tn_szukaj, 0, 'UTF-8') !== false ||
                   mb_stripos($tn_p['tn_numer_katalogowy'] ?? '', $tn_szukaj, 0, 'UTF-8') !== false || // Wyszukaj po Nr kat.
                   mb_stripos($tn_p['warehouse'] ?? '', $tn_szukaj, 0, 'UTF-8') !== false ||
                   mb_stripos($tn_p['desc'] ?? '', $tn_szukaj, 0, 'UTF-8') !== false ||
                   (is_numeric($tn_szukaj) && ($tn_p['id'] ?? null) == $tn_szukaj) // Szukanie po ID
                );
        });
    }

    // 3. Sortowanie
    // Mapa funkcji sortujących dla różnych kluczy
    $tn_funkcje_sortowania = [
        'name_asc' => fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''),
        'name_desc' => fn($a, $b) => strcasecmp($b['name'] ?? '', $a['name'] ?? ''),
        'catalog_nr_asc' => fn($a, $b) => strcasecmp($a['tn_numer_katalogowy'] ?? '', $b['tn_numer_katalogowy'] ?? ''), // Sortowanie po Nr kat.
        'catalog_nr_desc' => fn($a, $b) => strcasecmp($b['tn_numer_katalogowy'] ?? '', $a['tn_numer_katalogowy'] ?? ''),
        'price_asc' => fn($a, $b) => ($a['price'] ?? 0) <=> ($b['price'] ?? 0),
        'price_desc'=> fn($a, $b) => ($b['price'] ?? 0) <=> ($a['price'] ?? 0),
        'stock_asc' => fn($a, $b) => ($a['stock'] ?? 0) <=> ($b['stock'] ?? 0),
        'stock_desc'=> fn($a, $b) => ($b['stock'] ?? 0) <=> ($a['stock'] ?? 0),
        'id_asc' => fn($a, $b) => ($a['id'] ?? 0) <=> ($b['id'] ?? 0),
        'id_desc' => fn($a, $b) => ($b['id'] ?? 0) <=> ($a['id'] ?? 0),
    ];
    // Sprawdź, czy podany klucz sortowania istnieje, jeśli nie - użyj domyślnego
    if (isset($tn_funkcje_sortowania[$tn_sortowanie])) {
        usort($tn_filtrowane_produkty, $tn_funkcje_sortowania[$tn_sortowanie]);
    } else {
        // Fallback na sortowanie po nazwie rosnąco
        usort($tn_filtrowane_produkty, $tn_funkcje_sortowania['name_asc']);
        $tn_sortowanie = 'name_asc'; // Ustaw poprawny, użyty klucz sortowania
    }

    // 4. Stronicowanie
    $tn_ilosc_wszystkich = count($tn_filtrowane_produkty); // Liczba po filtracji
    $tn_ilosc_stron = $tn_na_stronie > 0 ? ceil($tn_ilosc_wszystkich / $tn_na_stronie) : 1;
    // Poprawka dla bieżącej strony, aby nie przekraczała liczby dostępnych stron
    $tn_biezaca_strona = max(1, min($tn_biezaca_strona, $tn_ilosc_stron == 0 ? 1 : $tn_ilosc_stron));
    $tn_offset = ($tn_biezaca_strona - 1) * $tn_na_stronie;
    // Użyj array_values, aby zresetować klucze przed slice dla spójności
    $tn_produkty_wyswietlane = $tn_na_stronie > 0 ? array_slice(array_values($tn_filtrowane_produkty), $tn_offset, $tn_na_stronie) : array_values($tn_filtrowane_produkty);

    // Zwróć przetworzone dane
    return [
        'produkty_wyswietlane' => $tn_produkty_wyswietlane,
        'ilosc_wszystkich'     => $tn_ilosc_wszystkich,
        'ilosc_stron'          => $tn_ilosc_stron,
        'biezaca_strona'       => $tn_biezaca_strona,
        'zapytanie_szukania'   => $tn_zapytanie_szukania,
        'sortowanie'           => $tn_sortowanie,
        'kategoria'            => $tn_filtr_kategorii // Zwróć aktywny filtr kategorii
    ];
}

?>