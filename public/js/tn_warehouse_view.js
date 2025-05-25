// Plik: public/js/tn_warehouse_view.js
// Wersja: 1.26 (Poprawiony wygląd etykiety magazynowej - V2)

document.addEventListener('DOMContentLoaded', function () {

    // --- Inicjalizacja globalnego obiektu aplikacji ---
    if (typeof window.tnApp === 'undefined') window.tnApp = {};
    // Odczytaj ścieżkę do skryptu kodów kreskowych z konfiguracji globalnej (ustawionej w PHP)
    // Zakładam, że tnAppConfig jest globalnym obiektem ustawionym przez PHP w widoku
    const barcodeScriptPath = window.tnAppConfig?.barcodeScriptPath || 'kod_kreskowy.php'; // Fallback

    // --- Funkcje Pomocnicze JS (Toast, Kopiowanie, Modal Regału Placeholder) ---
    // Zakładam, że te funkcje są już zdefiniowane globalnie lub przez inne skrypty
    // Jeśli nie, odkomentuj i użyj poniższych definicji:
    /*
    if (typeof window.tnApp.copyToClipboard === 'undefined') { window.tnApp.copyToClipboard = (text) => { if (!navigator.clipboard) { tnApp.showToast('Twoja przeglądarka nie obsługuje kopiowania do schowka.', 'warning'); return; } navigator.clipboard.writeText(text).then(() => { tnApp.showToast(`Skopiowano ID: ${text}`); }).catch(err => { console.error('Błąd kopiowania do schowka: ', err); tnApp.showToast('Nie udało się skopiować ID.', 'error'); }); }; }
    if (typeof window.tnApp.showToast === 'undefined') { window.tnApp.showToast = (message, type = 'info', delay = 3500) => { const toastContainer = document.getElementById('toastContainer'); if (!toastContainer) { console.error("Brak kontenera tostów #toastContainer"); return; } const toastId = 'toast-' + Date.now(); const bgClass = type === 'success' ? 'bg-success' : type === 'danger' ? 'bg-danger' : type === 'warning' ? 'bg-warning text-dark' : 'bg-primary'; const iconHtml = type === 'success' ? '<i class="bi bi-check-circle-fill me-2"></i>' : type === 'danger' ? '<i class="bi bi-exclamation-triangle-fill me-2"></i>' : type === 'warning' ? '<i class="bi bi-exclamation-circle-fill me-2"></i>' : '<i class="bi bi-info-circle-fill me-2"></i>'; const toastHtml = `<div class="toast align-items-center text-white ${bgClass} border-0 fade" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body">${iconHtml} ${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>`; toastContainer.insertAdjacentHTML('beforeend', toastHtml); const toastElement = document.getElementById(toastId); const toast = new bootstrap.Toast(toastElement, { delay: delay, autohide: true }); toast.show(); toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove()); }; }
    if (typeof window.tnApp.openRegalModal === 'undefined') { window.tnApp.openRegalModal = (regalData = null) => { console.warn("Funkcja tnApp.openRegalModal jest tylko placeholderem."); alert(`Akcja dla regału: ${regalData ? 'Edycja ' + regalData.tn_id_regalu : 'Dodaj nowy'}`); }; }
    */


    // --- Inicjalizacja Komponentów Bootstrap (Tooltipy, Popovery, Modale) ---
    // Kod inicjalizacyjny Twoich komponentów Bootstrap, który już masz
    // Upewnij się, że elementy #warehouseGridContainer i #toastContainer istnieją w HTML.
    const warehouseGridContainer = document.getElementById('warehouseGridContainer');

    // Inicjalizacja Tooltipów (poprawione, aby działały z delegacją lub były inicjowane po dodaniu slotów)
    // Lepszym podejściem jest inicjalizacja tooltipów po załadowaniu/filtrowaniu slotów lub użycie delegacji,
    // ale poniżej zostawiam istniejący kod inicjalizacji przy starcie DOM.
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });


    // Inicjalizacja Popoverów (poprawione, aby działały z delegacją lub były inicjowane po dodaniu slotów)
    // Podobnie jak tooltipy, lepiej inicjować dynamicznie lub delegować.
     const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
     const popoverList = popoverTriggerList.map(function (el) {
         try {
             // Parse content data from the data-popover-content attribute
             const contentData = JSON.parse(el.getAttribute('data-popover-content'));
             return new bootstrap.Popover(el, {
                 html: true,
                 trigger: 'manual', // Manual trigger to control show/hide
                 placement: 'auto',
                 title: contentData.title,
                 content: contentData.content,
                 customClass: 'tn-warehouse-popover',
                 sanitize: false // Allow HTML in content
             });
         } catch (e) {
             console.error("Error initializing popover:", e, el.getAttribute('data-popover-content'));
             return null;
         }
     }).filter(p => p !== null);

     // Manual popover trigger logic
     document.body.addEventListener('click', function (event) {
         popoverList.forEach(popover => {
             const element = popover.element;
             // Check if the click is outside the popover and its trigger element
             if (!element.contains(event.target) && !popover.tip.contains(event.target)) {
                 popover.hide();
             }
         });
         // Handle clicking on a popover trigger
         const clickedTrigger = event.target.closest('[data-bs-toggle="popover"]');
          if (clickedTrigger) {
              popoverList.forEach(popover => {
                  if (popover.element === clickedTrigger) {
                      popover.toggle(); // Toggle the clicked popover
                  } else {
                      popover.hide(); // Hide other popovers
                  }
              });
          }
     });


    // --- Logika Modala Kodu Kreskowego Lokalizacji ---
    const barcodeModalElement = document.getElementById('barcodeModal');
    const barcodeModalImage = document.getElementById('barcodeModalImage');
    const barcodeModalLocationIdSpan = document.getElementById('barcodeModalLocationId');

     if (barcodeModalElement && barcodeModalImage && barcodeModalLocationIdSpan && warehouseGridContainer) {
         // Użyj delegacji zdarzeń dla kliknięć w obszar kodu kreskowego slotu
         warehouseGridContainer.addEventListener('click', function(event) {
             const barcodeTrigger = event.target.closest('.tn-slot-barcode'); // Obszar kodu kreskowego
             if (barcodeTrigger) {
                 event.stopPropagation(); // Zatrzymaj propagację, aby nie zamykać popovera itp.
                 const locationId = barcodeTrigger.dataset.locationId;
                 const largeBarcodeSrc = barcodeTrigger.dataset.barcodeSrc; // URL dużego kodu

                 if (locationId && largeBarcodeSrc) {
                     barcodeModalLocationIdSpan.textContent = locationId;
                     barcodeModalImage.src = largeBarcodeSrc;
                     barcodeModalImage.alt = `Powiększony kod kreskowy dla ${locationId}`;

                     const modal = bootstrap.Modal.getOrCreateInstance(barcodeModalElement);
                     modal.show();
                 } else {
                     console.warn("Brak danych dla modala kodu kreskowego:", barcodeTrigger.dataset);
                      tnApp.showToast('Błąd: Brak danych kodu kreskowego.', 'error');
                 }
             }
         });
     } else {
         console.warn('Elementy modala kodu kreskowego lokalizacji nie znalezione lub #warehouseGridContainer brak.');
     }

    // Funkcja do drukowania KODU KRESKOWEGO z modala (jeśli nadal jej potrzebujesz)
    // Jest wywoływana przez przycisk w #barcodeModal
    if (typeof window.tnApp.printBarcodeModal === 'undefined') {
         window.tnApp.printBarcodeModal = () => {
             const locationId = barcodeModalLocationIdSpan?.textContent;
             const barcodeImageSrc = barcodeModalImage?.src;

             if (!locationId || !barcodeImageSrc || barcodeImageSrc === window.location.href) { // Sprawdź też czy src nie jest pusty/błędny
                 alert('Błąd: Nie można odczytać danych do druku kodu kreskowego lokalizacji.');
                 return;
             }

             const printWindow = window.open('', '_blank', 'height=400,width=600');
             if (!printWindow) {
                 alert('Nie można otworzyć okna drukowania. Sprawdź, czy przeglądarka nie blokuje wyskakujących okienek.');
                 return;
             }

             printWindow.document.write(`<!DOCTYPE html><html lang="pl"><head><meta charset="UTF-8"><title>Drukuj Kod Lokalizacji: ${locationId}</title><style>body { text-align: center; margin-top: 30px; font-family: sans-serif; } img { max-width: 90%; height: auto; max-height: 150px; object-fit: contain; } h4 { margin-bottom: 20px; } @media print { body { margin-top: 10px; } @page { margin: 1cm; } }</style></head><body><h4>Lokalizacja Magazynowa:</h4><h2>${locationId}</h2><img src="${barcodeImageSrc}" alt="Kod kreskowy ${locationId}"><script>window.onload = function() { window.print(); window.onafterprint = function(){ window.close(); }; setTimeout(function(){ if(!window.closed) window.close(); }, 5000); };</script></body></html>`);
             printWindow.document.close();
             printWindow.focus(); // Ustaw focus na nowym oknie
         };
     }


    // --- Logika Filtrowania Widoku ---
    // ... Twój istniejący kod filtrowania ...
    const filterRegalSelect = document.getElementById('tn_filter_regal');
    const filterStatusSelect = document.getElementById('tn_filter_status');
    const filterTextInput = document.getElementById('tn_filter_text');
    const clearFiltersBtn = document.getElementById('tn_clear_filters_btn');
    const noFilterResultsMessage = document.getElementById('noFilterResultsMessage'); // Używamy #noFilterResultsMessage

    const filterWarehouseView = () => {
        if (!warehouseGridContainer || !noFilterResultsMessage) {
            console.error("Brak #warehouseGridContainer lub #noFilterResultsMessage do filtrowania.");
            return;
        }

        const allLocationSlots = warehouseGridContainer.querySelectorAll('.tn-location-slot');
        const allRegalCards = warehouseGridContainer.querySelectorAll('.tn-regal-card'); // Karty regałów

        const selectedRegal = filterRegalSelect?.value ?? 'all';
        const selectedStatus = filterStatusSelect?.value ?? 'all';
        const filterText = filterTextInput?.value.toLowerCase().trim() ?? '';

        let visibleSlotsCount = 0;

        allLocationSlots.forEach(slot => {
            const slotRegalId = slot.dataset.regalId;
            const slotStatus = slot.dataset.status;
            // Użyj dataset.filterText, który zawiera już ID lokalizacji, ID produktu i nazwę
            const slotFilterTextData = slot.dataset.filterText?.toLowerCase() || '';

            const regalMatch = selectedRegal === 'all' || slotRegalId === selectedRegal;
            const statusMatch = selectedStatus === 'all' || slotStatus === selectedStatus;
            const textMatch = filterText === '' || slotFilterTextData.includes(filterText);

            if (regalMatch && statusMatch && textMatch) {
                slot.style.display = 'flex'; // Pokaż slot (używamy flex ze względu na strukturę slotu)
                 slot.style.opacity = '1'; // Upewnij się, że jest widoczny (po ewentualnym ukryciu)
                 slot.style.pointerEvents = 'auto'; // Włącz interakcję
                visibleSlotsCount++;
            } else {
                slot.style.display = 'none'; // Ukryj slot
                 slot.style.opacity = '0'; // Ustaw na 0 dla animacji
                 slot.style.pointerEvents = 'none'; // Wyłącz interakcję
            }
        });

        // Pokaż/ukryj karty regałów w zależności od tego, czy zawierają widoczne sloty
        allRegalCards.forEach(card => {
             const cardRegalId = card.dataset.regalId;
             const regalFilterMatch = selectedRegal === 'all' || cardRegalId === selectedRegal;
             // Sprawdź, czy w tej karcie regału są jakieś widoczne sloty (nie ukryte przez style.display = 'none')
             const hasVisibleSlotsInside = card.querySelector('.tn-location-slot:not([style*="display: none"])') !== null;

             if (regalFilterMatch && hasVisibleSlotsInside) {
                 card.style.display = ''; // Przywróć domyślne wyświetlanie (np. block)
                 // Ustaw opacity z opóźnieniem, aby animacja była widoczna po display
                 setTimeout(() => { card.style.opacity = '1'; }, 10);
             } else {
                  // Jeśli wszystkie sloty w regale są ukryte LUB regał nie pasuje do filtra, ukryj też kartę regału
                  card.style.opacity = '0';
                  // Ukryj fizycznie element po zakończeniu animacji opacity
                  setTimeout(() => { card.style.display = 'none'; }, 300); // Czas animacji opacity
             }
        });


        // Pokaż/ukryj komunikat o braku wyników
        // Komunikat powinien być widoczny TYLKO gdy są aktywne filtry i 0 widocznych slotów
        const filtersActive = selectedRegal !== 'all' || selectedStatus !== 'all' || filterText !== '';
        noFilterResultsMessage.classList.toggle('d-none', !(filtersActive && visibleSlotsCount === 0));
    };

    filterRegalSelect?.addEventListener('change', filterWarehouseView);
    filterStatusSelect?.addEventListener('change', filterWarehouseView);
    filterTextInput?.addEventListener('input', filterWarehouseView); // Filtracja podczas pisania

    // Obsługa przycisku "Wyczyść filtry"
    clearFiltersBtn?.addEventListener('click', () => {
        if(filterRegalSelect) filterRegalSelect.value = 'all';
        if(filterStatusSelect) filterStatusSelect.value = 'all';
        if(filterTextInput) filterTextInput.value = '';
        filterWarehouseView(); // Zastosuj puste filtry
    });

    // Initial application of filters on page load - przeniesione na koniec bloku DOMContentLoaded


    // --- Kontrola Gęstości Widoku ---
    // ... Twój istniejący kod kontroli gęstości ...
    const densityButtons = document.querySelectorAll('input[name="density"]');
    const savedDensity = localStorage.getItem('warehouseDensity') || 'normal'; // Domyślnie 'normal'

    const setDensity = (densityValue) => {
        if (!warehouseGridContainer) return;
        // Usuń istniejące klasy gęstości
        warehouseGridContainer.className = warehouseGridContainer.className.replace(/warehouse-density-\w+/g, '');
        // Dodaj nową klasę gęstości
        warehouseGridContainer.classList.add(`warehouse-density-${densityValue}`);

        // Zapisz wybraną gęstość
        localStorage.setItem('warehouseDensity', densityValue);

        // Ustaw zaznaczenie i aktywną klasę dla odpowiedniego przycisku radio/label
        const radioBtn = document.getElementById(`density${densityValue.charAt(0).toUpperCase() + densityValue.slice(1)}`);
        if(radioBtn) radioBtn.checked = true;

        document.querySelectorAll('label[for^="density"]').forEach(lbl => lbl.classList.remove('active'));
        document.querySelector(`label[for="density${densityValue.charAt(0).toUpperCase() + densityValue.slice(1)}"]`)?.classList.add('active');

        // Ukryj tooltipy przy zmianie gęstości, bo rozmiary elementów się zmieniają
        tooltipList.forEach(tooltip => tooltip.hide());
         popoverList.forEach(popover => popover.hide()); // Ukryj też popovery
    };

    densityButtons.forEach(button => {
        // Ustaw początkowy zaznaczony przycisk i aktywną klasę na podstawie zapisanej gęstości
        if (button.value === savedDensity) {
            button.checked = true;
            document.querySelector(`label[for="${button.id}"]`)?.classList.add('active');
        } else {
             document.querySelector(`label[for="${button.id}"]`)?.classList.remove('active');
        }
        // Dodaj listener zmiany
        button.addEventListener('change', (event) => {
            setDensity(event.target.value);
        });
    });

    // Zastosuj zapisaną gęstość przy ładowaniu strony
    setDensity(savedDensity);

    // Initial application of filters on page load - przeniesione na koniec bloku DOMContentLoaded


    // --- Podświetlanie Powiązanych Produktów ---
    // ... Twój istniejący kod podświetlania ...
    let lastHoveredSlot = null; // Deklaracja zmiennej lastHoveredSlot
    warehouseGridContainer?.addEventListener('mouseover', (event) => {
        // Znajdź element slotu, na który najechano, upewnij się, że jest widoczny i zajęty z produktem
        const targetSlot = event.target.closest('.tn-location-slot.status-occupied');

        // Jeśli najechano na inny slot z produktem i ten slot jest widoczny
        if (targetSlot && targetSlot.style.display !== 'none' && targetSlot.dataset.productId) {
             // Jeśli poprzednio najeżdżano na inny slot, usuń z niego podświetlenie i efekt hover
             if (lastHoveredSlot && lastHoveredSlot !== targetSlot) {
                 lastHoveredSlot.style.transform = 'scale(1)'; // Przywróć skalę
                 lastHoveredSlot.style.zIndex = lastHoveredSlot.getAttribute('data-default-zindex') || '1'; // Przywróć zIndex
             }

            const productId = targetSlot.dataset.productId;
            // Znajdź wszystkie WIDOCZNE sloty z tym samym ID produktu, z wyłączeniem aktualnie najechanego
            const relatedSlots = warehouseGridContainer.querySelectorAll(`.tn-location-slot.status-occupied[data-product-id="${productId}"]:not([style*="display: none"])`);

            relatedSlots.forEach(slot => {
                if (slot !== targetSlot) {
                     // Dodaj klasę podświetlenia
                    slot.classList.add('tn-slot-related-highlight');
                }
            });

             // Zapisz aktualnie najeżdżany slot do użycia w mouseout
             lastHoveredSlot = targetSlot;
             // Zwiększ z-index najeżdżanego slotu, aby był na wierzchu
             const currentZIndex = window.getComputedStyle(targetSlot).zIndex;
             const defaultZIndex = (currentZIndex === 'auto' || currentZIndex === '0') ? '1' : currentZIndex;
             targetSlot.setAttribute('data-default-zindex', defaultZIndex); // Zapisz domyślny z-index
             targetSlot.style.zIndex = '10'; // Ustaw wysoki z-index
             // Opcjonalny efekt skali tylko dla najeżdżanego slotu
              targetSlot.style.transform = `scale(1.03)`; // Lekko mniejsza skala dla lepszego dopasowania do ramki
              // Usuń domyślny box-shadow na rzecz podświetlenia
              targetSlot.style.boxShadow = 'none';


        } else {
             // Jeśli najechanie nie jest na widoczny slot z produktem
             // Usuń wszelkie aktywne podświetlenia
            const highlightedSlots = warehouseGridContainer.querySelectorAll('.tn-slot-related-highlight');
            highlightedSlots.forEach(slot => slot.classList.remove('tn-slot-related-highlight'));

             // Przywróć styl ostatnio najeżdżanego slotu, jeśli był
             if (lastHoveredSlot) {
                lastHoveredSlot.style.transform = 'scale(1)';
                lastHoveredSlot.style.zIndex = lastHoveredSlot.getAttribute('data-default-zindex') || '1';
                // Przywróć domyślny box-shadow (możesz potrzebować go zapisać wcześniej)
                lastHoveredSlot.style.boxShadow = ''; // Wyczyść styl, aby powrócił domyślny
                lastHoveredSlot = null; // Zresetuj ostatnio najeżdżany slot
            }
        }
    });

     // Użyj delegacji zdarzeń mouseout na kontenerze
     warehouseGridContainer?.addEventListener('mouseout', (event) => {
         // Sprawdź, czy opuszczamy slot (relatedTarget to element, na który przechodzimy)
         const fromSlot = event.target.closest('.tn-location-slot');
         const toSlot = event.relatedTarget?.closest('.tn-location-slot');

         // Jeśli opuszczamy slot, który był ostatnio najeżdżany
         if (fromSlot && fromSlot === lastHoveredSlot) {
             // I nie przechodzimy na inny slot Z TYM SAMYM produktem
             if (!toSlot || toSlot.dataset.productId !== fromSlot.dataset.productId) {
                  // Usuń podświetlenie ze wszystkich slotów z tym samym produktem
                  const productId = fromSlot.dataset.productId;
                  if(productId) {
                       warehouseGridContainer.querySelectorAll(`.tn-location-slot.status-occupied[data-product-id="${productId}"]`).forEach(slot => {
                           slot.classList.remove('tn-slot-related-highlight');
                       });
                  }

                 // Przywróć styl opuszczanego slotu
                 fromSlot.style.transform = 'scale(1)';
                 fromSlot.style.zIndex = fromSlot.getAttribute('data-default-zindex') || '1';
                 fromSlot.style.boxShadow = ''; // Przywróć domyślny box-shadow
                 lastHoveredSlot = null; // Zresetuj
             }
             // Jeśli przechodzimy na inny slot Z TYM SAMYM produktem, nic nie rób, podświetlenie i styl hover zostają.
         } else if (!fromSlot && lastHoveredSlot) {
             // Jeśli opuszczamy kontener, ale ostatni najeżdżany slot był zapamiętany
              lastHoveredSlot.style.transform = 'scale(1)';
              lastHoveredSlot.style.zIndex = lastHoveredSlot.getAttribute('data-default-zindex') || '1';
              lastHoveredSlot.style.boxShadow = ''; // Przywróć domyślny box-shadow
              lastHoveredSlot = null; // Zresetuj
         }
         // Jeśli opuszczamy element wewnątrz slotu (np. img, span) i przechodzimy na inny element w tym samym slocie, nic nie rób.
     });


    // --- Efekt Hover dla Slotów (Uproszczony i zintegrowany z podświetlaniem) ---
    // Logika efektu skali została przeniesiona do listenerów mouseover/mouseout dla podświetlania
    // Tutaj usunięto oddzielną logikę hover, aby uniknąć konfliktów.


    // --- Logika Drukowania Etykiety Magazynowej (POPRAWIONY WYGLĄD - V2) ---
    // Ta funkcja jest wywoływana przez listener dodany na kontenerze warehouseGridContainer
    if (typeof window.tnApp.printProductLabel === 'undefined') {
        window.tnApp.printProductLabel = (buttonElement) => {
            const slotElement = buttonElement.closest('.tn-location-slot');
            if (!slotElement) {
                console.error('Nie znaleziono elementu slotu dla przycisku drukowania etykiety.');
                tnApp.showToast('Błąd: Nie znaleziono slotu.', 'error');
                return;
            }

            // Pobierz dane z atrybutów data-* (dodano quantity, usunięto weight)
            const locationId = slotElement.dataset.locationId || 'Brak ID'; // ID lokalizacji (kluczowe)
            const arrivalDateRaw = slotElement.dataset.arrivalDate;
            // const productWeight = slotElement.dataset.productWeight; // USUNIĘTO WAGĘ
            const productInternalCatalogNr = slotElement.dataset.productInternalNr; // Numer do kodu kreskowego produktu
            const productName = slotElement.dataset.productName;
            const productId = slotElement.dataset.productId; // ID produktu
            const quantity = slotElement.dataset.quantity; // POBIERAMY ILOŚĆ (WYMAGA DODANIA data-quantity W PHP!)


            // Formatowanie daty przyjęcia (jeśli istnieje)
            let formattedArrivalDate = 'Brak';
            try {
                if (arrivalDateRaw && arrivalDateRaw.match(/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/)) { // Obsługa daty z opcjonalną godziną
                     const dateObj = new Date(arrivalDateRaw);
                     if (!isNaN(dateObj.getTime())) { // Użyj getTime() do lepszego sprawdzenia poprawności daty
                         const day = String(dateObj.getDate()).padStart(2, '0');
                         const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                         const year = dateObj.getFullYear();
                         formattedArrivalDate = `${day}.${month}.${year}`;
                     } else {
                          formattedArrivalDate = arrivalDateRaw; // Użyj surowej daty, jeśli parsowanie zawiodło
                     }
                 } else if (arrivalDateRaw) { // Pokaż, jeśli jest w innym formacie, ale nie pusty
                     formattedArrivalDate = arrivalDateRaw;
                 }
            } catch (e) {
                console.warn("Błąd formatowania daty przyjęcia:", arrivalDateRaw, e);
                formattedArrivalDate = arrivalDateRaw || 'Błąd';
            }

            // Przygotuj dane dla kodów kreskowych
            // Kod kreskowy produktu - użyj numeru katalogowego lub ID produktu, jeśli katalogowy brak
            const barcodeProductData = productInternalCatalogNr || productId || ''; // Dane do kodu kreskowego produktu
            const barcodeProductSrc = barcodeProductData
                ? `${barcodeScriptPath}?s=code128&d=${encodeURIComponent(barcodeProductData)}&h=50&ts=0&th=10` // Mniejszy kod kreskowy produktu
                : ''; // Pusty URL, jeśli brak danych

            // Kod kreskowy lokalizacji - zawsze generujemy dla locationId
            const barcodeLocationData = locationId;
             const barcodeLocationSrc = `${barcodeScriptPath}?s=code128&d=${encodeURIComponent(barcodeLocationData)}&h=60&ts=0&th=12`; // Większy kod kreskowy lokalizacji

            // --- Definicja HTML i CSS dla Etykiety Magazynowej (Zastosowanie Bootstrapa) ---
            // UWAGA: Pełne klasy Bootstrapa mogą wymagać dołączenia pliku CSS Bootstrapa w nagłówku tego HTML-a
            // Dla uproszczenia, użyjemy tylko niektórych klas, które mają wbudowane style
            const labelContentHTML = `
                <!DOCTYPE html>
                <html lang="pl">
                <head>
                    <meta charset="UTF-8">
                    <title>Etykieta Magazynowa - ${locationId}</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        /* Ustawienia strony do druku */
                        @page { size: 100mm 70mm; margin: 3mm; } /* Typowy rozmiar etykiety, margines 3mm */

                        /* Style ciała strony i głównego kontenera etykiety */
                        body {
                            font-family: 'Arial', sans-serif;
                            font-size: 9pt;
                            line-height: 1.2;
                            margin: 0;
                            padding: 0; /* Resetowanie paddingu ciała */
                            width: 100mm; /* Szerokość etykiety */
                            height: 70mm; /* Wysokość etykiety */
                            box-sizing: border-box;
                            background: #fff; /* Białe tło etykiety */
                            overflow: hidden; /* Ukryj co poza wymiarami */
                            display: flex;
                            flex-direction: column;
                            justify-content: space-between; /* Rozłożenie treści w pionie */
                             -webkit-print-color-adjust: exact; /* Drukuj kolory dokładnie */
                             print-color-adjust: exact;
                        }

                        .label-container {
                            width: 100%;
                            height: 100%;
                            padding: 3mm; /* Wewnętrzny padding wewnątrz wymiarów 100x70 */
                            box-sizing: border-box;
                            display: flex;
                            flex-direction: column;
                            justify-content: space-between;
                            border: 1px dashed #ccc; /* Ramka tylko na podglądzie w przeglądarce */
                        }

                        /* Sekcja nagłówka (Lokalizacja Magazynowa) */
                        .label-header {
                            font-size: 1.1em;
                            font-weight: bold;
                            text-align: center;
                            margin-bottom: 2mm; /* Odstęp po nagłówku */
                             flex-shrink: 0; /* Zapobiega kurczeniu */
                            border-bottom: 1px solid #ccc; /* Linia pod nagłówkiem */
                            padding-bottom: 1mm;
                        }
                        .location-id { font-size: 16pt; font-family: 'Courier New', Courier, monospace; } /* Styl ID lokalizacji */

                        /* Siatka informacji o produkcie */
                        .label-info-grid {
                            display: grid;
                            grid-template-columns: repeat(2, 1fr); /* Dwie równe kolumny */
                            gap: 1mm 4mm; /* Odstępy między elementami siatki (wiersz/kolumna) */
                            font-size: 0.9em;
                            flex-grow: 1; /* Rozciągnij, aby zająć dostępną przestrzeń */
                            margin-bottom: 2mm; /* Odstęp przed sekcją kodów */
                             border-bottom: 1px dotted #ccc; /* Linia pod sekcją info */
                            padding-bottom: 2mm;
                        }
                        .info-field { display: flex; flex-direction: column; } /* Pole informacji (label + value) */
                        .field-label { font-size: 8pt; color: #555; margin-bottom: 0; font-weight: normal; } /* Etykieta pola */
                        .field-value { font-size: 10pt; font-weight: bold; } /* Wartość pola */

                        /* Wyświetlanie nazwy produktu (rozciąga się na całą szerokość) */
                        .product-name-display {
                            grid-column: 1 / -1; /* Rozciągnij na całą szerokość siatki */
                            font-size: 9pt;
                            text-align: center;
                            color: #333;
                            border-top: 1px dotted #eee; /* Linia nad nazwą produktu */
                            padding-top: 1mm;
                            margin-top: 1mm;
                             white-space: nowrap; /* Zapobiega zawijaniu */
                             overflow: hidden; /* Ukrywa przepełnienie */
                             text-overflow: ellipsis; /* Dodaje wielokropek */
                        }

                        /* Sekcja kodów kreskowych */
                        .barcode-section {
                            text-align: center;
                            margin-top: auto; /* Wypycha sekcję na dół */
                            flex-shrink: 0; /* Zapobiega kurczeniu */
                             padding-top: 2mm; /* Odstęp nad kodami */
                             border-top: 1px dotted #ccc; /* Linia nad kodami */
                             display: flex; /* Użyj flexbox do rozmieszczenia kodów obok siebie, jeśli oba istnieją */
                             flex-direction: column; /* Domyślnie kody pod sobą */
                             gap: 1mm; /* Odstęp między kodami */
                        }

                        /* Styl obrazka kodu kreskowego */
                        .barcode-image {
                            display: block;
                            width: 98%; /* Szerokość max */
                            max-height: 20mm; /* Max wysokość obrazka kodu */
                            object-fit: contain; /* Zachowaj proporcje */
                            margin: 0 auto; /* Wyśrodkuj */
                            background-color: #fff; /* Białe tło pod kodem */
                             padding: 1px 0; /* Mały padding pionowy */
                        }
                         .barcode-image.location-barcode {
                             max-height: 25mm; /* Większa wysokość dla kodu lokalizacji */
                         }


                        /* Styl wartości tekstowej pod kodem */
                        .barcode-value {
                            font-size: 8pt;
                            margin-top: 0.5mm; /* Odstęp nad wartością */
                            font-family: 'Courier New', Courier, monospace; /* Font dla wartości kodu */
                        }
                         .no-barcode-text { /* Styl dla tekstu informującego o braku kodu */
                             font-style: italic;
                             color: #888;
                             font-size: 8pt;
                             text-align: center;
                         }

                        /* Stopka etykiety */
                        .label-footer-text {
                             font-size: 7pt;
                             color: #777;
                             text-align: center;
                             margin-top: 1mm; /* Odstęp od sekcji kodów */
                             flex-shrink: 0; /* Zapobiega kurczeniu */
                             border-top: 1px dotted #ccc; /* Linia nad stopką */
                             padding-top: 1mm;
                        }

                        /* Ukryj ramkę na wydruku */
                        @media print {
                            .label-container { border: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="label-container">
                        <div class="label-header">
                            <span class="field-label">Lokalizacja Magazynowa:</span>
                            <span class="location-id">${locationId}</span>
                        </div>

                        <div class="label-info-grid">
                            <div class="info-field">
                                <span class="field-label">ID PRODUKTU:</span>
                                <span class="field-value">${productId || 'Brak ID'}</span>
                            </div>
                            <div class="info-field">
                                <span class="field-label">DATA PRZYJĘCIA:</span>
                                <span class="field-value">${formattedArrivalDate}</span>
                            </div>
                            <div class="info-field">
                                <span class="field-label">NR KAT. WEW.:</span>
                                <span class="field-value">${productInternalCatalogNr || 'Brak'}</span>
                            </div>
                             <div class="info-field">
                                 <span class="field-label">ILOŚĆ:</span>
                                 <span class="field-value">${quantity || 'Brak'} szt.</span>
                             </div>
                            <div class="product-name-display">
                                ${productName || '(Brak nazwy produktu)'}
                            </div>
                        </div>

                        <div class="barcode-section">
                            ${barcodeProductSrc ? `<img src="${barcodeProductSrc}" alt="Kod kreskowy produktu ${barcodeProductData}" class="barcode-image product-barcode">` : `<span class="no-barcode-text">(Brak danych do kodu produktu)</span>`}
                             ${barcodeProductData ? `<div class="barcode-value">${barcodeProductData}</div>` : ''}

                             ${barcodeLocationSrc ? `<img src="${barcodeLocationSrc}" alt="Kod kreskowy lokalizacji ${barcodeLocationData}" class="barcode-image location-barcode">` : `<span class="no-barcode-text">(Brak danych do kodu lokalizacji)</span>`}
                             ${barcodeLocationData ? `<div class="barcode-value">${barcodeLocationData}</div>` : ''}
                        </div>

                         <div class="label-footer-text">
                             Wygenerowano: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}
                         </div>
                    </div>

                    <script>
                        // Automatyczne wywołanie okna drukowania po załadowaniu
                        window.onload = function() {
                            window.print();
                            // Opcjonalnie zamknij okno po wydruku/anulowaniu
                            window.onafterprint = function() { window.close(); };
                             // Zabezpieczenie, gdyby onafterprint nie działało
                             setTimeout(function() { if (!window.closed) window.close(); }, 1000); // Daj chwilę na dialog druku
                        };
                    </script>
                </body>
                </html>
            `;

            // --- Otwieranie Okna i Drukowanie ---
            // Użyjemy mniejszych wymiarów okna, bo etykieta jest mała
            const printWindow = window.open('', '_blank', 'width=400,height=300,resizable=yes,scrollbars=yes');
            if (!printWindow) {
                tnApp.showToast('Nie można otworzyć okna drukowania etykiety. Sprawdź blokadę popupów.', 'error');
                return;
            }
            printWindow.document.open();
            printWindow.document.write(labelContentHTML);
            printWindow.document.close();

            // Daj przeglądarce chwilę na załadowanie stylów przed wywołaniem drukowania
             setTimeout(() => {
                 try {
                     printWindow.focus(); // Ustaw focus na nowym oknie
                     // Drukowanie zostanie wywołane przez window.onload w nowym oknie
                 } catch (e) {
                     console.error("Błąd ustawiania focusu lub drukowania:", e);
                     tnApp.showToast('Błąd podczas przygotowania do druku.', 'error');
                      if (!printWindow.closed) { printWindow.close(); }
                 }
             }, 100); // Krótki timeout

        };
    }

    // --- Delegacja zdarzeń dla przycisku drukowania etykiety (Używa nowej funkcji printProductLabel) ---
    // Znajdź przyciski drukowania etykiet wewnątrz kontenera magazynu
    // Listener został przeniesiony tutaj, aby był blisko funkcji printProductLabel
     if (warehouseGridContainer) {
         warehouseGridContainer.addEventListener('click', function(event) {
             // Użyj closest, aby znaleźć przycisk lub jego rodzica
             const printButton = event.target.closest('.tn-slot-action-btn.tn-action-print-label');

             if (printButton) {
                 event.preventDefault(); // Zapobiegaj domyślnej akcji linku (jeśli byłby to link)
                 event.stopPropagation(); // Zatrzymaj propagację, aby nie kolidować z innymi listenerami

                 // Sprawdź, czy slot jest zajęty - etykiety drukujemy tylko dla zajętych slotów z produktem
                 const slotElement = printButton.closest('.tn-location-slot');
                 if (slotElement && slotElement.dataset.status === 'occupied') {
                      // Wywołaj funkcję drukowania, przekazując do niej element przycisku
                      tnApp.printProductLabel(printButton);
                 } else if (slotElement) {
                      // Jeśli slot nie jest zajęty, poinformuj użytkownika (przycisk powinien być ukryty, ale to zabezpieczenie)
                      tnApp.showToast('Etykieta produktu dostępna tylko dla zajętych lokalizacji.', 'warning');
                 }
             }
         });
     }


    // --- Obsługa Potwierdzenia Usuwania Regału ---
    // ... Twój istniejący kod usuwania regału ...
     warehouseGridContainer?.addEventListener('click', function(event) {
         const deleteButton = event.target.closest('a.tn-btn-action[href*="delete_regal"]'); // Dopasuj dokładniej selektor linku usuwania regału

         if (deleteButton) {
             event.preventDefault(); // Zatrzymaj domyślną akcję linku
             const regalId = deleteButton.dataset.regalId; // Zakładamy, że masz data-regal-id na przycisku/linku usuwania regału
             const deleteUrl = deleteButton.getAttribute('href');

             if (!regalId || !deleteUrl) {
                 console.error('Brak danych dla usuwania regału:', deleteButton);
                 tnApp.showToast('Błąd konfiguracji przycisku.', 'error');
                 return;
             }

             const confirmationMessage = `UWAGA!\nUsunięcie regału spowoduje usunięcie WSZYSTKICH lokalizacji w nim zawartych oraz potencjalnie odpięcie produktów!\n\nCzy na pewno chcesz usunąć regał '${regalId}'?`;

             if (window.confirm(confirmationMessage)) {
                 // Jeśli użytkownik potwierdzi, przejdź pod adres URL usuwania
                 window.location.href = deleteUrl;
             }
         }
     });


    // --- Logika Modali (Assign, Generate) ---
    // ... Twój istniejący kod obsługi modali przypisania i generowania ...
    const assignModalElement = document.getElementById('assignWarehouseModal');
    const assignProductSearchInput = document.getElementById('productSearchInput'); // Użyjemy productSearchInput z HTML modala
    const productSearchResultsDiv = document.getElementById('productSearchResults'); // Kontener na wyniki wyszukiwania
    const assignModalLocationIdSpan = document.getElementById('assignModalLocationId'); // Element do wyświetlenia ID lokalizacji w modalu
    const assignQuantityInput = document.getElementById('productQuantityInput'); // Pole ilości w modalu przypisania
    const assignProductToLocationBtn = document.getElementById('assignProductToLocationBtn'); // Przycisk 'Przypisz Produkt'
    const assignProductSelect = document.getElementById('tn_assign_product_id'); // To pole chyba nie istnieje w HTML modala? Użyjemy logiki z productSearchInput/productSearchResultsDiv


    let currentAssignLocationId = null; // Przechowuj ID lokalizacji dla modala przypisania
    let selectedProductForAssign = null; // Przechowuj wybrany produkt do przypisania


    // --- Obsługa Modala Przypisania Produktu ---
    if (assignModalElement && assignProductSearchInput && productSearchResultsDiv && assignModalLocationIdSpan && assignQuantityInput && assignProductToLocationBtn) {
         // Zdarzenie otwarcia modala przypisywania
         assignModalElement.addEventListener('show.bs.modal', function (event) {
             const button = event.relatedTarget; // Element, który otworzył modal (slot)
             currentAssignLocationId = button?.dataset.locationId || null; // Pobierz ID lokalizacji z data-* slotu

             if (assignModalLocationIdSpan) {
                 assignModalLocationIdSpan.textContent = currentAssignLocationId || 'Brak ID';
             }

             // Wyczyść formularz modala
             assignProductSearchInput.value = '';
             productSearchResultsDiv.innerHTML = '';
             assignQuantityInput.value = 1;
             selectedProductForAssign = null;
             assignProductToLocationBtn.disabled = true; // Wyłącz przycisk przypisania

              // Opcjonalnie: wyczyść style walidacji
             assignProductSearchInput.classList.remove('is-invalid');
             assignQuantityInput.classList.remove('is-invalid');
         });

         // Obsługa wyszukiwania produktów (keyup na input)
         let searchTimeout = null;
         assignProductSearchInput.addEventListener('input', function() {
             clearTimeout(searchTimeout);
             const query = this.value.trim();

             if (query.length < 2) { // Szukaj od 2 znaków
                 productSearchResultsDiv.innerHTML = ''; // Wyczyść wyniki
                 selectedProductForAssign = null;
                 assignProductToLocationBtn.disabled = true;
                 return;
             }

             searchTimeout = setTimeout(() => {
                 performProductSearchForAssign(query); // Wywołaj funkcję wyszukiwania
             }, 300); // Opóźnienie 300ms
         });

         // Funkcja wykonująca wyszukiwanie produktów dla modala
         const performProductSearchForAssign = (query) => {
             productSearchResultsDiv.innerHTML = '<div class="list-group-item text-center text-muted small py-2">Szukam...</div>';
             selectedProductForAssign = null;
             assignProductToLocationBtn.disabled = true;

             // Wysłanie żądania AJAX do index.php z akcją search_products
             // Upewnij się, że index.php jest dostępny pod tym adresem i obsługuje akcję search_products
             fetch(`index.php?action=search_products&query=${encodeURIComponent(query)}`)
                 .then(response => {
                     if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); }
                     return response.json();
                 })
                 .then(data => {
                     productSearchResultsDiv.innerHTML = ''; // Wyczyść wskaźnik ładowania

                     if (data.success && data.products && data.products.length > 0) {
                         data.products.forEach(product => {
                             const item = document.createElement('button');
                             item.classList.add('list-group-item', 'list-group-item-action');
                             item.setAttribute('type', 'button');
                             // Przekazujemy pełne dane produktu w dataset przycisku wyniku
                             item.dataset.product = JSON.stringify(product);

                             item.innerHTML = `
                                 <div class="d-flex w-100 justify-content-between align-items-center">
                                     <h6 class="mb-1 text-truncate me-2">${htmlspecialchars(product.name || 'Brak nazwy')}</h6>
                                     <small class="text-muted flex-shrink-0">ID: ${htmlspecialchars(product.id || 'Brak')}</small>
                                 </div>
                                 <p class="mb-1 small text-muted">Nr kat.: ${htmlspecialchars(product.catalog_nr || 'Brak')}</p>
                             `;

                             // Obsługa kliknięcia w wynik wyszukiwania
                             item.addEventListener('click', function() {
                                 // Usuń zaznaczenie z poprzednich wyników
                                 productSearchResultsDiv.querySelectorAll('.list-group-item').forEach(resItem => {
                                     resItem.classList.remove('active');
                                 });
                                 // Zaznacz kliknięty wynik
                                 this.classList.add('active');

                                 // Zapisz wybrany produkt i aktywuj przycisk przypisania
                                 selectedProductForAssign = JSON.parse(this.dataset.product);
                                 assignProductToLocationBtn.disabled = false;

                                 // Opcjonalnie: Wypełnij pole wyszukiwania nazwą produktu, aby pokazać co zostało wybrane
                                 assignProductSearchInput.value = selectedProductForAssign.name || selectedProductForAssign.id;
                                 // Opcjonalnie: Wyczyść listę wyników po wybraniu
                                 productSearchResultsDiv.innerHTML = '';
                             });

                             productSearchResultsDiv.appendChild(item);
                         });
                     } else {
                         productSearchResultsDiv.innerHTML = '<div class="list-group-item text-center text-muted small py-2">Brak wyników.</div>';
                         if (!data.success) {
                             console.error('Błąd wyszukiwania produktów (server):', data.message);
                             // Opcjonalnie wyświetl komunikat z serwera
                             // productSearchResultsDiv.innerHTML = `<div class="list-group-item text-center text-danger small py-2">${htmlspecialchars(data.message || 'Nieznany błąd serwera.')}</div>`;
                         }
                     }
                 })
                 .catch(error => {
                     console.error('Błąd podczas wyszukiwania produktów (fetch):', error);
                     productSearchResultsDiv.innerHTML = '<div class="list-group-item text-center text-danger small py-2">Błąd ładowania wyników.</div>';
                 });
         };

         // Obsługa przycisku "Przypisz Produkt" w modalu
         assignProductToLocationBtn.addEventListener('click', function() {
             if (!currentAssignLocationId || !selectedProductForAssign) {
                 tnApp.showToast('Wybierz lokalizację i produkt.', 'warning');
                 return;
             }

             const quantity = parseInt(assignQuantityInput.value, 10);

             if (isNaN(quantity) || quantity <= 0) {
                 tnApp.showToast('Wprowadź poprawną ilość (liczba większa od 0).', 'warning');
                  assignQuantityInput.classList.add('is-invalid');
                 return;
             } else {
                  assignQuantityInput.classList.remove('is-invalid');
             }

             // Sprawdź, czy produkt ma ID
             if (!selectedProductForAssign.id) {
                 tnApp.showToast('Wybrany produkt nie ma ID.', 'error');
                 return;
             }


             // Wyłącz przycisk i pokaż, że trwa przypisywanie
             this.disabled = true;
             this.textContent = 'Przypisywanie...';

             // Wysłanie danych do skryptu akcji assign_warehouse przez index.php
             const formData = new FormData();
             formData.append('action', 'assign_warehouse'); // Akcja dla routera index.php
             formData.append('location_id', currentAssignLocationId);
             formData.append('product_id', selectedProductForAssign.id); // Użyj ID wybranego produktu
             formData.append('quantity', quantity);

             // Dodaj token CSRF, jeśli Twój index.php go wymaga dla akcji POST
             // Znajdź element zawierający token CSRF w Twoim szablonie HTML
             const csrfTokenElement = document.querySelector('meta[name="csrf-token"]') || document.querySelector('input[name="tn_csrf_token"]');
             if (csrfTokenElement && csrfTokenElement.content) { // Jeśli token jest w meta tagu
                  formData.append('tn_csrf_token', csrfTokenElement.content);
             } else if (csrfTokenElement && csrfTokenElement.value) { // Jeśli token jest w input field
                  formData.append('tn_csrf_token', csrfTokenElement.value);
             } else {
                  console.warn("Element z tokenem CSRF nie znaleziony. Żądanie może zakończyć się błędem 403.");
                  // Możesz zrezygnować z wysyłania żądania, jeśli token jest wymagany
                  // this.disabled = false; this.textContent = 'Przypisz Produkt';
                  // tnApp.showToast('Błąd: Token CSRF nie znaleziony.', 'error'); return;
             }


             fetch('index.php', { // Adres URL do index.php
                 method: 'POST',
                 body: formData
             })
             .then(response => {
                 // Zwracaj response.json() tylko jeśli status jest OK, w przeciwnym razie rzuć błąd
                 if (!response.ok) {
                     // Próbuj odczytać odpowiedź jako JSON nawet przy błędzie, bo serwer może zwrócić błąd w JSON
                     return response.json().then(errorData => {
                          // Jeśli udało się sparsować JSON, rzuć błąd z wiadomością z serwera
                          throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                     }).catch(() => {
                         // Jeśli nie udało się sparsować JSON (np. serwer zwrócił HTML), rzuć generyczny błąd
                         throw new Error(`HTTP error! status: ${response.status}`);
                     });
                 }
                 return response.json(); // Parsuj odpowiedź JSON przy sukcesie
             })
             .then(data => {
                  // Przywróć przycisk do stanu początkowego
                  this.disabled = false;
                  this.textContent = 'Przypisz Produkt';

                 if (data.success) {
                     tnApp.showToast('Produkt przypisany pomyślnie!', 'success');
                     // Zamknij modal
                     const modal = bootstrap.Modal.getInstance(assignModalElement);
                     if (modal) modal.hide();

                     // Odśwież stronę, aby zobaczyć zmiany (najprostsze rozwiązanie)
                     window.location.reload();

                     // Bardziej zaawansowane: zaktualizuj tylko konkretny slot w widoku bez przeładowania
                     // Wymaga zwrócenia przez akcję assign_warehouse danych potrzebnych do renderowania slotu
                     // const updatedSlotData = data.slotData; // Przykładowe pole z odpowiedzi JSON
                     // updateSlotInView(currentAssignLocationId, updatedSlotData); // Funkcja do aktualizacji slotu
                 } else {
                     // Błąd podczas przypisywania (zwrócony przez serwer PHP)
                     tnApp.showToast('Błąd przypisania: ' + (data.message || 'Nieznany błąd serwera.'), 'danger');
                 }
             })
             .catch(error => {
                  // Przywróć przycisk do stanu początkowego
                  this.disabled = false;
                  this.textContent = 'Przypisz Produkt';
                 console.error('Błąd podczas wysyłania żądania przypisania:', error);
                 // Wyświetl komunikat błędu z konsoli lub generyczny
                 tnApp.showToast('Wystąpił błąd komunikacji: ' + error.message, 'danger');
             });
         });
    } else {
        console.warn('Elementy modala przypisania produktu nie znalezione w DOM.');
    }


    // --- Logika Modala Generowania Lokalizacji ---
    // ... Twój istniejący kod obsługi modala generowania ...
    const generateLocationsModalElement = document.getElementById('generateLocationsModal');
     const generateLocationsForm = document.getElementById('generateLocationsForm'); // Zakładam, że masz formularz w modalu
     const selectRegalToGenerate = document.getElementById('selectRegalToGenerate'); // Select z regałami
     const confirmGenerateOverwrite = document.getElementById('confirmGenerateOverwrite'); // Checkbox potwierdzenia
     const startGenerateLocationsBtn = document.getElementById('startGenerateLocationsBtn'); // Przycisk generowania

    // Walidacja formularza generowania (przykład)
    const validateGenerateForm = () => {
        let isValid = true;
        // Sprawdź, czy wybrano regały, jeśli lista regałów nie jest pusta
        if (selectRegalToGenerate && selectRegalToGenerate.options.length > 0 && selectRegalToGenerate.selectedOptions.length === 0) {
            // Jeśli lista regałów nie jest pusta, ale nic nie wybrano, ostrzeż, ale pozwól kontynuować (domyślnie wszystkie)
             selectRegalToGenerate.classList.remove('is-invalid'); // Usuń na wypadek, gdyby było
        } else if (selectRegalToGenerate && selectRegalToGenerate.options.length === 0 && selectRegalToGenerate.selectedOptions.length === 0) {
             // Brak regałów do wyboru i nic nie wybrano - to nie jest błąd walidacji formularza, ale logiczny.
             // Logika przycisku powinna to obsłużyć.
             selectRegalToGenerate.classList.remove('is-invalid');
        } else {
            // Jeśli wybrano co najmniej jeden regał, walidacja OK (dla tego pola)
             selectRegalToGenerate.classList.remove('is-invalid');
        }

        // Sprawdź potwierdzenie nadpisania
        if (confirmGenerateOverwrite && !confirmGenerateOverwrite.checked) {
            isValid = false; // Formularz nie jest ważny bez potwierdzenia
        }

        // Możesz dodać walidację zakresów poziomów/slotów, jeśli formularz ma takie pola
        // const levelStartInput = document.getElementById('tn_generate_level_start');
        // const levelEndInput = document.getElementById('tn_generate_level_end');
        // if (!validateRange(levelStartInput, levelEndInput)) isValid = false;
        // Podobnie dla slotów

        return isValid;
    };

    // Włącz/wyłącz przycisk generowania na podstawie potwierdzenia nadpisania
     if (confirmGenerateOverwrite && startGenerateLocationsBtn) {
         confirmGenerateOverwrite.addEventListener('change', function() {
             // Włącz przycisk tylko jeśli checkbox jest zaznaczony I formularz jest wstępnie ważny
             startGenerateLocationsBtn.disabled = !(this.checked && validateGenerateForm());
         });
     }
    // Dodaj listener na zmiany w selectRegalToGenerate, który też wpłynie na stan przycisku
     selectRegalToGenerate?.addEventListener('change', function() {
          if (confirmGenerateOverwrite) { // Sprawdzamy, czy checkbox istnieje
             startGenerateLocationsBtn.disabled = !(confirmGenerateOverwrite.checked && validateGenerateForm());
          } else {
              // Jeśli checkboxa nie ma, przycisk zawsze jest włączony, jeśli formularz ważny
              startGenerateLocationsBtn.disabled = !validateGenerateForm();
          }
     });

     // Walidacja przy próbie submitu formularza
     generateLocationsForm?.addEventListener('submit', function(event) {
         if (!validateGenerateForm()) {
              event.preventDefault(); // Zatrzymaj submit
              event.stopPropagation(); // Zatrzymaj propagację
              tnApp.showToast('Proszę potwierdzić chęć nadpisania danych.', 'warning');
              // Możesz też dodać wizualne wskazanie na checkbox
              if(confirmGenerateOverwrite) confirmGenerateOverwrite.classList.add('is-invalid');
         } else {
              if(confirmGenerateOverwrite) confirmGenerateOverwrite.classList.remove('is-invalid');
              // Formularz jest ważny, można kontynuować submit (lub AJAX)
         }
     });

    // Obsługa kliknięcia przycisku "Generuj Lokalizacje"
     if (startGenerateLocationsBtn) {
         startGenerateLocationsBtn.addEventListener('click', function(event) {
             event.preventDefault(); // Zawsze zatrzymaj domyślne działanie przycisku (submit)

             // Ponowna walidacja przed wysłaniem
             if (!validateGenerateForm()) {
                 tnApp.showToast('Popraw błędy w formularzu generowania.', 'warning');
                 return;
             }

             const selectedRegals = Array.from(selectRegalToGenerate.selectedOptions).map(option => option.value);

             // Dodatkowe potwierdzenie, jeśli nie wybrano regałów (generuj dla wszystkich)
             if (selectedRegals.length === 0 && selectRegalToGenerate && selectRegalToGenerate.options.length > 0) {
                 if (!confirm('Nie wybrano żadnych regałów. Czy na pewno chcesz wygenerować lokalizacje dla WSZYSTKICH zdefiniowanych regałów? Istniejące lokalizacje zostaną nadpisane.')) {
                     return; // Użytkownik anulował
                 }
                 // Jeśli potwierdzono, kontynuujemy z pustą listą (backend zinterpretuje jako 'wszystkie')
             } else if (selectedRegals.length === 0 && selectRegalToGenerate && selectRegalToGenerate.options.length === 0) {
                 // Brak regałów do wyboru w ogóle
                 tnApp.showToast('Brak zdefiniowanych regałów, dla których można wygenerować lokalizacje.', 'warning');
                 return;
             }


             // Wyłącz przycisk i pokaż, że trwa generowanie
             this.disabled = true;
             this.textContent = 'Generowanie...';
             // Opcjonalnie dodaj spinner
             // this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generowanie...';


             // Przygotowanie danych do wysłania (POST)
             const formData = new FormData();
             formData.append('action', 'generate_locations'); // Akcja dla routera index.php
             // Jeśli selectedRegals jest pustą tablicą, PHP powinno to zinterpretować jako 'wszystkie'
             // Jeśli wybrano regały, wysyłamy listę ID
             formData.append('regals', JSON.stringify(selectedRegals));

              // Dodaj token CSRF, jeśli Twój index.php go wymaga dla akcji POST
              const csrfTokenElement = document.querySelector('meta[name="csrf-token"]') || document.querySelector('input[name="tn_csrf_token"]');
              if (csrfTokenElement && csrfTokenElement.content) { // Jeśli token jest w meta tagu
                   formData.append('tn_csrf_token', csrfTokenElement.content);
              } else if (csrfTokenElement && csrfTokenElement.value) { // Jeśli token jest w input field
                   formData.append('tn_csrf_token', csrfTokenElement.value);
              } else {
                   console.warn("Element z tokenem CSRF nie znaleziony. Żądanie może zakończyć się błędem 403.");
                   // Jeśli token jest wymagany, odblokuj przycisk i poinformuj użytkownika
                   // this.disabled = !(confirmGenerateOverwrite?.checked ?? true); // Przywróć stan przycisku
                   // this.textContent = 'Generuj Lokalizacje';
                   // tnApp.showToast('Błąd: Token CSRF nie znaleziony.', 'error'); return;
              }


             fetch('index.php', { // Adres URL do index.php
                 method: 'POST',
                 body: formData
             })
             .then(response => {
                  // Sprawdzamy status odpowiedzi i próbujemy sparsować JSON
                  if (!response.ok) {
                       // Próbuj odczytać odpowiedź jako JSON nawet przy błędzie
                       return response.json().then(errorData => {
                            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                       }).catch(() => {
                           // Jeśli nie udało się sparsować JSON, rzuć generyczny błąd HTTP
                           throw new Error(`HTTP error! status: ${response.status}`);
                       });
                  }
                  return response.json(); // Parsuj JSON przy sukcesie
             })
             .then(data => {
                 // Przywróć przycisk do stanu początkowego
                 this.disabled = !(confirmGenerateOverwrite?.checked ?? true); // Stan zależy od checkboxa potwierdzenia
                 this.textContent = 'Generuj Lokalizacje';
                 // Usuń spinner, jeśli był
                 // this.innerHTML = 'Generuj Lokalizacje';


                 if (data.success) {
                     tnApp.showToast('Lokalizacje wygenerowane pomyślnie!', 'success');
                     // Zamknij modal
                     const modal = bootstrap.Modal.getInstance(generateLocationsModalElement);
                     if (modal) modal.hide();

                     // Odśwież stronę, aby zobaczyć nowe/zaktualizowane lokalizacje
                     window.location.reload();

                 } else {
                     // Błąd podczas generowania (zwrócony przez serwer PHP)
                     tnApp.showToast('Błąd generowania: ' + (data.message || 'Nieznany błąd serwera.'), 'danger');
                 }
             })
             .catch(error => {
                 // Przywróć przycisk do stanu początkowego
                 this.disabled = !(confirmGenerateOverwrite?.checked ?? true);
                 this.textContent = 'Generuj Lokalizacje';
                 // Usuń spinner, jeśli był
                 // this.innerHTML = 'Generuj Lokalizacje';

                 console.error('Błąd podczas wysyłania żądania generowania:', error);
                 // Wyświetl komunikat błędu z konsoli lub generyczny
                 tnApp.showToast('Wystąpił błąd komunikacji: ' + error.message, 'danger');
             });
         });
     } else {
         console.warn('Elementy modala generowania lokalizacji nie znalezione w DOM.');
     }

    // Funkcja pomocnicza do htmlspecialchars w JS (dla bezpieczeństwa)
     const htmlspecialchars = (str) => {
         if (typeof str !== 'string') return str; // Zwróć niezmienione, jeśli nie jest stringiem
         const map = {
             '&': '&amp;',
             '<': '&lt;',
             '>': '&gt;',
             '"': '&quot;',
             "'": '&#039;'
         };
         return str.replace(/[&<>"']/g, function(m) { return map[m]; });
     };


    // Finalnie zastosuj filtry po załadowaniu strony
    filterWarehouseView();

}); // Koniec DOMContentLoaded