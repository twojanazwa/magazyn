<?php
// templates/pages/tn_order_preview.php
/**
 * Widok podglądu szczegółów pojedynczego zamówienia.
 * Wersja: 1.5 (Zmieniono generowanie etykiety na JS)
 */

// Potrzebne zmienne z index.php:
/** @var array|null $tn_zamowienie_podgladu */
/** @var array $tn_produkty */
/** @var array $tn_kurierzy */
/** @var array $tn_ustawienia_globalne */
/** @var string $tn_token_csrf */

// --- Sprawdzenie danych i inicjalizacja ---
if (empty($tn_zamowienie_podgladu) || !isset($tn_zamowienie_podgladu['id'])) {
    if(function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash('Nie znaleziono zamówienia do wyświetlenia lub jest ono nieprawidłowe.', 'warning');
    $redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('orders') : 'index.php?page=orders';
    echo "<script>window.location.href = '" . addslashes($redirect_url) . "';</script>";
    exit;
}

// Upewnij się, że funkcje pomocnicze istnieją
if (!function_exists('tn_generuj_url')) { die('Błąd krytyczny: Brak funkcji tn_generuj_url()'); }
if (!function_exists('tn_pobierz_sciezke_obrazka')) { die('Błąd krytyczny: Brak funkcji tn_pobierz_sciezke_obrazka()'); }
// Załaduj helpery danych, jeśli jeszcze nie załadowane (dla tn_laduj_kurierow)
if (!function_exists('tn_laduj_kurierow')) require_once TN_SCIEZKA_SRC . 'functions/tn_data_helpers.php';

// --- Przygotowanie danych do wyświetlenia ---
$order_id = htmlspecialchars($tn_zamowienie_podgladu['id']);
$status_zam = htmlspecialchars($tn_zamowienie_podgladu['status'] ?? 'Nieznany');
$status_platnosci = htmlspecialchars($tn_zamowienie_podgladu['tn_status_platnosci'] ?? '');
$data_zamowienia = '-'; $data_aktualizacji = '-';
$tn_format_daty_czasu = ($tn_ustawienia_globalne['tn_format_daty'] ?? 'd.m.Y') . ' ' . ($tn_ustawienia_globalne['tn_format_czasu'] ?? 'H:i');
try { $data_zamowienia = !empty($tn_zamowienie_podgladu['order_date']) ? (new DateTime($tn_zamowienie_podgladu['order_date']))->format($tn_format_daty_czasu) : '-'; } catch (Exception $e) {}
try { $data_aktualizacji = !empty($tn_zamowienie_podgladu['date_updated']) ? (new DateTime($tn_zamowienie_podgladu['date_updated']))->format($tn_format_daty_czasu) : $data_zamowienia; } catch (Exception $e) {}

$klient_nazwa = htmlspecialchars($tn_zamowienie_podgladu['buyer_name'] ?? '-');
$dane_wysylki = nl2br(htmlspecialchars($tn_zamowienie_podgladu['buyer_daneWysylki'] ?? '-'));

$produkt_id = $tn_zamowienie_podgladu['product_id'] ?? null;
$produkt = null; $nazwa_produktu = 'Nieznany Produkt'; $cena_jednostkowa = 0;
$obrazek_produktu = tn_pobierz_sciezke_obrazka(null);
if ($produkt_id && !empty($tn_produkty)) {
    foreach ($tn_produkty as $p) { if (($p['id'] ?? null) == $produkt_id) { $produkt = $p; break; } }
    if ($produkt) {
        $nazwa_produktu = htmlspecialchars($produkt['name'] ?? 'B/D');
        $cena_jednostkowa = $produkt['price'] ?? 0;
        $obrazek_produktu = tn_pobierz_sciezke_obrazka($produkt['image'] ?? null);
    } else { $nazwa_produktu = "<span class='text-danger'>Produkt (ID: {$produkt_id}) nie znaleziony!</span>"; }
}
$ilosc = $tn_zamowienie_podgladu['quantity'] ?? 0;
$wartosc_calkowita = $cena_jednostkowa * $ilosc;
$waluta = htmlspecialchars($tn_ustawienia_globalne['waluta'] ?? 'PLN');

// Dane kuriera i śledzenia
$kurier_id = $tn_zamowienie_podgladu['courier_id'] ?? $tn_zamowienie_podgladu['courier'] ?? null;
$kurier = null; $nazwa_kuriera = null;
$tn_mapa_kurierow_preview = tn_laduj_kurierow(TN_PLIK_KURIERZY); // Użyj tn_laduj_kurierow dla mapy asocjacyjnej
if ($kurier_id && isset($tn_mapa_kurierow_preview[$kurier_id])) {
     $kurier = $tn_mapa_kurierow_preview[$kurier_id];
     $nazwa_kuriera = htmlspecialchars($kurier['name'] ?? 'B/D');
}
$nr_sledzenia = !empty($tn_zamowienie_podgladu['tracking_number']) ? htmlspecialchars($tn_zamowienie_podgladu['tracking_number']) : null;
$url_sledzenia = null;
if ($nr_sledzenia && $kurier && !empty($kurier['tracking_url_pattern'])) {
    $pattern = $kurier['tracking_url_pattern'];
    // Uproszczone tworzenie linku śledzenia
    if (str_contains($pattern, '{tracking_number}')) {
        $url_sledzenia = str_replace('{tracking_number}', rawurlencode($nr_sledzenia), $pattern);
    } elseif (str_contains($pattern, '%s')) {
         // Alternatywny wzorzec dla str_replace %s
        $url_sledzenia = sprintf($pattern, rawurlencode($nr_sledzenia));
    }
     else {
        // Przyjmujemy, że numer śledzenia jest dodawany na końcu, z ewentualnym znakiem zapytania lub slash
        $url_sledzenia = rtrim($pattern, '=/?&') . rawurlencode($nr_sledzenia);
    }
    $url_sledzenia = htmlspecialchars($url_sledzenia);
}

// Mapa klas dla statusów
$tn_status_klasa_mapa = [ 'Nowe' => 'text-bg-primary', 'W przygotowaniu' => 'text-bg-warning', 'Zrealizowane' => 'text-bg-success', 'Anulowane' => 'text-bg-danger', 'default' => 'text-bg-secondary'];
$klasa_badge_statusu = $tn_status_klasa_mapa[$status_zam] ?? $tn_status_klasa_mapa['default'];
$tn_status_plat_klasa_mapa = ['Opłacone' => 'text-bg-success', 'Nieopłacone' => 'text-bg-danger', 'Nadpłata' => 'text-bg-warning text-dark', 'Zwrot częściowy' => 'text-bg-info', 'Zwrot całkowity' => 'text-bg-dark', 'default' => 'text-bg-light text-dark border'];
$klasa_badge_platnosci = $tn_status_plat_klasa_mapa[$status_platnosci] ?? $tn_status_plat_klasa_mapa['default'];

// URL do generowania etykiety
// Generujemy URL w PHP, ale przycisk będzie go używał w JS
$url_generuj_etykiete = tn_generuj_url('generate_label', ['id' => $order_id]);

?>

<div class="container-fluid tn-order-preview">
    <?php // Nagłówek strony ?>
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2 flex-wrap gap-2">
        <h1 class="h4 mb-0 fw-light text-break">
            <i class="bi bi-receipt-cutoff me-2"></i>Szczegóły Zamówienia #<?php echo $order_id; ?>
        </h1>
        <div class="d-flex gap-2 flex-shrink-0">
             <button type="button" class="btn btn-warning btn-sm" onclick='if(typeof tnApp !== "undefined" && tnApp.setupOrderModal) tnApp.setupOrderModal(<?php echo json_encode($tn_zamowienie_podgladu, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                 <i class="bi bi-pencil-square me-1"></i> Edytuj Zamówienie
             </button>
             <a href="<?php echo tn_generuj_url('orders'); ?>" class="btn btn-outline-secondary btn-sm">
                 <i class="bi bi-arrow-left me-1"></i> Wróć do listy
             </a>
        </div>
    </div>

    <div class="row g-4">
        <?php // Kolumna lewa: Dane zamówienia i produktu ?>
        <div class="col-lg-7">
            <?php // Karta: Szczegóły Zamówienia ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                     <h6 class="mb-0 fw-normal"><i class="bi bi-file-earmark-text me-2"></i>Dane Główne</h6>
                     <span class="badge <?php echo $klasa_badge_statusu; ?>"><?php echo $status_zam; ?></span>
                </div>
                 <div class="card-body">
                    <dl class="row small gy-2 mb-0">
                         <dt class="col-sm-4 text-muted">Numer Zamówienia:</dt> <dd class="col-sm-8 fw-bold">#<?php echo $order_id; ?></dd>
                         <dt class="col-sm-4 text-muted">Data Złożenia:</dt> <dd class="col-sm-8"><?php echo $data_zamowienia; ?></dd>
                         <dt class="col-sm-4 text-muted">Ostatnia Aktualizacja:</dt> <dd class="col-sm-8"><?php echo $data_aktualizacji; ?></dd>
                         <dt class="col-sm-4 text-muted">Status Realizacji:</dt> <dd class="col-sm-8"><span class="badge <?php echo $klasa_badge_statusu; ?>"><?php echo $status_zam; ?></span></dd>
                         <dt class="col-sm-4 text-muted">Status Płatności:</dt> <dd class="col-sm-8"><?php echo !empty($status_platnosci) ? '<span class="badge '.$klasa_badge_platnosci.'">'.$status_platnosci.'</span>' : '<span class="text-muted fst-italic">Brak</span>'; ?></dd>
                    </dl>
                 </div>
            </div>

             <?php // Karta: Zamówiony Produkt ?>
             <div class="card shadow-sm mb-4">
                <div class="card-header bg-light"><h6 class="mb-0 fw-normal"><i class="bi bi-box-seam me-2"></i>Zamówiony Produkt</h6></div>
                 <div class="card-body">
                     <?php if ($produkt): ?>
                         <div class="d-flex align-items-center">
                             <img src="<?php echo $obrazek_produktu; ?>" alt="<?php echo $nazwa_produktu; ?>" class="img-thumbnail me-3" style="width: 75px; height: 75px; object-fit: contain;">
                             <div>
                                 <h6 class="mb-1 fs-sm"><a href="<?php echo tn_generuj_url('product_preview', ['id' => $produkt_id]); ?>" class="text-decoration-none"><?php echo $nazwa_produktu; ?></a></h6>
                                 <p class="small text-muted mb-1">ID: <?php echo htmlspecialchars($produkt_id); ?></p>
                                 <p class="mb-0 small">Cena/szt.: <?php echo number_format($cena_jednostkowa, 2, ',', ' '); ?> <?php echo htmlspecialchars($waluta); ?></p>
                             </div>
                         </div>
                         <hr class="my-3">
                         <dl class="row small gy-1 mb-0">
                             <dt class="col-sm-4 text-muted">Zamówiona ilość:</dt>
                             <dd class="col-sm-8 fw-bold"><?php echo htmlspecialchars($ilosc); ?> szt.</dd>
                             <dt class="col-sm-4 text-muted">Wartość produktów:</dt>
                             <dd class="col-sm-8 fw-bold text-primary"><?php echo number_format($wartosc_calkowita, 2, ',', ' '); ?> <?php echo htmlspecialchars($waluta); ?></dd>
                         </dl>
                     <?php else: ?>
                         <p class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Błąd: Nie można załadować informacji o produkcie (ID: <?php echo htmlspecialchars($produkt_id ?? '?'); ?>).</p>
                     <?php endif; ?>
                 </div>
             </div>
        </div>

        <?php // Kolumna prawa: Dane klienta, Wysyłka ?>
        <div class="col-lg-5">
             <?php // Karta: Dane Klienta ?>
             <div class="card shadow-sm mb-4">
                 <div class="card-header bg-light"><h6 class="mb-0 fw-normal"><i class="bi bi-person-lines-fill me-2"></i>Dane Klienta</h6></div>
                 <div class="card-body">
                     <p class="mb-1 fw-bold"><?php echo $klient_nazwa; ?></p>
                     <?php // TODO: Dodać email/telefon jeśli są w $tn_zamowienie_podgladu ?>
                 </div>
             </div>

             <?php // Karta: Dane Wysyłki i Kurier ?>
             <div class="card shadow-sm mb-4">
                 <div class="card-header bg-light"><h6 class="mb-0 fw-normal"><i class="bi bi-truck me-2"></i>Wysyłka</h6></div>
                 <div class="card-body">
                    <p class="small mb-2"><strong>Adres dostawy:</strong></p>
                    <div class="mb-3 ps-2 small border-start border-2" style="line-height: 1.6;">
                        <?php echo $dane_wysylki ?: '<span class="text-muted fst-italic">Brak danych</span>'; ?>
                    </div>
                     <hr>
                    <dl class="row small gy-1 mb-0">
                         <dt class="col-sm-4 text-muted">Kurier:</dt>
                         <dd class="col-sm-8"><?php echo $nazwa_kuriera ?: '<span class="text-muted fst-italic">Nie wybrano</span>'; ?></dd>

                         <dt class="col-sm-4 text-muted">Nr przesyłki:</dt>
                         <dd class="col-sm-8">
                             <?php if($nr_sledzenia): ?>
                                 <span class="font-monospace"><?php echo $nr_sledzenia; ?></span>
                                 <?php // Link śledzenia ?>
                                 <?php if ($url_sledzenia): ?>
                                     <a href="<?php echo $url_sledzenia; ?>" target="_blank" class="ms-1 badge text-bg-info text-decoration-none" title="Śledź przesyłkę">
                                         <i class="bi bi-box-arrow-up-right me-1"></i> Śledź
                                     </a>
                                 <?php endif; ?>
                             <?php else: echo '<span class="text-muted fst-italic">Brak</span>'; endif; ?>
                         </dd>
                    </dl>
                     <div class="mt-3 text-center">
                         <?php // Przycisk "Generuj Etykietę" - Teraz wywoływany przez JS ?>
                         <?php if (!empty($dane_wysylki) && $dane_wysylki !== '-'): ?>
                             <button type="button" class="btn btn-outline-primary btn-sm tn-generate-label-btn" data-label-url="<?php echo htmlspecialchars($url_generuj_etykiete); ?>">
                                  <i class="bi bi-printer me-1"></i> Generuj Etykietę
                             </button>
                         <?php else: ?>
                             <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Etykieta wymaga danych adresowych.">
                                  <i class="bi bi-printer me-1"></i> Generuj Etykietę
                             </button>
                         <?php endif; ?>
                     </div>
                 </div>
             </div>

             <?php // TODO: Można dodać sekcję powiązanych zwrotów/reklamacji ?>

        </div>
    </div> <?php // Koniec .row ?>
</div>

