<?php
// templates/partials/tn_modals.php
/**
 * Definicje okien modalnych Bootstrap używanych w aplikacji TN iMAG.
 * Wersja: 1.7.1 (Poprawki działania modala kurierów, ulepszenia UX)
 *
 * Oczekuje zmiennych z index.php lub głównego szablonu:
 * @var array $tn_ustawienia_globalne
 * @var string $tn_token_csrf
 * @var array $tn_produkty
 * @var array $tn_kurierzy         (załadowana jako asocjacyjna, klucz=ID)
 * @var array $tn_stan_magazynu
 * @var array $tn_regaly
 * // Dostęp do stałych lub zmiennych globalnych ustawionych w config.php
 * @var array $GLOBALS['tn_prawidlowe_statusy']
 * @var array $GLOBALS['tn_prawidlowe_statusy_platnosci']
 * @var array $GLOBALS['tn_prawidlowe_statusy_zwrotow']
 */

// --- Przygotowanie Danych dla Selectów w Modalach ---

// Kategorie produktów
$tn_kategorie_produktow = $tn_ustawienia_globalne['kategorie_produktow'] ?? [];
$tn_waluta = $tn_ustawienia_globalne['waluta'] ?? 'PLN'; // Zmienna waluty

// Wolne lokalizacje
$tn_wolne_lokalizacje = [];
if (!empty($tn_stan_magazynu)) {
    foreach ($tn_stan_magazynu as $loc) {
        if (($loc['status'] ?? '') === 'empty' && isset($loc['id'])) {
            $tn_wolne_lokalizacje[] = $loc['id'];
        }
    }
    natsort($tn_wolne_lokalizacje); // Sortowanie naturalne
}
$tn_pierwsza_wolna_lokalizacja = !empty($tn_wolne_lokalizacje) ? reset($tn_wolne_lokalizacje) : null; // Nie używane bezpośrednio w tym kodzie, ale pozostawione

// Dostępne statusy (z globalnych stałych lub zmiennych)
$tn_dostepne_statusy_zam = defined('TN_STATUSY_ZAMOWIEN') ? TN_STATUSY_ZAMOWIEN : ($GLOBALS['tn_prawidlowe_statusy'] ?? []);
$tn_dostepne_statusy_platnosci = defined('TN_STATUSY_PLATNOSCI') ? TN_STATUSY_PLATNOSCI : ($GLOBALS['tn_prawidlowe_statusy_platnosci'] ?? []);
$tn_dostepne_statusy_zwrotow = defined('TN_STATUSY_ZWROTOW') ? TN_STATUSY_ZWROTOW : ($GLOBALS['tn_prawidlowe_statusy_zwrotow'] ?? []);

// Aktywni kurierzy (ładowani jako asocjacyjna tablica)
$tn_aktywni_kurierzy_do_selecta = [];
if (!empty($tn_kurierzy)) {
    foreach ($tn_kurierzy as $courier_id => $kurier) {
        if (isset($kurier['is_active']) && $kurier['is_active'] === true) {
            $tn_aktywni_kurierzy_do_selecta[htmlspecialchars($courier_id)] = htmlspecialchars($kurier['name'] ?? 'ID: ' . htmlspecialchars($courier_id));
        }
    }
    asort($tn_aktywni_kurierzy_do_selecta); // Sortuj wg nazw
}

// Mapa regałów (dla modala generowania lokalizacji)
$tn_mapa_id_regalow_modal = [];
if (!empty($tn_regaly)) {
    $temp_regaly = $tn_regaly;
    usort($temp_regaly, fn ($a, $b) => strnatcmp($a['tn_id_regalu'] ?? '', $b['tn_id_regalu'] ?? ''));
    foreach ($temp_regaly as $r) {
        if (isset($r['tn_id_regalu'])) {
            $opis = !empty($r['tn_opis_regalu']) ? ' (' . htmlspecialchars($r['tn_opis_regalu']) . ')' : '';
            $tn_mapa_id_regalow_modal[htmlspecialchars($r['tn_id_regalu'])] = htmlspecialchars($r['tn_id_regalu']) . $opis;
        }
    }
}

// Produkty posortowane alfabetycznie (dla selectów)
$tn_produkty_posortowane = $tn_produkty ?? [];
if (!empty($tn_produkty_posortowane)) {
    usort($tn_produkty_posortowane, fn ($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));
}

// --- Funkcje Pomocnicze (Lokalne, dla pewności, z sanitizacją) ---
if (!function_exists('selected')) {
    function selected($a, $b)
    {
        if ((string) $a === (string) $b) echo ' selected';
    }
}
if (!function_exists('checked')) {
    function checked($a)
    {
        if (!!$a) echo ' checked';
    }
}
if (!function_exists('tn_generuj_url')) {
    function tn_generuj_url(string $id, array $p = [])
    {
        return '?page=' . urlencode($id) . (!empty($p) ? '&' . http_build_query($p) : '');
    }
} // Prosty fallback

?>

