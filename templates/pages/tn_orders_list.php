<?php
// templates/pages/tn_orders_list.php
/**
 * Widok listy zamówień.
 * Wersja: 1.3 (Poprawki ładowania mapy kurierów, link śledzenia)
 */

// Potrzebne zmienne z index.php:
/** @var array|null $tn_zamowienia_dane */
/** @var array $tn_ustawienia_globalne */
/** @var string $tn_token_csrf */
/** @var array $tn_produkty */
/** @var array $tn_kurierzy */ // Ta zmienna jest teraz ładowana poprawnie przez helper

// Bezpieczne rozpakowanie danych
$tn_zamowienia_wyswietlane = $tn_zamowienia_dane['zamowienia_wyswietlane'] ?? [];
$tn_ilosc_wszystkich = $tn_zamowienia_dane['ilosc_wszystkich'] ?? 0;
$tn_ilosc_stron = $tn_zamowienia_dane['ilosc_stron'] ?? 1;
$tn_biezaca_strona = $tn_zamowienia_dane['biezaca_strona'] ?? 1;
$tn_sortowanie = $tn_zamowienia_dane['sortowanie'] ?? 'date_desc';
$tn_filtr_statusu = $tn_zamowienia_dane['status'] ?? '';

// Przygotuj mapy dla łatwiejszego dostępu
$tn_mapa_produktow = array_column($tn_produkty ?? [], null, 'id');
// Zmieniono: Poprawne ładowanie mapy kurierów (klucz to tekstowe ID)
$tn_mapa_kurierow = tn_laduj_kurierow(TN_PLIK_KURIERZY); // Użyj funkcji helpera

// Ustawienia tabeli i formatowania
$tn_tabela_paskowana = $tn_ustawienia_globalne['wyglad']['tn_tabela_paskowana'] ?? true;
$tn_tabela_krawedzie = $tn_ustawienia_globalne['wyglad']['tn_tabela_krawedzie'] ?? true;
$tn_waluta = $tn_ustawienia_globalne['waluta'] ?? 'PLN';
$tn_format_daty = $tn_ustawienia_globalne['tn_format_daty'] ?? 'd.m.Y';
$tn_format_czasu = $tn_ustawienia_globalne['tn_format_czasu'] ?? 'H:i';

// Dostępne statusy zamówień
$tn_dostepne_statusy = defined('TN_STATUSY_ZAMOWIEN') ? TN_STATUSY_ZAMOWIEN : ['Nowe', 'W przygotowaniu', 'Zrealizowane', 'Anulowane'];
// Mapa klas dla statusów
$tn_status_klasa_mapa = [ 'Nowe' => 'text-bg-primary', 'W przygotowaniu' => 'text-bg-warning', 'Zrealizowane' => 'text-bg-success', 'Anulowane' => 'text-bg-danger', 'default' => 'text-bg-secondary'];

$tn_biezaca_strona_ident = 'orders'; // Dla paginacji i sortowania

// Sprawdzenie istnienia helperów (dla bezpieczeństwa)
if (!function_exists('tn_generuj_link_sortowania_zamowien')) { function tn_generuj_link_sortowania_zamowien($k,$e){return $e;} }
if (!function_exists('tn_generuj_url')) { function tn_generuj_url($id,$p=[]){return '?page='.$id;} }
if (!function_exists('tn_generuj_link_akcji_get')) { function tn_generuj_link_akcji_get($a,$p=[]){return '?action='.$a;} }

?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h1 class="h4 mb-0 me-2 d-inline-block align-middle"><i class="bi bi-receipt me-2"></i>Lista Zamówień</h1>
        <span class="badge bg-secondary rounded-pill align-middle"><?php echo $tn_ilosc_wszystkich; ?></span>
        <?php if (!empty($tn_filtr_statusu) && $tn_filtr_statusu !== 'Wszystkie'): ?>
             <span class="ms-2 fst-italic small text-muted">(Filtr: <?php echo htmlspecialchars($tn_filtr_statusu); ?>)</span>
        <?php endif; ?>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <button type="button" class="btn btn-primary btn-sm" onclick="if(typeof tnApp !== 'undefined' && tnApp.setupOrderModal) { tnApp.setupOrderModal(); } else { alert('Błąd JS'); }">
            <i class="bi bi-plus-circle me-1"></i>Dodaj Nowe Zamówienie
        </button>
    </div>
</div>

