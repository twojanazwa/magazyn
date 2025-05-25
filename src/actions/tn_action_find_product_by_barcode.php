<?php
// src/actions/tn_action_find_product_by_barcode.php

// Ustawienie nagłówków odpowiedzi na JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Dostosuj w produkcji
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Dołączanie niezbędnych plików z funkcjami pomocniczymi i konfiguracją
// Zakładamy, że pliki te znajdują się w odpowiednich miejscach w folderze src/
require_once __DIR__ . '/../config/tn_config.php'; // Plik konfiguracyjny
require_once __DIR__ . '/../functions/tn_data_helpers.php'; // Funkcje do pracy z danymi (np. wczytaj/zapisz JSON)
require_once __DIR__ . '/../functions/tn_security_helpers.php'; // Funkcje bezpieczeństwa (np. weryfikacja API key)

// Weryfikacja klucza API (używając funkcji z tn_security_helpers.php, jeśli istnieje)
// Jeśli tn_security_helpers.php nie ma funkcji weryfikacji, użyjemy prostej weryfikacji GET jak w api.php
$api_key = $_GET['api_key'] ?? null;

// Sprawdź czy istnieje funkcja weryfikacji API key w helperach bezpieczeństwa
if (function_exists('tn_verify_api_key')) {
     if (!tn_verify_api_key($api_key)) {
        tn_send_json_response(['error' => 'Nieprawidlowy klucz API.'], 401);
     }
} else {
    // Prosta weryfikacja klucza API jak w api.php, jeśli helpery jej nie mają
    $settings = tn_get_settings(); // Zakładamy, że tn_data_helpers.php ma funkcję tn_get_settings()
    $allowed_api_key = $settings['api_key'] ?? null;
     if ($api_key !== $allowed_api_key || $allowed_api_key === null) {
         // Logowanie błędu API key, jeśli dostępne funkcje logowania z helperów
         if (function_exists('tn_log_error')) {
              tn_log_error("Nieprawidlowy lub brak klucza API. Zapytanie: " . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
         }
        http_response_code(401);
        echo json_encode(['error' => 'Nieprawidłowy klucz API.']);
        exit();
     }
}


// Sprawdzenie, czy żądanie zawiera kod kreskowy w parametrze GET
$barcode = $_GET['barcode'] ?? null;

if ($barcode === null || $barcode === '') {
    // Jeśli brak kodu kreskowego w żądaniu
    if (function_exists('tn_send_json_response')) {
        tn_send_json_response(['error' => 'Brakuje parametru kodu kreskowego (barcode).'], 400);
    } else {
         http_response_code(400);
         echo json_encode(['error' => 'Brakuje parametru kodu kreskowego (barcode).']);
         exit();
    }
}

// Wczytaj dane produktów (używając funkcji z tn_data_helpers.php, np. tn_get_products())
// Zakładamy, że tn_data_helpers.php ma funkcję tn_get_products() zwracającą tablicę produktów lub null/[]
$products = tn_get_products(); // Funkcja do zaimplementowania/użycia z helperów

if ($products === null) {
    // Błąd wczytywania danych produktów przez helper
    if (function_exists('tn_send_json_response')) {
        tn_send_json_response(['error' => 'Blad serwera podczas wczytywania danych produktow.'], 500);
    } else {
         http_response_code(500);
         echo json_encode(['error' => 'Blad serwera podczas wczytywania danych produktow.']);
         exit();
    }
}


// Wyszukaj produkt po kodzie kreskowym
$found_product = null;
foreach ($products as $product) {
    // Sprawdzamy numer katalogowy
    if (isset($product['tn_numer_katalogowy']) && (string)$product['tn_numer_katalogowy'] === (string)$barcode) {
        $found_product = $product;
        break;
    }
    // Sprawdzamy numery zamienników (jeśli są tablicą)
    if (isset($product['tn_numery_zamiennikow']) && is_array($product['tn_numery_zamiennikow'])) {
        foreach ($product['tn_numery_zamiennikow'] as $zamiennik) {
            if ((string)$zamiennik === (string)$barcode) {
                $found_product = $product;
                break 2; // Znaleziono, wyjdź z obu pętli
            }
        }
    }
    // TODO: Dodaj sprawdzanie innych pól, jeśli kody kreskowe mogą być w innych miejscach (np. tn_numer_oryginalu)
}

// Zwróć znaleziony produkt lub komunikat o braku
if ($found_product) {
    // Jeśli dostępne funkcje helperów do wysyłki JSON
     if (function_exists('tn_send_json_response')) {
        tn_send_json_response($found_product);
     } else {
         http_response_code(200);
         echo json_encode($found_product);
         exit();
     }
} else {
    // Nie znaleziono produktu
    if (function_exists('tn_send_json_response')) {
        tn_send_json_response(['error' => 'Nie znaleziono produktu dla podanego kodu kreskowego.'], 404);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Nie znaleziono produktu dla podanego kodu kreskowego.']);
        exit();
    }
}

// Przykładowe proste funkcje helperów, jeśli nie ma ich w dołączonych plikach
// Należy sprawdzić zawartość src/functions/tn_data_helpers.php, src/functions/tn_error_helpers.php, src/functions/tn_security_helpers.php
// i usunąć poniższe definicje, jeśli funkcje już tam istnieją.

if (!function_exists('tn_send_json_response')) {
    function tn_send_json_response($data, $status_code = 200) {
        http_response_code($status_code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
}

if (!function_exists('tn_get_products')) {
     // Ta funkcja powinna być w src/functions/tn_data_helpers.php
    function tn_get_products() {
        // Użyj funkcji wczytujDaneJson z api.php lub jej odpowiednika z tn_data_helpers.php
        // Na potrzeby tego skryptu, tymczasowo zdefiniujemy tu uproszczoną wersję
        // lub załączymy api.php (ale to mniej czyste rozwiązanie)
        // Zakładamy, że plik products.json jest dostępny przez DATA_PATH
         $products_file = __DIR__ . '/../TNbazaDanych/products.json'; // Dostosuj ścieżkę
         if (!file_exists($products_file) || !is_readable($products_file)) {
             //logError("Brak pliku produktow lub brak uprawnien w tn_get_products.");
             return null; // W przypadku błędu odczytu zwróć null
         }
         $json_data = file_get_contents($products_file);
         $data = json_decode($json_data, true);
          if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
              //logError("Blad parsowania JSON produktow w tn_get_products: " . json_last_error_msg());
              return null;
          }
          return is_array($data) ? $data : []; // Upewnij się, że zwracana jest tablica
    }
}

if (!function_exists('tn_get_settings')) {
     // Ta funkcja powinna być w src/functions/tn_data_helpers.php
    function tn_get_settings() {
         $settings_file = __DIR__ . '/../TNbazaDanych/ustawienia.json'; // Dostosuj ścieżkę
          if (!file_exists($settings_file) || !is_readable($settings_file)) {
              //logError("Brak pliku ustawien lub brak uprawnien w tn_get_settings.");
              return []; // Zwróć pustą tablicę zamiast null, aby uniknąć błędów
          }
         $json_data = file_get_contents($settings_file);
         $data = json_decode($json_data, true);
          if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
              //logError("Blad parsowania JSON ustawien w tn_get_settings: " . json_last_error_msg());
              return [];
          }
          return is_array($data) ? $data : []; // Upewnij się, że zwracana jest tablica
    }
}

// TODO: Jeśli tn_security_helpers.php nie ma funkcji tn_verify_api_key,
// zaimplementuj ją tutaj lub w helperach.
// if (!function_exists('tn_verify_api_key')) {
//     function tn_verify_api_key($api_key_to_check) {
//         $settings = tn_get_settings();
//         $allowed_key = $settings['api_key'] ?? null;
//         return $api_key_to_check !== null && $api_key_to_check === $allowed_key && $allowed_key !== null;
//     }
// }

// TODO: Jeśli tn_error_helpers.php nie ma funkcji tn_log_error,
// zaimplementuj ją tutaj lub w helperach.
// if (!function_exists('tn_log_error')) {
//      define('DEFAULT_ERROR_LOG_FILE', __DIR__ . '/../logs/errors.log'); // Dostosuj ścieżkę do logów
//     function tn_log_error($message) {
//         file_put_contents(defined('API_ERROR_LOG_FILE') ? API_ERROR_LOG_FILE : DEFAULT_ERROR_LOG_FILE, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
//     }
// }

?>