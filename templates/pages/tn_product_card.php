<?php
/**
 * Karta produktu (do listy)
 * @var array $produkt
 * @var string $tn_waluta
 */
 // Funkcja powinna być już zdefiniowana w pliku nadrzędnym (home.php)
$tn_sciezka_obr_karta = function_exists('tn_pobierz_sciezke_obrazka_public')
    ? tn_pobierz_sciezke_obrazka_public($produkt['image'] ?? null)
    : 'placeholder.svg'; // Fallback
$tn_link_prod = 'index.php?page=produkt&id=' . ($produkt['id'] ?? '');
?>
<div class="card h-100 shadow-sm tn-product-card">
    <a href="<?php echo $tn_link_prod; ?>" class="text-center p-2">
        <img src="<?php echo $tn_sciezka_obr_karta; ?>" class="card-img-top tn-product-card-img" alt="<?php echo htmlspecialchars($produkt['name'] ?? ''); ?>">
    </a>
    <div class="card-body d-flex flex-column p-3">
        <h6 class="card-title fs-sm mb-1">
            <a href="<?php echo $tn_link_prod; ?>" class="text-decoration-none stretched-link"><?php echo htmlspecialchars($produkt['name'] ?? 'Brak nazwy'); ?></a>
        </h6>
        <p class="card-text small text-muted mb-2"><?php echo htmlspecialchars($produkt['producent'] ?? ''); ?></p>
        <div class="mt-auto">
            <p class="card-text fw-bold mb-1"><?php echo number_format($produkt['price'] ?? 0, 2, ',', ' '); ?> <?php echo htmlspecialchars($tn_waluta); ?></p>
            <?php if (($produkt['stock'] ?? 0) > 0): ?>
                <small class="text-success"><i class="bi bi-check-circle-fill"></i> Dostępny</small>
            <?php else: ?>
                <small class="text-danger"><i class="bi bi-x-circle-fill"></i> Brak</small>
            <?php endif; ?>
        </div>
    </div>
</div>