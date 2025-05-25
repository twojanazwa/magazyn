<?php
/**
 * Widok listy kurierów.
 * Wyświetla tabelę z kurierami, umożliwia dodawanie, edycję i usuwanie.
 * Wersja: 1.21 (Poprawki działania modala kurierów, ulepszenia UX)
 *
 * @var array $tn_kurierzy Tablica kurierów (załadowana jako asocjacyjna, klucz to ID).
 * @var string $tn_token_csrf Aktualny token CSRF.
 */

// Upewnij się, że funkcje pomocnicze są dostępne
if (!function_exists('tn_generuj_url')) {
    function tn_generuj_url(string $id, array $params = [])
    {
        /* ... fallback ... */
        return '?page=' . $id . '&' . http_build_query($params);
    }
}
if (!function_exists('tn_generuj_link_akcji_get')) {
    function tn_generuj_link_akcji_get(string $action, array $params = [])
    {
        /* ... fallback ... */
        global $tn_token_csrf;
        $params['action'] = $action;
        $params['tn_csrf_token'] = $tn_token_csrf ?? '';
        return 'index.php?' . http_build_query($params);
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h1 class="h4 mb-0 me-2 d-inline-block align-middle"><i class="bi bi-truck me-2"></i> Zarządzanie Kurierami</h1>
        <?php // Licznik - użyj $tn_kurierzy (załadowana jako asocjacyjna) 
        ?>
        <span class="badge bg-secondary rounded-pill align-middle"><?php echo count($tn_kurierzy ?? []); ?></span>
    </div>
    <?php // Przycisk dodawania - używa JS do otwarcia modala 
    ?>
    <button type="button" class="btn btn-primary btn-sm" onclick="if (typeof tnApp !== 'undefined' && tnApp.openCourierModal) {
        tnApp.openCourierModal();
    } else {
        console.error('tnApp lub tnApp.openCourierModal nie jest zdefiniowane.');
        alert('Błąd inicjalizacji interfejsu.');
    }">
        <i class="bi bi-plus-circle me-1"></i> Dodaj Kuriera
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th style="width: 180px;">ID Kuriera</th> <?php // ID tekstowe 
                    ?>
                    <th>Nazwa Kuriera</th>
                    <th>Wzorzec URL Śledzenia</th>
                    <th class="text-center">Aktywny</th>
                    <th>Notatki</th>
                    <th class="text-center" style="width: 100px;">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tn_kurierzy)) : ?>
                    <tr>
                        <td colspan="6" class="text-center p-4 text-muted fst-italic">
                            Brak zdefiniowanych kurierów. Dodaj pierwszego, klikając przycisk powyżej.
                        </td>
                    </tr>
                <?php else : ?>
                    <?php // Iteracja po tablicy asocjacyjnej (klucz => wartość) 
                    ?>
                    <?php foreach ($tn_kurierzy as $courier_id => $kurier) : ?>
                        <?php
                        // Używamy klucza $courier_id jako tekstowego ID
                        $is_active = $kurier['is_active'] ?? false;
                        // Generuj link usuwania używając $courier_id
                        $link_usun = tn_generuj_link_akcji_get('delete_courier', ['id' => $courier_id]);
                        $nazwa_kuriera_js = htmlspecialchars($kurier['name'] ?? $courier_id, ENT_QUOTES); // Do komunikatu confirm
                        ?>
                        <tr class="<?php echo $is_active ? '' : 'opacity-50'; ?>">
                            <td class="font-monospace text-muted"><?php echo htmlspecialchars($courier_id); ?></td>
                            <td class="fw-medium"><?php echo htmlspecialchars($kurier['name'] ?? '-'); ?></td>
                            <td class="font-monospace text-muted text-truncate" style="max-width: 300px;" title="<?php echo htmlspecialchars($kurier['tracking_url_pattern'] ?? ''); ?>">
                                <?php echo htmlspecialchars($kurier['tracking_url_pattern'] ?? '-'); ?>
                            </td>
                            <td class="text-center">
                                <?php if ($is_active) : ?>
                                    <i class="bi bi-check-circle-fill text-success" data-bs-toggle="tooltip" title="Tak"></i>
                                <?php else : ?>
                                    <i class="bi bi-x-circle-fill text-danger" data-bs-toggle="tooltip" title="Nie"></i>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($kurier['notes'] ?? ''); ?>">
                                <?php echo htmlspecialchars(mb_strimwidth($kurier['notes'] ?? '', 0, 50, '...')); ?>
                            </td>
                            <td class="text-center tn-przyciski-akcji">
                                <?php // Przycisk edycji - przekazuje dane kuriera (w tym jego tekstowe ID) do funkcji JS 
                                ?>
                                <button type="button" class="btn btn-outline-warning btn-sm py-0 px-1" onclick="if (typeof tnApp !== 'undefined' && tnApp.openCourierModal) {
                                            tnApp.openCourierModal(<?php echo json_encode($kurier, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>);
                                        } else {
                                            console.error('Błąd JS: Brak funkcji openCourierModal.');
                                            alert('Błąd interfejsu.');
                                        }" data-bs-toggle="tooltip" title="Edytuj">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php // Link usuwania z poprawnym ID ?>
                                <a href="<?php echo $link_usun; ?>" class="btn btn-outline-danger btn-sm py-0 px-1" onclick="return confirm('Czy na pewno usunąć kuriera \'<?php echo $nazwa_kuriera_js; ?>\' (ID: <?php echo htmlspecialchars($courier_id); ?>)?');" data-bs-toggle="tooltip" title="Usuń">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div> <?php // Koniec .table-responsive 
    ?>
</div> <?php // Koniec .card ?>

<?php // Upewnij się, że modal jest dołączony gdzieś w layoucie (np. w footerze) 
?>
