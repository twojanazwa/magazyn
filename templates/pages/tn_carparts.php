<?php
// templates/pages/tn_carparts.php
/**
 * Szablon strony wyświetlającej listę unikalnych pojazdów oraz funkcję wyszukiwania produktów po pojeździe.
 *
 * TEN PLIK ZAWIERA ZAGNIEŻDŻONĄ LOGIKĘ POBIERANIA (Z products.json), PRZETWARZANIA,
 * DODAWANIA (DO vehicles.json), WYSZUKIWANIA PRODUKTÓW I (BAZOWEJ) EDYCJI/USUWANIA DANYCH (tylko placeholdery).
 * JEST TO ROZWIĄZANIE NIEZGODNE Z ZALECANYMI PRAKTYKAMI (np. wzorzec MVC).
 * ZALECANE JEST PRZENIESIENIE LOGIKI DANYCH DO KONTROLERA (np. index.php).
 *
 * Oczekuje (teoretycznie) zmiennych z index.php, ale w tej wersji sam pobiera i modyfikuje dane:
 * @var array $tn_lista_pojazdow Lista unikalnych pojazdów (Make/Model => [wersje]).
 *
 * Zakłada dostępność funkcji pomocniczych:
 * tn_generuj_url() (jeśli używana w linkach)
 * tn_ustaw_komunikat_flash() (jeśli używana do komunikatów)
 */

// --- ZAGNIEŻDŻONA LOGIKA POBIERANIA, PRZETWARZANIA I ZAPISU DANYCH (PHP) ---
// !!! UWAGA: Ten blok kodu powinien być w kontrolerze, nie w szablonie! !!!

// Ścieżka do pliku JSON z danymi produktów (do odczytu)
$products_json_file_path = __DIR__ . '/../../TNbazaDanych/products.json'; // !!! DOSTOSUJ ŚCIEŻKĘ !!!

// Ścieżka do pliku JSON z nowo dodanymi pojazdami (do zapisu)
$vehicles_json_file_path = __DIR__ . '/../../TNbazaDanych/vehicles.json'; // !!! DOSTOSUJ ŚCIEŻKĘ !!!


// Funkcja do odczytu danych z pliku JSON
function readJsonFile($file_path) {
    if (file_exists($file_path) && is_readable($file_path)) {
        $file_content = file_get_contents($file_path);
        if ($file_content === false) {
            error_log("Błąd odczytu pliku JSON: " . $file_path);
            return null;
        }
        $decoded_data = json_decode($file_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Błąd dekodowania JSON w pliku " . $file_path . ": " . json_last_error_msg());
            return null;
        }
        return is_array($decoded_data) ? $decoded_data : []; // Zwróć tablicę lub pustą tablicę
    }
    // Jeśli plik nie istnieje, zwróć pustą tablicę zamiast null (przydatne przy odczycie vehicles.json)
    if (!file_exists($file_path)) {
        return [];
    }
    error_log("Plik JSON nie znaleziony lub brak uprawnień do odczytu: " . $file_path);
    return null; // Zwróć null tylko w przypadku błędu odczytu istniejącego pliku
}

// Funkcja do zapisu danych do pliku JSON
function writeJsonFile($file_path, $data) {
    // Upewnij się, że katalog istnieje
    $dir = dirname($file_path);
    if (!is_dir($dir)) {
        // Próba utworzenia katalogu z uprawnieniami 0775
        if (!mkdir($dir, 0775, true)) {
             error_log("Błąd: Nie można utworzyć katalogu dla pliku JSON: " . $dir);
             return false;
        }
    }

    // Sprawdź, czy plik jest zapisywalny lub czy katalog jest zapisywalny jeśli plik nie istnieje
    if (file_exists($file_path) && !is_writable($file_path)) {
         error_log("Brak uprawnień do zapisu do pliku JSON: " . $file_path);
         return false;
    }
     // Jeśli plik nie istnieje, sprawdź czy katalog jest zapisywalny
    if (!file_exists($file_path) && !is_writable($dir)) {
         error_log("Brak uprawnień do zapisu w katalogu docelowym dla pliku JSON: " . $dir);
         return false;
    }


    $json_content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json_content === false) {
        error_log("Błąd kodowania danych do formatu JSON: " . json_last_error_msg());
        return false;
    }

    // Użyj blokady pliku podczas zapisu (podstawowe zabezpieczenie przed jednoczesnym zapisem)
    // Dodaj opcję FILE_APPEND jeśli chcesz dodawać na końcu, ale w tym przypadku nadpisujemy całą tablicę
    $put_result = file_put_contents($file_path, $json_content, LOCK_EX);

    if ($put_result === false) {
        error_log("Błąd zapisu do pliku JSON: " . $file_path);
        return false;
    }

    return true;
}


