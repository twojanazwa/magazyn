<?php
/**
 * Akcja - Zapisz Kuriera (Dodawanie / Edycja).
 * Obsługuje zapisywanie danych nowego kuriera lub aktualizację istniejącego.
 * Wersja: 1.2 (Dostosowanie do tekstowego ID jako klucza)
 */

// Wczytanie potrzebnych plików i funkcji
require_once __DIR__ . '/../../config/tn_config.php';
require_once __DIR__ . '/../functions/tn_security_helpers.php';
require_once __DIR__ . '/../functions/tn_flash_messages.php';
require_once __DIR__ . '/../functions/tn_data_helpers.php';
require_once __DIR__ . '/../functions/tn_url_helpers.php';

// --------------------------------------------------------------------------
// 1. Bezpieczeństwo i podstawowa walidacja
// --------------------------------------------------------------------------

// Sprawdzenie metody żądania
if (!($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_courier')) {
    // Logika błędu, jeśli dostęp jest nieprawidłowy
    tn_ustaw_komunikat_flash('Błędna metoda żądania.', 'danger');
    $redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('couriers_list') : 'index.php?page=couriers_list';
    header("Location: " . $redirect_url);
    exit;
}

// Sprawdzenie logowania (jeśli wymagane)
if (session_status() == PHP_SESSION_NONE) session_start(); // Upewnij się, że sesja jest aktywna
if (!isset($_SESSION['tn_user_id'])) {
     tn_ustaw_komunikat_flash("Brak autoryzacji do wykonania tej akcji.", 'danger');
     $redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('login_page') : 'login.php';
     header("Location: " . $redirect_url);
     exit;
}

// Weryfikacja tokena CSRF
if (!tn_waliduj_token_csrf($_POST['tn_csrf_token'] ?? null)) {
    tn_ustaw_komunikat_flash('Błąd weryfikacji formularza (CSRF). Spróbuj ponownie.', 'danger');
    $redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('couriers_list') : 'index.php?page=couriers_list';
    header("Location: " . $redirect_url);
    exit;
}

// --------------------------------------------------------------------------
// 2. Pobranie i oczyszczenie danych wejściowych
// --------------------------------------------------------------------------

$courier_id_text = isset($_POST['courier_id_text']) ? trim($_POST['courier_id_text']) : '';
$courier_original_id = isset($_POST['courier_original_id']) ? trim($_POST['courier_original_id']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$tracking_url_pattern = isset($_POST['tracking_url_pattern']) ? trim($_POST['tracking_url_pattern']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
$is_active = isset($_POST['is_active']) && $_POST['is_active'] == '1';

// Określenie trybu: 'add' lub 'edit'
$mode = empty($courier_original_id) ? 'add' : 'edit';
$courier_id_to_process = ($mode === 'edit') ? $courier_original_id : $courier_id_text;

// --------------------------------------------------------------------------
// 3. Walidacja danych
// --------------------------------------------------------------------------
$errors = [];

if (empty($name)) {
    $errors[] = "Nazwa kuriera jest wymagana.";
}

if ($mode === 'add') {
    if (empty($courier_id_text)) {
        $errors[] = "Tekstowe ID kuriera jest wymagane (np. 'inpost_kurier').";
    } elseif (!preg_match('/^[a-z0-9_]+$/', $courier_id_text)) {
        $errors[] = "Tekstowe ID kuriera może zawierać tylko małe litery (a-z), cyfry (0-9) i znak podkreślenia (_).";
    } else {
        $existing_couriers = tn_laduj_kurierow(TN_PLIK_KURIERZY);
        if (isset($existing_couriers[$courier_id_text])) {
            $errors[] = "Kurier o podanym ID ('" . htmlspecialchars($courier_id_text) . "') już istnieje.";
        }
    }
} elseif ($mode === 'edit') {
    if (empty($courier_original_id)) {
         $errors[] = "Błąd edycji: Brak oryginalnego ID kuriera.";
         error_log("Błąd krytyczny: Próba edycji kuriera bez podania oryginalnego ID.");
    }
}

if (!empty($tracking_url_pattern) && !filter_var($tracking_url_pattern, FILTER_VALIDATE_URL) && strpos($tracking_url_pattern, '{tracking_number}') === false) {
     $errors[] = "Podany wzorzec URL śledzenia wydaje się niepoprawny. Powinien być pełnym adresem URL lub zawierać '{tracking_number}'.";
}

// --------------------------------------------------------------------------
// 4. Przetwarzanie danych (jeśli brak błędów)
// --------------------------------------------------------------------------

if (empty($errors)) {
    $couriers = tn_laduj_kurierow(TN_PLIK_KURIERZY);
    $success_message = '';

    $courier_data = [
        'id' => $courier_id_to_process, // ID tekstowe jest teraz częścią danych
        'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        'tracking_url_pattern' => htmlspecialchars($tracking_url_pattern, ENT_QUOTES, 'UTF-8'),
        'notes' => htmlspecialchars($notes, ENT_QUOTES, 'UTF-8'),
        'is_active' => $is_active,
        'last_updated' => date('Y-m-d H:i:s')
    ];

    if ($mode === 'add') {
        $courier_data['created_at'] = date('Y-m-d H:i:s');
        $couriers[$courier_id_to_process] = $courier_data;
        $success_message = "Pomyślnie dodano nowego kuriera: " . htmlspecialchars($name);
    } else { // $mode === 'edit'
        if (isset($couriers[$courier_original_id])) {
            $courier_data['created_at'] = $couriers[$courier_original_id]['created_at'] ?? date('Y-m-d H:i:s');
            $couriers[$courier_original_id] = $courier_data;
            $success_message = "Pomyślnie zaktualizowano dane kuriera: " . htmlspecialchars($name);
        } else {
            // Błąd krytyczny, przekierowanie nastąpi poniżej
            $errors[] = "Błąd krytyczny: Nie znaleziono kuriera o ID '" . htmlspecialchars($courier_original_id) . "' do edycji.";
            error_log($errors[count($errors)-1]); // Zaloguj błąd
        }
    }

    // Zapisz zmiany (tylko jeśli nie było błędu krytycznego przy edycji)
    if (empty($errors)) {
        if (tn_zapisz_kurierow(TN_PLIK_KURIERZY, $couriers)) {
            tn_ustaw_komunikat_flash($success_message, 'success');
        } else {
            tn_ustaw_komunikat_flash('Wystąpił błąd podczas zapisywania danych kuriera.', 'danger');
            error_log("Błąd zapisu pliku kurierów: " . TN_PLIK_KURIERZY);
        }
    }
}

// --------------------------------------------------------------------------
// 5. Obsługa błędów walidacji lub zapisu i przekierowanie
// --------------------------------------------------------------------------
if (!empty($errors)) {
    tn_ustaw_komunikat_flash("Popraw błędy formularza: <br>- " . implode('<br>- ', $errors), 'danger');
    // Opcjonalnie: Zapisz dane z formularza w sesji
    // $_SESSION['tn_flash_form_data']['courier'] = $_POST;
}

// Przekieruj z powrotem do listy kurierów
$redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('couriers_list') : 'index.php?page=couriers_list';
header("Location: " . $redirect_url);
exit;
?>