<?php
// templates/pages/tn_vehicles_list.php
/**
 * Szablon strony wyświetlającej listę unikalnych pojazdów z bazy produktów.
 *
 * Oczekuje zmiennych z index.php:
 * @var array $tn_lista_pojazdow Lista unikalnych pojazdów (Make/Model => [wersje]).
 *
 * Zakłada dostępność funkcji pomocniczych:
 * tn_generuj_url()
 *
 * Wymaga Bootstrap 5 CSS/JS i Bootstrap Icons.
 */

// --- Sprawdzenie danych ---
// Zapobiegaj bezpośredniemu dostępowi do pliku szablonu
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403); // Ustaw kod błędu 403 Forbidden
    die('Access Denied');
}

// Zakładamy, że $tn_lista_pojazdow jest przygotowana w index.php
// Upewnij się, że zmienna istnieje i jest tablicą
$vehiclesGroupedByMakeModel = $tn_lista_pojazdow ?? [];

// --- Koniec przygotowania danych ---
?>

<div class="container-fluid px-lg-4 py-4">
    <div class="card shadow-sm tn-vehicles-list mb-4">
        <div class="card-header bg-light py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <?php // Nagłówek ?>
                <h5 class="mb-0"><i class="bi bi-car-front-fill me-2"></i>Baza Pojazdów</h5>
              
                <a href="<?php echo function_exists('tn_generuj_url') ? htmlspecialchars(tn_generuj_url('add_vehicle'), ENT_QUOTES, 'UTF-8') : '#'; ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> Dodaj nowy pojazd
                </a>
               
            </div>
        </div>
        <div class="card-body p-4">

            <?php if (empty($vehiclesGroupedByMakeModel)): ?>
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle me-2"></i> Brak danych o pojazdach w bazie produktów.
                </div>
            <?php else: ?>
                <div class="accordion tn-vehicle-database-accordion" id="vehicleDatabaseAccordion">
                    <?php $accordion_item_index = 0; ?>
                    <?php foreach ($vehiclesGroupedByMakeModel as $makeModel => $versions): ?>
                        <?php if (!empty($versions)): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading<?php echo $accordion_item_index; ?>">
                                    <button class="accordion-button <?php echo ($accordion_item_index === 0) ? '' : 'collapsed'; ?> py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $accordion_item_index; ?>" aria-expanded="<?php echo ($accordion_item_index === 0) ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $accordion_item_index; ?>">
                                        <i class="bi bi-car-front me-2"></i><?php echo $makeModel; ?>
                                        <span class="badge bg-secondary ms-2 rounded-pill"><?php echo count($versions); ?> wersji</span>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $accordion_item_index; ?>" class="accordion-collapse collapse <?php echo ($accordion_item_index === 0) ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $accordion_item_index; ?>" data-bs-parent="#vehicleDatabaseAccordion">
                                    <div class="accordion-body small">
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($versions as $version): ?>
                                                <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-0 bg-transparent border-bottom-dashed">
                                                    <div class="flex-grow-1 me-3">
                                                        <div class="fw-bold"><?php echo $version['name']; ?> <span class="font-monospace text-muted small">(<?php echo $version['code']; ?>)</span></div>
                                                        <div class="text-muted small">
                                                            Poj: <?php echo $version['capacity']; ?> ccm³ |
                                                            Moc: <?php echo $version['kw']; ?> kW (<?php echo $version['hp']; ?> KM) |
                                                            Rocznik: <?php echo $version['year_start']; ?> - <?php echo $version['year_end']; ?>
                                                        </div>
                                                    </div>
                                                    <?php // Można dodać przyciski akcji dla wersji pojazdu tutaj ?>
                                                  
                                                    <div class="flex-shrink-0">
                                                        <a href="#" class="btn btn-outline-primary btn-sm" title="Edytuj wersję"><i class="bi bi-pencil"></i></a>
                                                        <a href="#" class="btn btn-outline-danger btn-sm" title="Usuń wersję"><i class="bi bi-trash"></i></a>
                                                    </div>
                                                   
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php $accordion_item_index++; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div> <?php // Koniec card-body ?>
    </div> <?php // Koniec card ?>
</div> <?php // Koniec container-fluid ?>

<?php // --- Style CSS specyficzne dla tej strony (opcjonalne) --- ?>
<style>
/* Dodaj lub dostosuj style specyficzne dla tej strony */
.tn-vehicle-database-accordion .accordion-button {
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
}
.tn-vehicle-database-accordion .accordion-body {
    padding-top: 0;
    padding-bottom: 0;
}
.tn-vehicle-database-accordion .list-group-item.border-bottom-dashed {
    border-bottom-style: dashed !important;
}
</style>

<?php // --- Skrypt JS (opcjonalny) --- ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Skrypty specyficzne dla tej strony (jeśli są potrzebne)
    // Np. inicjalizacja tooltipów dla elementów w akordeonie po rozwinięciu
    const vehicleAccordion = document.getElementById('vehicleDatabaseAccordion');
    if (vehicleAccordion) {
        vehicleAccordion.addEventListener('shown.bs.collapse', function (event) {
            // Inicjalizuj tooltipy w rozwiniętym akordeonie
            const tooltipTriggerList = [].slice.call(event.target.querySelectorAll('[data-bs-toggle="tooltip"]'));
             tooltipTriggerList.map(function (tooltipTriggerEl) {
                 const existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                 if (existingTooltip) {
                     existingTooltip.dispose();
                 }
                 return new bootstrap.Tooltip(tooltipTriggerEl);
             });
        });
    }

    // Inicjalizacja tooltipów dla elementów poza akordeonem (np. przyciski w nagłówku)
    const staticTooltipTriggerList = [].slice.call(document.querySelectorAll('.tn-vehicles-list > .card-header [data-bs-toggle="tooltip"]'));
    staticTooltipTriggerList.map(function (tooltipTriggerEl) {
        const existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
        if (existingTooltip) {
            existingTooltip.dispose();
        }
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
