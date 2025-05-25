<?php
// templates/pages/tn_return_form.php
/**
 * Formularz dodawania/edycji zgłoszenia zwrotu/reklamacji.
 * Wersja: 1.1 (Poprawka: Użycie zmiennej $tn_edytowany_zwrot)
 *
 * Oczekuje zmiennych z index.php:
 * @var array|null $tn_edytowany_zwrot Dane edytowanego zgłoszenia (null jeśli dodawanie nowego). <<-- POPRAWIONA NAZWA ZMIENNEJ
 * @var array $tn_ustawienia_globalne Ustawienia globalne.
 * @var array $tn_produkty Tablica wszystkich produktów (dla selecta).
 * @var array $tn_zamowienia Tablica wszystkich zamówień (dla selecta).
 * @var string $tn_token_csrf Token CSRF.
 */

// Sprawdź, czy edytujemy, czy dodajemy
// Użyj poprawnej zmiennej $tn_edytowany_zwrot
$tn_edycja = ($tn_edytowany_zwrot !== null && isset($tn_edytowany_zwrot['id']));
$tn_page_title = $tn_edycja ? 'Edytuj Zgłoszenie #' . htmlspecialchars($tn_edytowany_zwrot['id']) : 'Nowe Zgłoszenie Zwrotu/Reklamacji';
$tn_form_action = 'save_return';

// Pobierz dostępne statusy z globalnego zasięgu lub ustawień
$tn_dostepne_statusy = $GLOBALS['tn_prawidlowe_statusy_zwrotow'] ?? ($tn_ustawienia_globalne['zwroty_reklamacje']['statusy'] ?? []);

// Przygotuj wartości - użyj $tn_edytowany_zwrot zamiast $tn_zgloszenie
$tn_id = $tn_edytowany_zwrot['id'] ?? null;
$tn_type = $tn_edytowany_zwrot['type'] ?? 'reklamacja';
$tn_status = $tn_edytowany_zwrot['status'] ?? ($tn_ustawienia_globalne['zwroty_reklamacje']['domyslny_status'] ?? 'Nowe zgłoszenie');
$tn_order_id = $tn_edytowany_zwrot['order_id'] ?? null;
$tn_product_id = $tn_edytowany_zwrot['product_id'] ?? null;
$tn_quantity = $tn_edytowany_zwrot['quantity'] ?? 1;
$tn_customer_name = $tn_edytowany_zwrot['customer_name'] ?? '';
$tn_customer_contact = $tn_edytowany_zwrot['customer_contact'] ?? '';
$tn_reason = $tn_edytowany_zwrot['reason'] ?? '';
$tn_notes = $tn_edytowany_zwrot['notes'] ?? '';
$tn_resolution = $tn_edytowany_zwrot['resolution'] ?? '';
$tn_stock_added = $tn_edytowany_zwrot['returned_stock_added'] ?? false;

// Przygotuj opcje dla selectów Zamówień i Produktów (bez zmian)
$tn_opcje_zamowien = [];
if (!empty($tn_zamowienia)) {
    usort($tn_zamowienia, fn($a, $b) => ($b['order_date'] ?? 0) <=> ($a['order_date'] ?? 0));
    foreach ($tn_zamowienia as $zam) { $tn_opcje_zamowien[$zam['id']] = "#" . $zam['id'] . " - " . ($zam['buyer_name'] ?? '?') . " (" . date('d.m.Y', strtotime($zam['order_date'] ?? 'now')) . ")"; }
}
$tn_opcje_produktow = [];
if (!empty($tn_produkty)) {
     usort($tn_produkty, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));
    foreach ($tn_produkty as $prod) { $tn_opcje_produktow[$prod['id']] = ($prod['name'] ?? 'ID:'.$prod['id']) . " (ID: {$prod['id']})"; }
}

// Funkcja pomocnicza selected (jeśli nie jest globalna)
if (!function_exists('selected')) { function selected($a, $b) { if ((string)$a === (string)$b) echo ' selected'; } }
if (!function_exists('checked')) { function checked($a) { if ($a) echo ' checked'; } }
?>

