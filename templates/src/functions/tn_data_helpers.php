<?php
// src/functions/tn_data_helpers.php

/**
 * Ten plik zawiera funkcje pomocnicze do ładowania i zapisywania
 * danych aplikacji z/do plików JSON.
 * Wersja: 1.2 (Poprawka w tn_pobierz_nastepne_id_zwrotu)
 */

declare(strict_types=1); // Zalecane dla lepszej kontroli typów

// --- Funkcje ogólne dla JSON ---

/**
 * Bezpiecznie ładuje dane z pliku JSON.
 * Obsługuje brak pliku, błędy odczytu, pusty plik i błędy dekodowania JSON.
 *
 * @param string $tn_plik Ścieżka do pliku JSON.
 * @param mixed $tn_domyslne Wartość zwracana w przypadku błędu lub braku pliku (domyślnie pusta tablica).
 * @return mixed Zdekodowane dane JSON lub wartość domyślna.
 */
function tn_laduj_json_dane(string $tn_plik, $tn_domyslne = []) {
    if (!file_exists($tn_plik)) {
        // Jeśli plik nie istnieje, zwróć domyślne (nie loguj błędu w tym przypadku)
        return $tn_domyslne;
    }
    if (!is_readable($tn_plik)) {
        error_log("Błąd: Brak uprawnień do odczytu pliku JSON: " . $tn_plik);
        return $tn_domyslne;
    }

    // Użyj @file_get_contents, aby stłumić warningi, jeśli plik jest tymczasowo niedostępny
    $tn_dane = @file_get_contents($tn_plik);
    if ($tn_dane === false) {
        error_log("Błąd odczytu zawartości pliku JSON: " . $tn_plik);
        return $tn_domyslne;
    }
    $tn_dane_trim = trim($tn_dane);
    if (empty($tn_dane_trim)) {
        // Pusty plik traktujemy jako pustą tablicę (lub domyślne)
        return $tn_domyslne;
    }

    // Zawsze próbuj dekodować jako tablicę asocjacyjną (true)
    $tn_zdekodowane_dane = json_decode($tn_dane_trim, true);
    $tn_json_error = json_last_error();

    if ($tn_json_error !== JSON_ERROR_NONE) {
        error_log("Błąd dekodowania JSON z pliku: " . $tn_plik . " - Błąd (" . $tn_json_error . "): " . json_last_error_msg());
        return $tn_domyslne;
    }

    // Sprawdź typ wyniku, jeśli domyślne jest tablicą
    if (is_array($tn_domyslne) && !is_array($tn_zdekodowane_dane)) {
         error_log("Ostrzeżenie: Oczekiwano tablicy z pliku JSON, ale otrzymano inny typ: " . $tn_plik);
         return $tn_domyslne;
    }
    return $tn_zdekodowane_dane;
}

/**
 * Bezpiecznie zapisuje dane do pliku JSON. Używa zapisu atomowego.
 * Tworzy katalog docelowy, jeśli nie istnieje.
 *
 * @param string $tn_plik Ścieżka do pliku JSON.
 * @param mixed $tn_dane Dane do zakodowania i zapisania.
 * @return bool True w przypadku sukcesu, False w przypadku błędu.
 */