// --- Obsługa formularza dodawania nowego pojazdu (zapis do vehicles.json) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_vehicle') {
    // Pobierz dane z formularza (podstawowe pobranie, bez pełnej walidacji!)
    $new_vehicle_data = [
        'make'       => trim($_POST['make'] ?? ''),
        'model'      => trim($_POST['model'] ?? ''),
        'version_name' => trim($_POST['version_name'] ?? ''),
        'engine_code' => trim($_POST['engine_code'] ?? ''),
        'capacity_ccm' => trim($_POST['capacity_ccm'] ?? ''),
        'power_kw'   => trim($_POST['power_kw'] ?? ''),
        'power_hp'   => trim($_POST['power_hp'] ?? ''),
        'year_start' => trim($_POST['year_start'] ?? ''),
        'year_end'   => trim($_POST['year_end'] ?? ''),
        // Dodaj inne pola, jeśli są w formularzu
    ];

    // Podstawowa walidacja - sprawdź, czy wymagane pola nie są puste
    if (empty($new_vehicle_data['make']) || empty($new_vehicle_data['model']) || empty($new_vehicle_data['version_name'])) {
         if (function_exists('tn_ustaw_komunikat_flash')) {
            tn_ustaw_komunikat_flash('Proszę wypełnić wymagane pola (Marka, Model, Nazwa Wersji).', 'warning');
         }
    } else {
        // Odczytaj istniejące dane z vehicles.json
        // Oczekujemy tablicy stringów w vehicles.json
        $existing_vehicles_list = readJsonFile($vehicles_json_file_path);

        if ($existing_vehicles_list !== null) {
            // Utwórz nowy wpis pojazdu w formacie tekstowym, zgodnym z parsowaniem
            // Format: Marka Model:
            //     Wersja (Kod), Pojemność, kW, KM, RokPoczątkowy-RokKońcowy
            $new_vehicle_text_format = $new_vehicle_data['make'] . ' ' . $new_vehicle_data['model'] . ":\n";
            $new_vehicle_text_format .= "\t" . $new_vehicle_data['version_name'];
            if (!empty($new_vehicle_data['engine_code'])) {
                 $new_vehicle_text_format .= ' (' . $new_vehicle_data['engine_code'] . ')';
            }
            $new_vehicle_text_format .= ', ';
            $new_vehicle_text_format .= ($new_vehicle_data['capacity_ccm'] ?: '-') . ', ';
            $new_vehicle_text_format .= ($new_vehicle_data['power_kw'] ?: '-') . ', ';
            $new_vehicle_text_format .= ($new_vehicle_data['power_hp'] ?: '-') . ', ';
            $new_vehicle_text_format .= ($new_vehicle_data['year_start'] ?: '-') . '-';
            $new_vehicle_text_format .= ($new_vehicle_data['year_end'] ?: 'nadal'); // Użyj 'nadal' jeśli rok końcowy pusty
            // Dodaj inne dane w formacie tekstowym, jeśli są

            // Dodaj nowy string pojazdu do tablicy
            $existing_vehicles_list[] = $new_vehicle_text_format;

            // Zapisz zaktualizowaną tablicę stringów z powrotem do pliku vehicles.json
            if (writeJsonFile($vehicles_json_file_path, $existing_vehicles_list)) {
                 if (function_exists('tn_ustaw_komunikat_flash')) {
                    tn_ustaw_komunikat_flash('Pojazd został pomyślnie dodany do bazy pojazdów.', 'success');
                 }
                 // Opcjonalnie: przekieruj, aby uniknąć ponownego wysłania formularza przy odświeżeniu
                 // header('Location: ' . $_SERVER['REQUEST_URI']);
                 // exit;
            } else {
                 if (function_exists('tn_ustaw_komunikat_flash')) {
                    tn_ustaw_komunikat_flash('Błąd podczas zapisu pojazdu do pliku bazy pojazdów.', 'danger');
                 }
            }
        } else {
             if (function_exists('tn_ustaw_komunikat_flash')) {
                tn_ustaw_komunikat_flash('Nie można odczytać danych z pliku bazy pojazdów do dodania.', 'danger');
            }
        }
    }
}

// --- Koniec obsługi formularza dodawania ---


// --- Logika pobierania i parsowania danych z pliku products.json (PHP) ---
// Ta część kodu PHP pobiera dane z products.json do wyświetlenia na liście.
// Nowo dodane pojazdy do vehicles.json NIE SĄ tutaj uwzględniane.

$all_products = readJsonFile($products_json_file_path); // Pobierz wszystkie produkty

$all_vehicles_raw_from_products = []; // Tablica na surowe stringi z danymi pojazdów z pola 'vehicle' z products.json

if ($all_products !== null) {
    // Zbieraj surowe dane pojazdów z pola 'vehicle' z każdego produktu
    if (is_array($all_products)) {
        foreach ($all_products as $product) {
            if ((is_array($product) || is_object($product)) && isset($product['vehicle']) && is_string($product['vehicle']) && !empty($product['vehicle'])) {
                $all_vehicles_raw_from_products[] = $product['vehicle'];
            }
        }
    }
} else {
    // Komunikat o błędzie odczytu pliku JSON jest już logowany w funkcji readJsonFile
     if (function_exists('tn_ustaw_komunikat_flash')) {
        // Komunikat mógł być już ustawiony w funkcji readJsonFile
        // Można dodać dodatkowy komunikat, jeśli to konieczne
     }
}

// --- Logika parsowania surowych danych pojazdów (PHP) ---
// Ta część kodu PHP przetwarza surowy tekst ($all_vehicles_raw_from_products) zebrany z pól 'vehicle' z products.json.

// Połącz wszystkie surowe dane pojazdów w jeden długi string
$combined_vehicle_info_raw = implode("\n", $all_vehicles_raw_from_products);
$unique_vehicles_map = []; // Mapa do przechowywania unikalnych pojazdów (Marka/Model => [wersje])
$currentMakeModel = null; // Śledzenie aktualnej Marki/Modelu

