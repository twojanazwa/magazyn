<?php
// Plik: src/actions/tn_action_save_product.php
// Opis: Skrypt akcji do zapisywania (dodawania/edycji) danych produktu.

// Sprawdź, czy akcja jest wywołana metodą POST i czy wymagane pola istnieją
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['akcja']) || $_POST['akcja'] !== 'save_product') {
    tn_ustaw_komunikat_flash('Nieprawidłowe żądanie.', 'danger');
    header('Location: ' . tn_generuj_url('dashboard'));
    exit;
}

// Wymagaj zalogowania
if (!isset($_SESSION['tn_user_id'])) {
    tn_ustaw_komunikat_flash('Aby wykonać tę akcję, musisz być zalogowany.', 'warning');
    header('Location: ' . tn_generuj_url('login_page'));
    exit;
}

// Walidacja tokenu CSRF - wykonywana już w index.php, ale dobrze mieć też tutaj na wszelki wypadek
// if (!tn_waliduj_token_csrf($_POST['csrf_token'] ?? '')) {
//     tn_ustaw_komunikat_flash('Błąd CSRF.', 'danger');
//     header('Location: ' . tn_generuj_url('produkty')); // Przekieruj w bezpieczne miejsce
//     exit;
// }


// --- PRZETWARZANIE DANYCH Z FORMULARZA ---
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT); // ID tylko w trybie edycji
$nazwa = trim($_POST['nazwa'] ?? '');
$numer_katalogowy = trim($_POST['numer_katalogowy'] ?? '');
$numery_zamiennikow = trim($_POST['numery_zamiennikow'] ?? '');
$numery_oryginalu = trim($_POST['numery_oryginalu'] ?? '');
$producent = trim($_POST['producent'] ?? '');
$ilosc = filter_var($_POST['ilosc'] ?? 0, FILTER_VALIDATE_INT);
$cena_netto = filter_var($_POST['cena_netto'] ?? 0.0, FILTER_VALIDATE_FLOAT);
$cena_brutto = filter_var($_POST['cena_brutto'] ?? 0.0, FILTER_VALIDATE_FLOAT);
$vat = filter_var($_POST['vat'] ?? 0, FILTER_VALIDATE_INT);
$jednostka_miary = trim($_POST['jednostka_miary'] ?? 'szt');
$opis = trim($_POST['opis'] ?? '');
$pasuje_do_poj = trim($_POST['pasuje_do_poj'] ?? '');
$parametry = trim($_POST['parametry'] ?? '');
$kategoria = trim($_POST['kategoria'] ?? '');
$ean = trim($_POST['ean'] ?? '');
$waga = filter_var($_POST['waga'] ?? null, FILTER_VALIDATE_FLOAT);
$wymiary = trim($_POST['wymiary'] ?? '');
$aktywny = isset($_POST['aktywny']); // Checkbox zwraca '1' jeśli zaznaczony, inaczej brak go w POST
$miejsce_magazynowe_nowe = trim($_POST['miejsce_magazynowe'] ?? '');

// Walidacja podstawowych danych
if (empty($nazwa) || $ilosc === false || $ilosc < 0 || $cena_netto === false || $cena_netto < 0 || $cena_brutto === false || $cena_brutto < 0 || $vat === false || $vat < 0) {
    tn_ustaw_komunikat_flash('Błąd walidacji danych produktu. Proszę wypełnić wymagane pola poprawnymi wartościami.', 'danger');
    // Przekierowanie z powrotem na formularz z zachowaniem wprowadzonych danych (opcjonalnie, wymaga przekazania danych POST/GET)
    header('Location: ' . tn_generuj_url($id ? 'produkty' : 'produkty_nowy', ['id' => $id]));
    exit;
}

// Wczytaj istniejące dane produktów i magazynu
$produkty = tn_laduj_dane_json(TN_PLIK_PRODUKTY) ?? [];
$magazyn = tn_laduj_dane_json(TN_PLIK_MAGAZYN) ?? [];

