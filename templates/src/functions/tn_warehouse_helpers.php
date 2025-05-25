<?php
// src/functions/tn_warehouse_helpers.php

/**
 * Funkcja logiki przesunięcia magazynowego (nieużywana w przepływie z pytania, ale zachowana).
 * Próbuje wygenerować następny numer magazynowy w sekwencji.
 *
 * @param string $tn_magazyn Aktualny numer magazynowy (np. MG01-001).
 * @param string $tn_domyslny_magazyn Domyślny numer do zwrócenia w razie błędu.
 * @return string Następny numer magazynowy lub domyślny.
 */
function tn_przypisz_magazyn(string $tn_magazyn, string $tn_domyslny_magazyn) : string {
    if (preg_match('/^(RA)(\d{2})-(\d{3})$/', $tn_magazyn, $tn_wyniki)) {
        $tn_prefix = $tn_wyniki[1]; // MG
        $tn_czesc1 = $tn_wyniki[2]; // np. 01
        $tn_czesc2 = intval($tn_wyniki[3]); // np. 1
        $tn_czesc2++;
        if ($tn_czesc2 > 999) {
            $tn_czesc2 = 1; // Przepełnienie - wraca do 001
            // Można by tu dodać logikę zmiany $tn_czesc1, jeśli potrzebne
        }
        return sprintf("%s%s-%03d", $tn_prefix, $tn_czesc1, $tn_czesc2);
    }
    // Zwróć domyślny, jeśli format nie pasuje
    return $tn_domyslny_magazyn;
}

/**
 * Aktualizuje stan magazynu (plik warehouse.json) na podstawie przypisania produktu.
 *
 * @param string $tn_plik_magazyn Ścieżka do pliku warehouse.json.
 * @param string $tn_id_lokalizacji ID lokalizacji do zaktualizowania.
 * @param int|null $tn_id_produktu ID produktu do przypisania (lub null dla opróżnienia).
 * @param int $tn_ilosc Ilość produktu w lokalizacji.
 * @param array $tn_produkty Tablica wszystkich produktów (do walidacji).
 * @return bool True w przypadku sukcesu, False w przeciwnym razie.
 */
function tn_aktualizuj_miejsce_magazynowe(string $tn_plik_magazyn, string $tn_id_lokalizacji, ?int $tn_id_produktu, int $tn_ilosc, array $tn_produkty): bool {
    $tn_stan_magazynu = tn_laduj_magazyn($tn_plik_magazyn);
    $tn_znaleziono_lokalizacje = false;
    $tn_produkt_istnieje = ($tn_id_produktu === null); // Null oznacza opróżnienie, więc produkt "istnieje" w tym sensie

    // Sprawdź, czy produkt istnieje, jeśli jest podany
    if ($tn_id_produktu !== null) {
        foreach ($tn_produkty as $p) {
            if (($p['id'] ?? null) === $tn_id_produktu) {
                $tn_produkt_istnieje = true;
                break;
            }
        }
    }

    if (!$tn_produkt_istnieje && $tn_id_produktu !== null) {
        tn_ustaw_komunikat_flash("Błąd: Produkt o ID {$tn_id_produktu} nie istnieje.", 'danger');
        return false;
    }

    foreach ($tn_stan_magazynu as &$tn_miejsce) {
        if (isset($tn_miejsce['id']) && $tn_miejsce['id'] === $tn_id_lokalizacji) {
            if ($tn_id_produktu !== null && $tn_ilosc > 0) {
                $tn_miejsce['status'] = 'occupied';
                $tn_miejsce['product_id'] = $tn_id_produktu;
                $tn_miejsce['quantity'] = $tn_ilosc;
            } else {
                // Opróżnianie miejsca
                $tn_miejsce['status'] = 'empty';
                unset($tn_miejsce['product_id']);
                unset($tn_miejsce['quantity']);
            }
            $tn_znaleziono_lokalizacje = true;
            break;
        }
    }
    unset($tn_miejsce); // Ważne po referencji w pętli

    if (!$tn_znaleziono_lokalizacje) {
         tn_ustaw_komunikat_flash("Błąd: Nie znaleziono lokalizacji magazynowej o ID {$tn_id_lokalizacji}.", 'danger');
        return false;
    }

    // Zapisz zmiany
    if (tn_zapisz_magazyn($tn_plik_magazyn, $tn_stan_magazynu)) {
        tn_ustaw_komunikat_flash("Zaktualizowano miejsce magazynowe {$tn_id_lokalizacji}.", 'success');
        return true;
    } else {
        tn_ustaw_komunikat_flash("Błąd zapisu stanu magazynu dla lokalizacji {$tn_id_lokalizacji}.", 'danger');
        return false;
    }
}


?>