<?php // ============================================================ ?>
<?php // --- Modal Dodawania/Edycji Produktu (#productModal) --- ?>
<?php // ============================================================ ?>
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST" id="productForm" enctype="multipart/form-data" action="" class="needs-validation">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="tn_csrf_token" value="<?php echo htmlspecialchars($tn_token_csrf ?? ''); ?>">
            <input type="hidden" name="id" id="productId">
            <input type="hidden" name="original_warehouse" id="originalWarehouseValue">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary-to-secondary text-white py-3">
                    <h6 class="modal-title fs-5" id="productModalLabel"><i class="bi bi-box-seam me-2"></i> Formularz Wystawiania</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="productName" class="form-label small mb-1">Nazwa <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" id="productName" required minlength="2" maxlength="255">
                            <div class="invalid-feedback">Nazwa musi mieć od 2 do 255 znaków.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="productProducent" class="form-label small mb-1">Producent części <span class="text-danger">*</span></label>
                            <input type="text" name="producent" class="form-control form-control-sm" id="productProducent" required minlength="2" maxlength="100">
                            <div class="invalid-feedback">Producent musi mieć od 2 do 100 znaków.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="productCatalogNr" class="form-label small mb-1">Numer katalogowy części</label>
                            <input type="text" name="tn_numer_katalogowy" class="form-control form-control-sm" id="productCatalogNr" maxlength="50">
                            <div class="form-text small mt-1">np.: A 000 141 91 25 lub A0001419125</div>
                        </div>
                        <div class="col-md-6">
                            <label for="productCategorySelect" class="form-label small mb-1">Kategoria</label>
                            <select name="category" class="form-select form-select-sm" id="productCategorySelect">
                                <option value="">-- Wybierz --</option>
                                <?php if (!empty($tn_kategorie_produktow)) : foreach ($tn_kategorie_produktow as $tn_kat) : ?>
                                        <option value="<?php echo htmlspecialchars($tn_kat); ?>"><?php echo htmlspecialchars($tn_kat); ?></option>
                                <?php endforeach;
                                endif; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="productDesc" class="form-label small mb-1">Opis</label>
                            <textarea name="desc" class="form-control form-control-sm" id="productDesc" rows="3"></textarea>
                            <div class="form-text small mt-1">Dozwolone znaczniki HTML.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="productSpec" class="form-label small mb-1">Specyfikacja</label>
                            <textarea name="spec" class="form-control form-control-sm" id="productSpec" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="productParams" class="form-label small mb-1">Parametry</label>
                            <textarea name="params" class="form-control form-control-sm" id="productParams" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label for="productVehicle" class="form-label small mb-1">Pasuje do pojazdów</label>
                            <textarea name="vehicle" class="form-control form-control-sm" id="productVehicle" rows="2" placeholder="np. Mercedes-Benz, 124 Coupe: 300CE (124.920),2996,135,188,1989.01-1993.04"></textarea>
                            <div class="form-text small mt-1">np.: Mercedes-Benz, 124 Coupe: 300CE (124.920),2996,135,188,1989.01-1993.04 <code>(Marka, Model: Typ, Kod, ccm3, KW, KM, Rok od-do) </code></div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="productPrice" class="form-label small mb-1">Cena (<?php echo htmlspecialchars($tn_waluta); ?>) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="price" class="form-control form-control-sm" id="productPrice" step="0.01" min="0" required value="0.00">
                                <span class="input-group-text"><?php echo htmlspecialchars($tn_waluta); ?></span>
                            </div>
                            <div class="invalid-feedback">Podaj poprawną cenę.</div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="productShipping" class="form-label small mb-1">Koszt wysyłki (<?php echo htmlspecialchars($tn_waluta); ?>) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="shipping" class="form-control form-control-sm" id="productShipping" step="0.01" min="0" required value="0.00">
                                <span class="input-group-text"><?php echo htmlspecialchars($tn_waluta); ?></span>
                            </div>
                            <div class="invalid-feedback">Podaj poprawny koszt wysyłki.</div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="productStock" class="form-label small mb-1">Ilość <span class="text-danger">*</span></label>
                            <input type="number" name="stock" class="form-control form-control-sm" id="productStock" min="0" required value="0">
                            <div class="invalid-feedback">Podaj poprawną ilość.</div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="productUnit" class="form-label small mb-1">Jednostka</label>
                            <input type="text" name="tn_jednostka_miary" class="form-control form-control-sm" id="productUnit" placeholder="szt., kpl, l" maxlength="20">
                        </div>
                        <div class="col-md-6">
                            <label for="productLocationSelect" class="form-label small mb-1">Przypisz lokalizację</label>
                            <select name="tn_assign_location_id" id="productLocationSelect" class="form-select form-select-sm" aria-describedby="productLocationHelp">
                                <option value="">-- Nie przypisuj --</option>
                                <?php if (!empty($tn_wolne_lokalizacje)) : foreach ($tn_wolne_lokalizacje as $tn_loc_id) : ?>
                                        <option value="<?php echo htmlspecialchars($tn_loc_id); ?>"><?php echo htmlspecialchars($tn_loc_id); ?></option>
                                <?php endforeach;
                                endif; ?>
                            </select>
                            <div class="form-text small mt-1" id="productLocationHelp">Wybierz wolne miejsce. Przy edycji zmiana w Widoku Magazynu.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="productImageFile" class="form-label small mb-1">Zdjęcie (plik)</label>
                            <input type="file" name="image_file" class="form-control form-control-sm" id="productImageFile" accept="image/jpeg,image/png,image/gif,image/webp" aria-describedby="imageUploadHelp">
                            <div class="form-text small mt-1" id="imageUploadHelp">Max 2MB. JPG, PNG, GIF, WEBP. Zastąpi obecne.</div>
                            <div id="imagePreviewContainer" class="mt-2" style="display: none;">
                                <img id="imagePreview" src="#" alt="Podgląd" class="border rounded p-1 bg-light" style="max-width: 100px; max-height: 100px; object-fit: contain;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Anuluj</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i> Zapisz i zakończ</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php // ============================================================ ?>
