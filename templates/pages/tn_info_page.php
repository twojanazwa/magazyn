<?php
/**
 * templates/pages/tn_info_page.php
 * Szczegółowa strona informacyjna i diagnostyczna aplikacji TN iMAG.
 * Wersja: 2.9 (Dodano pola dla roli użytkownika i innych danych)
 *
 * Wyświetla informacje o aplikacji, serwerze, PHP, rozszerzeniach, plikach danych,
 * kluczowych katalogach, stałych konfiguracyjnych i sesji, oraz szczegóły o bieżącym
 * użytkowniku i jego połączeniu.
 *
 * Oczekuje zmiennych z index.php (lub config.php):
 * @var string|null $tn_app_name Nazwa aplikacji (preferowana z config.php).
 */

declare(strict_types=1);

// Upewnij się, że stałe konfiguracyjne są zdefiniowane. Jeśli nie, użyj fallbacków.
// W środowisku produkcyjnym te stałe powinny być ZAWSZE zdefiniowane w tn_config.php!
if (!defined('TN_APP_VERSION')) define('TN_APP_VERSION', '1.7.0'); // Domyślna wersja
if (!defined('TN_KORZEN_APLIKACJI')) define('TN_KORZEN_APLIKACJI', __DIR__ . '/../..'); // Domyślny katalog główny
if (!defined('TN_SCIEZKA_SRC')) define('TN_SCIEZKA_SRC', TN_KORZEN_APLIKACJI . '/src/'); // Domyślna ścieżka SRC
if (!defined('TN_SCIEZKA_DANE')) define('TN_SCIEZKA_DANE', TN_KORZEN_APLIKACJI . '/TNbazaDanych/'); // Domyślna ścieżka DANYCH
if (!defined('TN_SCIEZKA_TEMPLATKI')) define('TN_SCIEZKA_TEMPLATKI', TN_KORZEN_APLIKACJI . '/templates/'); // Domyślna ścieżka TEMPLATKI
if (!defined('TN_SCIEZKA_UPLOAD')) define('TN_SCIEZKA_UPLOAD', TN_KORZEN_APLIKACJI . '/TNuploads/'); // Domyślna ścieżka UPLOAD
// Domyślne ścieżki dla podkatalogów uploadu
if (!defined('TN_SCIEZKA_UPLOAD_IMAGES')) define('TN_SCIEZKA_UPLOAD_IMAGES', TN_SCIEZKA_UPLOAD . '');
if (!defined('TN_SCIEZKA_UPLOAD_AVATARS')) define('TN_SCIEZKA_UPLOAD_AVATARS', TN_SCIEZKA_UPLOAD . '');
if (!defined('TN_SCIEZKA_UPLOAD_LOGO')) define('TN_SCIEZKA_UPLOAD_LOGO', TN_SCIEZKA_UPLOAD . '');
if (!defined('TN_SCIEZKA_UPLOAD_ICONS')) define('TN_SCIEZKA_UPLOAD_ICONS', TN_SCIEZKA_UPLOAD . '');
if (!defined('TN_SCIEZKA_LOGS')) define('TN_SCIEZKA_LOGS', TN_KORZEN_APLIKACJI . '/tn-logi/'); // Domyślna ścieżka LOGS

// Domyślne ścieżki do plików danych JSON
if (!defined('TN_PLIK_USTAWIENIA')) define('TN_PLIK_USTAWIENIA', TN_SCIEZKA_DANE . 'settings.json');
if (!defined('TN_PLIK_UZYTKOWNICY')) define('TN_PLIK_UZYTKOWNICY', TN_SCIEZKA_DANE . 'users.json');
if (!defined('TN_PLIK_PRODUKTY')) define('TN_PLIK_PRODUKTY', TN_SCIEZKA_DANE . 'products.json');
if (!defined('TN_PLIK_ZAMOWIENIA')) define('TN_PLIK_ZAMOWIENIA', TN_SCIEZKA_DANE . 'orders.json');
if (!defined('TN_PLIK_MAGAZYN')) define('TN_PLIK_MAGAZYN', TN_SCIEZKA_DANE . 'warehouse.json'); 
if (!defined('TN_PLIK_REGALY')) define('TN_PLIK_REGALY', TN_SCIEZKA_DANE . 'regaly.json'); 
if (!defined('TN_PLIK_ZWROTY')) define('TN_PLIK_ZWROTY', TN_SCIEZKA_DANE . 'returns.json');
if (!defined('TN_PLIK_KURIERZY')) define('TN_PLIK_KURIERZY', TN_SCIEZKA_DANE . 'couriers.json');
if (!defined('TN_PLIK_DASHBOARD_NOTES')) define('TN_PLIK_DASHBOARD_NOTES', TN_SCIEZKA_DANE . 'dashboard_notes.json'); 