// Wyrażenie regularne do parsowania wierszy wersji pojazdu
// Uelastyczniono rok początkowy (opcjonalne .MM) i rok końcowy (dowolny tekst)
$versionRegex = '/^\s*(.+?)\s+\(([^)]+?)\)\s*,\s*(\d*)\s*,\s*(\d*)\s*,\s*(\d*)\s*,\s*(\d{4}(?:\.\d{2})?)\s*-\s*(.*?)\s*$/';

// Podziel surowe dane na pojedyncze wiersze i przetwórz
$lines = explode("\n", trim($combined_vehicle_info_raw));

foreach ($lines as $line) {
    $trimmedLine = trim($line);
    if (empty($trimmedLine)) continue;

    // Sprawdź, czy to wiersz Marka/Model (kończy się dwukropkiem i nie jest wcięty)
    if (str_ends_with($trimmedLine, ':') && !preg_match('/^\s/', $line)) {
        $currentMakeModel = htmlspecialchars(trim(substr($trimmedLine, 0, -1)), ENT_QUOTES, 'UTF-8');
        if (!isset($unique_vehicles_map[$currentMakeModel])) {
            $unique_vehicles_map[$currentMakeModel] = [];
        }
    }
    // Sprawdź, czy to wiersz wersji pojazdu (jest wcięty i pasuje do wyrażenia regularnego)
    // Przetwarzaj wiersze wersji tylko, jeśli wcześniej zidentyfikowano Markę/Model
    elseif ($currentMakeModel !== null && preg_match('/^\s+/', $line) && preg_match($versionRegex, $line, $matches)) {
        // Zdekoduj i sformatuj dane wersji z dopasowań regex
        $version_data = [
            'name'       => htmlspecialchars(trim($matches[1] ?? ''), ENT_QUOTES, 'UTF-8'),
            'code'       => htmlspecialchars(trim($matches[2] ?? ''), ENT_QUOTES, 'UTF-8') ?: '-',
            'capacity'   => htmlspecialchars(trim($matches[3] ?? ''), ENT_QUOTES, 'UTF-8') ?: '-',
            'kw'         => htmlspecialchars(trim($matches[4] ?? ''), ENT_QUOTES, 'UTF-8') ?: '-',
            'hp'         => htmlspecialchars(trim($matches[5] ?? ''), ENT_QUOTES, 'UTF-8') ?: '-',
            'year_start' => htmlspecialchars(trim($matches[6] ?? ''), ENT_QUOTES, 'UTF-8') ?: '-',
            'year_end'   => htmlspecialchars(trim($matches[7] ?? ''), ENT_QUOTES, 'UTF-8') ?: 'nadal',
            // 'extra_detail' => htmlspecialchars(trim($matches[8] ?? ''), ENT_QUOTES, 'UTF-8'), // Jeśli regex ma grupę 8
        ];

        // Utwórz unikalny klucz dla każdej wersji
        $version_key = implode('|', $version_data);

        // Dodaj wersję tylko, jeśli nie jest duplikatem
        $is_duplicate = false;
        if (isset($unique_vehicles_map[$currentMakeModel])) {
             foreach($unique_vehicles_map[$currentMakeModel] as $existing_version) {
                 if (implode('|', $existing_version) === $version_key) {
                     $is_duplicate = true;
                     break;
                 }
             }
        }

        if (!$is_duplicate) {
             $unique_vehicles_map[$currentMakeModel][] = $version_data;
        }
    }
    // Opcjonalnie: Zaloguj wiersze, które nie pasują do oczekiwanego formatu Wewnątrz bloku Marka/Model
    // elseif ($currentMakeModel !== null && !empty($trimmedLine)) {
    //    // error_log("Linia w bloku " . $currentMakeModel . " nie pasuje do wzorca: " . $line);
    // }
}

// Posortuj wersje alfabetycznie według nazwy
foreach($unique_vehicles_map as $makeModelKey => &$versions) {
    if (!empty($versions) && is_array($versions)) {
        usort($versions, fn($a, $b) => strcmp($a['name'], $b['name']));
    }
}
unset($versions);

// Przypisz przetworzone dane do zmiennej używanej w widoku
$vehiclesGroupedByMakeModel = $unique_vehicles_map;

// --- KONIEC ZAGNIEŻDŻONEJ LOGIKI POBIERANIA I PARSOWANIA POJAZDÓW Z PRODUCTS.JSON ---


// --- Logika wyszukiwania produktów po pojeździe (PHP) ---
$search_query = trim($_GET['search_vehicle'] ?? ''); // Pobierz frazę wyszukiwania z parametru URL
$filtered_products = []; // Tablica na produkty pasujące do wyszukiwania
$is_searching = !empty($search_query); // Flaga informująca, czy trwa wyszukiwanie

