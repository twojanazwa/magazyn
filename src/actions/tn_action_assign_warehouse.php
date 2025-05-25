<?php
// src/actions/tn_action_assign_warehouse.php

// Upewnij się, że ten skrypt jest dołączany tylko przez index.php
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    die('Niedozwolony dostęp bezpośredni!');
}

// Sprawdź, czy to POST i właściwa akcja (index.php już to sprawdził, ale dla pewności)
if (!($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_warehouse')) {
    // Ta sytuacja nie powinna wystąpić, jeśli index.php działa poprawnie
    error_log("Ostrzeżenie: tn_action_assign_warehouse.php wywołany niepoprawnie.");
    header("Location: /magazyn"); // Przekieruj na wszelki wypadek
    exit;
}

// --- Walidacja tokenu CSRF (index.php już to zrobił, można pominąć, ale zostawmy dla bezpieczeństwa) ---
if (!tn_waliduj_token_csrf($_POST['tn_csrf_token'] ?? null)) {
    tn_ustaw_komunikat_flash("Nieprawidłowy token bezpieczeństwa (CSRF). Przypisanie anulowane.", 'danger');
    header("Location: /magazyn");
    exit;
}

// Potrzebujemy dostępu do danych globalnych załadowanych w index.php
global $tn_stan_magazynu, $tn_produkty;
$tn_plik_magazyn = TN_PLIK_MAGAZYN;
$tn_plik_produkty = TN_PLIK_PRODUKTY; // Potrzebny do zapisu produktu

// --- Pobranie i walidacja danych z formularza ---
$tn_location_id = filter_input(INPUT_POST, 'location_id', FILTER_SANITIZE_STRING);
$tn_product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$tn_quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$tn_bledy = [];

// Podstawowa walidacja
if (empty($tn_location_id)) $tn_bledy[] = "Brak ID lokalizacji docelowej.";
if ($tn_product_id === false) $tn_bledy[] = "Nie wybrano prawidłowego produktu.";
if ($tn_quantity === false) $tn_bledy[] = "Ilość musi być liczbą całkowitą większą od 0.";

// Sprawdzenie, czy produkt istnieje
$tn_produkt_istnieje = false;
$tn_klucz_produktu_do_aktualizacji = -1; // *** NOWA ZMIENNA *** Klucz produktu w tablicy $tn_produkty
if ($tn_product_id !== false) {
    foreach ($tn_produkty as $tn_klucz_p => $p) { // Dodano $tn_klucz_p
        if (($p['id'] ?? null) === $tn_product_id) {
            $tn_produkt_istnieje = true;
            $tn_klucz_produktu_do_aktualizacji = $tn_klucz_p; // *** Zapisz klucz produktu ***
            break;
        }
    }
    if (!$tn_produkt_istnieje) $tn_bledy[] = "Wybrany produkt (ID: {$tn_product_id}) nie istnieje.";
}

// --- Przetwarzanie (jeśli brak błędów walidacji) ---
if (empty($tn_bledy)) {
    $tn_znaleziono_lokalizacje = false;
    $tn_klucz_lokalizacji = -1;

    // Znajdź lokalizację i sprawdź, czy jest pusta
    foreach ($tn_stan_magazynu as $tn_klucz => $tn_miejsce) {
        if (isset($tn_miejsce['id']) && $tn_miejsce['id'] === $tn_location_id) {
            $tn_znaleziono_lokalizacje = true;
            // Sprawdzaj status zamiast product_id dla pewności
            if (isset($tn_miejsce['status']) && strtolower($tn_miejsce['status']) === 'empty') {
                $tn_klucz_lokalizacji = $tn_klucz;
                break;
            } else {
                 $tn_bledy[] = "Lokalizacja {$tn_location_id} nie jest pusta lub ma nieprawidłowy status. Nie można przypisać produktu.";
                 break;
            }
        }
    }

    if (!$tn_znaleziono_lokalizacje) {
        $tn_bledy[] = "Nie znaleziono lokalizacji magazynowej o ID {$tn_location_id}.";
    } elseif ($tn_klucz_lokalizacji !== -1 && $tn_klucz_produktu_do_aktualizacji !== -1) { // Sprawdź też klucz produktu
        // Lokalizacja znaleziona i pusta, produkt istnieje - dokonaj przypisania

        // 1. Aktualizuj stan magazynu (w tablicy $tn_stan_magazynu)
        $tn_stan_magazynu[$tn_klucz_lokalizacji]['status'] = 'occupied'; // Użyj małych liter dla spójności
        $tn_stan_magazynu[$tn_klucz_lokalizacji]['product_id'] = $tn_product_id;
        $tn_stan_magazynu[$tn_klucz_lokalizacji]['quantity'] = $tn_quantity;

        // *** NOWA CZĘŚĆ: Aktualizuj numer magazynowy w produkcie ***
        $tn_produkty[$tn_klucz_produktu_do_aktualizacji]['warehouse'] = $tn_location_id;

        // --- Zapis do plików ---
        $tn_zapis_magazynu_ok = false;
        $tn_zapis_produktow_ok = false;

        // Zapisz stan magazynu
        if (tn_zapisz_magazyn($tn_plik_magazyn, $tn_stan_magazynu)) {
            $tn_zapis_magazynu_ok = true;
        } else {
            $tn_bledy[] = "Krytyczny błąd zapisu stanu magazynu (warehouse.json).";
            // TODO: Rozważyć próbę przywrócenia poprzedniego stanu $tn_stan_magazynu? (skomplikowane)
        }

        // Zapisz produkty (jeśli zapis magazynu się powiódł)
        if ($tn_zapis_magazynu_ok) {
            if (tn_zapisz_produkty($tn_plik_produkty, $tn_produkty)) {
                 $tn_zapis_produktow_ok = true;
            } else {
                 $tn_bledy[] = "Błąd zapisu danych produktu (products.json) po aktualizacji numeru magazynowego.";
                 // TODO: Rozważyć próbę przywrócenia $tn_stan_magazynu i $tn_produkty?
            }
        }

        // Ustaw komunikat na podstawie wyników zapisu
        if ($tn_zapis_magazynu_ok && $tn_zapis_produktow_ok) {
            tn_ustaw_komunikat_flash("Produkt (ID: {$tn_product_id}) został przypisany do lokalizacji {$tn_location_id} (ilość: {$tn_quantity}). Numer magazynowy produktu został zaktualizowany.", 'success');
        } else {
             // Błędy zostały już dodane do $tn_bledy
             tn_ustaw_komunikat_flash("Wystąpiły błędy podczas zapisu danych: " . implode(' ', $tn_bledy), 'danger');
        }

    } elseif ($tn_klucz_produktu_do_aktualizacji === -1 && empty($tn_bledy)) {
         // Ten błąd nie powinien wystąpić jeśli walidacja produktu działa, ale na wszelki wypadek
         $tn_bledy[] = "Nie znaleziono klucza produktu do aktualizacji (wewnętrzny błąd).";
    }
}

// --- Przekierowanie ---
if (!empty($tn_bledy)) {
    // Jeśli były jakiekolwiek błędy (walidacji lub zapisu), ustaw komunikat
    tn_ustaw_komunikat_flash("Błąd przypisywania produktu: " . implode(' ', $tn_bledy), 'danger');
}

// Zawsze wracaj do widoku magazynu po próbie wykonania akcji
header("Location: /magazyn");
exit;

?>