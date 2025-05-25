<?php
// src/actions/tn_action_import_products.php

// Ta akcja jest wywoływana przez POST z modala importu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_products') {

    // --- Walidacja tokenu CSRF ---
    if (!tn_waliduj_token_csrf($_POST['tn_csrf_token'] ?? null)) {
        tn_ustaw_komunikat_flash("Nieprawidłowy token bezpieczeństwa (CSRF). Import anulowany.", 'danger');
        header("Location: index.php");
        exit;
    }

    // Potrzebujemy dostępu do danych i ustawień
    global $tn_produkty, $tn_ustawienia_globalne;
    $tn_plik_produkty = TN_PLIK_PRODUKTY;
    $tn_blad_importu_msg = ''; // Zmieniono nazwę zmiennej błędu

    // --- Sprawdzenie i walidacja przesłanego pliku ---
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        $tn_blad_importu_msg = match ($_FILES['import_file']['error'] ?? UPLOAD_ERR_NO_FILE) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Przesłany plik jest za duży.',
            UPLOAD_ERR_PARTIAL => 'Plik został przesłany tylko częściowo.',
            UPLOAD_ERR_NO_FILE => 'Nie wybrano żadnego pliku do importu.',
            default => 'Wystąpił nieznany błąd podczas przesyłania pliku.',
        };
    } else {
        $tn_plik_tmp_sciezka = $_FILES['import_file']['tmp_name'];
        $tn_nazwa_pliku_oryg = basename($_FILES['import_file']['name']); // Bezpieczeństwo
        $tn_plik_rozmiar = $_FILES['import_file']['size'];
        $tn_plik_rozszerzenie = strtolower(pathinfo($tn_nazwa_pliku_oryg, PATHINFO_EXTENSION));

        // Dodatkowa weryfikacja typu MIME
        $tn_finfo = finfo_open(FILEINFO_MIME_TYPE);
        $tn_prawdziwy_plik_typ = finfo_file($tn_finfo, $tn_plik_tmp_sciezka);
        finfo_close($tn_finfo);

        $tn_maksymalny_rozmiar_pliku = 5 * 1024 * 1024; // 5MB
        $tn_dozwolone_typy_mime = ['application/json'];
        $tn_dozwolone_rozszerzenia = ['json'];

        if (!in_array($tn_prawdziwy_plik_typ, $tn_dozwolone_typy_mime) || !in_array($tn_plik_rozszerzenie, $tn_dozwolone_rozszerzenia)) {
            $tn_blad_importu_msg = 'Nieprawidłowy typ pliku. Dozwolony jest tylko format JSON.';
        } elseif ($tn_plik_rozmiar > $tn_maksymalny_rozmiar_pliku) {
            $tn_blad_importu_msg = 'Plik jest za duży (maksymalnie 5MB).';
        } else {
            // --- Odczyt i przetwarzanie pliku JSON ---
            $tn_zawartosc_pliku = file_get_contents($tn_plik_tmp_sciezka);
            if ($tn_zawartosc_pliku === false) {
                $tn_blad_importu_msg = 'Nie można odczytać zawartości przesłanego pliku.';
            } else {
                $tn_importowane_produkty = json_decode($tn_zawartosc_pliku, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($tn_importowane_produkty)) {
                    $tn_blad_importu_msg = 'Nieprawidłowy format pliku JSON. Oczekiwano tablicy obiektów. Błąd JSON: ' . json_last_error_msg();
                } else {
                    // --- Łączenie i walidacja importowanych danych ---
                    $tn_istniejace_id = array_column($tn_produkty, 'id');
                    $tn_nastepne_id = !empty($tn_istniejace_id) ? max($tn_istniejace_id) + 1 : 1;
                    $tn_prawidlowo_zaimportowane = [];
                    $tn_pominiete_liczba = 0;
                    $tn_zaktualizowane_liczba = 0;

                    foreach($tn_importowane_produkty as $tn_indeks_imp => $tn_element) {
                        // Sprawdź wymagane pola
                        if(!isset($tn_element['name']) || !isset($tn_element['price'])) {
                            $tn_pominiete_liczba++;
                            error_log("Import: Pominięto produkt (indeks: {$tn_indeks_imp}) z powodu braku wymaganych pól (name, price): " . print_r($tn_element, true));
                            continue; // Przejdź do następnego elementu
                        }

                        // Walidacja i sanityzacja pól importowanego produktu
                        $tn_nowy_produkt = [
                             'name' => trim(htmlspecialchars($tn_element['name'], ENT_QUOTES, 'UTF-8')),
                             'price' => filter_var($tn_element['price'], FILTER_VALIDATE_FLOAT),
                             'desc' => isset($tn_element['desc']) ? trim(htmlspecialchars($tn_element['desc'], ENT_QUOTES, 'UTF-8')) : '',
                             'spec' => isset($tn_element['spec']) ? trim(htmlspecialchars($tn_element['spec'], ENT_QUOTES, 'UTF-8')) : '',
                             'params' => isset($tn_element['params']) ? trim(htmlspecialchars($tn_element['params'], ENT_QUOTES, 'UTF-8')) : '',
                             'vehicle' => isset($tn_element['vehicle']) ? trim(htmlspecialchars($tn_element['vehicle'], ENT_QUOTES, 'UTF-8')) : '',
                             'shipping' => filter_var($tn_element['shipping'] ?? 0, FILTER_VALIDATE_FLOAT),
                             'category' => isset($tn_element['category']) ? trim(htmlspecialchars($tn_element['category'], ENT_QUOTES, 'UTF-8')) : ($tn_ustawienia_globalne['kategorie_produktow'][0] ?? 'Inne'), // Domyślna kategoria
                             'producent' => isset($tn_element['producent']) ? trim(htmlspecialchars($tn_element['producent'], ENT_QUOTES, 'UTF-8')) : '',
                             'stock' => filter_var($tn_element['stock'] ?? 0, FILTER_VALIDATE_INT),
                             'warehouse' => isset($tn_element['warehouse']) ? trim(htmlspecialchars($tn_element['warehouse'], ENT_QUOTES, 'UTF-8')) : $tn_ustawienia_globalne['domyslny_magazyn'],
                             'image' => isset($tn_element['image']) ? filter_var(trim($tn_element['image']), FILTER_SANITIZE_URL) : '' // Sanityzacja URL obrazka
                        ];

                         // Walidacja poprawności wartości
                         if ($tn_nowy_produkt['price'] === false || $tn_nowy_produkt['price'] < 0) $tn_nowy_produkt['price'] = 0;
                         if ($tn_nowy_produkt['shipping'] === false || $tn_nowy_produkt['shipping'] < 0) $tn_nowy_produkt['shipping'] = 0;
                         if ($tn_nowy_produkt['stock'] === false || $tn_nowy_produkt['stock'] < 0) $tn_nowy_produkt['stock'] = 0;
                         if (empty($tn_nowy_produkt['name'])) { // Nazwa nie może być pusta po oczyszczeniu
                              $tn_pominiete_liczba++;
                              error_log("Import: Pominięto produkt (indeks: {$tn_indeks_imp}) - pusta nazwa po oczyszczeniu.");
                              continue;
                         }


                        // Obsługa ID: jeśli istnieje w imporcie i istnieje już w bazie, aktualizuj; jeśli nie istnieje w imporcie, dodaj nowy
                        $tn_importowane_id = filter_var($tn_element['id'] ?? null, FILTER_VALIDATE_INT);
                        $tn_znaleziono_klucz_do_aktualizacji = false;

                        if ($tn_importowane_id !== false && $tn_importowane_id > 0) {
                            foreach ($tn_produkty as $tn_klucz => &$tn_istniejacy_p) {
                                if (($tn_istniejacy_p['id'] ?? null) === $tn_importowane_id) {
                                     // Znaleziono istniejący produkt - aktualizuj go
                                     $tn_nowy_produkt['id'] = $tn_importowane_id; // Zachowaj ID
                                     $tn_istniejacy_p = $tn_nowy_produkt;
                                     $tn_znaleziono_klucz_do_aktualizacji = true;
                                     $tn_zaktualizowane_liczba++;
                                     break;
                                }
                            }
                            unset($tn_istniejacy_p);
                        }

                        // Jeśli nie znaleziono do aktualizacji (lub nie było ID w imporcie), dodaj jako nowy
                        if (!$tn_znaleziono_klucz_do_aktualizacji) {
                             $tn_nowy_produkt['id'] = $tn_nastepne_id++;
                             $tn_produkty[] = $tn_nowy_produkt;
                             $tn_prawidlowo_zaimportowane[] = $tn_nowy_produkt; // Licz jako nowo dodany
                        }
                    } // Koniec pętli foreach po importowanych produktach

                    // --- Zapis do pliku ---
                     if (!empty($tn_prawidlowo_zaimportowane) || $tn_zaktualizowane_liczba > 0) {
                         if (tn_zapisz_produkty($tn_plik_produkty, $tn_produkty)) {
                             $tn_wiadomosc_sukces = "Import zakończony.";
                             if (count($tn_prawidlowo_zaimportowane) > 0) $tn_wiadomosc_sukces .= " Dodano: " . count($tn_prawidlowo_zaimportowane) . ".";
                             if ($tn_zaktualizowane_liczba > 0) $tn_wiadomosc_sukces .= " Zaktualizowano: " . $tn_zaktualizowane_liczba . ".";
                             if ($tn_pominiete_liczba > 0) $tn_wiadomosc_sukces .= " Pominięto: " . $tn_pominiete_liczba . " (sprawdź logi).";
                             tn_ustaw_komunikat_flash($tn_wiadomosc_sukces, 'success');
                         } else {
                             $tn_blad_importu_msg = 'Błąd zapisu danych produktów.';
                         }
                     } elseif ($tn_pominiete_liczba > 0) {
                           $tn_blad_importu_msg = 'Żadne produkty nie zostały zaimportowane. Pominięto: ' . $tn_pominiete_liczba . " (sprawdź logi).";
                     } else {
                          $tn_blad_importu_msg = 'Plik nie zawierał prawidłowych danych produktów do zaimportowania lub zaktualizowania.';
                     }
                }
            }
        }
    }

    // --- Ustaw komunikat błędu, jeśli wystąpił ---
    if (!empty($tn_blad_importu_msg)) {
        tn_ustaw_komunikat_flash("Błąd importu: " . $tn_blad_importu_msg, 'danger');
    }

    // --- Przekierowanie ---
    header("Location: /produkty"); // Zawsze wracaj do listy produktów
    exit;

} // Koniec if ($_SERVER['REQUEST_METHOD'] === 'POST'...)

?>