if ($is_searching && $all_products !== null) {
    $search_terms = explode(' ', $search_query); // Podziel frazę na pojedyncze słowa

    foreach ($all_products as $product) {
        // Sprawdź, czy produkt ma pole 'vehicle' i jest stringiem
        if (isset($product['vehicle']) && is_string($product['vehicle']) && !empty($product['vehicle'])) {
            $vehicle_data_string = $product['vehicle'];
            $match = true; // Załóż, że produkt pasuje, dopóki nie znajdziesz słowa, które nie pasuje

            // Sprawdź, czy wszystkie słowa z frazy wyszukiwania występują w stringu danych pojazdu (case-insensitive)
            foreach ($search_terms as $term) {
                if (!empty($term) && stripos($vehicle_data_string, $term) === false) {
                    $match = false; // Jeśli któreś słowo nie pasuje, cały produkt nie pasuje
                    break;
                }
            }

            // Jeśli wszystkie słowa pasują, dodaj produkt do przefiltrowanej listy
            if ($match) {
                $filtered_products[] = $product;
            }
        }
        // Opcjonalnie: Można dodać wyszukiwanie w innych polach produktu, jeśli są dostępne
        // elseif (isset($product['name']) && stripos($product['name'], $search_query) !== false) {
        //     $filtered_products[] = $product;
        // }
    }
}
// --- Koniec logiki wyszukiwania ---


// --- Sprawdzenie danych dla widoku (teraz $vehiclesGroupedByMakeModel jest już wypełnione przez PHP) ---
// Ta sekcja może pozostać, aby obsłużyć przypadek, gdyby mimo wszystko dane były puste
// if (empty($vehiclesGroupedByMakeModel)) {
   // Komunikat flash o braku danych może być już ustawiony
// }

?>

