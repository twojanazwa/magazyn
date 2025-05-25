<?php
// tn_biuro.php
/**
 * Główny plik obsługujący logikę generatora rachunków/faktur oraz warunkowo wyświetlający
 * stronę główną z modaliami do dodawania/edycji/ustawień lub szablon dokumentu.
 *
 * TEN PLIK ZAWIERA ZAGNIEŻDŻONĄ LOGIKĘ KONTROLERA I WIDOKÓW.
 * JEST TO ROZWIĄZANIE UPROSZCZONE. W PROFESJONALNYCH APLIKACJACH ZALECANA JEST ARCHITEKTURA MVC.
 *
 * Tryby działania (określane przez parametry GET lub POST):
 * - Brak parametrów lub action=summary: Wyświetla stronę główną biura z menu, podsumowaniem i modaliami.
 * - d=nr_fv: Wyświetla konkretną fakturę/rachunek (dołącza tn_invoice_template.html).
 * - Obsługa POST action=save_invoice: Zapisuje nową fakturę/rachunek (z modala).
 * - Obsługa POST action=save_settings: Zapisuje ustawienia (z modala).
 * - Obsługa POST action=edit_invoice: Zapisuje edytowaną fakturę/rachunek (z modala).
 * - Obsługa POST action=delete_invoice: Usuwa fakturę/rachunek.
 *
 * Zakłada dostępność funkcji pomocniczych:
 * tn_generuj_url() (jeśli używana w linkach)
 * tn_ustaw_komunikat_flash() (jeśli używana do komunikatów)
 */

// Włączenie wyświetlania błędów PHP (TYLKO DO DEBUGOWANIA! Usuń w produkcji)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ustawienie nagłówków dla poprawnego wyświetlania (opcjonalne, ale przydatne)
// header('Content-Type: text/html; charset=UTF-8');
// header('X-Frame-Options: DENY'); // Podstawowe zabezpieczenie przed kliknięciem w iframe

// --- Ścieżki do plików danych i szablonów ---
// Pamiętaj, aby dostosować te ścieżki do rzeczywistej struktury katalogów względem tego pliku (tn_biuro.php)
$invoices_json_file_path = __DIR__ . '/../data/invoices.json'; // Plik z fakturami/rachunkami
$settings_json_file_path = __DIR__ . '/../data/biuro_settings.json'; // Plik z ustawieniami biura
$invoice_template_path = __DIR__ . '/../invoice/tn_invoice_template.html'; // Szablon wyświetlania faktury

// Ścieżki do plików widoków (teraz głównie dołączane w głównym layoucie)
$view_doc_list_path = __DIR__ . '/tn_biuro_doc_list.php'; // Nadal używany do wyświetlania listy

// --- Funkcje pomocnicze ---

// Funkcja do odczytu danych z pliku JSON (zwraca tablicę obiektów)
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
        return is_array($decoded_data) ? $decoded_data : [];
    }
    if (!file_exists($file_path)) {
        return []; // Zwróć pustą tablicę, jeśli plik nie istnieje (np. pierwsza wizyta)
    }
    error_log("Plik JSON nie znaleziony lub brak uprawnień do odczytu: " . $file_path);
    return null;
}

// Funkcja do zapisu danych do pliku JSON
function writeJsonFile($file_path, $data) {
    $dir = dirname($file_path);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true)) {
             error_log("Błąd: Nie można utworzyć katalogu dla pliku JSON: " . $dir);
             return false;
        }
    }

    if (file_exists($file_path) && !is_writable($file_path)) {
         error_log("Brak uprawnień do zapisu do pliku JSON: " . $file_path);
         return false;
    }
    if (!file_exists($file_path) && !is_writable($dir)) {
         error_log("Brak uprawnień do zapisu w katalogu docelowym dla pliku JSON: " . $dir);
         return false;
    }

    $json_content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json_content === false) {
        error_log("Błąd kodowania danych do formatu JSON: " . json_last_error_msg());
        return false;
    }

    $put_result = file_put_contents($file_path, $json_content, LOCK_EX);

    if ($put_result === false) {
        error_log("Błąd zapisu do pliku JSON: " . $file_path);
        return false;
    }

    return true;
}

// Formatowanie waluty (dostępne w obu trybach) - przeniesiona do funkcji globalnej lub dołączenia
// Tutaj tylko deklaracja, jeśli nie ma jej w innym dołączonym pliku
if (!function_exists('format_currency')) {
    function format_currency($amount, $currency = 'PLN') {
        return number_format((float)$amount, 2, ',', ' ') . ' ' . htmlspecialchars($currency, ENT_QUOTES, 'UTF-8');
    }
}

// Funkcja pomocnicza do generowania URL (dostosuj do swojego systemu routingu)
// Zakładamy, że ten plik jest dostępny pod adresem /biuro
if (!function_exists('tn_generuj_url')) {
    function tn_generuj_url($page, $params = []) {
        $url = './biuro'; // Domyślny adres dla biura (ten plik)
        $query = [];

        if ($page === 'view_invoice' && isset($params['d'])) {
             $query['d'] = $params['d'];
        } elseif ($page === 'add_invoice') {
             $query['action'] = 'add'; // Akcja do otwarcia modala dodawania (może być niepotrzebna jeśli modal otwierany tylko JS)
        } elseif ($page === 'list_invoices') {
             $query['action'] = 'list'; // Akcja do wyświetlenia listy (może być domyślna)
        } elseif ($page === 'settings') {
             $query['action'] = 'settings'; // Akcja do otwarcia modala ustawień (może być niepotrzebna jeśli modal otwierany tylko JS)
        } elseif ($page === 'summary') {
             // Brak akcji lub action=summary to domyślna strona
        }
        // Dodaj inne przypadki routingu, jeśli potrzebne

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        return $url;
    }
}

