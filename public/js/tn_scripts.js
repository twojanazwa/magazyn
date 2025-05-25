var tnApp = tnApp || {};

(function(app) {
    'use strict';

    app.config = {
        defaultWarehouse: 'NIEPRZYPISANY',
        firstEmptyLocation: null,
        debug: false
    };

    const log = (message, ...data) => {
        if (app.config.debug) {
            console.log("TN_LOG:", message, data.length > 0 ? data : '');
        }
    };

    app.init = function() {
        log('Inicjalizacja TN App JS...');
        this.initFlashMessages();
        this.initTooltips();
        this.initThemeSwitcher();
        this.initProductModal();
        this.initOrderModal();
        this.initAssignWarehouseModal();
        this.initImageZoomModal();
        this.initBarcodeZoomModal();
        this.initWarehouseFilters();
        this.initCourierModal();
        this.initRegalModal();
        this.initProductTabs();
        log('Inicjalizacja TN App JS zakończona.');
    };

    app.initFlashMessages = function() {
        const flashContainers = document.querySelectorAll('.tn-flash-container .alert.alert-dismissible');
        if (flashContainers.length > 0) {
            window.setTimeout(() => {
                flashContainers.forEach(alert => {
                    try {
                        const alertInstance = bootstrap.Alert.getOrCreateInstance(alert);
                        if (alertInstance) {
                            alertInstance.close();
                        }
                    } catch (e) {
                        console.warn("Błąd zamykania alertu:", e, alert);
                    }
                });
            }, 7000);
        }
    };

    app.initTooltips = function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        if (tooltipTriggerList.length > 0) {
            tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl, {
                trigger: 'hover'
            }));
        }
    };

    app.initThemeSwitcher = function() {
        const getStoredTheme = () => localStorage.getItem('tn_theme');
        const setStoredTheme = theme => localStorage.setItem('tn_theme', theme);
        const getCurrentHtmlTheme = () => document.documentElement.getAttribute('data-bs-theme') || 'light';
        const getPreferredTheme = () => getStoredTheme() || getCurrentHtmlTheme();

        const updateThemeUI = (themeValue) => {
            const actualTheme = (themeValue === 'auto') ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : themeValue;
            const themeIcon = document.querySelector('.tn-theme-icon');
            const dropdownItemForIcon = document.querySelector(`.dropdown-item[data-bs-theme-value="${themeValue}"]`);
            const itemIconClass = dropdownItemForIcon ? dropdownItemForIcon.querySelector('i:first-child')?.className : (actualTheme === 'dark' ? 'bi-moon-stars-fill' : 'bi-sun-fill');

            if (themeIcon && itemIconClass) {
                const baseClass = itemIconClass.split(' ').find(cls => cls.startsWith('bi-'));
                if (baseClass) themeIcon.className = `bi ${baseClass} fs-5 tn-theme-icon`;
            }

            document.querySelectorAll('.dropdown-item[data-bs-theme-value]').forEach(el => {
                el.classList.remove('active');
                const check = el.querySelector('.bi-check.ms-auto');
                if (check) check.remove();
            });
            const activeBtn = document.querySelector(`.dropdown-item[data-bs-theme-value="${themeValue}"]`);
            if (activeBtn) {
                activeBtn.classList.add('active');
                if (!activeBtn.querySelector('.bi-check.ms-auto')) {
                    const check = document.createElement('i');
                    check.className = 'bi bi-check ms-auto';
                    activeBtn.appendChild(check);
                }
            }
        };

        const setTheme = themeValue => {
            let themeToSet = themeValue;
            if (themeValue === 'auto') {
                themeToSet = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-bs-theme', themeToSet);
            updateThemeUI(themeValue);
        };

        const initialTheme = getPreferredTheme();
        setTheme(initialTheme);
        document.querySelectorAll('[data-bs-theme-value]').forEach(toggle => {
            toggle.addEventListener('click', () => {
                const val = toggle.getAttribute('data-bs-theme-value');
                setStoredTheme(val);
                setTheme(val);
            });
        });
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (getStoredTheme() === 'auto') setTheme('auto');
        });
    };

    app.productModal = null;
    app.initProductModal = function() {
        const element = document.getElementById('productModal');
        if (!element) return;
        this.productModal = bootstrap.Modal.getOrCreateInstance(element);
        const form = document.getElementById('productForm');
        const productIdInput = document.getElementById('productId');
        const locationSelect = document.getElementById('productLocationSelect');
        const locationHelp = document.getElementById('productLocationHelp');
        const imagePreview = document.getElementById('imagePreview');
        const imageFile = document.getElementById('productImageFile');

        element.addEventListener('show.bs.modal', (event) => {
            const isEditMode = productIdInput && productIdInput.value !== '';
            if (locationSelect) {
                locationSelect.disabled = isEditMode;
                if (isEditMode) {
                    locationSelect.value = "";
                    if (locationHelp) locationHelp.innerHTML = 'Lokalizację zmień w <a href="' + (window.tnGenerujUrl ? tnGenerujUrl('warehouse_view') : '/magazyn') + '">Widoku Magazynu</a>.';
                } else {
                    locationSelect.value = app.config.firstEmptyLocation || "";
                    if (locationHelp) locationHelp.innerHTML = 'Opcjonalnie przypisz od razu do miejsca.';
                }
            }
            if (!isEditMode && imagePreview) {
                imagePreview.style.display = 'none';
                imagePreview.src = '#';
                if (imageFile) imageFile.value = '';
            }
            if (form) form.classList.remove('was-validated');
        });

        if (imageFile && imagePreview) {
            imageFile.addEventListener('change', evt => {
                const [file] = imageFile.files;
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    imagePreview.style.display = 'none';
                    imagePreview.src = '#';
                }
            });
        }
    };

    app.populateEditForm = function(product) {
        const modalElement = document.getElementById('productModal');
        if (!modalElement || !product || typeof product !== 'object') return;
        const form = document.getElementById('productForm');
        if (!form) return;

        document.getElementById('productModalLabel').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edytuj Produkt';
        form.reset();

        try {
            form.querySelector('#productId').value = product.id || '';
            form.querySelector('#productName').value = product.name || '';
            form.querySelector('#productProducent').value = product.producent || '';
            form.querySelector('#productCatalogNr').value = product.tn_numer_katalogowy || '';
            form.querySelector('#productCategorySelect').value = product.category || '';
            form.querySelector('#productDesc').value = product.desc || '';
            form.querySelector('#productSpec').value = product.spec || '';
            form.querySelector('#productParams').value = product.params || '';
            form.querySelector('#productVehicle').value = product.vehicle || '';
            form.querySelector('#productPrice').value = product.price || 0;
            form.querySelector('#productShipping').value = product.shipping || 0;
            form.querySelector('#productStock').value = product.stock || 0;
            form.querySelector('#productUnit').value = product.tn_jednostka_miary || '';
            form.querySelector('#originalWarehouseValue').value = product.warehouse || '';

            const preview = form.querySelector('#imagePreview');
            const imgPath = window.tnPobierzSciezkeObrazka ? tnPobierzSciezkeObrazka(product.image || null) : (product.image ? '/TNuploads/' + product.image : null);
            if (preview && imgPath && !imgPath.includes('data:image/svg+xml')) {
                preview.src = imgPath;
                preview.style.display = 'block';
            } else if (preview) {
                preview.style.display = 'none';
                preview.src = '#';
            }

            const locationSelect = form.querySelector('#productLocationSelect');
            const locationHelp = form.querySelector('#productLocationHelp');
            if (locationSelect) {
                locationSelect.value = "";
                locationSelect.disabled = true;
                if (locationHelp) locationHelp.innerHTML = 'Lokalizację zmień w <a href="' + (window.tnGenerujUrl ? tnGenerujUrl('warehouse_view') : '/magazyn') + '">Widoku Magazynu</a>.';
            }

        } catch (e) {
            console.error("Błąd wypełniania formularza produktu:", e);
        }

        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        modal.show();
    };

    app.openAddModal = function() {
        const modalElement = document.getElementById('productModal');
        if (!modalElement) return;
        const form = document.getElementById('productForm');
        if (!form) return;

        document.getElementById('productModalLabel').innerHTML = '<i class="bi bi-plus-circle me-2"></i> Dodaj Nowy Produkt';
        form.reset();
        form.querySelector('#productId').value = '';
        form.querySelector('#originalWarehouseValue').value = '';

        const locationSelect = form.querySelector('#productLocationSelect');
        const locationHelp = form.querySelector('#productLocationHelp');
        if (locationSelect) {
            locationSelect.disabled = false;
            locationSelect.value = app.config.firstEmptyLocation || "";
        }
        if (locationHelp) locationHelp.innerHTML = 'Opcjonalnie przypisz od razu do miejsca.';

        const preview = form.querySelector('#imagePreview');
        if (preview) {
            preview.style.display = 'none';
            preview.src = '#';
        }

        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        modal.show();
    };

    app.orderModal = null;
    app.initOrderModal = function() {
        const element = document.getElementById('orderModal');
        if (!element) return;
        this.orderModal = bootstrap.Modal.getOrCreateInstance(element);
        const productSelect = element.querySelector('#order_product_id');
        const quantityInput = element.querySelector('#order_quantity');
        const quantityWarning = element.querySelector('#quantity_warning');

        const checkStock = () => {
            if (!productSelect || !quantityInput || !quantityWarning) {
                if (quantityWarning) quantityWarning.style.display = 'none';
                return;
            }
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            if (!selectedOption || !selectedOption.dataset.stock || selectedOption.value === "") {
                quantityWarning.style.display = 'none';
                return;
            }
            const stock = parseInt(selectedOption.dataset.stock, 10);
            const quantity = parseInt(quantityInput.value, 10);
            quantityWarning.style.display = (!isNaN(stock) && !isNaN(quantity) && quantity > stock) ? 'block' : 'none';
        };

        if (productSelect) productSelect.addEventListener('change', checkStock);
        if (quantityInput) quantityInput.addEventListener('input', checkStock);
        element.addEventListener('show.bs.modal', () => {
            setTimeout(checkStock, 100);
        });
    };


    app.setupOrderModal = function(order = null) {
        const modalElement = document.getElementById('orderModal');
        if (!modalElement) return;
        const form = document.getElementById('orderForm');
        if (!form) return;
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const modalLabel = document.getElementById('orderModalLabel');
        form.reset();
        const warning = form.querySelector('#quantity_warning');
        if (warning) warning.style.display = 'none';
        form.classList.remove('was-validated');

        if (order && typeof order === 'object') {
            modalLabel.innerText = 'Edytuj zamówienie ID: ' + (order.id || '?');
            try {
                form.querySelector('#edit_order_id').value = order.id || '';
                form.querySelector('#order_product_id').value = order.product_id || '';
                form.querySelector('#order_quantity').value = order.quantity || 1;
                form.querySelector('#order_buyer_name').value = order.buyer_name || '';
                form.querySelector('#order_buyer_daneWysylki').value = order.buyer_daneWysylki || '';
                form.querySelector('#order_status').value = order.status || 'Nowe';
                form.querySelector('#order_payment_status').value = order.tn_status_platnosci || '';
                form.querySelector('#order_courier_id').value = order.courier_id || '';
                form.querySelector('#order_tracking_number').value = order.tracking_number || '';
            } catch (e) {
                console.error("Błąd wypełniania modala zamówienia:", e);
            }
        } else {
            modalLabel.innerText = 'Dodaj nowe zamówienie';
            form.querySelector('#edit_order_id').value = '';
            form.querySelector('#order_status').value = 'Nowe';
            form.querySelector('#order_payment_status').value = '';
            form.querySelector('#order_courier_id').value = '';
            form.querySelector('#order_tracking_number').value = '';
        }

        modal.show();
    };

    app.tnOtworzModalZamowienia = function(productId) {
        const modalElement = document.getElementById('orderModal');
        const productSelect = document.getElementById('order_product_id');
        if (!modalElement || !productSelect) {
            return;
        }
        this.setupOrderModal(null);
        const option = productSelect.querySelector('option[value="' + productId + '"]');
        if (productId && option && !option.disabled) {
            productSelect.value = productId;
            const quantityInput = document.getElementById('order_quantity');
            if (quantityInput) quantityInput.value = 1;
        } else if (productId) {
             if(typeof tnApp.showFlashMessage === 'function') tnApp.showFlashMessage('Wybrany produkt jest niedostępny.', 'warning');
             else alert('Wybrany produkt jest niedostępny.');
        }
    };


    app.assignWarehouseModal = null;
    app.initAssignWarehouseModal = function() {
        const element = document.getElementById('assignWarehouseModal');
        if (!element) return;
        this.assignWarehouseModal = bootstrap.Modal.getOrCreateInstance(element);
        const productSelect = element.querySelector('#modal_product_id');
        const quantityInput = element.querySelector('#modal_quantity');
        const quantityWarning = element.querySelector('#assign_quantity_warning');

        const checkAssignStock = () => {
            if (!productSelect || !quantityInput || !quantityWarning) return;
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            if (!selectedOption || !selectedOption.dataset.stock || selectedOption.value === "") {
                quantityWarning.style.display = 'none';
                return;
            }
            const stock = parseInt(selectedOption.dataset.stock, 10);
            const quantity = parseInt(quantityInput.value, 10);
            quantityWarning.style.display = (!isNaN(stock) && !isNaN(quantity) && quantity > stock) ? 'block' : 'none';
        };

        if (productSelect) productSelect.addEventListener('change', checkAssignStock);
        if (quantityInput) quantityInput.addEventListener('input', checkAssignStock);
        element.addEventListener('show.bs.modal', () => {
            setTimeout(checkAssignStock, 100);
        });


        document.querySelectorAll('.tn-location-slot[data-bs-toggle="modal"][data-bs-target="#assignWarehouseModal"]').forEach(slot => {
            slot.addEventListener('click', (event) => {
                const locationId = event.currentTarget.dataset.locationId;
                this.setupAssignModal(locationId);
            });
        });
    };

    app.setupAssignModal = function(locationId) {
        const modalElement = document.getElementById('assignWarehouseModal');
        if (!modalElement || !locationId) return;
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const form = document.getElementById('assignWarehouseForm');
        const locationIdInput = document.getElementById('modal_location_id');
        const locationDisplay = document.getElementById('modal_location_display_id');
        const productSelect = document.getElementById('modal_product_id');
        const quantityInput = document.getElementById('modal_quantity');
        const warning = document.getElementById('assign_quantity_warning');

        if (form) form.reset();
        if (warning) warning.style.display = 'none';

        if (locationIdInput && locationDisplay) {
            locationIdInput.value = locationId;
            locationDisplay.textContent = locationId;
        }
        if (productSelect) productSelect.value = '';
        if (quantityInput) quantityInput.value = 1;

        modal.show();
    };


    app.initWarehouseFilters = function() {
        const filterStatusSelect = document.getElementById('tn_filter_status');
        const filterTextInput = document.getElementById('tn_filter_text');
        if (filterStatusSelect || filterTextInput) {
            if (filterStatusSelect) {
                filterStatusSelect.addEventListener('change', () => this.filterWarehouseView());
            }
            if (filterTextInput) {
                filterTextInput.addEventListener('input', () => this.filterWarehouseView());
                filterTextInput.addEventListener('search', () => {
                    if (filterTextInput.value === '') this.filterWarehouseView();
                });
            }
        }
    };

    app.filterWarehouseView = function() {
        const filterStatusSelect = document.getElementById('tn_filter_status');
        const filterTextInput = document.getElementById('tn_filter_text');
        const locationSlots = document.querySelectorAll('.tn-location-slot');
        const regalCards = document.querySelectorAll('.tn-regal-card');
        if (locationSlots.length === 0) return;

        const selectedStatus = filterStatusSelect ? filterStatusSelect.value : 'all';
        const searchText = filterTextInput ? filterTextInput.value.toLowerCase().trim() : '';
        let visibleSlotsInRegal = {};

        locationSlots.forEach(slot => {
            const slotStatus = slot.dataset.status || '';
            const slotFilterText = slot.dataset.filterText || '';
            const slotRegalId = slot.closest('.tn-regal-card')?.dataset.regalId || '';
            const statusMatch = (selectedStatus === 'all' || slotStatus === selectedStatus || (selectedStatus === 'error' && slotStatus === 'error'));
            const textMatch = (searchText === '' || (slotFilterText && slotFilterText.includes(searchText)));
            let showSlot = statusMatch && textMatch;

            if (slotStatus === 'error' && selectedStatus !== 'all' && selectedStatus !== 'error') {
                showSlot = false;
            }

            slot.classList.toggle('tn-hidden', !showSlot);
            if (showSlot && slotRegalId) {
                visibleSlotsInRegal[slotRegalId] = (visibleSlotsInRegal[slotRegalId] || 0) + 1;
            }
        });

        regalCards.forEach(card => {
            const regalId = card.dataset.regalId;
            const hasVisibleSlots = regalId && visibleSlotsInRegal.hasOwnProperty(regalId) && visibleSlotsInRegal[regalId] > 0;
            const isSpecialCard = regalId === 'BEZ_REGALU';
            let showCard = hasVisibleSlots;

            if (isSpecialCard && !hasVisibleSlots && (selectedStatus === 'error' || selectedStatus === 'all') && searchText === '') {
                 if (card.querySelectorAll('.tn-location-slot').length > 0) showCard = true;
            }
             if (isSpecialCard && selectedStatus !== 'all' && selectedStatus !== 'error') {
                 showCard = false;
             }

            card.classList.toggle('tn-hidden', !showCard);
        });
    };


    app.imageZoomModal = null;
    app.initImageZoomModal = function() {
        const element = document.getElementById('tnImageZoomModal');
        if (!element) return;
        this.imageZoomModal = bootstrap.Modal.getOrCreateInstance(element);
        const zoomedImage = document.getElementById('tnZoomedImage');
        const modalTitle = document.getElementById('tnImageZoomModalLabel');

        if (this.imageZoomModal && zoomedImage && modalTitle) {
            element.addEventListener('show.bs.modal', (event) => {
                const triggerElement = event.relatedTarget;
                const imageSource = triggerElement ? (triggerElement.getAttribute('data-image-src') || triggerElement.src) : null;
                const imageTitle = triggerElement ? (triggerElement.getAttribute('data-image-title') || triggerElement.alt || 'Podgląd Zdjęcia') : 'Podgląd Zdjęcia';
                zoomedImage.src = imageSource || '';
                modalTitle.textContent = imageTitle;
            });
            element.addEventListener('hidden.bs.modal', () => {
                zoomedImage.src = '';
                modalTitle.textContent = 'Podgląd Zdjęcia';
            });
        }
    };

    app.showImageModal = function(imageUrl, imageTitle = 'Podgląd Zdjęcia') {
        const modalElement = document.getElementById('tnImageZoomModal');
        if (!modalElement) return;
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const zoomedImage = document.getElementById('tnZoomedImage');
        const modalTitle = document.getElementById('tnImageZoomModalLabel');
        if (zoomedImage && modalTitle && imageUrl) {
            zoomedImage.src = imageUrl;
            modalTitle.textContent = imageTitle;
            modal.show();
        }
    };

    app.barcodeZoomModal = null;
    app.initBarcodeZoomModal = function() {
        const element = document.getElementById('tnBarcodeZoomModal');
        if (!element) return;
        this.barcodeZoomModal = bootstrap.Modal.getOrCreateInstance(element);
        const modalBarcodeImage = element.querySelector('#barcode-modal-img');
        const modalTitle = element.querySelector('.modal-title');

        if (this.barcodeZoomModal && modalBarcodeImage && modalTitle) {
            element.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const barcodeValue = button.getAttribute('data-barcode-value');

                if (modalBarcodeImage && barcodeValue) {
                    const barcode_modal_url = `/kod_kreskowy.php?f=svg&s=ean-128&height=200&width=3&d=${encodeURIComponent(barcodeValue)}`;
                    modalBarcodeImage.src = barcode_modal_url;
                    modalBarcodeImage.alt = `Kod Kreskowy: ${barcodeValue}`;
                }
                if (modalTitle && barcodeValue) {
                    modalTitle.textContent = `Kod Kreskowy: ${barcodeValue}`;
                }
            });
            element.addEventListener('hidden.bs.modal', function() {
                modalBarcodeImage.src = '';
                modalBarcodeImage.alt = '';
                modalTitle.textContent = 'Kod Kreskowy';
            });
        }
    };


    app.courierModal = null;
    app.initCourierModal = function() {
        const element = document.getElementById('courierModal');
        if (!element) return;
        this.courierModal = bootstrap.Modal.getOrCreateInstance(element);
    };

    app.openCourierModal = function(courier = null) {
        const modalElement = document.getElementById('courierModal');
        if (!modalElement) {
            console.error("Nie znaleziono #courierModal");
            return;
        }
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const form = document.getElementById('courierForm');
        const modalLabel = document.getElementById('courierModalLabel');
        const idInput = document.getElementById('courierId');
        const nameInput = document.getElementById('courierName');
        const prefixInput = document.getElementById('courierTrackingPattern');
        const notesInput = document.getElementById('courierNotes');
        const activeInput = document.getElementById('courierIsActive');

        if (!form || !modalLabel || !idInput || !nameInput || !prefixInput || !notesInput || !activeInput) {
            console.error("Brak elementów formularza w modalu kuriera.");
            return;
        }

        form.classList.remove('was-validated');
        if (courier && typeof courier === 'object') {
            modalLabel.innerHTML = '<i class="bi bi-pencil-fill me-2"></i> Edytuj Kuriera';
            idInput.value = courier.id || '';
            nameInput.value = courier.name || '';
            prefixInput.value = courier.tracking_url_prefix || '';
            notesInput.value = courier.notes || '';
            activeInput.checked = courier.is_active === true || courier.is_active === '1' || courier.is_active === 1;
        } else {
            modalLabel.innerHTML = '<i class="bi bi-plus-circle me-2"></i> Dodaj Nowego Kuriera';
            form.reset();
            idInput.value = '';
            activeInput.checked = true;
        }
        if (modal) modal.show();
        else console.error("Nie można uzyskać instancji modala kuriera.");
    };


    app.regalModal = null;
    app.initRegalModal = function() {
        const element = document.getElementById('regalModal');
        if (!element) return;
        this.regalModal = bootstrap.Modal.getOrCreateInstance(element);
    };

    app.openRegalModal = function(regalData = null) {
        const modalElement = document.getElementById('regalModal');
        if (!modalElement) return;
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const form = document.getElementById('regalForm');
        const modalLabel = document.getElementById('regalModalLabel');
        const idInput = document.getElementById('regalIdInput');
        const descInput = document.getElementById('regalDescInput');
        const originalIdInput = document.getElementById('originalRegalId');
        const idHelp = document.getElementById('regalIdHelp');


        if (!form || !modalLabel || !idInput || !descInput || !originalIdInput || !idHelp) {
            console.error("Brak elementów formularza modala regału.");
            return;
        }
        form.classList.remove('was-validated');

        if (regalData && typeof regalData === 'object') {
            modalLabel.innerHTML = '<i class="bi bi-pencil-fill me-2"></i> Edytuj Regał';
            idInput.value = regalData.tn_id_regalu || '';
            idInput.readOnly = true;
            idInput.classList.add('form-control-plaintext');
            idInput.classList.remove('form-control');
            idHelp.textContent = 'ID regału nie można zmienić.';
            descInput.value = regalData.tn_opis_regalu || '';
            originalIdInput.value = regalData.tn_id_regalu || '';
        } else {
            modalLabel.innerHTML = '<i class="bi bi-plus-circle me-2"></i> Dodaj Regał';
            form.reset();
            idInput.value = '';
            idInput.readOnly = false;
            idInput.classList.remove('form-control-plaintext');
            idInput.classList.add('form-control');
            idHelp.textContent = 'Unikalny identyfikator (np. R01).';
            originalIdInput.value = '';
        }
        modal.show();
    };

    app.initProductTabs = function() {
        const productTabElement = document.getElementById('tnProductTab');
        if (productTabElement) {
            const firstActiveTab = productTabElement.querySelector('.nav-link.active');
            if (!firstActiveTab) {
                const firstAvailableTab = productTabElement.querySelector(".nav-link");
                if (firstAvailableTab) {
                    const tab = new bootstrap.Tab(firstAvailableTab);
                    tab.show();
                }
            }
        }
    };


    app.generujUrl = function(pageId, params = {}) {
        const base = document.body.getAttribute('data-base-url') || '';
        const pageMap = {
            'dashboard': '/',
            'products': '/produkty',
            'warehouse_view': '/magazyn',
            'orders': '/zamowienia',
            'settings': '/ustawienia',
            'login_page': '/logowanie'
        };
        let path = pageMap[pageId] || '/' + pageId;
        let queryString = Object.keys(params).length > 0 ? '?' + new URLSearchParams(params).toString() : '';
        let url = base + (path === '/' ? (queryString ? '/index.php' + queryString : '/') : path + queryString);
        return url.replace(/(?<!:)\/\/+/g, '/');
    };

    window.tnPotwierdzUsuniecie = function(wiadomosc = 'Czy na pewno chcesz to zrobić?') {
        return confirm(wiadomosc);
    };

    window.changeMainImage = function(thumbnailElement, newImageSrc, newAltText) {
        const mainImage = document.getElementById('tnMainProductImage');
        const modalLink = mainImage ? mainImage.closest('a[data-bs-toggle="modal"][data-bs-target="#tnImageZoomModal"]') : null;
        const currentActiveThumb = document.querySelector('.tn-gallery-thumbnail.active');

        if (mainImage && modalLink) {
            mainImage.src = newImageSrc;
            mainImage.alt = newAltText;

            modalLink.setAttribute('data-image-src', newImageSrc);
            modalLink.setAttribute('data-image-title', newAltText);

            if (currentActiveThumb) {
                currentActiveThumb.classList.remove('active');
            }
            if (thumbnailElement) {
                thumbnailElement.classList.add('active');
            }
        }
    };

})(tnApp);


