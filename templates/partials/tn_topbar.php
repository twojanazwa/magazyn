<?php

if (!function_exists('tn_generuj_url')) { die('Błąd krytyczny: Brak funkcji tn_generuj_url() w tn_topbar.php'); }
if (!function_exists('tn_generuj_link_akcji_get')) { die('Błąd krytyczny: Brak funkcji tn_generuj_link_akcji_get() w tn_topbar.php'); }
if (!function_exists('tn_get_avatar_path')) { die('Błąd krytyczny: Brak funkcji tn_get_avatar_path() w tn_topbar.php'); }


$tn_tytul_na_pasku = match ($tn_biezaca_strona_id) {
    'dashboard' => 'Pulpit',
    'product_preview' => 'Podgląd Produktu',
    'products' => 'Produkty',
    'order_preview' => 'Podgląd Zamówienia',
    'orders' => 'Zamówienia',
    'profile' => 'Mój Profil',
    'returns_list' => 'Zwroty i Reklamacje',
    'return_form_new' => 'Nowe Zgłoszenie',
    'return_form_edit' => 'Edycja Zgłoszenia',
    'return_preview' => 'Podgląd Zgłoszenia',
    'couriers_list' => 'Kurierzy',
    'settings' => 'Ustawienia',
    'info' => 'Informacje Systemowe',
    'warehouse_view' => 'Widok Magazynu',
    'help' => 'Pomoc',
    'login_page' => 'Logowanie',
    default => htmlspecialchars($tn_ustawienia_globalne['nazwa_strony'] ?? ''),
};


$tn_sortowanie = $tn_produkty_dane['sortowanie'] ?? 'name_asc';
$tn_zapytanie_szukania = $tn_produkty_dane['zapytanie_szukania'] ?? '';
$tn_filtr_kategorii_topbar = $tn_produkty_dane['kategoria'] ?? '';


$tn_link_logo_dashboard = tn_generuj_url('dashboard');


$tn_aktualny_motyw = $_COOKIE['tn_theme'] ?? ($tn_ustawienia_globalne['wyglad']['tn_motyw_domyslny'] ?? 'light');
$tn_ikona_motywu = ($tn_aktualny_motyw === 'dark') ? 'bi-moon-stars-fill' : 'bi-sun-fill';



$tn_zalogowany_uzytkownik_nazwa = $_SESSION['tn_user_fullname'] ?? ($_SESSION['tn_username'] ?? 'Użytkownik');
$tn_avatar_filename_z_sesji = $_SESSION['tn_user_avatar_filename'] ?? null;
$tn_zalogowany_avatar_path = tn_get_avatar_path($tn_avatar_filename_z_sesji);


$tn_licznik_nowych_zamowien = 0; if (!empty($tn_zamowienia)) { foreach ($tn_zamowienia as $o) { if (($o['status'] ?? '') === 'Nowe') $tn_licznik_nowych_zamowien++; } }
$tn_licznik_niskiego_stanu = 0; $tn_prog_niskiego_stanu = $tn_ustawienia_globalne['tn_prog_niskiego_stanu'] ?? 5; if (!empty($tn_produkty)) { foreach ($tn_produkty as $p) { $stock = intval($p['stock'] ?? 0); if ($stock > 0 && $stock <= $tn_prog_niskiego_stanu) $tn_licznik_niskiego_stanu++; } }
$tn_licznik_nowych_zwrotow = 0; $tn_statusy_nowych_zwrotow = $tn_ustawienia_globalne['zwroty_reklamacje']['statusy'] ?? []; if (!empty($tn_zwroty) && !empty($tn_statusy_nowych_zwrotow)) { $tn_pierwszy_status_zwrotu = $tn_statusy_nowych_zwrotow[0] ?? 'Nowe zgłoszenie'; foreach ($tn_zwroty as $z) { if (($z['status'] ?? '') === $tn_pierwszy_status_zwrotu) $tn_licznik_nowych_zwrotow++; } }