// Helper do generowania URL (uproszczony fallback, jeśli funkcja nie jest globalnie dostępna)
if (!function_exists('tn_generuj_url')) {
    function tn_generuj_url(string $id, array $p = []): string {
        $url = '?page=' . urlencode($id);
        if (!empty($p)) {
            $url .= '&' . http_build_query($p);
        }
        return $url;
    }
}
// Helper do formatowania rozmiaru plików
if (!function_exists('tn_format_bytes')) {
    function tn_format_bytes(int $bytes, int $precision = 2): string {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);
        $pow = floor($base);
        // Sprawdzenie, czy $pow jest w zakresie tablicy $units
        $pow = max(0, min($pow, count($units) - 1));
        return round(pow(1024, $base - $pow), $precision) . ' ' . $units[$pow];
    }
}
// Helper do sprawdzania uprawnień plików/katalogów
if (!function_exists('tn_check_permission')) {
    function tn_check_permission(string $path, bool $check_write = false): string {
        if (!file_exists($path)) return '<span class="badge text-bg-danger" title="Nie istnieje">Brak</span>';
        $readable = is_readable($path);
        $writable = $check_write ? is_writable($path) : null;

        $read_badge = $readable ? '<span class="badge text-bg-success" title="Odczyt">O</span>' : '<span class="badge text-bg-danger" title="Brak odczytu">BO</span>';
        $write_badge = '';
        if ($check_write) {
            $write_badge = $writable ? '<span class="badge text-bg-success" title="Zapis">Z</span>' : '<span class="badge text-bg-danger" title="Brak zapisu">BZ</span>';
        }
        return $read_badge . ($check_write ? ' ' . $write_badge : '');
    }
}

// Pobieranie informacji systemowych
$php_version = PHP_VERSION;
$server_os = PHP_OS;
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Nieznane';
$php_sapi = PHP_SAPI;
$upload_max_filesize = ini_get('upload_max_filesize');
$post_max_size = ini_get('post_max_size');
$max_execution_time = ini_get('max_execution_time');
$memory_limit = ini_get('memory_limit');
$display_errors = ini_get('display_errors');
$error_reporting_level = error_reporting(); // Zwraca poziom, nie nazwę
$log_errors = ini_get('log_errors');
$error_log_path = ini_get('error_log');
$timezone = ini_get('date.timezone') ?: 'Nie ustawiona';
$temp_dir = sys_get_temp_dir();

// Informacje o bieżącym żądaniu i aplikacji
$app_root_dir = TN_KORZEN_APLIKACJI ?? '<i>Nie zdefiniowano</i>'; // Katalog główny aplikacji
$script_filename = $_SERVER['SCRIPT_FILENAME'] ?? 'Nieznana'; // Ścieżka pliku skryptu
$request_uri = $_SERVER['REQUEST_URI'] ?? 'Nieznany'; // Żądany URI
$request_method = $_SERVER['REQUEST_METHOD'] ?? 'Nieznana'; // Metoda żądania
$server_protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'Nieznany'; // Protokół serwera

// Dodatkowe informacje o serwerze/PHP
$server_ip = $_SERVER['SERVER_ADDR'] ?? 'Nieznany'; // Adres IP serwera
$php_user = get_current_user() ?: 'Nieznany'; // Użytkownik, który uruchamia skrypt PHP
$server_name = $_SERVER['SERVER_NAME'] ?? 'Nieznana'; // Nazwa domeny serwera

