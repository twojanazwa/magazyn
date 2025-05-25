<?php
// templates/pages/tn_products_list.php
/**
 * Widok listy produktów.
 * Wyświetla tabelę produktów z możliwością sortowania, paginacji,
 * filtrowania (wyszukiwarka w topbarze, kategoria poniżej) oraz akcjami.
 * Wersja: 1.5 (Finalna wersja po poprawkach)
 *
 * Oczekuje zmiennych z index.php:
 * @var array|null $tn_produkty_dane Wynik z tn_przetworz_liste_produktow(). Zawiera klucze:
 * 'produkty_wyswietlane', 'ilosc_wszystkich', 'ilosc_stron', 'biezaca_strona',
 * 'sortowanie', 'zapytanie_szukania', 'kategoria'.
 * @var array $tn_ustawienia_globalne Ustawienia globalne.
 * @var string $tn_token_csrf Token CSRF.
 */

// Bezpieczne rozpakowanie danych z logiki przetwarzania
$tn_produkty_wyswietlane = $tn_produkty_dane['produkty_wyswietlane'] ?? [];
$tn_ilosc_wszystkich = $tn_produkty_dane['ilosc_wszystkich'] ?? 0;
$tn_ilosc_stron = $tn_produkty_dane['ilosc_stron'] ?? 1;
$tn_biezaca_strona = $tn_produkty_dane['biezaca_strona'] ?? 1;
$tn_sortowanie = $tn_produkty_dane['sortowanie'] ?? 'name_asc';
$tn_zapytanie_szukania = $tn_produkty_dane['zapytanie_szukania'] ?? '';
$tn_kategoria_filtrowania = $tn_produkty_dane['kategoria'] ?? '';

// Ustawienia tabeli i formatowania
$tn_tabela_paskowana = $tn_ustawienia_globalne['wyglad']['tn_tabela_paskowana'] ?? true;
$tn_tabela_krawedzie = $tn_ustawienia_globalne['wyglad']['tn_tabela_krawedzie'] ?? true;
$tn_waluta = $tn_ustawienia_globalne['waluta'] ?? 'PLN';
$tn_kategorie_produktow = $tn_ustawienia_globalne['kategorie_produktow'] ?? [];
$tn_prog_niskiego_stanu = $tn_ustawienia_globalne['tn_prog_niskiego_stanu'] ?? 5;
$tn_domyslny_magazyn_txt = $tn_ustawienia_globalne['domyslny_magazyn'] ?? 'NIEPRZYPISANY';

// Identyfikator bieżącej strony dla paginacji i linków sortowania
$tn_biezaca_strona_ident = 'products';

// Funkcje pomocnicze (upewnij się, że są dostępne)
if (!function_exists('selected')) { function selected($a, $b) { if ((string)$a === (string)$b) echo ' selected'; } }
if (!function_exists('tn_generuj_link_sortowania')) { die('Błąd krytyczny: Brak funkcji tn_generuj_link_sortowania()'); }
if (!function_exists('tn_pobierz_sciezke_obrazka')) { die('Błąd krytyczny: Brak funkcji tn_pobierz_sciezke_obrazka()'); }
if (!function_exists('tn_generuj_url')) { die('Błąd krytyczny: Brak funkcji tn_generuj_url()'); }
if (!function_exists('tn_generuj_link_akcji_get')) { die('Błąd krytyczny: Brak funkcji tn_generuj_link_akcji_get()'); }

?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      W bazie: <b><?php echo $tn_ilosc_wszystkich; ?></b>
          </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        
   
        <button type="button" class="btn btn-primary btn-sm" onclick="if(typeof tnApp !== 'undefined' && tnApp.openAddModal) { tnApp.openAddModal(); } else { alert('tnApp Błąd: Funkcja niezaładowana.'); }">
            <i class="bi bi-plus-circle me-1"></i>Dodaj Nową Ofertę
        </button>
    </div> 
</div>



