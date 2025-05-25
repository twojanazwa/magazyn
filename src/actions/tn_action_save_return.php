<?php
// src/actions/tn_action_save_return.php

// Podstawowe zabezpieczenia i zależności
if (!($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_return')) {
    header('Location: ../'); exit;
}

require_once __DIR__ . '/../../config/tn_config.php';
require_once __DIR__ . '/../functions/tn_security_helpers.php';
require_once __DIR__ . '/../functions/tn_flash_messages.php';
require_once __DIR__ . '/../functions/tn_data_helpers.php';
require_once __DIR__ . '/../functions/tn_url_helpers.php';

// Sprawdzenie logowania
if (!isset($_SESSION['tn_user_id'])) {
     tn_ustaw_komunikat_flash("Musisz być zalogowany.", 'warning');
     header('Location: ../../logowanie'); exit;
}

// Walidacja tokenu CSRF
if (!tn_waliduj_token_csrf($_POST['tn_csrf_token'] ?? null)) {
    tn_ustaw_komunikat_flash("Błąd bezpieczeństwa (token CSRF).", 'danger');
    header('Location: index.php?page=returns_list'); exit; // Wróć do listy
}

// --- Pobranie i walidacja danych ---
$tn_id = filter_input(INPUT_POST, 'return_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$tn_edycja = ($tn_id !== null && $tn_id !== false);

$tn_type = $_POST['type'] ?? '';
$tn_status = $_POST['status'] ?? '';
$tn_order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$tn_product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$tn_quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$tn_customer_name = trim(htmlspecialchars($_POST['customer_name'] ?? '', ENT_QUOTES, 'UTF-8'));
$tn_customer_contact = trim(htmlspecialchars($_POST['customer_contact'] ?? '', ENT_QUOTES, 'UTF-8'));
$tn_reason = trim(htmlspecialchars($_POST['reason'] ?? '', ENT_QUOTES, 'UTF-8'));
$tn_notes = trim(htmlspecialchars($_POST['notes'] ?? '', ENT_QUOTES, 'UTF-8'));
$tn_resolution = trim(htmlspecialchars($_POST['resolution'] ?? '', ENT_QUOTES, 'UTF-8'));
$tn_stock_added = isset($_POST['returned_stock_added']); // Czy checkbox zaznaczony
$tn_refund_amount = filter_input(INPUT_POST, 'refund_amount', FILTER_VALIDATE_FLOAT); // Filtruj kwotę

// Podstawowa walidacja
$tn_bledy = [];
if (!in_array($tn_type, ['zwrot', 'reklamacja'])) $tn_bledy[] = "Nieprawidłowy typ zgłoszenia.";
if (empty($GLOBALS['tn_prawidlowe_statusy_zwrotow']) || !in_array($tn_status, $GLOBALS['tn_prawidlowe_statusy_zwrotow'])) $tn_bledy[] = "Nieprawidłowy status zgłoszenia.";
if ($tn_order_id === false) $tn_bledy[] = "Nie wybrano powiązanego zamówienia.";
if ($tn_product_id === false) $tn_bledy[] = "Nie wybrano produktu.";
if ($tn_quantity === false) $tn_bledy[] = "Ilość musi być liczbą całkowitą większą od 0.";
if (empty($tn_customer_name)) $tn_bledy[] = "Imię i Nazwisko klienta jest wymagane.";
if ($tn_refund_amount === false) $tn_bledy[] = "Kwota zwrotu musi być liczbą."; // Walidacja kwoty

// TODO: Bardziej zaawansowana walidacja:
// - Czy produkt ($tn_product_id) faktycznie znajduje się w zamówieniu ($tn_order_id)?
// - Czy zgłaszana ilość ($tn_quantity) nie jest większa niż w zamówieniu?

// --- Przetwarzanie (jeśli brak błędów) ---
if (empty($tn_bledy)) {
    $tn_zwroty = tn_laduj_zwroty(TN_PLIK_ZWROTY);
    $current_time = date(DateTime::ATOM); // Format ISO 8601

    if ($tn_edycja) { // Edycja istniejącego
        $tn_klucz_do_edycji = -1;
        foreach ($tn_zwroty as $key => $z) {
            if (($z['id'] ?? null) == $tn_id) {
                 $tn_klucz_do_edycji = $key;
                 break;
            }
        }

        if ($tn_klucz_do_edycji === -1) {
            $tn_bledy[] = "Nie znaleziono zgłoszenia o ID {$tn_id} do edycji.";
        } else {
            // Zaktualizuj dane
            $tn_zwroty[$tn_klucz_do_edycji]['type'] = $tn_type;
            $tn_zwroty[$tn_klucz_do_edycji]['status'] = $tn_status;
            $tn_zwroty[$tn_klucz_do_edycji]['order_id'] = $tn_order_id;
            $tn_zwroty[$tn_klucz_do_edycji]['product_id'] = $tn_product_id;
            $tn_zwroty[$tn_klucz_do_edycji]['quantity'] = $tn_quantity;
            $tn_zwroty[$tn_klucz_do_edycji]['customer_name'] = $tn_customer_name;
            $tn_zwroty[$tn_klucz_do_edycji]['customer_contact'] = $tn_customer_contact;
            $tn_zwroty[$tn_klucz_do_edycji]['reason'] = $tn_reason;
            $tn_zwroty[$tn_klucz_do_edycji]['notes'] = $tn_notes;
            $tn_zwroty[$tn_klucz_do_edycji]['resolution'] = $tn_resolution;
            $tn_zwroty[$tn_klucz_do_edycji]['returned_stock_added'] = $tn_stock_added;
            $tn_zwroty[$tn_klucz_do_edycji]['refund_amount'] = $tn_refund_amount; // Zapisz kwotę zwrotu
            $tn_zwroty[$tn_klucz_do_edycji]['date_updated'] = $current_time;
             $akcja_komunikat = 'zaktualizowano';
        }

    } else { // Dodawanie nowego
        $tn_nowe_id = tn_pobierz_nastepne_id_zwrotu($tn_zwroty);
        $tn_nowe_zgloszenie = [
            'id' => $tn_nowe_id,
            'type' => $tn_type,
            'status' => $tn_status,
            'date_created' => $current_time,
            'date_updated' => $current_time,
            'order_id' => $tn_order_id,
            'product_id' => $tn_product_id,
            'quantity' => $tn_quantity,
            'customer_name' => $tn_customer_name,
            'customer_contact' => $tn_customer_contact,
            'reason' => $tn_reason,
            'notes' => $tn_notes,
            'resolution' => $tn_resolution, // Może być puste na początku
            'returned_stock_added' => $tn_stock_added,
            'refund_amount' => $tn_refund_amount // Zapisz kwotę zwrotu
        ];
        $tn_zwroty[] = $tn_nowe_zgloszenie;
         $akcja_komunikat = 'dodano';
    }

    // Zapisz dane (tylko jeśli nie było błędów wcześniej)
    if (empty($tn_bledy)) {
        if (tn_zapisz_zwroty(TN_PLIK_ZWROTY, $tn_zwroty)) {
            tn_ustaw_komunikat_flash("Zgłoszenie zwrotu/reklamacji zostało pomyślnie {$akcja_komunikat}.", 'success');
        } else {
            tn_ustaw_komunikat_flash("Wystąpił błąd podczas zapisywania danych zgłoszenia.", 'danger');
            error_log("Błąd zapisu pliku zwrotów: " . TN_PLIK_ZWROTY);
        }
    }
}

// --- Przekierowanie ---
if (!empty($tn_bledy)) {
    tn_ustaw_komunikat_flash("Nie zapisano zgłoszenia. Popraw błędy: <br>- " . implode('<br>- ', $tn_bledy), 'danger');
    // Wróć do formularza (jeśli to edycja, zachowaj ID) - wymaga modyfikacji routingu
     $redirect_url = $tn_edycja ? tn_generuj_url('return_form_edit', ['id' => $tn_id]) : tn_generuj_url('return_form_new');
     header("Location: " . $redirect_url); exit;
} else {
    // Wróć do listy po sukcesie
    header("Location: " . tn_generuj_url('returns_list')); exit;
}
?>