<?php
// templates/pages/tn_manage_vehicles.php
/**
 * Szablon strony/modala do zarządzania powiązanymi pojazdami dla produktu.
 *
 * Oczekuje zmiennych z index.php:
 * @var array|null $tn_produkt_do_edycji Dane produktu do edycji (musi zawierać 'id' i 'vehicle').
 * @var string $tn_token_csrf Aktualny token CSRF.
 * @var array $tn_ustawienia_globalne Załadowane ustawienia globalne.
 *
 * Zakłada dostępność funkcji pomocniczych:
 * tn_generuj_url(), tn_generuj_link_akcji_post(), tn_ustaw_komunikat_flash()
 */

// --- Sprawdzenie danych i inicjalizacja ---
// Zapobiegaj bezpośredniemu dostępowi do pliku szablonu
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403); // Ustaw kod błędu 403 Forbidden
    die('Access Denied');
}

// Definiuj stałe ścieżek jako fallback, jeśli nie zdefiniowano ich wcześniej
defined('TN_SCIEZKA_SRC') or define('TN_SCIEZKA_SRC', __DIR__ . '/../../src/'); // Definiuj jako fallback
defined('TN_SCIEZKA_TEMPLATEK') or define('TN_SCIEZKA_TEMPLATEK', __DIR__ . '/'); // Definiuj jako fallback

// Załaduj helpery, jeśli nie są już dostępne (powinny być z index.php)
require_once TN_SCIEZKA_SRC . 'functions/tn_url_helpers.php';
require_once TN_SCIEZKA_SRC . 'functions/tn_security_helpers.php';
require_once TN_SCIEZKA_SRC . 'functions/tn_flash_messages.php'; // Jeśli używasz flash messages

// Sprawdź, czy dane produktu zostały przekazane i są poprawne
if (empty($tn_produkt_do_edycji) || !is_array($tn_produkt_do_edycji) || !isset($tn_produkt_do_edycji['id'])) {
    $redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('products') : '/produkty';
    if (function_exists('tn_ustaw_komunikat_flash')) {
        tn_ustaw_komunikat_flash('Nie można edytować powiązań pojazdów: brak danych produktu.', 'danger');
    } else {
         echo "<p class='alert alert-danger'>Nie można edytować powiązań pojazdów: brak danych produktu.</p>";
    }
    // Przekierowanie po krótkim opóźnieniu
    echo "<script>setTimeout(function(){ window.location.href = '" . addslashes($redirect_url) . "'; }, 3000);</script>";
    exit;
}

// --- Przygotowanie danych do widoku ---
$productId = intval($tn_produkt_do_edycji['id']);
$productName = htmlspecialchars($tn_produkt_do_edycji['name'] ?? 'Brak nazwy', ENT_QUOTES, 'UTF-8');
// Surowe dane o pojazdach z pola 'vehicle'
$vehicleInfoRaw = htmlspecialchars($tn_produkt_do_edycji['vehicle'] ?? '', ENT_QUOTES, 'UTF-8');

// Link powrotny do podglądu produktu
$linkToProductPreview = function_exists('tn_generuj_url') ? htmlspecialchars(tn_generuj_url('product_preview', ['id' => $productId]), ENT_QUOTES, 'UTF-8') : '#';

// Link do akcji zapisującej zmiany (przykład)
// Zakładamy, że istnieje akcja 'save_vehicle_associations' obsługująca POST
$saveActionUrl = function_exists('tn_generuj_url') ? htmlspecialchars(tn_generuj_url('save_vehicle_associations'), ENT_QUOTES, 'UTF-8') : '';

// Token CSRF (zakładamy, że jest przekazany)
$csrfToken = htmlspecialchars($tn_token_csrf ?? '', ENT_QUOTES, 'UTF-8');

?>