<div class="container-fluid px-lg-4 py-4">
    <div class="card shadow-sm tn-vehicles-list mb-4">
        <div class="card-header bg-light py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="bi bi-tools me-2"></i>Lista Części Samochodowych</h5> <?php // Zmieniono nagłówek ?>
                <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#addVehicleFormCollapse" aria-expanded="false" aria-controls="addVehicleFormCollapse">
                    <i class="bi bi-plus-circle me-1"></i> Dodaj nowy pojazd do bazy
                </button>
            </div>
        </div>
        <div class="card-body p-4">

            <div class="collapse mb-4" id="addVehicleFormCollapse">
                <div class="card card-body bg-light">
                    <h6>Dodaj nowy pojazd do bazy pojazdów (vehicles.json):</h6>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add_vehicle">
                        <?php // Opcjonalnie: Pole na token CSRF ?>
                        <?php /*
                         // Jeśli używasz ochrony CSRF, odkomentuj poniższe pole
                         // i upewnij się, że zmienna $tn_token_csrf jest dostępna (np. przekazana z kontrolera)
                        <input type="hidden" name="tn_csrf_token" value="<?php echo htmlspecialchars($tn_token_csrf ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        */ ?>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="make" class="form-label">Marka <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="make" name="make" required>
                            </div>
                            <div class="col-md-4">
                                <label for="model" class="form-label">Model <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="model" name="model" required>
                            </div>
                             <div class="col-md-4">
                                <label for="version_name" class="form-label">Nazwa Wersji <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="version_name" name="version_name" required>
                            </div>
                            <div class="col-md-3">
                                <label for="engine_code" class="form-label">Kod Silnika</label>
                                <input type="text" class="form-control form-control-sm" id="engine_code" name="engine_code">
                            </div>
                            <div class="col-md-3">
                                <label for="capacity_ccm" class="form-label">Pojemność (cm³)</label>
                                <input type="text" class="form-control form-control-sm" id="capacity_ccm" name="capacity_ccm">
                            </div>
                            <div class="col-md-3">
                                <label for="power_kw" class="form-label">Moc (kW)</label>
                                <input type="text" class="form-control form-control-sm" id="power_kw" name="power_kw">
                            </div>
                             <div class="col-md-3">
                                <label for="power_hp" class="form-label">Moc (KM)</label>
                                <input type="text" class="form-control form-control-sm" id="power_hp" name="power_hp">
                            </div>
                             <div class="col-md-3">
                                <label for="year_start" class="form-label">Rok Początkowy</label>
                                <input type="text" class="form-control form-control-sm" id="year_start" name="year_start" placeholder="YYYY.MM">
                            </div>
                             <div class="col-md-3">
                                <label for="year_end" class="form-label">Rok Końcowy</label>
                                <input type="text" class="form-control form-control-sm" id="year_end" name="year_end" placeholder="YYYY.MM lub puste dla 'nadal'">
                            </div>
                            <?php // Dodaj inne pola formularza, jeśli potrzebne ?>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-plus-circle me-1"></i> Dodaj pojazd</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="mb-4">
                <h6>Wprowadź surowe dane pojazdów (format tekstowy):</h6>
                <textarea id="vehicleRawData" class="form-control font-monospace" rows="10" placeholder="Wklej lub wpisz dane pojazdów tutaj..."></textarea>
                <small class="form-text text-muted">Format: Marka Model:<br>&nbsp;&nbsp;&nbsp;&nbsp;Wersja (Kod), Pojemność, kW, KM, RokPoczątkowy-RokKońcowy</small>
            </div>

            <div class="mb-4">
                <h6>Podgląd parsowania:</h6>
                <div id="vehiclePreview" class="border p-3 rounded bg-light small" style="max-height: 300px; overflow-y: auto;">
                    <p class="text-muted">Podgląd pojawi się po wpisaniu danych.</p>
                </div>
            </div>

            <div class="mb-4">
                <h6>Wyszukaj produkty po pojeździe:</h6>
                <form method="GET" action="">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control form-control-sm" placeholder="Wyszukaj np. 'BMW E46 320d' lub 'Audi A4'" name="search_vehicle" value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>">
                        <button class="btn btn-outline-primary btn-sm" type="submit"><i class="bi bi-search"></i> Szukaj</button>
                         <?php if ($is_searching): // Pokaż przycisk "Wyczyść" tylko jeśli trwa wyszukiwanie ?>
                             <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i> Wyczyść</a>
                         <?php endif; ?>
                    </div>
                </form>
            </div>
            <?php if ($is_searching): // Sekcja z wynikami wyszukiwania ?>
                 <h6>Wyniki wyszukiwania dla "<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>":</h6>
                 <?php if (empty($filtered_products)): ?>
                     <div class="alert alert-warning" role="alert">
                         <i class="bi bi-exclamation-triangle me-2"></i> Brak produktów pasujących do podanych kryteriów pojazdu.
                     </div>
                 <?php else: ?>
                     <div class="list-group mb-4">
                         <?php foreach ($filtered_products as $product): ?>
                             <div class="list-group-item">
                                 <div class="fw-bold"><?php echo htmlspecialchars($product['name'] ?? 'Nazwa produktu', ENT_QUOTES, 'UTF-8'); ?></div>
                                 <div class="text-muted small">
                                     <?php
                                         // Wyświetl dane pojazdu powiązane z tym produktem
                                         echo "Pojazd: " . htmlspecialchars($product['vehicle'] ?? 'Brak danych pojazdu', ENT_QUOTES, 'UTF-8');
                                         // Możesz wyświetlić inne pola produktu, np. cenę, opis itp.
                                         // echo " | Cena: " . htmlspecialchars($product['price'] ?? '-', ENT_QUOTES, 'UTF-8');
                                     ?>
                                 </div>
                                 <?php
                                     // Opcjonalnie: Dodaj link do szczegółów produktu
                                     // $product_detail_url = function_exists('tn_generuj_url') ? htmlspecialchars(tn_generuj_url('product_detail', ['id' => $product['id'] ?? 0]), ENT_QUOTES, 'UTF-8') : '#';
                                     $product_detail_url = '#'; // Placeholder
                                 ?>
                                 <a href="<?php echo $product_detail_url; ?>" class="btn btn-outline-info btn-sm mt-2">Zobacz szczegóły produktu</a>
                             </div>
                         <?php endforeach; ?>
                     </div>
                 <?php endif; ?>
            <?php else: // Sekcja z pełną listą pojazdów (gdy nie ma wyszukiwania) ?>

                 <?php if (empty($vehiclesGroupedByMakeModel)): ?>
                     <div class="alert alert-info" role="alert">
                         <i class="bi bi-info-circle me-2"></i> Brak danych o pojazdach do wyświetlenia (z pliku products.json). Spróbuj wprowadzić dane powyżej lub dodać nowy pojazd.
                     </div>
                 <?php else: ?>
                     <h6>Lista pojazdów z pliku danych (products.json):</h6>
                     <?php // Wyświetlamy dane w prostej liście ?>
                     <div class="list-group">
                         <?php foreach ($vehiclesGroupedByMakeModel as $makeModel => $versions): ?>
                             <?php if (!empty($versions)): ?>
                                 <div class="list-group-item list-group-item-secondary fw-bold">
                                     <i class="bi bi-car-front me-2"></i><?php echo htmlspecialchars($makeModel, ENT_QUOTES, 'UTF-8'); ?>
                                     <span class="badge bg-secondary ms-2 rounded-pill"><?php echo count($versions); ?> wersji</span>
                                 </div>
                                 <div class="list-group list-group-flush">
                                     <?php foreach ($versions as $version): ?>
                                         <div class="list-group-item py-2 px-0 bg-transparent border-bottom-dashed d-flex justify-content-between align-items-center">
                                             <div class="flex-grow-1 me-3">
                                                 <div class="fw-bold">
                                                     <?php echo htmlspecialchars($version['name'] ?? 'Brak nazwy wersji', ENT_QUOTES, 'UTF-8'); ?>
                                                     <?php if(!empty($version['code']) && $version['code'] !== '-' && $version['code'] !== '0'): // Dodano sprawdzenie '0' ?>
                                                         <span class="font-monospace text-muted small">(<?php echo htmlspecialchars($version['code'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                                                     <?php endif; ?>
                                                 </div>
                                                 <div class="text-muted small">
                                                     Poj: <?php echo htmlspecialchars($version['capacity'] ?? '-', ENT_QUOTES, 'UTF-8'); ?> ccm³ |
                                                     Moc: <?php echo htmlspecialchars($version['kw'] ?? '-', ENT_QUOTES, 'UTF-8'); ?> kW (<?php echo htmlspecialchars($version['hp'] ?? '-', ENT_QUOTES, 'UTF-8'); ?> KM) |
                                                     Rocznik: <?php echo htmlspecialchars($version['year_start'] ?? '-', ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars($version['year_end'] ?? '...', ENT_QUOTES, 'UTF-8'); ?>
                                                      <?php if(isset($version['extra_detail']) && $version['extra_detail'] !== '-' && !empty($version['extra_detail'])): ?>
                                                         | Dodatkowo: <?php echo htmlspecialchars($version['extra_detail'], ENT_QUOTES, 'UTF-8'); ?>
                                                     <?php endif; ?>
                                                 </div>
                                             </div>
                                             <div class="flex-shrink-0">
                                                 <?php
                                                     // Edycja danych w products.json powinna odbywać się z poziomu podglądu produktu.
                                                     // Poniższy link jest placeholderem do hipotetycznej strony edycji produktu.
                                                     $edit_product_url = '#'; // Docelowo URL do strony edycji konkretnego produktu
                                                 ?>
                                                 <a href="<?php echo $edit_product_url; ?>" class="btn btn-outline-secondary btn-sm" title="Edytuj produkt zawierający ten pojazd"><i class="bi bi-box-arrow-up-right"></i></a>
                                                 <?php
                                                     // Usunięcie wpisu pojazdu z products.json w tym modelu jest złożone.
                                                     // Poniższy przycisk jest tylko placeholderem.
                                                 ?>
                                                 <button type="button" class="btn btn-outline-secondary btn-sm" title="Usuń ten wpis pojazdu (z products.json)" onclick="alert('Usunięcie wpisu pojazdu z products.json wymaga edycji pliku produktu. Ta funkcja nie jest dostępna tutaj.');"><i class="bi bi-trash"></i></button>
                                             </div>
                                         </div>
                                     <?php endforeach; ?>
                                 </div>
                             <?php endif; ?>
                         <?php endforeach; ?>
                     </div>
                 <?php endif; ?>

            <?php endif; // Koniec warunku $is_searching ?>


        </div> <?php // Koniec card-body ?>
    </div> <?php // Koniec card ?>
</div> <?php // Koniec container-fluid ?>

<?php // --- Style CSS specyficzne dla tej strony (opcjonalne) --- ?>
<style>
/* Style dla głównej listy pojazdów */
.list-group-item.border-bottom-dashed {
    border-bottom-style: dashed !important;
    border-bottom-width: 1px;
}

/* Styl dla nagłówka Marka/Model w prostej liście */
.list-group-item-secondary.fw-bold {
    background-color: var(--bs-secondary-bg); /* Użyj koloru tła z Bootstrap */
    color: var(--bs-secondary-color); /* Użyj koloru tekstu z Bootstrap */
    margin-top: 10px; /* Dodaj odstęp między grupami */
    border-radius: 0.25rem; /* Zaokrąglone rogi */
    border: 1px solid var(--bs-secondary-border-subtle); /* Delikatna ramka */
}


/* Style dla sekcji podglądu parsowania */
#vehiclePreview {
    white-space: pre-wrap; /* Zachowaj formatowanie białymi znakami */
    word-wrap: break-word; /* Zawijaj długie słowa */
    font-family: monospace; /* Użyj czcionki monospace dla czytelności */
    border: 1px dashed #999; /* Poprawiona kropkowana ramka */
    background-color: #f0f0f0; /* Lekko szare tło */
    margin-top: 15px; /* Dodaj odstęp od góry */
    padding: 15px; /* Dodaj wewnętrzny padding */
}

/* Style dla akordeonu w sekcji podglądu */
.tn-vehicle-database-accordion-preview .accordion-item {
    border: none; /* Usuń standardowe ramki akordeonu */
    margin-bottom: 5px; /* Dodaj mały odstęp między elementami akordeonu */
}

.tn-vehicle-database-accordion-preview .accordion-header .accordion-button {
    background-color: #e9ecef; /* Jasnoszare tło dla nagłówka */
    color: #495057; /* Ciemniejszy kolor tekstu */
    font-size: 0.9rem; /* Nieco mniejsza czcionka */
    padding: 0.4rem 1rem; /* Mniejszy padding */
    border-radius: 0.25rem; /* Zaokrąglone rogi */
    transition: background-color 0.2s ease; /* Płynne przejście koloru tła */
}

.tn-vehicle-database-accordion-preview .accordion-header .accordion-button:not(.collapsed) {
     background-color: #dee2e6; /* Nieco ciemniejsze tło po rozwinięciu */
     color: #212529;
}

.tn-vehicle-database-accordion-preview .accordion-body {
    background-color: #f8f9fa; /* Bardzo jasnoszare tło dla treści */
    padding: 0.5rem 1rem; /* Mniejszy padding */
    border-top: 1px solid #e9ecef; /* Cienka linia oddzielająca nagłówek od treści */
}

.tn-vehicle-database-accordion-preview .list-group-item {
    border-bottom: 1px dashed #ced4da !important; /* Kropkowana ramka dla elementów listy w podglądzie */
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
}

.tn-vehicle-database-accordion-preview .list-group-item:last-child {
    border-bottom: none !important; /* Usuń ramkę pod ostatnim elementem */
}


</style>

<?php // --- Skrypt JS do dynamicznego parsowania i podglądu --- ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Skrypty specyficzne dla tej strony (jeśli są potrzebne)
    // Usunięto inicjalizację tooltipów dla głównego akordeonu

    // Inicjalizacja tooltipów dla elementów poza akordeonem (np. przyciski w nagłówku)
    const staticTooltipTriggerList = [].slice.call(document.querySelectorAll('.tn-vehicles-list > .card-header [data-bs-toggle="tooltip"]'));
    staticTooltipTriggerList.map(function (tooltipTriggerEl) {
        const existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
        if (existingTooltip) {
            existingTooltip.dispose();
        }
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // --- Dynamiczne parsowanie i podgląd za pomocą JavaScript ---
    const vehicleRawDataTextarea = document.getElementById('vehicleRawData');
    const vehiclePreviewDiv = document.getElementById('vehiclePreview');

    // Funkcja do parsowania surowych danych tekstowych (replika logiki PHP w JS)
    function parseVehicleData(rawData) {
        const lines = rawData.split('\n');
        const uniqueVehiclesMap = {}; // Mapa do przechowywania unikalnych pojazdów (Make/Model => [versions])
        let currentMakeModel = null; // Śledzenie aktualnej Marki/Modelu z linii zakończonej ':'

        // Regex do parsowania wierszy wersji (musi być zgodny z regexem PHP)
        // Uelastyczniono rok początkowy (opcjonalne .MM) i rok końcowy (dowolny tekst)
        const versionRegex = /^\s*(.+?)\s+\(([^)]+?)\)\s*,\s*(\d*)\s*,\s*(\d*)\s*,\s*(\d*)\s*,\s*(\d{4}(?:\.\d{2})?)\s*-\s*(.*?)\s*$/;


        lines.forEach(line => {
            const trimmedLine = line.trim();
            if (trimmedLine === '') return; // Pomiń puste wiersze

            // Sprawdź, czy to wiersz Marka/Model (kończy się dwukropkiem i nie jest wcięty)
            if (trimmedLine.endsWith(':') && !/^\s/.test(line)) {
                currentMakeModel = trimmedLine.substring(0, trimmedLine.length - 1).trim();
                if (!uniqueVehiclesMap[currentMakeModel]) {
                    uniqueVehiclesMap[currentMakeModel] = [];
                }
            }
            // Sprawdź, czy to wiersz wersji pojazdu (jest wcięty i pasuje do wyrażenia regularnego)
            // Przetwarzaj wiersze wersji tylko, jeśli wcześniej zidentyfikowano Markę/Model (currentMakeModel !== null)
            else if (currentMakeModel !== null && /^\s+/.test(line)) {
                 const matches = line.match(versionRegex);
                 if (matches) {
                    const version_data = {
                        name: matches[1] ? matches[1].trim() : '',
                        code: matches[2] ? matches[2].trim() : '',
                        capacity: matches[3] ? matches[3].trim() : '-' ,
                        kw: matches[4] ? matches[4].trim() : '-',
                        hp: matches[5] ? matches[5].trim() : '-',
                        year_start: matches[6] ? matches[6].trim() : '',
                        year_end: matches[7] ? matches[7].trim() : 'nadal',
                        // extra_detail: matches[8] ? matches[8].trim() : '', // Jeśli regex ma grupę 8
                    };

                    // Utwórz unikalny klucz dla każdej wersji
                    const version_key = Object.values(version_data).join('|');

                    // Dodaj wersję tylko, jeśli nie jest duplikatem dla obecnej Marki/Modelu
                    let is_duplicate = false;
                    if (uniqueVehiclesMap[currentMakeModel]) {
                        for (const existing_version of uniqueVehiclesMap[currentMakeModel]) {
                            if (Object.values(existing_version).join('|') === version_key) {
                                is_duplicate = true;
                                break;
                            }
                        }
                    }

                    if (!is_duplicate) {
                        uniqueVehiclesMap[currentMakeModel].push(version_data);
                    }
                 }
                 // Opcjonalnie: można dodać logowanie w konsoli dla wierszy, które nie pasują do wzorca wersji,
                 // ale są wcięte i pod blokiem Marka/Model.
                 // else {
                 //     console.warn("Linia w bloku " + currentMakeModel + " nie pasuje do wzorca wersji:", line);
                 // }
            }
            // Opcjonalnie: można dodać logowanie w konsoli dla wierszy poza blokiem Marka/Model, które nie są puste
            // i nie kończą się dwukropkiem (np. "66 modeli", "2 Active Tourer (F45)").
            // Te linie są ignorowane przez obecną logikę parsowania wersji.
            // else if (currentMakeModel === null && !empty(trimmedLine) && !trimmedLine.endsWith(':')) {
            //     console.warn("Linia poza blokiem Marka/Model nie pasuje do wzorca:", line);
            // }
        });

        // Posortuj wersje wewnątrz każdej grupy Marka/Model (alfabetycznie po nazwie)
        for (const makeModel in uniqueVehiclesMap) {
            if (uniqueVehiclesMap.hasOwnProperty(makeModel) && Array.isArray(uniqueVehiclesMap[makeModel])) {
                uniqueVehiclesMap[makeModel].sort((a, b) => a.name.localeCompare(b.name));
            }
        }


        return uniqueVehiclesMap;
    }

    // Funkcja do generowania HTML podglądu na podstawie sparsowanych danych
    function generatePreviewHtml(parsedData) {
        let html = '';
        if (Object.keys(parsedData).length === 0) {
            html = '<p class="text-muted">Brak danych do podglądu lub błąd parsowania.</p>';
        } else {
            // Użyj klasy specyficznej dla podglądu, aby style nie kolidowały z główną listą
            html += '<div class="accordion tn-vehicle-database-accordion-preview">';
            let preview_accordion_index = 0;
            for (const makeModel in parsedData) {
                if (parsedData.hasOwnProperty(makeModel) && Array.isArray(parsedData[makeModel]) && parsedData[makeModel].length > 0) {
                     const versions = parsedData[makeModel];
                     // Użyj unikalnych ID dla elementów akordeonu podglądu
                     const previewAccordionId = `previewCollapse${preview_accordion_index}`;
                     const previewHeadingId = `previewHeading${preview_accordion_index}`;

                     html += `
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="${previewHeadingId}">
                                <button class="accordion-button ${preview_accordion_index === 0 ? '' : 'collapsed'} py-2" type="button" data-bs-toggle="collapse" data-bs-target="#${previewAccordionId}" aria-expanded="${preview_accordion_index === 0 ? 'true' : 'false'}" aria-controls="${previewAccordionId}">
                                    <i class="bi bi-car-front me-2"></i>${escapeHtml(makeModel)}
                                    <span class="badge bg-secondary ms-2 rounded-pill">${versions.length} wersji</span>
                                </button>
                            </h2>
                            <div id="${previewAccordionId}" class="accordion-collapse collapse ${preview_accordion_index === 0 ? 'show' : ''}" aria-labelledby="${previewHeadingId}" data-bs-parent=".tn-vehicle-database-accordion-preview">
                                <div class="accordion-body small">
                                    <div class="list-group list-group-flush">
                     `;

                     versions.forEach(version => {
                        html += `
                            <div class="list-group-item py-2 px-0 bg-transparent border-bottom-dashed">
                                <div class="flex-grow-1">
                                    <div class="fw-bold">
                                        ${escapeHtml(version.name || 'Brak nazwy wersji')}
                                        ${version.code && version.code !== '-' ? `<span class="font-monospace text-muted small">(${escapeHtml(version.code)})</span>` : ''}
                                    </div>
                                    <div class="text-muted small">
                                        Poj: ${escapeHtml(version.capacity || '-')} ccm³ |
                                        Moc: ${escapeHtml(version.kw || '-')} kW (${escapeHtml(version.hp || '-')} KM) |
                                        Rocznik: ${escapeHtml(version.year_start || '-')} - ${escapeHtml(version.year_end || '...')}${version.year_end === 'nadal' ? '' : ''}
                                        ${version.extra_detail && version.extra_detail !== '-' ? `| Dodatkowo: ${escapeHtml(version.extra_detail)}` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                     });

                     html += `
                                    </div>
                                </div>
                            </div>
                        </div>
                     `;
                     preview_accordion_index++;
                }
            }
             html += '</div>'; // Koniec .tn-vehicle-database-accordion-preview
        }
        return html;
    }

    // Prosta funkcja do zabezpieczania tekstu przed XSS w HTML
    function escapeHtml(unsafe) {
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }


    // Event listener na wprowadzanie danych w textarea
    vehicleRawDataTextarea.addEventListener('input', function() {
        const rawData = vehicleRawDataTextarea.value;
        const parsedData = parseVehicleData(rawData);
        const previewHtml = generatePreviewHtml(parsedData);
        vehiclePreviewDiv.innerHTML = previewHtml;

        // Ponowna inicjalizacja akordeonu Bootstrap dla podglądu (jeśli istnieje)
        // Używamy selektora .tn-vehicle-database-accordion-preview aby targetować tylko akordeon podglądu
        const previewAccordion = vehiclePreviewDiv.querySelector('.tn-vehicle-database-accordion-preview');
        if (previewAccordion) {
             // Upewnij się, że Bootstrap JS jest załadowany
             if (typeof bootstrap !== 'undefined' && typeof bootstrap.Collapse !== 'undefined') {
                 // Ponownie inicjalizuj komponenty Collapse w newo dodanym HTML
                 const collapseElements = previewAccordion.querySelectorAll('.accordion-collapse');
                 collapseElements.forEach(el => {
                     // Usuń istniejące instancje Collapse, jeśli istnieją (zapobiega problemom z wielokrotną inicjalizacją)
                     const existingCollapse = bootstrap.Collapse.getInstance(el);
                     if (existingCollapse) {
                         existingCollapse.dispose();
                     }
                     new bootstrap.Collapse(el, { toggle: false });
                 });

                 // Opcjonalnie: dodaj listenery dla tooltipów w podglądzie, jeśli są używane
                 // const tooltipsInPreview = previewAccordion.querySelectorAll('[data-bs-toggle="tooltip"]');
                 // tooltipsInPreview.forEach(tooltipTriggerEl => {
                 //     const existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                 //     if (existingTooltip) { existingTooltip.dispose(); }
                 //     new bootstrap.Tooltip(tooltipTriggerEl);
                 // });

             } else {
                 console.error("Bootstrap JS lub moduł Collapse nie jest załadowany.");
             }
        }
    });

    // Opcjonalnie: Wywołaj parsowanie przy ładowaniu strony, jeśli textarea ma domyślną wartość
    // (W tej wersji textarea jest pusta domyślnie, ale można to zmienić)
    // if (vehicleRawDataTextarea.value.trim() !== '') {
    //      const rawData = vehicleRawDataTextarea.value;
    //      const parsedData = parseVehicleData(rawData);
    //      const previewHtml = generatePreviewHtml(parsedData);
    //      vehiclePreviewDiv.innerHTML = previewHtml;
    //
    //      const previewAccordion = vehiclePreviewDiv.querySelector('.tn-vehicle-database-accordion-preview');
    //      if (previewAccordion) {
    //           if (typeof bootstrap !== 'undefined' && typeof bootstrap.Collapse !== 'undefined') {
    //               const collapseElements = previewAccordion.querySelectorAll('.accordion-collapse');
    //                collapseElements.forEach(el => {
    //                    const existingCollapse = bootstrap.Collapse.getInstance(el);
    //                    if (existingCollapse) { existingCollapse.dispose(); }
    //                    new bootstrap.Collapse(el, { toggle: false });
    //                });
    //           }
    //      }
    // }
});
</script>