$is_edit_mode = ($id !== false && $id !== null && $id > 0);
$index_produktu = -1;
$stary_produkt = null;
$stare_miejsce_magazynowe = null;

if ($is_edit_mode) {
    // Znajdź produkt w istniejącej liście
    foreach ($produkty as $key => $prod) {
        if (($prod['id'] ?? null) == $id) {
            $index_produktu = $key;
            $stary_produkt = $prod;
            $stare_miejsce_magazynowe = $stary_produkt['miejsce_magazynowe'] ?? null;
            break;
        }
    }

    if ($index_produktu === -1) {
        tn_ustaw_komunikat_flash('Produkt o podanym ID nie został znaleziony.', 'danger');
        header('Location: ' . tn_generuj_url('produkty'));
        exit;
    }
} else {
    // Nowy produkt - wygeneruj ID
    $id = uniqid('prod_', true); // Bardziej unikalne ID niż simple counter
    $index_produktu = count($produkty); // Dodaj na koniec listy
}

// --- OBSŁUGA ZDJĘĆ ---
$katalog_uploadu_produktow = TN_SCIEZKA_UPLOAD . 'produkty/';
if (!is_dir($katalog_uploadu_produktow)) {
    mkdir($katalog_uploadu_produktow, 0755, true);
}

$istniejace_zdjecia_do_zachowania = $_POST['keep_photos'] ?? [];
$zdjecia_do_usuniecia = $_POST['delete_photos'] ?? [];
$aktualne_zdjecia_produktu = $stary_produkt['zdjecia'] ?? []; // Zdjęcia przed zmianami

$nowe_zdjecia = []; // Tablica na ścieżki do nowo przesłanych zdjęć

// Przetwarzanie przesłanych plików
if (!empty($_FILES['zdjecia_upload']['name'][0])) { // Sprawdź, czy jakieś pliki zostały przesłane
    $suma_istniejacych_i_nowych = count($istniejace_zdjecia_do_zachowania) + count($_FILES['zdjecia_upload']['name']);
    if ($suma_istniejacych_i_nowych > 5) {
        tn_ustaw_komunikat_flash('Przekroczono maksymalną liczbę 5 zdjęć dla produktu.', 'warning');
        // Tutaj możesz zdecydować, czy przerwać zapis, czy zapisać tylko dozwoloną liczbę zdjęć
        // Na razie przerwiemy zapis, aby uniknąć problemów
        header('Location: ' . tn_generuj_url('produkty_form', ['id' => $id]));
        exit;
    }

    foreach ($_FILES['zdjecia_upload']['name'] as $key => $name) {
        if ($_FILES['zdjecia_upload']['error'][$key] == UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['zdjecia_upload']['tmp_name'][$key];
            $file_extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $dozwolone_rozszerzenia = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($file_extension, $dozwolone_rozszerzenia)) {
                // Generuj unikalną nazwę pliku, np. z ID produktu i unikalnego identyfikatora
                $nazwa_pliku = $id . '_' . uniqid() . '.' . $file_extension;
                $sciezka_zapisu = $katalog_uploadu_produktow . $nazwa_pliku;

                if (move_uploaded_file($tmp_name, $sciezka_zapisu)) {
                    $nowe_zdjecia[] = $sciezka_zapisu; // Zapisz pełną ścieżkę do tablicy
                } else {
                    tn_ustaw_komunikat_flash('Wystąpił błąd podczas przesyłania pliku: ' . htmlspecialchars($name), 'warning');
                }
            } else {
                 tn_ustaw_komunikat_flash('Niedozwolony typ pliku: ' . htmlspecialchars($name), 'warning');
            }
        } elseif ($_FILES['zdjecia_upload']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
             tn_ustaw_komunikat_flash('Błąd przesyłania pliku: ' . $_FILES['zdjecia_upload']['error'][$key], 'danger');
        }
    }
}