<div class="container-fluid px-lg-4 py-4">
    <div class="card shadow-sm tn-manage-vehicles mb-4">
        <div class="card-header bg-light py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <?php // Nagłówek ?>
                <h5 class="mb-0"><i class="bi bi-car-front me-2"></i>Zarządzaj powiązanymi pojazdami dla: <strong><?php echo $productName; ?> (ID: <?php echo $productId; ?>)</strong></h5>
                <a href="<?php echo $linkToProductPreview; ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Wróć do podglądu produktu
                </a>
            </div>
        </div>
        <div class="card-body p-4">

            <?php // Kontener na komunikaty flash - jeśli nie są wyświetlane globalnie ?>
            <div class="tn-flash-container mb-3">
                <?php
                // Przykładowe wyświetlanie komunikatów flash, jeśli nie są obsługiwane centralnie
                // if (function_exists('tn_wyswietl_komunikaty_flash')) {
                //     tn_wyswietl_komunikaty_flash();
                // }
                ?>
            </div>

            <?php // Formularz edycji danych pojazdów ?>
            <form method="POST" action="<?php echo $saveActionUrl; ?>" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="save_vehicle_associations">
                <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                <input type="hidden" name="tn_csrf_token" value="<?php echo $csrfToken; ?>">

                <div class="mb-3">
                    <label for="vehicleInfoRaw" class="form-label">Dane o powiązanych pojazdach (surowy tekst):</label>
                    <textarea class="form-control font-monospace" id="vehicleInfoRaw" name="vehicle_info_raw" rows="15" required><?php echo $vehicleInfoRaw; ?></textarea>
                    <div class="form-text">
                        Wprowadź dane pojazdów w formacie:<br>
                        <code>Marka/Model:<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;Nazwa (Kod), Poj, kW, KM, RRRR.MM - RRRR.MM<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;Inna Nazwa (Inny Kod), Inny Poj, Inne kW, Inne KM, RRRR.MM - RRRR.MM<br>
                        Inna Marka/Model:<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;...</code>
                    </div>
                     <div class="invalid-feedback">
                         Proszę podać dane o powiązanych pojazdach.
                     </div>
                </div>

                <div class="d-grid gap-2 d-md-block">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Zapisz zmiany</button>
                    <a href="<?php echo $linkToProductPreview; ?>" class="btn btn-secondary"><i class="bi bi-x-lg me-1"></i> Anuluj</a>
                </div>
            </form>

            <?php // Opcjonalnie: Sekcja podglądu sparsowanych danych (tylko do wyświetlenia) ?>
            <?php
                // Ponowne parsowanie danych do wyświetlenia pod formularzem
                $tempParsedVehicleData = [];
                if (!empty($vehicleInfoRaw)) {
                     $lines = explode("\n", trim(htmlspecialchars_decode($vehicleInfoRaw, ENT_QUOTES))); // Dekoduj HTML aby parsować oryginalny tekst
                     $currentMakeModel = null;
                     $versionRegex = '/^\s*(.+?)\s+\(([^)]+?)\)\s*,\s*(\d*)\s*,\s*(\d*)\s*,\s*(\d*)\s*,\s*(\d{4}\.\d{2})\s*-\s*(.*?)\s*$/';

                     foreach ($lines as $line) {
                         $trimmedLine = trim($line);
                         if (empty($trimmedLine)) continue;

                         if (str_ends_with($trimmedLine, ':') && !preg_match('/^\s/', $line)) {
                             $currentMakeModel = htmlspecialchars(trim(substr($trimmedLine, 0, -1)), ENT_QUOTES, 'UTF-8');
                             $tempParsedVehicleData[$currentMakeModel] = [];
                         } elseif ($currentMakeModel !== null && preg_match('/^\s+/', $line) && preg_match($versionRegex, $line, $matches)) {
                             $tempParsedVehicleData[$currentMakeModel][] = [
                                 'name' => htmlspecialchars(trim($matches[1] ?? ''), ENT_QUOTES, 'UTF-8'),
                                 'code' => htmlspecialchars(trim($matches[2] ?? ''), ENT_QUOTES, 'UTF-8'),
                                 'capacity' => htmlspecialchars(trim($matches[3] ?? ''), ENT_QUOTES, 'UTF-8') ?: '-',
                                 'kw' => htmlspecialchars(trim($matches[4] ?? ''), ENT_QUOTES, 'UTF-8') ?: '-',
                                 'hp' => htmlspecialchars(trim($matches[5] ?? ''), ENT_QUOTES, 'UTF-8') ?: '-',
                                 'year_start' => htmlspecialchars(trim($matches[6] ?? ''), ENT_QUOTES, 'UTF-8'),
                                 'year_end' => htmlspecialchars(trim($matches[7] ?? ''), ENT_QUOTES, 'UTF-8') ?: 'nadal',
                             ];
                         }
                     }
                }
            ?>
            <?php if (!empty($tempParsedVehicleData)): ?>
            <div class="mt-4 pt-3 border-top">
                <h6 class="mb-3">Podgląd sparsowanych danych:</h6>
                 <div class="accordion tn-vehicle-accordion" id="vehicleAccordionPreview">
                     <?php $accordion_item_index = 0; ?>
                     <?php foreach ($tempParsedVehicleData as $makeModel => $versions): ?>
                         <?php if (!empty($versions)): ?>
                             <div class="accordion-item">
                                 <h2 class="accordion-header" id="previewHeading<?php echo $accordion_item_index; ?>">
                                     <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#previewCollapse<?php echo $accordion_item_index; ?>" aria-expanded="false" aria-controls="previewCollapse<?php echo $accordion_item_index; ?>">
                                         <i class="bi bi-car-front-fill me-2"></i><?php echo $makeModel; ?>
                                     </button>
                                 </h2>
                                 <div id="previewCollapse<?php echo $accordion_item_index; ?>" class="accordion-collapse collapse" aria-labelledby="previewHeading<?php echo $accordion_item_index; ?>" data-bs-parent="#vehicleAccordionPreview">
                                     <div class="accordion-body small">
                                         <?php foreach ($versions as $version): ?>
                                             <div class="tn-vehicle-version-object border-bottom pb-2 mb-2">
                                                 <div class="row g-2">
                                                     <div class="col-12">
                                                         <div class="tn-vehicle-version-label text-muted text-uppercase" style="font-size: 0.7em;">Model:</div>
                                                         <div class="tn-vehicle-version-value"><strong><?php echo $version['name']; ?></strong></div>
                                                     </div>
                                                     <div class="col-md-6 col-lg-3">
                                                         <div class="tn-vehicle-version-label text-muted text-uppercase" style="font-size: 0.7em;">TYP:</div>
                                                         <div class="tn-vehicle-version-value font-monospace"><?php echo $version['code']; ?></div>
                                                     </div>
                                                     <div class="col-md-6 col-lg-3">
                                                         <div class="tn-vehicle-version-label text-muted text-uppercase" style="font-size: 0.7em;">ccm³:</div>
                                                         <div class="tn-vehicle-version-value"><?php echo $version['capacity']; ?></div>
                                                     </div>
                                                     <div class="col-md-6 col-lg-3">
                                                         <div class="tn-vehicle-version-label text-muted text-uppercase" style="font-size: 0.7em;">kW:</div>
                                                         <div class="tn-vehicle-version-value"><?php echo $version['kw']; ?></div>
                                                     </div>
                                                     <div class="col-md-6 col-lg-3">
                                                         <div class="tn-vehicle-version-label text-muted text-uppercase" style="font-size: 0.7em;">KM:</div>
                                                         <div class="tn-vehicle-version-value"><?php echo $version['hp']; ?></div>
                                                     </div>
                                                     <div class="col-12">
                                                         <div class="tn-vehicle-version-label text-muted text-uppercase" style="font-size: 0.7em;">Rocznik:</div>
                                                         <div class="tn-vehicle-version-value"><?php echo $version['year_start']; ?> - <?php echo $version['year_end']; ?></div>
                                                     </div>
                                                 </div>
                                             </div>
                                         <?php endforeach; ?>
                                     </div>
                                 </div>
                             </div>
                         <?php endif; ?>
                         <?php $accordion_item_index++; ?>
                     <?php endforeach; ?>
                 </div>
            </div>
            <?php endif; ?>

        </div> <?php // Koniec card-body ?>
    </div> <?php // Koniec card ?>
