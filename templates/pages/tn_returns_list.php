<?php
// templates/pages/tn_returns_list.php
/**
 * Widok listy zgłoszeń zwrotów/reklamacji.
 * Wersja: 1.2 (Poprawka klucza 'default', integracja paginacji, sortowania, linków)
 *
 * Oczekuje zmiennych z index.php:
 * @var array|null $tn_zwroty_dane Wynik z tn_przetworz_liste_zwrotow() (lub null). Zawiera klucze:
 * 'zwroty_wyswietlane', 'ilosc_wszystkich', 'ilosc_stron', 'biezaca_strona', 'sortowanie'.
 * @var array $tn_ustawienia_globalne Ustawienia globalne.
 * @var array $tn_produkty Tablica wszystkich produktów (do mapowania nazw).
 * @var string $tn_token_csrf Token CSRF (używany przez helpery linków akcji).
 */

// Bezpieczne rozpakowanie danych z logiki przetwarzania
$tn_zwroty_wyswietlane = $tn_zwroty_dane['zwroty_wyswietlane'] ?? [];
$tn_ilosc_wszystkich = $tn_zwroty_dane['ilosc_wszystkich'] ?? 0;
$tn_ilosc_stron = $tn_zwroty_dane['ilosc_stron'] ?? 1;
$tn_biezaca_strona = $tn_zwroty_dane['biezaca_strona'] ?? 1;
$tn_sortowanie = $tn_zwroty_dane['sortowanie'] ?? 'date_desc';
// TODO: Pobierz filtry z $tn_zwroty_dane, jeśli zostaną zaimplementowane

// Przygotuj mapę produktów dla szybkich odnośników do nazw
$tn_mapa_produktow_nazwy = array_column($tn_produkty ?? [], 'name', 'id');

// Ustawienia wyglądu tabeli i formatowania daty
$tn_tabela_paskowana = $tn_ustawienia_globalne['wyglad']['tn_tabela_paskowana'] ?? true;
$tn_tabela_krawedzie = $tn_ustawienia_globalne['wyglad']['tn_tabela_krawedzie'] ?? true;
$tn_format_daty = $tn_ustawienia_globalne['tn_format_daty'] ?? 'd.m.Y'; // Tylko data dla listy

// Mapa statusów zgłoszeń na klasy CSS dla badge'y
// Upewnij się, że 'default' istnieje
$tn_status_mapa_klas = [
    'Nowe zgłoszenie'          => 'text-bg-primary',
    'W trakcie rozpatrywania'  => 'text-bg-info',
    'Oczekuje na zwrot towaru' => 'text-bg-warning',
    'Zaakceptowana'            => 'text-bg-success',
    'Odrzucona'                => 'text-bg-danger',
    'Zakończona'               => 'text-bg-secondary',
    'default'                  => 'text-bg-light text-dark border' // Domyślna dla nieznanych statusów
];

// Identyfikator bieżącej strony dla paginacji i linków sortowania
$tn_biezaca_strona_ident = 'returns_list';