// Funkcja pomocnicza do ustawiania komunikatów flash (przykładowa)
// W realnej aplikacji wymagałoby to obsługi sesji i wyświetlania w layoutcie
if (!function_exists('tn_ustaw_komunikat_flash')) {
    function tn_ustaw_komunikat_flash($message, $type = 'info') {
        // Symulacja - w realnej aplikacji zapisz w $_SESSION i wyświetl w głównym layoutcie
        // echo "<div class='alert alert-{$type}'>{$message}</div>";
        // Można też zapisać w globalnej zmiennej i wyświetlić w tym pliku
        $GLOBALS['flash_messages'][] = ['message' => $message, 'type' => $type];
    }
}


// --- Logika obsługi żądań POST (Zapis danych) ---

$action = $_POST['action'] ?? $_GET['action'] ?? null; // Określ akcję na podstawie POST lub GET
$document_number_param = $_GET['d'] ?? null; // Numer dokumentu do wyświetlenia

$form_submit_message = ''; // Komunikaty po przesłaniu formularza dodawania
$settings_submit_message = ''; // Komunikaty po przesłaniu ustawień
$flash_messages = []; // Tablica na komunikaty flash (zbierane przez tn_ustaw_komunikat_flash)


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Obsługa zapisu nowej faktury/rachunku (z modala dodawania) ---
    if ($action === 'save_invoice') {
        // Pobierz dane z formularza (pamiętaj o walidacji i sanitacji w realnej aplikacji!)
        $new_invoice_data = [
            'invoice_number' => trim($_POST['invoice_number'] ?? ''),
            'order_date' => trim($_POST['order_date'] ?? date('Y-m-d')),
            'seller' => [
                'name' => trim($_POST['seller_name'] ?? ''),
                'address' => trim($_POST['seller_address'] ?? ''),
                'nip' => trim($_POST['seller_nip'] ?? ''),
                'bank_account' => trim($_POST['seller_bank_account'] ?? ''),
            ],
            'buyer' => [
                'name' => trim($_POST['buyer_name'] ?? ''),
                'address' => trim($_POST['buyer_address'] ?? ''),
                'nip' => trim($_POST['buyer_nip'] ?? ''),
            ],
            'items' => [],
            'payment_method' => trim($_POST['payment_method'] ?? ''),
            'payment_due_date' => trim($_POST['payment_due_date'] ?? ''),
            'currency' => trim($_POST['currency'] ?? 'PLN'),
        ];

        if (isset($_POST['item_name']) && is_array($_POST['item_name'])) {
            $item_names = $_POST['item_name'];
            $item_quantities = $_POST['item_quantity'] ?? [];
            $item_unit_prices_net = $_POST['item_unit_price_net'] ?? [];
            $item_tax_rates = $_POST['item_tax_rate'] ?? [];
            $item_units = $_POST['item_unit'] ?? [];

            foreach ($item_names as $key => $name) {
                $trimmed_name = trim($name);
                if (!empty($trimmed_name)) {
                    $new_invoice_data['items'][] = [
                        'name' => $trimmed_name,
                        'quantity' => floatval($item_quantities[$key] ?? 0),
                        'unit_price_net' => floatval($item_unit_prices_net[$key] ?? 0),
                        'tax_rate' => intval($item_tax_rates[$key] ?? 0),
                        'unit' => trim($item_units[$key] ?? ''),
                    ];
                }
            }
        }

        if (empty($new_invoice_data['invoice_number']) || empty($new_invoice_data['seller']['name']) || empty($new_invoice_data['buyer']['name']) || empty($new_invoice_data['items'])) {
             if (function_exists('tn_ustaw_komunikat_flash')) {
                tn_ustaw_komunikat_flash('Proszę wypełnić wymagane pola faktury (Numer, Sprzedawca, Nabywca) oraz dodać przynajmniej jedną pozycję.', 'warning');
             }
             // Ustaw komunikat dla trybu formularza dodawania
             $form_submit_message = '<div class="alert alert-warning">Proszę wypełnić wymagane pola faktury oraz dodać przynajmniej jedną pozycję.</div>';
        } else {
            $all_invoices = readJsonFile($invoices_json_file_path);

            if ($all_invoices !== null) {
                $invoice_exists = false;
                foreach($all_invoices as $inv) {
                    if (isset($inv['invoice_number']) && $inv['invoice_number'] === $new_invoice_data['invoice_number']) {
                        $invoice_exists = true;
                        break;
                    }
                }

                if ($invoice_exists) {
                     if (function_exists('tn_ustaw_komunikat_flash')) {
                        tn_ustaw_komunikat_flash('Faktura/rachunek o podanym numerze już istnieje.', 'danger');
                     }
                     $form_submit_message = '<div class="alert alert-danger">Faktura/rachunek o podanym numerze już istnieje.</div>';
                } else {
                    $all_invoices[] = $new_invoice_data;

                    if (writeJsonFile($invoices_json_file_path, $all_invoices)) {
                         if (function_exists('tn_ustaw_komunikat_flash')) {
                            tn_ustaw_komunikat_flash('Faktura/rachunek został pomyślnie zapisany.', 'success');
                         }
                         // Przekieruj na stronę wyświetlania faktury po zapisie
                         header('Location: ?d=' . urlencode($new_invoice_data['invoice_number']));
                         exit;
                    } else {
                         if (function_exists('tn_ustaw_komunikat_flash')) {
                            tn_ustaw_komunikat_flash('Błąd podczas zapisu faktury/rachunku do pliku.', 'danger');
                         }
                         $form_submit_message = '<div class="alert alert-danger">Błąd podczas zapisu faktury/rachunku do pliku.</div>';
                    }
                }
            } else {
                 if (function_exists('tn_ustaw_komunikat_flash')) {
                    tn_ustaw_komunikat_flash('Nie można odczytać danych faktur/rachunków do zapisu.', 'danger');
                 }
                 $form_submit_message = '<div class="alert alert-danger">Nie można odczytać danych faktur/rachunków do zapisu.</div>';
            }
        }
    }
    // --- Obsługa zapisu ustawień (z modala ustawień) ---
    elseif ($action === 'save_settings') {
        // Pobierz dane ustawień z formularza (pamiętaj o walidacji i sanitacji!)
        $settings_data = [
            'seller_name' => trim($_POST['seller_name'] ?? ''),
            'seller_address' => trim($_POST['seller_address'] ?? ''),
            'seller_nip' => trim($_POST['seller_nip'] ?? ''),
            'seller_bank_account' => trim($_POST['seller_bank_account'] ?? ''),
            'invoice_number_format' => trim($_POST['invoice_number_format'] ?? ''),
            'default_vat_rate' => intval($_POST['default_vat_rate'] ?? 23),
            // Dodaj inne pola ustawień
        ];

        // Podstawowa walidacja ustawień
        if (empty($settings_data['seller_name']) || empty($settings_data['invoice_number_format'])) {
             if (function_exists('tn_ustaw_komunikat_flash')) {
                tn_ustaw_komunikat_flash('Proszę wypełnić wymagane pola ustawień (Nazwa Sprzedawcy, Format Numeracji).', 'warning');
             }
             // Ustaw komunikat dla trybu ustawień
             $settings_submit_message = '<div class="alert alert-warning">Proszę wypełnić wymagane pola ustawień (Nazwa Sprzedawcy, Format Numeracji).</div>';
        } else {
            // Zapisz ustawienia do pliku JSON
            if (writeJsonFile($settings_json_file_path, $settings_data)) {
                 if (function_exists('tn_ustaw_komunikat_flash')) {
                    tn_ustaw_komunikat_flash('Ustawienia zostały pomyślnie zapisane.', 'success');
                 }
                 $settings_submit_message = '<div class="alert alert-success">Ustawienia zostały pomyślnie zapisane.</div>';
                 // Opcjonalnie: przekieruj, aby uniknąć ponownego wysłania formularza
                 // header('Location: ?action=settings');
                 // exit;
            } else {
                 if (function_exists('tn_ustaw_komunikat_flash')) {
                    tn_ustaw_komunikat_flash('Błąd podczas zapisu ustawień.', 'danger');
                 }
                 $settings_submit_message = '<div class="alert alert-danger">Błąd podczas zapisu ustawień.</div>';
            }
        }
    }
    // --- Obsługa zapisu edytowanej faktury/rachunku (z modala edycji) ---
    elseif ($action === 'edit_invoice') {
        // !!! TUTAJ POTRZEBNA LOGIKA EDYCJI !!!
        // 1. Pobierz ID/numer edytowanego dokumentu z POST.
        // 2. Odczytaj wszystkie faktury z invoices.json.
        // 3. Znajdź dokument o podanym ID/numerze.
        // 4. Zaktualizuj jego dane na podstawie pól z POST.
        // 5. Zapisz zaktualizowaną tablicę faktur z powrotem do invoices.json.
        // 6. Ustaw komunikat flash (sukces/błąd).
         if (function_exists('tn_ustaw_komunikat_flash')) {
            tn_ustaw_komunikat_flash('Funkcja edycji faktury/rachunku niezaimplementowana.', 'info');
         }
    }
     // --- Obsługa usuwania faktury/rachunku ---
    elseif ($action === 'delete_invoice') {
        // !!! TUTAJ POTRZEBNA LOGIKA USUWANIA !!!
        // 1. Pobierz ID/numer usuwanego dokumentu z POST.
        // 2. Odczytaj wszystkie faktury z invoices.json.
        // 3. Znajdź dokument o podanym ID/numerze i usuń go z tablicy.
        // 4. Zapisz zaktualizowaną tablicę faktur z powrotem do invoices.json.
        // 5. Ustaw komunikat flash (sukces/błąd).
         if (function_exists('tn_ustaw_komunikat_flash')) {
            tn_ustaw_komunikat_flash('Funkcja usuwania faktury/rachunku niezaimplementowana.', 'info');
         }
    }

    // Po obsłużeniu POST, jeśli nie było przekierowania, określ akcję do wyświetlenia
    $action = $_GET['action'] ?? 'summary'; // Domyślnie pokaż podsumowanie po POST, jeśli nie przekierowano
} else {
     // Jeśli żądanie nie jest POST, określ akcję na podstawie parametru 'action' (lub domyślnie 'summary')
     $action = $_GET['action'] ?? 'summary';
}