<?php // --- Modal Importu Produktów (#importModal) --- ?>
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data" action="" class="needs-validation">
            <input type="hidden" name="action" value="import_products">
            <input type="hidden" name="tn_csrf_token" value="<?php echo htmlspecialchars($tn_token_csrf ?? ''); ?>">
            <div class="modal-content">
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fs-5" id="importModalLabel"><i class="bi bi-upload me-2"></i> Importuj Produkty z JSON</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="importFile" class="form-label small mb-1">Wybierz plik JSON <span class="text-danger">*</span></label>
                        <input type="file" name="import_file" class="form-control form-control-sm" id="importFile" accept="application/json,.json" required aria-describedby="importFileHelp">
                        <div class="form-text small mt-1" id="importFileHelp">
                            Plik musi zawierać tablicę obiektów JSON. Wymagane klucze: <code>name</code>, <code>price</code>.<br>
                            Opcjonalne: <code>id</code>, <code>producent</code>, <code>tn_numer_katalogowy</code>, <code>category</code>, <code>desc</code>, <code>spec</code>, <code>params</code>, <code>vehicle</code>, <code>shipping</code>, <code>stock</code>, <code>tn_jednostka_miary</code>, <code>warehouse</code>, <code>image</code> (URL). Max 5MB.
                        </div>
                        <div class="invalid-feedback">Wybierz poprawny plik JSON.</div>
                    </div>
                    <div class="alert alert-warning small p-2" role="alert">
                        <i class="bi bi-exclamation-triangle me-1"></i> Istniejące produkty z pasującym ID zostaną **zaktualizowane**. Brak lub nowe ID spowoduje **dodanie** produktu.
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-upload me-2"></i> Importuj</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php // ============================================================ ?>
<?php // --- Modal Dodawania/Edycji Zamówienia (#orderModal) --- ?>
<?php // ============================================================ ?>
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST" id="orderForm" action="" class="needs-validation">
            <input type="hidden" name="action" value="save_order">
            <input type="hidden" name="tn_csrf_token" value="<?php echo htmlspecialchars($tn_token_csrf ?? ''); ?>">
            <input type="hidden" name="order_id" id="edit_order_id">
            <input type="hidden" name="current_status_filter" value="<?php echo htmlspecialchars($_GET['status'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <div class="modal-content">
                <div class="modal-header bg-info text-dark py-3">
                    <h5 class="modal-title fs-5" id="orderModalLabel"><i class="bi bi-receipt me-2"></i> Formularz Zamówienia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="order_product_id" class="form-label small mb-1">Produkt <span class="text-danger">*</span></label>
                            <select name="product_id" class="form-select form-select-sm" id="order_product_id" required aria-describedby="orderProductHelp">
                                <option value="">-- Wybierz produkt --</option>
                                <?php foreach ($tn_produkty_posortowane as $p) :
                                    $stock = intval($p['stock'] ?? 0);
                                    $dis = $stock <= 0 ? ' disabled' : '';
                                    $stxt = $stock <= 0 ? ' - BRAK' : " (Stan: {$stock})";
                                    ?>
                                    <option value="<?php echo htmlspecialchars($p['id']); ?>" data-stock="<?php echo $stock; ?>" <?php echo $dis; ?>>
                                        <?php echo htmlspecialchars($p['name'] ?? 'B/N') . $stxt; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="orderProductHelp" class="form-text small mt-1">Wybierz produkt z dostępnego stanu magazynowego.</div>
                            <div class="invalid-feedback">Wybierz produkt.</div>
                        </div>
                        <div class="col-md-5">
                            <label for="order_quantity" class="form-label small mb-1">Ilość <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="order_quantity" class="form-control form-control-sm" required min="1" value="1" aria-describedby="quantity_warning">
                            <div id="quantity_warning" class="form-text text-danger small mt-1" style="display: none;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Ilość przekracza dostępny stan magazynowy!
                            </div>
                            <div class="invalid-feedback">Podaj poprawną ilość.</div>
                        </div>
                        <div class="col-12">
                            <label for="order_buyer_name" class="form-label small mb-1">Nazwa Klienta <span class="text-danger">*</span></label>
                            <input type="text" name="buyer_name" id="order_buyer_name" class="form-control form-control-sm" required minlength="2" maxlength="255">
                            <div class="invalid-feedback">Nazwa klienta musi mieć od 2 do 255 znaków.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="order_status" class="form-label small mb-1">Status realizacji <span class="text-danger">*</span></label>
                            <select name="status" id="order_status" class="form-select form-select-sm" required>
                                <?php if (!empty($tn_dostepne_statusy_zam)) : foreach ($tn_dostepne_statusy_zam as $s) : ?>
                                        <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
                                <?php endforeach;
                                endif; ?>
                            </select>
                            <div class="invalid-feedback">Wybierz status zamówienia.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="order_payment_status" class="form-label small mb-1">Status płatności</label>
                            <select name="tn_status_platnosci" id="order_payment_status" class="form-select form-select-sm">
                                <option value="">-- Brak informacji --</option>
                                <?php if (!empty($tn_dostepne_statusy_platnosci)) : foreach ($tn_dostepne_statusy_platnosci as $sp) : ?>
                                        <option value="<?php echo htmlspecialchars($sp); ?>"><?php echo htmlspecialchars($sp); ?></option>
                                <?php endforeach;
                                endif; ?>
                            </select>
                        </div>
                        <hr class="my-3">
                        <div class="col-md-6">
                            <label for="order_courier_id" class="form-label small mb-1">Kurier</label>
                            <select name="courier_id" id="order_courier_id" class="form-select form-select-sm">
                                <option value="">-- Wybierz (opcjonalnie) --</option>
                                <?php foreach ($tn_aktywni_kurierzy_do_selecta as $id => $name) : ?>
                                        <option value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="order_tracking_number" class="form-label small mb-1">Numer przesyłki</label>
                            <input type="text" name="tracking_number" id="order_tracking_number" class="form-control form-control-sm" maxlength="50">
                        </div>
                        <hr class="my-3">
                        <div class="col-12">
                            <label for="order_buyer_daneWysylki" class="form-label small mb-1">Dane do wysyłki <span class="text-danger">*</span></label>
                            <textarea name="buyer_daneWysylki" id="order_buyer_daneWysylki" class="form-control form-control-sm" rows="3" required minlength="5" maxlength="500"></textarea>
                            <div class="invalid-feedback">Podaj poprawne dane adresowe (min. 5 znaków).</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Anuluj</button>
                    <button type="submit" class="btn btn-info"><i class="bi bi-save me-2"></i> Zapisz zamówienie</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php // ============================================================ ?>
