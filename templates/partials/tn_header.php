<?php

$tn_wyglad = $tn_ustawienia_globalne['wyglad'] ?? [];
$tn_motyw = $tn_wyglad['tn_motyw'] ?? ''; 
$tn_motyw_atrybut = ($tn_motyw === 'ciemny') ? 'ciemny' : 'jasny';
$tn_nowy_domyslny_rozmiar_czcionki = '12px';
$tn_rozmiar_czcionki_ustawienia = $tn_wyglad['rozmiar_czcionki'] ?? $tn_nowy_domyslny_rozmiar_czcionki;
if (!preg_match('/^\d+(\.\d+)?(px|rem|em|%)$/i', $tn_rozmiar_czcionki_ustawienia)) {
    $tn_rozmiar_czcionki_ustawienia = $tn_nowy_domyslny_rozmiar_czcionki;
}
$tn_rozmiar_czcionki_css = htmlspecialchars(trim($tn_rozmiar_czcionki_ustawienia), ENT_QUOTES, 'UTF-8');
$tn_szerokosc_sidebar_css = '185px';
$tn_kolor_akcentu_css = htmlspecialchars(trim($tn_wyglad['tn_kolor_akcentu'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8');
$tn_nazwa_aplikacji = htmlspecialchars($tn_ustawienia_globalne['nazwa_strony'] ?? '');
$tn_pelny_tytul_strony = htmlspecialchars($tn_tytul_strony);
$tn_sciezka_css = '../../public/css/tn_styles.css';
$tn_wersja_css = @filemtime($tn_sciezka_css) ?: time();
$tn_favicon_ico = '../../TNimg/favicon.ico';
?>
<!DOCTYPE html>
<html lang="pl" data-bs-theme="<?php echo $tn_motyw_atrybut; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tn_pelny_tytul_strony; ?></title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="icon" href="/TNimg/favicon.ico" sizes="any">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo $tn_sciezka_css; ?>?v=<?php echo $tn_wersja_css; ?>">
 </head>
<body>

<div class="d-flex tn-glowny-kontener">