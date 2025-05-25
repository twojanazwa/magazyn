<?php
// Plik: templates/pages/tn_product_form.php
// Szablon widoku dla formularza dodawania/edycji produktu (pełna strona).
// Dane produktu, kategorie i miejsca magazynowe są przekazywane z index.php.

// Zmienne dostępne w tym szablonie (przekazane z index.php):
// $produkt - tablica z danymi produktu (pusta dla nowego, pełna dla edycji)
// $kategorie - tablica z listą kategorii produktów
// $wszystkie_miejsca_magazynowe - tablica z listą wszystkich miejsc magazynowych (dla selecta)
// $csrf_token - token CSRF
// $miejsce_magazynowe_info - informacje o przypisanym miejscu magazynowym (tylko w trybie edycji, jeśli przekazane)

// Upewnij się, że zmienne zostały przekazane, ustaw domyślne puste wartości, jeśli nie
$produkt = $produkt ?? [];
$kategorie = $kategorie ?? [];
$wszystkie_miejsca_magazynowe = $wszystkie_miejsca_magazynowe ?? [];
$csrf_token = $csrf_token ?? '';
$miejsce_magazynowe_info = $miejsce_magazynowe_info ?? null;


// Określ tryb formularza (dodaj/edytuj)
$is_edit_mode = !empty($produkt['id']); // Sprawdź, czy ID produktu istnieje, aby określić tryb edycji
$form_title = $is_edit_mode ? 'Edytuj Produkt' : 'Dodaj Nowy Produkt';

// Adres URL do akcji zapisywania produktu (plik src/actions/tn_action_save_product.php)
// Formularz wysyła POST do index.php, który routuje do odpowiedniej akcji.
$action_url = tn_generuj_url('produkty', ['akcja' => 'save_product']); // Użyj 'save_product' jako nazwy akcji w pliku action

// Pobierz wartości dla formularza (użyj danych z $produkt lub domyślnych pustych stringów/0)
$id = htmlspecialchars($produkt['id'] ?? '');
$nazwa = htmlspecialchars($produkt['nazwa'] ?? '');
$numer_katalogowy = htmlspecialchars($produkt['numer_katalogowy'] ?? ''); // Nowe pole: Numer katalogowy części
$numery_zamiennikow = htmlspecialchars($produkt['numery_zamiennikow'] ?? ''); // Nowe pole: Numery katalogowe zamienników
$numery_oryginalu = htmlspecialchars($produkt['numery_oryginalu'] ?? ''); // Nowe pole: Numery katalogowe oryginału
$producent = htmlspecialchars($produkt['producent'] ?? ''); // Nowe pole: Producent
$ilosc = htmlspecialchars($produkt['ilosc'] ?? 0);
$cena_netto = htmlspecialchars($produkt['cena_netto'] ?? '');
$cena_brutto = htmlspecialchars($produkt['cena_brutto'] ?? '');
$vat = htmlspecialchars($produkt['vat'] ?? '');
$jednostka_miary = htmlspecialchars($produkt['jednostka_miary'] ?? 'szt'); // Nowe pole: Jednostka miary (domyślnie 'szt')
$opis = htmlspecialchars($produkt['opis'] ?? '');
$pasuje_do_poj = htmlspecialchars($produkt['pasuje_do_poj'] ?? ''); // Nowe pole: Pasuje do pojazdów
$parametry = htmlspecialchars($produkt['parametry'] ?? ''); // Nowe pole: Parametry
$kategoria = htmlspecialchars($produkt['kategoria'] ?? '');
$zdjecia = $produkt['zdjecia'] ?? []; // Nowe pole: Tablica ścieżek do zdjęć
$zdjecie_glowne = htmlspecialchars($produkt['zdjecie_glowne'] ?? ''); // Nowe pole: Nazwa pliku zdjęcia głównego
$ean = htmlspecialchars($produkt['ean'] ?? '');
$waga = htmlspecialchars($produkt['waga'] ?? '');
$wymiary = htmlspecialchars($produkt['wymiary'] ?? '');
$aktywny = $produkt['aktywny'] ?? true; // Wartość boolean
$miejsce_magazynowe = htmlspecialchars($produkt['miejsce_magazynowe'] ?? '');