function tn_zapisz_json_dane(string $tn_plik, $tn_dane) : bool {
    $tn_katalog = dirname($tn_plik);

    // Sprawdź/utwórz katalog
    if (!is_dir($tn_katalog)) {
        // Użyj trybu 0777 dla większej kompatybilności w różnych środowiskach (szczególnie lokalnych)
        // W produkcji rozważ bardziej restrykcyjne uprawnienia (np. 0755)
        if (!@mkdir($tn_katalog, 0777, true) && !is_dir($tn_katalog)) {
            error_log("Błąd krytyczny: Nie można utworzyć katalogu do zapisu JSON: " . $tn_katalog);
            return false;
        }
         // Dodaj plik .htaccess przy tworzeniu katalogu danych, aby zabezpieczyć bezpośredni dostęp
         if (defined('TN_SCIEZKA_DANE') && $tn_katalog === TN_SCIEZKA_DANE) {
             $htaccess_content = "Options -Indexes\nDeny from all";
             @file_put_contents($tn_katalog . '/.htaccess', $htaccess_content);
         } elseif (defined('TN_SCIEZKA_UPLOAD') && $tn_katalog === TN_SCIEZKA_UPLOAD) {
             // Dla katalogu upload, pozwól na dostęp do plików, ale zabroń listowania i wykonywania skryptów PHP
             $htaccess_content = "Options -Indexes\n<FilesMatch \"\\.(php|phtml|php[3-7]|phps)\">\n Deny from all\n</FilesMatch>";
             @file_put_contents($tn_katalog . '/.htaccess', $htaccess_content);
         } elseif (defined('TN_SCIEZKA_AVATARS') && $tn_katalog === TN_SCIEZKA_AVATARS) {
              // Dla avatarów podobnie jak dla uploadów
              $htaccess_content = "Options -Indexes\n<FilesMatch \"\\.(php|phtml|php[3-7]|phps)\">\n Deny from all\n</FilesMatch>";
              @file_put_contents($tn_katalog . '/.htaccess', $htaccess_content);
         }
    }

    // Sprawdź zapisywalność katalogu
    if (!is_writable($tn_katalog)) {
         error_log("Błąd krytyczny: Katalog docelowy nie jest zapisywalny: " . $tn_katalog);
         return false;
    }

    // Kodowanie danych do formatu JSON
    $tn_opcje_json = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;
    try {
        $tn_json_dane = json_encode($tn_dane, $tn_opcje_json);
        // Sprawdź błąd kodowania
        if ($tn_json_dane === false && json_last_error() !== JSON_ERROR_NONE) {
             throw new JsonException(json_last_error_msg(), json_last_error());
        }
    } catch (JsonException $e) {
        error_log("Błąd kodowania danych do JSON dla pliku: " . $tn_plik . " - Błąd (" . $e->getCode() . "): " . $e->getMessage());
        return false;
    }

    // Zapis atomowy (przez plik tymczasowy)
    $tn_plik_tmp = $tn_katalog . '/tmp_' . basename($tn_plik) . '.' . bin2hex(random_bytes(6)) . '.json';
    // Użyj @, aby stłumić ewentualne warningi, jeśli zapis się nie powiedzie z powodu uprawnień itp.
    if (@file_put_contents($tn_plik_tmp, $tn_json_dane, LOCK_EX) === false) {
        error_log("Błąd zapisu do pliku tymczasowego JSON: " . $tn_plik_tmp);
        @unlink($tn_plik_tmp); // Usuń nieudany plik tymczasowy
        return false;
    }

    // Zmiana nazwy pliku tymczasowego na docelowy - atomowa operacja w większości systemów
    if (!@rename($tn_plik_tmp, $tn_plik)) {
         error_log("Błąd zmiany nazwy pliku tymczasowego JSON ('{$tn_plik_tmp}') na docelowy ('{$tn_plik}'). Próba zapisu bezpośredniego (fallback).");
         @unlink($tn_plik_tmp); // Usuń plik tymczasowy, bo rename się nie powiodło

         // Fallback - próba zapisu bezpośredniego (mniej bezpieczne w przypadku błędów w trakcie)
         if (@file_put_contents($tn_plik, $tn_json_dane, LOCK_EX) === false) {
             error_log("Błąd bezpośredniego zapisu do pliku JSON (fallback): " . $tn_plik);
             return false; // Zapis ostatecznie nieudany
         }
          error_log("Ostrzeżenie: Użyto zapisu bezpośredniego (fallback) dla pliku: " . $tn_plik);
    }

    // Ustaw uprawnienia (opcjonalne, ale zalecane dla bezpieczeństwa i spójności)
    // Użyj 0666 dla większej kompatybilności w środowiskach developerskich, 0644 w produkcji.
    @chmod($tn_plik, 0666);

    return true; // Zapis zakończony sukcesem
}

// --- Funkcje specyficzne dla danych ---

/**
 * Ładuje ustawienia aplikacji, łącząc je z domyślnymi.
 * Tworzy plik z domyślnymi ustawieniami, jeśli nie istnieje.
 *
 * @param string $tn_plik_ustawien Ścieżka do pliku settings.json.
 * @param array $tn_domyslne_ustawienia Domyślna struktura ustawień.
 * @return array Połączone ustawienia aplikacji.
 */