<h1 class="h4 mb-4"><i class="bi bi-pencil-square me-2"></i><?php echo $tn_page_title; ?></h1>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action=""> <?php // Action="" wysyła do index.php ?>
            <input type="hidden" name="action" value="<?php echo $tn_form_action; ?>">
            <input type="hidden" name="tn_csrf_token" value="<?php echo $tn_token_csrf; ?>">
            <?php if ($tn_edycja): ?>
                <input type="hidden" name="return_id" value="<?php echo htmlspecialchars($tn_id); ?>">
            <?php endif; ?>

            <div class="row g-3">
                <?php // --- Podstawowe informacje o zgłoszeniu --- ?>
                <fieldset class="col-12 border p-3 rounded mb-3">
                    <legend class="h6 fs-sm fw-bold mb-3">Informacje o Zgłoszeniu</legend>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="tn_return_type" class="form-label small mb-1">Typ zgłoszenia <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="tn_return_type" name="type" required>
                                <option value="reklamacja" <?php selected($tn_type, 'reklamacja'); ?>>Reklamacja</option>
                                <option value="zwrot" <?php selected($tn_type, 'zwrot'); ?>>Zwrot</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                             <label for="tn_return_status" class="form-label small mb-1">Status <span class="text-danger">*</span></label>
                             <select class="form-select form-select-sm" id="tn_return_status" name="status" required>
                                <?php foreach ($tn_dostepne_statusy as $status_opt): ?>
                                    <option value="<?php echo htmlspecialchars($status_opt); ?>" <?php selected($tn_status, $status_opt); ?>>
                                        <?php echo htmlspecialchars($status_opt); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="col-md-4">
                            <label for="tn_return_order_id" class="form-label small mb-1">Powiązane Zamówienie <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="tn_return_order_id" name="order_id" required>
                                <option value="">-- Wybierz zamówienie --</option>
                                <?php foreach ($tn_opcje_zamowien as $id => $label): ?>
                                <option value="<?php echo $id; ?>" <?php selected($tn_order_id, $id); ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <?php // --- Informacje o produkcie --- ?>
                 <fieldset class="col-12 border p-3 rounded mb-3">
                    <legend class="h6 fs-sm fw-bold mb-3">Zgłaszany Produkt</legend>
                     <div class="row g-3">
                         <div class="col-md-8">
                            <label for="tn_return_product_id" class="form-label small mb-1">Produkt <span class="text-danger">*</span></label>
                             <select class="form-select form-select-sm" id="tn_return_product_id" name="product_id" required>
                                <option value="">-- Wybierz produkt --</option>
                                 <?php foreach ($tn_opcje_produktow as $id => $label): ?>
                                <option value="<?php echo $id; ?>" <?php selected($tn_product_id, $id); ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                             <div class="form-text small">Wybierz produkt, którego dotyczy zgłoszenie.</div>
                         </div>
                         <div class="col-md-4">
                             <label for="tn_return_quantity" class="form-label small mb-1">Ilość <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="tn_return_quantity" name="quantity" value="<?php echo htmlspecialchars($tn_quantity); ?>" min="1" required>
                         </div>
                     </div>
                 </fieldset>

                <?php // --- Dane Klienta i Powód --- ?>
                 <fieldset class="col-12 border p-3 rounded mb-3">
                    <legend class="h6 fs-sm fw-bold mb-3">Dane Klienta i Powód Zgłoszenia</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="tn_return_customer_name" class="form-label small mb-1">Imię i Nazwisko Klienta <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="tn_return_customer_name" name="customer_name" value="<?php echo htmlspecialchars($tn_customer_name); ?>" required>
                             <div class="form-text small">Można pobrać z zamówienia.</div>
                        </div>
                        <div class="col-md-6">
                             <label for="tn_return_customer_contact" class="form-label small mb-1">Kontakt Klienta (e-mail/tel)</label>
                            <input type="text" class="form-control form-control-sm" id="tn_return_customer_contact" name="customer_contact" value="<?php echo htmlspecialchars($tn_customer_contact); ?>">
                        </div>
                        <div class="col-12">
                            <label for="tn_return_reason" class="form-label small mb-1">Powód zgłoszenia <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-sm" id="tn_return_reason" name="reason" rows="3" required><?php echo htmlspecialchars($tn_reason); ?></textarea>
                        </div>
                    </div>
                </fieldset>

                <?php // --- Rozwiązanie i Notatki --- ?>
                 <fieldset class="col-12 border p-3 rounded mb-3">
                    <legend class="h6 fs-sm fw-bold mb-3">Rozwiązanie i Notatki Wewnętrzne</legend>
                    <div class="mb-3">
                         <label for="tn_return_resolution" class="form-label small mb-1">Opis rozwiązania</label>
                         <textarea class="form-control form-control-sm" id="tn_return_resolution" name="resolution" rows="2"><?php echo htmlspecialchars($tn_resolution); ?></textarea>
                         <div class="form-text small">Np. "Zwrot środków", "Wymiana", "Odrzucono...".</div>
                    </div>
                     <div class="mb-3">
                         <label for="tn_return_notes" class="form-label small mb-1">Notatki wewnętrzne</label>
                         <textarea class="form-control form-control-sm" id="tn_return_notes" name="notes" rows="3"><?php echo htmlspecialchars($tn_notes); ?></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="tn_return_stock_added" name="returned_stock_added" <?php checked($tn_stock_added); ?>>
                        <label class="form-check-label small" for="tn_return_stock_added">
                            Zwrócony towar dodano z powrotem na stan magazynowy?
                        </label>
                        <div class="form-text small mt-0">Zaznacz, jeśli towar wrócił i nadaje się do sprzedaży.</div>
                    </div>
                 </fieldset>

            </div> <?php // Koniec .row g-3 ?>

             <hr class="my-4">
             <div class="text-end">
                 <a href="<?php echo tn_generuj_url('returns_list'); ?>" class="btn btn-secondary"><i class="bi bi-x-lg me-1"></i>Anuluj</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i><?php echo $tn_edycja ? 'Zapisz zmiany' : 'Utwórz zgłoszenie'; ?>
                </button>
            </div>

        </form>
    </div> <?php // Koniec .card-body ?>
</div> <?php // Koniec .card ?>