<?php
// templates/partials/tn_sidebar.php
/**
 * Pasek boczny nawigacji aplikacji.
 * Wersja: 1.8 (Obsługa linków JS, poprawiona logika ID/URL, mapa rodziców)
 *
 * Renderuje menu na podstawie konfiguracji z settings.json,
 * obsługuje podmenu zagnieżdżone, linki funkcyjne JS, wyświetla odznaki z licznikami
 * i zapewnia możliwość przewijania długiego menu.
 *
 * Oczekuje zmiennych z index.php:
 * @var array $tn_ustawienia_globalne - Główne ustawienia aplikacji.
 * @var string $tn_biezaca_strona_id - Identyfikator aktualnie wyświetlanej strony (np. 'dashboard').
 * @var array $tn_produkty           - Tablica produktów (dla liczników).
 * @var array $tn_zamowienia         - Tablica zamówień (dla liczników).
 * @var array $tn_zwroty             - Tablica zwrotów/reklamacji (dla liczników).
 * @var array $tn_page_map           - Mapa ścieżek URL -> ID stron z index.php (używana do budowy mapy rodziców).
 */

// --- Przygotowanie danych dla Paska Bocznego ---
// Sprawdzenie, czy podstawowe funkcje są dostępne
if (!function_exists('tn_generuj_url')) { die('Błąd krytyczny: Brak funkcji tn_generuj_url() w tn_sidebar.php'); }

// Odczytaj konfigurację menu i ustawienia wyglądu
$tn_linki_menu = $tn_ustawienia_globalne['linki_menu'] ?? [];
$tn_sidebar_kolor = $tn_ustawienia_globalne['wyglad']['tn_kolor_sidebar'] ?? 'ciemny';
$tn_sidebar_classes = 'tn-sidebar-' . ($tn_sidebar_kolor === 'jasny' ? 'jasny' : 'ciemny');
$tn_klasa_tytulu_grupy = ($tn_sidebar_kolor === 'jasny') ? 'text-dark opacity-75' : 'text-secondary';
$tn_sidebar_footer_class = ($tn_sidebar_kolor === 'jasny') ? 'text-muted' : 'text-body-secondary opacity-75';

// --- Liczniki dla odznak (Badges) ---
// (Logika obliczania liczników jak w poprzednich wersjach)
$tn_licznik_nowych_zamowien = 0; if (!empty($tn_zamowienia)) { foreach ($tn_zamowienia as $o) { if (($o['status'] ?? '') === 'Nowe') $tn_licznik_nowych_zamowien++; } }
$tn_licznik_niskiego_stanu = 0; $tn_prog_niskiego_stanu = $tn_ustawienia_globalne['tn_prog_niskiego_stanu'] ?? 5; if (!empty($tn_produkty)) { foreach ($tn_produkty as $p) { $stock = intval($p['stock'] ?? 0); if ($stock > 0 && $stock <= $tn_prog_niskiego_stanu) $tn_licznik_niskiego_stanu++; } }
$tn_licznik_nowych_zwrotow = 0; $tn_statusy_nowych_zwrotow = $tn_ustawienia_globalne['zwroty_reklamacje']['statusy'] ?? []; if (!empty($tn_zwroty) && !empty($tn_statusy_nowych_zwrotow)) { $tn_pierwszy_status_zwrotu = $tn_statusy_nowych_zwrotow[0] ?? 'Nowe zgłoszenie'; foreach ($tn_zwroty as $z) { if (($z['status'] ?? '') === $tn_pierwszy_status_zwrotu) $tn_licznik_nowych_zwrotow++; } }

// --- Określanie Aktywnego Elementu Menu i Rodzica ---
$tn_mapa_podstron_na_rodzica = []; // Mapa [id_strony_podmenu] => id_elementu_menu_rodzica
if (!function_exists('tn_zbuduj_mape_rodzicow')) {
    function tn_zbuduj_mape_rodzicow(array $linki, array &$mapa, string $parent_menu_id = null): void {
        foreach ($linki as $link) {
            $id_elementu = $link['id'] ?? null; // ID elementu menu (np. 'settings')
            $id_strony = $link['id'] ?? null; // ID strony, do której prowadzi (zakładamy to samo)

            // Poprawka: Mapuj tylko jeśli id_strony faktycznie istnieje i URL nie jest '#'
            if ($parent_menu_id && $id_strony && ($link['url'] ?? '#') !== '#') {
                $mapa[$id_strony] = $parent_menu_id;
            }
            // Przejdź do submenu, przekazując ID elementu nadrzędnego
            if (!empty($link['submenu']) && is_array($link['submenu']) && $id_elementu) {
                tn_zbuduj_mape_rodzicow($link['submenu'], $mapa, $id_elementu);
            }
        }
    }
    // Zbuduj mapę na podstawie struktury menu z ustawień
    tn_zbuduj_mape_rodzicow($tn_linki_menu, $tn_mapa_podstron_na_rodzica);
}

