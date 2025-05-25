<?php
// src/functions/tn_settings_helpers.php

declare(strict_types=1);

/**
 * Przetwarza tekst z textarea dla linków menu.
 * Format: Tytuł|URL|ikona|Grupa|ID (ikona, grupa, ID opcjonalne).
 *
 * @param string $tn_surowy_tekst Tekst z textarea.
 * @param array &$tn_bledy Referencja do tablicy błędów.
 * @return array Przetworzona tablica linków menu lub pusta tablica w razie błędu.
 */
function tn_przetworz_ustawienia_menu(string $tn_surowy_tekst, array &$tn_bledy): array {
    $tn_przetworzone_linki = [];
    $tn_linie = explode("\n", str_replace(["\r\n", "\r"], "\n", $tn_surowy_tekst));
    $tn_uzyte_id = []; // Do sprawdzania unikalności generowanych ID

    foreach ($tn_linie as $tn_indeks => $tn_linia) {
        $tn_linia_trim = trim($tn_linia);
        if (empty($tn_linia_trim)) continue;

        $tn_czesci = explode('|', $tn_linia_trim, 5); // Max 5: Tytuł|URL|Ikona|Grupa|ID
        $tn_tytul = trim($tn_czesci[0] ?? '');
        $tn_url = trim($tn_czesci[1] ?? '');
        $tn_ikona = trim($tn_czesci[2] ?? '');
        $tn_grupa = trim($tn_czesci[3] ?? '');
        $tn_id_linku = trim($tn_czesci[4] ?? ''); // Pobierz ID, jeśli jest

        if (empty($tn_tytul) || empty($tn_url)) {
            $tn_bledy[] = "Błąd w menu (linia " . ($tn_indeks + 1) . "): Brak tytułu lub URL.";
            return [];
        }
        if ($tn_url !== '#' && !(preg_match('/^(https?:\/\/|\/)/', $tn_url) || filter_var($tn_url, FILTER_VALIDATE_URL))) {
             $tn_bledy[] = "Błąd w menu (linia " . ($tn_indeks + 1) . "): Nieprawidłowy URL/ścieżka '$tn_url'. Musi zaczynać się od '/', '#' lub być pełnym URL.";
             return [];
        }
        if (!empty($tn_ikona) && !preg_match('/^bi-[a-z0-9-]+$/i', $tn_ikona)) {
             $tn_bledy[] = "Błąd w menu (linia " . ($tn_indeks + 1) . "): Nieprawidłowy format ikony '$tn_ikona'.";
             return [];
        }
         if (!empty($tn_id_linku) && !preg_match('/^[a-zA-Z0-9_-]+$/', $tn_id_linku)) {
             $tn_bledy[] = "Błąd w menu (linia " . ($tn_indeks + 1) . "): Nieprawidłowy format ID '$tn_id_linku' (tylko litery, cyfry, -, _).";
             return [];
         }
         // Jeśli ID nie podano, wygeneruj unikalne
         if (empty($tn_id_linku)) {
            $tn_id_linku = 'menu-' . strtolower(preg_replace('/[^a-z0-9]+/', '-', $tn_tytul));
            $counter = 2;
            while (in_array($tn_id_linku, $tn_uzyte_id)) {
                 $tn_id_linku = 'menu-' . strtolower(preg_replace('/[^a-z0-9]+/', '-', $tn_tytul)) . '-' . $counter++;
            }
         } elseif (in_array($tn_id_linku, $tn_uzyte_id)) {
             $tn_bledy[] = "Błąd w menu (linia " . ($tn_indeks + 1) . "): Zduplikowane ID '$tn_id_linku'.";
             return [];
         }
         $tn_uzyte_id[] = $tn_id_linku;


        $tn_link_dane = [
            'tytul' => htmlspecialchars($tn_tytul, ENT_QUOTES, 'UTF-8'),
            'url' => htmlspecialchars($tn_url, ENT_QUOTES, 'UTF-8'), // Zapisz URL jak jest (np. '#' lub '/costam')
            'id' => $tn_id_linku // Zapisz ID
        ];
        if (!empty($tn_ikona)) $tn_link_dane['ikona'] = htmlspecialchars($tn_ikona, ENT_QUOTES, 'UTF-8');
        if (!empty($tn_grupa)) $tn_link_dane['grupa'] = htmlspecialchars($tn_grupa, ENT_QUOTES, 'UTF-8');

        // --- Dodano obsługę podmenu ---
        // Sprawdź, czy linia zaczyna się od wcięcia (np. spacji lub tabulatora) - to oznacza podmenu
        // Ta prosta implementacja zakłada, że podmenu jest bezpośrednio pod rodzicem
        if (str_starts_with($tn_linia, ' ') || str_starts_with($tn_linia, "\t")) {
             if (!empty($tn_przetworzone_linki)) {
                 $ostatni_indeks = count($tn_przetworzone_linki) - 1;
                 if (!isset($tn_przetworzone_linki[$ostatni_indeks]['submenu'])) {
                     $tn_przetworzone_linki[$ostatni_indeks]['submenu'] = [];
                 }
                 // Dodaj jako podmenu do ostatniego elementu nadrzędnego
                 // Usuń nadrzędny link z głównej listy, jeśli jest dodawany jako podmenu
                 $tn_submenu_item = $tn_link_dane;
                 // Upewnij się, że ID podmenu jest unikalne w kontekście rodzica
                 $sub_id_counter = 1;
                 $base_sub_id = 'submenu-' . $tn_id_linku . '-' . strtolower(preg_replace('/[^a-z0-9]+/', '-', $tn_submenu_item['tytul']));
                 $final_sub_id = $base_sub_id;
                 while(isset($tn_przetworzone_linki[$ostatni_indeks]['submenu'][$final_sub_id])) {
                     $final_sub_id = $base_sub_id . '-' . $sub_id_counter++;
                 }
                 $tn_submenu_item['id'] = $final_sub_id; // Ustaw unikalne ID dla podmenu
                 $tn_przetworzone_linki[$ostatni_indeks]['submenu'][] = $tn_submenu_item;
             } else {
                 $tn_bledy[] = "Błąd w menu (linia " . ($tn_indeks + 1) . "): Podmenu musi znajdować się pod elementem nadrzędnym.";
                 return [];
             }
        } else {
             $tn_przetworzone_linki[] = $tn_link_dane; // Dodaj jako element główny
        }
    }
    return $tn_przetworzone_linki;
}


