<?php
// templates/pages/tn_product_preview.php
/**
 * Szablon podglądu szczegółów produktu.
 * Wersja: 2.7 (Dopasowanie użycia kolorów Bootstrap do motywu)
 *
 * Oczekuje zmiennych z index.php:
 * @var array|null $tn_produkt_podgladu Dane produktu do wyświetlenia (lub null).
 * @var array $tn_ustawienia_globalne Załadowane ustawienia globalne.
 * @var array $tn_stan_magazynu Aktualny stan magazynu (do wyświetlania lokalizacji).
 * @var array $tn_produkty Tablica wszystkich produktów (dla podobnych produktów).
 * @var array $tn_zamowienia Tablica wszystkich zamówień (dla historii zamówień).
 * @var string $tn_token_csrf Aktualny token CSRF (jeśli używany w helperach).
 * @var array $tn_mapa_produktow Mapa ID => Dane produktu (dodano dla wydajności - opcjonalne, zależne od implementacji index.php).
 *
 * Zakłada dostępność funkcji pomocniczych:
 * tn_generuj_url(), tn_pobierz_sciezke_obrazka(), tn_generuj_link_akcji_get(), tn_format_date(), tn_ustaw_komunikat_flash()
 *
 * Używa klas i zmiennych CSS Bootstrapa do automatycznego dopasowania kolorów do motywu (jasny/ciemny).
 */

// --- Wczytanie zależności i sprawdzenie danych wejściowych ---
// Zapobiegaj bezpośredniemu dostępowi do pliku szablonu
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403); // Ustaw kod błędu 403 Forbidden
    die('Access Denied');
}

// Definiuj stałe ścieżek jako fallback, jeśli nie zdefiniowano ich wcześniej
defined('TN_SCIEZKA_SRC') or define('TN_SCIEZKA_SRC', __DIR__ . '/../../src/'); // Definiuj jako fallback, jeśli nie zdefiniowano wcześniej
defined('TN_SCIEZKA_TEMPLATEK') or define('TN_SCIEZKA_TEMPLATEK', __DIR__ . '/'); // Definiuj jako fallback

// Załaduj helpery, jeśli nie są już dostępne (powinny być z index.php)
// W prawdziwej aplikacji, te require_once powinny być w index.php
// require_once TN_SCIEZKA_SRC . 'functions/tn_url_helpers.php';
// require_once TN_SCIEZKA_SRC . 'functions/tn_image_helpers.php';
// require_once TN_SCIEZKA_SRC . 'functions/tn_security_helpers.php';
// require_once TN_SCIEZKA_SRC . 'functions/tn_flash_messages.php'; // Jeśli używasz flash messages

// Sprawdź, czy dane produktu zostały przekazane i są poprawne
if (empty($tn_produkt_podgladu) || !is_array($tn_produkt_podgladu) || !isset($tn_produkt_podgladu['id'])) {
    // Użyj bezpieczniejszego przekierowania
    $redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('products') : '/produkty';
    if (function_exists('tn_ustaw_komunikat_flash')) {
        tn_ustaw_komunikat_flash('Nie znaleziono produktu do wyświetlenia lub dane są nieprawidłowe.', 'warning');
    } else {
        // Fallback komunikat, jeśli flash messages nie są dostępne
        echo "<p class='alert alert-warning'>Nie znaleziono produktu do wyświetlenia lub dane są nieprawidłowe.</p>";
    }

    // Przekierowanie po krótkim opóźnieniu, aby użytkownik mógł zobaczyć komunikat fallback
    echo "<script>setTimeout(function(){ window.location.href = '" . addslashes($redirect_url) . "'; }, 3000);</script>";
    exit; // Zakończ wykonywanie skryptu
}

// --- Przygotowanie danych do widoku ---

$productData = $tn_produkt_podgladu; // Użyj krótszej, bardziej opisowej nazwy zmiennej

// Podstawowe dane produktu (zabezpieczone przed XSS)
$productId = intval($productData['id']);
// Używaj htmlspecialchars wszędzie, gdzie dane pochodzą z zewnętrznych źródeł (np. bazy danych)
$productName = htmlspecialchars($productData['name'] ?? 'Brak nazwy', ENT_QUOTES, 'UTF-8');
$producent = htmlspecialchars($productData['producent'] ?? '-', ENT_QUOTES, 'UTF-8');
$category = htmlspecialchars($productData['category'] ?? '-', ENT_QUOTES, 'UTF-8');
$catalogNumber = htmlspecialchars($productData['tn_numer_katalogowy'] ?? '-', ENT_QUOTES, 'UTF-8');
$unit = htmlspecialchars($productData['tn_jednostka_miary'] ?? 'szt.', ENT_QUOTES, 'UTF-8');
// Opis i specyfikacja - nl2br dodane później w widoku, htmlspecialchars teraz
$descriptionRaw = $productData['desc'] ?? '';
$specificationRaw = $productData['spec'] ?? '';
$paramsRaw = $productData['params'] ?? '';
$vehicleInfoRaw = $productData['vehicle'] ?? '';
$basePrice = floatval($productData['price'] ?? 0);
$stock = intval($productData['stock'] ?? 0);
$warehouseLocationRaw = htmlspecialchars($productData['warehouse'] ?? '', ENT_QUOTES, 'UTF-8'); // Zabezpiecz lokalizację magazynową
$shippingCost = floatval($productData['shipping'] ?? 0);