function tn_laduj_ustawienia(string $tn_plik_ustawien, array $tn_domyslne_ustawienia) : array {
    $tn_wczytane_ustawienia = tn_laduj_json_dane($tn_plik_ustawien, null); // Użyj null jako sygnału błędu/braku pliku

    // Jeśli plik nie istnieje lub wystąpił błąd odczytu/dekodowania
    if ($tn_wczytane_ustawienia === null || !is_array($tn_wczytane_ustawienia)) {
        // Jeśli plik fizycznie nie istnieje, spróbuj go utworzyć z domyślnymi
        if (!file_exists($tn_plik_ustawien)) {
             if (tn_zapisz_json_dane($tn_plik_ustawien, $tn_domyslne_ustawienia)) {
                 error_log("Utworzono domyślny plik ustawień: " . $tn_plik_ustawien);
             } else {
                 // To jest poważny błąd - aplikacja może nie działać poprawnie
                 error_log("BŁĄD KRYTYCZNY: Nie można utworzyć domyślnego pliku ustawień: " . $tn_plik_ustawien . ". Aplikacja może nie działać poprawnie.");
                 // Można tu rzucić wyjątek lub die() w zależności od wymagań
             }
        }
        // Zwróć domyślne ustawienia w przypadku błędu odczytu istniejącego pliku lub po utworzeniu nowego
        return $tn_domyslne_ustawienia;
    }

    // Połącz wczytane ustawienia z domyślnymi, aby mieć pewność, że wszystkie klucze istnieją
    // array_replace_recursive zastępuje wartości w pierwszej tablicy wartościami z drugiej,
    // wchodząc rekurencyjnie w głąb zagnieżdżonych tablic.
    return array_replace_recursive($tn_domyslne_ustawienia, $tn_wczytane_ustawienia);
}

/**
 * Zapisuje ustawienia aplikacji do pliku JSON.
 *
 * @param string $tn_plik_ustawien Ścieżka do pliku settings.json.
 * @param array $tn_ustawienia Tablica ustawień do zapisania.
 * @return bool Wynik operacji zapisu.
 */
function tn_zapisz_ustawienia(string $tn_plik_ustawien, array $tn_ustawienia) : bool {
    return tn_zapisz_json_dane($tn_plik_ustawien, $tn_ustawienia);
}

/**
 * Ładuje dane produktów z pliku JSON.
 *
 * @param string $tn_plik Ścieżka do pliku products.json.
 * @return array Tablica produktów.
 */
function tn_laduj_produkty(string $tn_plik) : array {
    return tn_laduj_json_dane($tn_plik, []);
}

/**
 * Zapisuje dane produktów do pliku JSON.
 * Sortuje produkty wg ID przed zapisem.
 *
 * @param string $tn_plik Ścieżka do pliku products.json.
 * @param array $tn_dane Tablica produktów do zapisania.
 * @return bool Wynik operacji zapisu.
 */
function tn_zapisz_produkty(string $tn_plik, array $tn_dane) : bool {
    // Sortowanie wg ID rosnąco
    usort($tn_dane, fn($a, $b) => ($a['id'] ?? 0) <=> ($b['id'] ?? 0));
    // Zapisz jako tablicę obiektów (JSON array)
    return tn_zapisz_json_dane($tn_plik, array_values($tn_dane));
}

/**
 * Ładuje dane zamówień z pliku JSON.
 *
 * @param string $tn_plik Ścieżka do pliku orders.json.
 * @return array Tablica zamówień.
 */
function tn_laduj_zamowienia(string $tn_plik) : array {
    return tn_laduj_json_dane($tn_plik, []);
}

/**
 * Zapisuje dane zamówień do pliku JSON.
 * Sortuje zamówienia wg daty malejąco (lub ID, jeśli brak daty) przed zapisem.
 *
 * @param string $tn_plik Ścieżka do pliku orders.json.
 * @param array $tn_dane Tablica zamówień do zapisania.
 * @return bool Wynik operacji zapisu.
 */
function tn_zapisz_zamowienia(string $tn_plik, array $tn_dane) : bool {
    // Sortowanie wg daty malejąco, a następnie wg ID malejąco dla tej samej daty
    usort($tn_dane, function($a, $b) {
        $date_a = $a['order_date'] ?? null;
        $date_b = $b['order_date'] ?? null;
        $id_a = $a['id'] ?? 0;
        $id_b = $b['id'] ?? 0;

        if ($date_a == $date_b) {
            return $id_b <=> $id_a; // ID malejąco
        }
        // Jeśli jedna z dat jest null, traktuj ją jako starszą
        if ($date_a === null) return 1;
        if ($date_b === null) return -1;
        // Sortuj po dacie malejąco
        return $date_b <=> $date_a;
    });
    return tn_zapisz_json_dane($tn_plik, array_values($tn_dane));
}

/**
 * Ładuje stan magazynu (lokalizacje) z pliku JSON.
 *
 * @param string $tn_plik Ścieżka do pliku warehouse.json.
 * @return array Tablica lokalizacji magazynowych.
 */
