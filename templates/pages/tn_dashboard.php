<?php

if (!defined('TN_WAREXPERT_VERSION')) {
    define('TN_WAREXPERT_VERSION', '1.6.0');
}

if (!function_exists('tn_generuj_url')) { function tn_generuj_url(string $id, array $p = []){ return '?page='.urlencode($id).'&'.http_build_query($p); } }
if (!function_exists('tn_pobierz_sciezke_obrazka')) { function tn_pobierz_sciezke_obrazka($f = null){ $basePath = 'uploads/images/'; $placeholder = 'assets/img/placeholder.svg'; return ($f && defined('TN_KORZEN_APLIKACJI') && file_exists(TN_KORZEN_APLIKACJI . '/' . $basePath . $f)) ? $basePath . htmlspecialchars($f) : $placeholder; } }
if (!function_exists('tn_mapuj_status_zam_na_kolor')) { function tn_mapuj_status_zam_na_kolor(string $status): string { switch (strtolower($status)) { case 'nowe': return 'info'; case 'w realizacji': return 'primary'; case 'oczekuje na płatność': return 'secondary'; case 'oczekuje na wysyłkę': return 'warning'; case 'wysłane': return 'purple'; case 'zrealizowane': return 'success'; case 'anulowane': return 'danger'; case 'zwrócone': return 'dark'; default: return 'light'; } } }
if (!function_exists('tn_mapuj_status_zwr_na_kolor')) { function tn_mapuj_status_zwr_na_kolor(string $status): string { switch (strtolower($status)) { case 'nowe zgłoszenie': return 'warning'; case 'w trakcie rozpatrywania': return 'info'; case 'zaakceptowany - oczekuje na zwrot': return 'primary'; case 'produkt otrzymany': return 'secondary'; case 'zwrot przetworzony / zakończony': return 'success'; case 'odrzucony': return 'danger'; case 'anulowany': return 'dark'; default: return 'light'; } } }

$tn_produkty = $tn_produkty ?? [];
$tn_zamowienia = $tn_zamowienia ?? [];
$tn_stan_magazynu = $tn_stan_magazynu ?? [];
$tn_zwroty = $tn_zwroty ?? [];
$tn_ustawienia_globalne = $tn_ustawienia_globalne ?? [];
$tn_dane_uzytkownika = $tn_dane_uzytkownika ?? []; 

$tn_waluta = htmlspecialchars($tn_ustawienia_globalne['waluta'] ?? 'PLN');
$tn_prog_niskiego_stanu = (int)($tn_ustawienia_globalne['tn_prog_niskiego_stanu'] ?? 5);
$tn_domyslny_status_zam = $tn_ustawienia_globalne['tn_domyslny_status_zam'] ?? 'Nowe';
$tn_status_nowego_zwrotu = $tn_ustawienia_globalne['zwroty_reklamacje']['domyslny_status'] ?? 'Nowe zgłoszenie';
$tn_format_daty_czasu_krotki = $tn_ustawienia_globalne['tn_format_daty'] ?? 'd.m.Y H:i'; 
$tn_user_id = htmlspecialchars($tn_dane_uzytkownika['id'] ?? 'Brak ID');
$tn_user_display_name = htmlspecialchars($tn_dane_uzytkownika['tn_imie_nazwisko'] ?? $tn_dane_uzytkownika['username'] ?? 'Użytkownik anonimowy');


$tn_procent_prowizji = 0.19;
$tn_podatek_dochodowy = 0.12;
$tn_koszt_magazynowania_proc = 0.42;

$tn_user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Nieznany';
$tn_user_hostname = @gethostbyaddr($tn_user_ip);
if ($tn_user_hostname === false || $tn_user_hostname === $tn_user_ip) {
    $tn_user_hostname = 'Brak danych';
}

$tn_liczba_produktow = count($tn_produkty);
$tn_liczba_zamowien_wszystkich = count($tn_zamowienia);
$tn_liczba_zwrotow_wszystkich = count($tn_zwroty);

$tn_laczna_ilosc_produktow_w_magazynie = 0;
if (!empty($tn_produkty)) {
    foreach ($tn_produkty as $prod) {
        $stock_value = isset($prod['stock']) && is_numeric($prod['stock']) ? (int)$prod['stock'] : 0;
        $tn_laczna_ilosc_produktow_w_magazynie += $stock_value;
    }
}

$tn_mapa_produktow = [];
$tn_mapa_cen = [];
if (!empty($tn_produkty)) {
    foreach ($tn_produkty as $prod) {
        $id = $prod['id'] ?? null;
        if ($id !== null) {
            $tn_mapa_produktow[(int)$id] = $prod;
            $tn_mapa_cen[(int)$id] = (float)($prod['price'] ?? 0);
        }
    }
}

$tn_wartosc_zamowien_all = 0.0;
$tn_liczba_nowych_zamowien = 0;
$tn_liczba_nowych_zwrotow = 0;
$tn_statusy_zamowien_licznik = [];
$tn_statusy_zwrotow_licznik = [];
$tn_sprzedaz_produktow = [];