// Użyj ID bieżącej strony przekazanego z index.php
$tn_aktywny_id_strony = $tn_biezaca_strona_id ?? 'dashboard';
// Znajdź ID rodzica w mapie (jeśli bieżąca strona jest podstroną)
$tn_aktywny_id_rodzica = $tn_mapa_podstron_na_rodzica[$tn_aktywny_id_strony] ?? null;


// --- Funkcja Rekurencyjna do Renderowania Menu ---
if (!function_exists('tn_renderuj_elementy_menu')) {
    /**
     * Renderuje rekurencyjnie elementy menu i podmenu.
     * Zakłada, że klucz 'id' w $linki[] odpowiada identyfikatorowi strony używanemu w routingu.
     * Obsługuje linki funkcyjne JS (url zaczynający się od 'js:').
     * @param array $elementy Tablica elementów menu/podmenu do wyrenderowania.
     * @param string $klasa_ul Dodatkowe klasy CSS dla tagu <ul>.
     * @param string|null $aktywny_id ID aktualnie aktywnej strony (z index.php).
     * @param string|null $aktywny_rodzic_id ID aktywnego elementu nadrzędnego (obliczone wyżej).
     */
    function tn_renderuj_elementy_menu(array $elementy, string $klasa_ul = '', ?string $aktywny_id = null, ?string $aktywny_rodzic_id = null) {
        global $tn_klasa_tytulu_grupy, $tn_licznik_nowych_zamowien, $tn_licznik_niskiego_stanu, $tn_licznik_nowych_zwrotow;
        static $tn_statyczna_grupa = null; if (empty($klasa_ul)) { $tn_statyczna_grupa = null; }

         echo "<ul class=\"nav nav-pills flex-column {$klasa_ul}\">";

         foreach ($elementy as $tn_link):
             $tn_tytul = htmlspecialchars($tn_link['tytul'] ?? 'Brak');
             $tn_url_cfg = trim($tn_link['url'] ?? '#');
             $tn_ikona = htmlspecialchars($tn_link['ikona'] ?? 'bi-link-45deg');
             $tn_grupa = $tn_link['grupa'] ?? null;
             $tn_id_elementu = $tn_link['id'] ?? null; // ID elementu (np. 'dashboard', 'settings')
             $tn_submenu = (!empty($tn_link['submenu']) && is_array($tn_link['submenu'])) ? $tn_link['submenu'] : null;

             // Pomiń element, jeśli nie ma ID (chyba że to separator lub nagłówek grupy)
             if ($tn_id_elementu === null && $tn_url_cfg !== '#' && empty($tn_grupa)) { error_log("Brak ID dla menu: {$tn_tytul}"); continue; }
             // Wygeneruj ID dla kontenerów submenu bez ID, ale z tytułem
             if ($tn_id_elementu === null && $tn_url_cfg === '#') { $tn_id_elementu = preg_replace('/[^a-z0-9]+/', '-', strtolower($tn_tytul)); }

             // Nagłówek grupy
             if ($tn_grupa !== null && $tn_grupa !== $tn_statyczna_grupa && empty($klasa_ul)) { $tn_statyczna_grupa = $tn_grupa; echo '<li class="nav-item mt-2 mb-1 tn-menu-sekcja-tytul px-3"><small class="' . $GLOBALS['tn_klasa_tytulu_grupy'] . ' text-uppercase fw-bold">' . htmlspecialchars($tn_grupa) . '</small></li>'; }

             // Sprawdzenie linku JS
             $is_js_link = str_starts_with($tn_url_cfg, 'js:');
             $js_function_call = '';
             if ($is_js_link) { $js_function_call = trim(substr($tn_url_cfg, 3)); $js_function_call = preg_replace('/[^\w\.\(\)\_\,\'\"\;]/', '', $js_function_call); if(!str_ends_with($js_function_call, ';')) { $js_function_call .= ';'; } $js_function_call .= ' return false;'; }

             // Aktywny stan
             $tn_czy_aktywny = (!$is_js_link && $tn_url_cfg !== '#' && $aktywny_id === $tn_id_elementu);
             $tn_czy_rodzic_aktywny = ($aktywny_rodzic_id === $tn_id_elementu);
             $tn_aktywna_klasa = ($tn_czy_aktywny || $tn_czy_rodzic_aktywny) ? 'active' : '';

             // Generuj URL
             $tn_finalny_url = '#'; if (!$is_js_link && $tn_url_cfg !== '#') { $tn_finalny_url = tn_generuj_url($tn_id_elementu); }

             // Odznaki (badge)
             $tn_badge_html = '';
              if ($tn_id_elementu === 'orders' && ($tn_licznik_nowych_zamowien ?? 0) > 0) { $tn_badge_html = '<span class="badge bg-danger rounded-pill ms-auto">' . $tn_licznik_nowych_zamowien . '</span>'; }
              elseif ($tn_id_elementu === 'products' && ($tn_licznik_niskiego_stanu ?? 0) > 0) { $tn_badge_html = '<span class="badge bg-warning text-dark rounded-pill ms-auto">' . $tn_licznik_niskiego_stanu . '</span>'; }
              elseif ($tn_id_elementu === 'returns_list' && ($tn_licznik_nowych_zwrotow ?? 0) > 0) { $tn_badge_html = '<span class="badge bg-info rounded-pill ms-auto">' . $tn_licznik_nowych_zwrotow . '</span>'; }

             echo '<li class="nav-item">';
             $link_attrs = 'class="nav-link d-flex align-items-center ' . $tn_aktywna_klasa . '" '; $onclick_attr = $is_js_link ? 'onclick="' . htmlspecialchars($js_function_call, ENT_QUOTES) . '"' : '';
             $link_tag_open = '<a href="' . $tn_finalny_url . '" ' . $link_attrs . $onclick_attr . '>'; $link_tag_close = '</a>';
             if ($tn_submenu !== null) { $link_attrs .= ' data-bs-toggle="collapse" data-bs-target="#collapse-' . $tn_id_elementu . '" aria-expanded="' . ($tn_czy_rodzic_aktywny ? 'true' : 'false') . '" aria-controls="collapse-' . $tn_id_elementu . '" '; if($tn_url_cfg === '#' && !$is_js_link) { $link_tag_open = '<button type="button" ' . $link_attrs . ' style="border: none; background: none; width: 100%; text-align: left; padding: 0.6rem 1rem; color: inherit;">'; $link_tag_close = '</button>'; } else { $link_tag_open = '<a href="'. '" ' . $link_attrs . $onclick_attr . '>'; $link_tag_close = '</a>'; } }
             echo $link_tag_open; ?> <i class="bi <?php echo $tn_ikona; ?> me-2 tn-ikona-menu"></i> <span class="flex-grow-1"><?php echo $tn_tytul; ?></span> <?php echo $tn_badge_html; ?> <?php if ($tn_submenu !== null): ?><i class="bi bi-chevron-down tn-submenu-arrow ms-auto"></i><?php endif; ?> <?php echo $link_tag_close;
             if ($tn_submenu !== null) { $collapse_show_class = $tn_czy_rodzic_aktywny ? 'show' : ''; echo '<div class="collapse ' . $collapse_show_class . '" id="collapse-' . $tn_id_elementu . '">'; tn_renderuj_elementy_menu($tn_submenu, 'tn-submenu ps-3', $aktywny_id, $tn_id_elementu); echo '</div>'; }
             echo '</li>'; endforeach; echo "</ul>";
    }
}
?>
<aside class="sidebar tn-sidebar d-none d-md-flex flex-column flex-shrink-0 p-3 shadow-sm vh-100 sticky-top <?php echo $tn_sidebar_classes; ?>"> <?php // Dodano klasy d-none d-md-flex ?>

    <a href="<?php echo tn_generuj_url('dashboard'); ?>" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-decoration-none tn-logo-link">
        <div class="logo tn-logo d-flex align-items-center">
             <?php if (!empty($tn_ustawienia_globalne['logo_strony'])): ?><img src="<?php echo htmlspecialchars($tn_ustawienia_globalne['logo_strony']); ?>" alt="<?php echo htmlspecialchars($tn_ustawienia_globalne['nazwa_strony']); ?>" class="tn-logo-img me-2" style="max-height: 100px; width: auto;"><p>.</p><?php endif; ?>

        </div>
    </a>


    <?php // --- Renderowanie Menu Głównego --- ?>
    <div class="tn-menu-scroll-wrapper flex-grow-1" style="overflow-y: auto; overflow-x: hidden; position: relative; z-index: 1;"> <?php // Wrapper do przewijania i z-index ?>
        <?php
             if (function_exists('tn_renderuj_elementy_menu')) {
                 tn_renderuj_elementy_menu($tn_linki_menu, '', $tn_aktywny_id_strony, $tn_aktywny_id_rodzica);
             } else { echo "<p class='text-danger p-3'>Błąd: Funkcja renderowania menu.</p>"; }
        ?>
    </div>