// Helper function to get all main menu item IDs (including submenu items)
if (!function_exists('tn_get_all_menu_ids')) {
    function tn_get_all_menu_ids(array $menu_items): array {
        $ids = [];
        foreach ($menu_items as $item) {
            if (!empty($item['id'])) {
                $ids[] = $item['id'];
            }
            if (!empty($item['submenu']) && is_array($item['submenu'])) {
                $ids = array_merge($ids, tn_get_all_menu_ids($item['submenu']));
            }
        }
        return $ids;
    }
}

// Get IDs of all items in the main navigation structure
$tn_all_main_menu_ids = tn_get_all_menu_ids($tn_linki_menu ?? []);


if (!function_exists('tn_renderuj_plaskie_menu_mobilne')) {
    /**
     * Renderuje płaską listę elementów menu na potrzeby menu mobilnego (Offcanvas).
     * Obsługuje linki funkcyjne JS (url zaczynający się od 'js:').
     * Pomija elementy bez ID lub z pustym URL (#), które są tylko kontenerami dla submenu.
     * Dodaje odznaki (badges) do odpowiednich elementów.
     * @param array $elementy Tablica elementów menu/podmenu do wyrenderowania.
     * @param string|null $aktywny_id ID aktualnie aktywnej strony.
     */
    function tn_renderuj_plaskie_menu_mobilne(array $elementy, ?string $aktywny_id = null) {
        global $tn_licznik_nowych_zamowien, $tn_licznik_niskiego_stanu, $tn_licznik_nowych_zwrotow;

        foreach ($elementy as $tn_link):
            $tn_tytul = htmlspecialchars($tn_link['tytul'] ?? 'Brak');
            $tn_url_cfg = trim($tn_link['url'] ?? '#');
            $tn_ikona = htmlspecialchars($tn_link['ikona'] ?? 'bi-link-45deg');
            $tn_id_elementu = $tn_link['id'] ?? null;

            // Renderuj tylko elementy, które mają ID i nie są tylko kontenerami bez URL i bez submenu
             if ($tn_id_elementu === null || ($tn_url_cfg === '#' && empty($tn_link['submenu']))) {
                // Jeśli element ma grupę, ale nie ma linku/id, potraktuj jako nagłówek sekcji w menu mobilnym
                 if (!empty($tn_link['grupa'])) {
                     echo '<li class="nav-item mt-2 mb-1 px-2"><h6 class="dropdown-header">' . htmlspecialchars($tn_link['grupa']) . '</h6></li>';
                 }
                 continue;
             }


            $is_js_link = str_starts_with($tn_url_cfg, 'js:');
            $js_function_call = '';
            if ($is_js_link) { $js_function_call = trim(substr($tn_url_cfg, 3)); $js_function_call = preg_replace('/[^\w\.\(\)\_\,\'\"\;]/', '', $js_function_call); if(!str_ends_with($js_function_call, ';')) { $js_function_call .= ';'; } $js_function_call .= ' return false;'; }


            $tn_czy_aktywny = (!$is_js_link && $tn_url_cfg !== '#' && $aktywny_id === $tn_id_elementu);
            $tn_aktywna_klasa = $tn_czy_aktywny ? 'active' : '';


            $tn_finalny_url = '#'; if (!$is_js_link && $tn_url_cfg !== '#') { $tn_finalny_url = tn_generuj_url($tn_id_elementu); }


            $tn_badge_html = '';
            if ($tn_id_elementu === 'orders' && ($tn_licznik_nowych_zamowien ?? 0) > 0) { $tn_badge_html = '<span class="badge bg-danger rounded-pill ms-auto">' . $tn_licznik_nowych_zamowien . '</span>'; }
            elseif ($tn_id_elementu === 'products' && ($tn_licznik_niskiego_stanu ?? 0) > 0) { $tn_badge_html = '<span class="badge bg-warning text-dark rounded-pill ms-auto">' . $tn_licznik_niskiego_stanu . '</span>'; }
            elseif ($tn_id_elementu === 'returns_list' && ($tn_licznik_nowych_zwrotow ?? 0) > 0) { $tn_badge_html = '<span class="badge bg-info rounded-pill ms-auto">' . $tn_licznik_nowych_zwrotow . '</span>'; }

            echo '<li class="nav-item">';

            $link_attrs = 'class="nav-link d-flex align-items-center ' . $tn_aktywna_klasa . ' tn-offcanvas-link" ';
            $onclick_attr = $is_js_link ? 'onclick="' . htmlspecialchars($js_function_call, ENT_QUOTES) . '"' : '';

            echo '<a href="' . $tn_finalny_url . '" ' . $link_attrs . $onclick_attr . '>';
            echo '<i class="bi ' . $tn_ikona . ' me-2 opacity-75"></i>';
            echo '<span class="flex-grow-1">' . $tn_tytul . '</span>';
            echo $tn_badge_html;
            echo '</a>';
            echo '</li>';

            // Jeśli element ma submenu, zrenderuj jego elementy bezpośrednio pod nim (spłaszczone)
            if (!empty($tn_link['submenu']) && is_array($tn_link['submenu'])) {
                 tn_renderuj_plaskie_menu_mobilne($tn_link['submenu'], $aktywny_id); // Rekurencyjne wywołanie
            }


        endforeach;
    }
}