// Informacje o DNS (testy rozdzielczości)
$server_ip_for_dns = $_SERVER['SERVER_ADDR'] ?? '';
$server_name_for_dns = $_SERVER['SERVER_NAME'] ?? '';
$reverse_dns = $server_ip_for_dns ? gethostbyaddr($server_ip_for_dns) : 'Brak IP serwera';
$forward_dns_server_name = $server_name_for_dns ? gethostbyname($server_name_for_dns) : 'Brak nazwy serwera';
// Test rozdzielczości zewnętrznej (dla popularnej domeny)
$external_domain_to_test = 'google.com';
$forward_dns_external = gethostbyname($external_domain_to_test);
$external_dns_status = ($forward_dns_external !== $external_domain_to_test && !empty($forward_dns_external)) ? htmlspecialchars($forward_dns_external) : 'Nie rozpoznano lub błąd';


// Sprawdzanie kluczowych rozszerzeń PHP
$extensions_to_check = [
    'gd',         // Do przetwarzania obrazów (miniatury, resize)
    'mbstring', // Do pracy z wielobajtowymi ciągami znaków (UTF-8)
    'json',       // Do kodowania/dekodowania danych JSON
    'curl',       // Do komunikacji z zewnętrznymi API (np. Allegro)
    'openssl',    // Często wymagane przez cURL i inne funkcje sieciowe
    'pdo_sqlite', // Jeśli w przyszłości używana będzie baza SQLite
    'zip',        // Jeśli planowany jest import/eksport w formatach ZIP
    'intl',       // Do internacjonalizacji i lokalizacji (np. formatowanie dat/liczb)
    'fileinfo', // Do określania typu MIME przesyłanych plików
];
$loaded_extensions = [];
foreach ($extensions_to_check as $ext) {
    $loaded_extensions[$ext] = extension_loaded($ext);
}

// Informacje o kluczowych katalogach i ich uprawnieniach (skrócone nazwy)
$dirs_to_check = [
    'Root' => TN_KORZEN_APLIKACJI ?? null,
    'Dane' => TN_SCIEZKA_DANE ?? null,
    'Upload' => TN_SCIEZKA_UPLOAD ?? null,
    'Upload/Obrazy' => TN_SCIEZKA_UPLOAD_IMAGES ?? null,
    'Upload/Avatary' => TN_SCIEZKA_UPLOAD_AVATARS ?? null,
    'Upload/Logo' => TN_SCIEZKA_UPLOAD_LOGO ?? null,
    'Upload/Ikony' => TN_SCIEZKA_UPLOAD_ICONS ?? null,
    'Logi' => TN_SCIEZKA_LOGS ?? null, // Sprawdź dedykowany katalog logs
    'Temp Sys' => $temp_dir, // Katalog tymczasowy PHP
];
$dirs_status = [];
foreach ($dirs_to_check as $label => $path) {
    if ($path === null || $path === false || $path === '') {
        $dirs_status[$label] = ['path' => '<i>Nie zdefiniowano/Nieznane</i>', 'status' => '<span class="badge text-bg-secondary">N/A</span>', 'perms' => '-', 'exists' => false, 'is_dir' => false];
        continue;
    }
    $exists = file_exists($path);
    $is_dir = is_dir($path);
    // Sprawdź uprawnienia zapisu dla kluczowych katalogów, gdzie aplikacja musi zapisywać
    // Używamy oryginalnych, pełnych nazw do sprawdzania uprawnień, ponieważ stałe
    // definiują pełne ścieżki, a logika sprawdzania uprawnień opiera się na ścieżce.
    $check_write = in_array($path, [
        TN_SCIEZKA_DANE ?? null,
        TN_SCIEZKA_UPLOAD ?? null,
        TN_SCIEZKA_UPLOAD_IMAGES ?? null,
        TN_SCIEZKA_UPLOAD_AVATARS ?? null,
        TN_SCIEZKA_UPLOAD_LOGO ?? null,
        TN_SCIEZKA_UPLOAD_ICONS ?? null,
        TN_SCIEZKA_LOGS ?? null,
        $temp_dir
    ], true); // Użyj true dla ścisłego porównania typu i wartości

    $dirs_status[$label] = [
        'path' => htmlspecialchars($path),
        'status' => $exists ? ($is_dir ? '<span class="badge text-bg-success">OK (Katalog)</span>' : '<span class="badge text-bg-warning text-dark">Istnieje (Plik?)</span>') : '<span class="badge text-bg-danger">Brak</span>',
        'perms' => $exists ? tn_check_permission($path, $check_write) : '-', // Sprawdź R/W tylko jeśli istnieje
        'exists' => $exists,
        'is_dir' => $is_dir,
    ];
}


