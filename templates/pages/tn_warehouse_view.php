<?php

// --- Definicje Funkcji Pomocniczych ---
if (!function_exists('tn_wyekstrahuj_poziom')) { function tn_wyekstrahuj_poziom(string $id_miejsca): string { $czesci = explode('-', $id_miejsca); return $czesci[1] ?? 'Nieznany'; } }

// --- Sprawdzenie dostępności globalnych funkcji ---
if (!function_exists('tn_pobierz_sciezke_obrazka')) { die('Błąd krytyczny: Brak funkcji tn_pobierz_sciezke_obrazka().'); }
if (!function_exists('tn_generuj_link_akcji_get')) { die('Błąd krytyczny: Brak funkcji tn_generuj_link_akcji_get()'); }
if (!function_exists('tn_generuj_url')) { die('Błąd krytyczny: Brak funkcji tn_generuj_url()'); }

// --- Przygotowanie Danych ---
$tn_mapa_produktow = [];
if (!empty($tn_produkty)) {
    foreach ($tn_produkty as $p) {
        if (isset($p['id'])) {
            $zdjecie_url = tn_pobierz_sciezke_obrazka($p['image'] ?? null);
            $tn_mapa_produktow[$p['id']] = [
                'id' => $p['id'],
                'name' => htmlspecialchars($p['name'] ?? 'B/N'),
                'catalog_nr' => htmlspecialchars($p['tn_numer_katalogowy'] ?? 'Brak'),
                'stock' => intval($p['stock'] ?? 0),
                'zdjecie_glowne_url' => $zdjecie_url,
                'link' => tn_generuj_url('product_preview', ['id' => $p['id']])
            ];
        }
    }
}

$tn_mapa_id_regalow = [];
if (!empty($tn_regaly)) {
    uksort($tn_regaly, 'strnatcmp');
    foreach ($tn_regaly as $r) { if (isset($r['tn_id_regalu'])) { $tn_mapa_id_regalow[$r['tn_id_regalu']] = $r; } }
} else {
    $tn_regaly = [];
}

$tn_lokalizacje_wg_poziomow = [];
$tn_wszystkie_miejsca = 0;
$tn_zajete_miejsca = 0;
$tn_bledne_lokalizacje = 0;
$tn_statystyki_regalow = [];

if (!empty($tn_stan_magazynu)) {
    usort($tn_stan_magazynu, fn($a, $b) => strnatcmp($a['id'] ?? '', $b['id'] ?? ''));

    foreach ($tn_stan_magazynu as $tn_miejsce) {
        if (!is_array($tn_miejsce) || !isset($tn_miejsce['id']) || !isset($tn_miejsce['status'])) continue;

        $tn_wszystkie_miejsca++;
        $is_occupied = (strtolower($tn_miejsce['status'] ?? '') === 'occupied');
        if ($is_occupied) $tn_zajete_miejsca++;

        $tn_id_regalu_lokalizacji = $tn_miejsce['tn_id_regalu'] ?? null;
        $regal_key = 'BEZ_REGALU';

        if ($tn_id_regalu_lokalizacji !== null && isset($tn_mapa_id_regalow[$tn_id_regalu_lokalizacji])) {
            $regal_key = $tn_id_regalu_lokalizacji;
            if (!isset($tn_statystyki_regalow[$regal_key])) {
                $tn_statystyki_regalow[$regal_key] = ['total' => 0, 'occupied' => 0];
            }
            $tn_statystyki_regalow[$regal_key]['total']++;
            if ($is_occupied) {
                $tn_statystyki_regalow[$regal_key]['occupied']++;
            }
        } elseif ($tn_id_regalu_lokalizacji !== null) {
            $tn_bledne_lokalizacje++;
            $regal_key = 'BEZ_REGALU';
        } else {
             $tn_bledne_lokalizacje++;
        }

        $poziom_key = tn_wyekstrahuj_poziom($tn_miejsce['id']);

        if (!isset($tn_lokalizacje_wg_poziomow[$regal_key])) $tn_lokalizacje_wg_poziomow[$regal_key] = [];
        if (!isset($tn_lokalizacje_wg_poziomow[$regal_key][$poziom_key])) $tn_lokalizacje_wg_poziomow[$regal_key][$poziom_key] = [];

        $tn_miejsce['regal_id_for_js'] = $regal_key;
        $tn_miejsce['product_id_for_js'] = $tn_miejsce['product_id'] ?? null;

        $tn_lokalizacje_wg_poziomow[$regal_key][$poziom_key][] = $tn_miejsce;
    }
}

$tn_poprawne_miejsca = $tn_wszystkie_miejsca - $tn_bledne_lokalizacje;
$tn_wolne_miejsca = $tn_poprawne_miejsca - $tn_zajete_miejsca;
$tn_procent_zapelnienia = $tn_poprawne_miejsca > 0 ? round(($tn_zajete_miejsca / $tn_poprawne_miejsca) * 100) : 0;

$tn_progress_class = 'bg-success';
if ($tn_procent_zapelnienia > 85) $tn_progress_class = 'bg-danger';
elseif ($tn_procent_zapelnienia > 60) $tn_progress_class = 'bg-warning text-dark';