?>
<nav class="navbar navbar-expand-lg bg-body-tertiary tn-gorny-pasek sticky-top shadow-sm border-bottom py-2">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold fs-5" href="<?php echo tn_generuj_url('dashboard'); ?>" title="Przejdź do pulpitu">
             <?php echo htmlspecialchars($tn_ustawienia_globalne['nazwa_strony'] ?? 'TN WareXPERT'); ?> <?php echo htmlspecialchars($tn_tytul_na_pasku === ($tn_ustawienia_globalne['nazwa_strony'] ?? '') ? '' : ' - ' . $tn_tytul_na_pasku); ?>
        </a>

        <div class="tn-typing-animation me-auto me-lg-3 ms-3">
             <span class="tn-typing-text">TNiMAG</span><span class="tn-typing-cursor"></span>
        </div>


        <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#tnMobileOffcanvas" aria-controls="tnMobileOffcanvas" aria-label="Rozwiń nawigację">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse d-none d-lg-flex" id="tnTopNavbarDesktop">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">

                <?php if ($tn_biezaca_strona_id === 'products'): ?>
                    <li class="nav-item me-lg-3">
                        <form class="d-flex" role="search" method="GET" action="<?php echo tn_generuj_url('products'); ?>">
                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($tn_sortowanie); ?>">
                            <?php if (!empty($tn_filtr_kategorii_topbar)): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($tn_filtr_kategorii_topbar); ?>"><?php endif; ?>
                            <div class="input-group input-group-sm">
                                <input class="form-control" type="search" name="search" placeholder="Szukaj w produktach..." aria-label="Szukaj" value="<?php echo htmlspecialchars($tn_zapytanie_szukania); ?>">
                                <button class="btn btn-outline-secondary" type="submit" title="Szukaj"><i class="bi bi-search"></i></button>
                                <?php if ($tn_zapytanie_szukania !== ''):
                                    $tn_clear_params = array_filter(['sort' => $tn_sortowanie, 'category' => $tn_filtr_kategorii_topbar]);
                                    $tn_clear_url = tn_generuj_url('products', $tn_clear_params);
                                ?>
                                    <a href="<?php echo $tn_clear_url; ?>" class="btn btn-outline-danger" title="Wyczyść wyszukiwanie"><i class="bi bi-x-lg"></i></a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </li>
                <?php endif; ?>

                <li class="nav-item dropdown me-lg-2">
                    <a class="nav-link d-flex align-items-center" href="#" id="tnQuickActionsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Szybkie akcje">
                         <i class="bi bi-plus-circle fs-5 me-1"></i>
                         Szybkie akcje
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="tnQuickActionsDropdown">
                        <li><h6 class="dropdown-header">Dodaj</h6></li>
                        <li><a class="dropdown-item" href="#" onclick="if(typeof tnApp?.openAddModal === 'function') tnApp.openAddModal(); else alert('Błąd JS');"><i class="bi bi-box-seam me-2 opacity-75"></i>Produkt</a></li>
                        <li><a class="dropdown-item" href="#" onclick="if(typeof tnApp?.setupOrderModal === 'function') tnApp.setupOrderModal(); else alert('Błąd JS');"><i class="bi bi-receipt me-2 opacity-75"></i>Zamówienie</a></li>
                        <li><a class="dropdown-item" href="<?php echo tn_generuj_url('return_form_new'); ?>"><i class="bi bi-arrow-return-left me-2 opacity-75"></i>Zwrot / Reklamacja</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Widoki</h6></li>
                        <li><a class="dropdown-item" href="<?php echo tn_generuj_url('warehouse_view'); ?>"><i class="bi bi-grid-3x3-gap me-2 opacity-75"></i>Magazyn</a></li>
                        <li><a class="dropdown-item" href="<?php echo tn_generuj_url('couriers_list'); ?>"><i class="bi bi-truck me-2 opacity-75"></i>Kurierzy</a></li>
                        <li><a class="dropdown-item" href="<?php echo tn_generuj_url('orders'); ?>"><i class="bi bi-receipt-cutoff me-2 opacity-75"></i>Zamówienia (lista)</a></li>
                        <li><a class="dropdown-item" href="<?php echo tn_generuj_url('returns_list'); ?>"><i class="bi bi-arrow-return-left me-2 opacity-75"></i>Zwroty/Rekl. (lista)</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown me-lg-2">
                    <a class="nav-link" href="#" id="tnThemeDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Zmień motyw">
                        <i class="bi <?php echo $tn_ikona_motywu; ?> fs-5 tn-theme-icon"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="tnThemeDropdown">
                        <li><h6 class="dropdown-header">Wybierz motyw</h6></li>
                        <li><button class="dropdown-item d-flex align-items-center" type="button" data-bs-theme-value="light"><i class="bi bi-sun-fill me-2 opacity-75"></i>Jasny</button></li>
                        <li><button class="dropdown-item d-flex align-items-center" type="button" data-bs-theme-value="dark"><i class="bi bi-moon-stars-fill me-2 opacity-75"></i>Ciemny</button></li>
                    </ul>
                </li>

                <li class="nav-item dropdown me-lg-2">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="tnUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Opcje użytkownika: <?php echo htmlspecialchars($tn_zalogowany_uzytkownik_nazwa); ?>">
                         <span class="d-none d-lg-inline me-1"><?php echo htmlspecialchars($tn_zalogowany_uzytkownik_nazwa); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="tnUserDropdown">
                        <li><h6 class="dropdown-header">Konto</h6></li>
                        <li><a class="dropdown-item" href="<?php echo tn_generuj_url('profile'); ?>"><i class="bi bi-person-gear me-2 opacity-75"></i>Mój Profil</a></li>
                        <li><a class="dropdown-item" href="<?php echo tn_generuj_url('settings'); ?>"><i class="bi bi-sliders me-2 opacity-75"></i>Ustawienia</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">System</h6></li>
                        <li><a class="dropdown-item" href="<?php echo tn_generuj_url('info'); ?>"><i class="bi bi-info-circle me-2 opacity-75"></i>Informacje Systemowe</a></li>
                        <li><a class="dropdown-item" href="<?php echo tn_generuj_url('help'); ?>"><i class="bi bi-question-circle me-2 opacity-75"></i>Pomoc</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo tn_generuj_link_akcji_get('logout'); ?>" onclick="return confirm('Czy na pewno chcesz się wylogować?');"><i class="bi bi-box-arrow-right me-2"></i>Wyloguj</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="offcanvas offcanvas-end d-lg-none" tabindex="-1" id="tnMobileOffcanvas" aria-labelledby="tnMobileOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="tnMobileOffcanvasLabel">Witaj, <?php echo htmlspecialchars($tn_zalogowany_uzytkownik_nazwa); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Zamknij"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <?php // Formularz wyszukiwania (Zawsze widoczny w Offcanvasie) ?>
         <form class="d-flex mb-3 px-2" role="search" method="GET" action="<?php echo tn_generuj_url('products'); ?>">
             <input type="hidden" name="sort" value="<?php echo htmlspecialchars($tn_sortowanie); ?>">
             <?php if (!empty($tn_filtr_kategorii_topbar)): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($tn_filtr_kategorii_topbar); ?>"><?php endif; ?>
             <div class="input-group input-group-sm">
                 <input class="form-control" type="search" name="search" placeholder="Szukaj w produktach..." aria-label="Szukaj" value="<?php echo htmlspecialchars($tn_zapytanie_szukania); ?>">
                 <button class="btn btn-outline-secondary" type="submit" title="Szukaj"><i class="bi bi-search"></i></button>
                 <?php if ($tn_zapytanie_szukania !== ''):
                     $tn_clear_params = array_filter(['sort' => $tn_sortowanie, 'category' => $tn_filtr_kategorii_topbar]);
                     $tn_clear_url = tn_generuj_url('products', $tn_clear_params);
                 ?>
                     <a href="<?php echo $tn_clear_url; ?>" class="btn btn-outline-danger" title="Wyczyść wyszukiwanie"><i class="bi bi-x-lg"></i></a>
                 <?php endif; ?>
             </div>
         </form>


        <ul class="nav nav-pills flex-column flex-grow-1 overflow-auto">

            <?php // --- Sekcja: Nawigacja Główna --- ?>
            <?php
            // Renderuj główne linki nawigacyjne z sidebara.
            // Funkcja tn_renderuj_plaskie_menu_mobilne została zmodyfikowana,
            // aby renderować również nagłówki grup z menu głównego i spłaszczać submenu.
            if (!empty($tn_linki_menu) && function_exists('tn_renderuj_plaskie_menu_mobilne')) {
                echo '<li class="nav-item mt-2 mb-1 px-2"><h6 class="dropdown-header">Nawigacja Główna</h6></li>';
                tn_renderuj_plaskie_menu_mobilne($tn_linki_menu, $tn_biezaca_strona_id);
            } elseif (empty($tn_linki_menu)) {
                 echo '<li class="nav-item px-2"><span class="nav-link text-muted">Brak elementów menu głównego</span></li>';
            } else {
                 echo '<li class="nav-item px-2"><span class="nav-link text-danger">Błąd renderowania menu głównego</span></li>';
            }
            ?>

            <?php // --- Sekcja: Narzędzia i Widoki --- ?>
            <?php
            // Połącz linki z Narzędzi i Widoków
            $tn_narzedzia_widoki_links = array_merge(
                [
                    ['id' => 'add_product', 'tytul' => 'Dodaj Produkt', 'ikona' => 'bi-box-seam', 'url' => 'js:tnApp.openAddModal();'],
                    ['id' => 'add_order', 'tytul' => 'Dodaj Zamówienie', 'ikona' => 'bi-receipt', 'url' => 'js:tnApp.setupOrderModal();'],
                    ['id' => 'add_return', 'tytul' => 'Dodaj Zwrot / Reklamację', 'ikona' => 'bi-arrow-return-left', 'url' => tn_generuj_url('return_form_new')],
                ],
                [
                    ['id' => 'warehouse_view', 'tytul' => 'Magazyn', 'ikona' => 'bi-grid-3x3-gap', 'url' => tn_generuj_url('warehouse_view')],
                    ['id' => 'couriers_list', 'tytul' => 'Kurierzy', 'ikona' => 'bi-truck', 'url' => tn_generuj_url('couriers_list')],
                    ['id' => 'orders', 'tytul' => 'Zamówienia (lista)', 'ikona' => 'bi-receipt-cutoff', 'url' => tn_generuj_url('orders')],
                    ['id' => 'returns_list', 'tytul' => 'Zwroty/Rekl. (lista)', 'ikona' => 'bi-arrow-return-left', 'url' => tn_generuj_url('returns_list')],
                ]
            );

            // Odfiltruj linki, których ID już znajduje się w głównym menu nawigacji
            $tn_narzedzia_widoki_do_wyswietlenia = array_filter($tn_narzedzia_widoki_links, function($link) use ($tn_all_main_menu_ids) {
                return !in_array($link['id'], $tn_all_main_menu_ids);
            });

            if (!empty($tn_narzedzia_widoki_do_wyswietlenia)):
            ?>
                <li class="nav-item mt-2 mb-1"><hr class="dropdown-divider"></li>
                <li class="nav-item mt-2 mb-1 px-2"><h6 class="dropdown-header">Narzędzia i Widoki</h6></li>
                <?php foreach($tn_narzedzia_widoki_do_wyswietlenia as $link): ?>
                    <li class="nav-item">
                        <a class="nav-link tn-offcanvas-link" href="<?php echo htmlspecialchars($link['url']); ?>" <?php if(str_starts_with($link['url'], 'js:')) echo 'onclick="' . htmlspecialchars(trim(substr($link['url'], 3)) . '; return false;', ENT_QUOTES) . '"'; ?> data-bs-dismiss="offcanvas">
                            <i class="bi <?php echo htmlspecialchars($link['ikona']); ?> me-2 opacity-75"></i>
                            <?php echo htmlspecialchars($link['tytul']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>


            <?php // --- Sekcja: Wygląd (Motyw) --- ?>
            <?php // Sekcja Motyw nie zawiera linków, które zazwyczaj są w nawigacji głównej, więc nie wymaga filtrowania ?>
            <li class="nav-item mt-2 mb-1"><hr class="dropdown-divider"></li>
            <li class="nav-item mt-2 mb-1 px-2"><h6 class="dropdown-header">Wygląd</h6></li>
            <li class="nav-item"><button class="nav-link text-start w-100 tn-offcanvas-link" type="button" data-bs-theme-value="light" data-bs-dismiss="offcanvas"><i class="bi bi-sun-fill me-2 opacity-75"></i>Motyw Jasny</button></li>
            <li class="nav-item"><button class="nav-link text-start w-100 tn-offcanvas-link" type="button" data-bs-theme-value="dark" data-bs-dismiss="offcanvas"><i class="bi bi-moon-stars-fill me-2 opacity-75"></i>Motyw Ciemny</button></li>


            <?php // --- Sekcja: Konto & System (Użytkownik) --- ?>
             <?php
            // Sprawdź, czy którekolwiek z tych linków NIE znajduje się w głównym menu nawigacji
            $tn_konto_system_links = [
                ['id' => 'profile', 'tytul' => 'Mój Profil', 'ikona' => 'bi-person-gear', 'url' => tn_generuj_url('profile')],
                ['id' => 'settings', 'tytul' => 'Ustawienia', 'ikona' => 'bi-sliders', 'url' => tn_generuj_url('settings')],
                ['id' => 'info', 'tytul' => 'Informacje Systemowe', 'ikona' => 'bi-info-circle', 'url' => tn_generuj_url('info')],
                ['id' => 'help', 'tytul' => 'Pomoc', 'ikona' => 'bi-question-circle', 'url' => tn_generuj_url('help')],
            ];
             $tn_konto_system_do_wyswietlenia = array_filter($tn_konto_system_links, function($link) use ($tn_all_main_menu_ids) {
                return !in_array($link['id'], $tn_all_main_menu_ids);
            });

            if (!empty($tn_konto_system_do_wyswietlenia)):
            ?>
                <li class="nav-item mt-2 mb-1"><hr class="dropdown-divider"></li>
                <li class="nav-item mt-2 mb-1 px-2"><h6 class="dropdown-header">Konto & System</h6></li>
                 <?php foreach($tn_konto_system_do_wyswietlenia as $link): ?>
                    <li class="nav-item">
                        <a class="nav-link tn-offcanvas-link" href="<?php echo htmlspecialchars($link['url']); ?>" <?php if(str_starts_with($link['url'], 'js:')) echo 'onclick="' . htmlspecialchars(trim(substr($link['url'], 3)) . '; return false;', ENT_QUOTES) . '"'; ?> data-bs-dismiss="offcanvas">
                            <i class="bi <?php echo htmlspecialchars($link['ikona']); ?> me-2 opacity-75"></i>
                            <?php echo htmlspecialchars($link['tytul']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php // --- Sekcja: Wyloguj --- ?>
            <li class="nav-item mt-2 mb-1"><hr class="dropdown-divider"></li>
            <li class="nav-item"><a class="nav-link text-danger tn-offcanvas-link" href="<?php echo tn_generuj_link_akcji_get('logout'); ?>" onclick="return confirm('Czy na pewno chcesz się wylogować?');" data-bs-dismiss="offcanvas"><i class="bi bi-box-arrow-right me-2"></i>Wyloguj</a></li>

        </ul>
    </div>
</div>

<style>
#tnMobileOffcanvas {
    width: 100vw;
}

#tnMobileOffcanvas .offcanvas-header {
    border-bottom: 1px solid var(--bs-border-color);
}