<?php // --- Modal Przypisywania do Magazynu (#assignWarehouseModal) --- ?>
<?php // ============================================================ ?>
<div class="modal fade" id="assignWarehouseModal" tabindex="-1" aria-labelledby="assignWarehouseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="assignWarehouseForm" method="POST" action="" class="needs-validation">
            <input type="hidden" name="action" value="assign_warehouse">
            <input type="hidden" name="tn_csrf_token" value="<?php echo htmlspecialchars($tn_token_csrf ?? ''); ?>">
            <input type="hidden" name="location_id" id="modal_location_id" value="">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fs-5" id="assignWarehouseModalLabel"><i class="bi bi-box-arrow-in-down me-2"></i> Przypisz Produkt do Miejsca</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-secondary p-2 mb-3 small" role="alert">
                        <i class="bi bi-geo-alt-fill me-2"></i> Miejsce docelowe: <strong id="modal_location_display_id" class="fs-6 font-monospace"></strong>
                    </div>
                    <div class="mb-3">
                        <label for="modal_product_id" class="form-label small mb-1">Produkt <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="modal_product_id" name="product_id" required aria-describedby="modalProductHelp">
                            <option value="">-- Wybierz produkt --</option>
                            <?php foreach ($tn_produkty_posortowane as $p) :
                                $s_assign = intval($p['stock'] ?? 0);
                                $d_assign = $s_assign <= 0 ? ' disabled' : '';
                                $st_assign = $s_assign <= 0 ? ' - BRAK' : " (Stan: {$s_assign})";
                                ?>
                                <option value="<?php echo htmlspecialchars($p['id']); ?>" data-stock="<?php echo $s_assign; ?>" <?php echo $d_assign; ?>>
                                    <?php echo htmlspecialchars($p['name'] ?? 'B/N') . $st_assign; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small mt-1" id="modalProductHelp">Wybierz produkt z dodatnim stanem magazynowym.</div>
                        <div class="invalid-feedback">Wybierz produkt.</div>
                    </div>
                    <div class="mb-3">
                        <label for="modal_quantity" class="form-label small mb-1">Ilość <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-sm" id="modal_quantity" name="quantity" min="1" required value="1" aria-describedby="assign_quantity_warning">
                        <div id="assign_quantity_warning" class="form-text text-danger mt-1 small" style="display: none;">
                            <i class="bi bi-exclamation-triangle-fill"></i> Ilość do przypisania przekracza całkowity stan produktu!
                        </div>
                        <div class="invalid-feedback">Podaj poprawną ilość.</div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Anuluj</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-2"></i> Przypisz</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php // ============================================================ ?>
