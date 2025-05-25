<?php
// Definicja klucza API dla frontendu
define('API_KEY_FRONTEND', '12345secret');
define('API_BASE_URL', 'api.php');
define('UPLOADS_DIR_FRONTEND', 'TNuploads/');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eMercPL - Aplikacja Mobilna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Podstawowe style */
        body {
            padding-bottom: 70px;
            background-color: #f0f2f5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 0.9rem;
            transition: background-color 0.3s, color 0.3s;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0; /* Usuń domyślny margines body */
        }

        .app-content-wrapper {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            visibility: hidden; /* Ukryj główną zawartość na początku */
        }
        .app-content-wrapper.loaded {
            visibility: visible;
        }


        /* Loader styles */
        #appLoader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #ff6600; /* Pomarańczowe tło */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.5s ease-out;
        }

        #appLoader.hidden {
            opacity: 0;
            pointer-events: none; /* Zapobiega interakcji po ukryciu */
        }

        #appLoader .loader-text {
            color: white;
            font-size: 2.5rem;
            font-weight: bold;
            text-align: center;
            animation: fadeInOut 3s ease-in-out forwards; /* forwards utrzymuje ostatni stan */
        }

        @keyframes fadeInOut {
            0% { opacity: 0; transform: scale(0.8); }
            25% { opacity: 1; transform: scale(1.05); } /* Pojawienie się i lekkie powiększenie */
            75% { opacity: 1; transform: scale(1); }   /* Utrzymanie widoczności */
            100% { opacity: 0; transform: scale(0.9); } /* Zniknięcie */
        }


        /* Nawigacja */
        .bottom-nav {
            position: fixed; bottom: 0; left: 0; right: 0; z-index: 1030;
            background-color: #ffffff; border-top: 1px solid #e0e0e0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.08);
            transition: background-color 0.3s, border-top-color 0.3s;
        }
        .bottom-nav .nav-link {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            font-size: 0.65rem; color: #6c757d;
            padding-top: 0.4rem; padding-bottom: 0.4rem;
            text-decoration: none; transition: color 0.15s ease-in-out;
            flex-grow: 1;
        }
        .bottom-nav .nav-link i { font-size: 1.3rem; margin-bottom: 2px; }
        .bottom-nav .nav-link.active { color: #ff6600; font-weight: 500; }
        .bottom-nav .nav-link:hover { color: #495057; }

        .app-header {
            background-color: #ff6600;
            color: white;
            padding: 0.65rem 1rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1020;
            transition: background-color 0.3s;
        }
        .app-header h1 { font-size: 1.15rem; margin-bottom: 0; font-weight: 600; }

        .main-content { padding: 0.75rem; flex-grow: 1; }
        .content-card {
            background-color: #ffffff; border-radius: 0.5rem;
            padding: 0.9rem; margin-bottom: 0.75rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            border: 1px solid #dee2e6;
            transition: background-color 0.3s, border-color 0.3s;
        }
        .loading-placeholder { text-align: center; padding: 2rem; color: #6c757d; }

        .product-list-img { width: 40px; height: 40px; object-fit: cover; margin-right: 10px; border-radius: 0.25rem; flex-shrink: 0; border: 1px solid #eee; }
        .details-link, .edit-link, .delete-link, .action-link { white-space: nowrap; margin-left: 4px; padding: 0.2rem 0.4rem; font-size: 0.75rem;}
        .details-link i, .edit-link i, .delete-link i, .action-link i { font-size: 0.9rem; }

        .dashboard-stat-card { border-left: 4px solid; transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
        .dashboard-stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .border-primary-accent { border-left-color: #0d6efd !important; }
        .border-success-accent { border-left-color: #198754 !important; }
        .border-info-accent { border-left-color: #0dcaf0 !important; }
        .border-warning-accent { border-left-color: #ffc107 !important; }
        .dashboard-stat-card .stat-value { font-size: 1.5rem; font-weight: 600; color: #212529; transition: color 0.3s;}
        .dashboard-stat-card .stat-label { font-size: 0.75rem; color: #495057; text-transform: uppercase; letter-spacing: 0.5px; transition: color 0.3s;}

        #productSearchInput, #orderSearchInput, #returnsSearchInput, #warehouseSearchInput { margin-bottom: 0.75rem; font-size: 0.9rem; }
        .list-group-item { padding-left: 0.5rem; padding-right: 0.5rem; border-left:0; border-right:0; transition: background-color 0.3s, border-color 0.3s;}
        .list-group-flush>.list-group-item:last-child { border-bottom-width: 1px; }

        .accordion-button { font-size: 0.9rem; padding: 0.75rem 1rem; transition: background-color 0.3s, color 0.3s; }
        .accordion-body { font-size: 0.85rem; transition: background-color 0.3s, color 0.3s;}
        .accordion-item.d-none { display: none !important; } 
        pre { font-size: 0.8rem; white-space: pre-wrap; background-color: #f8f9fa; padding: 0.5rem; border-radius: 0.25rem; transition: background-color 0.3s, color 0.3s, border-color 0.3s;}

        .form-label { font-size: 0.8rem; font-weight: 500; margin-bottom: 0.2rem; }
        .form-control, .form-select { font-size: 0.85rem; padding: 0.3rem 0.6rem; transition: background-color 0.3s, color 0.3s, border-color 0.3s;}
        .modal-body { max-height: 75vh; overflow-y: auto; }
        .modal-header .h5 { font-size: 1.1rem; }
        .modal-footer { padding: 0.5rem 0.75rem; }
        .modal-content { transition: background-color 0.3s, border-color 0.3s; }

        .toast-container { z-index: 1090 !important; }
        #scanResultCard .card-title { font-size: 1rem; font-weight: bold; }
        #scanResultCard .text-muted { font-size: 0.8rem; }
        #diagnosticsResultArea .table th, #diagnosticsResultArea .table td { font-size: 0.75rem; padding: 0.3rem; vertical-align: middle;}
        #diagnosticsResultArea .badge { font-size: 0.7rem; }

        .profile-avatar { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem; border: 3px solid #ff6600; }
        .profile-details dt { font-weight: 500; color: #495057; }
        .profile-details dd { color: #212529; }

        .product-filters .form-select, .product-filters .form-check-input, .product-filters .form-control { font-size: 0.8rem; }
        .product-filters .form-check-label { font-size: 0.8rem; }
        .product-filters > div { margin-bottom: 0.5rem;}

        .table-sm th, .table-sm td { font-size: 0.8rem; padding: 0.4rem;}
        .sold-product-name { font-weight: 500; }
        .sold-product-date { font-size: 0.7rem; color: #6c757d; }


        /* ----- TRYB CIEMNY ----- */
        body.dark-mode { background-color: #121212; color: #e0e0e0; }
        body.dark-mode .bottom-nav { background-color: #1e1e1e; border-top-color: #2c2c2c; }
        body.dark-mode .bottom-nav .nav-link { color: #a0a0a0; }
        body.dark-mode .bottom-nav .nav-link.active { color: #ff8c00; }
        body.dark-mode .bottom-nav .nav-link:hover { color: #c0c0c0; }
        body.dark-mode .app-header { background-color: #1e1e1e; }
        body.dark-mode .content-card { background-color: #1e1e1e; border-color: #2c2c2c; }
        body.dark-mode .loading-placeholder { color: #a0a0a0; }
        body.dark-mode .dashboard-stat-card .stat-value { color: #f0f0f0; }
        body.dark-mode .dashboard-stat-card .stat-label { color: #b0b0b0; }
        body.dark-mode .list-group-item { background-color: #1e1e1e; border-color: #2c2c2c !important; color: #e0e0e0; }
        body.dark-mode .list-group-item a { color: #ffA500; }
        body.dark-mode .list-group-item .text-muted { color: #888 !important; }
        body.dark-mode .accordion-button { background-color: #2c2c2c; color: #e0e0e0; }
        body.dark-mode .accordion-button:not(.collapsed) { background-color: #383838; }
        body.dark-mode .accordion-button::after { filter: invert(1) grayscale(100%) brightness(200%); }
        body.dark-mode .accordion-body { background-color: #1e1e1e; color: #d0d0d0; }
        body.dark-mode pre { background-color: #161616; color: #c0c0c0; border: 1px solid #2c2c2c; }
        body.dark-mode .form-control, body.dark-mode .form-select { background-color: #2c2c2c; color: #e0e0e0; border-color: #383838; }
        body.dark-mode .form-control::placeholder { color: #777; }
        body.dark-mode .modal-content { background-color: #1e1e1e; border-color: #2c2c2c; }
        body.dark-mode .modal-header, body.dark-mode .modal-footer { border-color: #2c2c2c; }
        body.dark-mode .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
        body.dark-mode .table { color: #e0e0e0; }
        body.dark-mode .table-bordered th, body.dark-mode .table-bordered td, body.dark-mode .table-bordered { border-color: #2c2c2c; }
        body.dark-mode .profile-details dt { color: #adb5bd; }
        body.dark-mode .profile-details dd { color: #e9ecef; }
        body.dark-mode .profile-avatar { border-color: #ff8c00; }
        body.dark-mode .sold-product-date { color: #a0a0a0; }
    </style>
</head>
<body>
    <div id="appLoader">
        <div class="loader-text">eMercPL</div>
    </div>

    <div class="app-content-wrapper">
        <header class="app-header">
            <h1 class="text-center" id="appTitle">eMercPL</h1>
        </header>

        <main class="main-content" id="mainContent">
            </main>

        <nav class="bottom-nav navbar navbar-expand p-0">
            <div class="container-fluid">
                <ul class="navbar-nav nav-fill w-100">
                    <li class="nav-item"><a class="nav-link" data-section="dashboard" href="#dashboard"><i class="bi bi-grid-1x2-fill"></i><span>Pulpit</span></a></li>
                    <li class="nav-item"><a class="nav-link" data-section="magazyn" href="#magazyn"><i class="bi bi-box-seam-fill"></i><span>Magazyn</span></a></li>
                    <li class="nav-item"><a class="nav-link" data-section="produkty" href="#produkty"><i class="bi bi-tags-fill"></i><span>Produkty</span></a></li>
                    <li class="nav-item"><a class="nav-link" data-section="zamowienia" href="#zamowienia"><i class="bi bi-cart-check-fill"></i><span>Zamówienia</span></a></li>
                    <li class="nav-item"><a class="nav-link" data-section="zwroty_reklamacje" href="#zwroty_reklamacje"><i class="bi bi-arrow-return-left"></i><span>Zwroty / Rekl.</span></a></li>
                    <li class="nav-item"><a class="nav-link" data-section="ustawienia" href="#ustawienia"><i class="bi bi-gear-fill"></i><span>Ustawienia</span></a></li>
                </ul>
            </div>
        </nav>
    </div>

    <div class="modal fade" id="appInfoModal" tabindex="-1" aria-labelledby="appInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="appInfoModalLabel"><i class="bi bi-info-circle-fill me-2"></i>Informacje o Aplikacji</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Nazwa Aplikacji:</strong> eMercPL (TN iMAG)</p>
                    <p><strong>Wersja:</strong> 1.3.1</p> 
                    <p><strong>Autor:</strong> Paweł Plichta</p>
                    <hr>
                    <p class="small text-muted">Aplikacja do zarządzania magazynem, produktami i zamówieniami.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Zamknij</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="profileEditModal" tabindex="-1" aria-labelledby="profileEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="profileEditModalLabel">Edytuj Profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="profileEditForm">
                        <div class="mb-3">
                            <label for="profileName" class="form-label">Imię i Nazwisko</label>
                            <input type="text" class="form-control form-control-sm" id="profileName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="profileEmail" class="form-label">Adres Email</label>
                            <input type="email" class="form-control form-control-sm" id="profileEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="profilePhone" class="form-label">Telefon</label>
                            <input type="tel" class="form-control form-control-sm" id="profilePhone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="profileAvatar" class="form-label">Nazwa pliku awatara (np. avatar.jpg)</label>
                            <input type="text" class="form-control form-control-sm" id="profileAvatar" name="avatar" placeholder="avatar.jpg">
                            <small class="form-text text-muted">Awatar należy wgrać osobno do folderu <?php echo UPLOADS_DIR_FRONTEND; ?>.</small>
                        </div>
                        <div class="alert alert-danger mt-2 d-none" id="profileEditFormError"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Anuluj</button>
                    <button type="button" class="btn btn-primary btn-sm" id="saveProfileButton">Zapisz Zmiany</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalLabel">Produkt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="productForm">
                        <input type="hidden" id="productId" name="id">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label for="productName" class="form-label">Nazwa produktu <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="productName" name="name" required>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="productProducer" class="form-label">Producent</label>
                                <input type="text" class="form-control form-control-sm" id="productProducer" name="producent">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label for="productSku" class="form-label">Nr katalogowy (SKU)</label>
                                <input type="text" class="form-control form-control-sm" id="productSku" name="tn_numer_katalogowy">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="productCategory" class="form-label">Kategoria</label>
                                <input type="text" class="form-control form-control-sm" id="productCategory" name="category">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label for="productPrice" class="form-label">Cena (zł)</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" id="productPrice" name="price" placeholder="0.00">
                            </div>
                             <div class="col-md-4 mb-2">
                                <label for="productStock" class="form-label">Ilość na stanie (katalog)</label>
                                <input type="number" step="1" class="form-control form-control-sm" id="productStock" name="stock" placeholder="0">
                                <small class="form-text text-muted">Ogólna ilość w katalogu.</small>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="productUnit" class="form-label">Jednostka miary</label>
                                <input type="text" class="form-control form-control-sm" id="productUnit" name="tn_jednostka_miary" value="szt.">
                            </div>
                        </div>
                        <hr>
                        <h6 class="mb-3">Dane Pojazdu (jeśli dotyczy)</h6>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label for="productVehicleBrand" class="form-label">Marka</label>
                                <input type="text" class="form-control form-control-sm" id="productVehicleBrand" name="marka">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="productVehicleModel" class="form-label">Model</label>
                                <input type="text" class="form-control form-control-sm" id="productVehicleModel" name="model">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="productVehicleType" class="form-label">Typ pojazdu</label>
                                <input type="text" class="form-control form-control-sm" id="productVehicleType" name="typ_pojazdu">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label for="productVehicleEngineCapacity" class="form-label">Poj. silnika (L)</label>
                                <input type="number" step="0.1" class="form-control form-control-sm" id="productVehicleEngineCapacity" name="pojemnosc_silnika" placeholder="Np. 1.9">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="productVehiclePowerKM" class="form-label">Moc (KM)</label>
                                <input type="number" step="1" class="form-control form-control-sm" id="productVehiclePowerKM" name="moc_km">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="productVehiclePowerKW" class="form-label">Moc (KW)</label>
                                <input type="number" step="1" class="form-control form-control-sm" id="productVehiclePowerKW" name="moc_kw">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="productVehicleYear" class="form-label">Rok produkcji</label>
                                <input type="number" step="1" min="1900" max="<?php echo date('Y') + 1; ?>" class="form-control form-control-sm" id="productVehicleYear" name="rok_produkcji" placeholder="Np. 2010">
                            </div>
                        </div>
                        <hr>
                        <div class="mb-2">
                            <label for="productImage" class="form-label">Nazwa pliku zdjęcia (np. prod_xxxx.jpg)</label>
                            <input type="text" class="form-control form-control-sm" id="productImage" name="image" placeholder="prod_xxxx.jpg" readonly>
                            <small class="form-text text-muted">Nazwa pliku zdjęcia, która zostanie użyta po wgraniu.</small>
                        </div>
                        <div class="mb-3" id="productImageUploadContainer">
                            <label for="productImageUpload" class="form-label">Wgraj zdjęcie produktu</label>
                            <input type="file" class="form-control form-control-sm" id="productImageUpload" name="product_image_upload" accept="image/*">
                            <small class="form-text text-muted">Wybierz plik graficzny (JPG, PNG, GIF).</small>
                            <div class="mt-2">
                                <img id="productImagePreview" src="" alt="Podgląd zdjęcia" class="img-fluid rounded" style="max-height: 150px; display: none;">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label for="productDesc" class="form-label">Opis</label>
                            <textarea class="form-control form-control-sm" id="productDesc" name="desc" rows="3"></textarea>
                        </div>
                         <div class="mb-2">
                            <label for="productVehicle" class="form-label">Pasuje do (Pojazdy - ogólne)</label>
                            <textarea class="form-control form-control-sm" id="productVehicle" name="vehicle" rows="3" placeholder="Np. Audi A4 B8 (2008-2015), BMW Seria 3 E90 (2005-2012)"></textarea>
                             <small class="form-text text-muted">Ogólne dopasowanie, jeśli nie podano szczegółowych danych pojazdu powyżej.</small>
                        </div>
                        <div class="mb-2">
                            <label for="productParams" class="form-label">Parametry</label>
                            <textarea class="form-control form-control-sm" id="productParams" name="params" rows="3"></textarea>
                        </div>
                        <div class="mb-2">
                            <label for="productSpec" class="form-label">Specyfikacja (dodatkowa)</label>
                            <textarea class="form-control form-control-sm" id="productSpec" name="spec" rows="3"></textarea>
                        </div>
                        <div class="alert alert-danger mt-2 d-none" id="productFormError"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Anuluj</button>
                    <button type="button" class="btn btn-primary btn-sm" id="saveProductButton">Zapisz Produkt</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderModalLabel">Zamówienie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="orderForm">
                        <input type="hidden" id="orderId" name="id">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label for="orderBuyerName" class="form-label">Nazwa kupującego <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="orderBuyerName" name="buyer_name" required>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="orderProductId" class="form-label">Produkt <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="orderProductId" name="product_id" required>
                                    <option value="">Wybierz produkt...</option>
                                    </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label for="orderQuantity" class="form-label">Ilość <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" id="orderQuantity" name="quantity" value="1" min="1" required>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="orderStatus" class="form-label">Status zamówienia</label>
                                <select class="form-select form-select-sm" id="orderStatus" name="status">
                                    <?php
                                    $statuses = ['Nowe', 'W realizacji', 'Oczekuje na płatność', 'Wysłane', 'Zrealizowane', 'Anulowane', 'Zwrot zgłoszony', 'Zwrot przyjęty', 'Reklamacja zgłoszona', 'Reklamacja w toku', 'Reklamacja uznana', 'Reklamacja odrzucona'];
                                    foreach ($statuses as $stat) {
                                        echo "<option value=\"{$stat}\">{$stat}</option>";
                                    }
                                    ?>
                                    </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="orderPaymentStatus" class="form-label">Status płatności</label>
                                <select class="form-select form-select-sm" id="orderPaymentStatus" name="tn_status_platnosci">
                                    <option value="Oczekuje na płatność">Oczekuje na płatność</option>
                                    <option value="Opłacone">Opłacone</option>
                                    <option value="Płatność przy odbiorze">Płatność przy odbiorze</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label for="orderShippingDetails" class="form-label">Dane do wysyłki <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-sm" id="orderShippingDetails" name="buyer_daneWysylki" rows="3" required></textarea>
                        </div>
                        <div class="row">
                             <div class="col-md-6 mb-2">
                                <label for="orderCourierId" class="form-label">Kurier</label>
                                <input type="text" class="form-control form-control-sm" id="orderCourierId" name="courier_id" placeholder="np. inpost_paczkomaty">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="orderTrackingNumber" class="form-label">Numer przesyłki</label>
                                <input type="text" class="form-control form-control-sm" id="orderTrackingNumber" name="tracking_number">
                            </div>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="orderProcessed" name="processed">
                            <label class="form-check-label small" for="orderProcessed">
                                Przetworzone (np. pobrane przez system zewnętrzny)
                            </label>
                        </div>
                        <div class="alert alert-danger mt-2 d-none" id="orderFormError"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Anuluj</button>
                    <button type="button" class="btn btn-primary btn-sm" id="saveOrderButton">Zapisz Zamówienie</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="assignProductModal" tabindex="-1" aria-labelledby="assignProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignProductModalLabel">Przyjmij Towar na Magazyn</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="assignProductForm">
                        <div class="mb-2">
                            <label for="assignProductId" class="form-label">Wybierz Produkt <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="assignProductId" name="product_id" required>
                                <option value="">Ładowanie produktów...</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="assignLocationId" class="form-label">Wybierz Pustą Lokalizację <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="assignLocationId" name="location_id" required>
                                <option value="">Ładowanie lokalizacji...</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="assignQuantity" class="form-label">Ilość <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="assignQuantity" name="quantity" value="1" min="1" required>
                        </div>
                        <div class="alert alert-danger mt-2 d-none" id="assignProductFormError"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Anuluj</button>
                    <button type="button" class="btn btn-primary btn-sm" id="saveAssignProductButton">Przypisz Produkt</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="moveProductModal" tabindex="-1" aria-labelledby="moveProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="moveProductModalLabel">Przesuń Towar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="moveProductForm">
                        <input type="hidden" id="moveSourceLocationId" name="source_location_id">
                        <input type="hidden" id="moveProductId" name="product_id">

                        <div class="mb-2">
                            <label class="form-label">Z lokalizacji:</label>
                            <input type="text" class="form-control form-control-sm" id="moveSourceLocationDisplay" readonly>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Produkt:</label>
                            <input type="text" class="form-control form-control-sm" id="moveProductNameDisplay" readonly>
                        </div>
                         <div class="mb-2">
                            <label class="form-label">Obecna ilość w lokalizacji źródłowej:</label>
                            <input type="number" class="form-control form-control-sm" id="moveCurrentQuantityDisplay" readonly>
                        </div>
                        <div class="mb-2">
                            <label for="moveQuantity" class="form-label">Ilość do przesunięcia <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="moveQuantity" name="quantity_to_move" value="1" min="1" required>
                        </div>
                        <div class="mb-2">
                            <label for="moveTargetLocationId" class="form-label">Do lokalizacji (pusta lub z tym samym produktem) <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="moveTargetLocationId" name="target_location_id" required>
                                <option value="">Ładowanie dostępnych lokalizacji...</option>
                            </select>
                        </div>
                        <div class="alert alert-danger mt-2 d-none" id="moveProductFormError"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Anuluj</button>
                    <button type="button" class="btn btn-primary btn-sm" id="saveMoveProductButton">Przesuń Produkt</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Potwierdź</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="confirmationModalBody">
                    Czy na pewno chcesz wykonać tę operację?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Anuluj</button>
                    <button type="button" class="btn btn-primary btn-sm" id="confirmActionButton">Potwierdź</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="returnComplaintModal" tabindex="-1" aria-labelledby="returnComplaintModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="returnComplaintModalLabel">Zgłoś Zwrot / Reklamację</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="returnComplaintForm">
                        <input type="hidden" id="returnComplaintOrderId" name="order_id">
                        <input type="hidden" id="returnComplaintProductId" name="product_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Zamówienie ID:</label>
                            <input type="text" class="form-control form-control-sm" id="returnComplaintDisplayOrderId" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Produkt:</label>
                            <input type="text" class="form-control form-control-sm" id="returnComplaintDisplayProduct" readonly>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="returnComplaintQuantity" class="form-label">Ilość zwracana/reklamowana <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" id="returnComplaintQuantity" name="quantity" min="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="returnComplaintType" class="form-label">Typ zgłoszenia <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="returnComplaintType" name="type" required>
                                    <option value="Zwrot zgłoszony">Zwrot</option>
                                    <option value="Reklamacja zgłoszona">Reklamacja</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="returnComplaintReason" class="form-label">Powód zgłoszenia <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-sm" id="returnComplaintReason" name="reason" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="returnComplaintWarehouseLocationId" class="form-label">Lokalizacja zwrotu na magazyn (opcjonalnie)</label>
                            <select class="form-select form-select-sm" id="returnComplaintWarehouseLocationId" name="warehouse_location_id">
                                <option value="">Wybierz lokalizację...</option>
                                </select>
                            <small class="form-text text-muted">Wybierz, jeśli produkt wraca na stan (np. po przyjęciu zwrotu).</small>
                        </div>
                        <div class="alert alert-danger mt-2 d-none" id="returnComplaintFormError"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Anuluj</button>
                    <button type="button" class="btn btn-primary btn-sm" id="saveReturnComplaintButton">Zgłoś</button>
                </div>
            </div>
        </div>
    </div>


    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer">
        </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Globalne zmienne konfiguracyjne i cache
    const API_KEY = '<?php echo API_KEY_FRONTEND; ?>';
    const API_URL_BASE = '<?php echo API_BASE_URL; ?>';
    const UPLOADS_URL_BASE = '<?php echo UPLOADS_DIR_FRONTEND; ?>';

    document.addEventListener('DOMContentLoaded', () => {
        const appLoader = document.getElementById('appLoader');
        const appContentWrapper = document.querySelector('.app-content-wrapper');

        const mainContent = document.getElementById('mainContent');
        const appTitle = document.getElementById('appTitle');
        const navLinks = document.querySelectorAll('.bottom-nav .nav-link');
        const defaultSection = 'dashboard';

        let allProductsCache = []; 
        let currentlyDisplayedProducts = []; 
        let allOrdersCache = [];
        let allWarehouseLocationsCache = []; 
        let currentUserProfile = null; 
        let currentProductFilters = {}; 

        let productModalInstance = null;
        let orderModalInstance = null;
        let assignProductModalInstance = null;
        let moveProductModalInstance = null;
        let appInfoModalInstance = null;
        let profileEditModalInstance = null;
        let confirmationModalInstance = null;
        let returnComplaintModalInstance = null; 


        if (document.getElementById('productModal')) productModalInstance = new bootstrap.Modal(document.getElementById('productModal'));
        if (document.getElementById('orderModal')) orderModalInstance = new bootstrap.Modal(document.getElementById('orderModal'));
        if (document.getElementById('assignProductModal')) assignProductModalInstance = new bootstrap.Modal(document.getElementById('assignProductModal'));
        if (document.getElementById('moveProductModal')) moveProductModalInstance = new bootstrap.Modal(document.getElementById('moveProductModal'));
        if (document.getElementById('appInfoModal')) appInfoModalInstance = new bootstrap.Modal(document.getElementById('appInfoModal'));
        if (document.getElementById('profileEditModal')) profileEditModalInstance = new bootstrap.Modal(document.getElementById('profileEditModal'));
        if (document.getElementById('confirmationModal')) confirmationModalInstance = new bootstrap.Modal(document.getElementById('confirmationModal'));
        if (document.getElementById('returnComplaintModal')) returnComplaintModalInstance = new bootstrap.Modal(document.getElementById('returnComplaintModal'));


        const productFormEl = document.getElementById('productForm');
        const productFormErrorEl = document.getElementById('productFormError');
        const saveProductButton = document.getElementById('saveProductButton');
        const productImageUploadInput = document.getElementById('productImageUpload'); 
        const productImageInput = document.getElementById('productImage'); 
        const productImagePreview = document.getElementById('productImagePreview');


        const orderFormEl = document.getElementById('orderForm');
        const orderFormErrorEl = document.getElementById('orderFormError');
        const saveOrderButton = document.getElementById('saveOrderButton');

        const assignProductFormEl = document.getElementById('assignProductForm');
        const assignProductFormErrorEl = document.getElementById('assignProductFormError');
        const saveAssignProductButton = document.getElementById('saveAssignProductButton');

        const moveProductFormEl = document.getElementById('moveProductForm');
        const moveProductFormErrorEl = document.getElementById('moveProductFormError');
        const saveMoveProductButton = document.getElementById('saveMoveProductButton');

        const profileEditFormEl = document.getElementById('profileEditForm');
        const profileEditFormErrorEl = document.getElementById('profileEditFormError');
        const saveProfileButton = document.getElementById('saveProfileButton');

        const returnComplaintFormEl = document.getElementById('returnComplaintForm');
        const returnComplaintFormErrorEl = document.getElementById('returnComplaintFormError');
        const saveReturnComplaintButton = document.getElementById('saveReturnComplaintButton');


        // Funkcja do wyświetlania modalu potwierdzenia
        window.showConfirmationModal = function(message) {
            return new Promise((resolve) => {
                const modalBody = document.getElementById('confirmationModalBody');
                const confirmBtn = document.getElementById('confirmActionButton');
                const cancelBtn = confirmationModalInstance._element.querySelector('[data-bs-dismiss="modal"]');

                modalBody.textContent = message;

                const handleConfirm = () => {
                    confirmationModalInstance.hide();
                    confirmBtn.removeEventListener('click', handleConfirm);
                    cancelBtn.removeEventListener('click', handleCancel);
                    resolve(true);
                };

                const handleCancel = () => {
                    confirmationModalInstance.hide();
                    confirmBtn.removeEventListener('click', handleConfirm);
                    cancelBtn.removeEventListener('click', handleCancel);
                    resolve(false);
                };

                confirmBtn.addEventListener('click', handleConfirm);
                cancelBtn.addEventListener('click', handleCancel);

                confirmationModalInstance.show();
            });
        };

        function hideLoader() {
            if (appLoader) {
                appLoader.classList.add('hidden');
                setTimeout(() => {
                    if(appContentWrapper) appContentWrapper.classList.add('loaded');
                }, 500); 
            }
        }

        function showAppContentInitial() {
            if(appContentWrapper) appContentWrapper.classList.add('loaded');
            document.body.style.paddingBottom = '70px';
        }

        window.handleLogout = function() {
            sessionStorage.removeItem('isLoggedIn');
            allProductsCache = [];
            currentlyDisplayedProducts = [];
            allOrdersCache = [];
            allWarehouseLocationsCache = [];
            currentUserProfile = null;
            currentProductFilters = {};
            window.location.hash = '#dashboard'; 
            window.location.reload(); 
        }


        async function fetchApi(endpoint, options = {}) {
            const base = API_URL_BASE.endsWith('/') ? API_URL_BASE : API_URL_BASE + '/';
            const cleanEndpoint = endpoint.startsWith('/') ? endpoint.substring(1) : endpoint;
            
            let url = `${base}${cleanEndpoint}`; 
            
            if (options.method === 'GET' && options.filters && Object.keys(options.filters).length > 0) {
                const queryParams = new URLSearchParams();
                for (const key in options.filters) {
                    if (options.filters[key] !== null && options.filters[key] !== undefined && options.filters[key] !== '') {
                         queryParams.append(key, options.filters[key]);
                    }
                }
                const queryString = queryParams.toString();
                if (queryString) {
                    url += `?${queryString}`;
                }
            }
            console.log("Fetching URL:", url, "Options:", options); 

            const defaultHeaders = {
                'X-API-KEY': API_KEY
            };
            if (!(options.body instanceof FormData)) {
                defaultHeaders['Content-Type'] = 'application/json';
            }
            options.headers = { ...defaultHeaders, ...options.headers };

            const response = await fetch(url, options);
            
            if (response.status === 401) {
                showToast("Sesja wygasła lub brak autoryzacji. Proszę zalogować się ponownie.", "danger");
                handleLogout(); 
                throw new Error("Brak autoryzacji (401)");
            }

            const contentType = response.headers.get("content-type");
            let responseData = {};
            if (response.status !== 204) { 
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    responseData = await response.json();
                } else {
                    responseData = { message: await response.text() }; 
                }
            }

            if (!response.ok) {
                const errorMessage = responseData.error || responseData.message || `Błąd HTTP: ${response.status} ${response.statusText}`;
                console.error(`Błąd API dla ${url}:`, errorMessage, responseData); 
                throw new Error(errorMessage);
            }
            return responseData;
        }

        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toastContainer');
            if (!toastContainer) {
                console.error("Toast container not found!");
                return;
            }
            const toastId = 'toast-' + Date.now();
            let bgClass = '';
            switch (type) {
                case 'success': bgClass = 'bg-success'; break;
                case 'danger': bgClass = 'bg-danger'; break;
                case 'warning': bgClass = 'bg-warning text-dark'; break;
                case 'info':
                default: bgClass = 'bg-info text-dark'; break;
            }

            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>`;
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
        }
        
        function populateCategoryFilter(productsSource) { 
            const categoryFilter = document.getElementById('productCategoryFilter');
            if (!categoryFilter) return;

            const categories = new Set();
            if (Array.isArray(productsSource)) {
                productsSource.forEach(p => {
                    if (p.category && p.category.trim() !== '') {
                        categories.add(p.category.trim());
                    }
                });
            }

            categoryFilter.innerHTML = '<option value="">Wszystkie kategorie</option>'; 
            Array.from(categories).sort().forEach(cat => {
                const option = document.createElement('option');
                option.value = cat;
                option.textContent = cat;
                categoryFilter.appendChild(option);
            });
        }

        function generujHtmlListyProduktow(produktyDoWyswietlenia) { 
            let filtersHtml = `
                <div class="row product-filters mb-3 gy-2">
                    <div class="col-md-4 col-sm-6">
                        <label for="productSearchInput" class="form-label visually-hidden">Szukaj</label>
                        <input type="text" id="productSearchInput" class="form-control form-control-sm" placeholder="Nazwa, ID, SKU...">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label for="productCategoryFilter" class="form-label visually-hidden">Kategoria</label>
                        <select id="productCategoryFilter" class="form-select form-select-sm">
                            <option value="">Wszystkie kategorie</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label for="productBrandFilter" class="form-label visually-hidden">Marka</label>
                        <input type="text" id="productBrandFilter" class="form-control form-control-sm" placeholder="Marka pojazdu...">
                    </div>
                    <div class="col-md-2 col-sm-6">
                         <label for="productModelFilter" class="form-label visually-hidden">Model</label>
                        <input type="text" id="productModelFilter" class="form-control form-control-sm" placeholder="Model pojazdu...">
                    </div>
                    <div class="col-md-1 col-sm-12 d-flex align-items-center justify-content-start justify-content-md-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="productInStockFilter">
                            <label class="form-check-label" for="productInStockFilter">Na stanie</label>
                        </div>
                    </div>
                     <div class="col-12">
                        <button id="applyApiFiltersButton" class="btn btn-sm btn-primary"><i class="bi bi-funnel-fill"></i> Filtruj Zaawansowane</button>
                        <button id="resetApiFiltersButton" class="btn btn-sm btn-outline-secondary ms-1"><i class="bi bi-x-lg"></i> Resetuj</button>
                    </div>
                </div>`;

            if (!Array.isArray(produktyDoWyswietlenia)) { 
                 return `<div class="content-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h5 mb-0">Produkty</h2>
                                <button class="btn btn-sm btn-success" onclick="openAddProductModal()"><i class="bi bi-plus-lg"></i> Dodaj</button>
                            </div>
                            ${filtersHtml}
                            <div id="productListContainer" class="mt-2"><p class="text-danger mt-3 small">Błąd ładowania produktów lub brak danych.</p></div>
                        </div>`;
            }
            
            let listaHtml = '<ul class="list-group list-group-flush" id="productListItems">';
            produktyDoWyswietlenia.forEach(produkt => {
                const nazwa = produkt.name || 'Brak nazwy';
                const id = produkt.id || 'Brak ID';
                const nrKat = produkt.tn_numer_katalogowy || '';
                const kategoria = produkt.category || 'Brak kategorii';
                const stan = produkt.stock || 0;
                const marka = produkt.marka || '';
                const model = produkt.model || '';

                const imgUrl = produkt.image ? `${UPLOADS_URL_BASE}${produkt.image}` : `https://placehold.co/40x40/eee/ccc?text=${encodeURIComponent(nazwa.charAt(0) || '?')}`;
                const imgErrorPlaceholder = `https://placehold.co/40x40/eee/ccc?text=?`;

                listaHtml += `
                    <li class="list-group-item d-flex align-items-center ps-0 product-list-item-filterable" 
                        data-category="${kategoria.toLowerCase()}" 
                        data-stock="${stan}"
                        data-brand="${marka.toLowerCase()}"
                        data-model="${model.toLowerCase()}">
                        <img src="${imgUrl}" alt="${nazwa}" class="product-list-img" onerror="this.onerror=null;this.src='${imgErrorPlaceholder}';">
                        <div class="flex-grow-1 me-2">
                            <strong class="d-block small product-name">${nazwa}</strong>
                            <small class="text-muted product-info" style="font-size: 0.7rem;">ID: ${id} ${nrKat ? '| Nr kat: ' + nrKat : ''}</small>
                            <small class="d-block text-muted product-category-display" style="font-size: 0.7rem;">Kat: ${kategoria} | Stan: ${stan}</small>
                            ${marka || model ? `<small class="d-block text-muted product-vehicle-display" style="font-size: 0.7rem;">Pojazd: ${marka} ${model}</small>` : ''}
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button onclick="loadProductDetails(${id});" class="btn btn-outline-secondary details-link" title="Szczegóły"><i class="bi bi-eye"></i></button>
                            <button onclick="openEditProductModal(${id});" class="btn btn-outline-primary edit-link" title="Edytuj"><i class="bi bi-pencil-square"></i></button>
                            <button onclick="deleteProduct(${id});" class="btn btn-outline-danger delete-link" title="Usuń"><i class="bi bi-trash"></i></button>
                        </div>
                    </li>`;
            });
            listaHtml += '</ul>';
            
            let noResultsMessageHtml = '';
            if (produktyDoWyswietlenia.length === 0 && Object.keys(currentProductFilters).length > 0) {
                 noResultsMessageHtml = '<p class="text-muted mt-3 small">Brak produktów spełniających kryteria zaawansowane.</p>';
            } else if (produktyDoWyswietlenia.length === 0) {
                 noResultsMessageHtml = '<p class="text-muted mt-3 small">Brak produktów do wyświetlenia.</p>';
            }


            const fullHtml = `<div class="content-card">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h2 class="h5 mb-0">Produkty</h2>
                                    <button class="btn btn-sm btn-success" onclick="openAddProductModal()"><i class="bi bi-plus-lg"></i> Dodaj</button>
                                </div>
                                ${filtersHtml}
                                <div id="productListContainer" class="mt-2">${noResultsMessageHtml || listaHtml}</div>
                                <p id="noMatchingProductsMessage" class="text-muted mt-3 small d-none">Brak produktów spełniających kryteria wyszukiwania/kategorii/stanu.</p>
                            </div>`;
            return fullHtml;
        }

        async function applyApiProductFilters() {
            const brandFilterVal = document.getElementById('productBrandFilter')?.value.trim() || '';
            const modelFilterVal = document.getElementById('productModelFilter')?.value.trim() || '';
            
            currentProductFilters = {}; 
            if (brandFilterVal) currentProductFilters.marka = brandFilterVal;
            if (modelFilterVal) currentProductFilters.model = modelFilterVal;
            
            const searchInputVal = document.getElementById('productSearchInput')?.value;
            const categoryFilterVal = document.getElementById('productCategoryFilter')?.value;
            const inStockOnlyVal = document.getElementById('productInStockFilter')?.checked;

            if (searchInputVal) currentProductFilters.name = searchInputVal;
            if (categoryFilterVal) currentProductFilters.category = categoryFilterVal;
            if (inStockOnlyVal) currentProductFilters.in_stock = 'true';


            console.log("Stosowanie filtrów API:", currentProductFilters);
            await loadContent('produkty', null, true); 
        }

        async function resetApiProductFilters() {
            currentProductFilters = {};
            const brandFilter = document.getElementById('productBrandFilter');
            const modelFilter = document.getElementById('productModelFilter');
            const searchInput = document.getElementById('productSearchInput');
            const categoryFilter = document.getElementById('productCategoryFilter');
            const inStockFilter = document.getElementById('productInStockFilter');

            if(brandFilter) brandFilter.value = '';
            if(modelFilter) modelFilter.value = '';
            if(searchInput) searchInput.value = '';
            if(categoryFilter) categoryFilter.value = '';
            if(inStockFilter) inStockFilter.checked = false;
            
            await loadContent('produkty', null, true); 
        }


        function filterProductsClientSide() { 
            const searchInput = document.getElementById('productSearchInput');
            const categoryFilter = document.getElementById('productCategoryFilter');
            const inStockFilter = document.getElementById('productInStockFilter');
            const noMatchingMessage = document.getElementById('noMatchingProductsMessage');

            if (!searchInput || !categoryFilter || !inStockFilter || !noMatchingMessage) {
                return;
            }

            const searchTerm = searchInput.value.toLowerCase().trim();
            const selectedCategory = categoryFilter.value.toLowerCase();
            const inStockOnly = inStockFilter.checked;

            const productItems = document.querySelectorAll('#productListItems .product-list-item-filterable'); 
            let visibleCount = 0;

            productItems.forEach(item => {
                const name = item.querySelector('.product-name')?.textContent.toLowerCase() || '';
                const info = item.querySelector('.product-info')?.textContent.toLowerCase() || ''; 
                const itemCategory = item.dataset.category.toLowerCase();
                const itemStock = parseInt(item.dataset.stock, 10);
                
                const matchesSearch = name.includes(searchTerm) || info.includes(searchTerm);
                const matchesCategory = selectedCategory === "" || itemCategory === selectedCategory;
                const matchesStock = !inStockOnly || itemStock > 0;

                if (matchesSearch && matchesCategory && matchesStock) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            const productListContainer = document.getElementById('productListContainer');
            const productList = productListContainer.querySelector('#productListItems');

            // Sprawdź, czy `currentlyDisplayedProducts` jest zdefiniowane i jest tablicą
            const currentProductList = Array.isArray(currentlyDisplayedProducts) ? currentlyDisplayedProducts : [];

            if (currentProductList.length > 0 && productList && productList.children.length > 0) {
                 noMatchingMessage.classList.toggle('d-none', visibleCount > 0);
            } else if (Object.keys(currentProductFilters).length > 0 && productListContainer.querySelector('p.text-muted')) {
                noMatchingMessage.classList.add('d-none');
            } else if (currentProductList.length === 0 && Object.keys(currentProductFilters).length === 0) { 
                noMatchingMessage.classList.add('d-none'); 
            }
        }


        function generujHtmlDashboard(summaryData, ostatnieProduktyKatalog) { 
            const {
                totalSalesValue = 0, totalOrders = 0, productsInStockCount = 0, totalProductsCatalog = 0,
                todaySalesValue = 0, todayOrdersCount = 0, currentMonthSalesValue = 0, currentMonthOrdersCount = 0,
                newOrdersTodayCount = 0, 
                pendingOrdersCount = 0,
                recentlySoldProducts = [] 
            } = summaryData || {};

            let ostatnieDodaneProduktyHtml = '<p class="text-muted mt-3 small">Brak ostatnio dodanych produktów.</p>';
            if (Array.isArray(ostatnieProduktyKatalog) && ostatnieProduktyKatalog.length > 0) {
                 ostatnieDodaneProduktyHtml = '<ul class="list-group list-group-flush">';
                 const produktyDoWyswietlenia = ostatnieProduktyKatalog.slice(0, 5); 
                 produktyDoWyswietlenia.forEach(produkt => {
                    const nazwa = produkt.name || 'Brak nazwy';
                    const id = produkt.id || 'Brak ID';
                    const nrKat = produkt.tn_numer_katalogowy || '';
                    const imgUrl = produkt.image ? `${UPLOADS_URL_BASE}${produkt.image}` : `https://placehold.co/40x40/eee/ccc?text=${encodeURIComponent(nazwa.charAt(0) || '?')}`;
                    const imgErrorPlaceholder = `https://placehold.co/40x40/eee/ccc?text=?`;
                    ostatnieDodaneProduktyHtml += `
                        <li class="list-group-item d-flex align-items-center ps-0">
                            <img src="${imgUrl}" alt="${nazwa}" class="product-list-img" onerror="this.onerror=null;this.src='${imgErrorPlaceholder}';">
                            <div class="flex-grow-1 me-2">
                                <a href="#" onclick="loadProductDetails(${id}); return false;" class="text-decoration-none">
                                    <strong class="d-block small product-name">${nazwa}</strong>
                                </a>
                                <small class="text-muted product-info" style="font-size: 0.7rem;">ID: ${id} ${nrKat ? '| Nr kat: ' + nrKat : ''}</small>
                            </div>
                            <button onclick="loadProductDetails(${id});" class="btn btn-sm btn-outline-secondary details-link" title="Szczegóły"><i class="bi bi-eye"></i></button>
                        </li>`;
                 });
                 ostatnieDodaneProduktyHtml += '</ul>';
            }

            let ostatnioSprzedaneHtml = '<p class="text-muted mt-2 small">Brak ostatnio sprzedanych produktów.</p>';
            if (Array.isArray(recentlySoldProducts) && recentlySoldProducts.length > 0) {
                ostatnioSprzedaneHtml = `
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 50px;"></th> <th>Produkt</th>
                                    <th class="text-center">Ilość</th>
                                    <th class="text-center">Akcje</th>
                                </tr>
                            </thead>
                            <tbody>`;
                recentlySoldProducts.forEach(item => {
                    const imgUrl = item.product_image ? `${UPLOADS_URL_BASE}${item.product_image}` : `https://placehold.co/30x30/eee/ccc?text=${encodeURIComponent(item.product_name.charAt(0) || '?')}`;
                    const imgErrorPlaceholder = `https://placehold.co/30x30/eee/ccc?text=?`;
                    ostatnioSprzedaneHtml += `
                        <tr>
                            <td><img src="${imgUrl}" alt="${item.product_name}" class="product-list-img" style="width:30px; height:30px;" onerror="this.onerror=null;this.src='${imgErrorPlaceholder}';"></td>
                            <td>
                                <a href="#" onclick="loadProductDetails(${item.product_id}); return false;" class="text-decoration-none sold-product-name">${item.product_name}</a>
                                <div class="sold-product-date">${item.order_date_formatted}</div>
                            </td>
                            <td class="text-center small">${item.quantity}</td>
                            <td class="text-center"><button class="btn btn-sm btn-outline-info py-0 px-1" onclick="loadOrderDetails(${item.order_id})" title="Zobacz zamówienie"><i class="bi bi-receipt"></i></button></td>
                        </tr>`;
                });
                ostatnioSprzedaneHtml += `</tbody></table></div>`;
            }


            return `
                <div class="row">
                    <div class="col-6 mb-2"><div class="content-card dashboard-stat-card border-primary-accent text-center h-100 p-2"><div class="stat-label">Sprzedaż Dzisiaj</div><div class="stat-value">${parseFloat(todaySalesValue).toFixed(2)} zł</div><small class="text-muted">${todayOrdersCount} zam.</small></div></div>
                    <div class="col-6 mb-2"><div class="content-card dashboard-stat-card border-success-accent text-center h-100 p-2"><div class="stat-label">Sprzedaż (Msc)</div><div class="stat-value">${parseFloat(currentMonthSalesValue).toFixed(2)} zł</div><small class="text-muted">${currentMonthOrdersCount} zam.</small></div></div>
                    <div class="col-6 mb-2"><div class="content-card dashboard-stat-card border-info-accent text-center h-100 p-2"><div class="stat-label">Nowe Zam. Dzisiaj</div><div class="stat-value">${newOrdersTodayCount}</div><small class="text-muted">zam.</small></div></div>
                    <div class="col-6 mb-2"><div class="content-card dashboard-stat-card border-warning-accent text-center h-100 p-2"><div class="stat-label">Zam. Oczekujące</div><div class="stat-value">${pendingOrdersCount}</div><small class="text-muted">zam.</small></div></div>
                    <div class="col-6 mb-2"><div class="content-card dashboard-stat-card border-info-accent text-center h-100 p-2"><div class="stat-label">Prod. w Mag.</div><div class="stat-value">${productsInStockCount}</div><small class="text-muted">szt.</small></div></div>
                    <div class="col-6 mb-2"><div class="content-card dashboard-stat-card border-warning-accent text-center h-100 p-2"><div class="stat-label">Wszystkich Zam.</div><div class="stat-value">${totalOrders}</div></div></div>
                </div>
                <div class="content-card">
                    <h3 class="mb-2 h6">Ostatnio Sprzedane Produkty</h3>
                    ${ostatnioSprzedaneHtml}
                </div>
                <div class="content-card"><h3 class="mb-2 h6">Ostatnio Dodane Produkty (Katalog)</h3>${ostatnieDodaneProduktyHtml}</div>
                <div class="content-card"><h3 class="mb-3 h6">Szybkie Akcje</h3><div class="d-grid gap-2">
                    <button class="btn btn-primary btn-sm" onclick="openAddProductModal()"><i class="bi bi-plus-circle me-1"></i>Dodaj Produkt</button>
                    <button class="btn btn-info btn-sm" onclick="openAddOrderModal()"><i class="bi bi-cart-plus me-1"></i>Dodaj Zamówienie</button>
                    </div></div>
                <div id="scanResultCardContainer" class="mt-2"></div>`;
        }

        function generujHtmlListyZamowien(zamowienia, filterFunction = null) {
            let filtrowaneZamowienia = zamowienia;
            if (typeof filterFunction === 'function') {
                filtrowaneZamowienia = zamowienia.filter(filterFunction);
            }

            if (!Array.isArray(filtrowaneZamowienia) || filtrowaneZamowienia.length === 0) {
                return `<p class="text-muted mt-3 small">Brak zamówień spełniających kryteria.</p>`;
            }

            let listaHtml = '<ul class="list-group list-group-flush" id="orderListItems">';
            filtrowaneZamowienia.sort((a, b) => (b.id || 0) - (a.id || 0));

            filtrowaneZamowienia.forEach(zam => {
                const status = zam.status || 'Nieznany';
                const statusClassMap = {'zrealizowane':'success','anulowane':'danger','w realizacji':'primary','nowe':'info','oczekuje na płatność':'secondary', 'wysłane': 'warning'};
                const statusClass = statusClassMap[status.toLowerCase()] || 'dark';
                const orderId = zam.id || '?';

                listaHtml += `
                    <li class="list-group-item d-flex justify-content-between align-items-center ps-0 order-item">
                        <div>
                            <a href="#" onclick="loadOrderDetails(${orderId}); return false;" class="text-decoration-none">
                                <strong class="small order-id">Zamówienie #${orderId}</strong>
                            </a>
                            <small class="d-block text-muted order-buyer" style="font-size: 0.7rem;">${zam.buyer_name || 'Anonim'}</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-${statusClass} rounded-pill small me-2 order-status">${status}</span>
                            <div class="btn-group btn-group-sm">
                                <button onclick="loadOrderDetails(${orderId});" class="btn btn-outline-secondary details-link" title="Szczegóły"><i class="bi bi-eye"></i></button>
                                <button onclick="openEditOrderModal(${orderId});" class="btn btn-outline-primary edit-link" title="Edytuj"><i class="bi bi-pencil-square"></i></button>
                                <button onclick="deleteOrder(${orderId});" class="btn btn-outline-danger delete-link" title="Usuń"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    </li>`;
            });
            listaHtml += '</ul>';
            return listaHtml;
        }

        function filterOrders() { 
            const searchInput = document.getElementById('orderSearchInput') || document.getElementById('returnsSearchInput');
            if (!searchInput) return;

            const searchTerm = searchInput.value.toLowerCase().trim();
            const listItemsContainerId = searchInput.id === 'orderSearchInput' ? 'orderListItems' : 'returnsListItems';
            const orderItems = document.querySelectorAll(`#${listItemsContainerId} li.order-item`);
            let visibleCount = 0;

            orderItems.forEach(item => {
                const orderIdText = item.querySelector('.order-id')?.textContent.toLowerCase() || '';
                const buyerNameText = item.querySelector('.order-buyer')?.textContent.toLowerCase() || '';
                const statusText = item.querySelector('.order-status')?.textContent.toLowerCase() || '';

                const isVisible = (
                    orderIdText.includes(searchTerm) ||
                    buyerNameText.includes(searchTerm) ||
                    statusText.includes(searchTerm)
                );
                item.style.display = isVisible ? 'flex' : 'none';
                if (isVisible) visibleCount++;
            });
        }
        
        function filterWarehouseLocations() {
            const searchInput = document.getElementById('warehouseSearchInput');
            if (!searchInput) return;
            const searchTerm = searchInput.value.toLowerCase().trim();
            const locationItems = document.querySelectorAll('#warehouseLocationListItems li.location-item');

            locationItems.forEach(item => {
                const locationIdText = item.querySelector('.location-id-text')?.textContent.toLowerCase() || '';
                const productInfoText = item.querySelector('.location-search-hidden')?.textContent.toLowerCase() || 
                                            item.querySelector('.location-product-info')?.textContent.toLowerCase() || '';
                
                item.style.display = (
                    locationIdText.includes(searchTerm) ||
                    productInfoText.includes(searchTerm)
                ) ? 'flex' : 'none';
            });
        }


        function generujHtmlWidokuMagazynu(lokalizacje) {
            let listContentHtml;
            if (!Array.isArray(lokalizacje) || lokalizacje.length === 0) {
                 listContentHtml = '<div class="alert alert-info small mt-2">Brak lokalizacji magazynowych.</div>';
            } else {
                allWarehouseLocationsCache = lokalizacje; 
                listContentHtml = '<ul class="list-group list-group-flush" id="warehouseLocationListItems">';
                const posortowaneLokalizacje = [...lokalizacje].sort((a,b)=>(a.id||'').localeCompare(b.id||''));

                posortowaneLokalizacje.forEach(l => {
                    const status = l.status || 'empty';
                    const statusClass = status === 'occupied' ? 'success' : 'secondary';
                    const produktId = l.product_id || null;
                    const ilosc = l.quantity || 0;
                    let produktNazwa = '';
                    let produktInfoForSearch = ''; 

                    if (status === 'occupied' && produktId !== null) {
                        const produktZCache = allProductsCache.find(p => p.id === produktId);
                        produktNazwa = produktZCache ? produktZCache.name : 'Produkt ID: ' + produktId;
                        produktInfoForSearch = `${produktNazwa} id:${produktId}`; 
                    }
                    const produktInfoDisplay = status === 'occupied' ? `(${produktNazwa}, Ilość: ${ilosc})` : '';
                    const statusText = status === 'occupied' ? 'Zajęte' : 'Puste';

                    listContentHtml += `<li class="list-group-item d-flex justify-content-between align-items-center ps-0 location-item">
                                <div>
                                    <strong class="small location-id-text">${l.id || 'Brak ID'}</strong>
                                    <small class="d-block text-muted location-product-info" style="font-size:0.7rem;">${produktInfoDisplay}</small>
                                    <span class="d-none location-search-hidden">${produktInfoForSearch}</span>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    ${status === 'occupied' ? `<button class="btn btn-outline-warning action-link" title="Zdejmij towar" onclick="handleRemoveProductFromLocation('${l.id}')"><i class="bi bi-box-arrow-up"></i></button>` : ''}
                                    ${status === 'occupied' ? `<button class="btn btn-outline-info action-link" title="Przesuń towar" onclick="openMoveProductModal('${l.id}', ${produktId}, ${ilosc})"><i class="bi bi-arrows-move"></i></button>` : ''}
                                    <span class="badge bg-${statusClass} rounded-pill small ms-1 p-2">${statusText}</span>
                                </div>
                            </li>`;
                });
                listContentHtml += '</ul>';
            }
           
            return `<div class="content-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">Magazyn</h2>
                            <button class="btn btn-sm btn-success" onclick="openAssignProductModal()"><i class="bi bi-box-arrow-in-down"></i> Przyjmij Towar</button>
                        </div>
                        <input type="text" id="warehouseSearchInput" class="form-control form-control-sm" placeholder="Szukaj (ID lok., nazwa/ID prod.)...">
                        <div id="warehouseLocationListContainer" class="mt-2">${listContentHtml}</div>
                    </div>`;
        }

        function parseVehicleData(vehicleString) {
            if (!vehicleString || vehicleString.trim() === '') {
                return '<p class="small text-muted">Brak informacji o pasujących pojazdach (ogólne).</p>';
            }
            const vehicles = vehicleString.split(/[,;\n\r]/) 
                                .map(v => v.trim())
                                .filter(v => v !== ''); 

            if (vehicles.length > 0) {
                let html = '<ul class="list-unstyled mb-0 small">';
                vehicles.forEach(v => {
                    const listItem = document.createElement('li');
                    const icon = document.createElement('i');
                    icon.className = 'bi bi-car-front-fill text-secondary me-1'; 
                    listItem.appendChild(icon);
                    listItem.appendChild(document.createTextNode(v)); 
                    html += listItem.outerHTML;
                });
                html += '</ul>';
                return html;
            }
            const p = document.createElement('p');
            p.className = "small";
            p.appendChild(document.createTextNode(vehicleString));
            return p.outerHTML;
        }


        function generujHtmlSzczegolowProduktu(produkt) {
            if (!produkt || produkt.error) return `<div class="content-card"><div class="alert alert-warning small">Nie znaleziono produktu lub błąd: ${produkt?.error || 'Nieznany błąd'}.</div><button class="btn btn-sm btn-light" onclick="loadContent('produkty')"><i class="bi bi-arrow-left"></i> Wróć do listy</button></div>`;

            const p = produkt;
            const imgUrl = p.image ? `${UPLOADS_URL_BASE}${p.image}` : `https://placehold.co/150x150/eee/ccc?text=${encodeURIComponent(p.name?.charAt(0) || '?')}`;
            const errUrl = 'https://placehold.co/150x150/eee/ccc?text=?';
            
            const formatTextForDisplay = (text) => text && text.trim() !== '' ? text.replace(/\r\n|\r|\n/g, '<br>') : '';
            
            const desc = formatTextForDisplay(p.desc);
            const vehGeneral = parseVehicleData(p.vehicle); 
            const params = formatTextForDisplay(p.params);
            const spec = formatTextForDisplay(p.spec);

            let vehicleDetailsHtml = '';
            if (p.marka || p.model || p.typ_pojazdu || p.pojemnosc_silnika || p.moc_km || p.moc_kw || p.rok_produkcji) {
                vehicleDetailsHtml = `
                    <h6 class="small fw-bold mt-2">Szczegóły Pojazdu:</h6>
                    <dl class="row mb-0" style="font-size:0.75rem;">
                        ${p.marka ? `<dt class="col-5 col-sm-4">Marka:</dt><dd class="col-7 col-sm-8">${p.marka}</dd>` : ''}
                        ${p.model ? `<dt class="col-5 col-sm-4">Model:</dt><dd class="col-7 col-sm-8">${p.model}</dd>` : ''}
                        ${p.typ_pojazdu ? `<dt class="col-5 col-sm-4">Typ:</dt><dd class="col-7 col-sm-8">${p.typ_pojazdu}</dd>` : ''}
                        ${p.rok_produkcji ? `<dt class="col-5 col-sm-4">Rok prod.:</dt><dd class="col-7 col-sm-8">${p.rok_produkcji}</dd>` : ''}
                        ${p.pojemnosc_silnika ? `<dt class="col-5 col-sm-4">Poj. silnika:</dt><dd class="col-7 col-sm-8">${p.pojemnosc_silnika} L</dd>` : ''}
                        ${p.moc_km ? `<dt class="col-5 col-sm-4">Moc (KM):</dt><dd class="col-7 col-sm-8">${p.moc_km} KM</dd>` : ''}
                        ${p.moc_kw ? `<dt class="col-5 col-sm-4">Moc (KW):</dt><dd class="col-7 col-sm-8">${p.moc_kw} KW</dd>` : ''}
                    </dl>`;
            }


            const accordionItems = [
                { id: 'cOpis', title: 'Opis', content: desc, expanded: true },
                { id: 'cPasuje', title: 'Pasuje do (ogólne)', content: vehGeneral },
                { id: 'cParams', title: 'Parametry', content: params },
                { id: 'cSpec', title: 'Specyfikacja', content: spec }
            ];

            let accordionHtml = '';
            let hasContent = false;
            accordionItems.forEach(item => {
                const itemHasActualContent = item.content && item.content.trim() !== '' && item.content.indexOf('Brak informacji') === -1 && item.content.indexOf('Brak informacji o pasujących pojazdach (ogólne).') === -1;
                if (itemHasActualContent) {
                    hasContent = true;
                    accordionHtml += `
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button ${item.expanded && itemHasActualContent ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#${item.id}" aria-expanded="${item.expanded && itemHasActualContent ? 'true' : 'false'}">
                                    ${item.title}
                                </button>
                            </h2>
                            <div id="${item.id}" class="accordion-collapse collapse ${item.expanded && itemHasActualContent ? 'show' : ''}" data-bs-parent="#pdAccordion">
                                <div class="accordion-body">${item.content}</div>
                            </div>
                        </div>`;
                }
            });


            return `<div class="content-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <button class="btn btn-sm btn-light" onclick="loadContent('produkty')"><i class="bi bi-arrow-left"></i> Wróć</button>
                            <div>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="openEditProductModal(${p.id})"><i class="bi bi-pencil-square"></i> Edytuj</button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(${p.id})"><i class="bi bi-trash"></i> Usuń</button>
                            </div>
                        </div>
                        <h2 class="h5 mb-3">${p.name || 'Brak nazwy'}</h2>
                        <div class="row g-3">
                            <div class="col-md-4 text-center mb-2 mb-md-0">
                                <img src="${imgUrl}" alt="${p.name||''}" class="img-fluid rounded border" style="max-height:180px; object-fit:contain;" onerror="this.onerror=null;this.src='${errUrl}';">
                            </div>
                            <div class="col-md-8">
                                <dl class="row mb-0" style="font-size:0.8rem;">
                                    <dt class="col-5 col-sm-4">ID:</dt><dd class="col-7 col-sm-8">${p.id||'?'}</dd>
                                    <dt class="col-5 col-sm-4">Nr kat:</dt><dd class="col-7 col-sm-8">${p.tn_numer_katalogowy||'-'}</dd>
                                    <dt class="col-5 col-sm-4">Producent:</dt><dd class="col-7 col-sm-8">${p.producent||'-'}</dd>
                                    <dt class="col-5 col-sm-4">Cena:</dt><dd class="col-7 col-sm-8">${p.price ? parseFloat(p.price).toFixed(2)+' zł':'-'}</dd>
                                    <dt class="col-5 col-sm-4">Stan (katalog):</dt><dd class="col-7 col-sm-8">${p.stock!=null ? p.stock : '0'} ${p.tn_jednostka_miary||'szt.'}</dd>
                                    <dt class="col-5 col-sm-4">Kategoria:</dt><dd class="col-7 col-sm-8">${p.category||'-'}</dd>
                                </dl>
                                ${vehicleDetailsHtml}
                            </div>
                        </div>
                        <hr class="my-3">
                        <div class="accordion" id="pdAccordion">
                            ${hasContent ? accordionHtml : '<p class="small text-muted">Brak dodatkowych informacji o produkcie.</p>'}
                        </div>
                    </div>`;
        }

        function generujHtmlSzczegolowZamowienia(zamowienie, produkt) {
            if (!zamowienie || zamowienie.error) return `<div class="content-card"><div class="alert alert-warning small">Nie znaleziono zamówienia lub błąd: ${zamowienie?.error || 'Nieznany błąd'}.</div><button class="btn btn-sm btn-light" onclick="loadContent('zamowienia')"><i class="bi bi-arrow-left"></i> Wróć do listy</button></div>`;

            const z = zamowienie; const p = produkt;
            const status = z.status || 'Nieznany';
            const statusClassMap = {'zrealizowane':'success','anulowane':'danger','w realizacji':'primary','nowe':'info','oczekuje na płatność':'secondary', 'wysłane': 'warning', 'zwrot zgłoszony': 'warning', 'zwrot przyjęty': 'info', 'reklamacja zgłoszona': 'warning', 'reklamacja w toku': 'primary', 'reklamacja uznana': 'success', 'reklamacja odrzucona': 'danger'};
            const statusClass = statusClassMap[status.toLowerCase()] || 'dark';

            const statusPlatnosci = z.tn_status_platnosci || 'Nieznany';
            const statusPlatnosciClassMap = {'opłacone':'success','oczekuje na płatność':'warning','płatność przy odbiorze':'info'};
            const statusPlatnosciClass = statusPlatnosciClassMap[statusPlatnosci.toLowerCase()] || 'secondary';

            let produktHtml = '<p class="text-warning small">Brak danych produktu.</p>';
            if (p && !p.error) {
                produktHtml = `<h6 class="mt-3 mb-1 small fw-bold">Zamówiony produkt</h6>
                               <p class="small mb-0">
                                 <a href="#" onclick="loadProductDetails(${p.id}); return false;"><strong>${p.name||'?'}</strong> (ID: ${p.id||'?'})</a><br>
                                 Ilość: ${z.quantity||'?'} ${p.tn_jednostka_miary||'szt.'}<br>
                                 Cena jedn.: ${p.price ? parseFloat(p.price).toFixed(2)+' zł':'-'}
                               </p>`;
            } else if (z.product_id && p && p.error) {
                 produktHtml = `<p class="text-danger small">Błąd ładowania danych produktu ID: ${z.product_id}.</p>`;
            }


            const statusOptions = ['Nowe', 'W realizacji', 'Oczekuje na płatność', 'Wysłane', 'Zrealizowane', 'Anulowane', 'Zwrot zgłoszony', 'Zwrot przyjęty', 'Reklamacja zgłoszona', 'Reklamacja w toku', 'Reklamacja uznana', 'Reklamacja odrzucona'];
            const canReturnOrComplain = !['anulowane', 'zwrot przyjęty', 'reklamacja uznana', 'reklamacja odrzucona'].includes(status.toLowerCase());


            return `<div class="content-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <button class="btn btn-sm btn-light" onclick="loadContent('zamowienia')"><i class="bi bi-arrow-left"></i> Wróć</button>
                            <div>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="openEditOrderModal(${z.id})"><i class="bi bi-pencil-square"></i> Edytuj</button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteOrder(${z.id})"><i class="bi bi-trash"></i> Usuń</button>
                            </div>
                        </div>
                        <h2 class="h5 mb-2">Zamówienie #${z.id||'?'}</h2>
                        <div class="mb-2">
                            <span class="badge bg-${statusClass} rounded-pill">${status}</span>
                            <span class="badge bg-${statusPlatnosciClass} rounded-pill">${statusPlatnosci}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <h6 class="small text-muted fw-bold">Kupujący</h6>
                                <p class="mb-1 small"><strong>${z.buyer_name||'-'}</strong></p>
                                <pre class="small bg-light p-2 rounded" style="white-space:pre-wrap;">${z.buyer_daneWysylki||'-'}</pre>
                            </div>
                            <div class="col-md-6">
                                <h6 class="small text-muted fw-bold">Wysyłka</h6>
                                <p class="small mb-1">Kurier: ${z.courier_id||'-'}<br>
                                   Nr przesyłki: ${z.tracking_number||'-'}
                                   ${z.tracking_number && z.courier_id ? ` <a href="${generateTrackingLink(z.courier_id, z.tracking_number)}" target="_blank" class="btn btn-sm btn-outline-info py-0 px-1 ms-1" title="Śledź"><i class="bi bi-truck"></i></a>` : ''}
                                </p>
                                ${produktHtml}
                            </div>
                        </div>
                        <hr class="my-3">
                        <div class="row">
                            <div class="col-md-8 mb-2">
                                <h6 class="small text-muted fw-bold">Zmień Status Zamówienia</h6>
                                <form id="changeOrderStatusForm" class="d-flex align-items-end">
                                    <input type="hidden" name="order_id_status" value="${z.id||''}">
                                    <div class="flex-grow-1 me-2">
                                        <label for="newOrderStatus" class="form-label visually-hidden">Nowy status</label>
                                        <select name="new_order_status" id="newOrderStatus" class="form-select form-select-sm">
                                            ${statusOptions.map(st => `<option value="${st}" ${status.toLowerCase() === st.toLowerCase() ? 'selected' : ''}>${st}</option>`).join('')}
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="handleChangeOrderStatus(${z.id||''})">Zapisz Status</button>
                                </form>
                            </div>
                            <div class="col-md-4 mb-2 d-flex align-items-end">
                                ${canReturnOrComplain && p && !p.error ? `<button class="btn btn-sm btn-outline-danger w-100" onclick="openReturnComplaintModal(${z.id}, ${p.id}, ${z.quantity}, '${p.name.replace(/'/g, "\\'")}')"><i class="bi bi-arrow-return-left"></i> Zgłoś Zwrot/Rekl.</button>` : ''}
                            </div>
                        </div>
                    </div>`;
        }

        function generateTrackingLink(courierId, trackingNumber) {
            if (!courierId || !trackingNumber) return '#';
            const num = encodeURIComponent(trackingNumber);
            switch (courierId.toLowerCase()) {
                case 'inpost_paczkomaty': case 'inpost': return `https://inpost.pl/sledzenie-przesylek?number=${num}`;
                case 'poczta_polska': return `https://emonitoring.poczta-polska.pl/?numer=${num}`;
                case 'dpd': return `https://tracktrace.dpd.com.pl/findParcel?parcelNr=${num}`;
                case 'dhl': return `https://sprawdz.dhl.com.pl/szukaj.aspx?m=0&sn=${num}`;
                default: return `https://www.google.com/search?q=${encodeURIComponent(courierId + ' tracking ' + num)}`;
            }
        }

        window.handleChangeOrderStatus = async function(orderId) {
            const form = document.getElementById('changeOrderStatusForm');
            if (!form) return;
            const newStatus = form.new_order_status.value;
            const confirmed = await showConfirmationModal(`Czy na pewno chcesz zmienić status zamówienia #${orderId} na "${newStatus}"?`);
            if (!confirmed) return;

            try {
                const result = await fetchApi(`zamowienia/${orderId}/zmien_status`, {
                    method: 'POST',
                    body: JSON.stringify({ nowy_status: newStatus })
                });
                showToast(result.message || 'Status zamówienia zaktualizowany.', 'success');
                loadOrderDetails(orderId); 
                const orderIndex = allOrdersCache.findIndex(o => o.id === orderId);
                if (orderIndex > -1 && result.zamowienie) {
                    allOrdersCache[orderIndex] = result.zamowienie;
                }
            } catch (error) {
                console.error('Błąd zmiany statusu:', error);
                showToast(`Błąd: ${error.message}`, 'danger');
            }
        }
        
        window.openAppInfoModal = function() {
            if (appInfoModalInstance) {
                appInfoModalInstance.show();
            }
        }

        function generujHtmlProfilu(user) {
            if (!user || user.error) {
                return `<div class="content-card"><div class="alert alert-danger small">Nie udało się załadować profilu: ${user?.error || 'Nieznany błąd'}.</div></div>`;
            }
            const displayName = user.tn_imie_nazwisko || user.username || 'Użytkownik';
            const avatarUrl = user.avatar ? `${UPLOADS_URL_BASE}${user.avatar}` : `https://placehold.co/100x100/ff6600/ffffff?text=${encodeURIComponent(displayName.charAt(0) || 'U')}`;
            const avatarErrorUrl = `https://placehold.co/100x100/6c757d/ffffff?text=?`;

            return `<div class="content-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">Mój Profil</h2>
                            <button class="btn btn-sm btn-primary" onclick="openProfileEditModal(currentUserProfile)"><i class="bi bi-pencil-square me-1"></i>Edytuj Profil</button>
                        </div>
                        <div class="text-center mb-3">
                            <img src="${avatarUrl}" alt="Awatar użytkownika ${displayName}" class="profile-avatar" onerror="this.onerror=null;this.src='${avatarErrorUrl}';">
                        </div>
                        <dl class="row profile-details">
                            <dt class="col-sm-4">Nazwa użytkownika:</dt>
                            <dd class="col-sm-8">${user.username || '-'}</dd>

                            <dt class="col-sm-4">Imię i Nazwisko:</dt>
                            <dd class="col-sm-8">${user.tn_imie_nazwisko || '-'}</dd>

                            <dt class="col-sm-4">Adres Email:</dt>
                            <dd class="col-sm-8">${user.email || '-'}</dd>

                            <dt class="col-sm-4">Telefon:</dt>
                            <dd class="col-sm-8">${user.phone || '-'}</dd>
                            
                            <dt class="col-sm-4">ID Użytkownika:</dt>
                            <dd class="col-sm-8">${user.id || '-'}</dd>
                        </dl>
                        <hr>
                        <h6 class="h6 mt-3 mb-2">Bezpieczeństwo</h6>
                        <small class="d-block text-muted mt-1">Funkcja logowania biometrycznego została usunięta.</small>
                    </div>`;
        }


        function generujHtmlUstawienia() {
            const isDarkModeActive = document.body.classList.contains('dark-mode');
            return `<div class="content-card">
                        <h2 class="mb-3 h5">Ustawienia Aplikacji</h2>
                        <div class="list-group">
                            <a href="#" class="list-group-item list-group-item-action" onclick="loadProfileView(); return false;">
                                <i class="bi bi-person-circle me-2"></i>Mój Profil
                            </a>
                            <a href="#" class="list-group-item list-group-item-action" onclick="runDiagnostics(); return false;">
                                <i class="bi bi-hdd-stack-fill me-2"></i>Diagnostyka Plików Danych
                            </a>
                            <a href="#" class="list-group-item list-group-item-action" onclick="showToast('Funkcja Zarządzania Kluczami API - niezaimplementowana', 'warning'); return false;">
                                <i class="bi bi-key-fill me-2"></i>Zarządzanie Kluczami API
                            </a>
                            <a href="#" class="list-group-item list-group-item-action" onclick="showToast('Funkcja Eksportu Danych - niezaimplementowana', 'warning'); return false;">
                                <i class="bi bi-download me-2"></i>Eksportuj Dane
                            </a>
                             <a href="#" class="list-group-item list-group-item-action" onclick="showToast('Funkcja Importu Danych - niezaimplementowana', 'warning'); return false;">
                                <i class="bi bi-upload me-2"></i>Importuj Dane
                            </a>
                            <div class="list-group-item">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="darkModeSwitch" onchange="toggleDarkMode(this.checked)" ${isDarkModeActive ? 'checked' : ''}>
                                    <label class="form-check-label small" for="darkModeSwitch">Tryb Ciemny</label>
                                </div>
                            </div>
                            <a href="#" class="list-group-item list-group-item-action" onclick="openAppInfoModal(); return false;">
                                <i class="bi bi-info-circle-fill me-2"></i>Informacje o Aplikacji
                            </a>
                             <a href="#" class="list-group-item list-group-item-action text-danger" onclick="handleLogout(); return false;">
                                <i class="bi bi-box-arrow-right me-2"></i>Wyloguj
                            </a>
                        </div>
                    </div>
                    <div class="content-card" id="diagnosticsResultArea" style="display: none;">
                         <h3 class="mb-2 h6">Wyniki Diagnostyki Plików</h3>
                         <div id="diagnosticsContent" class="table-responsive"></div>
                    </div>
                    <div class="content-card">
                        <h3 class="mb-2 h6">Klucz API (Frontend)</h3>
                        <input type="text" class="form-control form-control-sm" value="${API_KEY}" readonly>
                        <small class="form-text text-muted">Używany przez interfejs do komunikacji z API.</small>
                    </div>`;
        }

        function generujHtmlListyZwrotowReklamacji(zamowienia) {
            if (!Array.isArray(zamowienia)) return '<div class="alert alert-warning small">Błąd wczytywania zamówień.</div>';

            const statusyFiltra = ['anulowane','zwrot zgłoszony','zwrot przyjęty','reklamacja zgłoszona','reklamacja w toku','reklamacja uznana','reklamacja odrzucona'];
            const filtrowaneZamowienia = zamowienia.filter(z => z.status && statusyFiltra.includes(z.status.toLowerCase()));

            let html = `<div class="content-card">
                                <h2 class="mb-3 h5">Zwroty i Reklamacje</h2>
                                <input type="text" id="returnsSearchInput" class="form-control form-control-sm" placeholder="Szukaj (ID zam., kupujący, status)...">
                                <div id="returnsListContainer" class="mt-2">`;

            if(filtrowaneZamowienia.length === 0) {
                html += '<div class="alert alert-info small">Brak aktywnych zwrotów lub reklamacji.</div>';
            } else {
                html +='<ul class="list-group list-group-flush" id="returnsListItems">';
                filtrowaneZamowienia.sort((a,b)=>(b.id || 0)-(a.id || 0));

                filtrowaneZamowienia.forEach(z => {
                    const id = z.id || '?';
                    const status = z.status || 'Nieznany';
                    const statusClassMap = {'anulowane':'danger','zwrot zgłoszony':'warning','zwrot przyjęty':'info','reklamacja zgłoszona':'warning','reklamacja w toku':'primary','reklamacja uznana':'success','reklamacja odrzucona':'danger'};
                    const statusClass = statusClassMap[status.toLowerCase()] || 'dark';

                    html += `<li class="list-group-item d-flex justify-content-between align-items-center ps-0 order-item">
                                <div>
                                    <a href="#" onclick="loadOrderDetails(${id}); return false;" class="text-decoration-none">
                                        <strong class="small order-id">Zamówienie #${id}</strong>
                                    </a>
                                    <small class="d-block text-muted order-buyer" style="font-size:0.7rem;">${z.buyer_name||'Anonim'}</small>
                                </div>
                                <span class="badge bg-${statusClass} rounded-pill small order-status">${status}</span>
                            </li>`;
                });
                html += '</ul>';
            }
            html += '</div></div>';
            return html;
        }
        
        window.runDiagnostics = async function() {
            const resultArea = document.getElementById('diagnosticsResultArea');
            const resultContent = document.getElementById('diagnosticsContent');
            if (!resultArea || !resultContent) return;

            resultArea.style.display = 'block';
            resultContent.innerHTML = `<div class="loading-placeholder"><div class="spinner-border spinner-border-sm"></div> Sprawdzanie...</div>`;

            try {
                const data = await fetchApi('diagnostics/datafiles'); 

                let reportHtml = `<p class="small"><strong>Status Ogólny:</strong> <span class="badge bg-${data.status_ogolny === 'OK' ? 'success' : (data.status_ogolny === 'OSTRZEŻENIE' ? 'warning' : 'danger')}">${data.status_ogolny}</span></p`;
                reportHtml += '<div class="table-responsive"><table class="table table-sm table-bordered mt-2">';
                reportHtml += '<thead><tr><th>Zasób</th><th>Istnieje</th><th>Czytelny</th><th>Zapisywalny</th><th>JSON OK</th><th>Info</th></tr></thead><tbody>';

                if (data.szczegoly_plikow && Array.isArray(data.szczegoly_plikow)) { 
                    data.szczegoly_plikow.forEach(fileDetails => { 
                        const getIcon = (boolVal) => boolVal ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>';
                        const getWritableIcon = (isWritable, exists) => {
                            if (isWritable) return '<i class="bi bi-check-circle-fill text-success"></i>';
                            if (exists && !isWritable) return '<i class="bi bi-exclamation-triangle-fill text-warning"></i>';
                            return '<i class="bi bi-x-circle-fill text-danger"></i>';
                        };

                        reportHtml += `<tr>
                            <td><strong class="small">${fileDetails.file_name}</strong></td>
                            <td class="text-center">${getIcon(fileDetails.exists)}</td>
                            <td class="text-center">${getIcon(fileDetails.readable)}</td>
                            <td class="text-center">${getWritableIcon(fileDetails.writable, fileDetails.exists)}</td>
                            <td class="text-center">${getIcon(fileDetails.json_valid)}</td>
                            <td class="small">${fileDetails.message || '-'}</td>
                        </tr>`;
                    });
                } else {
                     reportHtml += '<tr><td colspan="6" class="text-center small">Brak szczegółowych danych o plikach lub nieprawidłowy format.</td></tr>';
                }
                reportHtml += '</tbody></table></div>';
                resultContent.innerHTML = reportHtml;

            } catch (error) {
                console.error('Błąd diagnostyki:', error);
                resultContent.innerHTML = `<div class="alert alert-danger small">Wystąpił błąd podczas diagnostyki: ${error.message}</div>`;
            }
        }

        window.toggleDarkMode = function(isDarkMode) {
            if (isDarkMode) {
                document.body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
                showToast('Tryb ciemny włączony.', 'info');
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
                showToast('Tryb ciemny wyłączony.', 'info');
            }
        }
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
        }


        // PRODUKTY
        window.openAddProductModal = function() {
            productFormEl.reset();
            document.getElementById('productId').value = '';
            document.getElementById('productModalLabel').textContent = 'Dodaj Nowy Produkt';
            productFormErrorEl.classList.add('d-none'); productFormErrorEl.textContent = '';
            if(productImageUploadInput) productImageUploadInput.value = ''; 
            if(productImagePreview) {
                productImagePreview.style.display = 'none'; 
                productImagePreview.src = ''; 
            }
            if (productModalInstance) productModalInstance.show();
        }

        window.openEditProductModal = async function(productId) {
            productFormEl.reset();
            productFormErrorEl.classList.add('d-none'); productFormErrorEl.textContent = '';
            document.getElementById('productModalLabel').textContent = 'Edytuj Produkt';
            if(productImageUploadInput) productImageUploadInput.value = ''; 
            if(productImagePreview) {
                productImagePreview.style.display = 'none'; 
                productImagePreview.src = ''; 
            }

            try {
                const produkt = await fetchApi(`produkty/${productId}`);
                document.getElementById('productId').value = produkt.id || '';
                document.getElementById('productName').value = produkt.name || '';
                document.getElementById('productProducer').value = produkt.producent || '';
                document.getElementById('productSku').value = produkt.tn_numer_katalogowy || '';
                document.getElementById('productCategory').value = produkt.category || '';
                document.getElementById('productPrice').value = produkt.price || '';
                document.getElementById('productStock').value = produkt.stock || '';
                document.getElementById('productUnit').value = produkt.tn_jednostka_miary || 'szt.';
                if(productImageInput) productImageInput.value = produkt.image || ''; 

                document.getElementById('productVehicleBrand').value = produkt.marka || '';
                document.getElementById('productVehicleModel').value = produkt.model || '';
                document.getElementById('productVehicleType').value = produkt.typ_pojazdu || '';
                document.getElementById('productVehicleEngineCapacity').value = produkt.pojemnosc_silnika || '';
                document.getElementById('productVehiclePowerKM').value = produkt.moc_km || '';
                document.getElementById('productVehiclePowerKW').value = produkt.moc_kw || '';
                document.getElementById('productVehicleYear').value = produkt.rok_produkcji || '';


                if (produkt.image && productImagePreview) {
                    productImagePreview.src = `${UPLOADS_URL_BASE}${produkt.image}`;
                    productImagePreview.style.display = 'block';
                } else if (productImagePreview) {
                    productImagePreview.style.display = 'none';
                    productImagePreview.src = '';
                }

                document.getElementById('productDesc').value = produkt.desc || '';
                document.getElementById('productVehicle').value = produkt.vehicle || ''; 
                document.getElementById('productParams').value = produkt.params || '';
                document.getElementById('productSpec').value = produkt.spec || '';
                if (productModalInstance) productModalInstance.show();
            }
            catch (error) {
                console.error('Błąd ładowania produktu do edycji:', error);
                showToast(`Błąd ładowania produktu: ${error.message}`, 'danger');
            }
        }

        if(productImageUploadInput) {
            productImageUploadInput.addEventListener('change', handleProductImageChange);
        }

        function handleProductImageChange() {
            if (!productImageUploadInput || !productImagePreview) return;
            const file = productImageUploadInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    productImagePreview.src = e.target.result;
                    productImagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);

                const timestamp = new Date().getTime();
                const fileExtension = file.name.split('.').pop();
                const simulatedFilename = `prod_${timestamp}.${fileExtension}`;
                if (productImageInput) productImageInput.value = simulatedFilename; 
            } else {
                productImagePreview.src = '';
                productImagePreview.style.display = 'none';
                if (productImageInput) productImageInput.value = ''; 
            }
        }


        async function handleSaveProduct() {
            const productId = document.getElementById('productId').value;
            const method = productId ? 'PUT' : 'POST';
            const endpoint = productId ? `produkty/${productId}` : `produkty`; 

            const formData = new FormData(productFormEl);
            const productData = {};
            formData.forEach((value, key) => {
                if (['price', 'shipping', 'pojemnosc_silnika'].includes(key)) {
                    productData[key] = value !== '' ? parseFloat(value) : null;
                } else if (['stock', 'moc_km', 'moc_kw', 'rok_produkcji'].includes(key)) {
                     productData[key] = value !== '' ? parseInt(value, 10) : null;
                } else if (key !== 'product_image_upload') {
                    productData[key] = value;
                }
            });

             if (productImageInput) productData.image = productImageInput.value;


            if (!productData.name || productData.name.trim() === '') {
                productFormErrorEl.textContent = 'Nazwa produktu jest wymagana.';
                productFormErrorEl.classList.remove('d-none');
                return;
            }
            productFormErrorEl.classList.add('d-none');
            saveProductButton.disabled = true;

            try {
                const result = await fetchApi(endpoint, { method: method, body: JSON.stringify(productData) });
                showToast(result.message || 'Produkt zapisany pomyślnie.', 'success');
                if (productModalInstance) productModalInstance.hide();
                loadContent('produkty', null, true); 
            } catch (error) {
                console.error('Błąd zapisu produktu:', error);
                productFormErrorEl.textContent = error.message;
                productFormErrorEl.classList.remove('d-none');
                showToast(`Błąd zapisu produktu: ${error.message}`, 'danger');
            } finally {
                saveProductButton.disabled = false;
            }
        }
        if(saveProductButton) saveProductButton.addEventListener('click', handleSaveProduct);

        window.deleteProduct = async function(productId) {
            const confirmed = await showConfirmationModal(`Czy na pewno chcesz usunąć produkt ID: ${productId}? Tej operacji nie można cofnąć.`);
            if (!confirmed) return;

            try {
                const result = await fetchApi(`produkty/${productId}`, { method: 'DELETE' }); 
                showToast(result.message || 'Produkt usunięty.', 'success');
                loadContent('produkty', null, true); 
            } catch (error) {
                console.error('Błąd usuwania produktu:', error);
                showToast(`Błąd usuwania produktu: ${error.message}`, 'danger');
            }
        }

        // ZAMÓWIENIA
        window.openAddOrderModal = async function() {
            orderFormEl.reset();
            document.getElementById('orderId').value = '';
            document.getElementById('orderModalLabel').textContent = 'Dodaj Nowe Zamówienie';
            orderFormErrorEl.classList.add('d-none'); orderFormErrorEl.textContent = '';

            const productSelect = document.getElementById('orderProductId');
            productSelect.innerHTML = '<option value="">Ładowanie produktów...</option>';
            try {
                let productsToLoad = allProductsCache;
                if (!productsToLoad || productsToLoad.length === 0) {
                    productsToLoad = await fetchApi('produkty'); 
                    allProductsCache = Array.isArray(productsToLoad) ? productsToLoad : [];
                }

                productSelect.innerHTML = '<option value="">Wybierz produkt...</option>';
                if (Array.isArray(productsToLoad)) {
                    productsToLoad.sort((a,b)=>(a.name||'').localeCompare(b.name||'')).forEach(prod => {
                        productSelect.innerHTML += `<option value="${prod.id}">${prod.name} (ID: ${prod.id})</option>`;
                    });
                }
            } catch (error) {
                console.error('Błąd ładowania produktów do zamówienia:', error);
                productSelect.innerHTML = '<option value="">Błąd ładowania produktów</option>';
                showToast('Błąd ładowania listy produktów.', 'danger');
            }
            if (orderModalInstance) orderModalInstance.show();
        }

        window.openEditOrderModal = async function(orderId) {
            orderFormEl.reset();
            orderFormErrorEl.classList.add('d-none'); orderFormErrorEl.textContent = '';
            document.getElementById('orderModalLabel').textContent = 'Edytuj Zamówienie';
            const productSelect = document.getElementById('orderProductId');
            productSelect.innerHTML = '<option value="">Ładowanie...</option>';

            try {
                const orderData = await fetchApi(`zamowienia/${orderId}`); 
                let productsToLoad = allProductsCache;
                if (!productsToLoad || productsToLoad.length === 0) {
                     productsToLoad = await fetchApi('produkty'); 
                     allProductsCache = Array.isArray(productsToLoad) ? productsToLoad : [];
                }

                productSelect.innerHTML = '<option value="">Wybierz produkt...</option>';
                if (Array.isArray(productsToLoad)) {
                     productsToLoad.sort((a,b)=>(a.name||'').localeCompare(b.name||'')).forEach(prod => {
                        const selected = (prod.id === orderData.product_id) ? 'selected' : '';
                        productSelect.innerHTML += `<option value="${prod.id}" ${selected}>${prod.name} (ID: ${prod.id})</option>`;
                    });
                }

                document.getElementById('orderId').value = orderData.id || '';
                document.getElementById('orderBuyerName').value = orderData.buyer_name || '';
                document.getElementById('orderShippingDetails').value = orderData.buyer_daneWysylki || '';
                document.getElementById('orderQuantity').value = orderData.quantity || 1;
                document.getElementById('orderStatus').value = orderData.status || 'Nowe';
                document.getElementById('orderPaymentStatus').value = orderData.tn_status_platnosci || 'Oczekuje na płatność';
                document.getElementById('orderCourierId').value = orderData.courier_id || '';
                document.getElementById('orderTrackingNumber').value = orderData.tracking_number || '';
                document.getElementById('orderProcessed').checked = orderData.processed || false;

                if (orderModalInstance) orderModalInstance.show();
            } catch (error) {
                console.error('Błąd ładowania zamówienia do edycji:', error);
                showToast(`Błąd ładowania zamówienia: ${error.message}`, 'danger');
            }
        }

        async function handleSaveOrder() {
            const orderId = document.getElementById('orderId').value;
            const method = orderId ? 'PUT' : 'POST';
            const endpoint = orderId ? `zamowienia/${orderId}` : `zamowienia`; 

            const formData = new FormData(orderFormEl);
            const orderData = {};
            formData.forEach((value, key) => {
                if (key === 'product_id' || key === 'quantity') orderData[key] = parseInt(value, 10);
                else if (key === 'processed') orderData[key] = document.getElementById('orderProcessed').checked;
                else orderData[key] = value;
            });

            if (!orderData.product_id || !orderData.buyer_name || !orderData.quantity || !orderData.buyer_daneWysylki) {
                orderFormErrorEl.textContent = 'Wymagane pola: Produkt, Nazwa kupującego, Ilość, Dane do wysyłki.';
                orderFormErrorEl.classList.remove('d-none');
                return;
            }
            orderFormErrorEl.classList.add('d-none');
            saveOrderButton.disabled = true;

            try {
                const result = await fetchApi(endpoint, { method: method, body: JSON.stringify(orderData) });
                showToast(result.message || 'Zamówienie zapisane pomyślnie.', 'success');
                if (orderModalInstance) orderModalInstance.hide();
                loadContent('zamowienia', null, true); 
            } catch (error) {
                console.error('Błąd zapisu zamówienia:', error);
                orderFormErrorEl.textContent = error.message;
                orderFormErrorEl.classList.remove('d-none');
                showToast(`Błąd zapisu zamówienia: ${error.message}`, 'danger');
            } finally {
                saveOrderButton.disabled = false;
            }
        }
        if(saveOrderButton) saveOrderButton.addEventListener('click', handleSaveOrder);

        window.deleteOrder = async function(orderId) {
            const confirmed = await showConfirmationModal(`Czy na pewno chcesz usunąć zamówienie ID: ${orderId}?`);
            if (!confirmed) return;

            try {
                const result = await fetchApi(`zamowienia/${orderId}`, { method: 'DELETE' }); 
                showToast(result.message || 'Zamówienie usunięte.', 'success');
                loadContent('zamowienia', null, true); 
            } catch (error) {
                console.error('Błąd usuwania zamówienia:', error);
                showToast(`Błąd usuwania zamówienia: ${error.message}`, 'danger');
            }
        }

        // MAGAZYN - PRZYPISZ
        window.openAssignProductModal = async function() {
            assignProductFormEl.reset();
            document.getElementById('assignProductModalLabel').textContent = 'Przyjmij Towar na Magazyn';
            assignProductFormErrorEl.classList.add('d-none'); assignProductFormErrorEl.textContent = '';

            const productSelect = document.getElementById('assignProductId');
            const locationSelect = document.getElementById('assignLocationId');
            productSelect.innerHTML = '<option value="">Ładowanie produktów...</option>';
            locationSelect.innerHTML = '<option value="">Ładowanie lokalizacji...</option>';

            try {
                let productsToLoad = allProductsCache;
                if (!productsToLoad || productsToLoad.length === 0) {
                    productsToLoad = await fetchApi('produkty'); 
                    allProductsCache = Array.isArray(productsToLoad) ? productsToLoad : [];
                }
                productSelect.innerHTML = '<option value="">Wybierz produkt...</option>';
                if (Array.isArray(productsToLoad)) {
                     productsToLoad.sort((a,b)=>(a.name||'').localeCompare(b.name||'')).forEach(prod => {
                        productSelect.innerHTML += `<option value="${prod.id}">${prod.name} (ID: ${prod.id})</option>`;
                    });
                }
                
                if (allWarehouseLocationsCache.length === 0) { 
                    allWarehouseLocationsCache = await fetchApi('magazyn');
                }
                locationSelect.innerHTML = '<option value="">Wybierz pustą lokalizację...</option>';
                if (Array.isArray(allWarehouseLocationsCache)) {
                    const emptyLocations = allWarehouseLocationsCache.filter(loc => loc.status === 'empty');
                    if (emptyLocations.length === 0) {
                        locationSelect.innerHTML = '<option value="" disabled>Brak wolnych lokalizacji!</option>';
                    } else {
                        emptyLocations.sort((a,b)=>(a.id||'').localeCompare(b.id||'')).forEach(loc => {
                            locationSelect.innerHTML += `<option value="${loc.id}">${loc.id}</option>`;
                        });
                    }
                }
            } catch (error) {
                console.error("Błąd ładowania danych do formularza przyjęcia:", error);
                showToast(`Błąd ładowania danych: ${error.message}`, 'danger');
                productSelect.innerHTML = '<option value="">Błąd</option>';
                locationSelect.innerHTML = '<option value="">Błąd</option>';
            }
            if (assignProductModalInstance) assignProductModalInstance.show();
        }

        async function handleAssignProductToLocation() {
            const formData = new FormData(assignProductFormEl);
            const assignData = {
                product_id: parseInt(formData.get('product_id'), 10),
                location_id: formData.get('location_id'),
                quantity: parseInt(formData.get('quantity'), 10)
            };

            if (!assignData.product_id || !assignData.location_id || !assignData.quantity || assignData.quantity < 1) {
                assignProductFormErrorEl.textContent = 'Wszystkie pola są wymagane, a ilość musi być większa od zera.';
                assignProductFormErrorEl.classList.remove('d-none');
                return;
            }
            assignProductFormErrorEl.classList.add('d-none');
            saveAssignProductButton.disabled = true;

            try {
                const result = await fetchApi('magazyn/przypisz', { method: 'POST', body: JSON.stringify(assignData) }); 
                showToast(result.message || 'Produkt przypisany do lokalizacji.', 'success');
                if (assignProductModalInstance) assignProductModalInstance.hide();
                loadContent('magazyn', null, true); 
            } catch (error) {
                console.error('Błąd przypisywania produktu:', error);
                assignProductFormErrorEl.textContent = error.message;
                assignProductFormErrorEl.classList.remove('d-none');
                showToast(`Błąd przypisania: ${error.message}`, 'danger');
            } finally {
                saveAssignProductButton.disabled = false;
            }
        }
        if(saveAssignProductButton) saveAssignProductButton.addEventListener('click', handleAssignProductToLocation);

        // MAGAZYN - ZDEJMIJ
        window.handleRemoveProductFromLocation = async function(locationId) {
            const confirmed = await showConfirmationModal(`Czy na pewno chcesz zdjąć towar z lokalizacji ${locationId}?`);
            if (!confirmed) return;

            try {
                const result = await fetchApi(`magazyn/zdejmij/${encodeURIComponent(locationId)}`, { method: 'POST' }); 
                showToast(result.message || `Towar zdjęty z lokalizacji ${locationId}.`, 'success');
                loadContent('magazyn', null, true); 
            } catch (error) {
                console.error('Błąd zdejmowania towaru:', error);
                showToast(`Błąd zdejmowania towaru: ${error.message}`, 'danger');
            }
        }

        // MAGAZYN - PRZESUŃ
        window.openMoveProductModal = async function(sourceLocationId, productId, currentQuantity) {
            moveProductFormEl.reset();
            document.getElementById('moveProductModalLabel').textContent = 'Przesuń Towar';
            moveProductFormErrorEl.classList.add('d-none'); moveProductFormErrorEl.textContent = '';

            document.getElementById('moveSourceLocationId').value = sourceLocationId;
            document.getElementById('moveProductId').value = productId;
            document.getElementById('moveSourceLocationDisplay').value = sourceLocationId;
            document.getElementById('moveCurrentQuantityDisplay').value = currentQuantity;
            const moveQuantityInput = document.getElementById('moveQuantity');
            moveQuantityInput.max = currentQuantity;
            moveQuantityInput.value = 1;

            const product = allProductsCache.find(p => p.id === productId);
            document.getElementById('moveProductNameDisplay').value = product ? product.name : `Produkt ID: ${productId}`;

            const targetLocationSelect = document.getElementById('moveTargetLocationId');
            targetLocationSelect.innerHTML = '<option value="">Ładowanie dostępnych lokalizacji...</option>';

            try {
                 if (allWarehouseLocationsCache.length === 0) { 
                    allWarehouseLocationsCache = await fetchApi('magazyn');
                }
                targetLocationSelect.innerHTML = '<option value="">Wybierz lokalizację docelową...</option>';
                if (Array.isArray(allWarehouseLocationsCache)) {
                    const availableTargetLocations = allWarehouseLocationsCache.filter(loc =>
                        loc.id !== sourceLocationId &&
                        (loc.status === 'empty' || (loc.status === 'occupied' && loc.product_id === productId))
                    );
                    if (availableTargetLocations.length === 0) {
                        targetLocationSelect.innerHTML = '<option value="" disabled>Brak odpowiednich lokalizacji docelowych!</option>';
                    } else {
                        availableTargetLocations.sort((a,b)=>(a.id||'').localeCompare(b.id||'')).forEach(loc => {
                            let optionText = loc.id;
                            if (loc.status === 'occupied') {
                                optionText += ` (zawiera ten sam produkt, ilość: ${loc.quantity})`;
                            } else {
                                optionText += ` (pusta)`;
                            }
                            targetLocationSelect.innerHTML += `<option value="${loc.id}">${optionText}</option>`;
                        });
                    }
                }
            } catch (error) {
                console.error("Błąd ładowania lokalizacji docelowych:", error);
                showToast(`Błąd ładowania lokalizacji: ${error.message}`, 'danger');
                targetLocationSelect.innerHTML = '<option value="">Błąd</option>';
            }
            if (moveProductModalInstance) moveProductModalInstance.show();
        }

        async function handleMoveProduct() {
            const formData = new FormData(moveProductFormEl);
            const moveData = {
                source_location_id: formData.get('source_location_id'),
                target_location_id: formData.get('target_location_id'),
                product_id: parseInt(formData.get('product_id'), 10),
                quantity_to_move: parseInt(formData.get('quantity_to_move'), 10)
            };

            if (!moveData.source_location_id || !moveData.target_location_id || !moveData.product_id || !moveData.quantity_to_move || moveData.quantity_to_move < 1) {
                moveProductFormErrorEl.textContent = 'Wszystkie pola są wymagane, a ilość musi być dodatnia.';
                moveProductFormErrorEl.classList.remove('d-none'); return;
            }
            if (moveData.source_location_id === moveData.target_location_id) {
                moveProductFormErrorEl.textContent = 'Lokalizacja źródłowa i docelowa nie mogą być takie same.';
                moveProductFormErrorEl.classList.remove('d-none'); return;
            }
            const maxQuantity = parseInt(document.getElementById('moveCurrentQuantityDisplay').value, 10);
            if (moveData.quantity_to_move > maxQuantity) {
                moveProductFormErrorEl.textContent = `Nie można przenieść więcej niż ${maxQuantity} szt.`;
                moveProductFormErrorEl.classList.remove('d-none'); return;
            }

            moveProductFormErrorEl.classList.add('d-none');
            saveMoveProductButton.disabled = true;

            try {
                const result = await fetchApi('magazyn/przesun', { method: 'POST', body: JSON.stringify(moveData) }); 
                showToast(result.message || 'Produkt został pomyślnie przesunięty.', 'success');
                if (moveProductModalInstance) moveProductModalInstance.hide();
                loadContent('magazyn', null, true); 
            } catch (error) {
                console.error('Błąd przesuwania produktu:', error);
                moveProductFormErrorEl.textContent = error.message;
                moveProductFormErrorEl.classList.remove('d-none');
                showToast(`Błąd przesuwania: ${error.message}`, 'danger');
            } finally {
                saveMoveProductButton.disabled = false;
            }
        }
        if(saveMoveProductButton) saveMoveProductButton.addEventListener('click', handleMoveProduct);

        // PROFIL UŻYTKOWNIKA
        window.loadProfileView = async function(forceLoad = false) {
            appTitle.textContent = "Mój Profil";
            navLinks.forEach(link => link.classList.remove('active'));
            const settingsLink = document.querySelector(`.bottom-nav .nav-link[data-section="ustawienia"]`);
            if(settingsLink) settingsLink.classList.add('active');


            mainContent.innerHTML = `<div class="loading-placeholder"><div class="spinner-border text-primary"></div><p class="mt-2">Ładowanie profilu...</p></div>`;
            try {
                let userProfile;
                if (currentUserProfile && !forceLoad) {
                    userProfile = currentUserProfile;
                } else {
                    try {
                         userProfile = await fetchApi('profil'); 
                         currentUserProfile = userProfile; 
                    } catch (apiError) {
                        console.warn("API /profil nieosiągalne lub błąd, używam danych demonstracyjnych dla profilu.", apiError);
                        userProfile = {
                            "id": 1,
                            "username": "admin",
                            "tn_imie_nazwisko": "Paweł Plichta",
                            "email": "admin@eMercPL.pl",
                            "phone": "123-456-789",
                            "avatar": "user_1_1745024356.png"
                        };
                        currentUserProfile = userProfile;
                    }
                }
                mainContent.innerHTML = generujHtmlProfilu(userProfile);
            } catch (error) {
                console.error("Błąd ładowania profilu:", error);
                mainContent.innerHTML = `<div class="content-card"><div class="alert alert-danger">Nie udało się załadować profilu: ${error.message}</div></div>`;
            }
        }

        window.openProfileEditModal = function(user) {
            if (!user) {
                showToast("Brak danych profilu do edycji.", "warning");
                loadProfileView(true).then(() => { 
                    if(currentUserProfile) openProfileEditModal(currentUserProfile);
                });
                return;
            }
            profileEditFormEl.reset();
            profileEditFormErrorEl.classList.add('d-none').textContent = '';
            
            document.getElementById('profileName').value = user.tn_imie_nazwisko || '';
            document.getElementById('profileEmail').value = user.email || '';
            document.getElementById('profilePhone').value = user.phone || '';
            document.getElementById('profileAvatar').value = user.avatar || '';

            if (profileEditModalInstance) profileEditModalInstance.show();
        }

        async function handleSaveProfile() {
            const formData = new FormData(profileEditFormEl);
            const profileData = {
                tn_imie_nazwisko: formData.get('name'),
                email: formData.get('email'),
                phone: formData.get('phone'),
                avatar: formData.get('avatar')
            };
            if (!profileData.tn_imie_nazwisko || !profileData.email) {
                profileEditFormErrorEl.textContent = "Imię i Nazwisko oraz Email są wymagane.";
                profileEditFormErrorEl.classList.remove('d-none');
                return;
            }
            profileEditFormErrorEl.classList.add('d-none');
            saveProfileButton.disabled = true;

            try {
                const result = await fetchApi('profil', { method: 'PUT', body: JSON.stringify(profileData) }); 
                showToast(result.message || 'Profil zaktualizowany pomyślnie.', 'success');
                if (profileEditModalInstance) profileEditModalInstance.hide();
                currentUserProfile = result.user || result; 
                loadProfileView(true); 
            } catch (error) {
                console.error("Błąd zapisu profilu:", error);
                profileEditFormErrorEl.textContent = error.message;
                profileEditFormErrorEl.classList.remove('d-none');
                showToast(`Błąd zapisu profilu: ${error.message}`, 'danger');
            } finally {
                saveProfileButton.disabled = false;
            }
        }
        if(saveProfileButton) saveProfileButton.addEventListener('click', handleSaveProfile);

        // ZWROTY / REKLAMACJE
        window.openReturnComplaintModal = async function(orderId, productId, maxQuantity, productName) {
            if (!returnComplaintFormEl || !returnComplaintModalInstance) {
                console.error("Return/Complaint modal form or instance not found.");
                return;
            }
            returnComplaintFormEl.reset();
            if(returnComplaintFormErrorEl) returnComplaintFormErrorEl.classList.add('d-none').textContent = '';

            document.getElementById('returnComplaintOrderId').value = orderId;
            document.getElementById('returnComplaintProductId').value = productId;
            document.getElementById('returnComplaintDisplayOrderId').value = `Zamówienie #${orderId}`;
            document.getElementById('returnComplaintDisplayProduct').value = productName || `Produkt ID: ${productId}`;
            
            const quantityInput = document.getElementById('returnComplaintQuantity');
            quantityInput.value = maxQuantity > 0 ? 1 : 0;
            quantityInput.max = maxQuantity;
            quantityInput.min = maxQuantity > 0 ? 1 : 0;


            const locationSelect = document.getElementById('returnComplaintWarehouseLocationId');
            locationSelect.innerHTML = '<option value="">Wybierz lokalizację...</option>';
             try {
                if (allWarehouseLocationsCache.length === 0) {
                    allWarehouseLocationsCache = await fetchApi('magazyn');
                }
                if (Array.isArray(allWarehouseLocationsCache)) {
                    const suitableLocations = allWarehouseLocationsCache.filter(loc => 
                        loc.status === 'empty' || (loc.status === 'occupied' && loc.product_id === parseInt(productId)) 
                    );
                    if (suitableLocations.length > 0) {
                         suitableLocations.sort((a,b)=>(a.id||'').localeCompare(b.id||'')).forEach(loc => {
                            let optionText = loc.id;
                            if (loc.status === 'occupied') optionText += ` (zawiera ten sam produkt, ilość: ${loc.quantity})`;
                            else optionText += ` (pusta)`;
                            locationSelect.innerHTML += `<option value="${loc.id}">${optionText}</option>`;
                        });
                    } else {
                        locationSelect.innerHTML = '<option value="">Brak odpowiednich lokalizacji</option>';
                    }
                }
            } catch (error) {
                console.error("Błąd ładowania lokalizacji dla zwrotu:", error);
                locationSelect.innerHTML = '<option value="">Błąd ładowania lokalizacji</option>';
            }

            returnComplaintModalInstance.show();
        };

        async function handleSaveReturnComplaint() {
            if (!returnComplaintFormEl || !saveReturnComplaintButton || !returnComplaintFormErrorEl) {
                 console.error("Return/Complaint form elements not found for saving.");
                 return;
            }

            const formData = new FormData(returnComplaintFormEl);
            const returnData = {
                order_id: parseInt(formData.get('order_id'), 10),
                product_id: parseInt(formData.get('product_id'), 10),
                quantity: parseInt(formData.get('quantity'), 10),
                reason: formData.get('reason'),
                status: formData.get('type'), 
                warehouse_location_id: formData.get('warehouse_location_id') || null
            };

            if (!returnData.order_id || !returnData.product_id || !returnData.quantity || returnData.quantity < 1 || !returnData.reason || !returnData.status) {
                returnComplaintFormErrorEl.textContent = 'Wszystkie pola oznaczone * są wymagane, a ilość musi być dodatnia.';
                returnComplaintFormErrorEl.classList.remove('d-none');
                return;
            }
            returnComplaintFormErrorEl.classList.add('d-none');
            saveReturnComplaintButton.disabled = true;

            try {
                console.log("Wysyłanie danych zwrotu:", returnData);
                const returnResult = await fetchApi('zwroty', { method: 'POST', body: JSON.stringify(returnData) });
                showToast(returnResult.message || 'Zgłoszenie zapisane pomyślnie.', 'success');
                console.log("Wynik zapisu zwrotu:", returnResult);

                console.log("Aktualizacja statusu zamówienia:", returnData.order_id, "na:", returnData.status);
                await fetchApi(`zamowienia/${returnData.order_id}/zmien_status`, {
                    method: 'POST',
                    body: JSON.stringify({ nowy_status: returnData.status })
                });
                showToast(`Status zamówienia #${returnData.order_id} zaktualizowany na "${returnData.status}".`, 'info');
                
                if (returnComplaintModalInstance) returnComplaintModalInstance.hide();
                loadOrderDetails(returnData.order_id); 
                loadContent('zwroty_reklamacje', null, true); 

            } catch (error) {
                console.error('Błąd zapisu zgłoszenia zwrotu/reklamacji:', error);
                if(returnComplaintFormErrorEl) {
                    returnComplaintFormErrorEl.textContent = `Błąd: ${error.message}`;
                    returnComplaintFormErrorEl.classList.remove('d-none');
                }
                showToast(`Błąd zapisu zgłoszenia: ${error.message}`, 'danger');
            } finally {
                if(saveReturnComplaintButton) saveReturnComplaintButton.disabled = false;
            }
        }
        if(saveReturnComplaintButton) saveReturnComplaintButton.addEventListener('click', handleSaveReturnComplaint);


        async function loadContent(section, resourceId = null, forceLoad = false) {
            if (appContentWrapper.classList.contains('loaded')) {
                 mainContent.innerHTML = `<div class="loading-placeholder"><div class="spinner-border text-primary"></div><p class="mt-2">Ładowanie...</p></div>`;
            }
            
            navLinks.forEach(link => link.classList.remove('active'));
            const activeLink = document.querySelector(`.bottom-nav .nav-link[data-section="${section}"]`);

            if (activeLink) {
                activeLink.classList.add('active');
                appTitle.textContent = activeLink.querySelector('span').textContent;
            } else if (section === 'dashboard') {
                appTitle.textContent = 'Pulpit';
                const dashboardNavLink = document.querySelector('.bottom-nav .nav-link[data-section="dashboard"]');
                if (dashboardNavLink) dashboardNavLink.classList.add('active');
            } else {
                 appTitle.textContent = 'eMercPL';
            }

            let htmlGenerator = null;
            let dataToProcess = null;

            try {
                switch(section) {
                    case 'dashboard':
                        const summaryData = await fetchApi('dashboard/summary'); 
                        if (forceLoad || allProductsCache.length === 0) {
                             allProductsCache = await fetchApi('produkty'); 
                             if(Array.isArray(allProductsCache)) allProductsCache.sort((a, b) => (b.id || 0) - (a.id || 0));
                        }
                        mainContent.innerHTML = generujHtmlDashboard(summaryData, allProductsCache);
                        break;

                    case 'magazyn':
                        dataToProcess = (forceLoad || allWarehouseLocationsCache.length === 0) ? await fetchApi('magazyn') : allWarehouseLocationsCache;
                        allWarehouseLocationsCache = Array.isArray(dataToProcess) ? dataToProcess : [];
                        if (forceLoad || allProductsCache.length === 0) { 
                            allProductsCache = await fetchApi('produkty'); 
                        }
                        htmlGenerator = generujHtmlWidokuMagazynu;
                        break;

                    case 'produkty':
                        if (resourceId) {
                            dataToProcess = await fetchApi(`produkty/${resourceId}`); 
                            htmlGenerator = generujHtmlSzczegolowProduktu;
                        } else {
                            dataToProcess = await fetchApi('produkty', { method: 'GET', filters: currentProductFilters });
                            currentlyDisplayedProducts = Array.isArray(dataToProcess) ? dataToProcess : []; 
                            
                            if (Object.keys(currentProductFilters).length === 0 || (forceLoad && Object.keys(currentProductFilters).length === 0) ) {
                                if(Array.isArray(currentlyDisplayedProducts)) allProductsCache = [...currentlyDisplayedProducts].sort((a, b) => (b.id || 0) - (a.id || 0));
                            }
                            htmlGenerator = generujHtmlListyProduktow;
                        }
                        break;

                    case 'zamowienia':
                        if (resourceId) {
                            const orderDetails = await fetchApi(`zamowienia/${resourceId}`); 
                            let productForOrder = null;
                            if (orderDetails && orderDetails.product_id) {
                                if (allProductsCache.length === 0 && !forceLoad) allProductsCache = await fetchApi('produkty'); 
                                productForOrder = allProductsCache.find(p => p.id === orderDetails.product_id);
                                if (!productForOrder || forceLoad) { 
                                    try { productForOrder = await fetchApi(`produkty/${orderDetails.product_id}`); } 
                                    catch(e) { console.warn("Nie udało się dociągnąć produktu dla zamówienia:", e); productForOrder = { error: e.message};}
                                }
                            }
                            mainContent.innerHTML = generujHtmlSzczegolowZamowienia(orderDetails, productForOrder);
                             window.scrollTo(0,0); return;
                        } else {
                            if (forceLoad || allOrdersCache.length === 0) {
                                allOrdersCache = await fetchApi('zamowienia'); 
                            }
                            dataToProcess = allOrdersCache;
                            htmlGenerator = (data) => {
                                let html = `<div class="content-card">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h2 class="h5 mb-0">Zamówienia</h2>
                                                    <button class="btn btn-sm btn-success" onclick="openAddOrderModal()"><i class="bi bi-plus-lg"></i> Dodaj</button>
                                                </div>
                                                <input type="text" id="orderSearchInput" class="form-control form-control-sm" placeholder="Szukaj (ID, kupujący, status)...">
                                                <div id="orderListContainer" class="mt-2">${generujHtmlListyZamowien(data)}</div>
                                            </div>`;
                                return html;
                            };
                        }
                        break;
                    case 'zwroty_reklamacje':
                        if (forceLoad || allOrdersCache.length === 0) { 
                           allOrdersCache = await fetchApi('zamowienia'); 
                        }
                        dataToProcess = allOrdersCache; 
                        htmlGenerator = generujHtmlListyZwrotowReklamacji;
                        break;

                    case 'ustawienia':
                        mainContent.innerHTML = generujHtmlUstawienia();
                        const darkModeSwitch = document.getElementById('darkModeSwitch');
                        if (darkModeSwitch) darkModeSwitch.checked = document.body.classList.contains('dark-mode');
                        break; 

                    default:
                        mainContent.innerHTML = `<div class="alert alert-warning">Nieznana sekcja: ${section}</div>`;
                         window.scrollTo(0,0); return;
                }

                if (htmlGenerator) {
                    mainContent.innerHTML = htmlGenerator(section === 'produkty' && !resourceId ? currentlyDisplayedProducts : dataToProcess);
                }
                
                if (section === 'produkty' && !resourceId) {
                    populateCategoryFilter(allProductsCache.length > 0 ? allProductsCache : currentlyDisplayedProducts); 
                    
                    const searchInputEl = document.getElementById('productSearchInput');
                    const categoryFilterEl = document.getElementById('productCategoryFilter');
                    const inStockFilterEl = document.getElementById('productInStockFilter');
                    const brandFilterEl = document.getElementById('productBrandFilter');
                    const modelFilterEl = document.getElementById('productModelFilter');

                    if(searchInputEl) searchInputEl.value = currentProductFilters.name || '';
                    if(categoryFilterEl) categoryFilterEl.value = currentProductFilters.category || '';
                    if(inStockFilterEl) inStockFilterEl.checked = currentProductFilters.in_stock === 'true';
                    if(brandFilterEl) brandFilterEl.value = currentProductFilters.marka || '';
                    if(modelFilterEl) modelFilterEl.value = currentProductFilters.model || '';

                    if(searchInputEl) searchInputEl.addEventListener('keyup', filterProductsClientSide); 
                    if(categoryFilterEl) categoryFilterEl.addEventListener('change', filterProductsClientSide);
                    if(inStockFilterEl) inStockFilterEl.addEventListener('change', filterProductsClientSide);
                    
                    const applyApiBtn = document.getElementById('applyApiFiltersButton');
                    const resetApiBtn = document.getElementById('resetApiFiltersButton');
                    if(applyApiBtn) applyApiBtn.addEventListener('click', applyApiProductFilters);
                    if(resetApiBtn) resetApiBtn.addEventListener('click', resetApiProductFilters);

                    filterProductsClientSide(); 
                }


            } catch (error) {
                console.error(`Błąd ładowania sekcji '${section}':`, error);
                mainContent.innerHTML = `<div class="content-card"><div class="alert alert-danger">Błąd ładowania sekcji '${section}' ${resourceId?'#'+resourceId:''}.<br><small>${error.message||''}</small></div></div>`;
                 if (error.message.includes("autoryzacji") || error.message.includes("401")) {
                    handleLogout();
                }
            } finally {
                 window.scrollTo(0,0);
            }
        }

        window.loadProductDetails = function(productId) { 
            window.location.hash = `produkty/${productId}`;
        }
        window.loadOrderDetails = function(orderId) { 
            window.location.hash = `zamowienia/${orderId}`;
        }

        navLinks.forEach(link => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                const section = link.getAttribute('data-section');
                if (section) {
                     window.location.hash = section; 
                }
            });
        });

        function loadInitialSection() {
            const hash = window.location.hash.substring(1);
            let section = defaultSection;
            let resourceId = null;

            if (hash) {
                const parts = hash.split('/');
                section = parts[0] || defaultSection; 
                if (parts.length > 1 && parts[1]) { 
                    resourceId = parts[1];
                }
            }
            
            console.log(`Ładowanie sekcji: ${section}, ID zasobu: ${resourceId}`);
            if (section === 'profil') { 
                 loadProfileView(true);
            } else {
                loadContent(section, resourceId, true);
            }
            setTimeout(hideLoader, 2800); 
        }
        
        window.addEventListener('hashchange', (event) => {
            const newHash = window.location.hash.substring(1);
            const oldHash = event.oldURL ? new URL(event.oldURL).hash.substring(1) : "";
            
            if (newHash !== oldHash) { 
                 loadInitialSection();
            }
        });

        document.body.addEventListener('keyup', (event) => {
            const targetId = event.target.id;
            if (targetId === 'orderSearchInput' || targetId === 'returnsSearchInput') {
                filterOrders(); 
            } else if (targetId === 'warehouseSearchInput') {
                filterWarehouseLocations();
            }
        });

        loadInitialSection();

    });
    </script>
</body>
</html>
