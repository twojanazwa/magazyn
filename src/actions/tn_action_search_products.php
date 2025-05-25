<?php
// Plik: src/actions/tn_action_search_products.php
// Opis: Obsługuje żądania AJAX do wyszukiwania produktów w pliku products.json

header('Content-Type: application/json'); // Ustaw nagłówek odpowiedzi na JSON

// Założenie: Plik products.json znajduje się w katalogu TNbazaDanych/
$productsFilePath = __DIR__ . '/../../TNbazaDanych/products.json'; // Dostosuj ścieżkę, jeśli to konieczne

// Sprawdź, czy plik istnieje i jest czytelny
if (!file_exists($productsFilePath) || !is_readable($productsFilePath)) {
    echo json_encode(['success' => false, 'message' => 'Błąd serwera: Plik produktów nie istnieje lub brak dostępu.']);
    exit;
}

// Odczytaj zawartość pliku products.json
$productsJson = file_get_contents($productsFilePath);
$products = json_decode($productsJson, true);

// Sprawdź, czy dane zostały poprawnie odczytane i są tablicą
if ($products === null || !is_array($products)) {
    echo json_encode(['success' => false, 'message' => 'Błąd serwera: Nieprawidłowy format danych w pliku produktów.']);
    exit;
}

// Pobierz frazę wyszukiwania z zapytania GET
$searchTerm = $_GET['query'] ?? '';
$searchTerm = mb_strtolower(trim($searchTerm)); // Konwertuj na małe litery i usuń białe znaki

$results = [];
// Jeśli fraza wyszukiwania jest pusta, zwróć pustą listę (lub np. pierwsze kilka produktów - tu zwracamy pustą)
if (empty($searchTerm)) {
    echo json_encode(['success' => true, 'products' => []]);
    exit;
}

// Przeszukaj produkty
foreach ($products as $product) {
    // Sprawdź, czy produkt ma wymagane pola
    if (!isset($product['id']) || !isset($product['name'])) {
        continue; // Pomiń produkty bez ID lub nazwy
    }

    $productId = $product['id'];
    $productName = mb_strtolower($product['name']);
    $productCatalogNr = mb_strtolower($product['tn_numer_katalogowy'] ?? ''); // Użyj klucza z TN_warehouse_view.php

    // Sprawdź, czy fraza wyszukiwania znajduje się w ID, nazwie lub numerze katalogowym
    if (mb_strpos($productId, $searchTerm) !== false ||
        mb_strpos($productName, $searchTerm) !== false ||
        mb_strpos($productCatalogNr, $searchTerm) !== false) {

        // Dodaj pasujący produkt do wyników (ogranicz pola, jeśli chcesz)
        $results[] = [
            'id' => htmlspecialchars($productId),
            'name' => htmlspecialchars($product['name']),
            'catalog_nr' => htmlspecialchars($product['tn_numer_katalogowy'] ?? 'Brak'),
            'image' => htmlspecialchars($product['image'] ?? null), // Dodaj ścieżkę obrazka
            // Możesz dodać inne potrzebne pola
        ];
    }
}

// Zwróć wyniki wyszukiwania w formacie JSON
echo json_encode(['success' => true, 'products' => $results]);
?>