// Określamy, które zdjęcia ostatecznie zostaną przypisane do produktu
// Zaczynamy od istniejących zdjęć, które nie zostały oznaczone do usunięcia
$ostateczna_lista_zdjec = [];
if ($is_edit_mode && !empty($aktualne_zdjecia_produktu)) {
    foreach ($aktualne_zdjecia_produktu as $sciezka_istniejacego) {
        // Użyjemy basename do porównania z nazwami plików w `keep_photos` i `delete_photos`
        $nazwa_istniejacego_pliku = basename($sciezka_istniejacego);
        // Sprawdź, czy zdjęcie nie zostało zaznaczone do usunięcia
        if (!in_array($nazwa_istniejacego_pliku, $zdjecia_do_usuniecia)) {
            $ostateczna_lista_zdjec[] = $sciezka_istniejacego; // Dodaj pełną ścieżkę
        } else {
            // Jeśli zdjęcie jest do usunięcia, fizycznie usuń plik
            if (file_exists($sciezka_istniejacego)) {
                @unlink($sciezka_istniejacego); // Użyj @ żeby stłumić błędy, jeśli plik już nie istnieje
            }
        }
    }
}

// Dodaj nowo przesłane zdjęcia do ostatecznej listy
$ostateczna_lista_zdjec = array_merge($ostateczna_lista_zdjec, $nowe_zdjecia);

// Ogranicz do maksymalnie 5 zdjęć, na wypadek gdyby JS zawiódł
$ostateczna_lista_zdjec = array_slice($ostateczna_lista_zdjec, 0, 5);

// Określ zdjęcie główne
$zdjecie_glowne = null;
$wybrane_zdjecie_glowne = $_POST['zdjecie_glowne'] ?? null;

if ($wybrane_zdjecie_glowne) {
    // Znajdź pełną ścieżkę dla wybranej nazwy pliku zdjęcia głównego
    foreach ($ostateczna_lista_zdjec as $sciezka_zdjecia) {
        if (basename($sciezka_zdjecia) === $wybrane_zdjecie_glowne) {
            $zdjecie_glowne = basename($sciezka_zdjecia); // Zapisz tylko nazwę pliku głównego
            break;
        }
    }
}

// Jeśli po wszystkich operacjach nadal nie ma zdjęcia głównego, a są jakieś zdjęcia, ustaw pierwsze jako główne
if (empty($zdjecie_glowne) && !empty($ostateczna_lista_zdjec)) {
     $zdjecie_glowne = basename($ostateczna_lista_zdjec[0]);
}


// --- AKTUALIZACJA DANYCH PRODUKTU ---
$dane_produktu = [
    'id'                  => $id,
    'nazwa'               => $nazwa,
    'numer_katalogowy'    => $numer_katalogowy, // Nowe pole
    'numery_zamiennikow'  => array_map('trim', explode(',', $numery_zamiennikow)), // Przetwórz na tablicę
    'numery_oryginalu'    => array_map('trim', explode(',', $numery_oryginalu)),   // Przetwórz na tablicę
    'producent'           => $producent,        // Nowe pole
    'ilosc'               => $ilosc,
    'cena_netto'          => $cena_netto,
    'cena_brutto'         => $cena_brutto,
    'vat'                 => $vat,
    'jednostka_miary'     => $jednostka_miary,   // Nowe pole
    'opis'                => $opis,
    'pasuje_do_poj'       => array_map('trim', explode("\n", $pasuje_do_poj)), // Podziel na linie, usuń białe znaki
    'parametry'           => array_map('trim', explode("\n", $parametry)), // Podziel na linie, usuń białe znaki
    'kategoria'           => $kategoria,
    'ean'                 => $ean,
    'waga'                => $waga,
    'wymiary'             => $wymiary,
    'aktywny'             => $aktywny,
    'miejsce_magazynowe'  => $miejsce_magazynowe_nowe,
    'zdjecia'             => $ostateczna_lista_zdjec, // Zapisz pełne ścieżki zdjęć
    'zdjecie_glowne'      => $zdjecie_glowne,     // Zapisz nazwę pliku zdjęcia głównego
    'data_dodania'        => $stary_produkt['data_dodania'] ?? date('Y-m-d H:i:s'),
    'data_modyfikacji'    => date('Y-m-d H:i:s')
];

