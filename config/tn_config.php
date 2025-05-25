<?php
/**
 * @autor: Paweł Plichta / tnApp
 * @wersja: 1.5.0
 * @app: tnApp (TN iMAG)
 */

ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_secure', '1'); 
session_name('tns01_ptx01'); 
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
} else {
    ini_set('session.cookie_secure', 0);
}
// session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => isset($_SERVER['HTTPS'])]); // Nowoczesne ustawienia, wymagają PHP 7.3+
ini_set('session.gc_maxlifetime', 1440);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', '0');
error_reporting(1);
ini_set('log_errors', '1');
ini_set('error_log', '/TNlogi/bledy.log');
define('TN_KLUCZ_SESJI', '129831u3b12b3123fdsfbsdfsd-urfbsb'); // Zmień to!

// Domyślna strefa czasowa
date_default_timezone_set('Europe/Warsaw'); // Ustaw odpowiednią strefę czasową

// Poziom raportowania błędów (w środowisku produkcyjnym ustaw na 0)
error_reporting(E_ALL); // E_ALL w środowisku deweloperskim
ini_set('display_errors', 0); // 1 w środowisku deweloperskim, 0 w produkcyjnym
$tn_cookie_domain = 'imag.tnapp.pl'; 
// Pobierz host z żądania
$host = $_SERVER['HTTP_HOST'] ?? '';

if (!empty($host)) {
    // Usuń ewentualny numer portu
    $host = parse_url("https://$host", PHP_URL_HOST) ?: $host;
    if ($host !== 'localhost' && !filter_var($host, FILTER_VALIDATE_IP)) {
       
        $tn_cookie_domain = '.' . $host;
        
         if (strpos($tn_cookie_domain, '.www.') === 0) {
             $tn_cookie_domain = substr($tn_cookie_domain, 4); // Usuń '.www'
         }
    }

}


define('TN_DOMENA_CIASTECZEK', $tn_cookie_domain);


// $tn_ustawienia_globalne = json_decode(file_get_contents(TN_SCIEZKA_DANYCH . 'settings.json'), true) ?? [];

// Format daty i czasu (można go wyciągnąć z $tn_ustawienia_globalne, jeśli są ładowane)
// $tn_format_daty_czasu = ($tn_ustawienia_globalne['tn_format_daty'] ?? 'd.m.Y') . ' ' . ($tn_ustawienia_globalne['tn_format_czasu'] ?? 'H:i');


define('TN_KORZEN_APLIKACJI', dirname(__DIR__)); 
define('TN_SCIEZKA_DANE', TN_KORZEN_APLIKACJI . '/TNbazaDanych/');
define('TN_SCIEZKA_SRC', TN_KORZEN_APLIKACJI . '/src/');
define('TN_SCIEZKA_TEMPLATEK', TN_KORZEN_APLIKACJI . '/templates/');
define('TN_SCIEZKA_PUBLIC', TN_KORZEN_APLIKACJI . '/public/');
define('TN_SCIEZKA_UPLOAD', TN_KORZEN_APLIKACJI . '/TNuploads/');
define('TN_SCIEZKA_AVATARS', TN_SCIEZKA_PUBLIC . 'avatars/');
define('TN_URL_AVATARS', '/public/avatars/');

// --- Stałe Plików Danych ---
// Upewnij się, że TN_PLIK_POJAZDY jest tutaj zdefiniowane
if (!defined('TN_PLIK_POJAZDY')) {
    define('TN_PLIK_POJAZDY', TN_KORZEN_APLIKACJI . 'vehicles.json'); // Zakładamy, że plik jest w głównym katalogu
}
define('TN_PLIK_PRODUKTY', TN_SCIEZKA_DANE . 'products.json');
define('TN_PLIK_ZAMOWIENIA', TN_SCIEZKA_DANE . 'orders.json');
define('TN_PLIK_USTAWIENIA', TN_SCIEZKA_DANE . 'settings.json');
define('TN_PLIK_MAGAZYN', TN_SCIEZKA_DANE . 'warehouse.json');
define('TN_PLIK_REGALY', TN_SCIEZKA_DANE . 'regaly.json');
define('TN_PLIK_UZYTKOWNICY', TN_SCIEZKA_DANE . 'users.json');
define('TN_PLIK_ZWROTY', TN_SCIEZKA_DANE . 'returns.json');
define('TN_PLIK_KURIERZY', TN_SCIEZKA_DANE . 'couriers.json');

$tn_wymagane_katalogi = [
    TN_SCIEZKA_DANE => 0777, TN_SCIEZKA_UPLOAD => 0777, TN_SCIEZKA_AVATARS => 0777
];
foreach ($tn_wymagane_katalogi as $tn_kat => $tn_chmod) {
    if (!is_dir($tn_kat)) {
        if (!@mkdir($tn_kat, $tn_chmod, true) && !is_dir($tn_kat)) { die("Błąd krytyczny: Nie można utworzyć katalogu ({$tn_kat})."); }
        else { $htaccess_content = "Options -Indexes\n<FilesMatch \"\\.(php|phtml|php[3-7]|phps|pl|py|cgi|asp|aspx|sh|bash)\">\n Deny from all\n</FilesMatch>"; if ($tn_kat === TN_SCIEZKA_DANE) $htaccess_content .= "\nDeny from all"; @file_put_contents($tn_kat . '/.htaccess', $htaccess_content); }
    }
    if (!is_writable($tn_kat)) { die("Błąd: Katalog '{$tn_kat}' nie został zapisany."); }
}