?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h1 class="h4 mb-0 me-2 d-inline-block align-middle"><i class="bi bi-arrow-return-left me-2"></i>Zwroty i Reklamacje</h1>
        <span class="badge bg-secondary rounded-pill align-middle"><?php echo $tn_ilosc_wszystkich; ?></span>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <?php // TODO: Miejsce na przyciski filtrowania (np. dropdown statusu) ?>
        <a href="<?php echo tn_generuj_url('return_form_new'); ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Dodaj Nowe Zgłoszenie
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table <?php echo $tn_tabela_paskowana ? 'table-striped' : ''; ?> <?php echo $tn_tabela_krawedzie ? 'table-bordered' : ''; ?> table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th class="text-center"><?php echo tn_generuj_link_sortowania_zwrotow('id', 'ID'); ?></th>
                    <th><?php echo tn_generuj_link_sortowania_zwrotow('date', 'Data Zgł.'); ?></th>
                    <th class="text-center"><?php echo tn_generuj_link_sortowania_zwrotow('type', 'Typ'); ?></th>
                    <th>Produkt (ID)</th> <?php // Sortowanie po ID produktu w logice ?>
                    <th><?php echo tn_generuj_link_sortowania_zwrotow('customer', 'Klient'); ?></th>
                    <th class="text-center"><?php echo tn_generuj_link_sortowania_zwrotow('order_id', 'Zam. #'); ?></th>
                    <th>Powód</th>
                    <th class="text-center"><?php echo tn_generuj_link_sortowania_zwrotow('status', 'Status'); ?></th>
                    <th class="text-center" style="width: 120px;">Akcje</th> <?php // Szerokość dla 3 przycisków ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tn_zwroty_wyswietlane)): ?>
                    <tr>
                        <td colspan="9" class="text-center p-4 text-muted fst-italic"> <?php // Zwiększono colspan ?>
                            Brak zgłoszeń zwrotów lub reklamacji<?php echo !empty($tn_get_params['status']) ? ' o statusie '.htmlspecialchars($tn_get_params['status']) : ''; ?>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tn_zwroty_wyswietlane as $zgłoszenie): ?>
                        <?php
                            // Przygotowanie danych wiersza
                            $produkt_id = $zgłoszenie['product_id'] ?? null;
                            $nazwa_produktu = $tn_mapa_produktow_nazwy[$produkt_id] ?? '?'; // Pobierz nazwę z mapy
                            $status = $zgłoszenie['status'] ?? 'Nieznany';
                            $klasa_badge = $tn_status_mapa_klas[$status] ?? $tn_status_mapa_klas['default']; // Użycie mapy z kluczem default

                            // Generowanie linków za pomocą helperów
                            // Ważne: ID jest przekazywane jako parametr, a nie część ścieżki dla tn_generuj_url
                            $link_edycji = tn_generuj_url('return_form_edit', ['id' => $zgłoszenie['id'] ?? 0]);
                            $link_podgladu_zwrotu = tn_generuj_url('return_preview', ['id' => $zgłoszenie['id'] ?? 0]);
                            $link_podgladu_zam = isset($zgłoszenie['order_id']) ? tn_generuj_url('order_preview', ['id' => $zgłoszenie['order_id']]) : '#';
                            $link_podgladu_prod = $produkt_id ? tn_generuj_url('product_preview', ['id' => $produkt_id]) : '#';
                        ?>
                        <tr>
                            <td class="text-center text-muted"><?php echo htmlspecialchars($zgłoszenie['id'] ?? '?'); ?></td>
                            <td class="text-nowrap">
                                <?php // Bezpieczne formatowanie daty
                                try { echo !empty($zgłoszenie['date_created']) ? (new DateTime($zgłoszenie['date_created']))->format($tn_format_daty) : '-'; } catch (Exception $e) { echo '-';}
                                ?>
                            </td>
                            <td class="text-center">
                                <?php echo ($zgłoszenie['type'] ?? '') === 'zwrot' ? '<span class="badge bg-warning text-dark">Zwrot</span>' : '<span class="badge bg-danger">Reklamacja</span>'; ?>
                            </td>
                            <td>
                                <?php if($produkt_id): ?>
                                    <a href="<?php echo $link_podgladu_prod; ?>" title="<?php echo htmlspecialchars($nazwa_produktu); ?>">
                                        <?php echo htmlspecialchars(mb_strimwidth($nazwa_produktu, 0, 35, '...')); // Skrócona nazwa ?>
                                    </a>
                                    <small class="text-muted">(<?php echo $produkt_id; ?>)</small>
                                <?php else: echo '?'; endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($zgłoszenie['customer_name'] ?? '-'); ?></td>
                            <td class="text-center">
                                <?php if($id_zam = $zgłoszenie['order_id'] ?? null): ?>
                                    <a href="<?php echo $link_podgladu_zam; ?>">#<?php echo htmlspecialchars($id_zam); ?></a>
                                <?php else: echo '?'; endif; ?>
                            </td>
                            <td class="text-truncate" style="max-width: 180px;" title="<?php echo htmlspecialchars($zgłoszenie['reason'] ?? ''); ?>">
                                <?php echo htmlspecialchars(mb_strimwidth($zgłoszenie['reason'] ?? '-', 0, 45, '...')); // Skrócony powód ?>
                            </td>
                            <td class="text-center">
                                <span class="badge <?php echo $klasa_badge; ?>"><?php echo htmlspecialchars($status); ?></span>
                            </td>
                            <td class="text-center tn-przyciski-akcji">
                                <a href="<?php echo $link_podgladu_zwrotu; ?>" class="btn btn-outline-info btn-sm py-0 px-1" data-bs-toggle="tooltip" title="Podgląd szczegółów zgłoszenia">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?php echo $link_edycji; ?>" class="btn btn-outline-warning btn-sm py-0 px-1" data-bs-toggle="tooltip" title="Edytuj / Rozpatrz zgłoszenie">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php // TODO: Przycisk usuwania (jeśli potrzebny) ?>
                                <?php /*
                                <a href="<?php echo tn_generuj_link_akcji_get('delete_return', ['id' => $zgłoszenie['id'] ?? 0]); ?>" class="btn btn-outline-danger btn-sm py-0 px-1" onclick="return confirm('...');" data-bs-toggle="tooltip" title="Usuń zgłoszenie"><i class="bi bi-trash"></i></a>
                                */ ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div> <?php // Koniec .table-responsive ?>

    <?php // Paginacja dla listy zwrotów
        // Sprawdź, czy są dane i więcej niż 1 strona
        if (!empty($tn_zwroty_dane) && ($tn_ilosc_stron ?? 1) > 1) {
            // Przygotuj parametry do zachowania w linkach paginacji
            $tn_parametry_paginacji = ['sort' => $tn_sortowanie];
            // TODO: Dodać aktywne filtry do $tn_parametry_paginacji
            // Przykład: if (!empty($aktywny_filtr_statusu)) $tn_parametry_paginacji['status'] = $aktywny_filtr_statusu;
            $tn_parametry_paginacji = array_filter($tn_parametry_paginacji); // Usuń puste

            // Udostępnij parametry dla szablonu paginacji
            $GLOBALS['tn_parametry_sort_filter'] = $tn_parametry_paginacji;
            // Identyfikator bieżącej strony jest już ustawiony ($tn_biezaca_strona_ident)

            // Dołącz szablon paginacji
            $pagination_template_path = TN_SCIEZKA_TEMPLATEK . 'partials/tn_pagination.php';
            if (file_exists($pagination_template_path)) {
                // Zmienne $tn_ilosc_stron i $tn_biezaca_strona są dostępne z tego zakresu
                include $pagination_template_path;
            } else {
                 echo '<p class="text-danger text-center my-3 small">Błąd: Brak szablonu paginacji.</p>';
            }
        }
    ?>
</div> <?php // Koniec .card ?>