<?php // --- Modal Dodawania/Edycji Kuriera (#courierModal) --- ?>
<?php // ============================================================ ?>
<div class="modal fade" id="courierModal" tabindex="-1" aria-labelledby="courierModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="courierForm" action="" class="needs-validation">
            <input type="hidden" name="action" value="save_courier">
            <input type="hidden" name="tn_csrf_token" value="<?php echo htmlspecialchars($tn_token_csrf ?? ''); ?>">
            <input type="hidden" name="courier_original_id" id="courierOriginalId">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white py-3">
                    <h5 class="modal-title fs-5" id="courierModalLabel"><i class="bi bi-truck me-2"></i> Formularz Kuriera</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                </div>
                <div class="modal-body">
                    <div id="courierIdTextGroup" class="mb-3">
                        <label for="courierIdTextInput" class="form-label small mb-1">ID Kuriera (tekstowe) <span class="text-danger">*</span></label>
                        <input type="text" name="courier_id_text" class="form-control form-control-sm" id="courierIdTextInput" pattern="^[a-z0-9_]+$" title="Tylko małe litery, cyfry i podkreślnik." placeholder="np. inpost_paczkomaty" required minlength="2" maxlength="50">
                        <div class="form-text small mt-1">Unikalny identyfikator (np. "dpd_polska"). Po utworzeniu nie można zmienić.</div>
                        <div class="invalid-feedback">Podaj poprawne ID kuriera (2-50 znaków, tylko małe litery, cyfry i podkreślnik).</div>
                    </div>
                    <div id="courierIdDisplayGroup" class="mb-3" style="display: none;">
                        <label class="form-label small mb-1">ID Kuriera</label>
                        <input type="text" class="form-control-plaintext form-control-sm ps-2" id="courierIdDisplay" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="courierName" class="form-label small mb-1">Nazwa Kuriera <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" id="courierName" required minlength="2" maxlength="100">
                        <div class="invalid-feedback">Podaj poprawną nazwę kuriera (2-100 znaków).</div>
                    </div>
                    <div class="mb-3">
                        <label for="courierTrackingPattern" class="form-label small mb-1">Wzorzec URL Śledzenia</label><input type="text" name="tracking_url_pattern" class="form-control form-control-sm" id="courierTrackingPattern" placeholder="https://.../{tracking_number}" maxlength="200">
                        <div class="form-text small mt-1">Użyj <code>{tracking_number}</code> jako placeholder numeru. Np. <code>https://xyz.com/track?nr={tracking_number}</code></div>
                        <div class="invalid-feedback">Podaj poprawny URL (max 200 znaków).</div>
                    </div>
                    <div class="mb-3">
                        <label for="courierNotes" class="form-label small mb-1">Notatki</label>
                        <textarea name="notes" class="form-control form-control-sm" id="courierNotes" rows="2" maxlength="500"></textarea>
                        <div class="form-text small mt-1">Max 500 znaków.</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="courierIsActive" name="is_active" value="1" checked>
                        <label class="form-check-label small" for="courierIsActive">Aktywny (dostępny w zamówieniach)</label>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Anuluj</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i> Zapisz Kuriera</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php // ============================================================ ?>
<?php // --- Modal Dodawania/Edycji Regału (#regalModal) --- ?>
<?php // ============================================================ ?>
<div class="modal fade" id="regalModal" tabindex="-1" aria-labelledby="regalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="regalForm" action="" class="needs-validation">
            <input type="hidden" name="action" value="create_regal">
            <input type="hidden" name="tn_csrf_token" value="<?php echo htmlspecialchars($tn_token_csrf ?? ''); ?>">
            <input type="hidden" name="original_regal_id" id="originalRegalId">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fs-5" id="regalModalLabel"><i class="bi bi-bookshelf me-2"></i> Formularz Regału</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="regalIdInput" class="form-label small mb-1">ID Regału <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="regalIdInput" name="tn_regal_id" required pattern="^[A-Za-z0-9_-]+$" title="Litery, cyfry, myślnik, podkreślnik" minlength="2" maxlength="20">
                        <div class="form-text small mt-1" id="regalIdHelp">Unikalny identyfikator (np. R01, MAG_A). Po utworzeniu nie można zmienić.</div>
                        <div class="invalid-feedback">Podaj poprawne ID regału (2-20 znaków, litery, cyfry, myślnik, podkreślnik).</div>
                    </div>
                    <div class="mb-3">
                        <label for="regalDescInput" class="form-label small mb-1">Opis Regału</label>
                        <input type="text" class="form-control form-control-sm" id="regalDescInput" name="tn_regal_opis" placeholder="np. Regał A - części drobne" maxlength="200">
                        <div class="form-text small mt-1">Max 200 znaków.</div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Anuluj</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i> Zapisz Regał</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php // ============================================================ ?>
