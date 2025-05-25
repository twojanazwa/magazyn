<?php
// Plik: tnApp-imag/src/actions/tn_action_update_warehouse_quantity.php
session_start(); // Potrzebne, jeśli sprawdzamy logowanie

require_once __DIR__ . '/../functions/tn_data_helpers.php';
require_once __DIR__ . '/../functions/tn_security_helpers.php';

// Ustaw nagłówek odpowiedzi na JSON
header('Content-Type: application/json');

// Przygotuj domyślną odpowiedź błędu
$response = ['success' => false, 'message' => 'Nieznany błąd.'];

// Sprawdzenie, czy użytkownik jest zalogowany (opcjonalne, ale zalecane)
if (!is_logged_in()) {
    $response['message'] = 'Brak autoryzacji.';
    echo json_encode($response);
    exit;
}

// Sprawdź, czy metoda to POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Nieprawidłowa metoda żądania.';
    echo json_encode($response);
    exit;
}

// Pobierz i zwaliduj dane wejściowe
$location_id = trim($_POST['location_id'] ?? '');
// Użyj filter_input dla bezpieczeństwa
$new_quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (empty($location_id)) {
    $response['message'] = 'Brak ID lokalizacji.';
    echo json_encode($response);
    exit;
}

if ($new_quantity === false || $new_quantity <= 0) {
    $response['message'] = 'Nieprawidłowa ilość. Ilość musi być liczbą całkowitą większą od 0.';
    echo json_encode($response);
    exit;
}

// Wczytaj aktualny stan magazynu
$warehouse_state = load_data('warehouse');
$found = false;
$updated_state = [];

// Przejdź przez stan magazynu i zaktualizuj odpowiedni wpis
foreach ($warehouse_state as $entry) {
    if (isset($entry['location_id']) && $entry['location_id'] === $location_id) {
        // Znaleziono wpis, zaktualizuj ilość i timestamp
        $entry['quantity'] = $new_quantity;
        $entry['assigned_timestamp'] = time(); // Aktualizuj timestamp przy zmianie ilości
        $updated_state[] = $entry;
        $found = true;
    } else {
        // Dodaj niezmieniony wpis do nowej tablicy
        $updated_state[] = $entry;
    }
}

// Jeśli wpis został znaleziony i zaktualizowany
if ($found) {
    // Zapisz nowy stan magazynu
    if (save_data('warehouse', $updated_state)) {
        $response['success'] = true;
        $response['message'] = 'Ilość została zaktualizowana.';
    } else {
        $response['message'] = 'Błąd zapisu danych.';
    }
} else {
    // Jeśli nie znaleziono wpisu o podanym location_id
    $response['message'] = "Nie znaleziono lokalizacji o ID '$location_id'.";
}

// Zwróć odpowiedź JSON
echo json_encode($response);
exit;
?>