<?php // Sekcja filtrowania statusu ?>
<div class="mb-3">
    <span class="small fw-bold me-2">Filtruj status:</span>
    <div class="btn-group btn-group-sm" role="group" aria-label="Filtrowanie statusu zamówień">
        <?php
            $base_filter_params = array_filter(['sort' => $tn_sortowanie]);
            $is_all_active = empty($tn_filtr_statusu) || $tn_filtr_statusu === 'Wszystkie';
            $all_url = tn_generuj_url($tn_biezaca_strona_ident, $base_filter_params);
        ?>
        <a href="<?php echo htmlspecialchars($all_url); ?>" class="btn <?php echo $is_all_active ? 'btn-dark' : 'btn-outline-secondary'; ?>">Wszystkie</a>
        <?php foreach($tn_dostepne_statusy as $status):
                $is_active = ($tn_filtr_statusu === $status);
                $status_params = array_merge($base_filter_params, ['status' => $status]);
                $status_url = tn_generuj_url($tn_biezaca_strona_ident, $status_params);
                $btn_class = 'btn-outline-secondary';
                $status_color_key = $tn_status_klasa_mapa[$status] ?? null;
                if($status_color_key) {
                     $color_name = str_replace(['text-bg-'], '', $status_color_key);
                     $btn_class = $is_active ? 'btn-' . $color_name : 'btn-outline-' . $color_name;
                     if($color_name === 'warning' && !$is_active) $btn_class .= ' text-warning';
                }
        ?>
            <a href="<?php echo htmlspecialchars($status_url); ?>" class="btn <?php echo $btn_class; ?> <?php echo $is_active ? 'active' : ''; ?>"><?php echo htmlspecialchars($status); ?></a>
        <?php endforeach; ?>
    </div>
</div>


