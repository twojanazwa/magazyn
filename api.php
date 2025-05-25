<?php

// --- Konfiguracja ---
define('API_KEY', '12345secret'); // Ustawiony klucz API
define('DATA_DIR', 'TNbazaDanych/');
define('PRODUCTS_JSON_PATH', DATA_DIR . 'products.json');
define('WAREHOUSE_JSON_PATH', DATA_DIR . 'warehouse.json');
define('ORDERS_JSON_PATH', DATA_DIR . 'orders.json');
define('REGALY_JSON_PATH', DATA_DIR . 'regaly.json');
define('USERS_JSON_PATH', DATA_DIR . 'users.json'); 
define('RETURNS_JSON_PATH', DATA_DIR . 'returns.json'); 

// --- Nagłówki ---
header('Content-Type: application/json; charset=utf-8');
// Dostęp CORS - odkomentuj w razie potrzeby
// header('Access-Control-Allow-Origin: *'); 
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, X-API-KEY, Authorization');

// Obsługa żądania OPTIONS (preflight) dla CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No Content
    exit;
}

// --- Funkcje pomocnicze ---

/**
 * Wczytuje dane JSON z pliku lub kończy działanie skryptu z błędem.
 * @param string $sciezka Ścieżka do pliku JSON.
 * @param string $nazwaZasobu Nazwa zasobu (dla komunikatów o błędach).
 * @param bool $exitOnError Czy zakończyć skrypt w przypadku błędu.
 * @return array|null Tablica z danymi lub null w przypadku błędu (jeśli $exitOnError = false).
 */
function wczytajLubZakoncz(string $sciezka, string $nazwaZasobu, bool $exitOnError = true): ?array {
    if (!is_readable($sciezka)) {
        if ($exitOnError) {
            http_response_code(500);
            $errorMsg = file_exists($sciezka) ? "Brak uprawnień do odczytu pliku: {$nazwaZasobu}" : "Nie znaleziono pliku: {$nazwaZasobu}";
            echo json_encode(['error' => $errorMsg . " ({$sciezka})"]);
            exit;
        }
        return null;
    }
    $jsonContent = file_get_contents($sciezka);
    if ($jsonContent === false) {
        if ($exitOnError) {
            http_response_code(500);
            echo json_encode(['error' => "Nie udało się odczytać: {$nazwaZasobu}"]);
            exit;
        }
        return null;
    }
    if (trim($jsonContent) === '') return []; // Pusty plik traktujemy jako pustą tablicę
    
    $dane = json_decode($jsonContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        if ($exitOnError) {
            http_response_code(500);
            echo json_encode(['error' => "Błąd JSON w {$nazwaZasobu}: " . json_last_error_msg()]);
            exit;
        }
        return null;
    }
    if (!is_array($dane) && $nazwaZasobu !== 'profil_uzytkownika_pojedynczy') { 
         if ($exitOnError) {
            http_response_code(500);
            echo json_encode(['error' => "Nieprawidłowy format danych w {$nazwaZasobu}, oczekiwano tablicy."]);
            exit;
         }
         return null;
    }
    return $dane; 
}

/**
 * Zapisuje dane do pliku JSON.
 * @param string $sciezka Ścieżka do pliku JSON.
 * @param array $dane Tablica z danymi do zapisania.
 * @param string $nazwaPliku Nazwa pliku (dla komunikatów o błędach).
 * @return bool True w przypadku sukcesu, false w przypadku błędu.
 */
