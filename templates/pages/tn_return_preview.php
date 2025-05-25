<?php
// templates/pages/tn_return_preview.php
/**
 * Widok szczegółów zgłoszenia zwrotu/reklamacji.
 * Wersja 1.1 (Poprawka: Lokalna definicja mapy statusów)
 *
 * Oczekuje zmiennych z index.php:
 * @var array|null $tn_zwrot_podgladu Dane zgłoszenia do wyświetlenia.
 * @var array $tn_produkty Tablica wszystkich produktów.
 * @var array $tn_zamowienia Tablica wszystkich zamówień.
 * @var array $tn_ustawienia_globalne Ustawienia globalne.
 * @var string $tn_token_csrf Token CSRF.
 */

// Sprawdź, czy dane zgłoszenia istnieją
if (empty($tn_zwrot_podgladu) || !isset($tn_zwrot_podgladu['id'])) {
    if(function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash('Nie znaleziono zgłoszenia do wyświetlenia.', 'warning');
    $redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('returns_list') : 'index.php?page=returns_list';
    echo "<script>window.location.href = '" . addslashes($redirect_url) . "';</script>"; // Przekierowanie JS
    exit;
}

// Upewnij się, że funkcje pomocnicze istnieją
if (!function_exists('tn_generuj_url')) { die('Błąd krytyczny: Brak funkcji tn_generuj_url()'); }
if (!function_exists('tn_get_avatar_path')) { die('Błąd krytyczny: Brak funkcji tn_get_avatar_path()'); } // Przykład

// Przygotuj dane do wyświetlenia
$tn_id_zgloszenia = htmlspecialchars($tn_zwrot_podgladu['id']);
$tn_typ_zgloszenia = htmlspecialchars($tn_zwrot_podgladu['type'] ?? '?');
$tn_status_zgloszenia = htmlspecialchars($tn_zwrot_podgladu['status'] ?? 'Nieznany'); // Użyj 'Nieznany' jako fallback
$tn_data_utworzenia = '-'; $tn_data_aktualizacji = '-';
$tn_format_daty_czasu = ($tn_ustawienia_globalne['tn_format_daty'] ?? 'd.m.Y') . ' ' . ($tn_ustawienia_globalne['tn_format_czasu'] ?? 'H:i');
try { $tn_data_utworzenia = !empty($tn_zwrot_podgladu['date_created']) ? (new DateTime($tn_zwrot_podgladu['date_created']))->format($tn_format_daty_czasu) : '-'; } catch (Exception $e) {}
try { $tn_data_aktualizacji = !empty($tn_zwrot_podgladu['date_updated']) ? (new DateTime($tn_zwrot_podgladu['date_updated']))->format($tn_format_daty_czasu) : '-'; } catch (Exception $e) {}

$tn_id_zamowienia = $tn_zwrot_podgladu['order_id'] ?? null;
$tn_id_produktu = $tn_zwrot_podgladu['product_id'] ?? null;
$tn_ilosc = htmlspecialchars($tn_zwrot_podgladu['quantity'] ?? '?');
$tn_klient_nazwa = htmlspecialchars($tn_zwrot_podgladu['customer_name'] ?? '-');
$tn_klient_kontakt = htmlspecialchars($tn_zwrot_podgladu['customer_contact'] ?? '-');
$tn_powod = nl2br(htmlspecialchars($tn_zwrot_podgladu['reason'] ?? '-'));
$tn_notatki = nl2br(htmlspecialchars($tn_zwrot_podgladu['notes'] ?? '-'));
$tn_rozwiazanie = nl2br(htmlspecialchars($tn_zwrot_podgladu['resolution'] ?? '-'));
$tn_czy_stan_dodany = $tn_zwrot_podgladu['returned_stock_added'] ?? false;

// Znajdź nazwę produktu
$tn_nazwa_produktu = 'Nieznany produkt';
if ($tn_id_produktu && !empty($tn_produkty)) {
    foreach($tn_produkty as $p) { if (($p['id'] ?? null) == $tn_id_produktu) { $tn_nazwa_produktu = htmlspecialchars($p['name'] ?? 'B/D'); break; } }
}
// Wygeneruj linki
$tn_link_do_zamowienia = $tn_id_zamowienia ? tn_generuj_url('order_preview', ['id' => $tn_id_zamowienia]) : '#';
$tn_link_do_produktu = $tn_id_produktu ? tn_generuj_url('product_preview', ['id' => $tn_id_produktu]) : '#';
$tn_link_do_edycji = tn_generuj_url('return_form_edit', ['id' => $tn_id_zgloszenia]);

// *** POPRAWKA: Lokalna definicja mapy statusów z kluczem 'default' ***
$tn_status_mapa_klas = [
    'Nowe zgłoszenie'          => 'text-bg-primary',
    'W trakcie rozpatrywania'  => 'text-bg-info',
    'Oczekuje na zwrot towaru' => 'text-bg-warning',
    'Zaakceptowana'            => 'text-bg-success',
    'Odrzucona'                => 'text-bg-danger',
    'Zakończona'               => 'text-bg-secondary',
    'default'                  => 'text-bg-light text-dark border' // Domyślna klasa
];
// Użycie poprawionej mapy
$klasa_badge_statusu = $tn_status_mapa_klas[$tn_zwrot_podgladu['status'] ?? 'default'] ?? $tn_status_mapa_klas['default']; // Podwójny fallback dla pewności

?>
<div class="container-fluid tn-return-preview">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2 flex-wrap gap-2"> <?php // Dodano flex-wrap i gap ?>
         <h1 class="h4 mb-0 fw-light text-break"> <?php // Dodano text-break ?>
            <i class="bi bi-clipboard-check me-2"></i>Szczegóły Zgłoszenia #<?php echo $tn_id_zgloszenia; ?>
            (<?php echo $tn_typ_zgloszenia === 'zwrot' ? 'Zwrot' : 'Reklamacja'; ?>)
         </h1>
         <div class="d-flex gap-2 flex-shrink-0"> <?php // Grupowanie przycisków ?>
             <a href="<?php echo $tn_link_do_edycji; ?>" class="btn btn-warning btn-sm">
                 <i class="bi bi-pencil-square me-1"></i> Edytuj
             </a>
             <a href="<?php echo tn_generuj_url('returns_list'); ?>" class="btn btn-outline-secondary btn-sm">
                 <i class="bi bi-arrow-left me-1"></i> Wróć do listy
             </a>
        </div>
    </div>

    <div class="row g-4">
        <?php // Kolumna lewa: Dane zgłoszenia, klient, powód ?>
        <div class="col-lg-7">
             <div class="card shadow-sm mb-4">
                 <div class="card-header d-flex justify-content-between align-items-center bg-light">
                     <h6 class="mb-0 fw-normal"><i class="bi bi-file-earmark-text me-2"></i>Dane Zgłoszenia</h6>
                     <span class="badge <?php echo $klasa_badge_statusu; ?>"><?php echo $tn_status_zgloszenia; ?></span>
                 </div>
                 <div class="card-body">
                     <dl class="row small gy-2 mb-0">
                         <dt class="col-sm-4 text-muted">Numer Zgłoszenia:</dt> <dd class="col-sm-8 fw-bold">#<?php echo $tn_id_zgloszenia; ?></dd>
                         <dt class="col-sm-4 text-muted">Typ:</dt> <dd class="col-sm-8"><?php echo $tn_typ_zgloszenia === 'zwrot' ? 'Zwrot' : 'Reklamacja'; ?></dd>
                         <dt class="col-sm-4 text-muted">Status:</dt> <dd class="col-sm-8"><span class="badge <?php echo $klasa_badge_statusu; ?>"><?php echo $tn_status_zgloszenia; ?></span></dd>
                         <dt class="col-sm-4 text-muted">Data Utworzenia:</dt> <dd class="col-sm-8"><?php echo $tn_data_utworzenia; ?></dd>
                         <dt class="col-sm-4 text-muted">Ostatnia Aktualizacja:</dt> <dd class="col-sm-8"><?php echo $tn_data_aktualizacji; ?></dd>
                         <dt class="col-sm-4 text-muted">Powiązane Zamówienie:</dt> <dd class="col-sm-8"><?php if($tn_id_zamowienia): ?><a href="<?php echo $tn_link_do_zamowienia; ?>">#<?php echo htmlspecialchars($tn_id_zamowienia); ?></a><?php else: echo '?'; endif; ?></dd>
                     </dl>
                 </div>
             </div>

            <div class="card shadow-sm mb-4">
                 <div class="card-header bg-light"><h6 class="mb-0 fw-normal"><i class="bi bi-person me-2"></i>Dane Klienta</h6></div>
                 <div class="card-body">
                     <dl class="row small gy-2 mb-0">
                         <dt class="col-sm-4 text-muted">Imię i Nazwisko:</dt> <dd class="col-sm-8"><?php echo $tn_klient_nazwa; ?></dd>
                         <dt class="col-sm-4 text-muted">Kontakt:</dt> <dd class="col-sm-8"><?php echo $tn_klient_kontakt ?: '-'; ?></dd>
                     </dl>
                 </div>
            </div>

            <div class="card shadow-sm mb-4">
                 <div class="card-header bg-light"><h6 class="mb-0 fw-normal"><i class="bi bi-chat-left-text me-2"></i>Powód Zgłoszenia</h6></div>
                 <div class="card-body small" style="min-height: 80px;"> <?php // Zmniejszono min-height ?>
                     <?php echo $tn_powod ?: '<p class="text-muted fst-italic">Brak opisu powodu.</p>'; ?>
                 </div>
            </div>

        </div>

         <?php // Kolumna prawa: Produkt, Rozwiązanie, Notatki ?>
        <div class="col-lg-5">
             <div class="card shadow-sm mb-4">
                <div class="card-header bg-light"><h6 class="mb-0 fw-normal"><i class="bi bi-box-seam me-2"></i>Zgłaszany Produkt</h6></div>
                <div class="card-body">
                     <?php if ($tn_id_produktu): ?>
                        <h6 class="mb-1 fs-sm">
                            <a href="<?php echo $tn_link_do_produktu; ?>" class="text-decoration-none" title="Zobacz produkt: <?php echo htmlspecialchars($tn_nazwa_produktu); ?>">
                                <?php echo $tn_nazwa_produktu; ?>
                            </a>
                         </h6>
                         <p class="small text-muted mb-2">ID: <?php echo htmlspecialchars($tn_id_produktu); ?></p>
                         <p class="mb-0">Zgłaszana ilość: <strong class="text-primary"><?php echo $tn_ilosc; ?> szt.</strong></p>
                     <?php else: ?>
                        <p class="text-danger fst-italic small">Brak powiązanego produktu.</p>
                     <?php endif; ?>
                </div>
             </div>

             <div class="card shadow-sm mb-4">
                 <div class="card-header bg-light"><h6 class="mb-0 fw-normal"><i class="bi bi-check2-square me-2"></i>Rozwiązanie / Decyzja</h6></div>
                 <div class="card-body small" style="min-height: 70px;">
                     <?php echo $tn_rozwiazanie ?: '<p class="text-muted fst-italic">Brak opisu rozwiązania.</p>'; ?>
                      <?php if($tn_czy_stan_dodany): ?>
                          <p class="mt-2 mb-0 text-success small"><i class="bi bi-box-arrow-in-down me-1"></i> Towar został oznaczony jako dodany z powrotem na stan.</p>
                      <?php endif; ?>
                 </div>
            </div>

            <div class="card shadow-sm mb-4">
                 <div class="card-header bg-light"><h6 class="mb-0 fw-normal"><i class="bi bi-journal-text me-2"></i>Notatki Wewnętrzne</h6></div>
                 <div class="card-body small" style="min-height: 80px;">
                      <?php echo $tn_notatki ?: '<p class="text-muted fst-italic">Brak notatek.</p>'; ?>
                 </div>
            </div>

        </div>
    </div> <?php // Koniec .row ?>
</div>