?>

<div class="container-fluid mt-4">
    <h1 class="mb-4"><?php echo $form_title; ?></h1>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            Szczegóły Produktu
        </div>
        <div class="card-body">
            <form action="<?php echo $action_url; ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="akcja" value="save_product">

                <?php if ($is_edit_mode): ?>
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <?php
                    // Przekaż istniejące ścieżki zdjęć, aby można było nimi zarządzać
                    if (!empty($zdjecia)) {
                        foreach ($zdjecia as $index => $zdjecie_sciezka) {
                            echo '<input type="hidden" name="existing_photos[' . $index . ']" value="' . htmlspecialchars($zdjecie_sciezka) . '">';
                        }
                    }
                    ?>
                <?php endif; ?>


                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="productName" class="form-label">Nazwa Produktu</label>
                            <input type="text" class="form-control" id="productName" name="nazwa" value="<?php echo $nazwa; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="productCatNumber" class="form-label">Numer Katalogowy Części</label>
                            <input type="text" class="form-control" id="productCatNumber" name="numer_katalogowy" value="<?php echo $numer_katalogowy; ?>">
                        </div>
                         <div class="mb-3">
                            <label for="productReplacementNumbers" class="form-label">Numery Katalogowe Zamienników (oddziel przecinkami)</label>
                            <input type="text" class="form-control" id="productReplacementNumbers" name="numery_zamiennikow" value="<?php echo $numery_zamiennikow; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="productOriginalNumbers" class="form-label">Numery Katalogowe Oryginału (oddziel przecinkami)</label>
                            <input type="text" class="form-control" id="productOriginalNumbers" name="numery_oryginalu" value="<?php echo $numery_oryginalu; ?>">
                        </div>
                         <div class="mb-3">
                            <label for="productManufacturer" class="form-label">Producent</label>
                            <input type="text" class="form-control" id="productManufacturer" name="producent" value="<?php echo $producent; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="productEAN" class="form-label">EAN</label>
                            <input type="text" class="form-control" id="productEAN" name="ean" value="<?php echo $ean; ?>">
                        </div>
                         <div class="mb-3">
                            <label for="productCategory" class="form-label">Kategoria</label>
                            <select class="form-select" id="productCategory" name="kategoria">
                                 <option value="">-- Wybierz kategorię --</option>
                                <?php foreach($kategorie as $cat): ?>
                                     <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($kategoria === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="productStock" class="form-label">Ilość (Stan Magazynowy)</label>
                                    <input type="number" class="form-control" id="productStock" name="ilosc" value="<?php echo $ilosc; ?>" min="0" required>
                                </div>
                            </div>
                             <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="productUnit" class="form-label">Jednostka</label>
                                    <input type="text" class="form-control" id="productUnit" name="jednostka_miary" value="<?php echo $jednostka_miary; ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="productPriceNet" class="form-label">Cena Netto (zł)</label>
                                    <input type="number" step="0.01" class="form-control" id="productPriceNet" name="cena_netto" value="<?php echo $cena_netto; ?>" required>
                                </div>
                            </div>
                             <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="productVAT" class="form-label">VAT (%)</label>
                                    <input type="number" step="1" class="form-control" id="productVAT" name="vat" value="<?php echo $vat; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="productPriceGross" class="form-label">Cena Brutto (zł)</label>
                                    <input type="number" step="0.01" class="form-control" id="productPriceGross" name="cena_brutto" value="<?php echo $cena_brutto; ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                             <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="productWeight" class="form-label">Waga (kg)</label>
                                    <input type="number" step="0.01" class="form-control" id="productWeight" name="waga" value="<?php echo $waga; ?>">
                                </div>
                            </div>
                             <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="productDimensions" class="form-label">Wymiary (np. S/W/G)</label>
                                    <input type="text" class="form-control" id="productDimensions" name="wymiary" value="<?php echo $wymiary; ?>">
                                </div>
                            </div>
                        </div>

                         <div class="mb-3">
                            <label for="warehouseLocation" class="form-label">Miejsce Magazynowe</label>
                            <select class="form-select" id="warehouseLocation" name="miejsce_magazynowe">
                                 <option value="">-- Brak --</option>
                                <?php
                                // Wyświetl opcje miejsc magazynowych
                                if (!empty($wszystkie_miejsca_magazynowe)) {
                                     // Możesz grupować miejsca według regałów, jeśli masz taką strukturę
                                    foreach ($wszystkie_miejsca_magazynowe as $lokacja_id => $lokacja_data) {
                                         // Opcjonalnie wyświetl tylko wolne miejsca + obecne miejsce produktu w trybie edycji
                                         $is_occupied_by_other = ($lokacja_data['status'] ?? '') === 'zajete' && !empty($lokacja_data['produkt_id']) && (string)($lokacja_data['produkt_id']) !== (string)($produkt['id'] ?? null);
                                         $is_current_location = ($miejsce_magazynowe === $lokacja_id);

                                         // Jeśli miejsce jest zajęte przez inny produkt i nie jest to obecne miejsce edytowanego produktu, pomiń je
                                         if ($is_occupied_by_other && !$is_current_location) {
                                             continue;
                                         }

                                        $display_text = htmlspecialchars($lokacja_id) . ' (' . htmlspecialchars($lokacja_data['status'] ?? 'Nieznany') . ')';
                                        if ($is_current_location) {
                                            $display_text .= ' - (Obecne)';
                                        } elseif (($lokacja_data['status'] ?? '') === 'zajete') {
                                             $display_text .= ' - Zajęte';
                                        }


                                        echo '<option value="' . htmlspecialchars($lokacja_id) . '" ' . ($is_current_location ? 'selected' : '') . '>' . $display_text . '</option>';

                                    }
                                }
                                ?>
                            </select>
                             <?php if ($is_edit_mode && !empty($produkt['miejsce_magazynowe'])): // Tylko w trybie edycji, jeśli miejsce było przypisane ?>
                                <?php if (isset($miejsce_magazynowe_info)): ?>
                                    <small class="form-text text-muted">Obecne przypisane miejsce: <strong><?php echo htmlspecialchars($produkt['miejsce_magazynowe']); ?></strong> (Status: <?php echo htmlspecialchars($miejsce_magazynowe_info['status'] ?? 'Nieznany'); ?>)</small>
                                <?php else: ?>
                                     <small class="form-text text-danger">Obecne przypisane miejsce (<strong><?php echo htmlspecialchars($produkt['miejsce_magazynowe']); ?></strong>) nie znaleziono w danych magazynu.</small>
                                <?php endif; ?>
                             <?php endif; ?>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="productActive" name="aktywny" value="1" <?php echo $aktywny ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="productActive">Produkt aktywny</label>
                        </div>
                    </div>
                </div>

                 <hr class="my-4">

                 <h5>Zdjęcia Produktu (max 5)</h5>
                <div class="mb-3">
                     <label for="productImages" class="form-label">Wybierz zdjęcia (Ctrl+klik aby wybrać wiele)</label>
                     <input type="file" class="form-control" id="productImages" name="zdjecia_upload[]" accept="image/*" multiple>
                     <small class="form-text text-muted">Maksymalnie 5 zdjęć.</small>
                </div>

                <div id="imagePreviewContainer" class="row g-3 mb-3">
                     <?php if ($is_edit_mode && !empty($zdjecia)): // Pokaż istniejące zdjęcia w trybie edycji ?>
                         <?php foreach ($zdjecia as $index => $zdjecie_sciezka):
                             $image_url = tn_pobierz_sciezke_obrazka(basename($zdjecie_sciezka)); // Użyj basename, bo ścieżka w JSON może być pełna, a tn_pobierz_sciezke_obrazka oczekuje nazwy pliku. Może być konieczna adaptacja funkcji tn_pobierz_sciezke_obrazka
                             $is_main = (basename($zdjecie_sciezka) === $zdjecie_glowne);
                             ?>
                             <div class="col-4 col-md-3 col-lg-2 existing-image-preview" data-filename="<?php echo htmlspecialchars(basename($zdjecie_sciezka)); ?>">
                                <div class="card h-100">
                                     <img src="<?php echo $image_url; ?>" class="card-img-top img-thumbnail" style="height: 100px; object-fit: cover;" alt="Zdjęcie produktu">
                                     <div class="card-body p-2">
                                         <div class="form-check">
                                             <input class="form-check-input" type="radio" name="zdjecie_glowne" id="mainImage<?php echo $index; ?>" value="<?php echo htmlspecialchars(basename($zdjecie_sciezka)); ?>" <?php echo $is_main ? 'checked' : ''; ?>>
                                             <label class="form-check-label" for="mainImage<?php echo $index; ?>">Główne</label>
                                         </div>
                                         <button type="button" class="btn btn-danger btn-sm mt-2 w-100 delete-existing-image">Usuń</button>
                                         <input type="hidden" name="keep_photos[]" value="<?php echo htmlspecialchars(basename($zdjecie_sciezka)); ?>">
                                     </div>
                                </div>
                             </div>
                         <?php endforeach; ?>
                     <?php endif; ?>
                     </div>
                <small class="form-text text-muted" id="imageCountText">Wybrano 0 nowych zdjęć. Możesz dodać do 5 zdjęć łącznie.</small>


                 <hr class="my-4">

                 <h5>Dodatkowe Informacje</h5>
                 <div class="row g-3">
                     <div class="col-md-6">
                         <div class="mb-3">
                            <label for="productDescription" class="form-label">Opis Produktu</label>
                            <textarea class="form-control" id="productDescription" name="opis" rows="5"><?php echo $opis; ?></textarea>
                        </div>
                     </div>
                     <div class="col-md-6">
                         <div class="mb-3">
                            <label for="productFitment" class="form-label">Pasuje do Pojazdów (marka, model, rocznik - po jednym w linii)</label>
                            <textarea class="form-control" id="productFitment" name="pasuje_do_poj" rows="5"><?php echo $pasuje_do_poj; ?></textarea>
                             <small class="form-text text-muted">Każdy wpis w nowej linii.</small>
                        </div>
                     </div>
                 </div>

                 <div class="mb-3">
                    <label for="productParameters" class="form-label">Parametry (Nazwa: Wartość - po jednym w linii)</label>
                    <textarea class="form-control" id="productParameters" name="parametry" rows="5"><?php echo $parametry; ?></textarea>
                     <small class="form-text text-muted">Format: Nazwa parametru: Wartość parametru. Każdy parametr w nowej linii.</small>
                </div>


                <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save"></i> Zapisz Produkt</button>
                <a href="<?php echo tn_generuj_url('produkty'); ?>" class="btn btn-secondary mt-3">Anuluj</a>
            </form>
        </div>
    </div>

</div>

<?php
// Skrypt JS do automatycznego przeliczania ceny brutto/netto/VAT oraz podglądu zdjęć
?>
<script>
 document.addEventListener('DOMContentLoaded', function() {
    // Skrypt do przeliczania cen (istniejący kod)
    const priceNetInput = document.getElementById('productPriceNet');
    const priceGrossInput = document.getElementById('productPriceGross');
    const vatInput = document.getElementById('productVAT');

    function calculatePrices(changedInput) {
        let priceNet = parseFloat(priceNetInput.value);
        let priceGross = parseFloat(priceGrossInput.value);
        let vat = parseFloat(vatInput.value);

        if (isNaN(vat) || vat < 0) {
             vat = 0;
             vatInput.value = 0;
        }


        if (changedInput === 'net' && !isNaN(priceNet)) {
            priceGross = priceNet * (1 + vat / 100);
            priceGrossInput.value = priceGross.toFixed(2);
        } else if (changedInput === 'gross' && !isNaN(priceGross)) {
            if (1 + vat / 100 > 0) {
                 priceNet = priceGross / (1 + vat / 100);
                 priceNetInput.value = priceNet.toFixed(2);
            } else {
                 priceNetInput.value = '0.00';
            }

        } else if (changedInput === 'vat') {
             if (!isNaN(priceNet) && parseFloat(priceNetInput.value) > 0) {
                 priceGross = priceNet * (1 + vat / 100);
                 priceGrossInput.value = priceGross.toFixed(2);
             }
             else if (!isNaN(priceGross) && parseFloat(priceGrossInput.value) > 0) {
                 if (1 + vat / 100 > 0) {
                     priceNet = priceGross / (1 + vat / 100);
                     priceNetInput.value = priceNet.toFixed(2);
                 } else {
                     priceNetInput.value = '0.00';
                 }
             }
        }
         if (!isNaN(parseFloat(priceNetInput.value))) priceNetInput.value = parseFloat(priceNetInput.value).toFixed(2);
         if (!isNaN(parseFloat(priceGrossInput.value))) priceGrossInput.value = parseFloat(priceGrossInput.value).toFixed(2);
    }

    priceNetInput.addEventListener('input', () => calculatePrices('net'));
    priceGrossInput.addEventListener('input', () => calculatePrices('gross'));
    vatInput.addEventListener('input', () => calculatePrices('vat'));

      if (priceNetInput.value !== '' || priceGrossInput.value !== '' || vatInput.value !== '') {
         if (priceNetInput.value !== '' && !isNaN(parseFloat(priceNetInput.value))) {
             calculatePrices('net');
         } else if (priceGrossInput.value !== '' && !isNaN(parseFloat(priceGrossInput.value))) {
             calculatePrices('gross');
         } else if (vatInput.value !== '' && !isNaN(parseFloat(vatInput.value))) {
              calculatePrices('vat');
         }
      }

    // Skrypt do podglądu wielu zdjęć
    const imageInput = document.getElementById('productImages');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const imageCountText = document.getElementById('imageCountText');
    const maxImages = 5;

    // Funkcja do aktualizacji licznika i stanu przycisku submit
    function updateImageCount() {
        const existingImages = imagePreviewContainer.querySelectorAll('.existing-image-preview').length;
        const newImages = imageInput.files.length;
        const totalImages = existingImages + newImages;

        imageCountText.textContent = `Wybrano ${newImages} nowych zdjęć. Obecnie: ${existingImages} istniejących. Łącznie możesz mieć do ${maxImages} zdjęć.`;

        if (totalImages > maxImages) {
            imageCountText.classList.add('text-danger');
            imageCountText.classList.remove('text-muted');
             // Opcjonalnie: wyłącz przycisk zapisu
             // document.querySelector('button[type="submit"]').disabled = true;
             alert(`Możesz dodać maksymalnie ${maxImages} zdjęć. Obecnie masz ${existingImages} istniejących i próbujesz dodać ${newImages} nowych.`);
             imageInput.value = ''; // Wyczyść wybrane pliki, aby użytkownik wybrał ponownie
             imagePreviewContainer.querySelectorAll('.new-image-preview').forEach(el => el.remove()); // Usuń podglądy nowych zdjęć
             updateImageCount(); // Zaktualizuj licznik po wyczyszczeniu
        } else {
             imageCountText.classList.remove('text-danger');
            imageCountText.classList.add('text-muted');
             // Opcjonalnie: włącz przycisk zapisu
             // document.querySelector('button[type="submit"]').disabled = false;
        }
         // Upewnij się, że zawsze jest zaznaczone jakieś radio dla zdjęcia głównego,
         // jeśli są jakieś zdjęcia (istniejące lub nowe)
         const allImages = imagePreviewContainer.querySelectorAll('.image-preview-item');
         if (allImages.length > 0) {
             const checkedRadio = imagePreviewContainer.querySelector('input[name="zdjecie_glowne"]:checked');
             if (!checkedRadio) {
                 // Jeśli brak zaznaczonego, zaznacz pierwszy element
                 const firstRadio = imagePreviewContainer.querySelector('input[name="zdjecie_glowne"]');
                 if (firstRadio) {
                     firstRadio.checked = true;
                 }
             }
         }
    }

    imageInput.addEventListener('change', function() {
        // Usuń poprzednie podglądy nowych zdjęć
        imagePreviewContainer.querySelectorAll('.new-image-preview').forEach(el => el.remove());

        const files = this.files;
        if (files) {
            Array.from(files).forEach((file, index) => {
                if (file.type.startsWith('image/') && imagePreviewContainer.querySelectorAll('.image-preview-item').length < maxImages) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const colDiv = document.createElement('div');
                         colDiv.classList.add('col-4', 'col-md-3', 'col-lg-2', 'new-image-preview', 'image-preview-item'); // Dodaj klasę image-preview-item
                         colDiv.innerHTML = `
                            <div class="card h-100">
                                <img src="${e.target.result}" class="card-img-top img-thumbnail" style="height: 100px; object-fit: cover;" alt="Podgląd zdjęcia">
                                <div class="card-body p-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="zdjecie_glowne" id="newMainImage${index}" value="${file.name}">
                                        <label class="form-check-label" for="newMainImage${index}">Główne</label>
                                    </div>
                                    <button type="button" class="btn btn-danger btn-sm mt-2 w-100 delete-new-image">Usuń</button>
                                </div>
                            </div>
                         `;
                        imagePreviewContainer.appendChild(colDiv);

                         // Dodaj listener do przycisku "Usuń" dla nowego zdjęcia
                         colDiv.querySelector('.delete-new-image').addEventListener('click', function() {
                             colDiv.remove();
                             updateImageCount(); // Zaktualizuj licznik po usunięciu
                             // Ważne: Usunięcie nowego zdjęcia z podglądu nie usuwa go z listy plików input.files
                             // W przypadku, gdy użytkownik usunie plik z podglądu, ale nie wybierze plików ponownie,
                             // ten plik nadal zostanie wysłany. Bardziej zaawansowane rozwiązanie wymagałoby
                             // manipulacji obiektem DataTransfer lub ręcznego tworzenia listy plików do wysłania.
                             // Na potrzeby tej implementacji, polegamy na liczniku i komunikacie o błędzie
                             // przy próbie dodania zbyt wielu zdjęć. Dla pełnej funkcjonalności usunięcia nowego zdjęcia
                             // przed wysłaniem, potrzebne jest dodatkowe skomplikowane JS.
                         });
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
        updateImageCount(); // Zaktualizuj licznik po dodaniu nowych zdjęć
    });

    // Dodaj listener do przycisków "Usuń" dla istniejących zdjęć (delegacja zdarzeń)
     imagePreviewContainer.addEventListener('click', function(e) {
         if (e.target && e.target.classList.contains('delete-existing-image')) {
             const imageCard = e.target.closest('.existing-image-preview');
             if (imageCard) {
                 const filename = imageCard.dataset.filename;
                 // Dodaj ukryte pole informujące serwer, które zdjęcie ma zostać usunięte
                 const deleteInput = document.createElement('input');
                 deleteInput.type = 'hidden';
                 deleteInput.name = 'delete_photos[]';
                 deleteInput.value = filename;
                 imageCard.closest('form').appendChild(deleteInput);

                 imageCard.remove();
                 updateImageCount(); // Zaktualizuj licznik po usunięciu istniejącego zdjęcia
             }
         }
     });

     // Inicjalne wywołanie, aby ustawić licznik przy ładowaniu strony w trybie edycji
     updateImageCount();

 });
</script>