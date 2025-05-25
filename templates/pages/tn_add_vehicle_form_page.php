<?php
// templates/pages/tn_add_vehicle_form.php
/**
 * Szablon formularza do dodawania nowego pojazdu do centralnej bazy.
 */
global $tn_token_csrf; // Upewnij się, że token CSRF jest dostępny globalnie lub przekazany

$form_data = $_SESSION['tn_form_data'] ?? [];
unset($_SESSION['tn_form_data']); 

$linkToVehiclesList = function_exists('tn_generuj_url') ? htmlspecialchars(tn_generuj_url('vehicles_list_page'), ENT_QUOTES, 'UTF-8') : '#';
?>
<div class="container-fluid px-lg-4 py-4">
    <div class="card shadow-sm tn-add-vehicle-form mb-4">
        <div class="card-header bg-light py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-plus-circle-dotted me-2"></i>Dodaj Nowy Pojazd do Bazy</h5>
                <a href="<?php echo $linkToVehiclesList; ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-list-ul me-1"></i> Wróć do Bazy Pojazdów
                </a>
            </div>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="/dodaj-pojazd" class="needs-validation" novalidate>
                <input type="hidden" name="tn_csrf_token" value="<?php echo htmlspecialchars($tn_token_csrf ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="make" class="form-label">Marka <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="make" name="make" value="<?php echo htmlspecialchars($form_data['make'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        <div class="invalid-feedback">Marka jest wymagana.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="model" class="form-label">Model <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="model" name="model" value="<?php echo htmlspecialchars($form_data['model'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        <div class="invalid-feedback">Model jest wymagany.</div>
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="mb-3">Dane Wersji Silnikowej (opcjonalne, jeśli dotyczy)</h6>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="version_name" class="form-label">Nazwa wersji (np. "1.8 16V (F07)")</label>
                        <input type="text" class="form-control" id="version_name" name="version_name" value="<?php echo htmlspecialchars($form_data['version_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="version_code" class="form-label">Kod wersji/silnika (np. "Z 18 XE")</label>
                        <input type="text" class="form-control" id="version_code" name="version_code" value="<?php echo htmlspecialchars($form_data['version_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="capacity" class="form-label">Pojemność (cm³)</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" value="<?php echo htmlspecialchars($form_data['capacity'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" min="0">
                    </div>
                    <div class="col-md-4">
                        <label for="kw" class="form-label">Moc (kW)</label>
                        <input type="number" step="0.1" class="form-control" id="kw" name="kw" value="<?php echo htmlspecialchars($form_data['kw'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" min="0">
                    </div>
                    <div class="col-md-4">
                        <label for="hp" class="form-label">Moc (KM)</label>
                        <input type="number" step="0.1" class="form-control" id="hp" name="hp" value="<?php echo htmlspecialchars($form_data['hp'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" min="0">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="year_start" class="form-label">Rok produkcji OD</label>
                        <input type="number" class="form-control" id="year_start" name="year_start" placeholder="RRRR" value="<?php echo htmlspecialchars($form_data['year_start'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" min="1900" max="<?php echo date('Y') + 5; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="year_end" class="form-label">Rok produkcji DO (zostaw puste jeśli aktualny)</label>
                        <input type="number" class="form-control" id="year_end" name="year_end" placeholder="RRRR" value="<?php echo htmlspecialchars($form_data['year_end'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" min="1900" max="<?php echo date('Y') + 6; ?>">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Dodaj Pojazd</button>
                    <a href="<?php echo $linkToVehiclesList; ?>" class="btn btn-secondary"><i class="bi bi-x-lg me-1"></i> Anuluj</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
  'use strict'
  var forms = document.querySelectorAll('.needs-validation')
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }
        form.classList.add('was-validated')
      }, false)
    })
})()
</script>