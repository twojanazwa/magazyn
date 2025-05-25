<?php
$notes_file_path = __DIR__ . '/TNbazaDanych/dashboard_notes.json';

$data_directory = dirname($notes_file_path);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Niedozwolona metoda żądania. Dozwolona tylko metoda POST.']);
    http_response_code(405); 
    exit;
}
$raw_data = file_get_contents('php://input');

if ($raw_data === false) {
      echo json_encode(['status' => 'error', 'message' => 'Nie udało się odczytać danych wejściowych.']);
    http_response_code(400);
    exit;
}
$data = json_decode($raw_data, true); 

if (json_last_error() !== JSON_ERROR_NONE) {
     echo json_encode(['status' => 'error', 'message' => 'Nieprawidłowy format danych JSON. Błąd: ' . json_last_error_msg()]);
    http_response_code(400); 
    exit;
}
if (!isset($data['notes'])) {
    echo json_encode(['status' => 'error', 'message' => 'Brakujący klucz "notes" w przesłanych danych.']);
    http_response_code(400); 
    exit;
}
$notes_content = trim($data['notes']);

$notes_to_save = ['notes' => $notes_content];

$json_to_save = json_encode($notes_to_save, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if ($json_to_save === false) {
    echo json_encode(['status' => 'error', 'message' => 'Nie udało się zakodować danych do formatu JSON.']);
    http_response_code(500); 
    exit;
}
if (!is_dir($data_directory)) {

    if (!mkdir($data_directory, 0775, true)) {
	echo json_encode(['status' => 'error', 'message' => 'Nie udało się utworzyć katalogu `data`. Sprawdź uprawnienia.']);
         http_response_code(500); 
         exit;
    }
}
if (!is_writable($data_directory)) {

     echo json_encode(['status' => 'error', 'message' => 'Katalog `data` nie ma uprawnień do zapisu.']);
     http_response_code(500); 
     exit;
}
if (file_put_contents($notes_file_path, $json_to_save) === false) {

    echo json_encode(['status' => 'error', 'message' => 'Podczas zapisu notatek wystąpił błąd. Sprawdź uprawnienia.']);
    http_response_code(500); 

} else {

    echo json_encode(['status' => 'success', 'message' => 'Notatki zapisane pomyślnie.']);
    http_response_code(200); 
}
exit;
?>