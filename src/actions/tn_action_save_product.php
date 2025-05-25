<?php
// src/actions/tn_action_save_product.php

// Podstawowe zabezpieczenia i sprawdzenie metody/akcji
if (!($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save')) {
    error_log("Ostrzeżenie: tn_action_save_product.php wywołany niepoprawnie.");
    $redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('products') : 'index.php?page=products';
    header("Location: " . $redirect_url);
    exit;
}

// Walidacja tokenu CSRF
if (!function_exists('tn_waliduj_token_csrf') || !tn_waliduj_token_csrf($_POST['tn_csrf_token'] ?? null)) {
    if(function_exists('tn_ustaw_komunikat_flash')) tn_ustaw_komunikat_flash("Nieprawidłowy token bezpieczeństwa (CSRF). Zapis produktu anulowany.", 'danger');
    $redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('products') : 'index.php?page=products';
    header("Location: " . $redirect_url);
    exit;
}

// Udostępnij zmienne globalne (powinny być załadowane w index.php)
global $tn_produkty, $tn_stan_magazynu, $tn_ustawienia_globalne;
$tn_plik_produkty = TN_PLIK_PRODUKTY;
$tn_plik_magazyn = TN_PLIK_MAGAZYN; // Potrzebny do aktualizacji lokalizacji przy dodawaniu

// --- Walidacja i sanityzacja danych produktu ---
$tn_bledy = [];
$tn_id = isset($_POST['id']) && !empty($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null; // ID tylko przy edycji
$tn_nazwa = trim(htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'));
$tn_producent = trim(htmlspecialchars($_POST['producent'] ?? '', ENT_QUOTES, 'UTF-8'));
$tn_kategoria = trim(htmlspecialchars($_POST['category'] ?? '', ENT_QUOTES, 'UTF-8'));
$tn_pojazd = trim(htmlspecialchars($_POST['vehicle'] ?? '', ENT_QUOTES, 'UTF-8'));

// Zezwól na HTML w polach opisowych - UWAGA na XSS!
$tn_opis = trim($_POST['desc'] ?? '');
$tn_specyfikacja = trim($_POST['spec'] ?? '');
$tn_parametry = trim($_POST['params'] ?? '');

// Odczyt i sanityzacja numeru katalogowego
$tn_numer_katalogowy = trim(htmlspecialchars($_POST['tn_numer_katalogowy'] ?? '', ENT_QUOTES, 'UTF-8'));
// Odczyt i sanityzacja jednostki miary
$tn_jednostka_miary = trim(htmlspecialchars($_POST['tn_jednostka_miary'] ?? '', ENT_QUOTES, 'UTF-8'));

$tn_cena = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
$tn_wysylka = filter_var($_POST['shipping'] ?? null, FILTER_VALIDATE_FLOAT);
$tn_magazyn_stan = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);

// Pobranie wybranej lokalizacji z selecta
$tn_wybrana_lokalizacja_id = isset($_POST['tn_assign_location_id']) ? trim($_POST['tn_assign_location_id']) : '';
$tn_oryginalny_warehouse = isset($_POST['original_warehouse']) ? trim($_POST['original_warehouse']) : ''; // Dla trybu edycji

// Podstawowa walidacja wymaganych pól
if (empty($tn_nazwa)) $tn_bledy[] = "Produkty: Nazwa jest wymagana.";
if (empty($tn_producent)) $tn_bledy[] = "Produkty: Producent jest wymagany.";
if ($tn_cena === null || $tn_cena < 0) $tn_bledy[] = "Produkty: Cena jest wymagana.";
if ($tn_wysylka === null || $tn_wysylka < 0) $tn_bledy[] = "Produkty: Koszt wysyłki jest wymagany.";
if ($tn_magazyn_stan === null || $tn_magazyn_stan < 0) $tn_bledy[] = "Produkty: Ilość jest wymagana.";

// --- Obsługa obrazka (tylko z pliku) ---
$tn_obrazek = ''; // Domyślnie brak obrazka
$tn_stary_obrazek = '';
$tn_obrazek_zaktualizowany = false;

// Znajdź stary obrazek i ew. stary warehouse przy edycji
if ($tn_id !== null) {
    foreach ($tn_produkty as $tn_p) {
        if (($tn_p['id'] ?? null) == $tn_id) {
            $tn_stary_obrazek = $tn_p['image'] ?? '';
            if(empty($tn_oryginalny_warehouse)) { $tn_oryginalny_warehouse = $tn_p['warehouse'] ?? ''; }
            break;
        }
    }
}

// Obsługa wgrywanego pliku
if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
    $tn_plik_tmp_sciezka = $_FILES['image_file']['tmp_name'];
    $tn_nazwa_pliku = basename($_FILES['image_file']['name']);
    $tn_plik_rozmiar = $_FILES['image_file']['size'];
    $tn_plik_rozszerzenie = strtolower(pathinfo($tn_nazwa_pliku, PATHINFO_EXTENSION));
    $tn_finfo = finfo_open(FILEINFO_MIME_TYPE);
    $tn_prawdziwy_plik_typ = finfo_file($tn_finfo, $tn_plik_tmp_sciezka);
    finfo_close($tn_finfo);
    $tn_dozwolone_typy_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $tn_dozwolone_rozszerzenia = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $tn_maksymalny_rozmiar_pliku = 2 * 1024 * 1024; // 2MB

    if (!in_array($tn_prawdziwy_plik_typ, $tn_dozwolone_typy_mime) || !in_array($tn_plik_rozszerzenie, $tn_dozwolone_rozszerzenia)) {
        $tn_bledy[] = "Niedozwolony typ pliku obrazka (JPG, PNG, GIF, WEBP).";
    } elseif ($tn_plik_rozmiar > $tn_maksymalny_rozmiar_pliku) {
        $tn_bledy[] = "Plik obrazka jest za duży (max 2MB).";
    } else {
        $tn_nowa_nazwa_pliku = 'prod_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $tn_plik_rozszerzenie;
        $tn_sciezka_docelowa = TN_SCIEZKA_UPLOAD . $tn_nowa_nazwa_pliku;
        if (move_uploaded_file($tn_plik_tmp_sciezka, $tn_sciezka_docelowa)) {
            $tn_obrazek = $tn_nowa_nazwa_pliku; // Ustaw nazwę nowego pliku
            $tn_obrazek_zaktualizowany = true;
            // Usuń stary plik obrazka, jeśli istniał i nie był URL-em
            if (!empty($tn_stary_obrazek) && !filter_var($tn_stary_obrazek, FILTER_VALIDATE_URL) && file_exists(TN_SCIEZKA_UPLOAD . $tn_stary_obrazek)) {
                @unlink(TN_SCIEZKA_UPLOAD . $tn_stary_obrazek);
            }
        } else {
            $tn_bledy[] = "Błąd podczas przenoszenia przesłanego pliku obrazka.";
            $tn_obrazek = $tn_stary_obrazek; // W razie błędu zachowaj stary
        }
    }
} else {
    // Nie wgrano nowego pliku - zachowaj stary obrazek
    $tn_obrazek = $tn_stary_obrazek;
    $tn_obrazek_zaktualizowany = false; // Nie usuwaj starego obrazka, jeśli nie wgrano nowego
}
// --- Koniec obsługi obrazka ---

// --- Zapis danych, jeśli nie ma błędów walidacji ---
if (empty($tn_bledy)) {
    // Ustal domyślny/wybrany numer magazynowy
    $tn_docelowy_numer_magazynowy = $tn_ustawienia_globalne['domyslny_magazyn'] ?? 'MA01-BULK';
    $tn_aktualizuj_magazyn_flag = false; // Czy aktualizować warehouse.json?
    $tn_nowe_id_produktu = null; // ID dla nowego produktu

    if ($tn_id === null && !empty($tn_wybrana_lokalizacja_id)) { // Dodawanie nowego ORAZ wybrano lokalizację
         $tn_docelowy_numer_magazynowy = $tn_wybrana_lokalizacja_id;
         $tn_aktualizuj_magazyn_flag = true;
    } elseif ($tn_id !== null) { // Edycja - zachowaj oryginalny numer magazynowy
        $tn_docelowy_numer_magazynowy = $tn_oryginalny_warehouse;
    }
    // Jeśli dodajemy i NIE wybrano lokalizacji, zostaje domyślny

    // Przygotuj tablicę danych produktu
    $tn_dane_produktu = [
        'name' => $tn_nazwa, 'producent' => $tn_producent, 'tn_numer_katalogowy' => $tn_numer_katalogowy, // Dodane pole
        'category' => $tn_kategoria, 'desc' => $tn_opis, 'spec' => $tn_specyfikacja, 'params' => $tn_parametry,
        'vehicle' => $tn_pojazd, 'price' => $tn_cena, 'shipping' => $tn_wysylka, 'stock' => $tn_magazyn_stan,
        'tn_jednostka_miary' => $tn_jednostka_miary, // Dodane pole
        'warehouse' => $tn_docelowy_numer_magazynowy, 'image' => $tn_obrazek
        // ID jest dodawane/zachowywane poniżej
    ];

    $tn_typ_akcji = ''; $tn_zapis_produktu_ok = false; $tn_zapis_magazynu_ok = true; // Załóż, że ok, chyba że wystąpi błąd

    // --- Dodawanie nowego produktu ---
    if ($tn_id === null) {
        // Generuj nowe ID
        $tn_nowe_id_produktu = 1; if (!empty($tn_produkty)) { $tn_id_produktow = array_column($tn_produkty, 'id'); $tn_id_numeryczne = array_filter($tn_id_produktow, 'is_numeric'); if (!empty($tn_id_numeryczne)) $tn_nowe_id_produktu = max($tn_id_numeryczne) + 1; }
        $tn_dane_produktu['id'] = $tn_nowe_id_produktu;

        // Aktualizacja magazynu, jeśli wybrano lokalizację
        if ($tn_aktualizuj_magazyn_flag && !empty($tn_wybrana_lokalizacja_id)) {
             $tn_klucz_lokalizacji_do_aktualizacji = -1;
             foreach($tn_stan_magazynu as $key => $loc) {
                  if(isset($loc['id']) && $loc['id'] === $tn_wybrana_lokalizacja_id) {
                       if(isset($loc['status']) && strtolower($loc['status']) === 'empty') { $tn_klucz_lokalizacji_do_aktualizacji = $key; break; }
                       else { $tn_bledy[] = "Wybrana lokalizacja {$tn_wybrana_lokalizacja_id} nie jest już pusta!"; $tn_aktualizuj_magazyn_flag = false; $tn_dane_produktu['warehouse'] = $tn_ustawienia_globalne['domyslny_magazyn'] ?? 'NIEPRZYPISANY'; break; }
                  }
             }
             if ($tn_klucz_lokalizacji_do_aktualizacji !== -1) {
                 $tn_stan_magazynu[$tn_klucz_lokalizacji_do_aktualizacji]['status'] = 'occupied';
                 $tn_stan_magazynu[$tn_klucz_lokalizacji_do_aktualizacji]['product_id'] = $tn_nowe_id_produktu;
                 $tn_stan_magazynu[$tn_klucz_lokalizacji_do_aktualizacji]['quantity'] = $tn_magazyn_stan; // Użyj stanu produktu
                 if (!tn_zapisz_magazyn($tn_plik_magazyn, $tn_stan_magazynu)) { $tn_bledy[] = "Błąd zapisu stanu magazynu dla lok. {$tn_wybrana_lokalizacja_id}."; $tn_zapis_magazynu_ok = false; $tn_dane_produktu['warehouse'] = $tn_ustawienia_globalne['domyslny_magazyn'] ?? 'NIEPRZYPISANY'; }
             } elseif(empty($tn_bledy)) { $tn_bledy[] = "Nie znaleziono lok. {$tn_wybrana_lokalizacja_id} w magazynie."; $tn_dane_produktu['warehouse'] = $tn_ustawienia_globalne['domyslny_magazyn'] ?? 'NIEPRZYPISANY'; $tn_zapis_magazynu_ok = false; }
        }

        // Dodaj produkt do tablicy i zapisz
        $tn_produkty[] = $tn_dane_produktu; $tn_typ_akcji = 'dodany';
        $tn_zapis_produktu_ok = tn_zapisz_produkty($tn_plik_produkty, $tn_produkty);
    }
    // --- Edycja istniejącego produktu ---
    else {
        $tn_znaleziono_klucz = false;
        foreach ($tn_produkty as $tn_klucz => &$tn_p) { // Użyj referencji
            if (($tn_p['id'] ?? null) == $tn_id) {
                $tn_dane_produktu['id'] = $tn_id; // Zachowaj ID
                $tn_p = $tn_dane_produktu;      // Nadpisz dane produktu
                $tn_znaleziono_klucz = true;
                break;
            }
        }
        unset($tn_p); // Ważne, aby usunąć referencję po pętli

        if ($tn_znaleziono_klucz) {
            $tn_typ_akcji = 'zaktualizowany';
            // Zapisz całą tablicę produktów
            $tn_zapis_produktu_ok = tn_zapisz_produkty($tn_plik_produkty, $tn_produkty);
        } else { $tn_bledy[] = "Edycja nieistniejącego produktu ID: ".htmlspecialchars((string)$tn_id); }
    }

    // Sprawdź wynik zapisu produktu
    if (!$tn_zapis_produktu_ok && empty($tn_bledy)) { $tn_bledy[] = "Produkty: Błąd zapisu pliku."; }

    // Ustaw komunikaty flash
    if ($tn_zapis_produktu_ok && $tn_zapis_magazynu_ok && empty($tn_bledy)) {
        $tn_komunikat = "Produkt został pomyślnie {$tn_typ_akcji}.";
        if ($tn_id === null && $tn_aktualizuj_magazyn_flag && $tn_zapis_magazynu_ok) { $tn_komunikat .= " Został przypisany do lok. {$tn_wybrana_lokalizacja_id}."; }
        tn_ustaw_komunikat_flash($tn_komunikat, 'success');
    } elseif (empty($tn_bledy)) { tn_ustaw_komunikat_flash("Produkty: Wystąpił błąd podczas zapisu danych.", 'danger'); }
    // Jeśli były błędy walidacji, zostaną dodane poniżej

} // Koniec if (empty($tn_bledy))

// --- Przekierowanie ---
if (!empty($tn_bledy)) {
    tn_ustaw_komunikat_flash("Popraw błędy: " . implode(' ', $tn_bledy), 'danger');
}

// Zawsze wracaj do listy produktów
$redirect_url = function_exists('tn_generuj_url') ? tn_generuj_url('products') : 'index.php?page=products';
// Można dodać parametry sortowania/strony, jeśli chcemy wrócić dokładnie tam, skąd przyszliśmy
header("Location: " . $redirect_url);
exit;
?>