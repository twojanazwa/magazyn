<?php
// src/actions/tn_action_update_profile.php
/**
 * Skrypt akcji do aktualizacji danych profilu użytkownika.
 * Obsługuje zmianę imienia, emaila, hasła i avatara.
 * Wersja: 1.0
 */

// Wczytanie potrzebnych plików i funkcji
// Zakładamy, że config.php jest już wczytany przez index.php
require_once __DIR__ . '/../../config/tn_config.php'; // Upewnij się, że ścieżka jest poprawna
require_once TN_SCIEZKA_SRC . 'functions/tn_security_helpers.php';
require_once TN_SCIEZKA_SRC . 'functions/tn_flash_messages.php';
require_once TN_SCIEZKA_SRC . 'functions/tn_data_helpers.php';
require_once TN_SCIEZKA_SRC . 'functions/tn_image_helpers.php'; // Potrzebne do obsługi avatara
require_once TN_SCIEZKA_SRC . 'functions/tn_url_helpers.php';   // Potrzebne do przekierowania

// --------------------------------------------------------------------------
// 1. Bezpieczeństwo i podstawowa walidacja
// --------------------------------------------------------------------------

// Sprawdzenie metody żądania i akcji
if (!($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile')) {
    // Cichy powrót, jeśli dostęp jest nieprawidłowy
    header('Location: ' . tn_generuj_url('dashboard'));
    exit;
}

// Sprawdzenie logowania - użytkownik musi być zalogowany, aby edytować swój profil
if (session_status() == PHP_SESSION_NONE) session_start(); // Upewnij się, że sesja jest aktywna
if (!isset($_SESSION['tn_user_id'])) {
     tn_ustaw_komunikat_flash("Brak autoryzacji.", 'danger');
     header('Location: ' . tn_generuj_url('login_page'));
     exit;
}

// Weryfikacja tokena CSRF
if (!tn_waliduj_token_csrf($_POST['tn_csrf_token'] ?? null)) {
    tn_ustaw_komunikat_flash('Błąd weryfikacji formularza (CSRF). Spróbuj ponownie.', 'danger');
    header('Location: ' . tn_generuj_url('profile')); // Wróć do profilu
    exit;
}

// --------------------------------------------------------------------------
// 2. Pobranie i oczyszczenie danych wejściowych
// --------------------------------------------------------------------------

$user_id_to_update = $_SESSION['tn_user_id']; // ID użytkownika z sesji
$new_fullname = isset($_POST['tn_imie_nazwisko']) ? trim(htmlspecialchars($_POST['tn_imie_nazwisko'], ENT_QUOTES, 'UTF-8')) : '';
$new_email = isset($_POST['email']) ? trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)) : '';
$current_password = isset($_POST['current_password']) ? trim($_POST['current_password']) : '';
$new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
$confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
$avatar_file_info = $_FILES['avatar_file'] ?? null; // Informacje o przesłanym pliku avatara

$errors = []; // Tablica na błędy walidacji
$user_data_updated = false; // Flaga informująca, czy cokolwiek zostało zmienione

// --------------------------------------------------------------------------
// 3. Walidacja danych
// --------------------------------------------------------------------------

// Walidacja emaila (jeśli podano)
if (!empty($new_email) && !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Podany adres e-mail jest nieprawidłowy.";
}

// Logika zmiany hasła
$password_change_requested = !empty($new_password) || !empty($confirm_password) || !empty($current_password);
$new_password_hash = null;

if ($password_change_requested) {
    if (empty($current_password)) {
        $errors[] = "Aby zmienić hasło, musisz podać swoje bieżące hasło.";
    }
    if (empty($new_password)) {
        $errors[] = "Nowe hasło nie może być puste.";
    } elseif (strlen($new_password) < 8) { // Minimalna długość hasła
        $errors[] = "Nowe hasło musi mieć co najmniej 8 znaków.";
    }
    if ($new_password !== $confirm_password) {
        $errors[] = "Nowe hasła nie są identyczne.";
    }

    // Jeśli walidacja nowego hasła przeszła pomyślnie, sprawdź bieżące hasło
    if (empty($errors) && !empty($current_password)) {
        $users = tn_laduj_uzytkownikow(TN_PLIK_UZYTKOWNICY);
        $current_user_data = null;
        foreach ($users as $user) {
            if (($user['id'] ?? null) == $user_id_to_update) {
                $current_user_data = $user;
                break;
            }
        }

        if ($current_user_data === null) {
             $errors[] = "Błąd wewnętrzny: Nie znaleziono danych bieżącego użytkownika.";
             error_log("Krytyczny błąd: Nie znaleziono użytkownika ID: {$user_id_to_update} podczas zmiany hasła.");
        } elseif (!isset($current_user_data['password_hash']) || !password_verify($current_password, $current_user_data['password_hash'])) {
            $errors[] = "Podane bieżące hasło jest nieprawidłowe.";
        } else {
            // Bieżące hasło poprawne, nowe hasło poprawne - przygotuj hash
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            if ($new_password_hash === false) {
                 $errors[] = "Wystąpił błąd podczas przetwarzania nowego hasła.";
                 error_log("Błąd password_hash() dla użytkownika ID: {$user_id_to_update}");
            }
        }
    }
}

