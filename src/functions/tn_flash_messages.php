<?php
// src/functions/tn_flash_messages.php

/**
 * Ustawia komunikat flash (tymczasowy) w sesji.
 *
 * @param string $tn_wiadomosc Treść komunikatu.
 * @param string $tn_typ Typ komunikatu (success, danger, warning, info) - odpowiada klasom Bootstrap.
 */
function tn_ustaw_komunikat_flash(string $tn_wiadomosc, string $tn_typ = 'success') : void {
    // Upewnij się, że sesja jest aktywna
    if (session_status() == PHP_SESSION_NONE) {
        session_start(); // Spróbuj uruchomić, jeśli nieaktywna (chociaż powinna być z config.php)
    }
    if (!isset($_SESSION['tn_flash_messages'])) {
        $_SESSION['tn_flash_messages'] = [];
    }
    // Dodaj nowy komunikat do tablicy w sesji
    $_SESSION['tn_flash_messages'][] = ['message' => $tn_wiadomosc, 'type' => $tn_typ];
}

/**
 * Pobiera wszystkie komunikaty flash z sesji i usuwa je.
 * Zwraca tablicę komunikatów lub pustą tablicę.
 * UWAGA: Ta funkcja powinna być wywołana tylko raz na żądanie (np. w stopce przed JS).
 *
 * @return array Tablica komunikatów (każdy element to ['message' => ..., 'type' => ...])
 */
function tn_pobierz_i_wyczysc_komunikaty_flash() : array {
    // Upewnij się, że sesja jest aktywna
    if (session_status() == PHP_SESSION_NONE) {
        error_log("Próba dostępu do komunikatów flash bez aktywnej sesji!");
        return [];
    }

    // Sprawdź, czy istnieją jakiekolwiek komunikaty
    $tn_komunikaty = $_SESSION['tn_flash_messages'] ?? []; // Użyj operatora koalescencji null

    // Wyczyść komunikaty z sesji od razu po pobraniu
    if (!empty($tn_komunikaty)) {
        unset($_SESSION['tn_flash_messages']);
    }

    // Zwróć pobrane komunikaty (mogą być pustą tablicą)
    return is_array($tn_komunikaty) ? $tn_komunikaty : []; // Zawsze zwracaj tablicę
}

?>