<div class="card shadow-sm mb-4">
    <div class="card-body p-2">
        <form method="GET" action="<?php echo tn_generuj_url('products'); ?>" class="d-flex flex-wrap align-items-center gap-2">
            <?php // Zachowaj sortowanie i szukanie przy filtrowaniu kategorii ?>
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($tn_sortowanie); ?>">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($tn_zapytanie_szukania); ?>">

            <label for="tn_filter_category" class="form-label small mb-0 me-1 fw-bold">Filtruj Kategorię:</label>
            <select name="category" id="tn_filter_category" class="form-select form-select-sm" style="width: auto; min-width: 180px;">
                <option value="">-- Wszystkie Kategorie --</option>
                <?php if(!empty($tn_kategorie_produktow)): foreach ($tn_kategorie_produktow as $kat): ?>
                    <option value="<?php echo htmlspecialchars($kat); ?>" <?php selected($tn_kategoria_filtrowania, $kat); ?>>
                        <?php echo htmlspecialchars($kat); ?>
                    </option>
                <?php endforeach; endif; ?>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm"><i class="bi bi-funnel-fill"></i> Filtruj</button>
            <?php // Przycisk czyszczenia filtra kategorii ?>
            <?php if (!empty($tn_kategoria_filtrowania)): ?>
                <?php $clear_cat_params = array_filter(['sort' => $tn_sortowanie, 'search' => $tn_zapytanie_szukania]); ?>
                <a href="<?php echo tn_generuj_url('products', $clear_cat_params); ?>" class="btn btn-outline-danger btn-sm" title="Wyczyść filtr kategorii"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </form>
         <?php // Informacja o aktywnym wyszukiwaniu (jeśli jest) ?>
         <?php if (!empty($tn_zapytanie_szukania)): ?>
             <div class="mt-2 small text-muted fst-italic ps-1">
                 Wyniki dla: "<?php echo htmlspecialchars($tn_zapytanie_szukania); ?>"
                 <?php $clear_search_params = array_filter(['sort' => $tn_sortowanie, 'category' => $tn_kategoria_filtrowania]); ?>
                 <a href="<?php echo tn_generuj_url('products', $clear_search_params); ?>">(wyczyść wyszukiwanie)</a>
             </div>
         <?php endif; ?>
    </div>
</div>