// --------------------------------------------------------------------------
// 4. Przetwarzanie Avatara (jeśli nie ma innych błędów)
// --------------------------------------------------------------------------
$new_avatar_filename = null;
$avatar_error_message = '';

if (empty($errors) && $avatar_file_info !== null && $avatar_file_info['error'] === UPLOAD_ERR_OK) {
    // Potrzebujemy obecnej nazwy avatara, aby go usunąć
    // Załadujmy dane użytkownika ponownie (lub użyjmy $current_user_data, jeśli hasło było zmieniane)
    if (!isset($current_user_data)) {
        $users_for_avatar = tn_laduj_uzytkownikow(TN_PLIK_UZYTKOWNICY);
        foreach ($users_for_avatar as $user) { if (($user['id'] ?? null) == $user_id_to_update) { $current_user_data = $user; break; } }
    }
    $old_avatar_filename = $current_user_data['avatar'] ?? null;

    // Użyj funkcji pomocniczej do obsługi uploadu
    $upload_result = tn_handle_avatar_upload($avatar_file_info, $user_id_to_update, $old_avatar_filename, $avatar_error_message);

    if ($upload_result === false) {
        // Jeśli funkcja zwróciła false, błąd jest w $avatar_error_message
        $errors[] = $avatar_error_message;
    } else {
        // Upload się powiódł, $upload_result zawiera nową nazwę pliku
        $new_avatar_filename = $upload_result;
    }
}

// --------------------------------------------------------------------------
// 5. Aktualizacja Danych Użytkownika (jeśli brak błędów)
// --------------------------------------------------------------------------

if (empty($errors)) {
    $users = tn_laduj_uzytkownikow(TN_PLIK_UZYTKOWNICY); // Załaduj ponownie na wszelki wypadek
    $user_key_to_update = -1;

    foreach ($users as $key => $user) {
        if (($user['id'] ?? null) == $user_id_to_update) {
            $user_key_to_update = $key;
            break;
        }
    }

    if ($user_key_to_update !== -1) {
        // Sprawdź, czy cokolwiek się zmieniło
        $changes_made = false;
        if ($users[$user_key_to_update]['tn_imie_nazwisko'] != $new_fullname) {
             $users[$user_key_to_update]['tn_imie_nazwisko'] = $new_fullname; $changes_made = true;
        }
        if ($users[$user_key_to_update]['email'] != $new_email) {
             $users[$user_key_to_update]['email'] = $new_email; $changes_made = true;
        }
        if ($new_password_hash !== null) { // Jeśli hasło było zmieniane
             $users[$user_key_to_update]['password_hash'] = $new_password_hash; $changes_made = true;
        }
        if ($new_avatar_filename !== null) { // Jeśli avatar był zmieniany
             $users[$user_key_to_update]['avatar'] = $new_avatar_filename; $changes_made = true;
        }

        if ($changes_made) {
            // Zapisz zaktualizowaną tablicę użytkowników
            if (tn_zapisz_uzytkownikow(TN_PLIK_UZYTKOWNICY, $users)) {
                // Zaktualizuj dane w sesji, aby były widoczne od razu
                $_SESSION['tn_user_fullname'] = $new_fullname ?: $_SESSION['tn_username']; // Użyj loginu jako fallback
                if ($new_avatar_filename !== null) {
                    $_SESSION['tn_user_avatar_filename'] = $new_avatar_filename;
                }
                tn_ustaw_komunikat_flash("Profil został pomyślnie zaktualizowany.", 'success');
            } else {
                 $errors[] = "Wystąpił błąd podczas zapisywania zaktualizowanych danych profilu.";
                 error_log("Błąd zapisu pliku użytkowników po aktualizacji profilu ID: {$user_id_to_update}");
            }
        } else {
            // Nic się nie zmieniło
            tn_ustaw_komunikat_flash("Nie wprowadzono żadnych zmian w profilu.", 'info');
        }
    } else {
        // Ten błąd nie powinien wystąpić
        $errors[] = "Błąd wewnętrzny: Nie można znaleźć użytkownika do aktualizacji.";
        error_log("Krytyczny błąd: Nie znaleziono klucza użytkownika ID: {$user_id_to_update} do aktualizacji profilu.");
    }
}

// --------------------------------------------------------------------------
// 6. Obsługa Błędów Walidacji i Przekierowanie
// --------------------------------------------------------------------------

if (!empty($errors)) {
    tn_ustaw_komunikat_flash("Nie można zaktualizować profilu. Popraw błędy: <br>- " . implode('<br>- ', $errors), 'danger');
}

// Zawsze przekierowuj z powrotem do strony profilu
header('Location: ' . tn_generuj_url('profile'));
exit;
?>