<?php // --- Modal Generowania Lokalizacji (#generateLocationsModal) --- ?>
<div class="modal fade" id="generateLocationsModal" tabindex="-1" aria-labelledby="generateLocationsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="" class="needs-validation">
            <input type="hidden" name="action" value="create_locations">
            <input type="hidden" name="tn_csrf_token" value="<?php echo htmlspecialchars($tn_token_csrf ?? ''); ?>">
            <div class="modal-content">
                <div class="modal-header bg-info text-dark py-3">
                    <h5 class="modal-title fs-5" id="generateLocationsModalLabel"><i class="bi bi-grid-3x2-gap-fill me-2"></i> Generuj Lokalizacje Magazynowe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($tn_mapa_id_regalow_modal)) : ?>
                        <div class="alert alert-warning py-2 small" role="alert"><i class="bi bi-exclamation-triangle me-2"></i> Najpierw dodaj przynajmniej jeden regał, aby móc wygenerować dla niego lokalizacje.</div>
                    <?php else : ?>
                        <div class="mb-3">
                            <label for="tn_gen_regal_id_modal" class="form-label small mb-1">Wybierz Regał <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="tn_gen_regal_id_modal" name="tn_regal_id" required>
                                <option value="">-- Wybierz regał --</option>
                                <?php foreach ($tn_mapa_id_regalow_modal as $r_id => $r_label) : ?>
                                    <option value="<?php echo htmlspecialchars($r_id); ?>"><?php echo $r_label; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Wybierz regał.</div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6 mb-2">
                                <label for="tn_gen_liczba_poziomow_modal" class="form-label small mb-1">Liczba Poziomów <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" id="tn_gen_liczba_poziomow_modal" name="tn_liczba_poziomow" required min="1" value="1" aria-describedby="poziomyHelp">
                                <div class="invalid-feedback">Podaj poprawną liczbę poziomów (min. 1).</div>
                                <div class="form-text small" id="poziomyHelp">Min. 1</div>
                            </div>
                            <div class="col-6 mb-2">
                                <label for="tn_gen_miejsc_na_poziom_modal" class="form-label small mb-1">Miejsc na Poziomie <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" id="tn_gen_miejsc_na_poziom_modal" name="tn_miejsc_na_poziom" required min="1" value="1"  aria-describedby="miejscaHelp">
                                 <div class="invalid-feedback">Podaj poprawną liczbę miejsc (min. 1).</div>
                                 <div class="form-text small" id="miejscaHelp">Min. 1</div>
                            </div>
                            <div class="col-6 mb-2">
                                <label for="tn_gen_prefix_poziomu_modal" class="form-label small mb-1">Prefix Poziomu</label>
                                <input type="text" class="form-control form-control-sm" id="tn_gen_prefix_poziomu_modal" name="tn_prefix_poziomu" value="<?php echo htmlspecialchars($tn_ustawienia_globalne['magazyn']['tn_prefix_poziom_domyslny'] ?? 'S'); ?>" placeholder="np. S, POZ" pattern="^[A-Za-z0-9]*$" title="Tylko litery i cyfry" maxlength="10">
                                <div class="form-text small">Max 10 znaków.</div>
                            </div>
                            <div class="col-6 mb-2">
                                <label for="tn_gen_prefix_miejsca_modal" class="form-label small mb-1">Prefix Miejsca</label>
                                <input type="text" class="form-control form-control-sm" id="tn_gen_prefix_miejsca_modal" name="tn_prefix_miejsca" value="<?php echo htmlspecialchars($tn_ustawienia_globalne['magazyn']['tn_prefix_miejsca_domyslny'] ?? 'P'); ?>" placeholder="np. P, M" pattern="^[A-Za-z0-9]*$" title="Tylko litery i cyfry" maxlength="10">
                                <div class="form-text small">Max 10 znaków.</div>
                            </div>
                        </div>
                        <div class="form-text small mt-2 mb-0">
                            Format generowanego ID: <code>ID_Regału-PrefixPozNumer-PrefixMieNumer</code> (np. R01-S01-P01).<br>
                            Numery poziomów i miejsc będą formatowane z wiodącym zerem (np. 01, 02.. 10).<br>
                            Istniejące ID lokalizacji zostaną pominięte.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Anuluj</button>
                    <button type="submit" class="btn btn-info" <?php if (empty($tn_mapa_id_regalow_modal)) echo 'disabled'; ?>>
                        <i class="bi bi-magic me-1"></i> Generuj Lokalizacje
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php // ============================================================ ?>
<?php // --- Modal do powiększania zdjęć (#tnImageZoomModal) --- ?>
<div class="modal fade" id="tnImageZoomModal" tabindex="-1" aria-labelledby="tnImageZoomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-dark text-white">
                <h6 class="modal-title" id="tnImageZoomModalLabel">Podgląd Zdjęcia Produktu</h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body text-center p-2">
                <img src="" id="tnZoomedImage" class="img-fluid rounded" alt="Powiększone zdjęcie" style="max-height: 85vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

