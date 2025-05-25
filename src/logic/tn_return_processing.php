<?php
// src/logic/tn_return_processing.php
// Wersja 1.1 (Poprawiono sygnaturę funkcji)

/**
 * Ten plik zawiera logikę biznesową do przetwarzania listy zwrotów/reklamacji,
 * taką jak sortowanie, filtrowanie (do dodania) i paginacja.
 */

/**
 * Przetwarza zgłoszenia zwrotów/reklamacji: sortuje i przygotowuje dane do paginacji.
 *
 * @param array $tn_zwroty Wejściowa tablica wszystkich zgłoszeń zwrotów/reklamacji.
 * @param array $tn_parametry_get Tablica parametrów GET (np. $_GET['sort'], $_GET['p'], ew. filtry).
 * @param int $tn_na_stronie Liczba zgłoszeń do wyświetlenia na jednej stronie.
 * @return array Wynikowa tablica z kluczami:
 * 'zwroty_wyswietlane' => Tablica zgłoszeń dla bieżącej strony.
 * 'ilosc_wszystkich'   => Całkowita liczba zgłoszeń (po ew. filtrowaniu).
 * 'ilosc_stron'        => Całkowita liczba stron paginacji.
 * 'biezaca_strona'     => Numer bieżącej strony.
 * 'sortowanie'         => Aktualnie używany parametr sortowania (np. 'date_desc').
 * // TODO: Dodać 'filtry' => Aktywne filtry
 */