if (!empty($tn_zamowienia)) {
    foreach ($tn_zamowienia as $zam) {
        $status = $zam['status'] ?? 'Nieznany';
        $tn_statusy_zamowien_licznik[$status] = ($tn_statusy_zamowien_licznik[$status] ?? 0) + 1;
        if ($status === $tn_domyslny_status_zam) {
            $tn_liczba_nowych_zamowien++;
        }

        $prod_id = isset($zam['product_id']) ? (int)$zam['product_id'] : null;
        $ilosc = isset($zam['quantity']) ? (int)$zam['quantity'] : 0;

        $cena = ($prod_id !== null && isset($tn_mapa_cen[$prod_id])) ? $tn_mapa_cen[$prod_id] : 0.0;

        $tn_wartosc_zamowien_all += $cena * $ilosc;

        if ($prod_id !== null && $ilosc > 0) {
            $tn_sprzedaz_produktow[$prod_id] = ($tn_sprzedaz_produktow[$prod_id] ?? 0) + $ilosc;
        }
    }
}

$tn_wartosc_wszystkich_zwrotow = 0.0;
if (!empty($tn_zwroty)) {
    foreach ($tn_zwroty as $zwr) {
        $status = $zwr['status'] ?? 'Nieznany';
        $tn_statusy_zwrotow_licznik[$status] = ($tn_statusy_zwrotow_licznik[$status] ?? 0) + 1;
        if ($status === $tn_status_nowego_zwrotu) {
            $tn_liczba_nowych_zwrotow++;
        }
    }
}


if (!empty($tn_statusy_zamowien_licznik)) { arsort($tn_statusy_zamowien_licznik); }
if (!empty($tn_statusy_zwrotow_licznik)) { arsort($tn_statusy_zwrotow_licznik); }
if (!empty($tn_sprzedaz_produktow)) { arsort($tn_sprzedaz_produktow); }

$tn_prowizja_sprzedazy = $tn_wartosc_zamowien_all * $tn_procent_prowizji;
$tn_koszty_magazynowania = $tn_wartosc_zamowien_all * $tn_koszt_magazynowania_proc;
$tn_dochod_do_opodatkowania = max(0.0, $tn_wartosc_zamowien_all - $tn_wartosc_wszystkich_zwrotow - $tn_prowizja_sprzedazy - $tn_koszty_magazynowania);
$tn_należny_podatek = $tn_dochod_do_opodatkowania * $tn_podatek_dochodowy;

$tn_wartosc_magazynu = 0.0;
if (!empty($tn_produkty)) {
    foreach ($tn_produkty as $prod) {
        $tn_wartosc_magazynu += (float)($prod['price'] ?? 0) * (int)($prod['stock'] ?? 0);
    }
}

$tn_ilosc_miejsc_wszystkich = count($tn_stan_magazynu);
$tn_ilosc_miejsc_zajetych = 0;
if (!empty($tn_stan_magazynu)) {
    foreach ($tn_stan_magazynu as $miejsce) {
        if (($miejsce['status'] ?? '') === 'occupied') {
            $tn_ilosc_miejsc_zajetych++;
        }
    }
}
$tn_procent_zajetosci = $tn_ilosc_miejsc_wszystkich > 0 ? round(($tn_ilosc_miejsc_zajetych / $tn_ilosc_miejsc_wszystkich) * 100) : 0;

$tn_zajetosc_progress_class = 'bg-info';
if ($tn_procent_zajetosci > 85) {
    $tn_zajetosc_progress_class = 'bg-danger';
} elseif ($tn_procent_zajetosci > 65) {
    $tn_zajetosc_progress_class = 'bg-warning';
}

$tn_produkty_brak_stanu_lista = [];
$tn_liczba_brak_stanu = 0;
if (!empty($tn_produkty)) {
    $brak_stanu_temp = [];
    foreach($tn_produkty as $p) {
        $stock = $p['stock'] ?? null;
        if (is_numeric($stock) && (int)$stock === 0) {
            $brak_stanu_temp[] = $p;
        }
    }
    $tn_liczba_brak_stanu = count($brak_stanu_temp);
    usort($brak_stanu_temp, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));
    $tn_produkty_brak_stanu_lista = array_slice($brak_stanu_temp, 0, 5); 
}

$sortuj_wg_daty_lub_id = function($a, $b, $klucz_daty) {
    $dateA_str = $a[$klucz_daty] ?? null;
    $dateB_str = $b[$klucz_daty] ?? null;
    $tsA = $dateA_str ? strtotime($dateA_str) : null;
    $tsB = $dateB_str ? strtotime($dateB_str) : null;

    if ($tsA !== null && $tsB !== null) {
        return $tsB <=> $tsA;
    } elseif ($tsA !== null) {
        return -1;
    } elseif ($tsB !== null) {
        return 1;
    } else {
        return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
    }
};

$zamowienia_do_sortowania = $tn_zamowienia;
$zwroty_do_sortowania = $tn_zwroty;
$produkty_do_sortowania = $tn_produkty;

usort($zamowienia_do_sortowania, fn($a, $b) => $sortuj_wg_daty_lub_id($a, $b, 'order_date'));
usort($zwroty_do_sortowania, fn($a, $b) => $sortuj_wg_daty_lub_id($a, $b, 'date_created'));
usort($produkty_do_sortowania, fn($a, $b) => ($b['id'] ?? 0) <=> ($a['id'] ?? 0));

$tn_ostatnie_zamowienia_lista = array_slice($zamowienia_do_sortowania, 0, 10);
$tn_ostatnie_zwroty_lista = array_slice($zwroty_do_sortowania, 0, 10);
$tn_ostatnio_dodane_prod_lista = array_slice($produkty_do_sortowania, 0, 10 );
$tn_top_produkty_sprzedaz_lista = array_slice($tn_sprzedaz_produktow, 0, 10, true);

