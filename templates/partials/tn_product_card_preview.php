<?php
// templates/partials/tn_product_card_preview.php
/**
 * Uproszczona karta produktu do wyświetlania w sekcjach takich jak "Podobne produkty".
 * Wersja: 1.0
 *
 * Oczekuje zmiennych:
 * @var array $produkt Dane produktu do wyświetlenia.
 * @var array $tn_ustawienia_globalne (Opcjonalnie, dla waluty)
 */

// Podstawowe sprawdzenie, czy zmienna $produkt istnieje i jest tablicą
if (!isset($produkt) || !is_array($produkt)) {
    echo '<div class="alert alert-danger small">Błąd: Brak danych produktu dla karty podglądu.</div>';
    return; // Zakończ, jeśli brak danych
}

// Helpery - zakładamy, że są załadowane globalnie lub załaduj je tutaj
if (!function_exists('tn_generuj_url')) {
    // Minimalny fallback, jeśli helper nie istnieje
    function tn_generuj_url(string $pageId, array $params = []): string {
        return '?page=' . urlencode($pageId) . ($params ? '&' . http_build_query($params) : '');
    }
}
if (!function_exists('tn_pobierz_sciezke_obrazka')) {
    // Minimalny fallback
    function tn_pobierz_sciezke_obrazka(?string $filename): string {
        $basePath = 'uploads/images/';
        $placeholder = 'assets/img/placeholder.svg';
        return ($filename && file_exists($basePath . $filename)) ? $basePath . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') : $placeholder;
    }
}

// Przygotowanie danych produktu (zabezpieczone)
$productId = intval($produkt['id'] ?? 0);
$productName = htmlspecialchars($produkt['name'] ?? 'Brak nazwy', ENT_QUOTES, 'UTF-8');
$productPrice = floatval($produkt['price'] ?? 0);
$productStock = intval($produkt['stock'] ?? 0);
$productImage = $produkt['image'] ?? null;
$unit = htmlspecialchars($produkt['tn_jednostka_miary'] ?? 'szt.', ENT_QUOTES, 'UTF-8');
$currency = htmlspecialchars($tn_ustawienia_globalne['waluta'] ?? 'PLN', ENT_QUOTES, 'UTF-8');

// Generowanie URL-i
$productUrl = tn_generuj_url('product_preview', ['id' => $productId]);
$imageUrl = tn_pobierz_sciezke_obrazka($productImage);

?>
<div class="card h-100 shadow-sm tn-product-card-preview">
    <a href="<?php echo htmlspecialchars($productUrl, ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none">
        <img src="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>" class="card-img-top tn-product-card-img" alt="<?php echo $productName; ?>" loading="lazy">
    </a>
    <div class="card-body d-flex flex-column p-2">
        <h6 class="card-title small mb-1 flex-grow-1">
            <a href="<?php echo htmlspecialchars($productUrl, ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none stretched-link tn-product-link" title="<?php echo $productName; ?>">
                <?php echo htmlspecialchars(mb_strimwidth($productName, 0, 55, '...'), ENT_QUOTES, 'UTF-8'); // Skróć nazwę ?>
            </a>
        </h6>
        <p class="card-text small text-muted mb-1">ID: <?php echo $productId; ?></p>
        <p class="card-text fw-bold text-primary mb-1"><?php echo number_format($productPrice, 2, ',', ' '); ?> <?php echo $currency; ?></p>
        <?php if ($productStock > 0): ?>
            <small class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Dostępny</small>
        <?php else: ?>
            <small class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>Niedostępny</small>
        <?php endif; ?>
    </div>
</div>

<style>
/* Style specyficzne dla tej karty - można przenieść do globalnego CSS */
.tn-product-card-preview .tn-product-card-img {
    height: 130px; /* Stała wysokość obrazka */
    object-fit: contain; /* Skalowanie z zachowaniem proporcji */
    padding: 0.5rem;
    background-color: #fff; /* Tło dla przeźroczystych PNG */
}
.tn-product-card-preview .card-body {
    font-size: 0.85rem; /* Mniejsza czcionka w body */
}
.tn-product-card-preview .card-title {
    font-size: 0.9rem; /* Mniejszy tytuł */
    font-weight: 500;
}
.tn-product-card-preview .tn-product-link {
    color: var(--bs-body-color); /* Kolor tekstu linku */
    transition: color 0.2s ease;
}
.tn-product-card-preview .tn-product-link:hover {
    color: var(--bs-primary); /* Kolor linku po najechaniu */
}
</style>