define('TN_STATUSY_ZAMOWIEN', ['Nowe', 'W przygotowaniu', 'Zrealizowane', 'Anulowane', 'Zwrot towaru', 'Wymiana towaru', 'Reklamacja']);
define('TN_STATUSY_PLATNOSCI', ['Nieopłacone', 'Opłacone', 'Nadpłata', 'Zwrot częściowy', 'Zwrot całkowity']);
define('TN_STATUSY_ZWROTOW', ['Nowe zgłoszenie', 'W trakcie rozpatrywania', 'Reklamacja przyjęta', 'Zwrot przyjęty', 'Wymiana towaru', 'Oczekuje na zwrot towaru', 'Zaakceptowana', 'Zrealizowane', 'Odrzucona', 'Zakończona']);

$tn_domyslne_ustawienia = [
    'produkty_na_stronie'    => 15,
    'zamowienia_na_stronie'  => 15,
    'zwroty_na_stronie'      => 15,
    'waluta'                 => 'PLN',
    'domyslny_procent_rabatu' => 0.0, 
    'tn_format_daty'         => 'd.m.Y',
    'tn_format_czasu'        => 'H:i',
    'firma' => [ 'tn_nazwa_firmy' => 'tnApp', 'tn_email_kontaktowy' => 'info@tnApp.pl' ],
    'nazwa_strony'           => 'tnApp',
    'logo_strony'            => '/TNimg/logo.png', 
    'tekst_stopki'           => 'tnApp',
    'linki_menu'             => [
        ['tytul' => 'Pulpit', 'url' => '/', 'ikona' => 'bi-speedometer2', 'grupa' => 'Nawigacja', 'id' => 'dashboard'],
        ['tytul' => 'Produkty', 'url' => '/produkty', 'ikona' => 'bi-boxes', 'grupa' => 'Magazyn', 'id' => 'products'],
        ['tytul' => 'Zamówienia', 'url' => '/zamowienia', 'ikona' => 'bi-receipt', 'grupa' => 'Sprzedaż', 'id' => 'orders'],
        ['tytul' => 'Zwroty', 'url' => '/zwroty', 'ikona' => 'bi-arrow-return-left', 'grupa' => 'Sprzedaż', 'id' => 'returns_list'],
        ['tytul' => 'Magazyn', 'url' => '/magazyn', 'ikona' => 'bi-grid-3x3-gap', 'grupa' => 'Magazyn', 'id' => 'warehouse_view'],
        ['tytul' => 'Ustawienia', 'url' => '#', 'ikona' => 'bi-sliders', 'grupa' => 'System', 'id' => 'setting',
         'submenu' => [
             ['tytul' => 'Ogólne', 'url' => '/ustawienia', 'ikona' => 'bi-gear-wide-connected', 'id' => 'settings'], 
	 ['tytul' => 'Baza pojazdów', 'url' => '/pojazdy', 'ikona' => 'bi-gear-wide-connected', 'id' => 'vehicles'], 
             ['tytul' => 'Kurierzy', 'url' => '/kurierzy', 'ikona' => 'bi-truck', 'id' => 'couriers_list'],
         ]
        ],
        ['tytul' => 'Mój Profil', 'url' => '/profil', 'ikona' => 'bi-person-badge', 'grupa' => 'System', 'id' => 'profile'],
        ['tytul' => 'Informacje', 'url' => '/informacje', 'ikona' => 'bi-info-circle', 'grupa' => 'System', 'id' => 'info'],
        ['tytul' => 'Pomoc', 'url' => '/pomoc', 'ikona' => 'bi-question-circle', 'grupa' => 'System', 'id' => 'help'],
    ],
    'wyglad' => [ 
        'tn_motyw'             => 'jasny', 'tn_kolor_sidebar'     => 'ciemny',
        'tn_tabela_paskowana'  => false, 'tn_tabela_krawedzie'  => false,
        'tn_kolor_akcentu'     => '#0d6efd', 'rozmiar_czcionki'     => '12px' 
    ],
    
    'domyslny_magazyn'       => '_DMS', 
    'tn_domyslny_status_zam' => 'Nowe',
    'tn_prog_niskiego_stanu' => 1,
    'magazyn' => [ 'tn_prefix_poziom_domyslny' => 'POZ', 'tn_prefix_miejsca_domyslny' => 'NRM'],
    'zwroty_reklamacje' => [ 
        'domyslny_status' => 'Nowe zgłoszenie',
        'statusy' => TN_STATUSY_ZWROTOW
    ]
];


$tn_prawidlowe_statusy_zamowien = ['Nowe', 'W przygotowaniu', 'Zrealizowane', 'Anulowane', 'Zwrot towaru', 'Wymiana towaru', 'Reklamacja'];
$tn_prawidlowe_statusy_platnosci = ['Nieopłacone', 'Opłacone', 'Nadpłata', 'Zwrot częściowy', 'Zwrot całkowity'];
$tn_prawidlowe_statusy_zwrotow = ['Nowe zgłoszenie', 'W trakcie rozpatrywania', 'Reklamacja przyjęta', 'Zwrot przyjęty', 'Wymiana towaru', 'Oczekuje na zwrot towaru', 'Zaakceptowana', 'Zrealizowan', 'Odrzucona', 'Zakończona'];

$GLOBALS['tn_prawidlowe_statusy'] = $tn_prawidlowe_statusy_zamowien;
$GLOBALS['tn_prawidlowe_statusy_platnosci'] = $tn_prawidlowe_statusy_platnosci;
$GLOBALS['tn_prawidlowe_statusy_zwrotow'] = $tn_prawidlowe_statusy_zwrotow;

date_default_timezone_set('Europe/Warsaw');

?>