document.addEventListener('DOMContentLoaded', function() {
    if (typeof tnApp !== 'undefined' && typeof tnApp.init === 'function') {
        tnApp.init();
        if (typeof tnApp.filterWarehouseView === 'function' && document.getElementById('tn_filter_status')) {
             tnApp.filterWarehouseView();
         }
    } else {
        console.error("Nie można zainicjalizować aplikacji TN App JS!");
    }
});

document.addEventListener('DOMContentLoaded', function () {

    // --- Globalny obiekt aplikacji (jeśli nie istnieje) ---
    if (typeof window.tnApp === 'undefined') window.tnApp = {};

     // --- Funkcja do kopiowania (jeśli nie istnieje) ---
     if (typeof window.tnApp.copyToClipboard === 'undefined') {
         window.tnApp.copyToClipboard = (text) => {
             navigator.clipboard.writeText(text).then(() => {
                 tnApp.showToast(`Skopiowano: ${text}`);
             }).catch(err => {
                 console.error('Błąd kopiowania do schowka: ', err);
                 tnApp.showToast('Błąd kopiowania!', 'error');
             });
         };
     }

    // --- Funkcja do pokazywania Toastów (jeśli nie istnieje) ---
    if (typeof window.tnApp.showToast === 'undefined') {
        window.tnApp.showToast = (message, type = 'info') => {
            const toastContainer = document.getElementById('toastContainer') || document.body; // Użyj body jako fallback
            const toastId = 'toast-' + Date.now();
            const bgClass = type === 'success' ? 'bg-success' : (type === 'error' ? 'bg-danger' : (type === 'warning' ? 'bg-warning text-dark' : 'bg-primary'));
            const icon = type === 'success' ? '<i class="bi bi-check-circle-fill me-2"></i>' : (type === 'error' ? '<i class="bi bi-exclamation-triangle-fill me-2"></i>' : (type === 'warning' ? '<i class="bi bi-exclamation-triangle-fill me-2"></i>' : '<i class="bi bi-info-circle-fill me-2"></i>'));

            const toastHtml = `
                <div class="toast align-items-center text-white ${bgClass} border-0 fade" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true" style="position: fixed; top: 1rem; right: 1rem; z-index: 1100;">
                  <div class="d-flex">
                    <div class="toast-body">
                      ${icon} ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                  </div>
                </div>`;
             // Wstrzyknij toast do kontenera lub body
             document.body.insertAdjacentHTML('beforeend', toastHtml); // Bezpieczniej do body
             const toastElement = document.getElementById(toastId);
             const toast = new bootstrap.Toast(toastElement, { delay: 3500, autohide: true });
             toast.show();
             toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
        };
    }

    // --- Inicjalizacja komponentów Bootstrap ---
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('#tnMainContent [data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl, { trigger: 'hover' }); });

    const popoverTriggerList = [].slice.call(document.querySelectorAll('#tnMainContent [data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        try {
            const popoverContentData = JSON.parse(popoverTriggerEl.getAttribute('data-popover-content'));
            return new bootstrap.Popover(popoverTriggerEl, {
                html: true, trigger: 'click', placement: 'auto',
                title: popoverContentData.title, content: popoverContentData.content,
                customClass: 'tn-warehouse-popover', sanitize: false
            });
        } catch (e) {
            console.error("Błąd parsowania danych popover:", e, popoverTriggerEl.getAttribute('data-popover-content'));
            return null; // Zwróć null, aby uniknąć błędu w .map
        }
    });

    // Zamykanie aktywnych popoverów
     document.addEventListener('click', function (event) {
        let clickedOnPopoverTrigger = false;
        popoverTriggerList.forEach(trigger => {
            if (trigger && (trigger === event.target || trigger.contains(event.target))) {
                clickedOnPopoverTrigger = true;
            }
        });

        // Zamknij inne popovery tylko jeśli kliknięto na nowy trigger
        if (clickedOnPopoverTrigger) {
             popoverTriggerList.forEach(trigger => {
                 if (trigger && trigger !== event.target && !trigger.contains(event.target)) {
                     const popoverInstance = bootstrap.Popover.getInstance(trigger);
                     if (popoverInstance) {
                         popoverInstance.hide();
                     }
                 }
             });
        } else {
            // Zamknij wszystkie, jeśli kliknięto poza jakimkolwiek triggerem i popoverem
             let clickedInsidePopover = event.target.closest('.popover.show');
             if (!clickedInsidePopover) {
                 popoverTriggerList.forEach(trigger => {
                     if(trigger){
                         const popoverInstance = bootstrap.Popover.getInstance(trigger);
                         if (popoverInstance) {
                             popoverInstance.hide();
                         }
                     }
                 });
             }
        }
    }, true);


    // --- Logika dla Modala Przypisywania (bez zmian) ---
    const assignModal = document.getElementById('assignWarehouseModal');
    if (assignModal) {
        assignModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const locationId = button?.getAttribute('data-location-id'); // Bezpieczny dostęp
            const modalLocationIdInput = assignModal.querySelector('#tn_assign_location_id');
            if (modalLocationIdInput) { modalLocationIdInput.value = locationId || ''; }
            const modalTitle = assignModal.querySelector('.modal-title');
            if (modalTitle) { modalTitle.textContent = locationId ? 'Przypisz produkt do miejsca: ' + locationId : 'Przypisz produkt'; }
        });
    } else { console.warn('#assignWarehouseModal nie znaleziono.'); }

    // --- Logika Filtrowania Widoku Magazynu (bez zmian) ---
    const filterRegalSelect = document.getElementById('tn_filter_regal');
    const filterStatusSelect = document.getElementById('tn_filter_status');
    const filterTextInput = document.getElementById('tn_filter_text');
    const clearFiltersBtn = document.getElementById('tn_clear_filters_btn');
    const warehouseGridContainer = document.getElementById('warehouseGridContainer'); // Kontener siatki
    const noResultsMessage = document.getElementById('noFilterResultsMessage');

    const filterWarehouseView = () => {
        if (!warehouseGridContainer) return; // Sprawdź czy kontener istnieje
        const allSlots = warehouseGridContainer.querySelectorAll('.tn-location-slot');
        const allRegalCards = warehouseGridContainer.querySelectorAll('.tn-regal-card');

        const selectedRegal = filterRegalSelect.value;
        const selectedStatus = filterStatusSelect.value;
        const filterText = filterTextInput.value.toLowerCase().trim();
        let visibleSlotsCount = 0;
        const highlightOnly = true; // Nadal używamy przyciemniania

        allSlots.forEach(slot => {
            const slotRegalId = slot.getAttribute('data-regal-id');
            const slotStatus = slot.getAttribute('data-status');
            const slotTextData = slot.getAttribute('data-filter-text')?.toLowerCase() || ''; // Bezpieczny dostęp

            const regalMatch = selectedRegal === 'all' || slotRegalId === selectedRegal;
            const statusMatch = selectedStatus === 'all' || slotStatus === selectedStatus;
            const textMatch = filterText === '' || slotTextData.includes(filterText);

            if (regalMatch && statusMatch && textMatch) {
                 slot.style.opacity = '1'; slot.style.pointerEvents = 'auto';
                 visibleSlotsCount++;
            } else {
                 slot.style.opacity = '0.3'; slot.style.pointerEvents = 'none';
            }
        });

        allRegalCards.forEach(card => {
             const cardRegalId = card.getAttribute('data-regal-id');
             const regalFilterMatch = selectedRegal === 'all' || cardRegalId === selectedRegal;
             const slotsInCard = card.querySelectorAll('.tn-location-slot');
             let hasVisibleSlotsBasedOnStatusAndText = false;
             slotsInCard.forEach(slot => {
                 const slotStatus = slot.getAttribute('data-status');
                 const slotTextData = slot.getAttribute('data-filter-text')?.toLowerCase() || '';
                 const statusMatch = selectedStatus === 'all' || slotStatus === selectedStatus;
                 const textMatch = filterText === '' || slotTextData.includes(filterText);
                 if (statusMatch && textMatch) { hasVisibleSlotsBasedOnStatusAndText = true; }
             });

             if (regalFilterMatch && hasVisibleSlotsBasedOnStatusAndText) {
                  card.style.opacity = '1'; card.style.display = ''; // Pokaż
             } else {
                  card.style.opacity = '0.3';
                  // Ukryjemy całkowicie jeśli filtr regału jest aktywny i nie pasuje
                  if (selectedRegal !== 'all' && cardRegalId !== selectedRegal) {
                       card.style.display = 'none';
                  } else {
                       card.style.display = ''; // Pokaż (ale będzie przyciemniony)
                  }
             }
        });

        noResultsMessage.classList.toggle('d-none', visibleSlotsCount > 0);
    };

    // Nasłuchiwanie na zmiany filtrów
    filterRegalSelect?.addEventListener('change', filterWarehouseView);
    filterStatusSelect?.addEventListener('change', filterWarehouseView);
    filterTextInput?.addEventListener('input', filterWarehouseView);
    clearFiltersBtn?.addEventListener('click', () => {
        if(filterRegalSelect) filterRegalSelect.value = 'all';
        if(filterStatusSelect) filterStatusSelect.value = 'all';
        if(filterTextInput) filterTextInput.value = '';
        filterWarehouseView();
    });

    // Inicjalne zastosowanie filtrów
    filterWarehouseView();

    // --- Kontrola Gęstości Widoku (Nowe) ---
    const densityButtons = document.querySelectorAll('input[name="density"]');
    const savedDensity = localStorage.getItem('warehouseDensity') || 'normal';

    const setDensity = (densityValue) => {
        warehouseGridContainer.classList.remove('warehouse-density-compact', 'warehouse-density-normal', 'warehouse-density-large');
        warehouseGridContainer.classList.add(`warehouse-density-${densityValue}`);
        localStorage.setItem('warehouseDensity', densityValue);
        // Zaktualizuj stan przycisków radio
        document.getElementById(`density${densityValue.charAt(0).toUpperCase() + densityValue.slice(1)}`)?.setAttribute('checked', true);
         // Zaktualizuj aktywne labele
         document.querySelectorAll('label[for^="density"]').forEach(label => label.classList.remove('active'));
         document.querySelector(`label[for="density${densityValue.charAt(0).toUpperCase() + densityValue.slice(1)}"]`)?.classList.add('active');
         // Popraw tooltipy po zmianie labelki (opcjonalne)
         tooltipTriggerList.forEach(el => bootstrap.Tooltip.getInstance(el)?.hide());
    };

    densityButtons.forEach(button => {
        if (button.value === savedDensity) {
             button.checked = true;
             // Ustaw klasę active na odpowiednim labelu
            document.querySelector(`label[for="${button.id}"]`)?.classList.add('active');
        } else {
             document.querySelector(`label[for="${button.id}"]`)?.classList.remove('active');
        }
        button.addEventListener('change', (event) => {
            setDensity(event.target.value);
        });
    });
    // Zastosuj zapisaną gęstość przy ładowaniu strony
    setDensity(savedDensity);


    // --- Podświetlanie Powiązanych Produktów (Nowe) ---
    warehouseGridContainer.addEventListener('mouseover', (event) => {
        const targetSlot = event.target.closest('.tn-location-slot.status-occupied');
        if (!targetSlot || targetSlot.style.opacity === '0.3') return; // Ignoruj przyciemnione lub nie-sloty

        const productId = targetSlot.getAttribute('data-product-id');
        if (!productId) return; // Brak produktu

        const relatedSlots = warehouseGridContainer.querySelectorAll(`.tn-location-slot.status-occupied[data-product-id="${productId}"]`);
        relatedSlots.forEach(slot => {
            if (slot !== targetSlot && slot.style.opacity !== '0.3') { // Nie podświetlaj samego siebie i przyciemnionych
                slot.classList.add('tn-slot-related-highlight');
            }
        });
    });

    warehouseGridContainer.addEventListener('mouseout', (event) => {
         const targetSlot = event.target.closest('.tn-location-slot.status-occupied');
         // Sprawdź czy opuszczamy slot (lub jego element potomny)
        if (targetSlot) {
            const productId = targetSlot.getAttribute('data-product-id');
            if (!productId) return;

             // Sprawdź, czy kursor nie przeniósł się na inny powiązany slot
            const relatedTargetSlot = event.relatedTarget?.closest('.tn-location-slot');
            const relatedTargetProductId = relatedTargetSlot?.getAttribute('data-product-id');

             if (relatedTargetProductId !== productId) {
                  // Usuń podświetlenie tylko jeśli nie przechodzimy na inny powiązany slot
                 const highlightedSlots = warehouseGridContainer.querySelectorAll('.tn-slot-related-highlight[data-product-id="' + productId + '"]');
                 highlightedSlots.forEach(slot => slot.classList.remove('tn-slot-related-highlight'));
             }
        } else {
             // Jeśli opuściliśmy cały kontener lub nie-slot, usuń wszystkie podświetlenia
             const allHighlighted = warehouseGridContainer.querySelectorAll('.tn-slot-related-highlight');
             allHighlighted.forEach(slot => slot.classList.remove('tn-slot-related-highlight'));
        }

    });

     // --- Modal dla Powiększonego Kodu Kreskowego (Nowe) ---
    const barcodeModal = document.getElementById('barcodeModal');
    const barcodeModalImage = document.getElementById('barcodeModalImage');
    const barcodeModalLocationId = document.getElementById('barcodeModalLocationId');

    if (barcodeModal && barcodeModalImage && barcodeModalLocationId) {
        // Użyj delegacji zdarzeń na kontenerze siatki
        warehouseGridContainer.addEventListener('click', function(event) {
            const barcodeTrigger = event.target.closest('.tn-slot-barcode');
            if (barcodeTrigger) {
                 event.stopPropagation(); // Zatrzymaj propagację, aby nie aktywować modala przypisania
                 const locationId = barcodeTrigger.getAttribute('data-location-id');
                 const barcodeSrc = barcodeTrigger.getAttribute('data-barcode-src');

                 if (locationId && barcodeSrc) {
                    barcodeModalLocationId.textContent = locationId;
                    barcodeModalImage.src = barcodeSrc;
                    barcodeModalImage.alt = `Powiększony kod kreskowy dla ${locationId}`;
                     // Instancja modala jest tworzona automatycznie przez atrybuty data-bs-*
                     // ale możemy ją pobrać, jeśli potrzebujemy wywołać metody
                     // const modalInstance = bootstrap.Modal.getInstance(barcodeModal) || new bootstrap.Modal(barcodeModal);
                     // modalInstance.show(); // Niepotrzebne jeśli używamy data-bs-toggle
                 }
            }
        });
    } else {
        console.warn('Elementy modala kodu kreskowego nie zostały znalezione.');
    }

    // --- Funkcja drukowania dla Modala Kodu Kreskowego ---
    if (typeof window.tnApp.printBarcodeModal === 'undefined') {
        window.tnApp.printBarcodeModal = () => {
            const locationId = barcodeModalLocationId?.textContent;
            const imgSrc = barcodeModalImage?.src;
            if (!locationId || !imgSrc) {
                alert('Błąd: Nie można odczytać danych do druku.');
                return;
            }

            const printWindow = window.open('', '_blank', 'height=400,width=600');
            if (!printWindow) {
                alert('Nie można otworzyć okna drukowania. Sprawdź ustawienia blokowania popupów.');
                return;
            }
            printWindow.document.write('<html><head><title>Drukuj Kod Kreskowy</title>');
            printWindow.document.write('<style>body { text-align: center; margin-top: 20px; font-family: sans-serif; } img { max-width: 90%; height: auto; } h4 { margin-bottom: 15px; }</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(`<h4>Lokalizacja: ${locationId}</h4>`);
            printWindow.document.write(`<img src="${imgSrc}" alt="Kod kreskowy dla ${locationId}">`);
            printWindow.document.write('<script>window.onload = function() { window.print(); window.onafterprint = function(){ window.close(); }; setTimeout(function(){ window.close(); }, 5000); };</script>'); // Drukuj i zamknij
            printWindow.document.write('</body></html>');
            printWindow.document.close();
             printWindow.focus(); // Focus on the new window is necessary for some browsers
        };
    }

    // --- Efekt Hover dla Slotów (Logika JS - bez zmian) ---
    const scaleFactor = 1.15;
    // Używamy delegacji zdarzeń dla hover na kontenerze siatki
    let lastHoveredSlot = null;

    warehouseGridContainer.addEventListener('mouseover', function(event) {
        const currentSlot = event.target.closest('.tn-location-slot');
        if (currentSlot && currentSlot !== lastHoveredSlot) {
            // Przywróć poprzedni slot do normalnego stanu
            if (lastHoveredSlot) {
                lastHoveredSlot.style.transform = 'scale(1)';
                const defaultZIndex = lastHoveredSlot.getAttribute('data-default-zindex') || '1';
                setTimeout(() => {
                     if (lastHoveredSlot && lastHoveredSlot.style.transform === 'scale(1)') {
                         lastHoveredSlot.style.zIndex = defaultZIndex;
                     }
                }, 250);
            }

            // Powiększ obecny slot, jeśli nie jest przyciemniony
            if (currentSlot.style.opacity !== '0.3') {
                const originalZIndex = window.getComputedStyle(currentSlot).zIndex;
                const defaultZIndex = (originalZIndex === 'auto' || originalZIndex === '0') ? '1' : originalZIndex;
                currentSlot.setAttribute('data-default-zindex', defaultZIndex); // Zapisz domyślny z-index
                currentSlot.style.zIndex = '10';
                currentSlot.style.transform = `scale(${scaleFactor})`;
                lastHoveredSlot = currentSlot;
            } else {
                lastHoveredSlot = null; // Resetuj, jeśli najechano na przyciemniony
            }
        }
    });

    warehouseGridContainer.addEventListener('mouseout', function(event) {
         // Sprawdź, czy opuszczamy kontener lub przechodzimy do elementu niebędącego slotem
        if (!event.relatedTarget || !event.relatedTarget.closest('.tn-location-slot')) {
            if (lastHoveredSlot) {
                 lastHoveredSlot.style.transform = 'scale(1)';
                 const defaultZIndex = lastHoveredSlot.getAttribute('data-default-zindex') || '1';
                 setTimeout(() => {
                      if (lastHoveredSlot && lastHoveredSlot.style.transform === 'scale(1)') {
                          lastHoveredSlot.style.zIndex = defaultZIndex;
                      }
                 }, 250);
                 lastHoveredSlot = null;
             }
        }
    });


});
