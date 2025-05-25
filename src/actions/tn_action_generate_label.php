<?php
// tnapp/src/actions/tn_action_generate_label.php
/**
 * Akcja do generowania etykiety wysyłkowej dla danego zamówienia.
 * Wersja: 1.0
 */

// Załaduj konfigurację i helpery
require_once dirname(__DIR__, 2) . '/config/tn_config.php';
require_once TN_SCIEZKA_SRC . 'functions/tn_data_helpers.php';
require_once TN_SCIEZKA_SRC . 'functions/tn_flash_messages.php'; // Do komunikatów
require_once TN_SCIEZKA_SRC . 'functions/tn_url_helpers.php'; // Do przekierowań

// Ustawienie nagłówków, aby przeglądarka wiedziała, że to plik do pobrania/wyświetlenia jako HTML
header('Content-Type: text/html; charset=UTF-8');
// header('Content-Disposition: attachment; filename="etykieta_zamowienie_' . ($order_id ?? 'nieznane') . '.html"'); // Opcjonalnie do wymuszenia pobrania

// Pobierz ID zamówienia z parametrów GET
$order_id = $_GET['id'] ?? null;

if (empty($order_id)) {
    tn_ustaw_komunikat_flash('Błąd: Nie podano ID zamówienia do wygenerowania etykiety.', 'danger');
    $redirect_url = tn_generuj_url('orders');
    header("Location: " . $redirect_url);
    exit;
}

// Ścieżka do pliku z zamówieniami
$plik_zamowien = TN_SCIEZKA_DANE . 'orders.json';

// Załaduj wszystkie zamówienia
$wszystkie_zamowienia = tn_laduj_zamowienia($plik_zamowien);

// Znajdź zamówienie o podanym ID
$zamowienie = null;
foreach ($wszystkie_zamowienia as $z) {
    if (isset($z['id']) && (string)$z['id'] === (string)$order_id) {
        $zamowienie = $z;
        break;
    }
}

if ($zamowienie === null) {
    tn_ustaw_komunikat_flash("Błąd: Nie znaleziono zamówienia o ID #{$order_id}.", 'danger');
    $redirect_url = tn_generuj_url('orders');
    header("Location: " . $redirect_url);
    exit;
}

// --- Przygotowanie danych etykiety ---
$dane_wysylki = htmlspecialchars($zamowienie['buyer_daneWysylki'] ?? 'BRAK DANYCH ADRESOWYCH');
$numer_zamowienia = htmlspecialchars($zamowienie['id'] ?? 'B/D');
// Można dodać więcej szczegółów, np. produkty, jeśli są potrzebne na etykiecie

// --- Generowanie treści etykiety (prostego HTML do druku) ---
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etykieta Wysyłkowa - Zamówienie #<?php echo $numer_zamowienia; ?></title>
    <style>
        body { font-family: sans-serif; margin: 0; padding: 20mm; }
        .etykieta { border: 1px solid #000; padding: 15mm; width: 100mm; height: 150mm; box-sizing: border-box; }
        .naglowek { text-align: center; margin-bottom: 15mm; }
        .naglowek h1 { margin: 0; font-size: 1.2em; }
        .adres { margin-bottom: 15mm; }
        .adres strong { display: block; margin-bottom: 5mm; }
        .adres p { margin: 0; white-space: pre-wrap; } /* Zachowaj formatowanie z textarea */
        .stopka { margin-top: 15mm; text-align: center; font-size: 0.8em; }
        @media print {
            body { padding: 0; }
            .etykieta { border: none; width: 100%; height: auto; }
            /* Można dodać @page size, aby wymusić rozmiar etykiety */
        }
    </style>
</head>
<body>
    <div class="etykieta">
        <div class="naglowek">
            <h1>TN iMAG - Etykieta Wysyłkowa</h1>
            <p>Zamówienie #<?php echo $numer_zamowienia; ?></p>
        </div>

        <div class="adres">
            <strong>Adres odbiorcy:</strong>
            <p><?php echo $dane_wysylki; ?></p>
        </div>

        <?php // Dodaj opcjonalnie kod kreskowy, jeśli masz helpera ?>
        <?php /*
        <div class="kod-kreskowy" style="text-align: center; margin-top: 20mm;">
             <?php
             // Przykład użycia hipotetycznej funkcji do generowania kodu kreskowego
             // require_once TN_SCIEZKA_SRC . 'functions/tn_barcode_helpers.php'; // Załaduj helpera
             // echo tn_generuj_kod_kreskowy($numer_zamowienia); // Wywołaj funkcję generującą kod kreskowy (np. jako obraz SVG/PNG)
             ?>
             <p>Numer Zamówienia (Kod Kreskowy)</p>
        </div>
        */ ?>

        <div class="stopka">
            <p><?php echo htmlspecialchars($tn_ustawienia_globalne['stopka'] ?? 'TN iMAG WMS'); ?></p>
            <?php // Można dodać adres nadawcy ze zmiennych globalnych ?>
        </div>
    </div>
    <script>
        // Opcjonalnie: automatyczne wywołanie drukowania po załadowaniu
        window.onload = function() {
            window.print();
            // Opcjonalnie: zamknij okno po wydruku/zamknięciu dialogu drukowania
            // setTimeout(function(){ window.close(); }, 100); // Działa różnie w przeglądarkach
        }
    </script>
</body>
</html>
<?php
exit; // Zakończ działanie skryptu po wygenerowaniu etykiety
?>