/**
 * Przetwarza tekst z textarea dla kategorii produktów.
 *
 * @param string $tn_surowy_tekst Tekst z textarea.
 * @param array &$tn_bledy Referencja do tablicy błędów.
 * @return array Przetworzona tablica kategorii lub pusta tablica w razie błędu.
 */
function tn_przetworz_ustawienia_kategorii(string $tn_surowy_tekst, array &$tn_bledy): array {
    $tn_linie_kat = explode("\n", str_replace(["\r\n", "\r"], "\n", $tn_surowy_tekst));
    $tn_przetworzone_kategorie = array_map('trim', $tn_linie_kat);
    $tn_przetworzone_kategorie = array_map(fn($kat) => htmlspecialchars($kat, ENT_QUOTES, 'UTF-8'), $tn_przetworzone_kategorie);
    $tn_przetworzone_kategorie = array_filter($tn_przetworzone_kategorie, fn($kat) => !empty($kat));
    $tn_przetworzone_kategorie = array_values(array_unique($tn_przetworzone_kategorie)); // Usuń duplikaty

    if (empty($tn_przetworzone_kategorie)) {
        $tn_bledy[] = "Lista kategorii produktów nie może być pusta.";
        return [];
    }
    return $tn_przetworzone_kategorie;
}

?>