$tn_system_info = [
    'Wersja aplikacji' => TN_WAREXPERT_VERSION,
    'Wersja PHP' => PHP_VERSION,
    'System operacyjny' => PHP_OS,
    'Serwer WWW' => $_SERVER['SERVER_SOFTWARE'] ?? 'Nieznane',
    'Czas serwera' => date('Y-m-d H:i:s T') 
];
$tn_czas_logowania = $tn_system_info['Czas serwera'];

$tn_notes_file = defined('TN_SCIEZKA_DANE') ? TN_SCIEZKA_DANE . 'dashboard_notes.json' : __DIR__ . '/../../TNbazaDanych/dashboard_notes.json';
$tn_initial_notes = '';
if (file_exists($tn_notes_file)) {
    $notes_json = @file_get_contents($tn_notes_file);
    if ($notes_json !== false) {
        $notes_data = json_decode($notes_json, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($notes_data['notes'])) {
            $tn_initial_notes = htmlspecialchars($notes_data['notes'], ENT_QUOTES, 'UTF-8');
        } elseif(json_last_error() !== JSON_ERROR_NONE) {
            error_log("Błąd dekodowania JSON z pliku notatek: " . $tn_notes_file . " - " . json_last_error_msg());
        }
    } else {
        error_log("Błąd odczytu pliku notatek: " . $tn_notes_file);
    }
}
?>

