<?php
// src/functions/tn_image_helpers.php

/**
 * Ten plik zawiera funkcje pomocnicze do obsługi obrazków produktów i avatarów użytkowników.
 */

/**
 * Generuje ścieżkę URL do obrazka produktu.
 * Obsługuje zewnętrzne URL-e, pliki w katalogu TNuploads oraz zwraca placeholder SVG, jeśli obrazek jest niedostępny.
 *
 * @param string|null $tn_nazwa_obrazka_lub_url Nazwa pliku obrazka w katalogu uploads lub pełny URL.
 * @return string Ścieżka URL do obrazka lub placeholder SVG.
 */
function tn_pobierz_sciezke_obrazka(?string $tn_nazwa_obrazka_lub_url) : string {
    // 1. Sprawdź, czy to pełny URL
    if (filter_var($tn_nazwa_obrazka_lub_url, FILTER_VALIDATE_URL)) {
        return htmlspecialchars($tn_nazwa_obrazka_lub_url, ENT_QUOTES, 'UTF-8');
    }

    // 2. Sprawdź, czy to nazwa pliku w katalogu uploadów
    if (!empty($tn_nazwa_obrazka_lub_url)) {
        $tn_nazwa_pliku = basename($tn_nazwa_obrazka_lub_url); // Dla bezpieczeństwa użyj basename()
        // Upewnij się, że stała TN_SCIEZKA_UPLOAD jest zdefiniowana (powinna być w config.php)
        if (!defined('TN_SCIEZKA_UPLOAD')) {
             error_log("Stała TN_SCIEZKA_UPLOAD nie jest zdefiniowana w tn_image_helpers.php!");
             // Zwróć placeholder w razie błędu konfiguracji
             goto return_placeholder;
        }
        $tn_sciezka_fizyczna = TN_SCIEZKA_UPLOAD . $tn_nazwa_pliku;

        if (file_exists($tn_sciezka_fizyczna) && is_file($tn_sciezka_fizyczna) && is_readable($tn_sciezka_fizyczna)) {
            // Zakładamy, że katalog TNuploads jest dostępny publicznie pod URL /TNuploads/
            // (np. przez dowiązanie symboliczne, alias Apache/Nginx lub bezpośrednio, jeśli app/ jest web rootem)
             $tn_base_url = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); if ($tn_base_url === '.') $tn_base_url = '';
             // Zawsze dodawaj / na początku ścieżki względnej
             $tn_image_url_path = '/TNuploads/' . rawurlencode($tn_nazwa_pliku);
             // Dodaj cache buster
             $cache_buster = @filemtime($tn_sciezka_fizyczna) ?: time();
             return $tn_base_url . $tn_image_url_path . '?v=' . $cache_buster;
        }
    }

    // 3. Zwróć domyślny placeholder SVG, jeśli obrazek nie został znaleziony
    return_placeholder: // Etykieta dla goto
    return 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22100%22%20height%3D%22100%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20100%20100%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_tn%20text%20%7B%20fill%3A%23aaa%3Bfont-weight%3Abold%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A14pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_tn%22%3E%3Crect%20width%3D%22100%22%20height%3D%22100%22%20fill%3D%22%23eee%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2222%22%20y%3D%2255%22%3ENo%20IMG%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
}


/**
 * Obsługuje przesyłanie pliku avatara użytkownika.
 * Waliduje plik (rozmiar, typ), generuje unikalną nazwę opartą na ID użytkownika i timestampie,
 * przenosi plik do katalogu avatarów (definiowanego przez TN_SCIEZKA_AVATARS)
 * i usuwa stary avatar, jeśli istniał.
 *
 * @param array|null $tn_file_info Tablica $_FILES['nazwa_pola_pliku'] lub null, jeśli nie przesłano pliku.
 * @param int $tn_user_id ID użytkownika (do nazwy pliku).
 * @param string|null $tn_stary_avatar Nazwa pliku starego avatara do usunięcia (z bazy danych).
 * @param string &$tn_error_message Referencja do zmiennej, gdzie zostanie zapisany ewentualny komunikat błędu.
 * @return string|false Nazwa nowego pliku avatara (np. 'user_1_1678886400.jpg') w przypadku sukcesu,
 * false w przypadku błędu lub jeśli nie przesłano nowego pliku.
 */