// Ustawienia globalne (zabezpieczone przed XSS)
$currency = htmlspecialchars($tn_ustawienia_globalne['waluta'] ?? 'PLN', ENT_QUOTES, 'UTF-8');
$lowStockThreshold = intval($tn_ustawienia_globalne['tn_prog_niskiego_stanu'] ?? 5);
// Użyj domyślnych formatów, jeśli ustawienia globalne ich nie dostarczają
$dateTimeFormat = ($tn_ustawienia_globalne['tn_format_daty'] ?? 'd.m.Y') . ' ' . ($tn_ustawienia_globalne['tn_format_czasu'] ?? 'H:i');
$defaultDiscountPercent = floatval($tn_ustawienia_globalne['domyslny_procent_rabatu'] ?? 0);
$priceAfterDiscount = $basePrice * (1 - ($defaultDiscountPercent / 100));


// --- Parsowanie danych "Pasuje do" (Ulepszony Regex i obsługa) ---
$parsedVehicleData = [];
// Sprawdź, czy dane istnieją przed próbą parsowania
if (!empty($vehicleInfoRaw)) {
    $lines = explode("\n", trim($vehicleInfoRaw));
    $currentMakeModel = null;

    // Ulepszony Regex do parsowania linii wersji pojazdu
    // Dopasowuje: Nazwa (Kod), Poj, kW, KM, DataStart - DataEnd(opcjonalnie)
    // Ulepszono, aby lepiej radzić sobie z różnymi spacjami, opcjonalnymi polami
    // i pozwolić na większą elastyczność w nazwach/kodach.
    // Grupy: 1: Nazwa, 2: Kod (w nawiasie), 3: Pojemność, 4: kW, 5: KM, 6: DataStart, 7: DataEnd lub puste
    // Dodano opcjonalne spacje w nawiasach i po przecinkach.
    $versionRegex = '/^\s*(.+?)\s+\(([^)]+?)\)\s*,\s*(\d*)\s*,\s*(\d*)\s*,\s*(\d*)\s*,\s*(\d{4}\.\d{2})\s*-\s*(.*?)\s*$/';

    foreach ($lines as $line) {
        $trimmedLine = trim($line);
        if (empty($trimmedLine)) continue; // Pomiń puste linie

        // Sprawdź, czy to linia z marką/modelem (kończy się na ':' i nie jest wcięta)
        // Lepsze sprawdzenie wcięcia - linia nie zaczyna się od spacji/tabulatora
        if (str_ends_with($trimmedLine, ':') && !preg_match('/^\s/', $line)) {
            // Usuń dwukropek i zabezpiecz przed XSS
            $currentMakeModel = htmlspecialchars(trim(substr($trimmedLine, 0, -1)), ENT_QUOTES, 'UTF-8');
            $parsedVehicleData[$currentMakeModel] = []; // Inicjalizuj tablicę dla tego modelu
        }
        // Sprawdź, czy to linia z wersją (ma wcięcie i pasuje do regex)
        // Sprawdzenie wcięcia - linia zaczyna się od co najmniej jednej spacji lub tabulatora
        elseif ($currentMakeModel !== null && preg_match('/^\s+/', $line) && preg_match($versionRegex, $line, $matches)) {
            // Zabezpiecz i przypisz sparsowane dane
            // Zapewnij, że wszystkie przechwycone grupy są sanityzowane
            $parsedVehicleData[$currentMakeModel][] = [
                'name' => htmlspecialchars(trim($matches[1] ?? ''), ENT_QUOTES, 'UTF-8'),
                'code' => htmlspecialchars(trim($matches[2] ?? ''), ENT_QUOTES, 'UTF-8'),
                'capacity' => htmlspecialchars(trim($matches[3] ?? ''), ENT_QUOTES, 'UTF-8') ?: '-', // Użyj '-' jeśli puste lub brak
                'kw' => htmlspecialchars(trim($matches[4] ?? ''), ENT_QUOTES, 'UTF-8') ?: '-',
                'hp' => htmlspecialchars(trim($matches[5] ?? ''), ENT_QUOTES, 'UTF-8') ?: '-',
                'year_start' => htmlspecialchars(trim($matches[6] ?? ''), ENT_QUOTES, 'UTF-8'),
                'year_end' => htmlspecialchars(trim($matches[7] ?? ''), ENT_QUOTES, 'UTF-8') ?: '...', // Jeśli puste, to '...'
            ];
        }
        // Logowanie linii, które nie pasują do wzorca (opcjonalne, przydatne do debugowania formatu danych)
        // elseif ($currentMakeModel !== null && !empty($trimmedLine)) {
        //     error_log("Linia nie pasuje do wzorca Pasuje do dla produktu ID " . $productId . ": " . $line);
        // }
    }
}
$parsedVehicleDataExists = !empty($parsedVehicleData); // Sprawdź czy sparsowane dane istnieją
// --- Koniec parsowania "Pasuje do" ---


// Galeria zdjęć
$mainImageName = $productData['image'] ?? null;
// Użyj helpera i upewnij się, że ścieżka jest bezpieczna dla atrybutu src
$mainImagePath = htmlspecialchars(tn_pobierz_sciezke_obrazka($mainImageName), ENT_QUOTES, 'UTF-8');
$galleryImages = [];
// Dodaj główne zdjęcie jako pierwsze w galerii
$galleryImages[] = ['path' => $mainImagePath, 'alt' => $productName . ' - Zdjęcie główne'];

// Dodaj zdjęcia z galerii, pomijając główne zdjęcie (jeśli jest duplikowane)
if (!empty($productData['gallery']) && is_array($productData['gallery'])) {
    foreach ($productData['gallery'] as $index => $imageName) {
        // Sprawdź, czy nazwa obrazka nie jest pusta i czy nie jest to to samo co główne zdjęcie
        if (!empty($imageName) && $imageName !== $mainImageName) {
            $path = htmlspecialchars(tn_pobierz_sciezke_obrazka($imageName), ENT_QUOTES, 'UTF-8');
            $galleryImages[] = ['path' => $path, 'alt' => $productName . ' - Zdjęcie ' . ($index + 2)]; // Numeracja od 2
        }
    }
}

