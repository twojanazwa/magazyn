<?php
// src/actions/tn_action_save_vehicle.php
/**
 * Plik akcji obsługujący zapis danych nowego pojazdu.
 *
 * Oczekuje danych POST z formularza dodawania pojazdu.
 * Waliduje dane i zapisuje je do pliku vehicles.json.
 */

declare(strict_types=1);

// Dołącz niezbędne pliki z funkcjami pomocniczymi
require_once __DIR__ . '/../functions/tn_data_helpers.php';
require_once __DIR__ . '/../functions/tn_flash_messages.php';
require_once __DIR__ . '/../functions/tn_url_helpers.php';
require_once __DIR__ . '/../functions/tn_security_helpers.php'; // Potrzebne do walidacji CSRF, choć walidacja jest w index.php

// Upewnij się, że żądanie jest typu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    tn_ustaw_komunikat_flash('Nieprawidłowa metoda żądania.', 'danger');
    header('Location: ' . tn_generuj_url('vehicles'));
    exit;
}

// Walidacja danych wejściowych
$make = trim($_POST['make'] ?? '');
$model = trim($_POST['model'] ?? '');
$version_name = trim($_POST['version_name'] ?? '');
$version_code = trim($_POST['version_code'] ?? '');
$capacity = filter_var($_POST['capacity'] ?? null, FILTER_VALIDATE_INT);
$kw = filter_var($_POST['kw'] ?? null, FILTER_VALIDATE_FLOAT);
$hp = filter_var($_POST['hp'] ?? null, FILTER_VALIDATE_FLOAT);
$year_start = filter_var($_POST['year_start'] ?? null, FILTER_VALIDATE_INT);
$year_end = filter_var($_POST['year_end'] ?? null, FILTER_VALIDATE_INT);

$bledy_walidacji = [];

if (empty($make)) {
    $bledy_walidacji[] = 'Marka pojazdu jest wymagana.';
}
if (empty($model)) {
    $bledy_walidacji[] = 'Model pojazdu jest wymagany.';
}

// Opcjonalna walidacja dla pól numerycznych, jeśli zostały wypełnione
if ($capacity !== false && $capacity < 0) {
    $bledy_walidacji[] = 'Pojemność nie może być ujemna.';
} elseif ($capacity === false && !empty($_POST['capacity'])) {
     $bledy_walidacji[] = 'Nieprawidłowa wartość pojemności.';
}
if ($kw !== false && $kw < 0) {
    $bledy_walidacji[] = 'Moc (kW) nie może być ujemna.';
} elseif ($kw === false && !empty($_POST['kw'])) {
    $bledy_walidacji[] = 'Nieprawidłowa wartość mocy (kW).';
}
if ($hp !== false && $hp < 0) {
    $bledy_walidacji[] = 'Moc (KM) nie może być ujemna.';
} elseif ($hp === false && !empty($_POST['hp'])) {
     $bledy_walidacji[] = 'Nieprawidłowa wartość mocy (KM).';
}

// Walidacja roczników
if ($year_start !== false && $year_end !== false) {
    if ($year_start > $year_end) {
        $bledy_walidacji[] = 'Rocznik początkowy nie może być późniejszy niż rocznik końcowy.';
    }
    if ($year_start < 1900 || $year_start > (int)date('Y')) {
         // Można dostosować zakres lat
         $bledy_walidacji[] = 'Nieprawidłowy rocznik początkowy.';
    }
     if ($year_end < 1900 || $year_end > (int)date('Y') + 1) {
         // Można dostosować zakres lat
         $bledy_walidacji[] = 'Nieprawidłowy rocznik końcowy.';
    }
} elseif ($year_start === false && !empty($_POST['year_start'])) {
     $bledy_walidacji[] = 'Nieprawidłowa wartość rocznika początkowego.';
} elseif ($year_end === false && !empty($_POST['year_end'])) {
    $bledy_walidacji[] = 'Nieprawidłowa wartość rocznika końcowego.';
}


// Jeśli są błędy walidacji, ustaw komunikaty flash i przekieruj z powrotem do formularza
if (!empty($bledy_walidacji)) {
    foreach ($bledy_walidacji as $blad) {
        tn_ustaw_komunikat_flash($blad, 'danger');
    }
    // Opcjonalnie można przekazać wprowadzone dane z powrotem do formularza przez sesję
    $_SESSION['tn_form_data'] = $_POST; // Zapisz dane formularza w sesji
    header('Location: ' . tn_generuj_url('add_vehicle')); // Przekieruj z powrotem do formularza dodawania
    exit;
}

// Wczytaj istniejące dane pojazdów
// Upewnij się, że stała TN_PLIK_POJAZDY jest zdefiniowana w config.php
if (!defined('TN_PLIK_POJAZDY')) {
     error_log("Błąd konfiguracji: Stała TN_PLIK_POJAZDY nie jest zdefiniowana.");
     tn_ustaw_komunikat_flash('Błąd konfiguracji serwera.', 'danger');
     header('Location: ' . tn_generuj_url('vehicles'));
     exit;
}
$pojazdy = tn_laduj_pojazdy(TN_PLIK_POJAZDY);

// Pobierz następne dostępne ID
$next_id = tn_pobierz_nastepne_id_pojazdu($pojazdy);

// Przygotuj dane nowego pojazdu
$nowy_pojadz = [
    'id' => $next_id,
    'make' => $make,
    'model' => $model,
    'version_name' => $version_name,
    'version_code' => $version_code,
    'capacity' => $capacity !== false ? $capacity : null, // Zapisz null, jeśli walidacja się nie powiodła lub pole było puste
    'kw' => $kw !== false ? $kw : null,
    'hp' => $hp !== false ? $hp : null,
    'year_start' => $year_start !== false ? $year_start : null,
    'year_end' => $year_end !== false ? $year_end : null,
    // Można dodać inne pola, np. datę dodania, użytkownika dodającego itp.
    'date_added' => date('Y-m-d H:i:s'),
    'added_by_user_id' => $_SESSION['tn_user_id'] ?? null, // Zapisz ID użytkownika, jeśli dostępna sesja
];

// Dodaj nowy pojazd do tablicy
$pojazdy[] = $nowy_pojadz;

// Zapisz zaktualizowane dane pojazdów
if (tn_zapisz_pojazdy(TN_PLIK_POJAZDY, $pojazdy)) {
    tn_ustaw_komunikat_flash("Pojazd '{$make} {$model}' został dodany.", 'success');
} else {
    tn_ustaw_komunikat_flash("Wystąpił błąd podczas zapisywania pojazdu.", 'danger');
    // Opcjonalnie logowanie błędu zapisu
    error_log("Błąd zapisu pliku pojazdów: " . TN_PLIK_POJAZDY);
}

// Przekieruj użytkownika z powrotem do listy pojazdów
header('Location: ' . tn_generuj_url('vehicles'));
exit;

?>
