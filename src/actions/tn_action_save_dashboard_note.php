<?php
// src/actions/tn_action_save_dashboard_note.php

// Zależności
require_once __DIR__ . '/../../config/tn_config.php';
require_once __DIR__ . '/../functions/tn_security_helpers.php';
require_once __DIR__ . '/../functions/tn_flash_messages.php';
require_once __DIR__ . '/../functions/tn_data_helpers.php';
require_once __DIR__ . '/../functions/tn_url_helpers.php';

// Weryfikacja Metody i Akcji
if (!($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_dashboard_note')) {
    header("Location: " . tn_generuj_url('dashboard')); exit;
}

// Sprawdzenie Sesji i Logowania
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['tn_user_id'])) {
     tn_ustaw_komunikat_flash("Musisz być zalogowany, aby dodawać notatki.", 'warning');
     header('Location: login.php'); exit;
}

// Walidacja tokenu CSRF
if (!tn_waliduj_token_csrf($_POST['tn_csrf_token'] ?? null)) {
    tn_ustaw_komunikat_flash("Błąd bezpieczeństwa (token CSRF).", 'danger');
    header('Location: ' . tn_generuj_url('dashboard')); exit;
}

// Pobranie i walidacja danych notatki
$tn_wiadomosc = trim($_POST['dashboard_note_message'] ?? '');
$tn_bledy = [];

if (empty($tn_wiadomosc)) {
    $tn_bledy[] = "Treść notatki nie może być pusta.";
}
// Można dodać limit długości, np. mb_strlen($tn_wiadomosc) > 1000

if (empty($tn_bledy)) {
    $tn_notatki = tn_laduj_notatki_dashboard(TN_PLIK_NOTATKI_DASHBOARD);
    $tn_nowe_id = tn_pobierz_nastepne_id_notatki($tn_notatki);

    $tn_nowa_notatka = [
        'id' => $tn_nowe_id,
        'timestamp' => time(), // Zapisz timestamp UNIX
        'user_id' => $_SESSION['tn_user_id'], // ID użytkownika
        'user_name' => $_SESSION['tn_user_fullname'] ?? $_SESSION['tn_username'], // Nazwa użytkownika
        'message' => htmlspecialchars($tn_wiadomosc, ENT_QUOTES, 'UTF-8'), // Zapisz oczyszczoną wiadomość
        'is_pinned' => false // Domyślnie nieprzypięta
    ];

    $tn_notatki[] = $tn_nowa_notatka;

    if (tn_zapisz_notatki_dashboard(TN_PLIK_NOTATKI_DASHBOARD, $tn_notatki)) {
        tn_ustaw_komunikat_flash("Notatka została dodana.", 'success');
    } else {
        tn_ustaw_komunikat_flash("Wystąpił błąd podczas zapisywania notatki.", 'danger');
        error_log("Błąd zapisu pliku notatek pulpitu: " . TN_PLIK_NOTATKI_DASHBOARD);
    }
} else {
     tn_ustaw_komunikat_flash("Nie dodano notatki. Popraw błędy: <br>- " . implode('<br>- ', $tn_bledy), 'warning');
}

// Przekieruj z powrotem do dashboardu
header("Location: " . tn_generuj_url('dashboard'));
exit;
?>