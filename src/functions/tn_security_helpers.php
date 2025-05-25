<?php
// src/functions/tn_security_helpers.php
// Wersja 1.1 (Usunięto zduplikowaną funkcję tn_generuj_link_akcji_get)

/**
 * Generuje lub pobiera token CSRF (Cross-Site Request Forgery) z sesji.
 * Token służy do ochrony formularzy przed atakami CSRF.
 *
 * @return string Token CSRF.
 */
function tn_generuj_token_csrf() : string {
    // Sprawdź, czy token już istnieje w sesji
    if (empty($_SESSION['tn_csrf_token'])) {
        try {
            // Jeśli nie, wygeneruj nowy, bezpieczny losowy token
            $_SESSION['tn_csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            // Obsługa błędu generowania tokenu (rzadkie, ale możliwe)
            error_log("Krytyczny błąd generowania tokenu CSRF: " . $e->getMessage());
            // W środowisku produkcyjnym można rzucić wyjątek lub zakończyć działanie.
            // W trybie deweloperskim można zwrócić stały token, ale to niebezpieczne.
            return 'błąd-generowania-tokenu-csrf'; // Zwróć błąd zamiast tokenu
        }
    }
    return $_SESSION['tn_csrf_token'];
}

/**
 * Waliduje podany token CSRF z tym zapisanym w sesji.
 * Używa bezpiecznej funkcji hash_equals do porównania, odpornej na ataki czasowe.
 *
 * @param string|null $tn_token Token przesłany przez użytkownika (z formularza POST lub parametru GET).
 * @return bool True, jeśli token jest prawidłowy i zgodny z sesją, False w przeciwnym razie.
 */
function tn_waliduj_token_csrf(?string $tn_token) : bool {
    // Sprawdź, czy token został przesłany i czy token istnieje w sesji
    if (empty($tn_token) || empty($_SESSION['tn_csrf_token'])) {
        error_log("Próba walidacji CSRF bez tokenu lub bez tokenu w sesji.");
        return false;
    }
    // Porównaj tokeny w sposób odporny na ataki czasowe
    return hash_equals($_SESSION['tn_csrf_token'], $tn_token);
}

// --- USUNIĘTO FUNKCJĘ tn_generuj_link_akcji_get() Z TEGO PLIKU ---
// Jej poprawna definicja znajduje się w pliku src/functions/tn_url_helpers.php

?>