<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table <?php echo $tn_tabela_paskowana ? 'table-striped' : ''; ?> <?php echo $tn_tabela_krawedzie ? 'table-bordered' : ''; ?> table-hover align-middle mb-0 small tn-tabela-zamowien">
            <thead class="table-light">
                <tr>
                    <th class="text-center"><?php echo tn_generuj_link_sortowania_zamowien('id', 'ID Zam.'); ?></th>
                    <th><?php echo tn_generuj_link_sortowania_zamowien('date', 'Data'); ?></th>
                    <th><?php echo tn_generuj_link_sortowania_zamowien('customer', 'Klient'); ?></th>
                    <th>Produkt</th>
                    <th class="text-center">Ilość</th>
                    <th class="text-end">Wartość</th>
                    <th class="text-center"><?php echo tn_generuj_link_sortowania_zamowien('status', 'Status'); ?></th>
                    <th>Kurier / Śledzenie</th>
                    <th class="text-center" style="width: 100px;">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tn_zamowienia_wyswietlane)): ?>
                    <tr>
                        <td colspan="9" class="text-center p-4 text-muted fst-italic">
                             Brak zamówień<?php echo (!empty($tn_filtr_statusu) && $tn_filtr_statusu !== 'Wszystkie') ? ' o statusie '.htmlspecialchars($tn_filtr_statusu) : ''; ?>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tn_zamowienia_wyswietlane as $zam): ?>
                        <?php
                            $produkt_id = $zam['product_id'] ?? null;
                            $produkt = $tn_mapa_produktow[$produkt_id] ?? null;
                            $nazwa_produktu = $produkt['name'] ?? '?';
                            $cena_produktu = $produkt['price'] ?? 0;
                            $ilosc = $zam['quantity'] ?? 0;
                            $wartosc = $cena_produktu * $ilosc;
                            $status_zam = $zam['status'] ?? 'Nieznany';
                            $klasy_badge_zam = $tn_status_klasa_mapa[$status_zam] ?? $tn_status_klasa_mapa['default'];

                            // Dane kuriera i śledzenia (Poprawiona logika)
                            $kurier_id = $zam['courier_id'] ?? $zam['courier'] ?? null;
                            $kurier = ($kurier_id && isset($tn_mapa_kurierow[$kurier_id])) ? $tn_mapa_kurierow[$kurier_id] : null;
                            $nazwa_kuriera = $kurier ? htmlspecialchars($kurier['name']) : null;
                            $nr_sledzenia = !empty($zam['tracking_number']) ? htmlspecialchars($zam['tracking_number']) : null;
                            $url_sledzenia = null;
                            if ($nr_sledzenia && $kurier && !empty($kurier['tracking_url_pattern'])) {
                                $pattern = $kurier['tracking_url_pattern'];
                                if (str_contains($pattern, '{tracking_number}')) {
                                    $url_sledzenia = str_replace('{tracking_number}', rawurlencode($nr_sledzenia), $pattern);
                                } else {
                                    $url_sledzenia = rtrim($pattern, '=/?&') . rawurlencode($nr_sledzenia);
                                }
                                $url_sledzenia = htmlspecialchars($url_sledzenia);
                            }

                            $link_podgladu = tn_generuj_url('order_preview', ['id' => $zam['id'] ?? 0]);
                            $link_usun = tn_generuj_link_akcji_get('delete_order', ['id' => $zam['id'] ?? 0]);
                        ?>
                        <tr class="<?php echo ($status_zam === 'Nowe') ? 'tn-order-new' : ''; ?>">
                            <td class="text-center text-muted"><?php echo htmlspecialchars($zam['id'] ?? '?'); ?></td>
                            <td class="text-nowrap">
                                <?php try { echo !empty($zam['order_date']) ? (new DateTime($zam['order_date']))->format($tn_format_daty . ' ' . $tn_format_czasu) : '-'; } catch (Exception $e) { echo '-';} ?>
                            </td>
                            <td class="text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($zam['buyer_name'] ?? ''); ?>">
                                <?php echo htmlspecialchars($zam['buyer_name'] ?? '-'); ?>
                            </td>
                            <td>
                                <?php if($produkt_id && $produkt): ?>
                                    <a href="<?php echo tn_generuj_url('product_preview', ['id' => $produkt_id]); ?>" title="<?php echo htmlspecialchars($nazwa_produktu); ?>">
                                        <?php echo htmlspecialchars(mb_strimwidth($nazwa_produktu, 0, 30, '...')); ?>
                                    </a>
                                <?php elseif($produkt_id): ?>
                                     <span class="text-danger" title="Produkt o ID <?php echo $produkt_id; ?> nie istnieje!">Brak Prod. (<?php echo $produkt_id; ?>)</span>
                                <?php else: echo '?'; endif; ?>
                            </td>
                            <td class="text-center"><?php echo htmlspecialchars($ilosc); ?></td>
                            <td class="text-end text-nowrap fw-medium"><?php echo number_format($wartosc, 2, ',', ' '); ?> <?php echo htmlspecialchars($tn_waluta); ?></td>
                            <td class="text-center"><span class="badge <?php echo $klasy_badge_zam; ?>"><?php echo htmlspecialchars($status_zam); ?></span></td>
                            <td> <?php // Kolumna Kurier / Śledzenie ?>
                                <?php if ($nazwa_kuriera): ?>
                                    <span title="Kurier: <?php echo $nazwa_kuriera; ?>"><?php echo $nazwa_kuriera; ?></span>
                                <?php else: echo '<span class="text-muted">-</span>'; endif; ?>
                                <?php if ($nr_sledzenia): ?>
                                    <small class="d-block text-muted font-monospace" title="Numer śledzenia">
                                        <?php echo $nr_sledzenia; ?>
                                        <?php if ($url_sledzenia): ?>
                                            <a href="<?php echo $url_sledzenia; ?>" target="_blank" class="ms-1" title="Śledź przesyłkę"><i class="bi bi-box-arrow-up-right"></i></a>
                                        <?php endif; ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center tn-przyciski-akcji">
                                <a href="<?php echo $link_podgladu; ?>" class="btn btn-outline-info btn-sm py-0 px-1" data-bs-toggle="tooltip" title="Podgląd">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button type="button" class="btn btn-outline-warning btn-sm py-0 px-1" onclick='if(typeof tnApp !== "undefined" && tnApp.setupOrderModal) tnApp.setupOrderModal(<?php echo json_encode($zam, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' data-bs-toggle="tooltip" title="Edytuj">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="<?php echo $link_usun; ?>" class="btn btn-outline-danger btn-sm py-0 px-1" onclick="return confirm('Czy na pewno usunąć zamówienie #<?php echo $zam['id'] ?? ''; ?>?');" data-bs-toggle="tooltip" title="Usuń">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php // Paginacja
        if (($tn_ilosc_stron ?? 1) > 1 && !empty($tn_zamowienia_dane)) {
            $tn_parametry_paginacji = ['sort' => $tn_sortowanie, 'status' => $tn_filtr_statusu];
            $tn_parametry_paginacji = array_filter($tn_parametry_paginacji); // Usuń puste filtry
            $GLOBALS['tn_parametry_sort_filter'] = $tn_parametry_paginacji;
            $pagination_template_path = TN_SCIEZKA_TEMPLATEK . 'partials/tn_pagination.php';
            if (file_exists($pagination_template_path)) { include $pagination_template_path; }
            else { echo '<p class="text-danger text-center my-3 small">Błąd: Brak szablonu paginacji.</p>'; }
        }
    ?>
</div>