function zapiszDaneJson(string $sciezka, array $dane, string $nazwaPliku): bool {
    $jsonContent = json_encode($dane, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    if ($jsonContent === false) {
        http_response_code(500);
        echo json_encode(['error' => "Błąd kodowania JSON dla {$nazwaPliku}: " . json_last_error_msg()]);
        return false;
    }
    $katalog = dirname($sciezka);
    if (!is_dir($katalog)) {
        if (!mkdir($katalog, 0775, true)) { 
            http_response_code(500);
            echo json_encode(['error' => "Nie udało się utworzyć katalogu: {$katalog}"]);
            return false;
        }
    }
    if (file_put_contents($sciezka, $jsonContent, LOCK_EX) === false) {
        http_response_code(500);
        echo json_encode(['error' => "Nie udało się zapisać do {$sciezka}"]);
        return false;
    }
    return true;
}

/**
 * Weryfikuje klucz API. Kończy działanie skryptu w przypadku nieautoryzowanego dostępu.
 */
function weryfikujKluczApi(): void {
    $przeslanyKlucz = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;
    if ($przeslanyKlucz !== API_KEY) {
        http_response_code(401);
        echo json_encode(['error' => 'Nieautoryzowany dostęp.']);
        exit;
    }
}

weryfikujKluczApi(); 

// --- Routing ---
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = ''; 

$requestPath = parse_url($requestUri, PHP_URL_PATH);
$scriptName = $_SERVER['SCRIPT_NAME'];

if (strpos($requestPath, $scriptName) === 0) {
    $endpointPath = substr($requestPath, strlen($scriptName));
} else {
    $scriptDir = dirname($scriptName);
    if ($scriptDir !== '/' && $scriptDir !== '\\' && strpos($requestPath, $scriptDir) === 0) {
        $endpointPath = substr($requestPath, strlen($scriptDir));
    } else {
        $endpointPath = $requestPath;
    }
}
if (empty($endpointPath) || $endpointPath[0] !== '/') {
    $endpointPath = '/' . $endpointPath;
}
if (!empty($basePath) && strpos($endpointPath, $basePath) === 0) {
    $endpointPath = substr($endpointPath, strlen($basePath));
    if (empty($endpointPath) || $endpointPath[0] !== '/') {
        $endpointPath = '/' . $endpointPath;
    }
}


$routes = [
    'GET' => [
        '/produkty' => function() {
            $daneProduktow = wczytajLubZakoncz(PRODUCTS_JSON_PATH, 'produkty');
            if (!is_array($daneProduktow)) { 
                echo json_encode([]);
                return;
            }
            $filtrowaneProdukty = $daneProduktow; 

            if (isset($_GET['tn_numer_katalogowy']) && $_GET['tn_numer_katalogowy'] !== '') {
                $szukanySku = trim(strtolower($_GET['tn_numer_katalogowy']));
                $filtrowaneProdukty = array_filter($filtrowaneProdukty, function($produkt) use ($szukanySku) {
                    return isset($produkt['tn_numer_katalogowy']) && strtolower($produkt['tn_numer_katalogowy']) === $szukanySku;
                });
            }
            if (isset($_GET['name']) && $_GET['name'] !== '') {
                $szukanaNazwa = trim(strtolower($_GET['name']));
                $filtrowaneProdukty = array_filter($filtrowaneProdukty, function($produkt) use ($szukanaNazwa) {
                    return isset($produkt['name']) && stripos(strtolower($produkt['name']), $szukanaNazwa) !== false;
                });
            }
            if (isset($_GET['category']) && $_GET['category'] !== '') {
                $szukanaKategoria = trim(strtolower($_GET['category']));
                $filtrowaneProdukty = array_filter($filtrowaneProdukty, function($produkt) use ($szukanaKategoria) {
                    return isset($produkt['category']) && strtolower($produkt['category']) === $szukanaKategoria;
                });
            }
            if (isset($_GET['min_price']) && $_GET['min_price'] !== '') {
                $minCena = (float)$_GET['min_price'];
                $filtrowaneProdukty = array_filter($filtrowaneProdukty, function($produkt) use ($minCena) {
                    return isset($produkt['price']) && (float)$produkt['price'] >= $minCena;
                });
            }
            if (isset($_GET['max_price']) && $_GET['max_price'] !== '') {
                $maxCena = (float)$_GET['max_price'];
                $filtrowaneProdukty = array_filter($filtrowaneProdukty, function($produkt) use ($maxCena) {
                    return isset($produkt['price']) && (float)$produkt['price'] <= $maxCena;
                });
            }
            if (isset($_GET['in_stock'])) { 
                $inStock = strtolower($_GET['in_stock']) === 'true';
                if ($inStock) {
                    $filtrowaneProdukty = array_filter($filtrowaneProdukty, function($produkt) {
                        return isset($produkt['stock']) && (int)$produkt['stock'] > 0;
                    });
                }
            }

            $vehicleFilters = [
                'marka' => 'marka', 'model' => 'model', 'typ' => 'typ_pojazdu', 
                'pojemnosc_silnika_od' => 'pojemnosc_silnika', 'pojemnosc_silnika_do' => 'pojemnosc_silnika',
                'moc_km_od' => 'moc_km', 'moc_km_do' => 'moc_km', 'moc_kw_od' => 'moc_kw', 'moc_kw_do' => 'moc_kw',
                'rok_produkcji_od' => 'rok_produkcji', 'rok_produkcji_do' => 'rok_produkcji',
            ];

            foreach ($vehicleFilters as $param => $field) {
                if (isset($_GET[$param]) && $_GET[$param] !== '') { 
                    $value = trim(strtolower($_GET[$param]));
                    if (str_ends_with($param, '_od')) {
                        $filtrowaneProdukty = array_filter($filtrowaneProdukty, function($produkt) use ($field, $value) {
                            return isset($produkt[$field]) && $produkt[$field] !== null && (float)$produkt[$field] >= (float)$value;
                        });
                    } elseif (str_ends_with($param, '_do')) {
                        $filtrowaneProdukty = array_filter($filtrowaneProdukty, function($produkt) use ($field, $value) {
                            return isset($produkt[$field]) && $produkt[$field] !== null && (float)$produkt[$field] <= (float)$value;
                        });
                    } else { 
                        $filtrowaneProdukty = array_filter($filtrowaneProdukty, function($produkt) use ($field, $value) {
                            return isset($produkt[$field]) && $produkt[$field] !== null && stripos(strtolower($produkt[$field]), $value) !== false;
                        });
                    }
                }
            }
            echo json_encode(array_values($filtrowaneProdukty)); 
        },
        '/produkty/(\d+)' => function($matches) { $id = (int)$matches[1]; $dane = wczytajLubZakoncz(PRODUCTS_JSON_PATH, 'produkty'); $item = null; foreach ($dane as $p) if (isset($p['id']) && $p['id'] === $id) $item = $p; if ($item) echo json_encode($item); else { http_response_code(404); echo json_encode(['error' => "Produkt ID {$id} nie znaleziony"]); }},
        '/magazyn' => function() { $dane = wczytajLubZakoncz(WAREHOUSE_JSON_PATH, 'magazyn'); if (isset($_GET['produkt_id'])) { $pid = (int)$_GET['produkt_id']; $res = array_filter($dane, fn($l) => isset($l['product_id']) && $l['product_id'] === $pid && ($l['status'] ?? '') === 'occupied'); echo json_encode(array_values($res)); } else { echo json_encode($dane); }},
        '/magazyn/([a-zA-Z0-9_-]+)' => function($matches) { $id = $matches[1]; $dane = wczytajLubZakoncz(WAREHOUSE_JSON_PATH, 'magazyn'); $item = null; foreach ($dane as $l) if (isset($l['id']) && $l['id'] === $id) $item = $l; if ($item) echo json_encode($item); else { http_response_code(404); echo json_encode(['error' => "Lokalizacja ID {$id} nie znaleziona"]); }},
        '/zamowienia' => function() { echo json_encode(wczytajLubZakoncz(ORDERS_JSON_PATH, 'zamówienia')); },
        '/zamowienia/(\d+)' => function($matches) { $id = (int)$matches[1]; $dane = wczytajLubZakoncz(ORDERS_JSON_PATH, 'zamówienia'); $item = null; foreach ($dane as $o) if (isset($o['id']) && $o['id'] === $id) $item = $o; if ($item) echo json_encode($item); else { http_response_code(404); echo json_encode(['error' => "Zamówienie ID {$id} nie znalezione"]); }},
        '/regaly' => function() { echo json_encode(wczytajLubZakoncz(REGALY_JSON_PATH, 'regały')); },
        '/regaly/([a-zA-Z0-9_-]+)' => function($matches) { $id = $matches[1]; $dane = wczytajLubZakoncz(REGALY_JSON_PATH, 'regały'); $item = null; foreach ($dane as $r) if (isset($r['tn_id_regalu']) && $r['tn_id_regalu'] === $id) $item = $r; if ($item) echo json_encode($item); else { http_response_code(404); echo json_encode(['error' => "Regał ID {$id} nie znaleziony"]); }},
        '/dashboard/summary' => function() {
            $orders = wczytajLubZakoncz(ORDERS_JSON_PATH, 'zamówienia');
            $products = wczytajLubZakoncz(PRODUCTS_JSON_PATH, 'produkty');
            $warehouse = wczytajLubZakoncz(WAREHOUSE_JSON_PATH, 'magazyn');
            $totalSalesValue = 0; $totalOrders = is_array($orders) ? count($orders) : 0; $productsInStockCount = 0; $totalProductsCatalog = is_array($products) ? count($products) : 0;
            $todaySalesValue = 0; $todayOrdersCount = 0; $currentMonthSalesValue = 0; $currentMonthOrdersCount = 0;
            $newOrdersTodayCount = 0; $pendingOrdersCount = 0;  
            $currentDate = date('Y-m-d'); $currentMonth = date('Y-m'); $productPrices = [];
            if(is_array($products)) foreach ($products as $p) if (isset($p['id'], $p['price'])) $productPrices[$p['id']] = (float)$p['price'];
            
            $recentlySoldProducts = []; $tempSoldItems = [];

            if (is_array($orders)) { 
                foreach ($orders as $o) {
                    $orderProductPrice = 0;
                    if (isset($o['product_id'], $productPrices[$o['product_id']], $o['quantity'])) $orderProductPrice = $productPrices[$o['product_id']] * (int)$o['quantity'];
                    if (isset($o['status']) && strtolower($o['status']) === 'zrealizowane') {
                        $totalSalesValue += $orderProductPrice;
                        $orderTimestamp = isset($o['date_updated']) ? strtotime($o['date_updated']) : (isset($o['order_date']) ? strtotime($o['order_date']) : 0);
                        if ($orderTimestamp > 0) { 
                            $productDetails = null;
                            if(is_array($products)){ 
                                foreach($products as $p_item) if (isset($p_item['id']) && $p_item['id'] === (int)$o['product_id']) {$productDetails = $p_item; break;}
                            }
                            $tempSoldItems[] = [
                                'order_id' => $o['id'] ?? null, 'product_id' => (int)($o['product_id'] ?? 0),
                                'product_name' => $productDetails['name'] ?? 'Nieznany produkt', 'product_image' => $productDetails['image'] ?? null,
                                'quantity' => (int)($o['quantity'] ?? 0), 'order_date_timestamp' => $orderTimestamp,
                                'order_date_formatted' => date('Y-m-d H:i', $orderTimestamp)
                            ];
                        }
                        if (isset($o['order_date'])) {
                            $orderDateOnly = date('Y-m-d', strtotime($o['order_date'])); $orderMonthYear = date('Y-m', strtotime($o['order_date']));
                            if ($orderDateOnly === $currentDate) { $todaySalesValue += $orderProductPrice; $todayOrdersCount++; }
                            if ($orderMonthYear === $currentMonth) { $currentMonthSalesValue += $orderProductPrice; $currentMonthOrdersCount++; }
                        }
                    }
                    if (isset($o['order_date']) && isset($o['status']) && strtolower($o['status']) === 'nowe') {
                        if (date('Y-m-d', strtotime($o['order_date'])) === $currentDate) $newOrdersTodayCount++;
                    }
                    if (isset($o['status'])) {
                        $statusLower = strtolower($o['status']);
                        if ($statusLower === 'oczekuje na płatność' || $statusLower === 'w realizacji') $pendingOrdersCount++;
                    }
                }
            }
            usort($tempSoldItems, function($a, $b) { return $b['order_date_timestamp'] <=> $a['order_date_timestamp']; });
            $recentlySoldProducts = array_slice($tempSoldItems, 0, 5);

            if(is_array($warehouse)) foreach ($warehouse as $l) if (isset($l['status']) && $l['status']==='occupied' && isset($l['quantity'])) $productsInStockCount += (int)$l['quantity'];
            
            echo json_encode([
                'totalSalesValue' => round($totalSalesValue,2), 'totalOrders' => $totalOrders, 'productsInStockCount' => $productsInStockCount,
                'totalProductsCatalog' => $totalProductsCatalog, 'todaySalesValue' => round($todaySalesValue,2), 'todayOrdersCount' => $todayOrdersCount,
                'currentMonthSalesValue' => round($currentMonthSalesValue,2), 'currentMonthOrdersCount' => $currentMonthOrdersCount,
                'newOrdersTodayCount' => $newOrdersTodayCount, 'pendingOrdersCount' => $pendingOrdersCount,
                'recentlySoldProducts' => $recentlySoldProducts 
            ]);
        },
        '/diagnostics/datafiles' => function() { $filesToCheck = ['Produkty' => PRODUCTS_JSON_PATH, 'Magazyn' => WAREHOUSE_JSON_PATH, 'Zamówienia' => ORDERS_JSON_PATH, 'Regały' => REGALY_JSON_PATH, 'Użytkownicy' => USERS_JSON_PATH, 'Zwroty' => RETURNS_JSON_PATH]; $report = []; $overallStatus = 'OK'; foreach ($filesToCheck as $name => $path) { $fileReport = ['file_name' => $name, 'path' => $path, 'exists' => false, 'readable' => false, 'writable' => false, 'json_valid' => false, 'message' => '']; if (file_exists($path)) { $fileReport['exists'] = true; if (is_readable($path)) { $fileReport['readable'] = true; $content = file_get_contents($path); if ($content !== false) { if (trim($content) === '') { $fileReport['json_valid'] = true; $fileReport['message'] = 'Plik pusty.'; } else { json_decode($content, true); if (json_last_error() === JSON_ERROR_NONE) $fileReport['json_valid'] = true; else { $fileReport['message'] = 'Błąd JSON: ' . json_last_error_msg(); $overallStatus = 'BŁĄD'; }}} else { $fileReport['message'] = 'Nie odczytano zawartości.'; $overallStatus = 'BŁĄD'; }} else { $fileReport['message'] = 'Nieczytelny.'; $overallStatus = 'BŁĄD'; } if (is_writable($path)) $fileReport['writable'] = true; else { $fileReport['writable'] = false; if (empty($fileReport['message'])) $fileReport['message'] .= 'Niezapisywalny.'; else $fileReport['message'] .= ' Dodatkowo niezapisywalny.'; if ($overallStatus !== 'BŁĄD' && $name !== 'Użytkownicy') $overallStatus = 'OSTRZEŻENIE'; } } else { $fileReport['message'] = 'Nie istnieje.'; $overallStatus = 'BŁĄD'; } $report[] = $fileReport; } echo json_encode(['status_ogolny' => $overallStatus, 'szczegoly_plikow' => $report]); },
        '/profil' => function() {
            $users = wczytajLubZakoncz(USERS_JSON_PATH, 'użytkownicy'); if (empty($users)) { http_response_code(404); echo json_encode(['error' => 'Brak zdefiniowanych użytkowników.']); exit; }
            $loggedInUser = null; foreach ($users as $user) if (isset($user['username']) && $user['username'] === 'admin') {$loggedInUser = $user; break;}
            if (!$loggedInUser && !empty($users)) $loggedInUser = $users[0];
            if ($loggedInUser) { unset($loggedInUser['password_hash']); echo json_encode($loggedInUser); } else { http_response_code(404); echo json_encode(['error' => 'Nie znaleziono profilu użytkownika.']); }
        },
        '/zwroty' => function() { echo json_encode(wczytajLubZakoncz(RETURNS_JSON_PATH, 'zwroty')); },
        '/zwroty/(\d+)' => function($matches) { $id = (int)$matches[1]; $dane = wczytajLubZakoncz(RETURNS_JSON_PATH, 'zwroty'); $item = null; foreach ($dane as $z) if (isset($z['id']) && $z['id'] === $id) $item = $z; if ($item) echo json_encode($item); else { http_response_code(404); echo json_encode(['error' => "Zwrot ID {$id} nie znaleziony"]); }},
    ],
    'POST' => [
        '/produkty' => function() { 
            $inputData = json_decode(file_get_contents('php://input'), true); 
            if ($inputData === null || !isset($inputData['name']) || empty(trim($inputData['name']))) { 
                http_response_code(400); echo json_encode(['error' => 'Nieprawidłowe dane. Nazwa produktu jest wymagana.']); exit; 
            } 
            $daneProduktow = wczytajLubZakoncz(PRODUCTS_JSON_PATH, 'produkty'); 
            $maxId = 0; foreach ($daneProduktow as $p) if (isset($p['id']) && $p['id'] > $maxId) $maxId = $p['id']; 
            $noweId = $maxId + 1; 
            $nowyProdukt = array_merge([
                'id' => $noweId, 'producent' => '', 'tn_numer_katalogowy' => '', 'category' => '', 
                'desc' => '', 'spec' => '', 'params' => '', 'vehicle' => '', 
                'price' => 0.0, 'shipping' => 0.0, 'stock' => 0, 'tn_jednostka_miary' => 'szt.', 
                'warehouse' => '', 'image' => '', 'date_added' => date('Y-m-d H:i:s'),
                'marka' => $inputData['marka'] ?? '',
                'model' => $inputData['model'] ?? '',
                'typ_pojazdu' => $inputData['typ_pojazdu'] ?? '',
                'pojemnosc_silnika' => isset($inputData['pojemnosc_silnika']) && $inputData['pojemnosc_silnika'] !== '' ? (float)$inputData['pojemnosc_silnika'] : null,
                'moc_km' => isset($inputData['moc_km']) && $inputData['moc_km'] !== '' ? (int)$inputData['moc_km'] : null,
                'moc_kw' => isset($inputData['moc_kw']) && $inputData['moc_kw'] !== '' ? (int)$inputData['moc_kw'] : null,
                'rok_produkcji' => isset($inputData['rok_produkcji']) && $inputData['rok_produkcji'] !== '' ? (int)$inputData['rok_produkcji'] : null,
            ], $inputData); 
            $nowyProdukt['name'] = trim($nowyProdukt['name']); 
            $daneProduktow[] = $nowyProdukt; 
            if (zapiszDaneJson(PRODUCTS_JSON_PATH, $daneProduktow, 'produkty')) { 
                http_response_code(201); echo json_encode(['success' => true, 'message' => 'Produkt dodany.', 'product' => $nowyProdukt]); 
            }
        },
        '/zamowienia' => function() { $inputData = json_decode(file_get_contents('php://input'), true); if ($inputData === null || !isset($inputData['product_id']) || !isset($inputData['buyer_name']) || !isset($inputData['quantity'])) { http_response_code(400); echo json_encode(['error' => 'Wymagane pola: product_id, buyer_name, quantity.']); exit; } $daneZamowien = wczytajLubZakoncz(ORDERS_JSON_PATH, 'zamówienia'); $maxId = 0; foreach ($daneZamowien as $o) if (isset($o['id']) && $o['id'] > $maxId) $maxId = $o['id']; $noweId = $maxId + 1; $noweZamowienie = ['id' => $noweId, 'product_id' => (int)$inputData['product_id'], 'buyer_name' => trim($inputData['buyer_name']), 'buyer_daneWysylki' => $inputData['buyer_daneWysylki'] ?? '', 'status' => $inputData['status'] ?? 'Nowe', 'tn_status_platnosci' => $inputData['tn_status_platnosci'] ?? 'Oczekuje na płatność', 'quantity' => (int)$inputData['quantity'], 'courier_id' => $inputData['courier_id'] ?? null, 'tracking_number' => $inputData['tracking_number'] ?? null, 'processed' => $inputData['processed'] ?? false, 'order_date' => date('Y-m-d H:i:s'), 'date_updated' => date('Y-m-d H:i:s')]; $daneZamowien[] = $noweZamowienie; if (zapiszDaneJson(ORDERS_JSON_PATH, $daneZamowien, 'zamówienia')) { http_response_code(201); echo json_encode(['success' => true, 'message' => 'Zamówienie dodane.', 'order' => $noweZamowienie]); }},
        '/zamowienia/(\d+)/zmien_status' => function($matches) { $orderId = (int)$matches[1]; $inputData = json_decode(file_get_contents('php://input'), true); if ($inputData === null || !isset($inputData['nowy_status'])) { http_response_code(400); echo json_encode(['error' => 'Brak pola "nowy_status".']); exit; } $nowyStatus = trim($inputData['nowy_status']); $daneZamowien = wczytajLubZakoncz(ORDERS_JSON_PATH, 'zamówienia'); $idx = null; $staryStatus = null; foreach($daneZamowien as $i => $z) if(isset($z['id']) && $z['id'] === $orderId) {$idx = $i; $staryStatus = $z['status'] ?? null; break;} if ($idx === null) { http_response_code(404); echo json_encode(['error' => "Zamówienie ID {$orderId} nie znalezione"]); exit; } $magazynZmodyfikowany = false; if (strtolower($nowyStatus) === 'zrealizowane' && strtolower($staryStatus) !== 'zrealizowane') { $pid = $daneZamowien[$idx]['product_id'] ?? null; $qty = (int)($daneZamowien[$idx]['quantity'] ?? 0); if ($pid && $qty > 0) { $daneMagazynu = wczytajLubZakoncz(WAREHOUSE_JSON_PATH, 'magazyn'); $zaktualizowano = false; foreach ($daneMagazynu as $iMag => &$lok) { if (isset($lok['product_id']) && $lok['product_id']===$pid && ($lok['status']??'')==='occupied' && (int)($lok['quantity']??0) >= $qty) { $lok['quantity'] = (int)$lok['quantity'] - $qty; if((int)$lok['quantity']<=0) {$lok['status']='empty';$lok['product_id']=null;$lok['quantity']=0;} $zaktualizowano = true; $magazynZmodyfikowany = true; break; }} if (!$zaktualizowano) {http_response_code(409); echo json_encode(['error' => "Błąd magazynu dla produktu ID {$pid}: niewystarczająca ilość lub brak produktu."]); exit;} if ($magazynZmodyfikowany && !zapiszDaneJson(WAREHOUSE_JSON_PATH, $daneMagazynu, 'magazyn')) exit; }} $daneZamowien[$idx]['status'] = $nowyStatus; $daneZamowien[$idx]['date_updated'] = date("Y-m-d H:i:s"); if (zapiszDaneJson(ORDERS_JSON_PATH, $daneZamowien, 'zamówienia')) { echo json_encode(['success' => true, 'message' => "Status zamówienia #{$orderId} zmieniony.", 'zamowienie' => $daneZamowien[$idx]]); }},
        '/magazyn/przypisz' => function() { $inputData = json_decode(file_get_contents('php://input'), true); if ($inputData === null || !isset($inputData['location_id'], $inputData['product_id'], $inputData['quantity'])) { http_response_code(400); echo json_encode(['error' => 'Nieprawidłowe dane. Wymagane: location_id, product_id, quantity.']); exit; } $locationId = trim($inputData['location_id']); $productId = (int)$inputData['product_id']; $quantity = (int)$inputData['quantity']; if ($quantity <= 0) { http_response_code(400); echo json_encode(['error' => 'Ilość musi być większa od zera.']); exit; } $daneMagazynu = wczytajLubZakoncz(WAREHOUSE_JSON_PATH, 'magazyn'); $daneProduktow = wczytajLubZakoncz(PRODUCTS_JSON_PATH, 'produkty'); $produktIstnieje = false; foreach($daneProduktow as $p) if(isset($p['id']) && $p['id'] === $productId) $produktIstnieje = true; if (!$produktIstnieje) { http_response_code(404); echo json_encode(['error' => "Produkt o ID {$productId} nie istnieje."]); exit; } $idxLokalizacji = null; foreach($daneMagazynu as $i => $lok) if(isset($lok['id']) && $lok['id'] === $locationId) $idxLokalizacji = $i; if ($idxLokalizacji === null) { http_response_code(404); echo json_encode(['error' => "Lokalizacja ID {$locationId} nie znaleziona."]); exit; } if (($daneMagazynu[$idxLokalizacji]['status'] ?? 'empty') !== 'empty') { http_response_code(409); echo json_encode(['error' => "Lokalizacja {$locationId} jest już zajęta."]); exit; } $daneMagazynu[$idxLokalizacji]['product_id'] = $productId; $daneMagazynu[$idxLokalizacji]['quantity'] = $quantity; $daneMagazynu[$idxLokalizacji]['status'] = 'occupied'; if (zapiszDaneJson(WAREHOUSE_JSON_PATH, $daneMagazynu, 'magazyn')) { http_response_code(200); echo json_encode(['success' => true, 'message' => "Produkt ID {$productId} przypisany do lokalizacji {$locationId}.", 'location' => $daneMagazynu[$idxLokalizacji]]); }},
        '/magazyn/zdejmij/([a-zA-Z0-9_-]+)' => function($matches) { $locationId = $matches[1]; $daneMagazynu = wczytajLubZakoncz(WAREHOUSE_JSON_PATH, 'magazyn'); $idxLokalizacji = null; foreach($daneMagazynu as $i => $lok) if(isset($lok['id']) && $lok['id'] === $locationId) $idxLokalizacji = $i; if ($idxLokalizacji === null) { http_response_code(404); echo json_encode(['error' => "Lokalizacja ID {$locationId} nie znaleziona."]); exit; } if (($daneMagazynu[$idxLokalizacji]['status'] ?? 'empty') === 'empty') { http_response_code(400); echo json_encode(['error' => "Lokalizacja {$locationId} jest już pusta."]); exit; } $productIdZdjety = $daneMagazynu[$idxLokalizacji]['product_id']; $daneMagazynu[$idxLokalizacji]['product_id'] = null; $daneMagazynu[$idxLokalizacji]['quantity'] = 0; $daneMagazynu[$idxLokalizacji]['status'] = 'empty'; if (zapiszDaneJson(WAREHOUSE_JSON_PATH, $daneMagazynu, 'magazyn')) { http_response_code(200); echo json_encode(['success' => true, 'message' => "Produkt ID {$productIdZdjety} zdjęty z lokalizacji {$locationId}.", 'location' => $daneMagazynu[$idxLokalizacji]]); }},
        '/magazyn/przesun' => function() { $inputData = json_decode(file_get_contents('php://input'), true); if ($inputData === null || !isset($inputData['source_location_id'], $inputData['target_location_id'], $inputData['product_id'], $inputData['quantity_to_move'])) { http_response_code(400); echo json_encode(['error' => 'Nieprawidłowe dane. Wymagane: source_location_id, target_location_id, product_id, quantity_to_move.']); exit; } $sourceLocationId = trim($inputData['source_location_id']); $targetLocationId = trim($inputData['target_location_id']); $productIdToMove = (int)$inputData['product_id']; $quantityToMove = (int)$inputData['quantity_to_move']; if ($sourceLocationId === $targetLocationId) { http_response_code(400); echo json_encode(['error' => 'Lokalizacja źródłowa i docelowa nie mogą być takie same.']); exit; } if ($quantityToMove <= 0) { http_response_code(400); echo json_encode(['error' => 'Ilość do przesunięcia musi być większa od zera.']); exit; } $daneMagazynu = wczytajLubZakoncz(WAREHOUSE_JSON_PATH, 'magazyn'); $idxSource = null; $idxTarget = null; foreach($daneMagazynu as $i => $lok) { if (isset($lok['id'])) { if ($lok['id'] === $sourceLocationId) $idxSource = $i; if ($lok['id'] === $targetLocationId) $idxTarget = $i; }} if ($idxSource === null) { http_response_code(404); echo json_encode(['error' => "Lokalizacja źródłowa ID {$sourceLocationId} nie znaleziona."]); exit; } if ($idxTarget === null) { http_response_code(404); echo json_encode(['error' => "Lokalizacja docelowa ID {$targetLocationId} nie znaleziona."]); exit; } if (($daneMagazynu[$idxSource]['status'] ?? 'empty') !== 'occupied') { http_response_code(400); echo json_encode(['error' => "Lokalizacja źródłowa {$sourceLocationId} jest pusta."]); exit; } if (($daneMagazynu[$idxSource]['product_id'] ?? null) !== $productIdToMove) { http_response_code(400); echo json_encode(['error' => "Lokalizacja źródłowa {$sourceLocationId} nie zawiera produktu ID {$productIdToMove}."]); exit; } if ((int)($daneMagazynu[$idxSource]['quantity'] ?? 0) < $quantityToMove) { http_response_code(400); echo json_encode(['error' => "Niewystarczająca ilość produktu ID {$productIdToMove} w lokalizacji {$sourceLocationId} (jest: ".($daneMagazynu[$idxSource]['quantity']??0).", potrzeba: {$quantityToMove})."]); exit; } if (($daneMagazynu[$idxTarget]['status'] ?? 'empty') === 'occupied' && ($daneMagazynu[$idxTarget]['product_id'] ?? null) !== $productIdToMove) { http_response_code(409); echo json_encode(['error' => "Lokalizacja docelowa {$targetLocationId} jest zajęta przez inny produkt."]); exit; } $daneMagazynu[$idxSource]['quantity'] = (int)$daneMagazynu[$idxSource]['quantity'] - $quantityToMove; if ($daneMagazynu[$idxSource]['quantity'] <= 0) { $daneMagazynu[$idxSource]['status'] = 'empty'; $daneMagazynu[$idxSource]['product_id'] = null; $daneMagazynu[$idxSource]['quantity'] = 0; } if (($daneMagazynu[$idxTarget]['status'] ?? 'empty') === 'empty') { $daneMagazynu[$idxTarget]['product_id'] = $productIdToMove; $daneMagazynu[$idxTarget]['quantity'] = $quantityToMove; $daneMagazynu[$idxTarget]['status'] = 'occupied'; } else { $daneMagazynu[$idxTarget]['quantity'] = (int)($daneMagazynu[$idxTarget]['quantity'] ?? 0) + $quantityToMove; } if (zapiszDaneJson(WAREHOUSE_JSON_PATH, $daneMagazynu, 'magazyn')) { http_response_code(200); echo json_encode(['success' => true, 'message' => "Przesunięto {$quantityToMove} szt. produktu ID {$productIdToMove} z {$sourceLocationId} do {$targetLocationId}."]); }},
        '/zwroty' => function() {
            $inputData = json_decode(file_get_contents('php://input'), true);
            if ($inputData === null || !isset($inputData['order_id'], $inputData['product_id'], $inputData['quantity'], $inputData['reason'], $inputData['status'])) {
                http_response_code(400); echo json_encode(['error' => 'Nieprawidłowe dane. Wymagane pola: order_id, product_id, quantity, reason, status.']); exit;
            }
            $daneZwrotow = wczytajLubZakoncz(RETURNS_JSON_PATH, 'zwroty'); $maxId = 0; foreach ($daneZwrotow as $z) if (isset($z['id']) && $z['id'] > $maxId) $maxId = $z['id'];
            $noweId = $maxId + 1;
            $nowyZwrot = [
                'id' => $noweId, 'order_id' => (int)$inputData['order_id'], 'product_id' => (int)$inputData['product_id'],
                'quantity' => (int)$inputData['quantity'], 'reason' => trim($inputData['reason']), 'status' => trim($inputData['status']), 
                'return_date' => date('Y-m-d H:i:s'), 'warehouse_location_id' => $inputData['warehouse_location_id'] ?? null 
            ];
            $daneZwrotow[] = $nowyZwrot;
            if (zapiszDaneJson(RETURNS_JSON_PATH, $daneZwrotow, 'zwroty')) {
                if (isset($nowyZwrot['warehouse_location_id']) && $nowyZwrot['quantity'] > 0 && !empty($nowyZwrot['warehouse_location_id']) && 
                    (strtolower($nowyZwrot['status']) === 'zwrot przyjęty' || strtolower($nowyZwrot['status']) === 'reklamacja uznana')) { 
                    $daneMagazynu = wczytajLubZakoncz(WAREHOUSE_JSON_PATH, 'magazyn');
                    $locationId = $nowyZwrot['warehouse_location_id']; $productId = $nowyZwrot['product_id']; $quantity = $nowyZwrot['quantity'];
                    $magazynZaktualizowany = false;
                    foreach($daneMagazynu as $i => &$lok) { 
                        if (isset($lok['id']) && $lok['id'] === $locationId) {
                            if (($lok['status'] ?? 'empty') === 'empty' || (($lok['status'] ?? 'empty') === 'occupied' && ($lok['product_id'] ?? null) === $productId)) {
                                $lok['product_id'] = $productId; $lok['quantity'] = (int)($lok['quantity'] ?? 0) + $quantity; $lok['status'] = 'occupied';
                                $magazynZaktualizowany = true; break; 
                            }
                        }
                    }
                    if ($magazynZaktualizowany && !zapiszDaneJson(WAREHOUSE_JSON_PATH, $daneMagazynu, 'magazyn')) { /* Błąd zapisu magazynu */ }
                }
                http_response_code(201); echo json_encode(['success' => true, 'message' => 'Zgłoszenie zwrotu/reklamacji dodane.', 'return' => $nowyZwrot]);
            }
        },
    ],
    'PUT' => [
        '/produkty/(\d+)' => function($matches) { 
            $productId = (int)$matches[1]; 
            $inputData = json_decode(file_get_contents('php://input'), true); 
            if ($inputData === null || empty($inputData)) { 
                http_response_code(400); echo json_encode(['error' => 'Brak danych do aktualizacji.']); exit; 
            } 
            $daneProduktow = wczytajLubZakoncz(PRODUCTS_JSON_PATH, 'produkty'); 
            $idx = null; foreach($daneProduktow as $i => $p) if(isset($p['id']) && $p['id'] === $productId) {$idx = $i; break;} 
            if ($idx === null) { 
                http_response_code(404); echo json_encode(['error' => "Produkt ID {$productId} nie znaleziony"]); exit; 
            } 
            $allowedFields = [
                'name', 'producent', 'tn_numer_katalogowy', 'category', 'desc', 'spec', 'params', 'vehicle', 
                'price', 'shipping', 'stock', 'tn_jednostka_miary', 'warehouse', 'image',
                'marka', 'model', 'typ_pojazdu', 'pojemnosc_silnika', 'moc_km', 'moc_kw', 'rok_produkcji'
            ];
            foreach ($inputData as $key => $value) { 
                if ($key === 'id' && (int)$value !== $productId) continue; 
                if (in_array($key, $allowedFields)) { 
                    if (in_array($key, ['price', 'shipping', 'pojemnosc_silnika'])) {
                        $daneProduktow[$idx][$key] = ($value !== '' && $value !== null) ? (float)$value : null;
                    } elseif (in_array($key, ['stock', 'moc_km', 'moc_kw', 'rok_produkcji'])) {
                        $daneProduktow[$idx][$key] = ($value !== '' && $value !== null) ? (int)$value : null;
                    } else {
                        $daneProduktow[$idx][$key] = $value; 
                    }
                }
            } 
            if (zapiszDaneJson(PRODUCTS_JSON_PATH, $daneProduktow, 'produkty')) { 
                echo json_encode(['success' => true, 'message' => "Produkt #{$productId} zaktualizowany.", 'product' => $daneProduktow[$idx]]); 
            }
        },
        '/zamowienia/(\d+)' => function($matches) { $orderId = (int)$matches[1]; $inputData = json_decode(file_get_contents('php://input'), true); if ($inputData === null || empty($inputData)) { http_response_code(400); echo json_encode(['error' => 'Brak danych do aktualizacji zamówienia.']); exit; } $daneZamowien = wczytajLubZakoncz(ORDERS_JSON_PATH, 'zamówienia'); $idx = null; foreach($daneZamowien as $i => $z) if(isset($z['id']) && $z['id'] === $orderId) {$idx = $i; break;} if ($idx === null) { http_response_code(404); echo json_encode(['error' => "Zamówienie ID {$orderId} nie znalezione"]); exit; } $dozwolonePola = ['product_id', 'buyer_name', 'buyer_daneWysylki', 'quantity', 'courier_id', 'tracking_number', 'processed', 'tn_status_platnosci']; foreach ($inputData as $key => $value) { if (in_array($key, $dozwolonePola)) { if ($key === 'product_id' || $key === 'quantity') $daneZamowien[$idx][$key] = (int)$value; elseif ($key === 'processed') $daneZamowien[$idx][$key] = (bool)$value; else $daneZamowien[$idx][$key] = $value; }} $daneZamowien[$idx]['date_updated'] = date("Y-m-d H:i:s"); if (zapiszDaneJson(ORDERS_JSON_PATH, $daneZamowien, 'zamówienia')) { echo json_encode(['success' => true, 'message' => "Zamówienie #{$orderId} zaktualizowane.", 'order' => $daneZamowien[$idx]]); }},
        '/profil' => function() {
            $inputData = json_decode(file_get_contents('php://input'), true); if ($inputData === null || empty($inputData)) { http_response_code(400); echo json_encode(['error' => 'Brak danych do aktualizacji profilu.']); exit; }
            $users = wczytajLubZakoncz(USERS_JSON_PATH, 'użytkownicy'); if (empty($users)) { http_response_code(500); echo json_encode(['error' => 'Brak pliku użytkowników lub jest on pusty.']); exit; }
            $userIndex = -1; foreach ($users as $index => $user) if (isset($user['username']) && $user['username'] === 'admin') {$userIndex = $index; break;}
            if ($userIndex === -1 && !empty($users)) $userIndex = 0;
            if ($userIndex === -1) { http_response_code(404); echo json_encode(['error' => 'Nie znaleziono użytkownika do aktualizacji.']); exit; }
            $allowedFields = ['tn_imie_nazwisko', 'email', 'phone', 'avatar']; $updated = false;
            foreach ($allowedFields as $field) if (array_key_exists($field, $inputData)) { $users[$userIndex][$field] = trim($inputData[$field]); $updated = true; }
            if (!$updated) { http_response_code(400); echo json_encode(['error' => 'Brak pól do zaktualizowania lub nieprawidłowe pola.']); exit; }
            if (zapiszDaneJson(USERS_JSON_PATH, $users, 'użytkownicy')) { $updatedUser = $users[$userIndex]; unset($updatedUser['password_hash']); echo json_encode(['success' => true, 'message' => 'Profil zaktualizowany.', 'user' => $updatedUser]); }
        },
        // Endpoint do pełnej edycji zwrotu/reklamacji
        '/zwroty/(\d+)' => function($matches) {
            $returnId = (int)$matches[1];
            $inputData = json_decode(file_get_contents('php://input'), true);

            if ($inputData === null || empty($inputData)) {
                http_response_code(400);
                echo json_encode(['error' => 'Brak danych do aktualizacji zwrotu/reklamacji.']);
                exit;
            }

            $daneZwrotow = wczytajLubZakoncz(RETURNS_JSON_PATH, 'zwroty');
            $idx = null;
            $staryStatus = null;
            foreach($daneZwrotow as $i => $z) {
                if(isset($z['id']) && $z['id'] === $returnId) {
                    $idx = $i;
                    $staryStatus = $z['status'] ?? null;
                    break;
                }
            }

            if ($idx === null) {
                http_response_code(404);
                echo json_encode(['error' => "Zwrot/Reklamacja ID {$returnId} nie znaleziony/a."]);
                exit;
            }

            // Pola dozwolone do aktualizacji (order_id i product_id nie powinny być zmieniane)
            $allowedFieldsToUpdate = ['quantity', 'reason', 'status', 'warehouse_location_id'];
            $updated = false;

            foreach ($allowedFieldsToUpdate as $field) {
                if (array_key_exists($field, $inputData)) {
                    if ($field === 'quantity') {
                        $daneZwrotow[$idx][$field] = (int)$inputData[$field];
                    } else {
                        $daneZwrotow[$idx][$field] = trim($inputData[$field]);
                    }
                    $updated = true;
                }
            }
            
            if (!$updated) {
                 http_response_code(400);
                 echo json_encode(['error' => 'Brak pól do zaktualizowania lub nieprawidłowe pola.']);
                 exit;
            }
            
            $nowyStatus = $daneZwrotow[$idx]['status'];
            $magazynZmodyfikowany = false;

            // Logika aktualizacji magazynu, jeśli status zmienia się na akceptujący zwrot/reklamację
            $acceptedStatuses = ['zwrot przyjęty', 'reklamacja uznana'];
            if (in_array(strtolower($nowyStatus), $acceptedStatuses) && (!in_array(strtolower($staryStatus), $acceptedStatuses) || $inputData['quantity'] !== $daneZwrotow[$idx]['quantity_before_edit'] )) { // Sprawdź czy status się zmienił na akceptujący lub ilość
                if (isset($daneZwrotow[$idx]['warehouse_location_id']) && !empty($daneZwrotow[$idx]['warehouse_location_id']) && $daneZwrotow[$idx]['quantity'] > 0) {
                    $daneMagazynu = wczytajLubZakoncz(WAREHOUSE_JSON_PATH, 'magazyn');
                    $locationId = $daneZwrotow[$idx]['warehouse_location_id'];
                    $productId = $daneZwrotow[$idx]['product_id'];
                    $quantityToReturn = $daneZwrotow[$idx]['quantity'];
                    
                    // Jeśli edytujemy ilość, musimy cofnąć poprzednią zmianę i zastosować nową
                    // To jest uproszczenie; w realnym systemie potrzebna byłaby bardziej zaawansowana transakcyjność lub logowanie zmian
                    // Na razie zakładamy, że jeśli status jest akceptujący, to aktualizujemy stan o *nową* ilość.

                    foreach($daneMagazynu as $iMag => &$lok) {
                        if (isset($lok['id']) && $lok['id'] === $locationId) {
                            if (($lok['status'] ?? 'empty') === 'empty' || (($lok['status'] ?? 'empty') === 'occupied' && ($lok['product_id'] ?? null) === $productId)) {
                                $lok['product_id'] = $productId;
                                // Aktualizujemy o różnicę, jeśli to edycja ilości przy już przyjętym zwrocie,
                                // lub dodajemy całość, jeśli status dopiero się zmienia na przyjęty.
                                // Dla uproszczenia, jeśli status jest akceptujący, po prostu ustawiamy nową ilość.
                                // Bardziej zaawansowana logika wymagałaby śledzenia poprzedniej zwróconej ilości.
                                // Na razie, jeśli status już był akceptujący, to jest to problematyczne.
                                // Załóżmy, że update magazynu dzieje się tylko przy zmianie statusu na akceptujący.
                                if (!in_array(strtolower($staryStatus), $acceptedStatuses)) {
                                     $lok['quantity'] = (int)($lok['quantity'] ?? 0) + $quantityToReturn;
                                } // Jeśli status już był akceptujący, a zmieniamy tylko np. powód, nie ruszamy magazynu.
                                  // Edycja ilości przy już przyjętym zwrocie wymagałaby bardziej skomplikowanej logiki.
                                $lok['status'] = 'occupied';
                                $magazynZmodyfikowany = true;
                                break;
                            }
                        }
                    }
                    if ($magazynZmodyfikowany && !zapiszDaneJson(WAREHOUSE_JSON_PATH, $daneMagazynu, 'magazyn')) { /* Błąd zapisu magazynu */ }
                }
            }


            if (zapiszDaneJson(RETURNS_JSON_PATH, $daneZwrotow, 'zwroty')) {
                echo json_encode(['success' => true, 'message' => "Zwrot/Reklamacja #{$returnId} zaktualizowany/a.", 'return' => $daneZwrotow[$idx]]);
            }
        },
        '/zwroty/(\d+)/zmien_status' => function($matches) { // Ten endpoint może być teraz mniej potrzebny, jeśli PUT /zwroty/{id} obsługuje zmianę statusu
            $returnId = (int)$matches[1]; $inputData = json_decode(file_get_contents('php://input'), true); if ($inputData === null || !isset($inputData['nowy_status'])) { http_response_code(400); echo json_encode(['error' => 'Brak pola "nowy_status".']); exit; }
            $nowyStatus = trim($inputData['nowy_status']); $daneZwrotow = wczytajLubZakoncz(RETURNS_JSON_PATH, 'zwroty'); $idx = null; $staryStatus = null;
            foreach($daneZwrotow as $i => $z) if(isset($z['id']) && $z['id'] === $returnId) {$idx = $i; $staryStatus = $z['status'] ?? null; break;}
            if ($idx === null) { http_response_code(404); echo json_encode(['error' => "Zwrot ID {$returnId} nie znaleziony"]); exit; }
            
            $daneZwrotow[$idx]['status'] = $nowyStatus;
            $magazynZmodyfikowany = false;
            $acceptedStatuses = ['zwrot przyjęty', 'reklamacja uznana'];

            if (in_array(strtolower($nowyStatus), $acceptedStatuses) && !in_array(strtolower($staryStatus), $acceptedStatuses)) {
                if (isset($daneZwrotow[$idx]['warehouse_location_id']) && !empty($daneZwrotow[$idx]['warehouse_location_id']) && $daneZwrotow[$idx]['quantity'] > 0) {
                    $daneMagazynu = wczytajLubZakoncz(WAREHOUSE_JSON_PATH, 'magazyn');
                    $locationId = $daneZwrotow[$idx]['warehouse_location_id'];
                    $productId = $daneZwrotow[$idx]['product_id'];
                    $quantityToReturn = $daneZwrotow[$idx]['quantity'];
                    foreach($daneMagazynu as $iMag => &$lok) {
                        if (isset($lok['id']) && $lok['id'] === $locationId) {
                            if (($lok['status'] ?? 'empty') === 'empty' || (($lok['status'] ?? 'empty') === 'occupied' && ($lok['product_id'] ?? null) === $productId)) {
                                $lok['product_id'] = $productId;
                                $lok['quantity'] = (int)($lok['quantity'] ?? 0) + $quantityToReturn;
                                $lok['status'] = 'occupied';
                                $magazynZmodyfikowany = true;
                                break;
                            }
                        }
                    }
                    if ($magazynZmodyfikowany && !zapiszDaneJson(WAREHOUSE_JSON_PATH, $daneMagazynu, 'magazyn')) { /* Błąd zapisu magazynu */ }
                }
            }

            if (zapiszDaneJson(RETURNS_JSON_PATH, $daneZwrotow, 'zwroty')) { echo json_encode(['success' => true, 'message' => "Status zwrotu #{$returnId} zmieniony.", 'return' => $daneZwrotow[$idx]]); }
        },
    ],
    'DELETE' => [
        '/produkty/(\d+)' => function($matches) { $productId = (int)$matches[1]; $daneProduktow = wczytajLubZakoncz(PRODUCTS_JSON_PATH, 'produkty'); $idx = null; foreach($daneProduktow as $i => $p) if(isset($p['id']) && $p['id'] === $productId) {$idx = $i; break;} if ($idx === null) { http_response_code(404); echo json_encode(['error' => "Produkt ID {$productId} nie znaleziony"]); exit; } array_splice($daneProduktow, $idx, 1); if (zapiszDaneJson(PRODUCTS_JSON_PATH, $daneProduktow, 'produkty')) { echo json_encode(['success' => true, 'message' => "Produkt #{$productId} usunięty."]); }},
        '/zamowienia/(\d+)' => function($matches) { $orderId = (int)$matches[1]; $daneZamowien = wczytajLubZakoncz(ORDERS_JSON_PATH, 'zamówienia'); $idx = null; foreach($daneZamowien as $i => $z) if(isset($z['id']) && $z['id'] === $orderId) {$idx = $i; break;} if ($idx === null) { http_response_code(404); echo json_encode(['error' => "Zamówienie ID {$orderId} nie znalezione"]); exit; } array_splice($daneZamowien, $idx, 1); if (zapiszDaneJson(ORDERS_JSON_PATH, $daneZamowien, 'zamówienia')) { echo json_encode(['success' => true, 'message' => "Zamówienie #{$orderId} usunięte."]); }},
        '/zwroty/(\d+)' => function($matches) {
            $returnId = (int)$matches[1]; $daneZwrotow = wczytajLubZakoncz(RETURNS_JSON_PATH, 'zwroty'); $idx = null;
            foreach($daneZwrotow as $i => $z) if(isset($z['id']) && $z['id'] === $returnId) {$idx = $i; break;}
            if ($idx === null) { http_response_code(404); echo json_encode(['error' => "Zwrot ID {$returnId} nie znaleziony"]); exit; }
            array_splice($daneZwrotow, $idx, 1);
            if (zapiszDaneJson(RETURNS_JSON_PATH, $daneZwrotow, 'zwroty')) { echo json_encode(['success' => true, 'message' => "Zwrot #{$returnId} usunięty."]); }
        },
    ],
];

// --- Dopasowanie i wykonanie routingu ---
$routeFound = false;
if (isset($routes[$requestMethod])) {
    foreach ($routes[$requestMethod] as $pattern => $handler) {
        if (preg_match('#^' . $pattern . '(?:\?.*)?$#', $endpointPath, $matches)) { 
            call_user_func($handler, $matches);
            $routeFound = true;
            break;
        }
    }
}

if (!$routeFound) {
    if (isset($routes[$requestMethod])) {
        http_response_code(404);
        echo json_encode(['error' => 'Nie znaleziono zasobu API dla ścieżki: ' . htmlspecialchars($endpointPath)]);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Niedozwolona metoda HTTP.']);
    }
}
exit;

?>