// Usuń puste wpisy z tablic zamienników, oryginału, pasuje_do_poj i parametrów
$dane_produktu['numery_zamiennikow'] = array_filter($dane_produktu['numery_zamiennikow']);
$dane_produktu['numery_oryginalu'] = array_filter($dane_produktu['numery_oryginalu']);
$dane_produktu['pasuje_do_poj'] = array_filter($dane_produktu['pasuje_do_poj']);
$dane_produktu['parametry'] = array_filter($dane_produktu['parametry']);


// Aktualizuj listę produktów
if ($is_edit_mode) {
    $produkty[$index_produktu] = $dane_produktu;
    $komunikat_sukces = 'Produkt zaktualizowany pomyślnie!';
} else {
    $produkty[] = $dane_produktu;
    $komunikat_sukces = 'Nowy produkt dodany pomyślnie!';
}

// --- AKTUALIZACJA STANU MAGAZYNU ---
// Rozpocznij transakcję (symulacja dla plików JSON)
// Zapis danych produktów i magazynu powinien być traktowany jako jedna operacja.
// W przypadku plików JSON, nie mamy prawdziwych transakcji.
// Najprostszym sposobem na "transakcyjność" jest:
// 1. Zrobić kopię zapasową obecnych plików JSON.
// 2. Zmodyfikować dane w pamięci.
// 3. Zapisać zmodyfikowane dane do plików.
// 4. W przypadku błędu podczas zapisu, przywrócić dane z kopii zapasowej.

// Tutaj pomijamy pełną implementację transakcji na plikach dla uproszczenia,
// ale w rzeczywistej aplikacji jest to kluczowe dla integralności danych.

$blad_zapisu_magazynu = false;
$blad_zapisu_produktow = false;