function tn_przetworz_liste_zwrotow(array $tn_zwroty, array $tn_parametry_get, int $tn_na_stronie) : array {

    // 1. Pobierz parametry sortowania i paginacji z GET
    $tn_sortowanie = $tn_parametry_get['sort'] ?? 'date_desc'; // Domyślnie sortuj po dacie utworzenia malejąco
    $tn_biezaca_strona = filter_var($tn_parametry_get['p'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($tn_biezaca_strona === false) $tn_biezaca_strona = 1;

    // 2. Filtrowanie (TODO: Rozbudować w przyszłości)
    // Przykład: Filtrowanie po statusie
    // $tn_filtr_status = $tn_parametry_get['status'] ?? null;
    // $tn_filtrowane_zwroty = $tn_zwroty;
    // if ($tn_filtr_status !== null && $tn_filtr_status !== 'Wszystkie') {
    //     $tn_filtrowane_zwroty = array_filter($tn_filtrowane_zwroty, fn($z) => ($z['status'] ?? '') === $tn_filtr_status);
    // }
    // Przykład: Filtrowanie po typie
    // $tn_filtr_typ = $tn_parametry_get['type'] ?? null;
    // if ($tn_filtr_typ !== null && in_array($tn_filtr_typ, ['zwrot', 'reklamacja'])) {
    //      $tn_filtrowane_zwroty = array_filter($tn_filtrowane_zwroty, fn($z) => ($z['type'] ?? '') === $tn_filtr_typ);
    // }

    $tn_filtrowane_zwroty = $tn_zwroty; // Na razie używamy wszystkich

    // 3. Przygotowanie danych do sortowania (dodanie timestampów)
    $tn_zwroty_do_sortowania = array_map(function($zgloszenie) {
        // Konwertuj daty na timestampy dla łatwiejszego sortowania numerycznego
        // Użyj @, aby stłumić błędy, jeśli format daty jest nieprawidłowy
        $zgloszenie['timestamp_created'] = isset($zgloszenie['date_created']) ? @strtotime($zgloszenie['date_created']) ?: 0 : 0;
        $zgloszenie['timestamp_updated'] = isset($zgloszenie['date_updated']) ? @strtotime($zgloszenie['date_updated']) ?: 0 : 0;
        // Dodaj klucze z małymi literami dla sortowania case-insensitive
        $zgloszenie['sort_customer'] = isset($zgloszenie['customer_name']) ? mb_strtolower($zgloszenie['customer_name']) : '';
        $zgloszenie['sort_status'] = isset($zgloszenie['status']) ? mb_strtolower($zgloszenie['status']) : '';
        $zgloszenie['sort_type'] = isset($zgloszenie['type']) ? mb_strtolower($zgloszenie['type']) : '';
        return $zgloszenie;
    }, $tn_filtrowane_zwroty);

    // 4. Sortowanie
    $tn_sort_parts = explode('_', $tn_sortowanie);
    $tn_sort_key = $tn_sort_parts[0] ?? 'date'; // Domyślny klucz sortowania
    $tn_sort_direction_str = $tn_sort_parts[1] ?? 'desc'; // Domyślny kierunek
    $tn_sort_direction = ($tn_sort_direction_str === 'asc') ? SORT_ASC : SORT_DESC; // Kierunek dla array_multisort
    $tn_sort_flags = SORT_REGULAR; // Domyślna flaga sortowania

    // Wybierz kolumnę i flagi sortowania na podstawie klucza
    switch ($tn_sort_key) {
        case 'id':
        case 'order_id':
        case 'product_id':
            $tn_kolumna_sort = array_column($tn_zwroty_do_sortowania, $tn_sort_key);
            $tn_sort_flags = SORT_NUMERIC;
            break;
        case 'date': // Sortowanie po dacie utworzenia
            $tn_kolumna_sort = array_column($tn_zwroty_do_sortowania, 'timestamp_created');
            $tn_sort_flags = SORT_NUMERIC;
            // Upewnij się, że domyślne sortowanie po dacie ('date') jest DESC
            if ($tn_sort_direction_str !== 'asc') { $tn_sortowanie = 'date_desc'; $tn_sort_direction = SORT_DESC; } else { $tn_sortowanie = 'date_asc'; }
            break;
        case 'status':
            $tn_kolumna_sort = array_column($tn_zwroty_do_sortowania, 'sort_status'); // Użyj kolumny z małymi literami
            $tn_sort_flags = SORT_STRING | SORT_FLAG_CASE; // Użyj SORT_STRING dla naturalnego sortowania tekstu
            break;
        case 'type':
            $tn_kolumna_sort = array_column($tn_zwroty_do_sortowania, 'sort_type');
            $tn_sort_flags = SORT_STRING | SORT_FLAG_CASE;
            break;
        case 'customer':
             $tn_kolumna_sort = array_column($tn_zwroty_do_sortowania, 'sort_customer');
             $tn_sort_flags = SORT_STRING | SORT_FLAG_CASE;
             break;
        default:
            // Nieznany klucz sortowania - użyj domyślnego
            $tn_sortowanie = 'date_desc'; // Zaktualizuj parametr sortowania
            $tn_kolumna_sort = array_column($tn_zwroty_do_sortowania, 'timestamp_created');
            $tn_sort_direction = SORT_DESC;
            $tn_sort_flags = SORT_NUMERIC;
            break;
    }

    // Wykonaj sortowanie, jeśli są dane i kolumna sortowania została poprawnie przygotowana
    if (!empty($tn_zwroty_do_sortowania) && isset($tn_kolumna_sort) && count($tn_kolumna_sort) === count($tn_zwroty_do_sortowania)) {
        try {
            // Użyj array_multisort do sortowania oryginalnej tablicy na podstawie wybranej kolumny
            array_multisort(
                $tn_kolumna_sort,   // Kolumna do sortowania
                $tn_sort_direction, // Kierunek (ASC/DESC)
                $tn_sort_flags,     // Flagi sortowania (NUMERIC/STRING etc.)
                $tn_zwroty_do_sortowania // Tablica do posortowania (modyfikowana przez referencję)
            );
        } catch (Exception $e) {
             error_log("Błąd podczas sortowania zwrotów: " . $e->getMessage());
             // W przypadku błędu sortowania, zwróć nieposortowaną listę
        }
    }

    // 5. Stronicowanie (Paginacja)
    $tn_ilosc_wszystkich = count($tn_zwroty_do_sortowania); // Liczba elementów po filtrowaniu
    $tn_ilosc_stron = ($tn_na_stronie > 0) ? ceil($tn_ilosc_wszystkich / $tn_na_stronie) : 1;
    // Poprawka: Upewnij się, że bieżąca strona nie jest większa niż liczba dostępnych stron
    $tn_biezaca_strona = max(1, min($tn_biezaca_strona, $tn_ilosc_stron == 0 ? 1 : $tn_ilosc_stron));
    $tn_offset = ($tn_biezaca_strona - 1) * $tn_na_stronie;

    // Wycięcie fragmentu tablicy dla bieżącej strony
    $tn_zwroty_wyswietlane = ($tn_na_stronie > 0) ? array_slice($tn_zwroty_do_sortowania, $tn_offset, $tn_na_stronie) : $tn_zwroty_do_sortowania;

    // 6. Zwróć przetworzone dane
    return [
        'zwroty_wyswietlane' => $tn_zwroty_wyswietlane, // Zgłoszenia do wyświetlenia na bieżącej stronie
        'ilosc_wszystkich'   => $tn_ilosc_wszystkich,   // Całkowita liczba zgłoszeń (po filtrowaniu)
        'ilosc_stron'        => $tn_ilosc_stron,        // Całkowita liczba stron paginacji
        'biezaca_strona'     => $tn_biezaca_strona,     // Numer bieżącej strony
        'sortowanie'         => $tn_sortowanie         // Aktualny parametr sortowania
        // TODO: Dodać 'filtry' => Aktywne filtry
    ];
}
?>