#tnMobileOffcanvas .offcanvas-body {
    padding: 0;
}

#tnMobileOffcanvas .nav-link {
    padding: 0.75rem 1.5rem !important;
    color: var(--bs-body-color);
}

#tnMobileOffcanvas .nav-link.active {
    background-color: var(--bs-primary);
    color: var(--bs-white) !important;
    font-weight: 600;
}

#tnMobileOffcanvas .dropdown-header {
    padding: 0.75rem 1.5rem 0.25rem 1.5rem !important;
    font-size: 0.85em;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
}

#tnMobileOffcanvas .dropdown-divider {
    margin: 0.5rem 1.5rem !important;
    border-color: var(--bs-border-color);
}

#tnMobileOffcanvas .nav-link[data-bs-theme-value] {
    background: none;
    border: none;
    color: var(--bs-body-color);
    text-align: left;
    width: 100%;
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem !important;
}
#tnMobileOffcanvas .nav-link[data-bs-theme-value]:hover {
     color: var(--bs-link-hover-color);
     background-color: var(--bs-body-tertiary-bg);
}

#tnMobileOffcanvas .nav-link i {
    font-size: 1.1em;
}

#tnMobileOffcanvas .offcanvas-body > .d-flex.align-items-center.mb-3 {
    padding: 1rem 1.5rem;
    margin-bottom: 1rem !important;
}

.tn-typing-animation {
    display: inline-block;
    font-family: monospace;
    overflow: hidden;
    border-right: .15em solid green;
    white-space: nowrap;
    animation:
        typing 3.0s steps(7, end) forwards,
        blink-caret .75s step-end infinite;
    width: 0;
}

@keyframes typing {
    from { width: 0 }
    to { width: 6.5em }
}

@keyframes blink-caret {
    from, to { border-color: transparent }
    50% { border-color: green; }
}

[data-bs-theme="dark"] .tn-typing-animation {
    border-right-color: #28a745;
}

[data-bs-theme="dark"] @keyframes blink-caret {
    from, to { border-color: transparent }
    50% { border-color: #28a745; }
}

</style>
