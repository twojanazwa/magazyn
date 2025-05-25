<?php
// src/logic/tn_order_processing.php

/**
 * Przetwarza zamówienia: filtruje, sortuje i przygotowuje do paginacji.
 *
 * @param array $tn_zamowienia Wejściowa tablica zamówień.
 * @param array $tn_produkty Tablica produktów (do pobrania cen).
 * @param array $tn_parametry_get Tablica $_GET (lub sparsowane parametry).
 * @param int $tn_na_stronie Liczba zamówień na stronie.
 * @return array Wynikowa tablica z kluczami: 'zamowienia_wyswietlane', 'ilosc_wszystkich', 'ilosc_stron', 'biezaca_strona', 'sortowanie', 'status'
 */
function tn_przetworz_liste_zamowien(array $tn_zamowienia, array $tn_produkty, array $tn_parametry_get, int $tn_na_stronie) : array {

    // Pobierz parametry
    $tn_sortowanie = $tn_parametry_get['sort'] ?? 'date_desc';
    $tn_filtr_statusu = $tn_parametry_get['status'] ?? '';
    $tn_biezaca_strona = filter_var($tn_parametry_get['p'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($tn_biezaca_strona === false) $tn_biezaca_strona = 1;

    // Mapa cen produktów
    $tn_mapa_cen = array_column($tn_produkty, 'price', 'id');

    // Dodaj wartość i timestamp do zamówień
    $tn_zamowienia_z_wartoscia = array_map(function($zam) use ($tn_mapa_cen) {
        $productId = $zam['product_id'] ?? null; $quantity = $zam['quantity'] ?? 0; $price = $tn_mapa_cen[$productId] ?? 0;
        $zam['order_value'] = $price * $quantity;
        $zam['order_timestamp'] = isset($zam['order_date']) ? strtotime($zam['order_date']) : 0;
        return $zam;
    }, $tn_zamowienia);

    // Filtrowanie po statusie
    $tn_filtrowane_zamowienia = $tn_zamowienia_z_wartoscia;
    global $tn_prawidlowe_statusy;
    if ($tn_filtr_statusu !== '' && $tn_filtr_statusu !== 'Wszystkie' && in_array($tn_filtr_statusu, $tn_prawidlowe_statusy ?? [])) {
        $tn_filtrowane_zamowienia = array_filter($tn_filtrowane_zamowienia, function ($zam) use ($tn_filtr_statusu) {
            return isset($zam['status']) && $zam['status'] === $tn_filtr_statusu;
        });
    }

    // Sortowanie
    $tn_sort_key = str_replace(['_asc', '_desc'], '', $tn_sortowanie);
    $tn_sort_direction = str_ends_with($tn_sortowanie, '_asc') ? SORT_ASC : SORT_DESC;

    // Klucze do sortowania
    $tn_kolumna_do_sortowania = match ($tn_sort_key) {
        'id' => array_column($tn_filtrowane_zamowienia, 'id'),
        'customer' => array_column($tn_filtrowane_zamowienia, 'buyer_name'),
        'status' => array_column($tn_filtrowane_zamowienia, 'status'),
        'value' => array_column($tn_filtrowane_zamowienia, 'order_value'),
        'date' => array_column($tn_filtrowane_zamowienia, 'order_timestamp'),
        default => array_column($tn_filtrowane_zamowienia, 'order_timestamp')
    };

    // Ustalenie domyślnego kierunku i flag sortowania
    $tn_sort_flags = SORT_REGULAR; // Domyślna flaga
    if ($tn_sort_key === 'date' && !str_ends_with($tn_sortowanie, '_asc') && !str_ends_with($tn_sortowanie, '_desc')) {
         $tn_sort_direction = SORT_DESC; $tn_sortowanie = 'date_desc'; $tn_sort_flags = SORT_NUMERIC;
    } elseif (!in_array($tn_sort_key, ['id', 'customer', 'status', 'value', 'date'])) {
         $tn_sortowanie = 'date_desc'; $tn_kolumna_do_sortowania = array_column($tn_filtrowane_zamowienia, 'order_timestamp'); $tn_sort_direction = SORT_DESC; $tn_sort_flags = SORT_NUMERIC;
    } elseif (in_array($tn_sort_key, ['customer', 'status'])) {
         $tn_sort_flags = SORT_STRING | SORT_FLAG_CASE; // Sortowanie tekstowe bez wielkości liter
    } elseif (in_array($tn_sort_key, ['id', 'value', 'date'])) {
         $tn_sort_flags = SORT_NUMERIC; // Sortowanie numeryczne
    }

    // Wykonaj sortowanie, jeśli są dane
    if (!empty($tn_filtrowane_zamowienia)) {
        // *** POPRAWKA: Przekaż flagę bezpośrednio, a tablicę do sortowania na końcu ***
        array_multisort(
            $tn_kolumna_do_sortowania, // Kolumna, według której sortujemy
            $tn_sort_direction,       // Kierunek (ASC/DESC)
            $tn_sort_flags,           // Flagi sortowania (np. SORT_NUMERIC, SORT_STRING | SORT_FLAG_CASE)
            $tn_filtrowane_zamowienia // Tablica, która ma zostać posortowana
        );
    }

    // Stronicowanie (bez zmian)
    $tn_ilosc_wszystkich = count($tn_filtrowane_zamowienia);
    $tn_ilosc_stron = $tn_na_stronie > 0 ? ceil($tn_ilosc_wszystkich / $tn_na_stronie) : 1;
    $tn_biezaca_strona = max(1, min($tn_biezaca_strona, $tn_ilosc_stron == 0 ? 1 : $tn_ilosc_stron));
    $tn_offset = ($tn_biezaca_strona - 1) * $tn_na_stronie;
    $tn_zamowienia_wyswietlane = $tn_na_stronie > 0 ? array_slice($tn_filtrowane_zamowienia, $tn_offset, $tn_na_stronie) : $tn_filtrowane_zamowienia;

    return [
        'zamowienia_wyswietlane' => $tn_zamowienia_wyswietlane,
        'ilosc_wszystkich'     => $tn_ilosc_wszystkich,
        'ilosc_stron'          => $tn_ilosc_stron,
        'biezaca_strona'       => $tn_biezaca_strona,
        'sortowanie'           => $tn_sortowanie,
        'status'               => $tn_filtr_statusu
    ];
}
?>