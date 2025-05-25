<?php // --- JavaScript dla Widoku Magazynu --- ?>
document.addEventListener('DOMContentLoaded', function () {

    // --- Inicjalizacja globalnego obiektu aplikacji (jeśli nie istnieje) ---
    if (typeof window.tnApp === 'undefined') window.tnApp = {};

    // --- Funkcje Pomocnicze JS ---
    // Kopiowanie do schowka
    if (typeof window.tnApp.copyToClipboard === 'undefined') {
        window.tnApp.copyToClipboard = (text) => {
            if (!navigator.clipboard) {
                tnApp.showToast('Twoja przeglądarka nie obsługuje kopiowania do schowka.', 'warning');
                return;
            }
            navigator.clipboard.writeText(text).then(() => {
                tnApp.showToast(`Skopiowano ID: ${text}`);
            }).catch(err => {
                console.error('Błąd kopiowania do schowka: ', err);
                tnApp.showToast('Nie udało się skopiować ID.', 'error');
            });
        };
    }
    // Wyświetlanie tostów (komunikatów)
    if (typeof window.tnApp.showToast === 'undefined') {
        window.tnApp.showToast = (message, type = 'info', delay = 3500) => {
            const toastContainer = document.getElementById('toastContainer');
            if (!toastContainer) { console.error("Brak kontenera tostów #toastContainer"); return; }
            const toastId = 'toast-' + Date.now();
            const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : type === 'warning' ? 'bg-warning text-dark' : 'bg-primary';
            const iconHtml = type === 'success' ? '<i class="bi bi-check-circle-fill me-2"></i>' : type === 'error' ? '<i class="bi bi-exclamation-triangle-fill me-2"></i>' : type === 'warning' ? '<i class="bi bi-exclamation-circle-fill me-2"></i>' : '<i class="bi bi-info-circle-fill me-2"></i>';

            const toastHtml = `
                <div class="toast align-items-center text-white ${bgClass} border-0 fade" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${iconHtml} ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>`;
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, { delay: delay, autohide: true });
            toast.show();
            toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove()); // Usuń element po ukryciu
        };
    }
    // Funkcja otwierania modala regału (zakładamy, że istnieje i jest bardziej rozbudowana)
    if (typeof window.tnApp.openRegalModal === 'undefined') {
        window.tnApp.openRegalModal = (regalData = null) => {
             console.warn("Funkcja tnApp.openRegalModal jest tylko placeholderem. Zaimplementuj logikę otwierania i wypełniania modala regału.");
             // Tutaj powinna być logika otwierania modala #regalModal (jeśli istnieje)
             // i wypełniania go danymi z regalData (jeśli edycja) lub czyszczenia (jeśli dodawanie)
             alert(`Akcja dla regału: ${regalData ? 'Edycja ' + regalData.tn_id_regalu : 'Dodaj nowy'}`);
        };
    }


    // --- Inicjalizacja Komponentów Bootstrap ---
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('#warehouseGridContainer [data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl, { trigger: 'hover' }); // Tooltipy na hover
    });

    const popoverTriggerList = [].slice.call(document.querySelectorAll('#warehouseGridContainer [data-bs-toggle="popover"]'));
    const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        try {
            const popoverContentData = JSON.parse(popoverTriggerEl.getAttribute('data-popover-content'));
            return new bootstrap.Popover(popoverTriggerEl, {
                html: true,
                trigger: 'click', // Popover na kliknięcie
                placement: 'auto',
                title: popoverContentData.title,
                content: popoverContentData.content,
                customClass: 'tn-warehouse-popover', // Klasa dla ew. styli CSS
                sanitize: false // Umożliwia HTML w treści (np. <img>) - używaj ostrożnie!
            });
        } catch (e) {
             console.error("Błąd parsowania danych popovera:", e, popoverTriggerEl.getAttribute('data-popover-content'));
             return null; // Zwróć null, aby uniknąć błędów w mapowaniu
        }
    }).filter(p => p !== null); // Odfiltruj nulle, jeśli parsowanie się nie udało

    // Zamykanie innych popoverów przy otwieraniu nowego
    document.addEventListener('click', function (event) {
        const isPopoverTarget = event.target.closest('[data-bs-toggle="popover"]');
        const isInsidePopover = event.target.closest('.popover.show');

        popoverList.forEach(popover => {
            const popoverElement = popover.tip; // Element DOM popovera
            const triggerElement = popover.element; // Element, który wyzwolił popover

            // Jeśli kliknięto poza popoverem i poza jego wyzwalaczem
            if (!isInsidePopover && triggerElement !== isPopoverTarget && !triggerElement.contains(isPopoverTarget)) {
                popover.hide();
            }
            // Jeśli kliknięto na inny wyzwalacz popovera, zamknij ten
            else if (isPopoverTarget && triggerElement !== isPopoverTarget && !triggerElement.contains(isPopoverTarget)) {
                 popover.hide();
            }
        });
    }, true); // Użyj capture phase


    // --- Logika Modala Kodu Kreskowego Lokalizacji ---
    const barcodeModalElement = document.getElementById('barcodeModal');
    const barcodeModalImage = document.getElementById('barcodeModalImage');
    const barcodeModalLocationIdSpan = document.getElementById('barcodeModalLocationId');
    const warehouseGridContainer = document.getElementById('warehouseGridContainer'); // Używane w wielu miejscach

    if (barcodeModalElement && barcodeModalImage && barcodeModalLocationIdSpan && warehouseGridContainer) {
        // Użyj delegacji zdarzeń na kontenerze siatki
        warehouseGridContainer.addEventListener('click', function(event) {
            const barcodeTrigger = event.target.closest('.tn-slot-barcode'); // Sprawdź czy kliknięto na obszar kodu
            if (barcodeTrigger) {
                event.stopPropagation(); // Zapobiegaj innym akcjom (np. otwarciu modala przypisania)
                const locationId = barcodeTrigger.dataset.locationId;
                const largeBarcodeSrc = barcodeTrigger.dataset.barcodeSrc;

                if (locationId && largeBarcodeSrc) {
                    barcodeModalLocationIdSpan.textContent = locationId;
                    barcodeModalImage.src = largeBarcodeSrc;
                    barcodeModalImage.alt = `Powiększony kod kreskowy dla ${locationId}`;
                    // Pokaż modal Bootstrapa
                    const modal = bootstrap.Modal.getOrCreateInstance(barcodeModalElement);
                    modal.show();
                } else {
                     console.warn("Brak danych dla modala kodu kreskowego:", barcodeTrigger.dataset);
                }
            }
        });
    } else {
        console.warn('Nie znaleziono wszystkich elementów wymaganych dla modala kodu kreskowego lokalizacji.');
    }

    // Funkcja drukowania kodu kreskowego z modala
    if (typeof window.tnApp.printBarcodeModal === 'undefined') {
        window.tnApp.printBarcodeModal = () => {
            const locationId = barcodeModalLocationIdSpan?.textContent;
            const barcodeImageSrc = barcodeModalImage?.src;

            if (!locationId || !barcodeImageSrc) {
                alert('Błąd: Nie można odczytać danych do druku kodu kreskowego lokalizacji.');
                return;
            }

            const printWindow = window.open('', '_blank', 'height=400,width=600');
            if (!printWindow) {
                alert('Nie można otworzyć okna drukowania. Sprawdź, czy przeglądarka nie blokuje wyskakujących okienek.');
                return;
            }

            // Generowanie HTML do druku
            printWindow.document.write(`
                <!DOCTYPE html>
                <html lang="pl">
                <head>
                    <meta charset="UTF-8">
                    <title>Drukuj Kod Lokalizacji: ${locationId}</title>
                    <style>
                        body { text-align: center; margin-top: 30px; font-family: sans-serif; }
                        img { max-width: 90%; height: auto; max-height: 150px; object-fit: contain; }
                        h4 { margin-bottom: 20px; }
                        @media print {
                            body { margin-top: 10px; } /* Mniejszy margines na wydruku */
                            @page { margin: 1cm; } /* Marginesy strony */
                        }
                    </style>
                </head>
                <body>
                    <h4>Lokalizacja Magazynowa:</h4>
                    <h2>${locationId}</h2>
                    <img src="${barcodeImageSrc}" alt="Kod kreskowy ${locationId}">
                    <script>
                        window.onload = function() {
                            window.print(); // Wywołaj drukowanie
                            // Próba zamknięcia okna po drukowaniu (może nie działać we wszystkich przeglądarkach)
                            window.onafterprint = function(){ window.close(); };
                            // Zapasowe zamknięcie po chwili
                            setTimeout(function(){ if(!window.closed) window.close(); }, 5000);
                        };
                    </script>
                </body>
                </html>
            `);
            printWindow.document.close(); // Zakończ pisanie do dokumentu
            printWindow.focus(); // Skupienie okna
        };
    }


    // --- Logika Filtrowania Widoku ---
    const filterRegalSelect = document.getElementById('tn_filter_regal');
    const filterStatusSelect = document.getElementById('tn_filter_status');
    const filterTextInput = document.getElementById('tn_filter_text');
    const clearFiltersBtn = document.getElementById('tn_clear_filters_btn');
    const noResultsMessage = document.getElementById('noFilterResultsMessage');

    const filterWarehouseView = () => {
        if (!warehouseGridContainer || !noResultsMessage) {
             console.error("Brak #warehouseGridContainer lub #noFilterResultsMessage do filtrowania.");
             return;
        }
        const allLocationSlots = warehouseGridContainer.querySelectorAll('.tn-location-slot');
        const allRegalCards = warehouseGridContainer.querySelectorAll('.tn-regal-card');

        const selectedRegal = filterRegalSelect?.value ?? 'all';
        const selectedStatus = filterStatusSelect?.value ?? 'all';
        const filterText = filterTextInput?.value.toLowerCase().trim() ?? '';

        let visibleSlotsCount = 0;

        // Filtrowanie poszczególnych slotów
        allLocationSlots.forEach(slot => {
            const slotRegalId = slot.dataset.regalId;
            const slotStatus = slot.dataset.status;
            const slotFilterTextData = slot.dataset.filterText?.toLowerCase() || '';

            const regalMatch = selectedRegal === 'all' || slotRegalId === selectedRegal;
            const statusMatch = selectedStatus === 'all' || slotStatus === selectedStatus;
            const textMatch = filterText === '' || slotFilterTextData.includes(filterText);

            if (regalMatch && statusMatch && textMatch) {
                slot.style.display = ''; // Użyj domyślnego stylu (flex)
                slot.style.opacity = '1';
                slot.style.pointerEvents = 'auto';
                visibleSlotsCount++;
            } else {
                slot.style.display = 'none'; // Całkowicie ukryj niefiltrowane sloty
                slot.style.opacity = '0'; // Dodatkowo dla pewności
                slot.style.pointerEvents = 'none';
            }
        });

        // Pokazywanie/ukrywanie całych kart regałów
        allRegalCards.forEach(card => {
            const cardRegalId = card.dataset.regalId;
            const regalFilterMatch = selectedRegal === 'all' || cardRegalId === selectedRegal;
            // Sprawdź, czy w tej karcie jest *jakikolwiek* widoczny slot (po filtrowaniu status/tekst)
            const hasVisibleSlotsInside = card.querySelector('.tn-location-slot[style*="display: flex"], .tn-location-slot:not([style*="display: none"])') !== null;

            if (regalFilterMatch && hasVisibleSlotsInside) {
                card.style.display = ''; // Pokaż kartę regału
                card.style.opacity = '1';
            } else {
                card.style.display = 'none'; // Ukryj całą kartę regału
                card.style.opacity = '0';
            }
        });

        // Pokaż/ukryj komunikat o braku wyników
        noResultsMessage.classList.toggle('d-none', visibleSlotsCount > 0);
    };

    // Nasłuchiwanie na zmiany filtrów
    filterRegalSelect?.addEventListener('change', filterWarehouseView);
    filterStatusSelect?.addEventListener('change', filterWarehouseView);
    filterTextInput?.addEventListener('input', filterWarehouseView); // 'input' reaguje natychmiast
    clearFiltersBtn?.addEventListener('click', () => {
        if(filterRegalSelect) filterRegalSelect.value = 'all';
        if(filterStatusSelect) filterStatusSelect.value = 'all';
        if(filterTextInput) filterTextInput.value = '';
        filterWarehouseView(); // Zastosuj wyczyszczone filtry
    });

    filterWarehouseView(); // Wywołaj filtrowanie przy pierwszym ładowaniu strony


    // --- Kontrola Gęstości Widoku ---
    const densityButtons = document.querySelectorAll('input[name="density"]');
    // Odczytaj zapisaną gęstość lub użyj domyślnej 'normal'
    const savedDensity = localStorage.getItem('warehouseDensity') || 'normal';

    const setDensity = (densityValue) => {
        if (!warehouseGridContainer) return;
        // Usuń stare klasy, dodaj nową
        warehouseGridContainer.className = warehouseGridContainer.className.replace(/warehouse-density-\w+/g, '');
        warehouseGridContainer.classList.add(`warehouse-density-${densityValue}`);
        // Zapisz wybór w localStorage
        localStorage.setItem('warehouseDensity', densityValue);
        // Zaznacz odpowiedni przycisk radio
        const radioBtn = document.getElementById(`density${densityValue.charAt(0).toUpperCase() + densityValue.slice(1)}`);
        if(radioBtn) radioBtn.checked = true;
        // Zaktualizuj aktywne etykiety (opcjonalne, jeśli Bootstrap sam tego nie robi dobrze)
        document.querySelectorAll('label[for^="density"]').forEach(lbl => lbl.classList.remove('active'));
        document.querySelector(`label[for="density${densityValue.charAt(0).toUpperCase() + densityValue.slice(1)}"]`)?.classList.add('active');
        // Ukryj tooltipy, które mogły zostać po zmianie layoutu
        tooltipList.forEach(tooltip => tooltip.hide());
    };

    densityButtons.forEach(button => {
        // Zaznacz zapisany/domyślny przycisk przy ładowaniu
        if (button.value === savedDensity) {
            button.checked = true;
             document.querySelector(`label[for="${button.id}"]`)?.classList.add('active');
        } else {
             document.querySelector(`label[for="${button.id}"]`)?.classList.remove('active');
        }
        // Dodaj nasłuchiwanie na zmianę
        button.addEventListener('change', (event) => setDensity(event.target.value));
    });

    setDensity(savedDensity); // Ustaw gęstość przy ładowaniu strony


    // --- Podświetlanie Powiązanych Produktów na Hover ---
    warehouseGridContainer?.addEventListener('mouseover', (event) => {
        const targetSlot = event.target.closest('.tn-location-slot.status-occupied');
        // Sprawdź czy slot jest widoczny (nie ukryty przez filtr) i czy ma ID produktu
        if (!targetSlot || targetSlot.style.display === 'none' || !targetSlot.dataset.productId) return;

        const productId = targetSlot.dataset.productId;
        // Znajdź wszystkie inne widoczne sloty z tym samym produktem
        const relatedSlots = warehouseGridContainer.querySelectorAll(
            `.tn-location-slot.status-occupied[data-product-id="${productId}"]:not([style*="display: none"])`
        );
        relatedSlots.forEach(slot => {
            if (slot !== targetSlot) { // Nie podświetlaj samego siebie
                slot.classList.add('tn-slot-related-highlight');
            }
        });
    });

    warehouseGridContainer?.addEventListener('mouseout', (event) => {
        const targetSlot = event.target.closest('.tn-location-slot.status-occupied');
        if (targetSlot && targetSlot.dataset.productId) {
            const productId = targetSlot.dataset.productId;
             // Sprawdź czy kursor nie przeniósł się na inny powiązany slot
            const relatedTargetSlot = event.relatedTarget?.closest(`.tn-location-slot.status-occupied[data-product-id="${productId}"]`);
            if(!relatedTargetSlot) { // Jeśli kursor opuścił grupę powiązanych slotów
                const highlightedSlots = warehouseGridContainer.querySelectorAll('.tn-slot-related-highlight');
                highlightedSlots.forEach(slot => slot.classList.remove('tn-slot-related-highlight'));
            }
        } else { // Jeśli kursor opuścił slot niebędący zajętym lub obszar poza slotami
             const highlightedSlots = warehouseGridContainer.querySelectorAll('.tn-slot-related-highlight');
             highlightedSlots.forEach(slot => slot.classList.remove('tn-slot-related-highlight'));
        }
    });


    // --- Efekt Powiększenia Slota na Hover (Opcjonalny) ---
    let lastHoveredSlot = null;
    warehouseGridContainer?.addEventListener('mouseover', function(event) {
        const currentSlot = event.target.closest('.tn-location-slot');
        if (currentSlot && currentSlot !== lastHoveredSlot && currentSlot.style.display !== 'none') {
            // Usuń efekt ze starego slotu
            if (lastHoveredSlot) {
                lastHoveredSlot.style.transform = 'scale(1)';
                lastHoveredSlot.style.zIndex = lastHoveredSlot.getAttribute('data-default-zindex') || '1';
            }
            // Dodaj efekt do nowego slotu
            const currentZIndex = window.getComputedStyle(currentSlot).zIndex;
            const defaultZIndex = (currentZIndex === 'auto' || currentZIndex === '0') ? '1' : currentZIndex;
            currentSlot.setAttribute('data-default-zindex', defaultZIndex);
            currentSlot.style.zIndex = '10'; // Wyniesienie ponad inne
            currentSlot.style.transform = `scale(1.05)`; // Lekkie powiększenie
            lastHoveredSlot = currentSlot;
        }
    });
    warehouseGridContainer?.addEventListener('mouseout', function(event) {
        const currentSlot = event.target.closest('.tn-location-slot');
        // Sprawdź czy opuszczono slot i czy nie wjechano na inny slot
        if (lastHoveredSlot && (!event.relatedTarget || !event.relatedTarget.closest('.tn-location-slot'))) {
            lastHoveredSlot.style.transform = 'scale(1)';
            lastHoveredSlot.style.zIndex = lastHoveredSlot.getAttribute('data-default-zindex') || '1';
            lastHoveredSlot = null;
        }
    });


    // --- Logika Drukowania Etykiety Produktu ---
    if (typeof window.tnApp.printProductLabel === 'undefined') {
        window.tnApp.printProductLabel = (buttonElement) => {
            const slotElement = buttonElement.closest('.tn-location-slot');
            if (!slotElement) {
                console.error('Nie znaleziono elementu slotu dla przycisku drukowania etykiety.');
                tnApp.showToast('Błąd: Nie znaleziono slotu.', 'error');
                return;
            }

            // Pobierz wszystkie potrzebne dane z atrybutów data-* slotu
            const locationId = slotElement.dataset.locationId;
            const productId = slotElement.dataset.productId;
            const productName = slotElement.dataset.productName;
            const productCatalogNr = slotElement.dataset.productCatalogNr;

            // Podstawowa walidacja danych
            if (!locationId || !productId || !productName || !productCatalogNr) {
                 console.error('Brakujące dane dla etykiety:', slotElement.dataset);
                 tnApp.showToast('Błąd: Brak pełnych danych produktu/lokalizacji dla etykiety.', 'error');
                 return;
            }

            const printDate = new Date().toLocaleDateString('pl-PL', { year: 'numeric', month: '2-digit', day: '2-digit'});
            // Generuj URL kodu kreskowego LOKALIZACJI dla tej etykiety
            const barcodeSrc = `<?php echo $barcodeScriptPath; ?>?s=code128&d=${encodeURIComponent(locationId)}&h=60&ts=0&th=15`;

            // --- Definicja HTML i CSS dla Etykiety ---
            const labelContentHTML = `
                <!DOCTYPE html>
                <html lang="pl">
                <head>
                    <meta charset="UTF-8">
                    <title>Etykieta - ${locationId} / ${productId}</title>
                    <style>
                        /* --- Style Etykiety --- */
                        @page {
                            /* === DOSTOSUJ ROZMIAR ETYKIETY === */
                            size: 100mm 70mm; /* Przykładowy rozmiar, np. 10x7 cm */
                            /* ================================= */
                            margin: 4mm; /* Marginesy wewnętrzne strony/drukarki */
                        }
                        body {
                            font-family: Arial, Helvetica, sans-serif;
                            font-size: 10pt;
                            line-height: 1.3;
                            margin: 0;
                            padding: 1mm; /* Wewnętrzny padding etykiety */
                            width: calc(100% - 2mm); /* Szerokość netto = szerokość strony - 2*padding */
                            height: calc(100% - 2mm);/* Wysokość netto */
                           /* border: 1px dashed #999; */ /* Ramka pomocnicza - usuń lub zakomentuj */
                            position: relative;
                            box-sizing: border-box;
                            overflow: hidden;
                            display: flex;
                            flex-direction: column;
                        }
                        .product-name {
                            font-size: 13pt;
                            font-weight: bold;
                            text-align: center;
                            border-bottom: 1px solid #ccc;
                            padding-bottom: 2mm;
                            margin-bottom: 2mm;
                            flex-shrink: 0;
                            /* Obsługa długich nazw */
                            word-break: break-word;
                            overflow-wrap: break-word;
                            max-height: 2.8em; /* Ogranicz do ok. 2 linii */
                            overflow: hidden;
                        }
                        .product-details {
                            font-size: 10pt;
                            margin-bottom: 3mm;
                            flex-shrink: 0;
                            padding-left: 1mm;
                            padding-right: 1mm;
                        }
                        .product-details span {
                            display: block;
                            margin-bottom: 1.5mm;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                         }
                        .product-details strong { margin-left: 5px; font-weight: bold; }
                        .location-info {
                             margin-top: auto; /* Wypchnij na dół */
                             flex-shrink: 0;
                             text-align: center;
                             padding-top: 2mm;
                             border-top: 1px solid #ccc;
                        }
                        .location-barcode img {
                            max-width: 95%;
                            /* === DOSTOSUJ WYSOKOŚĆ KODU === */
                            height: 18mm;
                            /* ============================== */
                            object-fit: contain;
                            display: block;
                            margin: 1mm auto 1mm auto;
                        }
                        .location-id-text {
                             font-size: 14pt;
                             font-weight: bold;
                             text-align: center;
                             letter-spacing: 1px;
                             margin-bottom: 1mm;
                             font-family: 'Courier New', Courier, monospace; /* Czytelniejszy font dla ID */
                         }
                        .print-date {
                            position: absolute;
                            bottom: 0.5mm;
                            left: 0.5mm;
                            font-size: 6pt;
                            color: #555;
                        }
                        @media print {
                            body {
                                border: none; /* Usuń ramkę na wydruku */
                                -webkit-print-color-adjust: exact;
                                print-color-adjust: exact; /* Zachowaj kolory/czernie kodu */
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="product-name">${productName}</div>
                    <div class="product-details">
                        <span>Nr Mag.: <strong>${productId}</strong></span>
                        <span>Nr Kat.: <strong>${productCatalogNr}</strong></span>
                        <?php // Można dodać więcej pól produktu, jeśli są w data-* ?>
                    </div>
                    <div class="location-info">
                        <div class="location-barcode">
                            <img src="${barcodeSrc}" alt="Kod kreskowy lokalizacji ${locationId}">
                        </div>
                        <div class="location-id-text">${locationId}</div>
                    </div>
                    <div class="print-date">Wydruk: ${printDate}</div>
                </body>
                </html>
            `;

            // --- Otwieranie Nowego Okna i Drukowanie Etykiety ---
            const printWindow = window.open('', '_blank', 'width=450,height=350,resizable=yes,scrollbars=yes');
            if (!printWindow) {
                tnApp.showToast('Nie można otworzyć okna drukowania etykiety. Sprawdź blokadę popupów.', 'error');
                return;
            }
            printWindow.document.open();
            printWindow.document.write(labelContentHTML);
            printWindow.document.close();

            // Poczekaj na załadowanie (szczególnie kodu kreskowego) przed drukowaniem
            setTimeout(() => {
                try {
                     printWindow.focus();
                     printWindow.print();
                     // Można spróbować zamknąć okno po drukowaniu, ale może to być blokowane
                     // printWindow.onafterprint = function() { if (!printWindow.closed) { printWindow.close(); } };
                     // setTimeout(() => { if (!printWindow.closed) { printWindow.close(); } }, 7000);
                } catch (e) {
                    console.error("Błąd podczas drukowania etykiety: ", e);
                    tnApp.showToast('Wystąpił błąd podczas drukowania etykiety.', 'error');
                    if (!printWindow.closed) { printWindow.close(); } // Zamknij w razie błędu
                }
            }, 700); // Zwiększone opóźnienie dla pewności załadowania kodu kreskowego
        };
    }

    // --- Delegacja Zdarzeń dla Przycisku Drukowania Etykiety Produktu ---
    warehouseGridContainer?.addEventListener('click', function(event) {
        const printButton = event.target.closest('.tn-action-print-label');
        if (printButton) {
            event.preventDefault(); // Zapobiegaj domyślnej akcji przycisku/linku
            event.stopPropagation(); // Zatrzymaj propagację do rodzica (np. do otwarcia modala przypisania)
            tnApp.printProductLabel(printButton); // Wywołaj funkcję drukowania
        }
    });


    // --- Logika Modala Przypisywania Produktu (Walidacja ilości) ---
    const assignProductSelect = document.getElementById('tn_assign_product_id');
    const assignQuantityInput = document.getElementById('tn_assign_quantity');
    const assignQuantityHelp = document.getElementById('assignQuantityHelp');

    assignProductSelect?.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const maxStock = selectedOption.dataset.stock;
        if (maxStock !== undefined && assignQuantityInput) {
            assignQuantityInput.max = maxStock; // Ustaw maksymalną ilość
            assignQuantityInput.placeholder = `Max: ${maxStock}`;
            if (assignQuantityHelp) {
                 assignQuantityHelp.textContent = `Dostępne w magazynie: ${maxStock} szt.`;
            }
            // Zresetuj walidację ilości
            assignQuantityInput.classList.remove('is-invalid');
            validateAssignQuantity(); // Sprawdź od razu
        } else if (assignQuantityHelp) {
            assignQuantityHelp.textContent = ''; // Wyczyść info, jeśli brak danych
        }
    });

    const validateAssignQuantity = () => {
         if (!assignQuantityInput || !assignProductSelect) return;
         const quantity = parseInt(assignQuantityInput.value);
         const selectedOption = assignProductSelect.options[assignProductSelect.selectedIndex];
         const maxStock = selectedOption ? parseInt(selectedOption.dataset.stock) : null;
         let isValid = true;

         if (isNaN(quantity) || quantity < 1) {
             assignQuantityInput.classList.add('is-invalid');
             assignQuantityInput.nextElementSibling.textContent = 'Ilość musi być liczbą większą od 0.';
             isValid = false;
         } else if (maxStock !== null && quantity > maxStock) {
             assignQuantityInput.classList.add('is-invalid');
              assignQuantityInput.nextElementSibling.textContent = `Ilość nie może przekroczyć stanu magazynowego (${maxStock} szt.).`;
              isValid = false;
         } else {
             assignQuantityInput.classList.remove('is-invalid');
         }
         return isValid;
    };

    assignQuantityInput?.addEventListener('input', validateAssignQuantity);

    // Walidacja formularza przypisania przed wysłaniem (opcjonalnie)
    const assignProductForm = document.getElementById('assignProductForm');
    assignProductForm?.addEventListener('submit', function(event) {
        let isFormValid = true;
        // Walidacja produktu
        if (!assignProductSelect || assignProductSelect.value === "") {
            assignProductSelect.classList.add('is-invalid');
            isFormValid = false;
        } else {
             assignProductSelect.classList.remove('is-invalid');
        }
        // Walidacja ilości
        if (!validateAssignQuantity()) {
             isFormValid = false;
        }

        if (!isFormValid) {
            event.preventDefault(); // Zatrzymaj wysyłanie formularza
            event.stopPropagation();
            tnApp.showToast('Popraw błędy w formularzu przypisania.', 'warning');
        }
        // Jeśli wszystko jest ok, formularz zostanie wysłany
    });

    // Wyczyszczenie walidacji przy zamykaniu modala przypisania
    const assignModalElement = document.getElementById('assignWarehouseModal');
    assignModalElement?.addEventListener('hidden.bs.modal', function () {
        assignProductForm?.reset(); // Resetuj formularz
        assignProductSelect?.classList.remove('is-invalid');
        assignQuantityInput?.classList.remove('is-invalid');
        if (assignQuantityHelp) assignQuantityHelp.textContent = '';
    });


    // --- Logika Modala Generowania Lokalizacji (prosta walidacja zakresów) ---
    const generateLocationsForm = document.getElementById('generateLocationsForm');
    const levelStartInput = document.getElementById('tn_generate_level_start');
    const levelEndInput = document.getElementById('tn_generate_level_end');
    const slotStartInput = document.getElementById('tn_generate_slot_start');
    const slotEndInput = document.getElementById('tn_generate_slot_end');

    const validateRange = (startInput, endInput) => {
        if (!startInput || !endInput) return true; // Nie można walidować
        const start = parseInt(startInput.value);
        const end = parseInt(endInput.value);
        let isValid = true;

        if (isNaN(start) || start < 1) {
             startInput.classList.add('is-invalid');
             isValid = false;
        } else {
             startInput.classList.remove('is-invalid');
        }
         if (isNaN(end) || end < 1) {
             endInput.classList.add('is-invalid');
             isValid = false;
        } else {
             endInput.classList.remove('is-invalid');
        }
        // Sprawdź czy zakres jest poprawny (koniec >= początek)
        if (isValid && end < start) {
            endInput.classList.add('is-invalid');
            // Można dodać komunikat, np. przez stworzenie diva na błędy
            isValid = false;
            tnApp.showToast('Wartość "Do" nie może być mniejsza niż "Od".', 'warning');
        }
         return isValid;
    };

    generateLocationsForm?.addEventListener('submit', function(event) {
         let isFormValid = true;
         const regalSelect = document.getElementById('tn_generate_regal_id');

         if(!regalSelect || regalSelect.value === "") {
            regalSelect.classList.add('is-invalid');
            isFormValid = false;
         } else {
             regalSelect.classList.remove('is-invalid');
         }

         if (!validateRange(levelStartInput, levelEndInput)) isFormValid = false;
         if (!validateRange(slotStartInput, slotEndInput)) isFormValid = false;

         if (!isFormValid) {
             event.preventDefault();
             event.stopPropagation();
             tnApp.showToast('Popraw błędy w formularzu generowania.', 'warning');
         }
    });

     // Wyczyszczenie walidacji przy zamykaniu modala generowania
    const generateModalElement = document.getElementById('generateLocationsModal');
    generateModalElement?.addEventListener('hidden.bs.modal', function () {
        generateLocationsForm?.reset();
        const invalidInputs = generateLocationsForm?.querySelectorAll('.is-invalid');
        invalidInputs?.forEach(input => input.classList.remove('is-invalid'));
    });

});