// Informacje o plikach danych JSON
$data_files_to_check = [
    'Ustawienia' => TN_PLIK_USTAWIENIA ?? null,
    'Użytkownicy' => TN_PLIK_UZYTKOWNICY ?? null,
    'Produkty' => TN_PLIK_PRODUKTY ?? null,
    'Zamówienia' => TN_PLIK_ZAMOWIENIA ?? null,
    'Magazyn' => TN_PLIK_MAGAZYN ?? null,
    'Regały' => TN_PLIK_REGALY ?? null,
    'Zwroty' => TN_PLIK_ZWROTY ?? null,
    'Kurierzy' => TN_PLIK_KURIERZY ?? null,
    'Notatki Dashboard' => TN_PLIK_DASHBOARD_NOTES ?? null, // Dodano
    //'Zadania (opcjonalnie)' => defined('TN_PLIK_ZADANIA') ? TN_PLIK_ZADANIA : null, // Przykład opcjonalnego pliku
];
$data_files_status = [];
foreach ($data_files_to_check as $label => $path) {
    if ($path === null) {
        $data_files_status[$label] = ['path' => '<i>Nie zdefiniowano</i>', 'status' => '<span class="badge text-bg-secondary">N/A</span>', 'perms' => '-', 'size' => '-', 'modified' => '-'];
        continue;
    }
    $exists = file_exists($path);
    $data_files_status[$label] = [
        'path' => htmlspecialchars($path),
        'status' => $exists ? '<span class="badge text-bg-success">OK</span>' : '<span class="badge text-bg-danger">Brak pliku</span>',
        'perms' => tn_check_permission($path, true), // Sprawdź Read/Write dla plików danych
        'size' => $exists ? tn_format_bytes(filesize($path)) : '-',
        'modified' => $exists ? date('Y-m-d H:i:s', filemtime($path)) : '-'
    ];
}

// Informacje o zdefiniowanych stałych aplikacji
$app_constants = [
    'TN_APP_VERSION',
    'TN_KORZEN_APLIKACJI',
    'TN_SCIEZKA_SRC',
    'TN_SCIEZKA_DANE',
    'TN_SCIEZKA_TEMPLATKI',
    'TN_SCIEZKA_UPLOAD',
    'TN_SCIEZKA_UPLOAD_IMAGES',
    'TN_SCIEZKA_UPLOAD_AVATARS',
    'TN_SCIEZKA_UPLOAD_LOGO',
    'TN_SCIEZKA_UPLOAD_ICONS',
    'TN_SCIEZKA_LOGS',
    'TN_PLIK_USTAWIENIA',
    'TN_PLIK_UZYTKOWNICY',
    'TN_PLIK_PRODUKTY',
    'TN_PLIK_ZAMOWIENIA',
    'TN_PLIK_MAGAZYN',
    'TN_PLIK_REGALY',
    'TN_PLIK_ZWROTY',
    'TN_PLIK_KURIERZY',
    'TN_PLIK_DASHBOARD_NOTES',
    // 'TN_PLIK_ZADANIA', // Przykład opcjonalnej stałej
];
$constants_status = [];
foreach ($app_constants as $const_name) {
    $constants_status[$const_name] = defined($const_name) ? constant($const_name) : '<i>Nie zdefiniowano</i>';
}


// Informacje o Sesji PHP
$session_status_map = [ PHP_SESSION_DISABLED => 'Wyłączone', PHP_SESSION_NONE => 'Brak aktywnej', PHP_SESSION_ACTIVE => 'Aktywna' ];
$session_id = session_id();
$session_status = session_status();
$session_name = session_name();
$session_cookie_params = session_get_cookie_params();