<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table <?php echo $tn_tabela_paskowana ? 'table-striped' : ''; ?> <?php echo $tn_tabela_krawedzie ? 'table-bordered' : ''; ?> table-hover align-middle mb-0 small tn-tabela-produktow">
            <thead class="table-light">
                <tr>
                    <th class="text-center" style="width: 60px;"></th>
                    <th><?php echo tn_generuj_link_sortowania('name', 'Nazwa / Producent'); ?></th>
                    <th>Numer katalogowy części</th>
                   
                    <th class="text-center"><?php echo tn_generuj_link_sortowania('stock', 'Ilość'); ?></th>
                    <th>Nr. mag.</th> 
                    <th class="text-end"><?php echo tn_generuj_link_sortowania('price', ' Cena '); ?></th>
                    <th class="text-center" style="width: 100px;"> </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tn_produkty_wyswietlane)): ?>
                    <tr>
                        <td colspan="8" class="text-center p-4 text-muted fst-italic">
                            Brak produktów<?php echo ($tn_zapytanie_szukania || $tn_kategoria_filtrowania) ? ' pasujących do kryteriów' : ''; ?>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tn_produkty_wyswietlane as $tn_p): ?>
                        <?php
                            $tn_id = $tn_p['id'] ?? null;
                            if ($tn_id === null) continue; // Pomiń produkty bez ID

                            $tn_stock = intval($tn_p['stock'] ?? 0);
                            $tn_row_class = '';
                            if ($tn_stock <= 0) { $tn_row_class = 'tn-stock-out table-danger-subtle'; }
                            elseif ($tn_stock <= $tn_prog_niskiego_stanu) { $tn_row_class = 'tn-stock-low table-warning-subtle'; }

                            $tn_img_src = tn_pobierz_sciezke_obrazka($tn_p['image'] ?? null);
                            $tn_link_podglad = tn_generuj_url('product_preview', ['id' => $tn_id]);
                            $tn_link_usun = tn_generuj_link_akcji_get('delete_product', ['id' => $tn_id]);

                          
                            $tn_lokalizacje_produktu_text = htmlspecialchars($tn_p['warehouse'] ?? '');
                            if (empty($tn_lokalizacje_produktu_text) || $tn_lokalizacje_produktu_text === $tn_domyslny_magazyn_txt) {
                                $tn_lokalizacje_produktu = '<span class="text-muted fst-italic">Brak</span>';
                            } else {
                                $locs = explode(',', $tn_lokalizacje_produktu_text);
                                $loc_links = [];
                                foreach($locs as $loc) { $loc = trim($loc); if(!empty($loc)) { $loc_links[] = '<a href="'.tn_generuj_url('warehouse_view', ['search'=>$loc]).'" class="text-muted text-decoration-none" title="Pokaż lokalizację '.$loc.'">'.$loc.'</a>'; } }
                                $tn_lokalizacje_produktu = !empty($loc_links) ? implode(', ', $loc_links) : '<span class="text-muted fst-italic">Brak</span>';
                            }
                        ?>
                        <tr class="<?php echo $tn_row_class; ?>">
                            <td class="text-center p-1 align-middle">
                                <img src="<?php echo $tn_img_src; ?>" alt="<?php echo htmlspecialchars($tn_p['name'] ?? ''); ?>"
                                     class="img-thumbnail tn-miniatura-produktu bg-body-tertiary" loading="lazy" style="width: 45px; height: 45px;"
                                     onclick="if(typeof tnApp !== 'undefined' && tnApp.showImageModal) tnApp.showImageModal('<?php echo $tn_img_src; ?>', '<?php echo htmlspecialchars(addslashes($tn_p['name'] ?? ''), ENT_QUOTES); ?>')"
                                     onerror="this.onerror=null; this.src='<?php echo tn_pobierz_sciezke_obrazka(null); // Placeholder SVG ?>'; this.classList.add('tn-img-placeholder');"
                                >
                            </td>
                            <td class="align-middle">
                                <a href="<?php echo $tn_link_podglad; ?>" class="fw-bold text-body text-decoration-none">
                                    <?php echo htmlspecialchars($tn_p['name'] ?? 'Brak nazwy'); ?>
                                </a>
                                <small class="d-block text-muted"><?php echo htmlspecialchars($tn_p['producent'] ?? '-'); ?></small>
                            </td>
                             <td class="font-monospace text-muted align-middle"><?php echo htmlspecialchars($tn_p['tn_numer_katalogowy'] ?? '-'); ?></td>
                            
                            <td class="text-center fw-bold align-middle">
                                <?php if ($tn_stock <= 0): ?> <span class="text-danger" title="Brak produktu na stanie"><?php echo $tn_stock; ?></span>
                                <?php elseif ($tn_stock <= $tn_prog_niskiego_stanu): ?> <span class="text-warning" title="Niski stan magazynowy"><?php echo $tn_stock; ?></span>
                                <?php else: ?> <span class="text-success" title="Dostępny"><?php echo $tn_stock; ?></span>
                                <?php endif; ?>
                            </td>
                             <td class="small align-middle text-muted text-truncate" style="max-width: 150px;">
                                <?php echo $tn_lokalizacje_produktu; // Zawiera już HTML, nie escapujemy ?>
                             </td>
                            <td class="text-end text-nowrap fw-medium align-middle">
                                <?php echo number_format($tn_p['price'] ?? 0, 2, ',', ' '); ?> <?php echo $tn_waluta; ?>
                            </td>
                            <td class="text-center align-middle tn-przyciski-akcji">
                                <a href="<?php echo $tn_link_podglad; ?>" class="btn btn-outline-info btn-sm py-0 px-1" data-bs-toggle="tooltip" title="Podgląd">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button type="button" class="btn btn-outline-warning btn-sm py-0 px-1" onclick='if(typeof tnApp !== "undefined" && tnApp.populateEditForm) { tnApp.populateEditForm(<?php echo json_encode($tn_p, JSON_HEX_APOS | JSON_HEX_QUOT); ?>); } else { alert("Błąd tnApp: Brak funkcji edycji."); }' data-bs-toggle="tooltip" title="Edytuj">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="<?php echo $tn_link_usun; ?>" class="btn btn-outline-danger btn-sm py-0 px-1" onclick="return confirm('Czy na pewno usunąć produkt \'<?php echo htmlspecialchars(addslashes($tn_p['name'] ?? ''), ENT_QUOTES); ?>\'? Tej operacji nie można cofnąć.');" data-bs-toggle="tooltip" title="Usuń">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div> <?php // Koniec .table-responsive ?>

    <?php // Paginacja
        if (($tn_ilosc_stron ?? 1) > 1 && !empty($tn_produkty_dane)) {
            $tn_parametry_paginacji = ['sort' => $tn_sortowanie, 'search' => $tn_zapytanie_szukania, 'category' => $tn_kategoria_filtrowania];
            $tn_parametry_paginacji = array_filter($tn_parametry_paginacji); 
            $GLOBALS['tn_parametry_sort_filter'] = $tn_parametry_paginacji;
                    $pagination_template_path = TN_SCIEZKA_TEMPLATEK . 'partials/tn_pagination.php';
            if (file_exists($pagination_template_path)) { include $pagination_template_path; }
            else { echo '<p class="text-danger text-center my-3 small">Błąd: Brak szablonu paginacji.</p>'; }
        }
    ?>
</div> 