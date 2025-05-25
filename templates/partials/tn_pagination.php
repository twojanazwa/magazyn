<?php
// templates/partials/tn_pagination.php
/**
 * Oczekuje zmiennych z widoku nadrzędnego:
 * @var int $tn_ilosc_stron
 * @var int $tn_biezaca_strona
 * @var string $tn_biezaca_strona_ident ('products' lub 'orders')
 * @var array $tn_parametry_sort_filter (Tablica z aktywnymi filtrami/sortowaniem, np. ['sort' => 'date_desc', 'status' => 'Nowe', 'category' => 'Opony'])
 */

$tn_strona_dla_paginacji = $tn_biezaca_strona_ident ?? 'products'; // Domyślnie produkty
$tn_parametry_sort_filter = $tn_parametry_sort_filter ?? []; // Inicjalizuj, jeśli nie przekazano

if (($tn_ilosc_stron ?? 1) > 1):

    $tn_link_strony_paginacji = function($tn_nr_strony) use ($tn_strona_dla_paginacji, $tn_parametry_sort_filter) {
        $tn_parametry_url = $tn_parametry_sort_filter; // Zacznij od istniejących filtrów/sortowania
        // Dodaj numer strony do parametrów dla funkcji generującej URL
        if ($tn_nr_strony > 1) {
            $tn_parametry_url['p'] = $tn_nr_strony;
        } else {
            unset($tn_parametry_url['p']); // Usuń 'p' dla pierwszej strony
        }
        return tn_generuj_url($tn_strona_dla_paginacji, $tn_parametry_url);
    };
?>
<nav aria-label="Nawigacja po stronach">
    <ul class="pagination pagination-sm justify-content-center tn-paginacja mt-3">
        <?php // Przyciski Poprzednia/Następna i numery stron (logika bez zmian, używa $tn_link_strony_paginacji) ?>
         <li class="page-item <?php echo ($tn_biezaca_strona <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="<?php echo ($tn_biezaca_strona > 1) ? $tn_link_strony_paginacji($tn_biezaca_strona - 1) : '#'; ?>" aria-label="Poprzednia">&laquo;</a></li>
         <?php $tn_okno = 2; $tn_start = max(1, $tn_biezaca_strona - $tn_okno); $tn_koniec = min($tn_ilosc_stron, $tn_biezaca_strona + $tn_okno); if ($tn_start > 1) { echo '<li class="page-item"><a class="page-link" href="' . $tn_link_strony_paginacji(1) . '">1</a></li>'; if ($tn_start > 2) { echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; } } for ($i = $tn_start; $i <= $tn_koniec; $i++): ?><li class="page-item <?php if ($i == $tn_biezaca_strona) echo 'active'; ?>" <?php if ($i == $tn_biezaca_strona) echo 'aria-current="page"'; ?>><a class="page-link" href="<?php echo $tn_link_strony_paginacji($i); ?>"><?php echo $i; ?></a></li><?php endfor; if ($tn_koniec < $tn_ilosc_stron) { if ($tn_koniec < $tn_ilosc_stron - 1) { echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; } echo '<li class="page-item"><a class="page-link" href="' . $tn_link_strony_paginacji($tn_ilosc_stron) . '">' . $tn_ilosc_stron . '</a></li>'; } ?>
         <li class="page-item <?php echo ($tn_biezaca_strona >= $tn_ilosc_stron) ? 'disabled' : ''; ?>"><a class="page-link" href="<?php echo ($tn_biezaca_strona < $tn_ilosc_stron) ? $tn_link_strony_paginacji($tn_biezaca_strona + 1) : '#'; ?>" aria-label="Następna">&raquo;</a></li>
    </ul>
</nav>
<?php endif; ?>