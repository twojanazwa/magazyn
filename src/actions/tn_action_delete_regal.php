<?php
// src/actions/tn_action_delete_regal.php

require_once __DIR__ . '/../../config/tn_config.php';
require_once __DIR__ . '/../functions/tn_security_helpers.php';
require_once __DIR__ . '/../functions/tn_flash_messages.php';
require_once __DIR__ . '/../functions/tn_data_helpers.php';
require_once __DIR__ . '/../functions/tn_url_helpers.php';

if (!($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete_regal')) { header('Location: ../../index.php'); exit; }
if (!isset($_SESSION['tn_user_id'])) { header('Location: ../../login.php'); exit; }
if (!tn_waliduj_token_csrf($_GET['tn_csrf_token'] ?? null)) { tn_ustaw_komunikat_flash("Błąd CSRF.", 'danger'); header('Location: ' . tn_generuj_url('warehouse_view')); exit; }

$tn_id_regalu_do_usuniecia = trim($_GET['id'] ?? '');
$tn_bledy = [];

if (empty($tn_id_regalu_do_usuniecia)) {
    $tn_bledy[] = "Brak ID regału do usunięcia.";
} else {
    $tn_regaly = tn_laduj_regaly(TN_PLIK_REGALY);
    $tn_stan_magazynu = tn_laduj_magazyn(TN_PLIK_MAGAZYN);
    $tn_regal_znaleziony = false;
    $tn_nowe_regaly = [];
    $tn_nowy_stan_magazynu = [];

    // Filtruj regały, aby usunąć wybrany
    foreach($tn_regaly as $r) {
        if (($r['tn_id_regalu'] ?? null) === $tn_id_regalu_do_usuniecia) {
            $tn_regal_znaleziony = true;
        } else {
            $tn_nowe_regaly[] = $r;
        }
    }

    if (!$tn_regal_znaleziony) {
        $tn_bledy[] = "Nie znaleziono regału o ID '".htmlspecialchars($tn_id_regalu_do_usuniecia)."'.";
    } else {
        // Filtruj stan magazynu, usuwając lokalizacje z usuwanego regału
        $tn_usuniete_lokalizacje_licznik = 0;
        foreach($tn_stan_magazynu as $m) {
            if (($m['tn_id_regalu'] ?? null) !== $tn_id_regalu_do_usuniecia) {
                $tn_nowy_stan_magazynu[] = $m;
            } else {
                 $tn_usuniete_lokalizacje_licznik++;
            }
        }

        // Zapisz obie zmodyfikowane tablice
        $zapis_regalow_ok = tn_zapisz_regaly(TN_PLIK_REGALY, $tn_nowe_regaly);
        $zapis_magazynu_ok = tn_zapisz_magazyn(TN_PLIK_MAGAZYN, $tn_nowy_stan_magazynu);

        if ($zapis_regalow_ok && $zapis_magazynu_ok) {
            tn_ustaw_komunikat_flash("Regał '".htmlspecialchars($tn_id_regalu_do_usuniecia)."' oraz {$tn_usuniete_lokalizacje_licznik} powiązanych lokalizacji zostały usunięte.", 'success');
        } else {
            if (!$zapis_regalow_ok) $tn_bledy[] = "Błąd zapisu pliku definicji regałów.";
            if (!$zapis_magazynu_ok) $tn_bledy[] = "Błąd zapisu pliku stanu magazynu.";
        }
    }
}

if (!empty($tn_bledy)) {
    tn_ustaw_komunikat_flash("Błąd usuwania regału: " . implode(' ', $tn_bledy), 'danger');
}

header("Location: " . tn_generuj_url('warehouse_view'));
exit;

?>