// Lokalizacje w magazynie
$productLocations = [];
if (!empty($tn_stan_magazynu) && is_array($tn_stan_magazynu)) {
    foreach ($tn_stan_magazynu as $loc) {
        // Sprawdź poprawność struktury elementu i zgodność ID produktu
        if (is_array($loc) && ($loc['product_id'] ?? null) == $productId && isset($loc['id'])) {
            $locationIdHtml = htmlspecialchars($loc['id'], ENT_QUOTES, 'UTF-8');
            // Użyj helpera do generowania linku i zabezpiecz go
            $locationLink = htmlspecialchars(tn_generuj_url('warehouse_view', ['search' => $loc['id']]), ENT_QUOTES, 'UTF-8');
            $productLocations[] = [
                'id' => $locationIdHtml,
                'quantity' => intval($loc['quantity'] ?? 0),
                'link' => $locationLink
            ];
        }
    }
    // Sortuj naturalnie po ID lokalizacji
    usort($productLocations, fn($a, $b) => strnatcmp($a['id'], $b['id']));
}

// Historia zamówień
$orderHistory = [];
if (!empty($tn_zamowienia) && is_array($tn_zamowienia)) {
    // Filtruj zamówienia tylko dla bieżącego produktu
    $productOrders = array_filter($tn_zamowienia, fn($o) => is_array($o) && ($o['product_id'] ?? null) == $productId);
    // Sortuj zamówienia malejąco po dacie zamówienia
    usort($productOrders, fn($a, $b) => ($b['order_date'] ?? 0) <=> ($a['order_date'] ?? 0));
    // Ogranicz do 10 najnowszych
    $orderHistory = array_slice($productOrders, 0, 10);
}

// Podobne produkty
$similarProducts = [];
// Sprawdź, czy kategoria istnieje i czy są produkty do filtrowania
if ($category && $category !== '-' && !empty($tn_produkty) && is_array($tn_produkty)) {
    // Filtruj produkty z tej samej kategorii, pomijając bieżący produkt i te bez stanu magazynowego
    $filtered = array_filter($tn_produkty, fn($p) =>
        is_array($p) &&
        isset($p['id'], $p['category']) &&
        strcasecmp($p['category'], $category) === 0 && // Porównanie kategorii bez względu na wielkość liter
        intval($p['id']) !== $productId && // Upewnij się, że ID jest intem przed porównaniem
        intval($p['stock'] ?? 0) > 0
    );
    // Weź maksymalnie 5 podobnych produktów
    $similarProducts = array_slice(array_values($filtered), 0, 5);
}

// Formatowanie parametrów (z ':')
$formattedParams = [];
if (!empty($paramsRaw)) {
    $lines = explode("\n", trim($paramsRaw));
    foreach ($lines as $line) {
        $parts = explode(':', $line, 2);
        // Sprawdź, czy są dwie części i obie nie są puste po przycięciu
        if (count($parts) === 2 && !empty(trim($parts[0])) && !empty(trim($parts[1]))) {
            // Zabezpiecz obie części przed XSS
            $formattedParams[htmlspecialchars(trim($parts[0]), ENT_QUOTES, 'UTF-8')] = htmlspecialchars(trim($parts[1]), ENT_QUOTES, 'UTF-8');
        }
    }
}

// Przygotowanie linków (zabezpieczone przed XSS)
$linkToList = htmlspecialchars(tn_generuj_url('products'), ENT_QUOTES, 'UTF-8');
// Użyj helpera do generowania linku akcji GET (zakładamy, że helper dba o CSRF)
$linkToDelete = htmlspecialchars(tn_generuj_link_akcji_get('delete_product', ['id' => $productId]), ENT_QUOTES, 'UTF-8');

// Domyślna aktywna zakładka (jeśli potrzebne, np. po przekierowaniu z błędem parsowania vehicle)
// Ustal domyślną zakładkę: jeśli Opis jest pusty, domyślnie Parametry, chyba że jest parametr GET 'tab'
$defaultActiveTab = $_GET['tab'] ?? (!empty($descriptionRaw) ? 'tn-opis' : 'tn-param');
// Upewnij się, że domyślna zakładka faktycznie istnieje w tablicach danych, jeśli nie jest 'opis'
// Przeszukaj dostępne zakładki w kolejności preferencji
$availableTabs = [];
if (!empty($descriptionRaw)) $availableTabs[] = 'tn-opis';
if (!empty($specificationRaw)) $availableTabs[] = 'tn-spec';
if (!empty($formattedParams)) $availableTabs[] = 'tn-param';
if (!empty($vehicleInfoRaw)) $availableTabs[] = 'tn-vehicle';
if (!empty($productLocations)) $availableTabs[] = 'tn-lokalizacje';
if (!empty($orderHistory)) $availableTabs[] = 'tn-historia';


// Jeśli domyślna zakładka z GET jest dostępna, użyj jej
if (isset($_GET['tab']) && in_array($_GET['tab'], $availableTabs)) {
    $defaultActiveTab = $_GET['tab'];
} elseif (!empty($availableTabs)) {
    // Jeśli domyślna z GET nie ma, lub jej nie było, wybierz pierwszą dostępną
    $defaultActiveTab = $availableTabs[0];
} else {
    // Jeśli żadna zakładka nie ma danych, domyślnie pokaż Opis (nawet pusty)
    $defaultActiveTab = 'tn-opis';
}


// Link do strony zarządzania pojazdami (przykład)