</div> <?php // Koniec container-fluid ?>

<?php // --- Style CSS specyficzne dla tej strony (opcjonalne) --- ?>
<style>
/* Dodaj lub dostosuj style specyficzne dla tej strony */
.tn-manage-vehicles .form-text {
    font-size: 0.85em;
    color: var(--bs-secondary-color);
}
.tn-manage-vehicles .form-text code {
    font-size: 0.9em;
    color: var(--bs-body-color);
    background-color: var(--bs-light);
    padding: 0.2em 0.4em;
    border-radius: 0.25rem;
}
/* Style dla podglądu sparsowanych danych (jeśli dodano) */
.tn-manage-vehicles .tn-vehicle-version-object {
     margin-bottom: 1rem;
     padding-bottom: 1rem;
     border-bottom: 1px solid var(--bs-border-color);
}
.tn-manage-vehicles .tn-vehicle-version-object:last-child {
     border-bottom: none;
     margin-bottom: 0;
     padding-bottom: 0;
}
.tn-manage-vehicles .tn-vehicle-version-label {
     font-size: 0.7em;
     color: var(--bs-secondary-color);
     text-transform: uppercase;
     margin-bottom: 0.1rem;
}
.tn-manage-vehicles .tn-vehicle-version-value {
     font-size: 0.9em;
     font-weight: bold;
}
.tn-manage-vehicles .tn-vehicle-version-value.font-monospace {
      font-family: var(--bs-font-monospace);
}
</style>

<?php // --- Skrypt JS (opcjonalny) --- ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Inicjalizacja walidacji formularza Bootstrap
    const form = document.querySelector('.tn-manage-vehicles .needs-validation');
    if (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    }

    // Opcjonalnie: JavaScript do dynamicznego parsowania i wyświetlania podglądu w miarę wpisywania
    // To wymaga bardziej zaawansowanego JS i nie jest zawarte w tym podstawowym szablonie.
    // Można by dodać event listener na textarea i aktualizować sekcję podglądu.
});
</script>