// --- Logika routingu (wybór, co wyświetlić) ---

// Jeśli jest parametr 'd', wyświetl fakturę/rachunek
if ($document_number_param !== null) {
    // Dane do wyświetlenia ($invoice_data_to_display, $subtotal_net_display, itp.)
    // zostały przygotowane w bloku logiki trybu wyświetlania powyżej.
    // Teraz dołączamy szablon HTML.
    $all_invoices = readJsonFile($invoices_json_file_path); // Ponownie odczytaj faktury dla trybu wyświetlania
     if ($all_invoices !== null) {
         foreach ($all_invoices as $invoice) {
             if (isset($invoice['invoice_number']) && $invoice['invoice_number'] === $document_number_param) {
                 $invoice_data_to_display = $invoice;
                 break;
             }
         }
         if ($invoice_data_to_display === null) {
             $display_error_message = 'Nie znaleziono faktury/rachunku o podanym numerze.';
             error_log("Próba wyświetlenia nieistniejącej faktury/rachunku: " . $document_number_param);
         } else {
             // Oblicz sumy dla wyświetlania
             $subtotal_net_display = 0; $total_tax_display = 0; $total_gross_display = 0; $tax_summary_display = [];
             if (isset($invoice_data_to_display['items']) && is_array($invoice_data_to_display['items'])) {
                 foreach ($invoice_data_to_display['items'] as $item) {
                     $item_quantity = $item['quantity'] ?? 0; $item_unit_price_net = $item['unit_price_net'] ?? 0; $item_tax_rate = $item['tax_rate'] ?? 0;
                     $item_net = (float)$item_unit_price_net * (float)$item_quantity;
                     $item_tax = $item_net * ((float)$item_tax_rate / 100);
                     $item_gross = $item_net + $item_tax;
                     $subtotal_net_display += $item_net; $total_tax_display += $item_tax; $total_gross_display += $item_gross;
                     if (!isset($tax_summary_display[$item_tax_rate])) { $tax_summary_display[$item_tax_rate] = ['net' => 0, 'tax' => 0, 'gross' => 0]; }
                     $tax_summary_display[$item_tax_rate]['net'] += $item_net; $tax_summary_display[$item_tax_rate]['tax'] += $item_tax; $tax_summary_display[$item_tax_rate]['gross'] += $item_gross;
                 }
             }
             $currency_format_display = $invoice_data_to_display['currency'] ?? 'PLN';
         }
     } else {
         $display_error_message = 'Błąd odczytu danych faktur/rachunków.';
     }


    if (file_exists($invoice_template_path)) {
        // Przekazujemy zmienne do dołączanego pliku poprzez ich zdefiniowanie w bieżącym zasięgu
        include $invoice_template_path;
    } else {
        // Jeśli plik szablonu nie istnieje
        error_log("Błąd: Plik szablonu faktury/rachunku nie znaleziony: " . $invoice_template_path);
        echo '<div class="container-fluid"><div class="alert alert-danger" role="alert"><i class="bi bi-x-circle me-2"></i>Błąd serwera: Nie można znaleźć szablonu faktury/rachunku.</div></div>';
    }

} else {
    // Jeśli nie ma parametru 'd', wyświetl główną strukturę strony z menu i odpowiednim widokiem

    // --- Przygotowanie danych dla strony głównej/podsumowania ---
    $all_invoices = readJsonFile($invoices_json_file_path);
     if ($all_invoices === null) {
         if (function_exists('tn_ustaw_komunikat_flash')) {
            tn_ustaw_komunikat_flash('Błąd odczytu listy faktur/rachunków.', 'danger');
         }
         $all_invoices = [];
    }}

    $total_invoices_count = count($all_invoices);
    $total_gross_all_invoices = 0;

    if (!empty($all_invoices)) {
        foreach ($all_invoices as $invoice) {
             if (isset($invoice['items']) && is_array($invoice['items'])) {
                 foreach ($invoice['items'] as $item) {
                     $item_quantity = $item['quantity'] ?? 0;
                     $item_unit_price_net = $item['unit_price_net'] ?? 0;
                     $item_tax_rate = $item['tax_rate'] ?? 0;
                     $item_net = (float)$item_unit_price_net * (float)$item_quantity;
                     $item_tax = $item_net * ((float)$item_tax_rate / 100);
                     $total_gross_all_invoices += $item_net + $item_tax;
                 }
             }
        }
    }

    // Pobierz dane sprzedawcy z ustawień dla podsumowania
    $settings_data = readJsonFile($settings_json_file_path);
     if ($settings_data === null) {
         if (function_exists('tn_ustaw_komunikat_flash')) {
            tn_ustaw_komunikat_flash('Błąd odczytu ustawień biura.', 'danger');
         }
         $settings_data = [];
    }
    $seller_name_summary = $settings_data['seller_name'] ?? 'BRAK';
    $seller_address_summary = $settings_data['seller_address'] ?? 'BRAK';
    $seller_nip_summary = $settings_data['seller_nip'] ?? 'BRAK';


    // --- WYŚWIETLANIE GŁÓWNEJ STRUKTURY Z MENU I WIDOKAMI ---
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biuro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body {
            font-size: 0.9rem;
            line-height: 1.4;
            color: #333;
            background-color: #f8f9fa;
            padding-bottom: 50px;
        }
        .container-fluid {
            margin-top: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
        .nav-link {
            cursor: pointer;
        }
         .invoice-item-row {
             display: none;
         }
         .summary-table th, .summary-table td {
             padding: 0.5rem;
             font-size: 0.9rem;
         }
         .summary-table th {
             background-color: #e9ecef;
         }
    </style>
</head>
<body>

    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-header bg-light py-3">
                <h5 class="mb-0"><i class="bi bi-building me-2"></i>Panel Biura</h5>
            </div>
            <div class="card-body p-4">

                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($action === 'summary') ? 'active' : ''; ?>" href="<?php echo tn_generuj_url('summary'); ?>"><i class="bi bi-house me-1"></i> Strona Główna</a>
                    </li>
                     <li class="nav-item dropdown">
                         <a class="nav-link dropdown-toggle <?php echo ($action === 'add') ? 'active' : ''; ?>" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false"><i class="bi bi-file-earmark-plus me-1"></i> Wystaw Dokument</a>
                         <ul class="dropdown-menu">
                             <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addDocumentModal">Fakturę</a></li>
                             <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addDocumentModal">Rachunek</a></li>
                         </ul>
                     </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($action === 'list') ? 'active' : ''; ?>" href="<?php echo tn_generuj_url('list_invoices'); ?>"><i class="bi bi-list-columns-reverse me-1"></i> Lista Dokumentów</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($action === 'settings') ? 'active' : ''; ?>" href="#" data-bs-toggle="modal" data-bs-target="#settingsModal"><i class="bi bi-gear me-1"></i> Ustawienia</a>
                    </li>
                </ul>
                <?php
                if (!empty($flash_messages)) {
                    foreach ($flash_messages as $msg) {
                        echo "<div class='alert alert-{$msg['type']} alert-dismissible fade show' role='alert'>";
                        echo htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8');
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                        echo '</div>';
                    }
                     $flash_messages = [];
                }
                ?>


                <?php
                switch ($action) {
                    case 'list':
                        if (file_exists($view_doc_list_path)) {
                             // Przekazujemy zmienną $all_invoices do dołączanego pliku
                            include $view_doc_list_path;
                        } else {
                             error_log("Błąd: Plik widoku listy dokumentów nie znaleziony: " . $view_doc_list_path);
                             echo '<div class="alert alert-danger" role="alert"><i class="bi bi-x-circle me-2"></i>Błąd serwera: Nie można znaleźć widoku listy dokumentów.</div>';
                        }
                        break;

                    case 'summary':
                    default:
                        // Strona główna biura z podsumowaniem i menu
                        // Zmienne $total_invoices_count, $total_gross_all_invoices,
                        // $seller_name_summary, $seller_address_summary, $seller_nip_summary są dostępne.
                ?>
                        <h6>Podsumowanie:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm summary-table">
                                    <thead>
                                        <tr>
                                            <th colspan="2">Statystyki Dokumentów</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Łączna liczba dokumentów:</td>
                                            <td><?php echo $total_invoices_count; ?></td>
                                        </tr>
                                         <tr>
                                             <td>Łączna wartość brutto (PLN):</td>
                                             <td><?php echo format_currency($total_gross_all_invoices, 'PLN'); ?></td>
                                         </tr>
                                    </tbody>
                                </table>
                            </div>
                             <div class="col-md-6">
                                 <table class="table table-sm summary-table">
                                     <thead>
                                         <tr>
                                             <th colspan="2">Dane Sprzedawcy (z Ustawień)</th>
                                         </tr>
                                     </thead>
                                     <tbody>
                                         <tr>
                                             <td>Nazwa:</td>
                                             <td><?php echo htmlspecialchars($seller_name_summary, ENT_QUOTES, 'UTF-8'); ?></td>
                                         </tr>
                                          <tr>
                                              <td>Adres:</td>
                                              <td><?php echo htmlspecialchars($seller_address_summary, ENT_QUOTES, 'UTF-8'); ?></td>
                                          </tr>
                                           <tr>
                                               <td>NIP:</td>
                                               <td><?php echo htmlspecialchars($seller_nip_summary, ENT_QUOTES, 'UTF-8'); ?></td>
                                           </tr>
                                     </tbody>
                                 </table>
                             </div>
                        </div>

                         <div class="mt-4">
                             <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addDocumentModal"><i class="bi bi-file-earmark-plus me-1"></i> Wystaw Nowy Dokument</button>
                              <a href="<?php echo tn_generuj_url('list_invoices'); ?>" class="btn btn-secondary"><i class="bi bi-list-columns-reverse me-1"></i> Zobacz Listę Dokumentów</a>
                         </div>

                <?php
                        break;
                }
                ?>

            </div>
        </div>
    </div>

    <div class="modal fade" id="addDocumentModal" tabindex="-1" aria-labelledby="addDocumentModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addDocumentModalLabel">Dodaj Nowy Dokument (Fakturę/Rachunek)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
              <?php // Komunikat po przesłaniu formularza dodawania (jeśli był błąd) ?>
              <?php echo $form_submit_message; ?>
              <form method="POST" action="./biuro">
                  <input type="hidden" name="action" value="save_invoice">
                  <?php // Opcjonalnie: Pole na token CSRF ?>
                  <?php /* <input type="hidden" name="tn_csrf_token" value="<?php echo htmlspecialchars($tn_token_csrf ?? '', ENT_QUOTES, 'UTF-8'); ?>"> */ ?>

                  <div class="row g-3 mb-4">
                      <div class="col-md-4">
                          <label for="invoice_number" class="form-label">Numer Faktury/Rachunku <span class="text-danger">*</span></label>
                          <input type="text" class="form-control form-control-sm" id="invoice_number" name="invoice_number" required>
                      </div>
                      <div class="col-md-4">
                          <label for="order_date" class="form-label">Data wystawienia</label>
                          <input type="date" class="form-control form-control-sm" id="order_date" name="order_date" value="<?php echo date('Y-m-d'); ?>">
                      </div>
                       <div class="col-md-4">
                          <label for="currency" class="form-label">Waluta</label>
                          <input type="text" class="form-control form-control-sm" id="currency" name="currency" value="PLN">
                      </div>
                  </div>

                   <div class="row g-3 mb-4">
                       <div class="col-md-6">
                           <h6>Dane Sprzedawcy:</h6>
                           <div class="mb-2">
                               <label for="seller_name" class="form-label">Nazwa <span class="text-danger">*</span></label>
                               <input type="text" class="form-control form-control-sm" id="seller_name" name="seller_name" required>
                           </div>
                            <div class="mb-2">
                               <label for="seller_address" class="form-label">Adres</label>
                               <input type="text" class="form-control form-control-sm" id="seller_address" name="seller_address">
                           </div>
                            <div class="mb-2">
                               <label for="seller_nip" class="form-label">NIP</label>
                               <input type="text" class="form-control form-control-sm" id="seller_nip" name="seller_nip">
                           </div>
                            <div class="mb-2">
                               <label for="seller_bank_account" class="form-label">Numer Konta</label>
                               <input type="text" class="form-control form-control-sm" id="seller_bank_account" name="seller_bank_account">
                           </div>
                       </div>
                       <div class="col-md-6">
                           <h6>Dane Nabywcy:</h6>
                            <div class="mb-2">
                               <label for="buyer_name" class="form-label">Nazwa <span class="text-danger">*</span></label>
                               <input type="text" class="form-control form-control-sm" id="buyer_name" name="buyer_name" required>
                           </div>
                            <div class="mb-2">
                               <label for="buyer_address" class="form-label">Adres</label>
                               <input type="text" class="form-control form-control-sm" id="buyer_address" name="buyer_address">
                           </div>
                            <div class="mb-2">
                               <label for="buyer_nip" class="form-label">NIP</label>
                               <input type="text" class="form-control form-control-sm" id="buyer_nip" name="buyer_nip">
                           </div>
                       </div>
                   </div>

                   <h6>Pozycje zamówienia:</h6>
                   <div id="invoice_items_container" class="mb-3">
                       <div class="row g-2 mb-2 invoice-item-row">
                           <div class="col-md-4">
                               <input type="text" class="form-control form-control-sm item-name" name="item_name[]" placeholder="Nazwa pozycji" >
                           </div>
                           <div class="col-md-1">
                               <input type="number" step="0.01" class="form-control form-control-sm item-quantity" name="item_quantity[]" placeholder="Ilość" value="1" min="0.01">
                           </div>
                            <div class="col-md-1">
                               <input type="text" class="form-control form-control-sm item-unit" name="item_unit[]" placeholder="Jedn." value="szt.">
                           </div>
                           <div class="col-md-2">
                               <input type="number" step="0.01" class="form-control form-control-sm item-price-net" name="item_unit_price_net[]" placeholder="Cena jedn. netto" min="0">
                           </div>
                           <div class="col-md-1">
                               <input type="number" step="1" class="form-control form-control-sm item-tax-rate" name="item_tax_rate[]" placeholder="VAT %" value="23">
                           </div>
                            <div class="col-md-2">
                               <input type="text" class="form-control form-control-sm item-price-gross" placeholder="Wartość brutto" readonly tabindex="-1">
                            </div>
                           <div class="col-md-1 d-flex align-items-end">
                               <button type="button" class="btn btn-danger btn-sm remove-item-btn"><i class="bi bi-x"></i></button>
                           </div>
                       </div>
                       </div>

                   <button type="button" class="btn btn-secondary btn-sm mb-4" id="add_item_btn"><i class="bi bi-plus"></i> Dodaj pozycję</button>

                   <div class="row g-3 mb-4">
                       <div class="col-md-6">
                           <label for="payment_method" class="form-label">Metoda płatności</label>
                           <input type="text" class="form-control form-control-sm" id="payment_method" name="payment_method" value="Przelew">
                       </div>
                       <div class="col-md-6">
                           <label for="payment_due_date" class="form-label">Termin płatności</label>
                           <input type="date" class="form-control form-control-sm" id="payment_due_date" name="payment_due_date" value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>">
                       </div>
                   </div>

              </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
            <button type="submit" form="addDocumentModal form" class="btn btn-primary"><i class="bi bi-save me-1"></i> Zapisz Dokument</button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="editDocumentModal" tabindex="-1" aria-labelledby="editDocumentModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editDocumentModalLabel">Edytuj Dokument</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
              <form method="POST" action="./biuro" id="editDocumentForm">
                  <input type="hidden" name="action" value="edit_invoice">
                   <input type="hidden" name="invoice_number_to_edit" id="invoice_number_to_edit" value=""> <?php // Pole na numer dokumentu do edycji ?>
                  <?php // Opcjonalnie: Pole na token CSRF ?>
                  <?php /* <input type="hidden" name="tn_csrf_token" value="<?php echo htmlspecialchars($tn_token_csrf ?? '', ENT_QUOTES, 'UTF-8'); ?>"> */ ?>

                  <p>Tutaj będzie formularz do edycji danych dokumentu.</p>
                  <p>Wymaga to implementacji logiki PHP i JavaScript do:</p>
                  <ol>
                      <li>Pobrania danych konkretnego dokumentu po kliknięciu "Edytuj".</li>
                      <li>Wypełnienia pól tego formularza pobranymi danymi.</li>
                      <li>Obsługi zapisu zaktualizowanych danych do pliku JSON (znalezienie i podmiana obiektu w tablicy).</li>
                  </ol>
                  <p class="text-muted">Na razie jest to tylko placeholder.</p>

                  <?php // Przykład pól (musisz dodać wszystkie potrzebne pola) ?>
                   <div class="mb-3">
                       <label for="edit_invoice_number" class="form-label">Numer Faktury/Rachunku</label>
                       <input type="text" class="form-control" id="edit_invoice_number" name="invoice_number" readonly> <?php // Numer zazwyczaj nieedytowalny ?>
                   </div>
                   <div class="mb-3">
                       <label for="edit_seller_name" class="form-label">Nazwa Sprzedawcy</label>
                       <input type="text" class="form-control" id="edit_seller_name" name="seller_name">
                   </div>
                   <?php // Dodaj pola dla nabywcy, pozycji, itp. ?>

              </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
            <button type="submit" form="editDocumentForm" class="btn btn-primary"><i class="bi bi-save me-1"></i> Zapisz Zmiany</button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="settingsModalLabel">Ustawienia Biura</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
              <?php // Komunikat po przesłaniu ustawień (jeśli był błąd) ?>
              <?php echo $settings_submit_message; ?>
              <form method="POST" action="./biuro" id="settingsForm">
                  <input type="hidden" name="action" value="save_settings">
                   <?php // Opcjonalnie: Pole na token CSRF ?>
                   <?php /* <input type="hidden" name="tn_csrf_token" value="<?php echo htmlspecialchars($tn_token_csrf ?? '', ENT_QUOTES, 'UTF-8'); ?>"> */ ?>

                  <h6>Dane Sprzedawcy:</h6>
                  <div class="row g-3 mb-4">
                      <div class="col-md-6">
                          <label for="seller_name" class="form-label">Nazwa Sprzedawcy <span class="text-danger">*</span></label>
                          <input type="text" class="form-control form-control-sm" id="seller_name" name="seller_name" value="<?php echo htmlspecialchars($settings_data['seller_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                      </div>
                      <div class="col-md-6">
                          <label for="seller_address" class="form-label">Adres Sprzedawcy</label>
                          <input type="text" class="form-control form-control-sm" id="seller_address" name="seller_address" value="<?php echo htmlspecialchars($settings_data['seller_address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                      </div>
                      <div class="col-md-6">
                          <label for="seller_nip" class="form-label">NIP Sprzedawcy</label>
                          <input type="text" class="form-control form-control-sm" id="seller_nip" name="seller_nip" value="<?php echo htmlspecialchars($settings_data['seller_nip'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                      </div>
                       <div class="col-md-6">
                          <label for="seller_bank_account" class="form-label">Numer Konta Sprzedawcy</label>
                          <input type="text" class="form-control form-control-sm" id="seller_bank_account" name="seller_bank_account" value="<?php echo htmlspecialchars($settings_data['seller_bank_account'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                      </div>
                  </div>

                  <h6>Ustawienia Dokumentów:</h6>
                   <div class="row g-3 mb-4">
                       <div class="col-md-6">
                           <label for="invoice_number_format" class="form-label">Format Numeracji Faktur/Rachunków <span class="text-danger">*</span></label>
                           <input type="text" class="form-control form-control-sm" id="invoice_number_format" name="invoice_number_format" value="<?php echo htmlspecialchars($settings_data['invoice_number_format'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Np. FV/YYYY/MM/[NR]" required>
                           <small class="form-text text-muted">Użyj [NR] dla kolejnego numeru, [YYYY] dla roku, [MM] dla miesiąca.</small>
                       </div>
                        <div class="col-md-6">
                           <label for="default_vat_rate" class="form-label">Domyślna stawka VAT (%)</label>
                           <input type="number" step="1" class="form-control form-control-sm" id="default_vat_rate" name="default_vat_rate" value="<?php echo htmlspecialchars($settings_data['default_vat_rate'] ?? '23', ENT_QUOTES, 'UTF-8'); ?>" min="0">
                       </div>
                   </div>
              </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
            <button type="submit" form="settingsForm" class="btn btn-primary"><i class="bi bi-save me-1"></i> Zapisz Ustawienia</button>
          </div>
        </div>
      </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Skrypt dla modala dodawania pozycji w formularzu dodawania
            const addDocumentModal = document.getElementById('addDocumentModal');
            if (addDocumentModal) {
                const itemsContainer = addDocumentModal.querySelector('#invoice_items_container');
                const addItemBtn = addDocumentModal.querySelector('#add_item_btn');
                const itemRowTemplate = addDocumentModal.querySelector('.invoice-item-row');

                function addItemRow() {
                    const newItemRow = itemRowTemplate.cloneNode(true);
                    newItemRow.style.display = 'flex';
                    newItemRow.classList.remove('invoice-item-row');
                    newItemRow.classList.add('actual-item-row');

                    newItemRow.querySelectorAll('input').forEach(input => {
                         if (input.type !== 'button' && input.type !== 'submit') {
                              input.value = '';
                         }
                         if (input.classList.contains('item-quantity')) input.value = '1';
                         if (input.classList.contains('item-unit')) input.value = 'szt.';
                         if (input.classList.contains('item-tax-rate')) input.value = '23';
                         if (input.classList.contains('item-price-gross')) input.value = '';
                    });

                    newItemRow.querySelector('.remove-item-btn').addEventListener('click', function() {
                        newItemRow.remove();
                    });

                    const quantityInput = newItemRow.querySelector('.item-quantity');
                    const priceNetInput = newItemRow.querySelector('.item-price-net');
                    const priceGrossInput = newItemRow.querySelector('.item-price-gross');
                    const taxRateInput = newItemRow.querySelector('.item-tax-rate');

                    function calculateGross() {
                        const quantity = parseFloat(quantityInput.value) || 0;
                        const priceNet = parseFloat(priceNetInput.value) || 0;
                        const taxRate = parseInt(taxRateInput.value) || 0;

                        const itemNet = quantity * priceNet;
                        const itemTax = itemNet * (taxRate / 100);
                        const itemGross = itemNet + itemTax;

                        priceGrossInput.value = itemGross.toFixed(2);
                    }

                    quantityInput.addEventListener('input', calculateGross);
                    priceNetInput.addEventListener('input', calculateGross);
                    taxRateInput.addEventListener('input', calculateGross);

                    itemsContainer.appendChild(newItemRow);
                }

                addItemBtn.addEventListener('click', addItemRow);

                 if (itemsContainer.querySelectorAll('.actual-item-row').length === 0) {
                     addItemRow();
                 }

                // Opcjonalnie: Obsługa otwierania modala dodawania, np. resetowanie formularza
                addDocumentModal.addEventListener('show.bs.modal', function (event) {
                    const form = addDocumentModal.querySelector('form');
                    form.reset(); // Zresetuj formularz przy otwarciu
                    // Usuń wszystkie dodane dynamicznie pozycje oprócz szablonu
                    itemsContainer.querySelectorAll('.actual-item-row').forEach(row => row.remove());
                    addItemRow(); // Dodaj jedną pustą pozycję
                });
            }

            // Skrypt dla modala edycji dokumentu
            const editDocumentModal = document.getElementById('editDocumentModal');
            if (editDocumentModal) {
                // Obsługa otwierania modala edycji
                editDocumentModal.addEventListener('show.bs.modal', function (event) {
                    // Przycisk, który wywołał modal
                    const button = event.relatedTarget;
                    // Pobierz numer dokumentu z atrybutu data-* (np. data-bs-invoice-number="FV/10/2023/123")
                    const invoiceNumber = button.getAttribute('data-bs-invoice-number');

                    // !!! TUTAJ POTRZEBNA LOGIKA POBIERANIA DANYCH DOKUMENTU !!!
                    // 1. Wyślij zapytanie AJAX do skryptu PHP (np. do tego samego pliku z odpowiednią akcją GET)
                    //    aby pobrać dane dokumentu o numerze invoiceNumber.
                    // 2. Po otrzymaniu danych, wypełnij pola formularza w modalu edycji.
                    // 3. Ustaw wartość ukrytego pola #invoice_number_to_edit.

                    // Na razie tylko ustawiamy numer dokumentu w tytule modala i ukrytym polu
                    const modalTitle = editDocumentModal.querySelector('.modal-title');
                    const invoiceNumberToEditInput = editDocumentModal.querySelector('#invoice_number_to_edit');
                     const editInvoiceNumberInput = editDocumentModal.querySelector('#edit_invoice_number'); // Pole tylko do odczytu

                    modalTitle.textContent = 'Edytuj Dokument: ' + (invoiceNumber || 'BRAK');
                    invoiceNumberToEditInput.value = invoiceNumber || '';
                     if(editInvoiceNumberInput) {
                        editInvoiceNumberInput.value = invoiceNumber || '';
                     }

                    // !!! PRZYKŁADOWE WYPEŁNIENIE PÓL (ZASTĄP RZECZYWISTYM POBIERANIEM DANYCH) !!!
                    // editDocumentModal.querySelector('#edit_seller_name').value = 'Przykładowy Sprzedawca';
                    // ... wypełnij inne pola ...
                });
            }


            // Skrypt dla modala ustawień
            const settingsModal = document.getElementById('settingsModal');
            if (settingsModal) {
                // Obsługa otwierania modala ustawień (opcjonalnie: załadowanie aktualnych ustawień)
                settingsModal.addEventListener('show.bs.modal', function (event) {
                    // W tym uproszczonym modelu ustawienia są ładowane przy każdym załadowaniu strony PHP.
                    // Jeśli chcesz ładować je tylko przy otwarciu modala, musisz dodać tutaj zapytanie AJAX.

                    // const form = settingsModal.querySelector('form');
                    // // Przykład ładowania danych (wymaga AJAX)
                    // fetch('./biuro?action=get_settings') // Przykładowy URL do pobrania ustawień
                    //     .then(response => response.json())
                    //     .then(settings => {
                    //         form.querySelector('#seller_name').value = settings.seller_name || '';
                    //         // ... wypełnij inne pola ...
                    //     })
                    //     .catch(error => console.error('Błąd ładowania ustawień:', error));
                });
            }

        });
    </script>


