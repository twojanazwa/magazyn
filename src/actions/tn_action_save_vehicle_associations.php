<?php
// src/actions/tn_action_save_vehicle_associations.php
/**
 * Akcja obsługująca zapis zmian w powiązaniach pojazdów dla produktu.
 * Oczekuje danych POST: product_id, vehicle_info_raw, tn_csrf_token.
 *
 * Zakłada dostępność funkcji:
 * tn_waliduj_token_csrf(), tn_ustaw_komunikat_flash(), tn_generuj_url(),
 * tn_laduj_produkty(), tn_zapisz_produkty()
 */

// Upewnij się, że żądanie jest typu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    tn_ustaw_komunikat_flash('Nieprawidłowa metoda żądania.', 'danger');
    header('Location: ' . tn_generuj_url('dashboard')); // Przekieruj na pulpit
    exit;
}

// Walidacja tokenu CSRF (powinna być już wykonana w index.php, ale dodatkowe sprawdzenie nie zaszkodzi)
// Jeśli index.php gwarantuje walidację, ten blok można usunąć.
// if (!tn_waliduj_token_csrf($_POST['tn_csrf_token'] ?? null)) {
//     tn_ustaw_komunikat_flash('Błąd bezpieczeństwa: Nieprawidłowy token CSRF.', 'danger');
//     header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? tn_generuj_url('dashboard')));
//     exit;
// }

// Sprawdź, czy wymagane dane zostały przesłane
$product_id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
$vehicle_info_raw = $_POST['vehicle_info_raw'] ?? null; // Surowy tekst z textarea

if ($product_id === false || $product_id === null || $vehicle_info_raw === null) {
    tn_ustaw_komunikat_flash('Błąd: Brak wymaganych danych do zapisu.', 'danger');
    // Przekieruj z powrotem na listę produktów lub stronę zarządzania pojazdami, jeśli ID było znane
    $redirect_url = ($product_id !== false && $product_id !== null) ? tn_generuj_url('manage_vehicles', ['product_id' => $product_id]) : tn_generuj_url('products');
    header('Location: ' . $redirect_url);
    exit;
}

// Ścieżka do pliku danych produktów (zakładamy, że jest zdefiniowana w config.php)
defined('TN_PLIK_PRODUKTY') or define('TN_PLIK_PRODUKTY', TN_KORZEN_APLIKACJI . '/TNbazaDanych/products.json'); // Fallback

// Wczytaj istniejące dane produktów
$produkty = tn_laduj_produkty(TN_PLIK_PRODUKTY);

if ($produkty === false) {
    // Błąd ładowania danych
    error_log("Błąd zapisu powiązań pojazdów: Nie można wczytać danych produktów z " . TN_PLIK_PRODUKTY);
    tn_ustaw_komunikat_flash('Błąd serwer a: Nie można wczytać danych produktów.', 'danger');
    header('Location: ' . tn_generuj_url('products')); // Przekieruj na listę produktów
    exit;
}

// Znajdź produkt do zaktualizowania
$product_found = false;
foreach ($produkty as &$produkt) { // Użyj referencji (&) aby móc modyfikować element w pętli
    if (isset($produkt['id']) && (int)$produkt['id'] === $product_id) {
        // Zaktualizuj pole 'vehicle'
        $produkt['vehicle'] = $vehicle_info_raw;
        $product_found = true;
        break; // Znaleziono i zaktualizowano, można przerwać pętlę
    }
}
unset($produkt); // Usuń referencję po pętli

if (!$product_found) {
    // Produkt o podanym ID nie został znaleziony
    tn_ustaw_komunikat_flash('Błąd: Produkt o podanym ID nie został znaleziony.', 'danger');
    header('Location: ' . tn_generuj_url('products')); // Przekieruj na listę produktów
    exit;
}

// Zapisz zaktualizowane dane produktów
$save_success = tn_zapisz_produkty(TN_PLIK_PRODUKTY, $produkty);

if ($save_success) {
    tn_ustaw_komunikat_flash('Powiązania pojazdów zostały pomyślnie zapisane.', 'success');
    // Przekieruj z powrotem na stronę podglądu produktu, do zakładki "Pasuje do"
    header('Location: ' . tn_generuj_url('product_preview', ['id' => $product_id]) . '#tn-vehicle-tab');
    exit;
} else {
    // Błąd zapisu danych
    error_log("Błąd zapisu powiązań pojazdów: Nie można zapisać danych produktów do " . TN_PLIK_PRODUKTY);
    tn_ustaw_komunikat_flash('Błąd serwera: Nie można zapisać danych produktów.', 'danger');
    // Przekieruj z powrotem na stronę zarządzania pojazdami
    header('Location: ' . tn_generuj_url('manage_vehicles', ['product_id' => $product_id]));
    exit;
}

?>