function tn_handle_avatar_upload(?array $tn_file_info, int $tn_user_id, ?string $tn_stary_avatar, string &$tn_error_message): string|false {
    // Sprawdź, czy stałe ścieżek są zdefiniowane
     if (!defined('TN_SCIEZKA_AVATARS')) {
        $tn_error_message = 'Błąd konfiguracji: Ścieżka do avatarów nie jest zdefiniowana.';
        error_log($tn_error_message);
        return false;
    }

    // Sprawdź, czy plik został faktycznie przesłany
    if (empty($tn_file_info) || !isset($tn_file_info['error']) || $tn_file_info['error'] === UPLOAD_ERR_NO_FILE) {
        // Brak przesłanego pliku - to nie jest błąd, po prostu nie aktualizujemy avatara.
        return false;
    }

     // Sprawdź inne błędy uploadu
    if (is_array($tn_file_info['error'])) {
        $tn_error_message = 'Nieprawidłowe parametry przesyłania pliku (tablica błędów).';
        error_log($tn_error_message . print_r($tn_file_info['error'], true));
        return false;
    }
    switch ($tn_file_info['error']) {
        case UPLOAD_ERR_OK: break; // OK, kontynuuj
        case UPLOAD_ERR_INI_SIZE: // Przekroczono upload_max_filesize w php.ini
        case UPLOAD_ERR_FORM_SIZE: // Przekroczono MAX_FILE_SIZE w formularzu HTML
             $tn_error_message = 'Przesłany plik jest za duży.'; return false;
        case UPLOAD_ERR_PARTIAL: $tn_error_message = 'Plik został przesłany tylko częściowo.'; return false;
        case UPLOAD_ERR_CANT_WRITE: $tn_error_message = 'Błąd zapisu pliku na dysku serwera.'; return false;
        case UPLOAD_ERR_EXTENSION: $tn_error_message = 'Przesyłanie pliku zatrzymane przez rozszerzenie PHP.'; return false;
        default: $tn_error_message = 'Wystąpił nieznany błąd podczas przesyłania pliku (kod: '.$tn_file_info['error'].').'; return false;
    }

    // Sprawdź rozmiar pliku (np. max 1MB dla avatarów)
    $max_size = 1 * 1024 * 1024; // 1 MB
    if ($tn_file_info['size'] > $max_size) {
        $tn_error_message = 'Plik avatara jest za duży (maksymalnie 1MB).';
        return false;
    }
    if ($tn_file_info['size'] <= 0) {
        $tn_error_message = 'Przesłany plik avatara jest pusty.';
        return false;
    }


    // Bezpieczne sprawdzenie typu MIME i rozszerzenia
    try {
         $finfo = new finfo(FILEINFO_MIME_TYPE);
         $mime_type = $finfo->file($tn_file_info['tmp_name']);
         if($mime_type === false) throw new RuntimeException('Nie można odczytać typu MIME pliku.');
    } catch (Exception $e) {
         error_log("Błąd finfo: " . $e->getMessage());
         $tn_error_message = 'Nie można zweryfikować typu przesłanego pliku.';
         return false;
    }

    $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    // Użyj basename() i pathinfo() dla bezpieczeństwa
    $original_filename = basename($tn_file_info['name']);
    $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));

    if (!in_array($mime_type, $allowed_mime_types) || !in_array($file_extension, $allowed_extensions)) {
        $tn_error_message = 'Niedozwolony typ pliku avatara (JPG, PNG, GIF, WEBP). Wykryto: '.$mime_type;
        return false;
    }

    // Generuj nową, unikalną i bezpieczną nazwę pliku
    $new_filename = 'user_' . $tn_user_id . '_' . time() . '.' . $file_extension;
    $destination_path = rtrim(TN_SCIEZKA_AVATARS, '/') . '/' . $new_filename; // Użyj stałej z config.php

    // Przenieś przesłany plik z katalogu tymczasowego do docelowego
    if (!move_uploaded_file($tn_file_info['tmp_name'], $destination_path)) {
        $tn_error_message = 'Nie udało się zapisać przesłanego pliku avatara na serwerze.';
        error_log("Błąd move_uploaded_file dla avatara: z {$tn_file_info['tmp_name']} do {$destination_path}");
        return false;
    }

    // Ustaw odpowiednie uprawnienia dla nowego pliku (np. 0644)
    @chmod($destination_path, 0644);

    // Usuń stary avatar, jeśli istniał
    if (!empty($tn_stary_avatar)) {
        $old_avatar_name = basename($tn_stary_avatar); // Bezpieczeństwo
        if (!empty($old_avatar_name)) { // Dodatkowe sprawdzenie
             $old_avatar_path = rtrim(TN_SCIEZKA_AVATARS, '/') . '/' . $old_avatar_name;
             if (file_exists($old_avatar_path) && is_file($old_avatar_path) && $old_avatar_path !== $destination_path) { // Nie usuwaj nowego pliku
                 if (!@unlink($old_avatar_path)) {
                     error_log("Nie udało się usunąć starego avatara: {$old_avatar_path}");
                 }
             }
        }
    }

    return $new_filename; // Zwróć nazwę nowego pliku
}