function tn_laduj_magazyn(string $tn_plik) : array {
    return tn_laduj_json_dane($tn_plik, []);
}

/**
 * Zapisuje stan magazynu do pliku JSON.
 * Sortuje lokalizacje wg ID (naturalnie) przed zapisem.
 *
 * @param string $tn_plik Ścieżka do pliku warehouse.json.
 * @param array $tn_dane Tablica lokalizacji do zapisania.
 * @return bool Wynik operacji zapisu.
 */
function tn_zapisz_magazyn(string $tn_plik, array $tn_dane) : bool {
    // Sortowanie naturalne wg ID lokalizacji (np. R01-S01-P10 > R01-S01-P02)
    usort($tn_dane, fn($a, $b) => strnatcmp($a['id'] ?? '', $b['id'] ?? ''));
    return tn_zapisz_json_dane($tn_plik, array_values($tn_dane));
}

/**
 * Ładuje definicje regałów z pliku JSON.
 *
 * @param string $tn_plik Ścieżka do pliku regaly.json.
 * @return array Tablica definicji regałów.
 */
function tn_laduj_regaly(string $tn_plik) : array {
    return tn_laduj_json_dane($tn_plik, []);
}

/**
 * Zapisuje definicje regałów do pliku JSON.
 * Sortuje regały wg ID (naturalnie) przed zapisem.
 *
 * @param string $tn_plik Ścieżka do pliku regaly.json.
 * @param array $tn_dane Tablica definicji regałów do zapisania.
 * @return bool Wynik operacji zapisu.
 */
function tn_zapisz_regaly(string $tn_plik, array $tn_dane) : bool {
    // Sortowanie naturalne wg ID regału
    usort($tn_dane, fn($a, $b) => strnatcmp($a['tn_id_regalu'] ?? '', $b['tn_id_regalu'] ?? ''));
    return tn_zapisz_json_dane($tn_plik, array_values($tn_dane));
}

// --- Funkcje dla użytkowników ---

/**
 * Ładuje dane użytkowników z pliku JSON.
 *
 * @param string $tn_plik Ścieżka do pliku users.json.
 * @return array Tablica użytkowników.
 */
function tn_laduj_uzytkownikow(string $tn_plik) : array {
    return tn_laduj_json_dane($tn_plik, []);
}

/**
 * Znajduje użytkownika w tablicy po nazwie (ignoruje wielkość liter).
 *
 * @param string $tn_username Szukana nazwa użytkownika.
 * @param array $tn_uzytkownicy Tablica użytkowników.
 * @return array|null Dane znalezionego użytkownika lub null.
 */
function tn_znajdz_uzytkownika_po_nazwie(string $tn_username, array $tn_uzytkownicy): ?array {
    foreach ($tn_uzytkownicy as $user) {
        if (isset($user['username']) && strcasecmp($user['username'], $tn_username) === 0) {
            return $user;
        }
    }
    return null;
}

/**
 * Zapisuje dane użytkowników do pliku JSON.
 * Sortuje użytkowników wg ID przed zapisem.
 *
 * @param string $tn_plik Ścieżka do pliku users.json.
 * @param array $tn_dane Tablica użytkowników do zapisania.
 * @return bool Wynik operacji zapisu.
 */
function tn_zapisz_uzytkownikow(string $tn_plik, array $tn_dane) : bool {
    // Sortowanie wg ID rosnąco
    usort($tn_dane, fn($a, $b) => ($a['id'] ?? 0) <=> ($b['id'] ?? 0));
    return tn_zapisz_json_dane($tn_plik, array_values($tn_dane));
}

// --- Funkcje dla zwrotów/reklamacji ---

/**
 * Ładuje dane zwrotów/reklamacji z pliku JSON.
 *
 * @param string $tn_plik Ścieżka do pliku returns.json.
 * @return array Tablica zgłoszeń.
 */
function tn_laduj_zwroty(string $tn_plik) : array {
    return tn_laduj_json_dane($tn_plik, []);
}

/**
 * Zapisuje dane zwrotów/reklamacji do pliku JSON.
 * Sortuje zgłoszenia wg daty utworzenia malejąco (najnowsze pierwsze),
 * a następnie wg ID malejąco dla tej samej daty.
 *
 * @param string $tn_plik Ścieżka do pliku returns.json.
 * @param array $tn_dane Tablica zgłoszeń do zapisania.
 * @return bool Wynik operacji zapisu.
 */
