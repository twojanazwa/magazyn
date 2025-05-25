<?php
if (!($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login')) {
      header('Location: /logowanie');
    exit;
}
if (!function_exists('tn_waliduj_token_csrf')) require_once 'src/functions/tn_security_helpers.php';
if (!function_exists('tn_ustaw_komunikat_flash')) require_once 'src/functions/tn_flash_messages.php';
if (!function_exists('tn_laduj_json_dane')) require_once 'src/functions/tn_data_helpers.php'; 
if (!defined('TN_PLIK_UZYTKOWNICY')) {
    define('TN_PLIK_UZYTKOWNICY', TN_SCIEZKA_DANE . 'users.json');
}
if (!function_exists('tn_laduj_uzytkownikow')) {
    function tn_laduj_uzytkownikow(string $tn_plik): array {
        return tn_laduj_json_dane($tn_plik, []);
    }
}
if (!function_exists('tn_znajdz_uzytkownika_po_nazwie')) {
    function tn_znajdz_uzytkownika_po_nazwie(string $tn_username, array $tn_uzytkownicy): ?array {
        foreach ($tn_uzytkownicy as $user) {
            if (isset($user['username']) && strcasecmp($user['username'], $tn_username) === 0) {
                return $user;
            }
        }
        return null;
    }
}
if (!tn_waliduj_token_csrf($_POST['tn_csrf_token'] ?? null)) {
    tn_ustaw_komunikat_flash("Nieprawidłowy token.", 'danger');
    header('Location: /logowanie');
    exit;
}
$tn_wprowadzony_username = trim($_POST['tn_username'] ?? '');
$tn_wprowadzone_haslo = trim($_POST['tn_password'] ?? '');
if (empty($tn_wprowadzony_username) || empty($tn_wprowadzone_haslo)) {
    tn_ustaw_komunikat_flash("Logowanie: Nazwa użytkownika i hasło są wymagane.", 'warning');
    header('Location: /logowanie');
    exit;
}
$tn_uzytkownicy = tn_laduj_uzytkownikow(TN_PLIK_UZYTKOWNICY);
$tn_znaleziony_uzytkownik = tn_znajdz_uzytkownika_po_nazwie($tn_wprowadzony_username, $tn_uzytkownicy);
if ($tn_znaleziony_uzytkownik !== null && isset($tn_znaleziony_uzytkownik['password_hash']) && password_verify($tn_wprowadzone_haslo, $tn_znaleziony_uzytkownik['password_hash'])) {
      session_regenerate_id(true);
    $_SESSION['tn_user_id'] = $tn_znaleziony_uzytkownik['id'];
    $_SESSION['tn_username'] = $tn_znaleziony_uzytkownik['username'];
    $_SESSION['tn_user_fullname'] = $tn_znaleziony_uzytkownik['tn_imie_nazwisko'] ?? $tn_znaleziony_uzytkownik['username']; 
    //  $_SESSION['tn_user_role'] = $user['role'];

    header('Location: /');
    exit;

} else {
    tn_ustaw_komunikat_flash("Logowanie: Nieprawidłowe dane.", 'danger');
    header('Location: /logowanie');
    exit;
}
?>