// Jeśli przypisano nowe miejsce magazynowe lub zmieniono istniejące
if ($miejsce_magazynowe_nowe !== ($stary_produkt['miejsce_magazynowe'] ?? null)) {
    // Jeśli istniało stare miejsce, zwolnij je
    if (!empty($stare_miejsce_magazynowe) && isset($magazyn[$stare_miejsce_magazynowe])) {
        $magazyn[$stare_miejsce_magazynowe]['status'] = 'wolne';
        $magazyn[$stare_miejsce_magazynowe]['produkt_id'] = null;
        $magazyn[$stare_miejsce_magazynowe]['ilosc'] = 0; // Zerujemy ilość
    }

    // Jeśli wybrano nowe miejsce, zajmij je
    if (!empty($miejsce_magazynowe_nowe)) {
        if (isset($magazyn[$miejsce_magazynowe_nowe])) {
            // Sprawdź, czy miejsce nie zostało zajęte przez inny produkt w międzyczasie
            // (choć formularz powinien ograniczać wybór)
            if (($magazyn[$miejsce_magazynowe_nowe]['status'] ?? '') === 'wolne' || ($magazyn[$miejsce_magazynowe_nowe]['produkt_id'] ?? null) == $id) {
                 $magazyn[$miejsce_magazynowe_nowe]['status'] = 'zajete';
                 $magazyn[$miejsce_magazynowe_nowe]['produkt_id'] = $id;
                 $magazyn[$miejsce_magazynowe_nowe]['ilosc'] = $ilosc; // Zapisz aktualną ilość produktu w tym miejscu
            } else {
                 // To miejsce jest już zajęte przez inny produkt
                 tn_ustaw_komunikat_flash('Wybrane miejsce magazynowe (' . htmlspecialchars($miejsce_magazynowe_nowe) . ') zostało w międzyczasie zajęte. Proszę wybrać inne.', 'warning');
                 // W tym przypadku możemy przywrócić stare miejsce lub zostawić produkt bez przypisanego miejsca
                 // Najbezpieczniej usunąć nowe przypisanie i poinformować użytkownika
                 $dane_produktu['miejsce_magazynowe'] = $stare_miejsce_magazynowe; // Przywróć stare miejsce w danych produktu
                 // Nie zmieniamy danych magazynu, ponieważ nie udało się przypisać nowego miejsca
            }
        } else {
             // Wybrane miejsce magazynowe nie istnieje - błąd danych lub konfiguracji
             tn_ustaw_komunikat_flash('Wybrane miejsce magazynowe (' . htmlspecialchars($miejsce_magazynowe_nowe) . ') nie istnieje w bazie danych magazynu.', 'danger');
             // Ustawiamy produkt bez przypisanego miejsca
              $dane_produktu['miejsce_magazynowe'] = null;
              // Nie zmieniamy danych magazynu
        }
    }
} else {
    // Miejsce magazynowe nie zostało zmienione, ale mogła zmienić się ilość produktu
    // Jeśli produkt ma przypisane miejsce, zaktualizuj ilość w magazynie
    if (!empty($miejsce_magazynowe_nowe) && isset($magazyn[$miejsce_magazynowe_nowe])) {
         // Sprawdź, czy przypisane miejsce nadal należy do tego produktu
         if (($magazyn[$miejsce_magazynowe_nowe]['produkt_id'] ?? null) == $id) {
             $magazyn[$miejsce_magazynowe_nowe]['ilosc'] = $ilosc;
         } else {
             // To miejsce jest teraz zajęte przez inny produkt - błąd danych!
             error_log("Błąd integralności danych: Produkt ID {$id} ma przypisane miejsce {$miejsce_magazynowe_nowe}, ale w magazynie miejsce to jest zajęte przez produkt ID {$magazyn[$miejsce_magazynowe_nowe]['produkt_id'] ?? 'brak'}");
             tn_ustaw_komunikat_flash('Błąd integralności danych magazynu. Przypisane miejsce jest zajęte przez inny produkt. Usunięto przypisanie miejsca dla tego produktu.', 'danger');
              $dane_produktu['miejsce_magazynowe'] = null; // Usuń błędne przypisanie
              // Nie zmieniamy danych magazynu, ponieważ miejsce jest zajęte przez inny produkt
         }
    }
}

// Zapisz zaktualizowane dane magazynu (po operacji na produktach)
if (!tn_zapisz_dane_json(TN_PLIK_MAGAZYN, $magazyn)) {
     tn_ustaw_komunikat_flash('Błąd zapisu danych magazynu.', 'danger');
     $blad_zapisu_magazynu = true;
     // W przypadku błędu zapisu magazynu, można by próbować przywrócić stary stan lub zaznaczyć produkt jako wymagający uwagi
}


// Zapisz zaktualizowane dane produktów
if (!tn_zapisz_dane_json(TN_PLIK_PRODUKTY, $produkty)) {
    tn_ustaw_komunikat_flash('Błąd zapisu danych produktów.', 'danger');
    $blad_zapisu_produktow = true;
     // W przypadku błędu zapisu produktów, można by próbować przywrócić stary stan danych produktów i magazynu
}


// --- PRZEKIEROWANIE PO ZAPISIE ---
if (!$blad_zapisu_produktow && !$blad_zapisu_magazynu) {
    tn_ustaw_komunikat_flash($komunikat_sukces, 'success');
    // Przekieruj na podgląd produktu, jeśli edytowano, lub na listę produktów, jeśli dodano nowy
    if ($is_edit_mode) {
        header('Location: ' . tn_generuj_url('product_preview', ['id' => $id]));
    } else {
        header('Location: ' . tn_generuj_url('produkty'));
    }
    exit;
} else {
    // Jeśli wystąpił błąd zapisu, przekieruj z powrotem na formularz (lub inną stronę błędu)
     header('Location: ' . tn_generuj_url($is_edit_mode ? 'produkty_form' : 'produkty_nowy', ['id' => ($is_edit_mode ? $id : null)]));
     exit;
}

?>