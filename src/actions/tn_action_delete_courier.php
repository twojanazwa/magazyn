<?php
// src/actions/tn_action_delete_courier.php

require_once __DIR__ . '/../../config/tn_config.php';
require_once __DIR__ . '/../functions/tn_security_helpers.php';
require_once __DIR__ . '/../functions/tn_flash_messages.php';
require_once __DIR__ . '/../functions/tn_data_helpers.php';
require_once __DIR__ . '/../functions/tn_url_helpers.php';

// Sprawdzenie metody i akcji
if (!($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete_courier')) {
    // Ciche przekierowanie, jeśli dostęp jest niepoprawny
    $redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('dashboard') : 'index.php';
    header("Location: " . $redirect_url);
    exit;
}

// Sprawdzenie logowania
if (session_status() == PHP_SESSION_NONE) session_start(); // Upewnij się, że sesja jest aktywna
if (!isset($_SESSION['tn_user_id'])) {
    tn_ustaw_komunikat_flash("Brak autoryzacji.", 'danger');
    $redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('login_page') : 'login.php';
    header("Location: " . $redirect_url);
    exit;
}

// Walidacja tokenu CSRF (z GET)
if (!tn_waliduj_token_csrf($_GET['tn_csrf_token'] ?? null)) {
    tn_ustaw_komunikat_flash("Błąd CSRF.", 'danger');
    $redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('couriers_list') : 'index.php?page=couriers_list';
    header("Location: " . $redirect_url);
    exit;
}

// Pobierz ID (tekstowe) jako string
$id_tekstowe = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);

if (empty($id_tekstowe)) {
    tn_ustaw_komunikat_flash("Nieprawidłowe ID kuriera do usunięcia.", 'warning');
} else {
    // Ładuj kurierów jako tablicę asocjacyjną (klucz => dane)
    $kurierzy = tn_laduj_kurierow(TN_PLIK_KURIERZY);

    // Sprawdź, czy klucz (ID tekstowe) istnieje
    if (isset($kurierzy[$id_tekstowe])) {
         $nazwa_usuwanego = $kurierzy[$id_tekstowe]['name'] ?? $id_tekstowe; // Nazwa dla komunikatu

         // Usuń element używając klucza tekstowego
         unset($kurierzy[$id_tekstowe]);

         // Zapisz zmodyfikowaną tablicę (tn_zapisz_kurierow konwertuje do listy obiektów)
         if (tn_zapisz_kurierow(TN_PLIK_KURIERZY, $kurierzy)) {
             tn_ustaw_komunikat_flash("Kurier '".htmlspecialchars($nazwa_usuwanego)."' (ID: ".htmlspecialchars($id_tekstowe).") został usunięty.", 'success');
         } else {
              tn_ustaw_komunikat_flash("Błąd zapisu danych po usunięciu kuriera.", 'danger');
              error_log("Błąd zapisu pliku kurierów po usunięciu ID: " . $id_tekstowe);
         }
    } else {
         tn_ustaw_komunikat_flash("Nie znaleziono kuriera o ID '".htmlspecialchars($id_tekstowe)."' do usunięcia.", 'warning');
    }
}

// Zawsze przekierowuj na listę kurierów
$redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('couriers_list') : 'index.php?page=couriers_list';
header("Location: " . $redirect_url);
exit;
?>