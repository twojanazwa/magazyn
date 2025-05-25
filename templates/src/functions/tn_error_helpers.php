<?php
// Plik: tnApp/src/functions/tn_error_helpers.php
/**
 * Funkcje pomocnicze związane z obsługą i wyświetlaniem błędów.
 */

declare(strict_types=1);

if (!function_exists('tn_wyswietl_strone_bledu')) {
    /**
     * Wyświetla niestandardową stronę błędu i kończy działanie skryptu.
     *
     * Ustawia odpowiedni kod odpowiedzi HTTP i próbuje załadować
     * dedykowany plik HTML dla błędu (np. tn-404.html) z folderu /templates/error_pages/.
     * Jeśli plik nie istnieje, wyświetla prosty komunikat domyślny.
     * Loguje błędy >= 500.
     *
     * @param int $kod_bledu Kod błędu HTTP (np. 403, 404, 500).
     * @param string $wiadomosc Opcjonalna wiadomość do zalogowania (szczególnie dla błędów 500).
     * Nie jest domyślnie wyświetlana użytkownikowi.
     * @return void Funkcja kończy działanie skryptu (exit).
     */
    function tn_wyswietl_strone_bledu(int $kod_bledu, string $wiadomosc = ''): void
    {
        // Upewnij się, że stałe ścieżek są zdefiniowane
        if (!defined('TN_KORZEN_APLIKACJI')) {
            // Próba automatycznego ustalenia ścieżki, jeśli nie zdefiniowano w config
            define('TN_KORZEN_APLIKACJI', dirname(__DIR__, 2));
             error_log('OSTRZEŻENIE: Stała TN_KORZEN_APLIKACJI nie była zdefiniowana w tn_error_helpers.php. Ustawiono na: ' . TN_KORZEN_APLIKACJI);
        }
         if (!defined('TN_SCIEZKA_TEMPLATEK')) {
            define('TN_SCIEZKA_TEMPLATEK', TN_KORZEN_APLIKACJI . '/templates/');
            error_log('OSTRZEŻENIE: Stała TN_SCIEZKA_TEMPLATEK nie była zdefiniowana. Ustawiono na: ' . TN_SCIEZKA_TEMPLATEK);
        }

        // Podstawowa walidacja kodu błędu
        if ($kod_bledu < 400 || $kod_bledu > 599) {
            error_log("Nieprawidłowy kod błędu przekazany do tn_wyswietl_strone_bledu: " . $kod_bledu . ". Użyto domyślnego 500.");
            $kod_bledu = 500;
        }

        // Ustaw kod odpowiedzi HTTP - rób to tylko jeśli nagłówki nie zostały jeszcze wysłane
        if (!headers_sent()) {
            http_response_code($kod_bledu);
        } else {
            error_log("OSTRZEŻENIE: Nagłówki zostały już wysłane przed wywołaniem tn_wyswietl_strone_bledu dla kodu " . $kod_bledu);
        }

        // Zdefiniuj ścieżkę do katalogu ze stronami błędów
        $sciezka_bledow = TN_SCIEZKA_TEMPLATEK . 'error_pages/';
        $plik_bledu = $sciezka_bledow . 'tn-' . $kod_bledu . '.html'; // Używamy nazw plików z prefixem tn-

        // Zaloguj błąd, jeśli jest to błąd serwera lub inny krytyczny
        if ($kod_bledu >= 500) {
             // Użyj funkcji error_log do zapisania w logu serwera/PHP
             $komunikat_logu = "Błąd aplikacji (Kod: {$kod_bledu})";
             if (!empty($wiadomosc)) {
                 $komunikat_logu .= " - Wiadomość: " . $wiadomosc;
             }
             // Dodaj ślad stosu (stack trace), jeśli dostępny (przydatne przy wyjątkach)
             // Można to zintegrować z set_exception_handler
             // $komunikat_logu .= "\nTrace: " . (new \Exception())->getTraceAsString();
             error_log($komunikat_logu);
        } elseif (!empty($wiadomosc)) {
            // Loguj inne błędy z wiadomością, jeśli jest podana (np. specyficzny powód 403/404)
             error_log("Info aplikacji (Kod: {$kod_bledu}): {$wiadomosc}");
        }

        // Spróbuj wyświetlić niestandardową stronę błędu
        if (file_exists($plik_bledu) && is_readable($plik_bledu)) {
            // Wyczyszczenie ewentualnych wcześniejszych buforów wyjściowych, aby uniknąć mieszania treści
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            // Wyświetl zawartość pliku HTML
            // Użycie include zamiast readfile pozwala na potencjalne użycie PHP w plikach błędów w przyszłości
            include $plik_bledu;
        } else {
            // Jeśli plik nie istnieje, wyświetl prosty komunikat awaryjny
            // Zapewnia to, że użytkownik zawsze coś zobaczy
            if (!headers_sent()) { // Ponowne sprawdzenie, bo czyściliśmy bufory
                 header('Content-Type: text/html; charset=UTF-8');
            }

            echo "<!DOCTYPE html><html lang=\"pl\"><head><meta charset=\"UTF-8\"><title>Błąd {$kod_bledu}</title>";
            echo "<style>body{font-family:sans-serif;padding:20px;text-align:center;background-color:#f1f1f1;} h1{color:#d9534f;} p{color:#555;}</style></head><body>";
            echo "<h1>Wystąpił Błąd {$kod_bledu}</h1>";
            $domyslna_wiadomosc = match ($kod_bledu) {
                403 => "Nie masz uprawnień do dostępu do tego zasobu.",
                404 => "Nie znaleziono żądanego zasobu.",
                500 => "Wystąpił wewnętrzny błąd serwera. Przepraszamy za niedogodności.",
                default => "Wystąpił nieoczekiwany błąd.",
            };
            echo "<p>" . htmlspecialchars($domyslna_wiadomosc) . "</p>";
            echo "<p><a href=\"/\">Powrót na stronę główną</a></p>";
            echo "";
            echo "</body></html>";
             error_log("Nie znaleziono lub nie można odczytać pliku strony błędu: {$plik_bledu}. Wyświetlono komunikat domyślny.");
        }

        // Zakończ wykonywanie skryptu po wyświetleniu strony błędu
        exit;
    }
}
?>