function tn_zapisz_zwroty(string $tn_plik, array $tn_dane) : bool {
    usort($tn_dane, function($a, $b) {
        try {
            // Użyj @, aby stłumić błędy przy nieprawidłowych datach
            $date_a = isset($a['date_created']) ? @(new DateTime($a['date_created'])) : null;
            $date_b = isset($b['date_created']) ? @(new DateTime($b['date_created'])) : null;
        } catch (Exception $e) {
             error_log("Błąd parsowania daty podczas sortowania zwrotów: " . $e->getMessage());
             // W razie błędu traktuj daty jako równe, sortuj po ID
             return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
        }
        // Porównanie dat
        if ($date_a == $date_b) {
            // Jeśli daty są takie same (lub obie błędne/null), sortuj po ID malejąco
            return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
        }
        // Jeśli jedna data jest null, traktuj ją jako starszą (idzie na koniec przy sortowaniu DESC)
        if ($date_a === null) return 1;
        if ($date_b === null) return -1;
        // Sortuj po dacie malejąco (najnowsze pierwsze)
        return $date_b <=> $date_a;
    });
    return tn_zapisz_json_dane($tn_plik, array_values($tn_dane));
}

/**
 * Pobiera następne dostępne ID dla nowego zgłoszenia zwrotu/reklamacji.
 *
 * @param array $tn_zwroty Tablica istniejących zgłoszeń.
 * @return int Następne ID.
 */
function tn_pobierz_nastepne_id_zwrotu(array $tn_zwroty): int {
    if (empty($tn_zwroty)) {
        return 1; // Jeśli brak zwrotów, zacznij od ID 1
    }
    // Pobierz wszystkie istniejące ID
    $ids = array_column($tn_zwroty, 'id');
    // Odfiltruj tylko numeryczne ID (na wypadek gdyby były jakieś błędne dane)
    $numeric_ids = array_filter($ids, 'is_numeric');

    // Jeśli nie ma żadnych numerycznych ID (np. wszystkie są puste lub błędne), zacznij od 1
    if (empty($numeric_ids)) {
        return 1;
    }

    // Znajdź maksymalne istniejące numeryczne ID i zwróć następne
    return max($numeric_ids) + 1;
}

// --- Funkcje dla kurierów (Poprawione) ---

/**
 * Ładuje dane kurierów z pliku JSON jako tablicę asocjacyjną.
 * Kluczem tablicy będzie tekstowe ID kuriera.
 *
 * @param string $tn_plik Ścieżka do pliku couriers.json.
 * @return array Tablica kurierów (klucz => dane) lub pusta tablica w przypadku błędu.
 */
function tn_laduj_kurierow(string $tn_plik) : array {
    // Używamy już poprawionej funkcji tn_laduj_json_dane
    $dane_indeksowane = tn_laduj_json_dane($tn_plik, []);
    $dane_asocjacyjne = [];
    if (is_array($dane_indeksowane)) {
        foreach ($dane_indeksowane as $kurier) {
            // Sprawdź, czy istnieje tekstowe 'id' i czy nie jest puste
            if (isset($kurier['id']) && is_string($kurier['id']) && $kurier['id'] !== '') {
                // Użyj tekstowego 'id' jako klucza tablicy asocjacyjnej
                $dane_asocjacyjne[$kurier['id']] = $kurier;
            } else {
                 error_log("Ostrzeżenie: Pominięto kuriera bez poprawnego tekstowego ID w pliku: " . $tn_plik);
            }
        }
    }
    return $dane_asocjacyjne;
}

/**
 * Zapisuje dane kurierów do pliku JSON.
 * Zapisuje jako tablicę obiektów (JSON array), zachowując pole 'id' wewnątrz.
 *
 * @param string $tn_plik Ścieżka do pliku couriers.json.
 * @param array $tn_dane Tablica asocjacyjna kurierów (klucz => dane) do zapisania.
 * @return bool True w przypadku sukcesu, False w przypadku błędu.
 */
function tn_zapisz_kurierow(string $tn_plik, array $tn_dane) : bool {
    // Sortowanie wg nazwy (kluczem jest ID tekstowe, sortujemy wartości)
    uasort($tn_dane, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));

    // Zapisz wartości tablicy asocjacyjnej jako tablicę indeksowaną numerycznie
    // Pole 'id' jest już częścią danych wewnątrz każdego elementu $tn_dane.
    return tn_zapisz_json_dane($tn_plik, array_values($tn_dane));
}

?>