<div class="container-fluid px-lg-4 py-4">

    <div class="row g-3 mb-4">

        <div class="col-6 col-md-4 col-lg-3">
            <div class="card shadow-sm text-center h-100 border-primary border-start border-4 border-top-0 border-end-0 border-bottom-0">
                <div class="card-body py-3 px-2 d-flex flex-column justify-content-center"><i class="bi bi-boxes fs-2 text-primary mb-2 d-block"></i><h4 class="mb-1 lh-1"><?php echo $tn_liczba_produktow; ?></h4><p class="card-text text-muted small mb-0">Wszystkie produkty</p></div>
            </div>
        </div>
        
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card shadow-sm text-center h-100 border-indigo border-start border-4 border-top-0 border-end-0 border-bottom-0">
                <div class="card-body py-3 px-2 d-flex flex-column justify-content-center">
                    <i class="bi bi-archive-fill fs-2 text-indigo mb-2 d-block"></i>
                    <h4 class="mb-1 lh-1"><?php echo $tn_laczna_ilosc_produktow_w_magazynie; ?></h4>
                    <p class="card-text text-muted small mb-0">Łącznie sztuk w magazynie</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card shadow-sm text-center h-100 border-danger border-start border-4 border-top-0 border-end-0 border-bottom-0">
                <div class="card-body py-3 px-2 position-relative d-flex flex-column justify-content-center">
                    <?php if ($tn_liczba_brak_stanu > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark kpi-badge"><?php echo $tn_liczba_brak_stanu; ?><span class="visually-hidden">brak stanu</span></span>
                    <?php endif; ?>
                    <i class="bi bi-box-seam-fill fs-2 text-danger mb-2 d-block"></i>
                    <h4 class="mb-1 lh-1"><?php echo $tn_liczba_brak_stanu; ?></h4>
                    <p class="card-text text-muted small mb-0">Brak stanu</p>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <div class="card shadow-sm text-center h-100 border-success border-start border-4 border-top-0 border-end-0 border-bottom-0">
                <div class="card-body py-3 px-2 d-flex flex-column justify-content-center"><i class="bi bi-receipt fs-2 text-success mb-2 d-block"></i><h4 class="mb-1 lh-1"><?php echo $tn_liczba_zamowien_wszystkich; ?></h4><p class="card-text text-muted small mb-0">Wszystkie zamówienia</p></div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <div class="card shadow-sm text-center h-100 border-info border-start border-4 border-top-0 border-end-0 border-bottom-0">
                <div class="card-body py-3 px-2 position-relative d-flex flex-column justify-content-center">
                    <?php if ($tn_liczba_nowych_zamowien > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger kpi-badge"><?php echo $tn_liczba_nowych_zamowien; ?><span class="visually-hidden">nowe</span></span>
                    <?php endif; ?>
                    <i class="bi bi-cart-plus-fill fs-2 text-info mb-2 d-block"></i>
                    <h4 class="mb-1 lh-1"><?php echo $tn_liczba_nowych_zamowien; ?></h4>
                    <p class="card-text text-muted small mb-0">Nowe zamówienia</p>
                </div>
            </div>
        </div>

         <div class="col-6 col-md-4 col-lg-3">
             <div class="card shadow-sm text-center h-100 border-warning border-start border-4 border-top-0 border-end-0 border-bottom-0">
                 <div class="card-body py-3 px-2 d-flex flex-column justify-content-center">
                     <i class="bi bi-arrow-repeat fs-2 text-warning mb-2 d-block"></i>
                     <h4 class="mb-1 lh-1"><?php echo $tn_liczba_zwrotow_wszystkich; ?></h4>
                     <p class="card-text text-muted small mb-0">Wszystkie zwroty</p>
                 </div>
             </div>
         </div>

         <div class="col-6 col-md-4 col-lg-3">
             <div class="card shadow-sm text-center h-100 border-secondary border-start border-4 border-top-0 border-end-0 border-bottom-0">
                 <div class="card-body py-3 px-2 position-relative d-flex flex-column justify-content-center">
                     <?php if ($tn_liczba_nowych_zwrotow > 0): ?>
                         <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger kpi-badge"><?php echo $tn_liczba_nowych_zwrotow; ?><span class="visually-hidden">nowe</span></span>
                     <?php endif; ?>
                     <i class="bi bi-arrow-return-left fs-2 text-secondary mb-2 d-block"></i>
                     <h4 class="mb-1 lh-1"><?php echo $tn_liczba_nowych_zwrotow; ?></h4>
                     <p class="card-text text-muted small mb-0">Nowe zwroty</p>
                 </div>
             </div>
         </div>

         <div class="col-6 col-md-4 col-lg-3">
             <div class="card shadow-sm text-center h-100 border-purple border-start border-4 border-top-0 border-end-0 border-bottom-0" style="--bs-border-opacity: .7;">
                 <div class="card-body py-3 px-2 d-flex flex-column justify-content-center">
                     <i class="bi bi-hdd-stack-fill fs-2 text-purple mb-2 d-block"></i>
                     <h4 class="mb-1 lh-1"><?php echo $tn_procent_zajetosci; ?>%</h4>
                     <div class="progress mt-1" role="progressbar" aria-label="Zajętość lokalizacji" aria-valuenow="<?php echo $tn_procent_zajetosci; ?>" aria-valuemin="0" aria-valuemax="100" style="height: 6px;">
                         <div class="progress-bar <?php echo $tn_zajetosc_progress_class; ?>" style="width: <?php echo $tn_procent_zajetosci; ?>%"></div>
                     </div>
                     <p class="card-text text-muted small mb-0 mt-1">Zajętość magazynu</p>
                 </div>
             </div>
         </div>

         <div class="col-6 col-md-4 col-lg-3">
             <div class="card shadow-sm text-center h-100 border-dark border-start border-4 border-top-0 border-end-0 border-bottom-0">
                 <div class="card-body py-3 px-2 d-flex flex-column justify-content-center"><i class="bi bi-cash-stack fs-2 text-dark mb-2 d-block"></i><h4 class="mb-1 lh-1"><?php echo number_format($tn_wartosc_magazynu, 0, ',', ' '); ?> <?php echo $tn_waluta; ?></h4><p class="card-text text-muted small mb-0">Wartość magazynu</p></div>
             </div>
         </div>
    </div>

    <div class="row g-4">

        <div class="col-lg-6 d-flex flex-column">
            <div class="card shadow-sm mb-4 flex-grow-1">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Ostatnie Zamówienia (<?php echo count($tn_ostatnie_zamowienia_lista); ?>)</h6><a href="<?php echo tn_generuj_url('orders'); ?>" class="btn btn-sm btn-link py-0 fw-medium">Wszystkie</a></div>
                <div class="card-body p-0 tn-card-body-scrollable-sm">
                    <?php if(empty($tn_ostatnie_zamowienia_lista)): ?>
                        <p class="text-center text-muted small p-3 m-0 fst-italic">Brak zamówień.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush small">
                            <?php foreach($tn_ostatnie_zamowienia_lista as $zam): ?>
                            <li class="list-group-item list-group-item-action px-3 py-2 d-flex justify-content-between align-items-center gap-2">
                                <div>
                                    <a href="<?php echo tn_generuj_url('order_preview', ['id' => $zam['id'] ?? 0]); ?>" class="text-decoration-none fw-medium stretched-link">Numer zamówienia: #<?php echo htmlspecialchars($zam['id'] ?? '?'); ?></a> <small><?php echo htmlspecialchars($zam['status'] ?? '-'); ?> | <?php echo htmlspecialchars($zam['tn_status_platnosci'] ?? '-'); ?></small>
                                    <small class="text-muted d-block text-truncate" style="max-width: 250px;"><?php echo htmlspecialchars($zam['buyer_name'] ?? '-'); ?> | ID: <?php echo htmlspecialchars($zam['id'] ?? '?'); ?></small>
                                </div>
                                <small class="text-muted text-nowrap">
                                    <?php
                                    try {
                                        echo !empty($zam['order_date']) ? (new DateTime($zam['order_date']))->format($tn_format_daty_czasu_krotki) : '-';
                                    } catch (Exception $e){
                                        error_log("Błąd formatowania daty zamówienia (ID: {$zam['id']}): " . $e->getMessage());
                                        echo '-';
                                    }
                                    ?>
                                </small>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mb-4 flex-grow-1">
                <div class="card-header bg-primary-subtle text-primary-emphasis d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-archive me-2"></i>Podsumowanie Magazynowe</h6>
                </div>
                <div class="card-body py-2 px-3">
                    <dl class="row mb-0 small w-100">
                        <dt class="col-7 fw-normal text-muted pt-2 d-flex align-items-center"><i class="bi bi-boxes me-2 text-primary"></i>Wszystkie produkty (rodzaje):</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2"><small class="text-muted"><?php echo $tn_liczba_produktow; ?></small></dd>

                        <dt class="col-7 fw-normal text-muted pt-2 d-flex align-items-center"><i class="bi bi-archive-fill me-2 text-indigo"></i>Łącznie sztuk w magazynie:</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2"><small class="text-muted"><?php echo $tn_laczna_ilosc_produktow_w_magazynie; ?> szt.</small></dd>
                        
                        <dt class="col-7 fw-normal text-danger pt-2 d-flex align-items-center"><i class="bi bi-box-seam-fill me-2"></i>Produkty z zerowym stanem:</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2 text-danger"><small><?php echo $tn_liczba_brak_stanu; ?></small></dd>
                        
                        <dt class="col-7 fw-normal text-muted pt-2 d-flex align-items-center"><i class="bi bi-hdd-stack-fill me-2 text-purple"></i>Zajętość magazynu:</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2"><small class="text-muted"><?php echo $tn_procent_zajetosci; ?>%</small></dd>

                        <dt class="col-7 fw-normal text-danger pt-2 d-flex align-items-center"><i class="bi bi-cash-stack me-2"></i>Wartość magazynu:</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2 fw-bold text-danger"><small><?php echo number_format($tn_wartosc_magazynu, 2, ',', ' '); ?> <?php echo $tn_waluta; ?></small></dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm mb-4 flex-grow-1">
                <div class="card-header bg-success-subtle text-success-emphasis d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-cart-check me-2"></i>Podsumowanie Zamówień</h6>
                </div>
                <div class="card-body py-2 px-3">
                    <dl class="row mb-0 small w-100">
                        <dt class="col-7 fw-normal text-muted pt-2 d-flex align-items-center"><i class="bi bi-receipt me-2 text-success"></i>Wszystkie zamówienia (liczba):</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2"><small class="text-muted"><?php echo $tn_liczba_zamowien_wszystkich; ?></small></dd>

                        <dt class="col-7 fw-normal text-muted pt-2 d-flex align-items-center"><i class="bi bi-cart-plus-fill me-2 text-info"></i>Nowe zamówienia (liczba):</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2"><small class="text-muted"><?php echo $tn_liczba_nowych_zamowien; ?></small></dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm mb-4 flex-grow-1">
                <div class="card-header bg-warning-subtle text-warning-emphasis d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-box-arrow-left me-2"></i>Podsumowanie Zwrotów</h6>
                </div>
                <div class="card-body py-2 px-3">
                    <dl class="row mb-0 small w-100">
                        <dt class="col-7 fw-normal text-muted pt-2 d-flex align-items-center"><i class="bi bi-arrow-repeat me-2 text-warning"></i>Wszystkie zwroty (liczba):</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2"><small class="text-muted"><?php echo $tn_liczba_zwrotow_wszystkich; ?></small></dd>

                        <dt class="col-7 fw-normal text-muted pt-2 d-flex align-items-center"><i class="bi bi-arrow-return-left me-2 text-secondary"></i>Nowe zwroty (liczba):</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2"><small class="text-muted"><?php echo $tn_liczba_nowych_zwrotow; ?></small></dd>
                        
                        <dt class="col-7 fw-normal text-muted pt-2 d-flex align-items-center"><i class="bi bi-cash-coin me-2 text-warning"></i>Wartość zwrotów:</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2"><small class="text-muted"><?php echo number_format($tn_wartosc_wszystkich_zwrotow, 2, ',', ' '); ?> <?php echo $tn_waluta; ?> <span class="fst-italic">(wymaga danych)</span></small></dd>
                    </dl>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-sticky-fill me-2"></i>Notatnik </h6>
                    <small id="notes-status" class="text-muted fst-italic"></small>
                </div>
                <div class="card-body p-2">
                    <textarea id="dashboard-notes" class="form-control form-control-sm" rows="5" placeholder="Wklej lub wpisz treść do zapamiętania.." aria-label="Szybkie notatki"><?php echo $tn_initial_notes; ?></textarea>
                </div>
            </div>

        </div>

        <div class="col-lg-6 d-flex flex-column">
            <div class="card shadow-sm mb-4 flex-grow-1">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-box-arrow-in-down me-2"></i>Ostatnio Dodane Produkty (<?php echo count($tn_ostatnio_dodane_prod_lista); ?>)</h6><a href="<?php echo tn_generuj_url('products', ['sort' => 'id_desc']); ?>" class="btn btn-sm btn-link py-0 fw-medium">Wszystkie</a></div>
                <div class="card-body p-0 tn-card-body-scrollable-sm">
                    <?php if(empty($tn_ostatnio_dodane_prod_lista)): ?><p class="text-center text-muted small p-3 m-0 fst-italic">Brak produktów.</p><?php else: ?>
                        <ul class="list-group list-group-flush small"><?php foreach($tn_ostatnio_dodane_prod_lista as $prod): ?>
                            <li class="list-group-item list-group-item-action px-3 py-2 d-flex align-items-center gap-2"><img src="<?php echo tn_pobierz_sciezke_obrazka($prod['image'] ?? null); ?>" alt="Miniatura" class="rounded border tn-list-img"><div class="flex-grow-1 text-truncate"><a href="<?php echo tn_generuj_url('product_preview', ['id' => $prod['id'] ?? 0]); ?>" class="text-decoration-none fw-medium stretched-link" title="<?php echo htmlspecialchars($prod['name'] ?? ''); ?>"><?php echo htmlspecialchars($prod['name'] ?? 'B/N'); ?></a><small class="text-muted d-block">ID: <?php echo htmlspecialchars($prod['id'] ?? '?'); ?> | Producent: <?php echo htmlspecialchars($prod['producent'] ?? '?'); ?></small></div><span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill ms-auto fs-inherit"><?php echo htmlspecialchars($prod['stock'] ?? '0'); ?> szt.</span></li>
                        <?php endforeach; ?></ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card shadow-sm mb-4 flex-grow-1">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-trophy-fill me-2 text-warning"></i>Top Sprzedające się (<?php echo count($tn_top_produkty_sprzedaz_lista); ?>)</h6></div>
                <div class="card-body p-0 tn-card-body-scrollable-sm">
                    <?php if (empty($tn_top_produkty_sprzedaz_lista)): ?>
                        <p class="text-center text-muted small p-3 m-0 fst-italic">Brak danych o sprzedaży.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush small">
                            <?php foreach ($tn_top_produkty_sprzedaz_lista as $prod_id => $ilosc_sprzedana):
                                $produkt = $tn_mapa_produktow[$prod_id] ?? null;
                                if (!$produkt) continue;
                            ?>
                            <li class="list-group-item list-group-item-action px-3 py-2 d-flex align-items-center gap-2">
                                <img src="<?php echo tn_pobierz_sciezke_obrazka($produkt['image'] ?? null); ?>" alt="Miniatura" class="rounded border tn-list-img">
                                <div class="flex-grow-1 text-truncate">
                                    <a href="<?php echo tn_generuj_url('product_preview', ['id' => $produkt['id'] ?? 0]); ?>" class="text-decoration-none fw-medium stretched-link" title="<?php echo htmlspecialchars($produkt['name'] ?? ''); ?>"><?php echo htmlspecialchars($produkt['name'] ?? 'B/N'); ?></a>
                                    <small class="text-muted d-block">ID: <?php echo htmlspecialchars($produkt['id'] ?? '?'); ?> | Producent: <?php echo htmlspecialchars($produkt['producent'] ?? '?'); ?> | Nr katalogowy: <?php echo htmlspecialchars($produkt['tn_numer_katalogowy'] ?? '?'); ?> </small>
                                </div>
                                <span class="badge bg-success-subtle text-success-emphasis rounded-pill ms-auto fs-inherit fw-bold"><?php echo $ilosc_sprzedana; ?> sprzed.</span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card shadow-sm mb-4 flex-grow-1">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-arrow-repeat me-2 text-warning"></i>Ostatnie Zwroty (<?php echo count($tn_ostatnie_zwroty_lista); ?>)</h6><a href="<?php echo tn_generuj_url('returns'); ?>" class="btn btn-sm btn-link py-0 fw-medium">Wszystkie</a></div>
                <div class="card-body p-0 tn-card-body-scrollable-sm">
                    <?php if(empty($tn_ostatnie_zwroty_lista)): ?>
                        <p class="text-center text-muted small p-3 m-0 fst-italic">Brak zwrotów.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush small">
                            <?php foreach($tn_ostatnie_zwroty_lista as $zwr): ?>
                            <li class="list-group-item list-group-item-action px-3 py-2 d-flex justify-content-between align-items-center gap-2">
                                <div>
                                    <a href="<?php echo tn_generuj_url('return_preview', ['id' => $zwr['id'] ?? 0]); ?>" class="text-decoration-none fw-medium stretched-link">Zgłoszenie #<?php echo htmlspecialchars($zwr['id'] ?? '?'); ?></a>
                                    <small class="text-muted d-block text-truncate" style="max-width: 250px;">Zamówienie #<?php echo htmlspecialchars($zwr['order_id'] ?? '?'); ?> | Status: <?php echo htmlspecialchars($zwr['status'] ?? '-'); ?></small>
                                </div>
                                <small class="text-muted text-nowrap">
                                    <?php
                                    try {
                                        echo !empty($zwr['date_created']) ? (new DateTime($zwr['date_created']))->format($tn_format_daty_czasu_krotki) : '-';
                                    } catch (Exception $e){
                                        error_log("Błąd formatowania daty zwrotu (ID: {$zwr['id']}): " . $e->getMessage());
                                        echo '-';
                                    }
                                    ?>
                                </small>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

             <div class="card shadow-sm mb-4 flex-grow-1">
                <div class="card-header bg-danger-subtle text-danger-emphasis d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-currency-dollar me-2"></i>Podsumowanie Finansowe</h6>
                </div>
                <div class="card-body py-2 px-3">
                     <dl class="row mb-0 small w-100">
                        <dt class="col-7 fw-normal text-muted pt-2 d-flex align-items-center"><i class="bi bi-graph-up me-2 text-success"></i>Przychód ze sprzedaży:</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2"><small class="text-muted"><?php echo number_format($tn_wartosc_zamowien_all, 2, ',', ' '); ?> <?php echo $tn_waluta; ?></small></dd>

                        <dt class="col-7 fw-normal text-muted pt-2 d-flex align-items-center"><i class="bi bi-percent me-2 text-primary"></i>Prowizja i koszt pakowania:</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2"><small class="text-muted"><?php echo number_format($tn_prowizja_sprzedazy, 2, ',', ' '); ?> <?php echo $tn_waluta; ?></small></dd>

                        <dt class="col-7 fw-normal text-muted pt-2 d-flex align-items-center"><i class="bi bi-building-gear me-2 text-secondary"></i>Koszty związane z magazynowaniem:</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2"><small class="text-muted"><?php echo number_format($tn_koszty_magazynowania, 2, ',', ' '); ?> <?php echo $tn_waluta; ?></small></dd>

                        <dt class="col-7 fw-normal text-muted pt-2 d-flex align-items-center"><i class="bi bi-calculator-fill me-2 text-info"></i>Dochód do opodatkowania:</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2"><small class="text-muted"><?php echo number_format($tn_dochod_do_opodatkowania, 2, ',', ' '); ?> <?php echo $tn_waluta; ?></small></dd>

                        <dt class="col-7 fw-bold text-danger pt-2 d-flex align-items-center"><i class="bi bi-file-earmark-ruled-fill me-2"></i>Należny podatek:</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2 fw-bold text-danger"><small><?php echo number_format($tn_należny_podatek, 2, ',', ' '); ?> <?php echo $tn_waluta; ?></small></dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm mb-4 flex-grow-1">
                <div class="card-header bg-teal-subtle text-teal-emphasis d-flex justify-content-between align-items-center py-2">
                     <h6 class="mb-0 fw-semibold"><i class="bi bi-person-badge me-2"></i>Informacje o Sesji</h6>
                </div>
                <div class="card-body py-2 px-3">
                    <dl class="row mb-0 small w-100">
                        <dt class="col-7 fw-normal text-muted pt-2 d-flex align-items-center"><i class="bi bi-person-fill me-2 text-teal"></i>Zalogowany użytkownik:</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2"><small class="text-muted"><?php echo $tn_user_display_name; ?></small></dd>
                        <dt class="col-7 fw-normal text-muted pt-2 d-flex align-items-center"><i class="bi bi-key-fill me-2 text-teal"></i>ID użytkownika:</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2"><small class="text-muted"><?php echo $tn_user_id; ?></small></dd>
                        <dt class="col-7 fw-normal text-muted pt-2 d-flex align-items-center"><i class="bi bi-pc-display-horizontal me-2 text-teal"></i>Adres IP:</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2"><small class="text-muted"><?php echo htmlspecialchars($tn_user_ip); ?></small></dd>
                        <dt class="col-7 fw-normal text-muted pt-2 d-flex align-items-center"><i class="bi bi-diagram-3 me-2 text-teal"></i>Host:</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2"><small class="text-muted"><?php echo htmlspecialchars($tn_user_hostname); ?></small></dd>
                        <dt class="col-7 fw-normal text-muted pt-2 d-flex align-items-center"><i class="bi bi-clock-fill me-2 text-teal"></i>Czas logowania:</dt>
                        <dd class="col-5 text-end fs-6 lh-sm pt-2"><small class="text-muted"><?php echo htmlspecialchars($tn_czas_logowania); ?></small></dd>
                    </dl>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
:root { 
    --bs-purple: #6f42c1; 
    --bs-indigo: #6610f2;
    --bs-teal: #20c997; 
}
/* Dodano subtelne tła dla nowych kart */
.bg-primary-subtle { background-color: #cfe2ff !important; }
.text-primary-emphasis { color: #0a3678 !important; }
.bg-success-subtle { background-color: #d1e7dd !important; }
.text-success-emphasis { color: #0f5132 !important; }
.bg-warning-subtle { background-color: #fff3cd !important; }
.text-warning-emphasis { color: #664d03 !important; }
.bg-danger-subtle { background-color: #f8d7da !important; }
.text-danger-emphasis { color: #58151c !important; }
.bg-info-subtle { background-color: #cff4fc !important; } /* Już mogło istnieć */
.text-info-emphasis { color: #055160 !important; } /* Już mogło istnieć */
.bg-teal-subtle { background-color: #ccf0e7 !important; }
.text-teal-emphasis { color: #0c6850 !important; }


.border-purple { border-color: var(--bs-purple) !important; }
.text-purple { color: var(--bs-purple) !important; }
.bg-purple { background-color: var(--bs-purple) !important; }
.bg-purple-subtle { background-color: #e7e0f4 !important; }
.text-purple-emphasis { color: #492a81 !important; }

.border-indigo { border-color: var(--bs-indigo) !important; }
.text-indigo { color: var(--bs-indigo) !important; }
.bg-indigo { background-color: var(--bs-indigo) !important; }

.border-teal { border-color: var(--bs-teal) !important; }
.text-teal { color: var(--bs-teal) !important; }
.bg-teal { background-color: var(--bs-teal) !important; }
dl.row dt .text-teal { color: var(--bs-teal) !important; } 


.card-body .fs-2 { font-size: 2rem !important; }
.card-body h4 { font-weight: 600; }
.card-body h4.small { font-size: 1.1rem; font-weight: 500; }
.card-body p.small { font-size: 0.8rem; }
.kpi-badge { font-size: 0.7em; padding: 0.3em 0.5em; line-height: 1; }

.tn-card-body-scrollable { max-height: 280px; overflow-y: auto; }
.tn-card-body-scrollable-sm { 
    max-height: 450px; 
    overflow-y: auto; 
} 

.list-group-item { border-left: 0; border-right: 0; border-color: var(--bs-border-color-translucent); }
.list-group-item:first-child { border-top-left-radius: 0; border-top-right-radius: 0; border-top: 0; }
.list-group-item:last-child { border-bottom-left-radius: 0; border-bottom-right-radius: 0; border-bottom: 0; }
.list-group-item-action:hover { background-color: var(--bs-tertiary-bg); }
[data-bs-theme="dark"] .list-group-item-action:hover { background-color: var(--bs-secondary-bg); }
.card-body[class*="tn-card-body-scrollable"] .list-group { border-radius: 0; }

.card-footer { border-top: 1px solid var(--bs-border-color-translucent); }
.card-footer.bg-light { background-color: var(--bs-light-bg-subtle) !important; }

.badge.fs-inherit { font-size: 0.9em; padding: 0.35em 0.6em; }

.tn-dl-bg { background-color: var(--bs-tertiary-bg); border-radius: var(--bs-border-radius-sm); padding: 0.5rem 0.75rem; }
[data-bs-theme="dark"] .tn-dl-bg { background-color: var(--bs-secondary-bg); }
dl.row { --bs-gutter-x: 0.5rem; }
dl.row dt, dl.row dd {
    padding-top: 0.5rem; 
    padding-bottom: 0.5rem; 
    margin-bottom: 0;
    border-bottom: 1px solid var(--bs-border-color-translucent);
}
dl.row dt i.bi { 
    font-size: 1.1em; 
    color: var(--bs-secondary-color); 
}
dl.row dt.text-danger i.bi, dl.row dd.text-danger i.bi { color: var(--bs-danger); } 
dl.row dt .text-primary { color: var(--bs-primary) !important; } 
dl.row dt .text-indigo { color: var(--bs-indigo) !important; }
dl.row dt .text-purple { color: var(--bs-purple) !important; }
dl.row dt .text-success { color: var(--bs-success) !important; }
dl.row dt .text-info { color: var(--bs-info) !important; }
dl.row dt .text-warning { color: var(--bs-warning) !important; }
dl.row dt .text-secondary { color: var(--bs-secondary) !important; }


dl.row > dt.col-12 { 
    border-bottom: 1px solid var(--bs-border-color-translucent); 
    padding-bottom: 0.5rem;
    margin-bottom: 0.5rem; 
    font-size: 0.95rem; 
}
dl.row > dt.col-12 i.bi {
    font-size: 1.2em; 
    color: var(--bs-info-emphasis); 
}

dl.row > *:last-of-type,
dl.row > *:nth-last-child(2):nth-child(odd) {
    border-bottom: 0;
}
dl.row dt { color: var(--bs-secondary-color); }
dl.row dt.fw-bold.text-danger { color: var(--bs-danger) !important; } 
dl.row dd { word-break: break-word; }
dl.row dd small { font-size: 0.9em; font-weight: normal; } 
dl.row dd.fw-bold.text-danger small { font-weight: 600; color: var(--bs-danger) !important; } 
dl.row dd small span.fst-italic { font-size: 0.9em; color: var(--bs-secondary-color); } 
dl.row dd.fs-6 small { font-size: 1em; } 


.tn-list-img { width: 32px; height: 32px; object-fit: contain; background-color: #fff; flex-shrink: 0; display: block; }

#notes-status { transition: opacity 0.5s ease-in-out; }
#notes-status.saving { color: var(--bs-primary); opacity: 1; }
#notes-status.saved { color: var(--bs-success); opacity: 1; }
#notes-status.error { color: var(--bs-danger); opacity: 1; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notesTextarea = document.getElementById('dashboard-notes');
    const notesStatus = document.getElementById('notes-status');
    let saveTimeout;
    const debounceTime = 750; 

    const saveNotes = () => {
        if (!notesStatus) return;
        const notesContent = notesTextarea.value;
        notesStatus.textContent = 'Zapisuję...';
        notesStatus.className = 'text-muted fst-italic saving';

        let saveUrl = 'save_notes.php'; 
        try {
             let currentBaseUrl = window.location.href.split('?')[0].split('#')[0];
             let currentPathSegments = currentBaseUrl.split('/');
             currentPathSegments.pop(); 
             saveUrl = currentPathSegments.join('/') + '/' + 'save_notes.php';
        } catch(e) { 
            console.error("Nie można zbudować URL dla save_notes.php na podstawie bieżącej lokalizacji.", e);
        }

        fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notes: notesContent })
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => { 
                    throw new Error(`Błąd serwera: ${response.status} ${response.statusText}. Odpowiedź: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                notesStatus.textContent = 'Zapisano ✅';
                notesStatus.className = 'text-muted fst-italic saved';
            } else {
                notesStatus.textContent = 'Błąd zapisu';
                notesStatus.className = 'text-muted fst-italic error';
                console.error('Błąd zapisu notatek (odpowiedź serwera):', data.message || 'Nieznany błąd');
            }
            setTimeout(() => { 
                notesStatus.textContent = '';
                notesStatus.className = 'text-muted fst-italic';
            }, 2000);
        })
        .catch(error => {
            notesStatus.textContent = 'Błąd sieci/serwera';
            notesStatus.className = 'text-muted fst-italic error';
            console.error('Błąd podczas zapisu notatek (fetch catch):', error);
             setTimeout(() => { 
                notesStatus.textContent = '';
                notesStatus.className = 'text-muted fst-italic';
            }, 2000);
        });
    };

    if (notesTextarea && notesStatus) {
        notesTextarea.addEventListener('input', () => {
            notesStatus.textContent = 'Zmiany niezapisane'; 
            notesStatus.className = 'text-muted fst-italic';
            clearTimeout(saveTimeout); 
            saveTimeout = setTimeout(saveNotes, debounceTime); 
        });
    } else {
        console.warn('Elementy #dashboard-notes lub #notes-status nie zostały znalezione na stronie.');
    }
});
</script>