<script>
    // Валидация форм Bootstrap при отправке
    (function () {
        'use strict'

        // Получаем все формы, к которым хотим применить пользовательскую валидацию Bootstrap
        var forms = document.querySelectorAll('.modal form.needs-validation')

        // Цикл по ним и предотвращение отправки
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }

                    form.classList.add('was-validated')
                }, false)
            })
    })()


    // Podgląd obrazka przed загрузкой
    const imageFile = document.getElementById('productImageFile');
    const imagePreview = document.getElementById('imagePreview');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');

    if (imageFile && imagePreview && imagePreviewContainer) {
        imageFile.addEventListener('change', function () {
            const file = this.files[0];

            if (file) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                    imagePreviewContainer.style.display = 'block';
                }

                reader.readAsDataURL(file);
            } else {
                imagePreview.src = '#';
                imagePreviewContainer.style.display = 'none';
            }
        });
    }


    // Funkcja inicjalizacji модала с продуктом
    function initializeProductModal(modalId, isEditing = false, data = {}) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const form = modal.querySelector('form');
        const title = modal.querySelector('.modal-title');
        const productIdField = modal.querySelector('#productId');
        const productNameField = modal.querySelector('#productName');
        const productProducentField = modal.querySelector('#productProducent');
        const productCatalogNrField = modal.querySelector('#productCatalogNr');
        const productCategorySelect = modal.querySelector('#productCategorySelect');
        const productDescField = modal.querySelector('#productDesc');
        const productSpecField = modal.querySelector('#productSpec');
        const productParamsField = modal.querySelector('#productParams');
        const productVehicleField = modal.querySelector('#productVehicle');
        const productPriceField = modal.querySelector('#productPrice');
        const productShippingField = modal.querySelector('#productShipping');
        const productStockField = modal.querySelector('#productStock');
        const productUnitField = modal.querySelector('#productUnit');
        const productLocationSelect = modal.querySelector('#productLocationSelect');
        const productImageFileField = modal.querySelector('#productImageFile');
        const imagePreview = modal.querySelector('#imagePreview');
        const imagePreviewContainer = modal.querySelector('#imagePreviewContainer');
        const originalWarehouseValueField = modal.querySelector('#originalWarehouseValue');


        if (isEditing) {
            title.textContent = 'Edytuj Produkt';
            productIdField.value = data.id || '';
            productNameField.value = data.name || '';
            productProducentField.value = data.producent || '';
            productCatalogNrField.value = data.tn_numer_katalogowy || '';
            productCategorySelect.value = data.category || '';
            productDescField.value = data.desc || '';
            productSpecField.value = data.spec || '';
            productParamsField.value = data.params || '';
            productVehicleField.value = data.vehicle || '';
            productPriceField.value = data.price || '0.00';
            productShippingField.value = data.shipping || '0.00';
            productStockField.value = data.stock || '0';
            productUnitField.value = data.tn_jednostka_miary || '';
            productLocationSelect.value = data.warehouse || '';
            originalWarehouseValueField.value = data.warehouse || '';

            if (data.image_url) {
                imagePreview.src = data.image_url;
                imagePreviewContainer.style.display = 'block';
            } else {
                imagePreview.src = '#';
                imagePreviewContainer.style.display = 'none';
            }

            productLocationSelect.disabled = !!data.warehouse;

        } else {
            title.textContent = 'Dodaj Produkt';
            form.reset();
            productIdField.value = '';
            productLocationSelect.disabled = false;
            originalWarehouseValueField.value = '';
            imagePreview.src = '#';
            imagePreviewContainer.style.display = 'none';
        }

        form.classList.remove('was-validated');
    }


    // Funkcja inicjalizacji модала zamówлення
    function initializeOrderModal(modalId, isEditing = false, data = {}) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const form = modal.querySelector('form');
        const title = modal.querySelector('.modal-title');
        const orderIdField = modal.querySelector('#edit_order_id');
        const orderProductIdSelect = modal.querySelector('#order_product_id');
        const orderQuantityField = modal.querySelector('#order_quantity');
        const orderBuyerNameField = modal.querySelector('#order_buyer_name');
        const orderStatusSelect = modal.querySelector('#order_status');
        const orderPaymentStatusSelect = modal.querySelector('#order_payment_status');
        const orderCourierSelect = modal.querySelector('#order_courier_id');
        const orderTrackingNumberField = modal.querySelector('#order_tracking_number');
        const orderBuyerDaneWysylkiField = modal.querySelector('#order_buyer_daneWysylki');
        const quantityWarning = modal.querySelector('#quantity_warning');


        if (isEditing) {
            title.textContent = 'Edytuj Zamówienie';
            orderIdField.value = data.order_id || '';
            orderProductIdSelect.value = data.product_id || '';
            orderQuantityField.value = data.quantity || '1';
            orderBuyerNameField.value = data.buyer_name || '';
            orderStatusSelect.value = data.status || '';
            orderPaymentStatusSelect.value = data.tn_status_platnosci || '';
            orderCourierSelect.value = data.courier_id || '';
            orderTrackingNumberField.value = data.tracking_number || '';
            orderBuyerDaneWysylkiField.value = data.buyer_daneWysylki || '';

            const selectedOption = orderProductIdSelect.querySelector(`option[value="${data.product_id}"]`);
            const maxQuantity = selectedOption ? parseInt(selectedOption.getAttribute('data-stock'), 10) : 0;
            orderQuantityField.max = maxQuantity;
            quantityWarning.style.display = parseInt(orderQuantityField.value, 10) > maxQuantity ? 'block' : 'none';


        } else {
            title.textContent = 'Dodaj Zamówienie';
            form.reset();
            orderIdField.value = '';
            orderQuantityField.value = '1';
            quantityWarning.style.display = 'none';
        }

        form.classList.remove('was-validated');
    }



    // Funkcja inicjalizacji модала Przypisywania do Magazynu
    function initializeAssignWarehouseModal(modalId, locationId, locationName) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const form = modal.querySelector('form');
        const modalLocationIdField = modal.querySelector('#modal_location_id');
        const modalLocationDisplayId = modal.querySelector('#modal_location_display_id');
        const modalProductIdSelect = modal.querySelector('#modal_product_id');
        const modalQuantityField = modal.querySelector('#modal_quantity');
        const quantityWarning = modal.querySelector('#assign_quantity_warning');

        modalLocationIdField.value = locationId;
        modalLocationDisplayId.textContent = locationName;
        modalQuantityField.value = '1';

        modalProductIdSelect.value = '';
        form.classList.remove('was-validated');
        quantityWarning.style.display = 'none';


        modalProductIdSelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption) {
                const maxQuantity = parseInt(selectedOption.getAttribute('data-stock'), 10);
                modalQuantityField.max = maxQuantity;
                quantityWarning.style.display = parseInt(modalQuantityField.value, 10) > maxQuantity ? 'block' : 'none';
            }
        });

        modalQuantityField.addEventListener('input', function () {
            const selectedOption = modalProductIdSelect.options[modalProductIdSelect.selectedIndex];
            if (selectedOption) {
                const maxQuantity = parseInt(selectedOption.getAttribute('data-stock'), 10);
                quantityWarning.style.display = parseInt(this.value, 10) > maxQuantity ? 'block' : 'none';
            }
        });
    }


    // Funkcja inicjalizacji модала Kuriera
    function initializeCourierModal(modalId, isEditing = false, data = {}) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const form = modal.querySelector('form');
        const title = modal.querySelector('.modal-title');
        const courierIdTextGroup = modal.querySelector('#courierIdTextGroup');
        const courierIdDisplayGroup = modal.querySelector('#courierIdDisplayGroup');
        const courierIdDisplay = modal.querySelector('#courierIdDisplay');
        const courierIdTextInput = modal.querySelector('#courierIdTextInput');
        const courierNameField = modal.querySelector('#courierName');
        const courierTrackingPatternField = modal.querySelector('#courierTrackingPattern');
        const courierNotesField = modal.querySelector('#courierNotes');
        const courierIsActiveCheckbox = modal.querySelector('#courierIsActive');

        if (isEditing) {
            title.textContent = 'Edytuj Kuriera';
            courierIdTextGroup.style.display = 'none';
            courierIdDisplayGroup.style.display = 'block';
            courierIdDisplay.value = data.courier_id || '';
            courierNameField.value = data.name || '';
            courierTrackingPatternField.value = data.tracking_url_pattern || '';
            courierNotesField.value = data.notes || '';
            courierIsActiveCheckbox.checked = data.is_active === true;
        } else {
            title.textContent = 'Dodaj Kuriera';
            form.reset();
            courierIdTextGroup.style.display = 'block';
            courierIdDisplayGroup.style.display = 'none';
            courierIdDisplay.value = '';
        }

        form.classList.remove('was-validated');
    }


    // Funkcja inicjalizacji модала Regału
    function initializeRegalModal(modalId, isEditing = false, data = {}) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const form = modal.querySelector('form');
        const title = modal.querySelector('.modal-title');
        const regalIdInput = modal.querySelector('#regalIdInput');
        const regalDescInput = modal.querySelector('#regalDescInput');
        const originalRegalIdField = modal.querySelector('#originalRegalId');


        if (isEditing) {
            title.textContent = 'Edytuj Regał';
            regalIdInput.value = data.tn_id_regalu || '';
            regalDescInput.value = data.tn_opis_regalu || '';
            originalRegalIdField.value = data.tn_id_regalu;
            regalIdInput.disabled = true;
        } else {
            title.textContent = 'Dodaj Regał';
            form.reset();
            regalIdInput.value = '';
            regalIdInput.disabled = false;
            originalRegalIdField.value = '';
        }

        form.classList.remove('was-validated');
    }



    // Funkcja inicjalizacji модала Generowania Lokalizacji
    function initializeGenerateLocationsModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const form = modal.querySelector('form');
        const regalIdSelect = modal.querySelector('#tn_gen_regal_id_modal');
        const liczbaPoziomowInput = modal.querySelector('#tn_gen_liczba_poziomow_modal');
        const miejscaNaPoziomInput = modal.querySelector('#tn_gen_miejsc_na_poziom_modal');
        const prefixPoziomuInput = modal.querySelector('#tn_gen_prefix_poziomu_modal');
        const prefixMiejscaInput = modal.querySelector('#tn_gen_prefix_miejsca_modal');

        form.reset();
        form.classList.remove('was-validated');
        regalIdSelect.disabled = false;

        liczbaPoziomowInput.value = '1';
        miejscaNaPoziomInput.value = '1';
        prefixPoziomuInput.value = '';
        prefixMiejscaInput.value = '';
    }


    // Funkcja inicjalizacji модала powiększania zdjęć
    function initializeImageZoomModal(modalId, imageUrl) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const zoomedImage = modal.querySelector('#tnZoomedImage');
        zoomedImage.src = imageUrl;
    }
</script>