?>
<div class="container-fluid px-lg-4 py-4">
    <div class="card shadow-sm tn-podglad-produktu mb-4" id="tnMainContent">
        <div class="card-header bg-light py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <?php // Breadcrumbs (okruszki) dla lepszej nawigacji ?>
                <nav aria-label="breadcrumb" style="--bs-breadcrumb-divider: '>';">
                    <ol class="breadcrumb small mb-0">
                        <li class="breadcrumb-item"><a href="<?php echo $linkToList; ?>" class="text-decoration-none">Produkty</a></li>
                        <?php if ($category && $category !== '-'): ?>
                            <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars(tn_generuj_url('products', ['category' => $category]), ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none"><?php echo $category; ?></a></li>
                        <?php endif; ?>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars(mb_strimwidth($productName, 0, 50, '...'), ENT_QUOTES, 'UTF-8'); ?></li>
                    </ol>
                </nav>
                <div class="d-flex gap-2 flex-wrap">
                    <?php // Przycisk Edycji używa JS do wypełnienia modala ?>
                    <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edytuj produkt"
                            onclick='if(typeof tnApp?.populateEditForm === "function") tnApp.populateEditForm(<?php echo json_encode($productData, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>); else alert("Funkcja edycji nie jest dostępna.");'>
                        <i class="bi bi-pencil me-1"></i> Edytuj
                    </button>
                    <?php // Przycisk Usuń z potwierdzeniem ?>
                    <a href="<?php echo $linkToDelete; ?>" class="btn btn-outline-danger btn-sm" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Usuń produkt"
                       onclick="return confirm('Czy na pewno usunąć produkt \'<?php echo htmlspecialchars(addslashes($productName), ENT_QUOTES, 'UTF-8'); ?>\'? Tej operacji nie można cofnąć.');">
                        <i class="bi bi-trash me-1"></i> Usuń
                    </a>
                    <a href="<?php echo $linkToList; ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Wróć do listy
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <?php // --- Kolumna Zdjęć i Kodu Kreskowego --- ?>
                <div class="col-lg-5 text-center">
                    <?php // Link do modala powiększenia obrazka ?>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#tnImageZoomModal"
                       data-image-src="<?php echo $galleryImages[0]['path'] ?? ''; ?>"
                       data-image-title="<?php echo htmlspecialchars($galleryImages[0]['alt'] ?? $productName, ENT_QUOTES, 'UTF-8'); ?>">
                        <img src="<?php echo $galleryImages[0]['path'] ?? ''; ?>"
                             alt="<?php echo htmlspecialchars($galleryImages[0]['alt'] ?? $productName, ENT_QUOTES, 'UTF-8'); ?>"
                             class="img-fluid rounded border p-1 mb-3 tn-obrazek-podgladu bg-light shadow-sm"
                             id="tnMainProductImage" style="max-height: 350px; width: auto; cursor: zoom-in;" title="Kliknij, aby powiększyć" loading="lazy">
                    </a>
                    <?php // Galeria miniaturek ?>
                    <?php if (count($galleryImages) > 1): ?>
                    <div class="d-flex justify-content-center flex-wrap gap-2 mb-3">
                        <?php foreach ($galleryImages as $index => $zdjecie): ?>
                        <img src="<?php echo $zdjecie['path']; ?>" alt="<?php echo $zdjecie['alt']; ?>"
                             class="rounded tn-gallery-thumbnail <?php echo ($index === 0) ? 'active' : ''; ?>"
                             onclick="changeMainImage(this, '<?php echo addslashes($zdjecie['path']); ?>', '<?php echo addslashes($zdjecie['alt']); ?>')"
                             style="width: 60px; height: 60px; object-fit: contain; cursor: pointer; border: 2px solid transparent; transition: border-color 0.2s ease, transform 0.2s ease;"
                             loading="lazy">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                     <?php // Kod kreskowy ?>
                    <?php if (!empty($warehouseLocationRaw)): ?>
                    <div class="mt-3 text-center">
                        <?php // Link do otwarcia modala kodu kreskowego ?>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#tnBarcodeZoomModal" data-barcode-value="<?php echo htmlspecialchars($warehouseLocationRaw, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php
                                // Parametry dla generatora kodu kreskowego
                                $barcode_params = http_build_query([
                                    'f' => 'svg',
                                    's' => 'ean-128',
                                    'd' => $warehouseLocationRaw
                                ]);
                                // Upewnij się, że ścieżka do skryptu jest poprawna
                                $barcode_generator_url = '/kod_kreskowy.php?' . $barcode_params;
                            ?>
                            <img src="<?php echo htmlspecialchars($barcode_generator_url, ENT_QUOTES, 'UTF-8'); ?>"
                                 alt="Numer magazynowy: <?php echo htmlspecialchars($warehouseLocationRaw, ENT_QUOTES, 'UTF-8'); ?>"
                                 style="max-width: 100%; height: auto; max-height: 150px;"
                                 class="mb-2 d-block mx-auto">
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <?php // --- Kolumna Informacji o Produkcie (Uporządkowana i ulepszona) --- ?>
                <div class="col-lg-7">
                    <h3 class="h4 mb-2"><?php echo $productName; ?></h3>
                   

                    <?php // Kluczowe informacje w ulepszonej siatce ?>
                    <div class="row mb-3 g-2 small">
                        <div class="col-md-6">
                            <div class="p-2 rounded h-100">
                                <div class="text-muted text-uppercase" style="font-size: 0.7em;"><i class="bi bi-upc me-1 opacity-75"></i>Numer katalogowy części:</div>
                                <strong class="font-monospace"><?php echo $catalogNumber !== '-' ? $catalogNumber : '<span class="text-muted">-</span>'; ?></strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2  rounded h-100">
                                <div class="text-muted text-uppercase" style="font-size: 0.7em;"><i class="bi bi-key-fill me-1 opacity-55"></i>ID:</div>
                                <strong><?php echo $productId; ?></strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 rounded h-100">
                                <div class="text-muted text-uppercase" style="font-size: 0.7em;"><i class="bi bi-rulers me-1 opacity-75"></i>Jednostka:</div>
                                <strong><?php echo $unit !== '-' ? $unit : '<span class="text-muted">-</span>'; ?></strong>
                            </div>
                        </div>
                         <div class="col-md-6">
                            <div class="p-2 rounded h-100">
                                <div class="text-muted text-uppercase" style="font-size: 0.7em;"><i class="bi bi-box-seam me-1 opacity-75"></i>Numer magazynowy:</div>
                                <strong><?php echo !empty($warehouseLocationRaw) ? $warehouseLocationRaw : '<span class="text-muted">-</span>'; ?></strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 rounded h-100">
                                <div class="text-muted text-uppercase" style="font-size: 0.7em;"><i class="bi bi-building me-1 opacity-75"></i>Producent części:</div>
                                <strong><?php echo $producent !== '-' ? $producent : '<span class="text-muted">-</span>'; ?></strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 rounded h-100">
                                <div class="text-muted text-uppercase" style="font-size: 0.7em;"><i class="bi bi-tag me-1 opacity-75"></i>Kategoria:</div>
                                <strong><?php echo $category !== '-' ? $category : '<span class="text-muted">-</span>'; ?></strong>
                            </div>
                        </div>
                    </div>

                    <?php // Cena i Koszt Wysyłki ?>
                    <div class="mb-3 pt-2 border-top">
                        <span class="fs-2 fw-bold text-primary me-2"><?php echo number_format($basePrice, 2, ',', ' '); ?> <?php echo $currency; ?></span>
                         <?php if ($basePrice > 0 && $defaultDiscountPercent > 0): ?>
                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle ms-1" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo $defaultDiscountPercent; ?>% rabatu">
                                <i class="bi bi-percent me-1"></i>Po rabacie: <?php echo number_format($priceAfterDiscount, 2, ',', ' '); ?> <?php echo $currency; ?>
                            </span>
                         <?php endif; ?>
                    </div>
                    <div class="mb-3 small">
                        <i class="bi bi-truck me-1 opacity-75"></i><strong>Koszt dostawy:</strong>
                        <span class="ms-1 fw-medium"><?php echo number_format($shippingCost, 2, ',', ' '); ?> <?php echo $currency; ?></span>
                    </div>

                    <?php // Stan Magazynowy ?>
                    <div class="mb-3 pt-2 border-top">
                         <?php
                            $stockAlertClass = 'info'; $stockIcon = 'bi-info-circle-fill'; $stockText = "Dostępność: <strong>{$stock}</strong> {$unit}";
                            if ($stock > $lowStockThreshold) { $stockAlertClass = 'success'; $stockIcon = 'bi-check-circle-fill'; $stockText = "Dostępna ilość: <strong>{$stock}</strong> {$unit}"; }
                            elseif ($stock > 0) { $stockAlertClass = 'warning'; $stockIcon = 'bi-exclamation-triangle-fill'; $stockText = "Niski stan: <strong>{$stock}</strong> {$unit}"; $stockText .= " <small class='opacity-75'>(Próg: {$lowStockThreshold})</small>"; }
                            else { $stockAlertClass = 'danger'; $stockIcon = 'bi-x-octagon-fill'; $stockText = "<strong>Produkt niedostępny</strong> (0 {$unit})"; }
                         ?>
                         <div class="alert alert-<?php echo $stockAlertClass; ?> d-flex align-items-center py-2 px-3 small mb-1 shadow-sm" role="alert">
                             <i class="bi <?php echo $stockIcon; ?> me-2 fs-6 flex-shrink-0"></i>
                             <div class="flex-grow-1"><?php echo $stockText; // HTML jest bezpieczny, bo generowany przez nas ?></div>
                         </div>
                    </div>

                     <?php // Przycisk szybkiego dodania zamówienia (jeśli JS jest dostępne) ?>
                     <div class="mt-3 pt-2 border-top d-grid gap-2">
                         <button type="button" class="btn btn-success btn-sm" onclick="if(typeof tnApp?.tnOtworzModalZamowienia === 'function') tnApp.tnOtworzModalZamowienia(<?php echo $productId; ?>); else alert('Funkcja dodawania zamówienia nie jest dostępna.');" <?php echo $stock <= 0 ? 'disabled' : ''; ?>>
                              <i class="bi bi-cart-plus me-1"></i> Dodaj zamówienie dla tej części
                         </button>
                     </div>

                </div>
            </div>

             <?php // --- Zakładki z Dodatkowymi Informacjami --- ?>
             <div class="mt-4 pt-3 border-top">
                 <ul class="nav nav-tabs mb-0 nav-fill small" id="tnProductTab" role="tablist">
                     <?php if (!empty($descriptionRaw)): ?><li class="nav-item" role="presentation"><button class="nav-link <?php echo $defaultActiveTab === 'tn-opis' ? 'active' : ''; ?> py-2" id="tn-opis-tab" data-bs-toggle="tab" data-bs-target="#tn-opis-pane" type="button" role="tab" aria-controls="tn-opis-pane" aria-selected="<?php echo $defaultActiveTab === 'tn-opis' ? 'true' : 'false'; ?>"><i class="bi bi-card-text me-1"></i>Opis</button></li><?php endif; ?>
                     <?php if (!empty($specificationRaw)): ?><li class="nav-item" role="presentation"><button class="nav-link <?php echo $defaultActiveTab === 'tn-spec' ? 'active' : ''; ?> py-2" id="tn-spec-tab" data-bs-toggle="tab" data-bs-target="#tn-spec-pane" type="button" role="tab" aria-controls="tn-spec-pane" aria-selected="<?php echo $defaultActiveTab === 'tn-spec' ? 'true' : 'false'; ?>"><i class="bi bi-list-ul me-1"></i>Specyfikacja</button></li><?php endif; ?>
                     <?php if (!empty($formattedParams)): ?><li class="nav-item" role="presentation"><button class="nav-link <?php echo $defaultActiveTab === 'tn-param' ? 'active' : ''; ?> py-2" id="tn-param-tab" data-bs-toggle="tab" data-bs-target="#tn-param-pane" type="button" role="tab" aria-controls="tn-param-pane" aria-selected="<?php echo $defaultActiveTab === 'tn-param' ? 'true' : 'false'; ?>"><i class="bi bi-sliders me-1"></i>Parametry</button></li><?php endif; ?>
                     <?php if (!empty($vehicleInfoRaw)): ?><li class="nav-item" role="presentation"><button class="nav-link <?php echo $defaultActiveTab === 'tn-vehicle' ? 'active' : ''; ?> py-2" id="tn-vehicle-tab" data-bs-toggle="tab" data-bs-target="#tn-vehicle-pane" type="button" role="tab" aria-controls="tn-vehicle-pane" aria-selected="<?php echo $defaultActiveTab === 'tn-vehicle' ? 'true' : 'false'; ?>"><i class="bi bi-car-front-fill me-1"></i>Pasuje do</button></li><?php endif; ?>
                     <?php if (!empty($productLocations)): ?><li class="nav-item" role="presentation"><button class="nav-link <?php echo $defaultActiveTab === 'tn-lokalizacje' ? 'active' : ''; ?> py-2" id="tn-lokalizacje-tab" data-bs-toggle="tab" data-bs-target="#tn-lokalizacje-pane" type="button" role="tab" aria-controls="tn-lokalizacje-pane" aria-selected="<?php echo $defaultActiveTab === 'tn-lokalizacje' ? 'true' : 'false'; ?>"><i class="bi bi-geo-alt-fill me-1"></i>Lokalizacje <span class="badge bg-secondary ms-1 rounded-pill"><?php echo count($productLocations); ?></span></button></li><?php endif; ?>
                     <?php if (!empty($orderHistory)): ?>
                     <li class="nav-item" role="presentation"><button class="nav-link <?php echo $defaultActiveTab === 'tn-historia' ? 'active' : ''; ?> py-2" id="tn-historia-tab" data-bs-toggle="tab" data-bs-target="#tn-historia-pane" type="button" role="tab" aria-controls="tn-historia-pane" aria-selected="<?php echo $defaultActiveTab === 'tn-historia' ? 'true' : 'false'; ?>"><i class="bi bi-clock-history me-1"></i>Historia Zam.</button></li>
                     <?php endif; ?>
                 </ul>

                 <div class="tab-content border border-top-0 p-4 bg-body-tertiary rounded-bottom shadow-sm" id="tnProductTabContent">
                     <?php // Zakładka: Opis ?>
                     <?php if (!empty($descriptionRaw)): ?>
                     <div class="tab-pane fade <?php echo $defaultActiveTab === 'tn-opis' ? 'show active' : ''; ?>" id="tn-opis-pane" role="tabpanel" aria-labelledby="tn-opis-tab" tabindex="0">
                         <?php echo nl2br(htmlspecialchars($descriptionRaw, ENT_QUOTES, 'UTF-8')); ?>
                     </div>
                     <?php endif; ?>

                     <?php // Zakładka: Specyfikacja ?>
                     <?php if (!empty($specificationRaw)): ?>
                     <div class="tab-pane fade <?php echo $defaultActiveTab === 'tn-spec' ? 'show active' : ''; ?>" id="tn-spec-pane" role="tabpanel" aria-labelledby="tn-spec-tab" tabindex="0">
                         <h6 class="mb-3">Specyfikacja Techniczna</h6>
                         <?php echo nl2br(htmlspecialchars($specificationRaw, ENT_QUOTES, 'UTF-8')); ?>
                     </div>
                     <?php endif; ?>

                     <?php // Zakładka: Parametry ?>
                     <?php if (!empty($formattedParams)): ?>
                     <div class="tab-pane fade <?php echo $defaultActiveTab === 'tn-param' ? 'show active' : ''; ?>" id="tn-param-pane" role="tabpanel" aria-labelledby="tn-param-tab" tabindex="0">
                         <h6 class="mb-3">Parametry</h6>
                         <dl class="row small tn-def-list">
                             <?php foreach ($formattedParams as $key => $value): ?>
                                 <dt class="col-sm-5 col-md-4 col-lg-3"><?php echo $key; ?>:</dt>
                                 <dd class="col-sm-7 col-md-8 col-lg-9"><?php echo $value; ?></dd>
                             <?php endforeach; ?>
                         </dl>
                     </div>
                     <?php endif; ?>

                     <?php // Zakładka: Pasuje do (Ulepszony wygląd tabeli z akordeonem) ?>
                     <?php if (!empty($vehicleInfoRaw)): // Pokaż zakładkę jeśli są jakiekolwiek dane ?>
                     <div class="tab-pane fade <?php echo $defaultActiveTab === 'tn-vehicle' ? 'show active' : ''; ?>" id="tn-vehicle-pane" role="tabpanel" aria-labelledby="tn-vehicle-tab" tabindex="0">
                         <h6 class="mb-3">Pasuje do Pojazdów</h6>

                         <?php // Dodano przycisk do zarządzania pojazdami ?>
                         <div class="mb-3 text-end">
                            <a href="../../../produkty/<?php echo $productId; ?>/zarzadzaj-pojazdami" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-car-front me-1"></i> Uzupełnij dane pojazdów
                            </a>
                         </div>



                         <?php if ($parsedVehicleDataExists): // Pokaż akordeon jeśli parsowanie się udało ?>
                             <div class="accordion tn-vehicle-accordion" id="vehicleAccordion">
                                 <?php $accordion_item_index = 0; ?>
                                 <?php foreach ($parsedVehicleData as $makeModel => $versions): ?>
                                     <?php if (!empty($versions)): ?>
                                         <div class="accordion-item">
                                             <h5 class="accordion-header" id="heading<?php echo $accordion_item_index; ?>">
                                                 <button class="accordion-button <?php echo ($accordion_item_index === 0 && $defaultActiveTab === 'tn-vehicle') ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $accordion_item_index; ?>" aria-expanded="<?php echo ($accordion_item_index === 0 && $defaultActiveTab === 'tn-vehicle') ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $accordion_item_index; ?>">
                                                     <i class="bi bi-car-front-fill me-2"></i><?php echo $makeModel; ?> 
                                                 </button>
                                             </h5>   
                                             <div id="collapse<?php echo $accordion_item_index; ?>" class="accordion-collapse collapse <?php echo ($accordion_item_index === 0 && $defaultActiveTab === 'tn-vehicle') ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $accordion_item_index; ?>" data-bs-parent="#vehicleAccordion">
                                                 <div class="accordion-body">
                                                     <?php foreach ($versions as $version): ?>
                                                         <div class="tn-vehicle-version-object border-bottom pb-2 mb-2">
                                                           <div class="text-muted" style="font-size: 1.1em;">
								<Strong><?php echo $version['name']; ?> (<?php echo $version['code']; ?>) </Strong><br /> <small>
                                                              Poj.: <?php echo htmlspecialchars($version['capacity'] ?? '-', ENT_QUOTES, 'UTF-8'); ?> cm³ |
                                                                Moc: <?php echo htmlspecialchars($version['kw'] ?? '-', ENT_QUOTES, 'UTF-8'); ?> kW 
                                                                (<?php echo htmlspecialchars($version['hp'] ?? '-', ENT_QUOTES, 'UTF-8'); ?> KM) |
                                                                Rok: <?php echo htmlspecialchars($version['year_start'] ?? '-', ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars($version['year_end'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small>


  
                                                                   </div>
                                                                      </div>
                                                         
                                                     <?php endforeach; ?>
                                                          </div>
                                            </div>   </div>
                                     <?php endif; ?>
                                     <?php $accordion_item_index++; ?>
                                 <?php endforeach; ?>
                                </div>
                         <?php else: // Pokaż surowe dane jeśli parsowanie się nie udało ?>
                             <div class="tn-vehicle-info alert alert-info small" style="white-space: pre-wrap; font-family: var(--bs-font-monospace); font-size: 0.85em;">
                                 <?php echo htmlspecialchars($vehicleInfoRaw, ENT_QUOTES, 'UTF-8'); ?>
                             </div>
                             <p class="mt-2 small text-muted fst-italic">Nie udało się automatycznie przetworzyć danych do tabeli. Sprawdź formatowanie danych w polu "Pasuje do". Oczekiwany format to: "Marka/Model:", a następnie wcięte linie w formacie "Nazwa (Kod), Poj, kW, KM, RRRR.MM - RRRR.MM".</p>
                         <?php endif; ?>
                     </div>
                     <?php endif; ?>

                     <?php // Zakładka: Lokalizacje ?>
                     <?php if (!empty($productLocations)): ?>
                     <div class="tab-pane fade <?php echo $defaultActiveTab === 'tn-lokalizacje' ? 'show active' : ''; ?>" id="tn-lokalizacje-pane" role="tabpanel" aria-labelledby="tn-lokalizacje-tab" tabindex="0">
                         <h6 class="mb-3">Rozmieszczenie w Magazynie</h6>
                         <?php if (empty($productLocations)): ?>
                             <p class="text-muted fst-italic">Produkt nie jest przypisany w magazynie.</p>
                         <?php else: ?>
                             <ul class="list-group list-group-flush">
                                 <?php $totalLocatedQuantity = 0; foreach ($productLocations as $loc): $totalLocatedQuantity += $loc['quantity']; ?>
                                     <li class="list-group-item d-flex justify-content-between align-items-center py-1 px-0 bg-transparent border-bottom-dashed">
                                         <a href="<?php echo $loc['link']; ?>" class="text-decoration-none" title="Pokaż lokalizację <?php echo $loc['id']; ?> w magazynie">
                                              <i class="bi bi-geo-alt-fill me-2 text-secondary"></i><?php echo $loc['id']; ?>
                                         </a>
                                         <span class="badge bg-primary rounded-pill"><?php echo $loc['quantity']; ?> <?php echo $unit; ?></span>
                                     </li>
                                 <?php endforeach; ?>
                             </ul>
                             <?php if ($totalLocatedQuantity !== $stock): ?>
                                <div class="mt-3 alert alert-warning py-2 px-3 small">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    Uwaga: Całkowita ilość w przypisanych lokalizacjach (<?php echo $totalLocatedQuantity; ?>) różni się od stanu magazynowego produktu (<?php echo $stock; ?>).
                                </div>
                             <?php endif; ?>
                         <?php endif; ?>
                     </div>
                     <?php endif; ?>

                     <?php // Zakładka: Historia Zamówień ?>
                     <?php if (!empty($orderHistory)): ?>
                     <div class="tab-pane fade <?php echo $defaultActiveTab === 'tn-historia' ? 'show active' : ''; ?>" id="tn-historia-pane" role="tabpanel" aria-labelledby="tn-historia-tab" tabindex="0">
                         <h6 class="mb-3">Ostatnie Zamówienia (max 10)</h6>
                         <div class="table-responsive">
                             <table class="table table-sm table-striped table-hover small caption-top mb-0">
                                 <thead class="small"><tr><th>Numer zamówienia</th><th>Data</th><th>Nazwa Klienta</th><th class="text-center">Ilość</th><th>Status</th></tr></thead>
                                 <tbody>
                                     <?php foreach ($orderHistory as $zam): ?>
                                     <tr>
                                         <td><a href="<?php echo htmlspecialchars(tn_generuj_url('order_preview', ['id' => $zam['id'] ?? 0]), ENT_QUOTES, 'UTF-8'); ?>" title="Zobacz zamówienie #<?php echo htmlspecialchars($zam['id'] ?? '?'); ?>"><?php echo htmlspecialchars($zam['id'] ?? '?'); ?></a></td>
                                         <td class="text-nowrap"><?php echo function_exists('tn_format_date') ? tn_format_date($zam['order_date'] ?? null, $dateTimeFormat) : (htmlspecialchars($zam['order_date'] ?? '-') . ' '); ?></td>
                                         <td><?php echo htmlspecialchars($zam['buyer_name'] ?? '-'); ?></td>
                                         <td class="text-center"><?php echo htmlspecialchars($zam['quantity'] ?? '?'); ?></td>
                                         <td><?php
                                             $status = htmlspecialchars($zam['status'] ?? '?');
                                             // Prosta logika mapowania statusu na klasę Bootstrap (można rozbudować)
                                             $statusClass = 'text-bg-secondary';
                                             if (stripos($status, 'W realizacji') !== false) $statusClass = 'text-bg-primary';
                                             elseif (stripos($status, 'Zakończone') !== false) $statusClass = 'text-bg-success';
                                             elseif (stripos($status, 'Anulowane') !== false) $statusClass = 'text-bg-danger';
                                             elseif (stripos($status, 'Nowe') !== false) $statusClass = 'text-bg-info';
                                             ?><span class="badge <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                                     </tr>
                                     <?php endforeach; ?>
                                 </tbody>
                             </table>
                         </div>
                         <?php $allOrdersLink = htmlspecialchars(tn_generuj_url('orders', ['product_id' => $productId]), ENT_QUOTES, 'UTF-8'); ?>
                         <a href="<?php echo $allOrdersLink; ?>" class="btn btn-sm btn-outline-secondary mt-3"><i class="bi bi-list-ul me-1"></i> Pokaż wszystkie zamówienia</a>
                     </div>
                     <?php endif; ?>
                 </div> <?php // Koniec .tab-content ?>
             </div> <?php // Koniec .mt-4 ?>

        </div> <?php // Koniec .card-body ?>
    </div> <?php // Koniec .card ?>

  <?php // --- Sekcja Podobnych Produktów --- ?>
    <?php if (!empty($similarProducts)): ?>
    <div class="mt-5">
        <h4 class="mb-3"><i class="bi bi-diagram-2 me-2"></i> Inne części w bazie</h4>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3">
            <?php foreach ($similarProducts as $podobny): ?>
                <?php // Renderuj kartę podobnego produktu - użyj partiala jeśli istnieje ?>
                <?php $produkt = $podobny; // Przypisz do zmiennej oczekiwanej przez partial ?>
                <div class="col">
                    <?php include TN_SCIEZKA_TEMPLATEK . 'partials/tn_product_card_preview.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php // --- Modal do powiększania zdjęć --- ?>
    <?php include TN_SCIEZKA_TEMPLATEK . 'partials/tn_image_zoom_modal.php'; ?>

</div> <?php // Koniec .container-fluid ?>

<?php // --- Style CSS specyficzne dla tej strony --- ?>
<style>
/* Użyj bardziej specyficznych selektorów, jeśli to możliwe, aby uniknąć konfliktów */
.tn-podglad-produktu .tn-obrazek-podgladu { max-height: 350px; width: auto; transition: transform 0.3s ease; }
.tn-podglad-produktu .tn-obrazek-podgladu:hover { transform: scale(1.03); }
.tn-podglad-produktu .tn-gallery-thumbnail { width: 60px; height: 60px; object-fit: contain; cursor: pointer; border: 2px solid transparent; transition: border-color 0.2s ease, transform 0.2s ease; padding: 2px; background-color: var(--bs-tertiary-bg); }
.tn-podglad-produktu .tn-gallery-thumbnail:hover { border-color: var(--bs-primary); transform: scale(1.1); }
.tn-podglad-produktu .tn-gallery-thumbnail.active { border-color: var(--bs-primary); box-shadow: 0 0 0 2px rgba(var(--bs-primary-rgb), 0.5); }
/* Usunięto tn-def-list-condensed - użyj standardowych klas Bootstrap lub inline-block/flex dla dt/dd */
.tn-podglad-produktu .tn-vehicle-info { font-size: 0.9em; white-space: pre-wrap; max-height: 300px; overflow-y: auto; }
.tn-podglad-produktu #barcode { max-width: 200px; height: auto; display: block; margin: 0 auto; }

/* Dodatkowe style dla parsowanych danych pojazdów */
.tn-vehicle-version-object {
    /* Dodaj odstęp między poszczególnymi wersjami */
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--bs-border-color); /* Delikatna linia oddzielająca */
}
.tn-vehicle-version-object:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}
.tn-vehicle-version-label {
    font-size: 0.7em;
    color: var(--bs-secondary-color); /* Użyj zmiennej Bootstrap dla koloru */
    text-transform: uppercase;
    margin-bottom: 0.1rem;
}
.tn-vehicle-version-value {
    font-size: 0.9em;
    font-weight: bold; /* Wartość powinna być bardziej widoczna */
}
.tn-vehicle-version-value.font-monospace {
     font-family: var(--bs-font-monospace);
}

/* Styl dla linii oddzielającej w list-group lokalizacji */
.list-group-item.border-bottom-dashed {
    border-bottom-style: dashed !important;
}

</style>