// Informacje o użytkowniku i połączeniu
$remote_addr = $_SERVER['REMOTE_ADDR'] ?? 'Nieznany';
$remote_port = $_SERVER['REMOTE_PORT'] ?? 'Nieznany';
$http_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Nieznany';
$http_referer = $_SERVER['HTTP_REFERER'] ?? 'Brak';

// --- Przygotowanie danych użytkownika i ról (PRZYKŁAD) ---

$logged_in_user_id = $_SESSION['tn_user_id'] ?? 'Brak (niezalogowany)';
$logged_in_username = $_SESSION['tn_username'] ?? 'Gość'; 
$logged_in_user_role = $_SESSION['tn_imie_nazwisko'] ?? 'Gość'; // 
// Możesz dodać więcej pól, np. email, data rejestracji itp., jeśli są dostępne
// $logged_in_user_email = $_SESSION['tn_email'] ?? 'Brak';
// $logged_in_registration_date = $_SESSION['tn_registration_date'] ?? 'Brak';
// ---------------------------------------------------------

?>

<div class="container-fluid px-lg-4 py-4">
   

  

    <div class="row g-4">
        <?php // Kolumna lewa ?>
        <div class="col-lg-6">

            <?php // Karta: Informacje o Aplikacji ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary-subtle text-primary-emphasis py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-app-indicator me-2"></i>Informacje</h6></div>
                <div class="card-body">
                    <table class="table table-sm small mb-0 table-borderless">
                        <tbody>
                            <tr>
                                <th scope="row" style="width: 40%;">Nazwa </th>
                                <td><?php echo htmlspecialchars($tn_app_name ?? 'TN iMAG'); ?></td> <?php // Użyj zmiennej, jeśli dostępna, lub domyślnej nazwy ?>
                            </tr>
                            <tr>
                                <th scope="row">Wersja</th>
                                <td><?php echo htmlspecialchars(TN_APP_VERSION); ?></td>
                            </tr>
                            <tr><th scope="row">Katalog Główny</th><td><code><?php echo htmlspecialchars($app_root_dir); ?></code></td></tr>
                            <tr><th scope="row">Plik Skryptu</th><td><code><?php echo htmlspecialchars($script_filename); ?></code></td></tr>
                            <tr><th scope="row">Żądany URI</th><td><code><?php echo htmlspecialchars($request_uri); ?></code></td></tr>
                            <tr><th scope="row">Metoda Żądania</th><td><code><?php echo htmlspecialchars($request_method); ?></code></td></tr>
                            <tr><th scope="row">Protokół Serwera</th><td><code><?php echo htmlspecialchars($server_protocol); ?></code></td></tr>
                             </tbody>
                    </table>
                </div>
            </div>

            <?php // Karta: Informacje o Serwerze ?>
            <div class="card shadow-sm mb-4">
                 <div class="card-header bg-info-subtle text-info-emphasis py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-hdd-stack me-2"></i>Serwer</h6></div>
                 <div class="card-body">
                     <table class="table table-sm small mb-0 table-borderless">
                          <tbody>
                              <tr><th scope="row" style="width: 40%;">Wersja PHP</th><td><?php echo htmlspecialchars($php_version); ?></td></tr>
                              <tr><th scope="row">System</th><td><?php echo htmlspecialchars($server_os); ?></td></tr>
                              <tr><th scope="row">Oprogramowanie </th><td><?php echo htmlspecialchars($server_software); ?></td></tr>
                              <tr><th scope="row">SAPI (Interfejs)</th><td><?php echo htmlspecialchars($php_sapi); ?></td></tr>
                              <tr><th scope="row">Adres IP Serwera</th><td><code><?php echo htmlspecialchars($server_ip); ?></code></td></tr>
                              <tr><th scope="row">Nazwa Użytkownika PHP</th><td><code><?php echo htmlspecialchars($php_user); ?></code></td></tr>
                              <tr><th scope="row">Nazwa Domeny Serwera</th><td><code><?php echo htmlspecialchars($server_name); ?></code></td></tr>
                              <tr><th scope="row">Wsteczne DNS (PTR)</th><td><code><?php echo htmlspecialchars((string) $reverse_dns); ?></code></td></tr>
                              <tr><th scope="row">DNS serwera (A)</th><td><code><?php echo htmlspecialchars((string) $forward_dns_server_name); ?></code></td></tr>
                              <tr><th scope="row">DNS zewnętrzny (<?php echo htmlspecialchars($external_domain_to_test); ?>)</th><td><code><?php echo $external_dns_status; ?></code></td></tr>
                           </tbody>
                     </table>
                 </div>
            </div>

            <?php // Karta: Konfiguracja PHP (rozszerzona) ?>
            <div class="card shadow-sm mb-4">
                 <div class="card-header bg-warning-subtle text-warning-emphasis py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-gear-wide-connected me-2"></i>Konfiguracja</h6></div>
                 <div class="card-body">
                     <table class="table table-sm small mb-0 table-borderless">
                          <tbody>
                              <tr><th scope="row" style="width: 40%;">Wyświetlanie Błędów (display_errors)</th><td><code><?php echo htmlspecialchars($display_errors); ?></code> <?php echo $display_errors == '1' || strtolower($display_errors) === 'on' ? '<span class="badge bg-warning text-dark ms-1">Zalecane "Off" w prod.</span>' : '<span class="badge bg-success ms-1">OK</span>'; ?></td></tr>
                              <tr><th scope="row">Raportowanie Błędów (error_reporting)</th><td><code><?php echo $error_reporting_level; ?></code> (Poziom numeryczny)</td></tr>
                              <tr><th scope="row">Logowanie Błędów (log_errors)</th><td><code><?php echo htmlspecialchars($log_errors); ?></code> <?php echo $log_errors == '1' || strtolower($log_errors) === 'on' ? '<span class="badge bg-success ms-1">OK</span>' : '<span class="badge bg-warning text-dark ms-1">Zalecane "On"</span>'; ?></td></tr>
                              <tr><th scope="row">Ścieżka Logu Błędów (error_log)</th><td><code><?php echo htmlspecialchars($error_log_path ?: 'Domyślna serwera'); ?></code></td></tr>
                              <tr><th scope="row">Strefa Czasowa (date.timezone)</th><td><code><?php echo htmlspecialchars($timezone); ?></code> <?php echo $timezone === 'Nie ustawiona' ? '<span class="badge bg-warning text-dark ms-1">Ustaw w php.ini</span>' : ''; ?></td></tr>
                              <tr><th scope="row">Max. Rozmiar Uploadu (upload_max_filesize)</th><td><code><?php echo htmlspecialchars($upload_max_filesize); ?></code></td></tr>
                              <tr><th scope="row">Max. Rozmiar POST (post_max_size)</th><td><code><?php echo htmlspecialchars($post_max_size); ?></code></td></tr>
                              <tr><th scope="row">Limit Czasu Wykonania (max_execution_time)</th><td><code><?php echo htmlspecialchars($max_execution_time); ?> s</code></td></tr>
                              <tr><th scope="row">Limit Pamięci (memory_limit)</th><td><code><?php echo htmlspecialchars($memory_limit); ?></code></td></tr>
                           </tbody>
                     </table>
                 </div>
            </div>

            <?php // Karta: Załadowane Rozszerzenia PHP ?>
            <div class="card shadow-sm mb-4">
                 <div class="card-header bg-success-subtle text-success-emphasis py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-puzzle me-2"></i> Rozszerzenia</h6></div>
                 <div class="card-body">
                     <ul class="list-group list-group-flush small">
                          <?php foreach ($loaded_extensions as $ext_name => $is_loaded): ?>
                             <li class="list-group-item d-flex justify-content-between align-items-center py-1 px-0 bg-transparent">
                                 <code><?php echo $ext_name; ?></code>
                                 <?php echo $is_loaded ? '<span class="badge text-bg-success">Załadowane</span>' : '<span class="badge text-bg-danger">Brak</span>'; ?>
                             </li>
                          <?php endforeach; ?>
                     </ul>
                 </div>
                 <div class="card-footer small text-muted py-1 px-3">
                      Sprawdza obecność rozszerzeń kluczowych dla funkcjonalności aplikacji.
                 </div>
            </div>


        </div> <?php // Koniec lewej kolumny ?>

        <?php // Kolumna prawa ?>
        <div class="col-lg-6">

            <?php // Karta: Status Kluczowych Katalogów ?>
            <div class="card shadow-sm mb-4">
                 <div class="card-header bg-secondary-subtle text-secondary-emphasis py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-folder me-2"></i>Katalogi</h6></div>
                 <div class="card-body p-0">
                     <div class="table-responsive">
                         <table class="table table-sm small table-striped table-hover mb-0">
                             <thead class="table-light">
                                 <tr>
                                     <th>Katalog</th>
                                     <th class="text-center">Status</th>
                                     <th class="text-center">Uprawnienia (O/Z)</th>
                                     <th>Ścieżka</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 <?php foreach ($dirs_status as $label => $status): ?>
                                 <tr>
                                     <td class="fw-medium"><?php echo htmlspecialchars($label); ?></td>
                                     <td class="text-center"><?php echo $status['status']; ?></td>
                                     <td class="text-center"><?php echo $status['perms']; ?></td>
                                     <td><code><?php echo $status['path']; ?></code></td>
                                 </tr>
                                 <?php endforeach; ?>
                             </tbody>
                         </table>
                     </div>
                 </div>
                 <div class="card-footer small text-muted py-1 px-3">
                      <i class="bi bi-info-circle me-1"></i>Uprawnienia zapisu są kluczowe dla katalogów danych.
                 </div>
            </div>

            <?php // Karta: Status Plików Danych JSON ?>
            <div class="card shadow-sm mb-4">
                  <div class="card-header bg-dark-subtle text-dark-emphasis py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-database me-2"></i>Baza Danychh</h6>
                  </div>
                  <div class="card-body p-0">
                      <div class="table-responsive">
                          <table class="table table-sm small table-striped table-hover mb-0">
                               <thead class="table-light">
                                   <tr>
                                       <th>Plik Danych</th>
                                       <th class="text-center">Status</th>
                                       <th class="text-center">Uprawnienia (O/Z)</th>
                                       <th class="text-end">Rozmiar</th>
                                       <th>Ostatnia Modyfikacja</th>
                                   </tr>
                               </thead>
                               <tbody>
                                   <?php foreach ($data_files_status as $label => $status): ?>
                                   <tr>
                                       <td class="fw-medium"><?php echo htmlspecialchars($label); ?></td>
                                       <td class="text-center"><?php echo $status['status']; ?></td>
                                       <td class="text-center"><?php echo $status['perms']; ?></td>
                                       <td class="text-end font-monospace"><?php echo $status['size']; ?></td>
                                       <td class="text-muted text-nowrap"><?php echo $status['modified']; ?></td>
                                   </tr>
                                   <?php endforeach; ?>
                               </tbody>
                          </table>
                      </div>
                  </div>
                  <div class="card-footer small text-muted py-1 px-3">
                       <i class="bi bi-info-circle me-1"></i> Sprawdza elementy bazy danych. Uprawnienia zapisu są niezbędne do działania aplikacji.
                  </div>
            </div>

            <?php // Karta: Informacje o Użytkowniku i Połączeniu ?>
            <div class="card shadow-sm mb-4">
                 <div class="card-header bg-primary-subtle text-primary-emphasis py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-person-circle me-2"></i>Informacje o polączeniu</h6></div>
                 <div class="card-body">
                     <table class="table table-sm small mb-0 table-borderless">
                          <tbody>
                              <tr><th scope="row" style="width: 40%;">Adres IP</th><td><code><?php echo htmlspecialchars($remote_addr); ?></code></td></tr>
                              <tr><th scope="row">Port</th><td><code><?php echo htmlspecialchars($remote_port); ?></code></td></tr>
                              <tr><th scope="row">User Agent</th><td><code><?php echo htmlspecialchars($http_user_agent); ?></code></td></tr>
                              <tr><th scope="row">Referer</th><td><code><?php echo htmlspecialchars($http_referer); ?></code></td></tr>
                         
                              <tr><th scope="row">ID | Nazwa użytkownika </th><td><b><code>ID:<?php echo htmlspecialchars((string) $logged_in_user_id); ?></code> | <?php echo htmlspecialchars($logged_in_username); ?></b></td></tr>
                            
                         
                           </tbody>
                     </table>
                 </div>
                 <div class="card-footer small text-muted py-1 px-3">
                      <i class="bi bi-info-circle me-1"></i> Informacje o połączeniu klienta z serwerem oraz dane logowania.
                 </div>
            </div>


            <?php // Karta: Informacje o Sesji PHP ?>
            <div class="card shadow-sm mb-4">
                 <div class="card-header bg-danger-subtle text-danger-emphasis py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-shield-lock me-2"></i>Informacje o Sesji</h6></div>
                 <div class="card-body">
                     <table class="table table-sm small mb-0 table-borderless">
                          <tbody>
                              <tr><th scope="row" style="width: 40%;">Status Sesji</th><td><?php echo $session_status_map[$session_status] ?? 'Nieznany'; ?></td></tr>
                              <tr><th scope="row">ID</th><td><code><?php echo htmlspecialchars($session_id ?: 'Brak'); ?></code></td></tr>
                              <tr><th scope="row">Cookie</th><td><code><?php echo htmlspecialchars($session_name); ?></code></td></tr>
                              <tr><th scope="row">Czas życia Cookie</th><td><?php echo $session_cookie_params['lifetime'] > 0 ? $session_cookie_params['lifetime'] . ' s' : 'Do zamknięcia przeglądarki'; ?></td></tr>
                              <tr><th scope="row">Ścieżka Cookie</th><td><code><?php echo htmlspecialchars($session_cookie_params['path']); ?></code></td></tr>
                              
                              <tr><th scope="row">Secure Cookie (HTTPS)</th><td><?php echo $session_cookie_params['secure'] ? '<span class="badge text-bg-success">Tak</span>' : '<span class="badge bg-warning text-dark">Nie (HTTP)</span>'; ?></td></tr>
                               <tr><th scope="row">HttpOnly Cookie</th><td><?php echo $session_cookie_params['httponly'] ? '<span class="badge text-bg-success">Tak</span>' : '<span class="badge bg-warning text-dark">Nie</span>'; ?></td></tr>
                           </tbody>
                     </table>
                 </div>
                 <div class="card-footer small text-muted py-1 px-3">
                      <i class="bi bi-info-circle me-1"></i> Bezpieczna konfiguracja sesji jest ważna dla ochrony danych.
                 </div>
            </div>


        </div> <?php // Koniec prawej kolumny ?>
    </div> <?php // Koniec .row ?>
