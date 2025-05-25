<?php
/**
 * Strona szczegółów produktu
 * @var array $produkt
 * @var string $tn_waluta
 */

// Użyj tej samej funkcji co w home.php
if (!function_exists('tn_pobierz_sciezke_obrazka_public')) {
     function tn_pobierz_sciezke_obrazka_public(?string $tn_nazwa_obrazka_lub_url): string { if (filter_var($tn_nazwa_obrazka_lub_url, FILTER_VALIDATE_URL)) { return htmlspecialchars($tn_nazwa_obrazka_lub_url, ENT_QUOTES, 'UTF-8'); } if (!empty($tn_nazwa_obrazka_lub_url) && defined('TN_SHARED_UPLOADS_URL_PATH') && TN_SHARED_UPLOADS_URL_PATH !== '') { $tn_nazwa_pliku = basename($tn_nazwa_obrazka_lub_url); if (defined('TN_SHARED_UPLOADS_PATH') && TN_SHARED_UPLOADS_PATH !== '' && file_exists(TN_SHARED_UPLOADS_PATH . $tn_nazwa_pliku)) { return TN_SHARED_UPLOADS_URL_PATH . rawurlencode($tn_nazwa_pliku); } } return 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22100%22%20height%3D%22100%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20100%20100%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_tn%20text%20%7B%20fill%3A%23aaa%3Bfont-weight%3Abold%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A14pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_tn%22%3E%3Crect%20width%3D%22100%22%20height%3D%22100%22%20fill%3D%22%23eee%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2222%22%20y%3D%2255%22%3ENo%20IMG%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E'; }
}
$tn_sciezka_obr = tn_pobierz_sciezke_obrazka_public($produkt['image'] ?? null);
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php">Strona Główna</a></li>
    <?php if (!empty($produkt['category'])): ?>
    <li class="breadcrumb-item"><a href="index.php?page=kategoria&nazwa=<?php echo urlencode($produkt['category']); ?>"><?php echo htmlspecialchars($produkt['category']); ?></a></li>
    <?php endif; ?>
    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($produkt['name']); ?></li>
  </ol>
</nav>

<div class="row g-4">
    <div class="col-md-5">
        <img src="<?php echo $tn_sciezka_obr; ?>" class="img-fluid rounded border shadow-sm" alt="<?php echo htmlspecialchars($produkt['name']); ?>">
    </div>
    <div class="col-md-7">
        <h1><?php echo htmlspecialchars($produkt['name']); ?></h1>
        <p class="text-muted">Producent: <?php echo htmlspecialchars($produkt['producent'] ?? '-'); ?></p>
        <?php if (!empty($produkt['tn_numer_katalogowy'])): ?>
        <p class="text-muted small">Nr kat.: <span class="font-monospace"><?php echo htmlspecialchars($produkt['tn_numer_katalogowy']); ?></span></p>
        <?php endif; ?>

        <h3 class="text-primary my-3"><?php echo number_format($produkt['price'] ?? 0, 2, ',', ' '); ?> <?php echo htmlspecialchars($tn_waluta); ?></h3>

        <p><strong>Dostępność:</strong>
            <?php if (($produkt['stock'] ?? 0) > 0): ?>
                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Dostępny (<?php echo $produkt['stock']; ?> szt.)</span>
            <?php else: ?>
                 <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Niedostępny</span>
            <?php endif; ?>
        </p>

        <?php if (!empty($produkt['desc'])): ?>
        <div class="mt-4">
            <h5>Opis</h5>
            <p><?php echo nl2br(htmlspecialchars($produkt['desc'])); ?></p>
        </div>
        <?php endif; ?>

         <?php if (!empty($produkt['spec'])): ?>
        <div class="mt-4">
            <h5>Specyfikacja</h5>
            <p><?php echo nl2br(htmlspecialchars($produkt['spec'])); ?></p>
        </div>
        <?php endif; ?>

        <?php // TODO: Dodać przycisk "Dodaj do koszyka" jeśli będzie taka funkcjonalność ?>
        <a href="index.php" class="btn btn-outline-secondary mt-4"><i class="bi bi-arrow-left"></i> Wróć do katalogu</a>
    </div>
</div>