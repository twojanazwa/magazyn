<?php
// src/actions/tn_action_logout.php

// Wymagana sesja do jej zniszczenia
require_once __DIR__ . '/../../config/tn_config.php'; // Załaduj config, który startuje sesję
require_once __DIR__ . '/../functions/tn_security_helpers.php'; // Dla tokenu CSRF (GET)

// Ta akcja powinna być wywoływana przez GET z linku z tokenem CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['action']) || $_GET['action'] !== 'logout') {
    header('Location: ../../index.php'); // Wróć do głównej strony, jeśli dostęp jest nieprawidłowy
    exit;
}

// Walidacja tokenu CSRF (przekazanego przez GET)
if (!tn_waliduj_token_csrf($_GET['tn_csrf_token'] ?? null)) {
     // Można ustawić komunikat, ale użytkownik i tak zostanie wylogowany poniżej
     // tn_ustaw_komunikat_flash("Błąd bezpieczeństwa przy wylogowaniu.", 'warning');
     // W przypadku błędu tokenu, lepiej po prostu wylogować dla bezpieczeństwa
}

// Zniszcz wszystkie zmienne sesji.
$_SESSION = array();

// Jeśli używane są ciasteczka sesji, usuń je.
// Uwaga: To zniszczy sesję, a nie tylko dane sesji!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Na koniec zniszcz sesję.
session_destroy();

// Przekieruj na stronę logowania
header('Location: ../../logowanie');
exit;
?>