</div> <?php // Koniec .container-fluid ?>

<?php // Style specyficzne dla tej strony ?>
<style>
/* Dodaj niestandardowe style, jeśli potrzebne */
.table th[scope="row"] {
    font-weight: 500; /* Standardowa waga dla nagłówków wierszy */
}

/* Styl dla tabel bez obramowania, aby wiersze były bardziej oddzielone */
.table-borderless tbody tr {
    border-bottom: 1px solid var(--bs-border-color-translucent); /* Delikatna linia pod każdym wierszem */
}
.table-borderless tbody tr:last-child {
    border-bottom: none; /* Brak linii pod ostatnim wierszem */
}

/* Zmniejszenie odstępów w listach w kartach */
.card-body .list-group-item.py-1 {
    padding-top: 0.25rem !important;
    padding-bottom: 0.25rem !important;
}

/* Styl dla ścieżek i wartości konfiguracyjnych */
code {
    font-size: 0.9em; /* Nieco mniejsza czcionka dla kodu */
    color: var(--bs-code-color); /* Kolor kodu z Bootstrapa */
     /*word-break: break-all;*/ /* Łam długie ścieżki */
    /* Dodaj tło dla lepszej czytelności */
    background-color: var(--bs-tertiary-bg);
    padding: 0.1rem 0.4rem;
    border-radius: var(--bs-border-radius-sm);
}
[data-bs-theme="dark"] code {
     background-color: var(--bs-secondary-bg);
}


/* Odstęp między badge'ami O/Z */
.badge + .badge {
    margin-left: 0.25rem;
}

/* Styl dla wierszy tabeli z kodem */
.table code {
    background-color: transparent; 
    padding: 0;
    border-radius: 0;
}

/* Zmniejszenie czcionki dla tabel i list o klasie small wewnątrz card-body */
.card-body .table.small,
.card-body .list-group.small {
    font-size: 0.85em; 
}


</style>