</aside>

<style>
.tn-submenu { list-style: none; padding-left: 1.1rem; margin-left: 0.6rem; border-left: 1px solid var(--tn-sidebar-border); padding-top: 0.25rem; padding-bottom: 0.25rem; } .tn-submenu .nav-item { margin-left: -0.6rem; } .tn-submenu .nav-link { font-size: 0.85em; padding: 0.4rem 1rem; color: var(--bs-secondary-color); } .tn-submenu .nav-link i.tn-ikona-menu { font-size: 1em; width: 1.1em; } .tn-submenu .nav-link:hover { color: var(--tn-sidebar-hover-text) !important; background-color: var(--tn-sidebar-hover-bg); } .tn-submenu .nav-link.active { color: var(--tn-sidebar-active-text) !important; background-color: transparent; font-weight: 600; }
a[data-bs-toggle="collapse"] .tn-submenu-arrow, button[data-bs-toggle="collapse"] .tn-submenu-arrow { transition: transform 0.2s ease-in-out; font-size: 0.7em; color: var(--bs-secondary-color); } a[data-bs-toggle="collapse"][aria-expanded="true"] .tn-submenu-arrow, button[data-bs-toggle="collapse"][aria-expanded="true"] .tn-submenu-arrow { transform: rotate(180deg); color: inherit; }

</style>