/**
 * Generuje ścieżkę URL do avatara użytkownika lub domyślnego placeholdera SVG.
 *
 * @param string|null $tn_nazwa_pliku_avatara Nazwa pliku avatara z bazy danych (users.json).
 * @return string Ścieżka URL do avatara lub SVG placeholder.
 */
function tn_get_avatar_path(?string $tn_nazwa_pliku_avatara): string {
    // Upewnij się, że stałe są zdefiniowane
     if (!defined('TN_SCIEZKA_AVATARS') || !defined('TN_URL_AVATARS')) {
        error_log("Błąd konfiguracji: Brak stałych TN_SCIEZKA_AVATARS lub TN_URL_AVATARS w tn_get_avatar_path.");
        goto return_avatar_placeholder; // Użyj goto, aby uniknąć duplikacji kodu placeholdera
    }

    if (!empty($tn_nazwa_pliku_avatara)) {
        $tn_nazwa_pliku = basename($tn_nazwa_pliku_avatara); // Bezpieczeństwo
        $tn_sciezka_fizyczna = rtrim(TN_SCIEZKA_AVATARS, '/') . '/' . $tn_nazwa_pliku;
        // Użyj stałej TN_URL_AVATARS (ścieżka URL)
        $tn_sciezka_url_base = rtrim(TN_URL_AVATARS, '/');
        $tn_sciezka_url = $tn_sciezka_url_base . '/' . rawurlencode($tn_nazwa_pliku);

        if (file_exists($tn_sciezka_fizyczna) && is_file($tn_sciezka_fizyczna) && is_readable($tn_sciezka_fizyczna)) {
             // Dodaj timestamp jako cache buster
             $cache_buster = @filemtime($tn_sciezka_fizyczna) ?: time();
             // Ustal bazowy URL aplikacji dla linków względnych
             $tn_base_url = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); if ($tn_base_url === '.') $tn_base_url = '';
             // Upewnij się, że ścieżka zaczyna się od /
             if (!str_starts_with($tn_sciezka_url, '/')) {
                  $tn_sciezka_url = '/' . $tn_sciezka_url;
             }
             return $tn_base_url . $tn_sciezka_url . '?v=' . $cache_buster;
        } else {
             // Opcjonalnie zaloguj, że plik avatara podany w bazie nie istnieje
             // error_log("Plik avatara nie istnieje lub nie jest plikiem: " . $tn_sciezka_fizyczna);
        }
    }

    // Etykieta dla goto - zwraca domyślny placeholder SVG
    return_avatar_placeholder:
    return 'data:image/svg+xml;charset=UTF-8,%3Csvg xmlns="http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg" width="40" height="40" fill="%236c757d" class="bi bi-person-circle" viewBox="0 0 16 16"%3E%3Cpath d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"%2F%3E%3Cpath fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"%2F%3E%3C%2Fsvg%3E';
}

?>