$placeholder_src = tn_pobierz_sciezke_obrazka(null);
$barcodeScriptPath = 'kod_kreskowy.php';

?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Widok Magazynu - tnApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* --- Gęstość Widoku (Definicje klas) --- */
        .warehouse-density-compact .tn-location-slot { padding: 0.25rem; font-size: 0.8rem; min-height: 120px; }
        .warehouse-density-compact .tn-slot-id { font-size: 0.7rem; }
        .warehouse-density-compact .tn-slot-thumbnail-small { width: 25px; height: 25px; margin-right: 3px; }
        .warehouse-density-compact .tn-slot-product-details .badge { font-size: 0.55rem; padding: 0.1em 0.25em; }
        .warehouse-density-compact .tn-slot-product-details small { font-size: 0.65rem; }
        .warehouse-density-compact .tn-slot-product-details .tn-product-link { font-size: 0.7rem; }
        .warehouse-density-compact .tn-slot-empty-text { font-size: 0.7rem; }
        .warehouse-density-compact .tn-slot-actions { top: 2px; right: 2px; gap: 2px; }
        .warehouse-density-compact .tn-slot-action-btn { padding: 0.05rem 0.15rem; font-size: 0.6rem; }
        .warehouse-density-compact .tn-barcode-image { height: 12px; }
        .warehouse-density-compact .tn-slot-status-icon { font-size: 0.7rem; }
        .warehouse-density-compact .tn-level-slots { gap: 0.25rem; }

        /* Normal (Domyślny) */
        .warehouse-density-normal .tn-location-slot { padding: 0.5rem; font-size: 0.875rem; min-height: 150px; }
        .warehouse-density-normal .tn-slot-id { font-size: 0.8rem; }
        .warehouse-density-normal .tn-slot-thumbnail-small { width: 40px; height: 40px; margin-right: 8px; }
        .warehouse-density-normal .tn-slot-product-details .badge { font-size: 0.7rem; padding: 0.2em 0.4em; }
        .warehouse-density-normal .tn-slot-product-details small { font-size: 0.8rem; }
        .warehouse-density-normal .tn-slot-product-details .tn-product-link { font-size: 0.875rem; }
        .warehouse-density-normal .tn-slot-empty-text { font-size: 0.875rem; }
        .warehouse-density-normal .tn-slot-actions { top: 4px; right: 4px; gap: 3px; }
        .warehouse-density-normal .tn-slot-action-btn { padding: 0.2rem 0.4rem; font-size: 0.8rem; }
        .warehouse-density-normal .tn-barcode-image { height: 20px; }
        .warehouse-density-normal .tn-slot-status-icon { font-size: 1rem; }
        .warehouse-density-normal .tn-level-slots { gap: 0.5rem; }

        .warehouse-density-large .tn-location-slot { padding: 0.75rem; font-size: 1rem; min-height: 180px; }
        .warehouse-density-large .tn-slot-id { font-size: 0.9rem; }
        .warehouse-density-large .tn-slot-thumbnail-small { width: 50px; height: 50px; margin-right: 10px; }
        .warehouse-density-large .tn-slot-product-details .badge { font-size: 0.8rem; padding: 0.25em 0.5em; }
        .warehouse-density-large .tn-slot-product-details small { font-size: 0.9rem; }
        .warehouse-density-large .tn-slot-product-details .tn-product-link { font-size: 1rem; }
        .warehouse-density-large .tn-slot-empty-text { font-size: 1rem; }
        .warehouse-density-large .tn-slot-actions { top: 6px; right: 6px; gap: 4px;}
        .warehouse-density-large .tn-slot-action-btn { padding: 0.25rem 0.5rem; font-size: 0.9rem; }
        .warehouse-density-large .tn-barcode-image { height: 25px; }
        .warehouse-density-large .tn-slot-status-icon { font-size: 1.1rem; }
        .warehouse-density-large .tn-level-slots { gap: 0.75rem; }

        /* --- Ogólne Style Siatki Magazynu --- */
        .tn-level-slots {
            display: grid; /* Zmieniono na CSS Grid */
            grid-template-columns: repeat(4, 1fr); /* 4 równe kolumny */
            gap: var(--bs-gutter-x, 0.75rem);
        }

        .tn-location-slot {
            display: flex;
            flex-direction: column;
            position: relative;
            border: 1px solid var(--bs-border-color-translucent);
            border-radius: var(--bs-border-radius-sm);
            overflow: hidden;
            background-color: var(--bs-body-bg);
            box-shadow: var(--bs-box-shadow-sm);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out, border-color 0.3s ease;
        }
        .tn-location-slot:hover {
            transform: translateY(-3px);
            box-shadow: var(--bs-box-shadow);
        }

        /* Statusy wizualne */
        .tn-location-slot.status-occupied { border-left: 4px solid var(--bs-primary); }
        .tn-location-slot.status-empty {
            border-left: 4px solid var(--bs-success);
            cursor: pointer;
            background-color: var(--bs-success-bg-subtle);
        }
        .tn-location-slot.status-error { border-left: 4px solid var(--bs-danger); background-color: var(--bs-danger-bg-subtle); }

        /* Nagłówek slotu (ID, ikona statusu) */
        .tn-slot-header {
            padding: 0.3rem 0.5rem;
            background-color: var(--bs-tertiary-bg);
            border-bottom: 1px solid var(--bs-border-color-translucent);
            flex-shrink: 0;
        }
        .tn-slot-id {
            font-weight: 500;
            color: var(--bs-secondary-color);
            cursor: pointer;
            font-family: 'Courier New', Courier, monospace;
        }
        .tn-slot-id:hover { color: var(--bs-primary); }
        .tn-slot-status-icon { vertical-align: middle; }

        /* Główna zawartość slotu */
        .tn-slot-content {
            padding: 0.5rem;
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-grow: 1;
            overflow: hidden;
        }
        .tn-slot-product { text-align: left; width: 100%; }
        .tn-slot-thumbnail-small {
            object-fit: cover;
            border-radius: var(--bs-border-radius-sm);
            vertical-align: middle;
            border: 1px solid var(--bs-border-color-translucent);
            flex-shrink: 0;
            background-color: #fff;
        }
        .tn-slot-thumbnail-placeholder {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bs-secondary-bg-subtle);
            color: var(--bs-secondary-color);
        }
        .tn-slot-thumbnail-placeholder i { font-size: 1.2em; }

        .tn-img-placeholder {
            opacity: 0.6;
            filter: grayscale(80%);
        }
        .tn-slot-product-details {
            display: inline-block;
            vertical-align: middle;
            overflow: hidden;
        }
        .tn-slot-product-details .tn-product-link {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--bs-body-color);
            font-weight: 500;
        }
        .tn-slot-product-details .tn-product-link:hover { color: var(--bs-primary); }
        .tn-slot-empty-text {
            color: var(--bs-success-text-emphasis);
            font-style: italic;
            text-align: center;
            width: 100%;
        }

        /* Przyciski akcji w slocie */
        .tn-slot-actions {
            position: absolute;
            display: flex;
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
            z-index: 5;
            background-color: rgba(var(--bs-body-bg-rgb), 0.7);
            padding: 2px;
            border-radius: var(--bs-border-radius-sm);
            pointer-events: none;
        }
        .tn-location-slot:hover .tn-slot-actions {
            opacity: 1;
            pointer-events: auto;
        }
        .tn-slot-action-btn {
            line-height: 1;
            border-color: rgba(var(--bs-body-color-rgb), 0.2);
        }
        .tn-slot-action-btn:hover {
            border-color: currentColor;
        }
        .tn-location-slot.status-empty .tn-slot-actions .tn-action-print-label,
        .tn-location-slot.status-error .tn-slot-actions .tn-action-print-label,
        .tn-location-slot.status-empty .tn-slot-actions .tn-action-clear,
        .tn-location-slot.status-error .tn-slot-actions .tn-action-clear,
        .tn-location-slot.status-empty .tn-slot-actions .tn-action-quick-view,
        .tn-location-slot.status-error .tn-slot-actions .tn-action-quick-view {
            display: none;
        }

        /* Kod kreskowy na dole slotu */
        .tn-slot-barcode {
            margin-top: auto;
            padding: 0.3rem 0.5rem;
            background-color: var(--bs-tertiary-bg);
            border-top: 1px solid var(--bs-border-color-translucent);
            flex-shrink: 0;
        }
        .tn-barcode-image {
            display: block;
            margin: 0 auto;
            max-width: 90%;
            object-fit: contain;
            background-color: #fff;
            padding: 1px;
        }

        /* Podświetlenie powiązanych slotów */
        .tn-slot-related-highlight {
            box-shadow: 0 0 0 3px var(--bs-info-border-subtle);
            border-left-color: var(--bs-info) !important;
            z-index: 2 !important;
        }

        /* Styl Popovera */
        .tn-warehouse-popover { max-width: 250px; }

        /* Styl lepkich filtrów */
        .tn-warehouse-filters {
            background-color: rgba(var(--bs-body-bg-rgb), 0.95);
            backdrop-filter: blur(5px);
        }

        /* Styl dla karty regału i poziomów */
        .tn-regal-card { transition: opacity 0.3s ease, display 0s linear 0.3s; }
        .tn-level-block {
            border-bottom: 1px solid var(--bs-border-color-translucent);
            background-color: var(--bs-body-bg);
        }
        .tn-level-block:last-child { border-bottom: none; }
        .tn-level-header {
            background-color: var(--bs-secondary-bg-subtle);
            font-size: 0.85em;
            padding: 0.2rem 0.75rem;
            border-bottom: 1px solid var(--bs-border-color-translucent);
        }
        .tn-btn-action { line-height: 1; }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Magazyn</h1>
         <div class="d-flex flex-wrap gap-2 align-items-center">
            <button type="button" class="btn btn-success btn-sm" onclick="if(typeof tnApp?.openRegalModal === 'function') tnApp.openRegalModal(); else alert('Błąd JS: Funkcja openRegalModal nie istnieje!');"><i class="bi bi-plus-circle me-1"></i>Dodaj Regał</button>
            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#generateLocationsModal"><i class="bi bi-magic me-1"></i>Generuj Lokalizacje</button>
         </div>
    </div>

    <div class="card shadow-sm mb-4 tn-statystyki-magazynu">
        <div class="card-header bg-light-subtle py-2"><h6 class="mb-0 fw-normal"><i class="bi bi-bar-chart-line-fill me-2"></i>Statystyki Magazynu</h6></div>
        <div class="card-body p-3 small">
            <dl class="row mb-2 g-1">
                <dt class="col-sm-5 col-md-4 col-lg-3">Zdefiniowanych regałów:</dt><dd class="col-sm-7 col-md-8 col-lg-9"><strong><?php echo count($tn_mapa_id_regalow); ?></strong></dd>
                <dt class="col-sm-5 col-md-4 col-lg-3">Całkowita liczba miejsc:</dt><dd class="col-sm-7 col-md-8 col-lg-9"><strong><?php echo $tn_wszystkie_miejsca; ?></strong></dd>
                <dt class="col-sm-5 col-md-4 col-lg-3">Miejsca zajęte:</dt><dd class="col-sm-7 col-md-8 col-lg-9"><strong class="text-primary"><?php echo $tn_zajete_miejsca; ?></strong></dd>
                <dt class="col-sm-5 col-md-4 col-lg-3">Miejsca wolne (w regałach):</dt><dd class="col-sm-7 col-md-8 col-lg-9"><strong class="text-secondary"><?php echo max(0, $tn_wolne_miejsca); ?></strong></dd>
                <?php if ($tn_bledne_lokalizacje > 0): ?>
                    <dt class="col-sm-5 col-md-4 col-lg-3 text-danger">Miejsca z błędem:</dt>
                    <dd class="col-sm-7 col-md-8 col-lg-9"><strong class="text-danger"><?php echo $tn_bledne_lokalizacje; ?></strong></dd>
                <?php endif; ?>
            </dl>
            <?php if ($tn_poprawne_miejsca > 0): ?>
                <label class="form-label fw-bold small mb-1" id="progressLabelZapelnienie">Ogólne zapełnienie zdefiniowanych miejsc:</label>
                <div class="progress mt-1" role="progressbar" style="height: 18px;" aria-labelledby="progressLabelZapelnienie" aria-valuenow="<?php echo $tn_procent_zapelnienia; ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar <?php echo $tn_progress_class; ?> progress-bar-striped progress-bar-animated" style="width: <?php echo $tn_procent_zapelnienia; ?>%; font-size: 0.75em;">
                        <strong><?php echo $tn_procent_zapelnienia; ?>%</strong>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-muted fst-italic mt-2 mb-0 small">Brak poprawnie zdefiniowanych miejsc do obliczenia zapełnienia.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm mb-4 tn-warehouse-filters sticky-top bg-body-tertiary" style="top: 65px; z-index: 1025;">
        <div class="card-header bg-light-subtle py-2"><h6 class="mb-0 fw-normal"><i class="bi bi-filter me-2"></i>Filtrowanie Widoku</h6></div>
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-6 col-lg-3">
                    <label for="tn_filter_regal" class="form-label small mb-1">Regał:</label>
                    <select id="tn_filter_regal" class="form-select form-select-sm">
                        <option value="all" selected>Wszystkie regały</option>
                        <?php foreach ($tn_mapa_id_regalow as $id_regalu => $regal_dane): ?>
                            <option value="<?php echo htmlspecialchars($id_regalu); ?>">
                                <?php echo htmlspecialchars($id_regalu); ?>
                                <?php echo !empty($regal_dane['tn_opis_regalu']) ? ' - ' . htmlspecialchars($regal_dane['tn_opis_regalu']) : ''; ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if (isset($tn_lokalizacje_wg_poziomow['BEZ_REGALU'])): ?>
                            <option value="BEZ_REGALU">Miejsca bez/błędnym regałem</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-6 col-lg-3">
                    <label for="tn_filter_status" class="form-label small mb-1">Status miejsca:</label>
                    <select id="tn_filter_status" class="form-select form-select-sm">
                        <option value="all" selected>Wszystkie</option>
                        <option value="occupied">Zajęte</option>
                        <option value="empty">Wolne</option>
                        <option value="error">Błąd</option>
                    </select>
                </div>
                <div class="col-lg-4">
                    <label for="tn_filter_text" class="form-label small mb-1">Szukaj (ID miejsca/produktu, nazwa...):</label>
                    <input type="search" id="tn_filter_text" class="form-control form-control-sm" placeholder="Wpisz szukaną frazę...">
                </div>
                <div class="col-lg-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="tn_clear_filters_btn">
                        <i class="bi bi-x-lg me-1"></i> Wyczyść
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
         <h4 class="mb-0 h5"><i class="bi bi-grid-fill me-2"></i>Rozmieszczenie w Magazynie</h4>
         <div class="btn-group btn-group-sm" role="group" aria-label="Kontrola gęstości widoku">
            <input type="radio" class="btn-check" name="density" id="densityCompact" autocomplete="off" value="compact">
            <label class="btn btn-outline-secondary" for="densityCompact" title="Widok kompaktowy"><i class="bi bi-arrows-angle-contract"></i></label>
            <input type="radio" class="btn-check" name="density" id="densityNormal" autocomplete="off" value="normal">
            <label class="btn btn-outline-secondary" for="densityNormal" title="Widok normalny"><i class="bi bi-arrows-fullscreen"></i></label>
            <input type="radio" class="btn-check" name="density" id="densityLarge" autocomplete="off" value="large">
            <label class="btn btn-outline-secondary" for="densityLarge" title="Widok duży"><i class="bi bi-arrows-angle-expand"></i></label>
        </div>
    </div>

    <div id="warehouseGridContainer" class="warehouse-density-normal">

        <?php if (empty($tn_mapa_id_regalow) && empty($tn_lokalizacje_wg_poziomow['BEZ_REGALU'])): ?>
            <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i> Brak zdefiniowanych regałów i lokalizacji w magazynie. Użyj przycisków powyżej, aby je dodać lub wygenerować.</div>
        <?php else: ?>

            <?php
                foreach ($tn_mapa_id_regalow as $tn_biezacy_id_regalu => $tn_regal):
                    $tn_poziomy_w_regale = $tn_lokalizacje_wg_poziomow[$tn_biezacy_id_regalu] ?? [];
                    if(!empty($tn_poziomy_w_regale)) uksort($tn_poziomy_w_regale, 'strnatcmp');

                    $stats = $tn_statystyki_regalow[$tn_biezacy_id_regalu] ?? ['total' => 0, 'occupied' => 0];
                    $tn_liczba_miejsc_w_regale = $stats['total'];
                    $tn_liczba_zajetych_w_regale = $stats['occupied'];
                    $tn_procent_zapelnienia_regalu = $tn_liczba_miejsc_w_regale > 0 ? round(($tn_liczba_zajetych_w_regale / $tn_liczba_miejsc_w_regale) * 100) : 0;

                    $tn_progress_class_regal = 'bg-success';
                    if ($tn_procent_zapelnienia_regalu > 85) $tn_progress_class_regal = 'bg-danger';
                    elseif ($tn_procent_zapelnienia_regalu > 60) $tn_progress_class_regal = 'bg-warning text-dark';
            ?>
                <div class="card tn-regal-card mb-4 shadow-sm" data-regal-id="<?php echo htmlspecialchars($tn_biezacy_id_regalu); ?>">
                    <div class="tn-regal-header card-header d-flex justify-content-between align-items-center py-2 flex-wrap gap-2">
                        <div class="me-auto">
                            <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
                                <i class="bi bi-bookshelf"></i>
                                <span>Regał: <?php echo htmlspecialchars($tn_biezacy_id_regalu); ?></span>
                                <?php if(!empty($tn_regal['tn_opis_regalu'])): ?>
                                    <small class="text-muted fw-normal d-none d-md-inline">- <?php echo htmlspecialchars($tn_regal['tn_opis_regalu']); ?></small>
                                <?php endif; ?>
                            </h6>
                            <?php if ($tn_liczba_miejsc_w_regale > 0): ?>
                            <div class="progress mt-1" role="progressbar" style="height: 8px; max-width: 200px;" aria-label="Zapełnienie regału <?php echo htmlspecialchars($tn_biezacy_id_regalu); ?>" aria-valuenow="<?php echo $tn_procent_zapelnienia_regalu; ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar <?php echo $tn_progress_class_regal; ?>" style="width: <?php echo $tn_procent_zapelnienia_regalu; ?>%"></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            <span class="badge bg-secondary rounded-pill"><?php echo $tn_liczba_zajetych_w_regale . '&nbsp;/&nbsp;' . $tn_liczba_miejsc_w_regale; ?></span>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1 tn-btn-action"
                                    onclick='if(typeof tnApp?.openRegalModal === "function") tnApp.openRegalModal(<?php echo json_encode($tn_regal, JSON_HEX_APOS | JSON_HEX_QUOT); ?>); else alert("Błąd JS!");'
                                    data-bs-toggle="tooltip" title="Edytuj regał">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="<?php echo tn_generuj_link_akcji_get('delete_regal', ['id' => $tn_biezacy_id_regalu]); ?>"
                               class="btn btn-outline-danger btn-sm py-0 px-1 tn-btn-action"
                               onclick="return confirm('UWAGA!\nUsunięcie regału spowoduje usunięcie WSZYSTKICH lokalizacji w nim zawartych oraz potencjalnie odpięcie produktów!\n\nCzy na pewno usunąć regał \'<?php echo htmlspecialchars(addslashes($tn_biezacy_id_regalu), ENT_QUOTES); ?>\'?');"
                               data-bs-toggle="tooltip" title="Usuń regał i wszystkie jego lokalizacje">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>
                    <div class="tn-regal-body card-body p-0">
                        <?php if (empty($tn_poziomy_w_regale)): ?>
                            <p class="text-muted fst-italic small p-3 mb-0">Brak zdefiniowanych lokalizacji w tym regale.</p>
                        <?php else: ?>
                            <?php
                                foreach ($tn_poziomy_w_regale as $tn_id_poziomu => $tn_miejsca_na_poziomie):
                            ?>
                                <div class="tn-level-block">
                                    <div class="tn-level-header px-3 py-1">
                                        <span class="fw-medium text-secondary-emphasis"><i class="bi bi-layers-half me-1 opacity-75"></i> Poziom: <?php echo htmlspecialchars($tn_id_poziomu); ?></span>
                                    </div>
                                    <div class="tn-level-slots p-3">
                                        <?php
                                            foreach ($tn_miejsca_na_poziomie as $tn_miejsce):
                                                $tn_id_miejsca = $tn_miejsce['id'] ?? 'B/D';
                                                $tn_id_miejsca_html = htmlspecialchars($tn_id_miejsca);
                                                $tn_status_aktualny = strtolower($tn_miejsce['status'] ?? 'error');
                                                $tn_produkt_id_z_miejsca = $tn_miejsce['product_id_for_js'];
                                                $tn_ilosc = $tn_miejsce['quantity'] ?? 0;
                                                $tn_produkt_info_tekst = strtolower($tn_id_miejsca_html . ' ' . $tn_biezacy_id_regalu);

                                                $tn_produkt_html = ''; $tn_modal_attributes = ''; $tn_clear_link_html = ''; $tn_tooltip_title = ''; $status_icon = ''; $popover_content = ''; $quick_view_button = '';
                                                $data_product_attribute = ''; $print_label_button = '';
                                                $product_name_attr = ''; $product_catalog_nr_attr = '';

                                                $barcodeUrl = $barcodeScriptPath . '?s=code128&d=' . urlencode($tn_id_miejsca) . '&h=50&ts=0';
                                                $largeBarcodeUrl = $barcodeScriptPath . '?s=code128&d=' . urlencode($tn_id_miejsca) . '&h=150&ts=0&th=20';

                                                if ($tn_status_aktualny === 'occupied' && $tn_produkt_id_z_miejsca !== null && isset($tn_mapa_produktow[$tn_produkt_id_z_miejsca])) {
                                                    $tn_produkt = $tn_mapa_produktow[$tn_produkt_id_z_miejsca];
                                                    $tn_produkt_info_tekst .= ' ' . $tn_produkt_id_z_miejsca . ' ' . strtolower($tn_produkt['name']);
                                                    $tn_prod_link = $tn_produkt['link'];
                                                    $zdjecie_url_produktu = $tn_produkt['zdjecie_glowne_url'];
                                                    $miniaturka_mala_html = '';

                                                    $data_product_attribute = 'data-product-id="' . htmlspecialchars($tn_produkt_id_z_miejsca) . '"';
                                                    $product_name_attr = 'data-product-name="' . htmlspecialchars($tn_produkt['name']) . '"';
                                                    $product_catalog_nr_attr = 'data-product-catalog-nr="' . htmlspecialchars($tn_produkt['catalog_nr']) . '"';

                                                    if ($zdjecie_url_produktu !== $placeholder_src && $zdjecie_url_produktu) {
                                                        $miniaturka_mala_html = '<img src="' . htmlspecialchars($zdjecie_url_produktu) . '" alt="Miniatura" class="tn-slot-thumbnail-small" loading="lazy" onerror="this.onerror=null; this.src=\''.htmlspecialchars($placeholder_src).'\'; this.classList.add(\'tn-img-placeholder\');">';
                                                    } else {
                                                        $miniaturka_mala_html = '<span class="tn-slot-thumbnail-small tn-slot-thumbnail-placeholder" title="Brak zdjęcia"><i class="bi bi-image"></i></span>';
                                                    }

                                                    $tn_produkt_html = '<div class="d-flex align-items-center gap-2">'
                                                                        .$miniaturka_mala_html
                                                                        .'<div class="tn-slot-product-details flex-grow-1">'
                                                                            .'<a href="'.$tn_prod_link.'" class="text-decoration-none fw-medium d-block tn-product-link" title="'.htmlspecialchars($tn_produkt['name']).'">'
                                                                                . htmlspecialchars(mb_strimwidth($tn_produkt['name'], 0, 25, '...'))
                                                                            .'</a>'
                                                                            .'<small class="d-block text-muted">ID: '.htmlspecialchars($tn_produkt_id_z_miejsca).' | Ilość: '.$tn_ilosc.' szt.</small>'
                                                                        .'</div>'
                                                                     .'</div>';
                                                    $tn_tooltip_title = 'Produkt: ' . htmlspecialchars($tn_produkt['name']) . ' (' . $tn_ilosc . ' szt.) | Lokalizacja: ' . $tn_id_miejsca_html;
                                                    $tn_link_opróżnij = tn_generuj_link_akcji_get('clear_slot', ['location_id' => $tn_id_miejsca]);
                                                    $tn_clear_link_html = '<a href="'.$tn_link_opróżnij.'" class="btn btn-outline-danger btn-sm tn-slot-action-btn tn-action-clear" data-bs-toggle="tooltip" title="Opróżnij lokalizację" onclick="return confirm(\'Czy na pewno chcesz opróżnić lokalizację ' . $tn_id_miejsca_html . '?\')"><i class="bi bi-x-lg"></i></a>';
                                                    $status_icon = '<i class="bi bi-box-seam-fill text-primary opacity-75 tn-slot-status-icon"></i>';
                                                    $popover_image = ($zdjecie_url_produktu !== $placeholder_src && $zdjecie_url_produktu) ? '<img src="'.htmlspecialchars($zdjecie_url_produktu).'" alt="Podgląd '.htmlspecialchars($tn_produkt['name']).'" class="img-fluid rounded mb-2" style="max-height: 100px;">' : '<div class="text-center text-muted mb-2"><i class="bi bi-image" style="font-size: 2rem;"></i><br>Brak zdjęcia</div>';
                                                    $popover_content_data = [
                                                        'title' => htmlspecialchars($tn_produkt['name']),
                                                        'content' => '<div class="text-center">' . $popover_image . '</div>'
                                                                    .'<p class="mb-1 small">ID Produktu: <strong>' . htmlspecialchars($tn_produkt_id_z_miejsca) . '</strong></p>'
                                                                    .'<p class="mb-1 small">Nr kat.: <strong>'. htmlspecialchars($tn_produkt['catalog_nr']) .'</strong></p>'
                                                                    .'<p class="mb-0 small">Ilość: <strong>' . $tn_ilosc . ' szt.</strong></p>'
                                                    ];
                                                    $popover_content = htmlspecialchars(json_encode($popover_content_data), ENT_QUOTES, 'UTF-8');
                                                    $quick_view_button = '<button type="button" class="btn btn-outline-info btn-sm tn-slot-action-btn tn-action-quick-view" data-bs-toggle="popover" data-popover-content=\''.$popover_content.'\' title="Szybki podgląd"><i class="bi bi-search"></i></button>';
                                                    $print_label_button = '<button type="button" class="btn btn-outline-secondary btn-sm tn-slot-action-btn tn-action-print-label" data-bs-toggle="tooltip" title="Drukuj etykietę produktu"><i class="bi bi-printer"></i></button>';

                                                } elseif ($tn_status_aktualny === 'empty') {
                                                    $tn_produkt_html = '<span class="tn-slot-empty-text"><i class="bi bi-plus-circle-dotted me-1"></i>Wolne</span>';
                                                    $tn_tooltip_title = 'Lokalizacja wolna: ' . $tn_id_miejsca_html . ' | Kliknij, aby przypisać produkt';
                                                    $tn_modal_attributes = 'data-bs-toggle="modal" data-bs-target="#assignWarehouseModal"';
                                                    $status_icon = '<i class="bi bi-square text-secondary opacity-75 tn-slot-status-icon"></i>';
                                                } else {
                                                    $tn_status_aktualny = 'error';
                                                    $tn_produkt_info_tekst .= ' error błąd';
                                                    $tn_produkt_html = '<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i> Błąd Danych</span>';
                                                    if($tn_produkt_id_z_miejsca && !isset($tn_mapa_produktow[$tn_produkt_id_z_miejsca])) {
                                                         $tn_tooltip_title = 'Błąd: Przypisany produkt (ID: '.htmlspecialchars($tn_produkt_id_z_miejsca).') nie istnieje! Lokalizacja: ' . $tn_id_miejsca_html;
                                                    } else {
                                                         $tn_tooltip_title = 'Błąd konfiguracji lub danych lokalizacji: ' . $tn_id_miejsca_html;
                                                    }
                                                    $status_icon = '<i class="bi bi-exclamation-diamond-fill text-danger opacity-75 tn-slot-status-icon"></i>';
                                                }

                                                $tn_data_attributes = 'data-location-id="' . $tn_id_miejsca_html . '" '
                                                                    . 'data-status="' . $tn_status_aktualny . '" '
                                                                    . 'data-regal-id="' . htmlspecialchars($tn_miejsce['regal_id_for_js']) .'" '
                                                                    . 'data-filter-text="' . htmlspecialchars(trim($tn_produkt_info_tekst)) . '" '
                                                                    . $data_product_attribute
                                                                    . $product_name_attr
                                                                    . $product_catalog_nr_attr;
                                        ?>
                                        <div class="tn-location-slot status-<?php echo $tn_status_aktualny; ?>" <?php echo $tn_data_attributes; ?> <?php echo $tn_modal_attributes; ?> data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo htmlspecialchars($tn_tooltip_title); ?>">
                                            <div class="tn-slot-actions">
                                                <?php echo $print_label_button; ?>
                                                <?php echo $quick_view_button; ?>
                                                <?php echo $tn_clear_link_html; ?>
                                            </div>
                                            <div class="tn-slot-header d-flex justify-content-between align-items-center">
                                                <span class="tn-slot-id font-monospace" title="Kliknij, aby skopiować ID: <?php echo $tn_id_miejsca_html; ?>" onclick="tnApp.copyToClipboard('<?php echo $tn_id_miejsca_html; ?>'); event.stopPropagation();"><?php echo $tn_id_miejsca_html; ?></span>
                                                <?php echo $status_icon; ?>
                                            </div>
                                            <div class="tn-slot-content flex-grow-1">
                                                <div class="tn-slot-product"><?php echo $tn_produkt_html; ?></div>
                                            </div>
                                            <div class="tn-slot-barcode text-center mt-auto pt-2"
                                                 data-location-id="<?php echo $tn_id_miejsca_html; ?>"
                                                 data-barcode-src="<?php echo htmlspecialchars($largeBarcodeUrl); ?>"
                                                 title="Kliknij, aby powiększyć kod kreskowy dla <?php echo $tn_id_miejsca_html; ?>"
                                                 style="cursor: pointer;">
                                                <img src="<?php echo htmlspecialchars($barcodeUrl); ?>"
                                                     alt="Kod kreskowy dla <?php echo $tn_id_miejsca_html; ?>"
                                                     class="tn-barcode-image" loading="lazy">
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php
            if (isset($tn_lokalizacje_wg_poziomow['BEZ_REGALU']) && !empty($tn_lokalizacje_wg_poziomow['BEZ_REGALU'])): ?>
                <div class="card tn-regal-card mb-4 border-danger shadow-sm" data-regal-id="BEZ_REGALU">
                    <div class="tn-regal-header card-header bg-danger-subtle text-danger-emphasis d-flex justify-content-between align-items-center py-2">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-exclamation-diamond-fill me-2"></i>Miejsca bez przypisanego lub z błędnym Regałem</h6>
                        <?php
                            $licznik_blednych = 0;
                            foreach($tn_lokalizacje_wg_poziomow['BEZ_REGALU'] as $poziom) $licznik_blednych += count($poziom);
                        ?>
                        <span class="badge bg-danger rounded-pill"><?php echo $licznik_blednych; ?></span>
                    </div>
                    <div class="tn-regal-body card-body p-3 d-flex flex-wrap">
                        <?php
                        foreach ($tn_lokalizacje_wg_poziomow['BEZ_REGALU'] as $tn_miejsca_na_poziomie):
                            foreach ($tn_miejsca_na_poziomie as $tn_miejsce):
                                $tn_id_miejsca = $tn_miejsce['id'] ?? 'B/D'; $tn_id_miejsca_html = htmlspecialchars($tn_id_miejsca);
                                $tn_produkt_html = '<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> Błąd Regału</span>';
                                $status_icon = '<i class="bi bi-exclamation-diamond-fill text-danger opacity-75 tn-slot-status-icon"></i>';
                                $barcodeUrl = $barcodeScriptPath . '?s=code128&d=' . urlencode($tn_id_miejsca) . '&h=50&ts=0';
                                $largeBarcodeUrl = $barcodeScriptPath . '?s=code128&d=' . urlencode($tn_id_miejsca) . '&h=150&ts=0&th=20';
                                $tn_data_attributes = 'data-location-id="'.$tn_id_miejsca_html.'" data-status="error" data-regal-id="BEZ_REGALU" data-filter-text="' . htmlspecialchars(strtolower($tn_id_miejsca_html . ' error błąd bez regalu')) . '"';
                        ?>
                            <div class="tn-location-slot status-error" <?php echo $tn_data_attributes; ?> data-bs-toggle="tooltip" title="Błąd: Lokalizacja nie jest poprawnie przypisana do istniejącego regału!">
                                 <div class="tn-slot-header d-flex justify-content-between align-items-center">
                                     <span class="tn-slot-id font-monospace" title="Kliknij, aby skopiować ID: <?php echo $tn_id_miejsca_html; ?>" onclick="tnApp.copyToClipboard('<?php echo $tn_id_miejsca_html; ?>'); event.stopPropagation();"><?php echo $tn_id_miejsca_html; ?></span>
                                     <?php echo $status_icon; ?>
                                 </div>
                                 <div class="tn-slot-content flex-grow-1"> <div class="tn-slot-product"><?php echo $tn_produkt_html; ?></div> </div>
                                 <div class="tn-slot-barcode text-center mt-auto pt-1"
                                      data-location-id="<?php echo $tn_id_miejsca_html; ?>"
                                      data-barcode-src="<?php echo htmlspecialchars($largeBarcodeUrl); ?>"
                                      title="Kliknij, aby powiększyć kod kreskowy dla <?php echo $tn_id_miejsca_html; ?>"
                                      style="cursor: pointer;">
                                      <img src="<?php echo htmlspecialchars($barcodeUrl); ?>" alt="Kod kreskowy dla <?php echo $tn_id_miejsca_html; ?>" class="tn-barcode-image" loading="lazy">
                                 </div>
                            </div>
                        <?php endforeach; endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>
</div>

<div id="noFilterResultsMessage" class="alert alert-warning d-none mt-3" role="alert">
  <i class="bi bi-exclamation-triangle-fill me-2"></i> Brak lokalizacji pasujących do wybranych filtrów.
</div>

<?php // --- Modale Aplikacji (Brakuje AssignWarehouseModal i RegalModal - upewnij się, że są includowane gdzie indziej) --- ?>

<div class="modal fade" id="barcodeModal" tabindex="-1" aria-labelledby="barcodeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="barcodeModalLabel">Kod kreskowy dla lokalizacji: <span id="barcodeModalLocationId" class="fw-bold"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body text-center">
                <img id="barcodeModalImage" src="" alt="Powiększony kod kreskowy" class="img-fluid" style="max-height: 200px; min-height: 150px;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zamknij</button>
                <button type="button" class="btn btn-primary" onclick="if(typeof tnApp?.printBarcodeModal === 'function') tnApp.printBarcodeModal(); else alert('Błąd JS: Brak funkcji printBarcodeModal');">
                    <i class="bi bi-printer me-1"></i> Drukuj Kod Lokalizacji
                </button>
            </div>
        </div>
    </div>
</div>


<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
</div>
<script src="https://twoja-nazwa.pl/public/js/tn_warehouse_view.js"></script>

</body>
</html>