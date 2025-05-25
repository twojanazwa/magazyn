<?php
// templates/pages/tn_settings_form.php
/**
 * Formularz edycji ustawień aplikacji.
 * Wersja: 1.5 (Dodano rozszerzone dane firmy i strukturę pod integracje)
 *
 * Oczekuje zmiennych z index.php:
 * @var array $tn_ustawienia_globalne Załadowane ustawienia globalne.
 * @var string $tn_token_csrf Aktualny token CSRF.
 * @var array $tn_produkty (nieużywane bezpośrednio)
 * @var array $tn_zamowienia (nieużywane bezpośrednio)
 * @var array $tn_zwroty (nieużywane bezpośrednio)
 */

declare(strict_types=1); // Włącz ścisłe typowanie

// --- Funkcje Pomocnicze (Lokalne, dla pewności) ---
// Te funkcje powinny być globalnie dostępne, ale dodajemy fallbacki
if (!function_exists('tn_generuj_liste_textarea')) {
    /**
     * Generuje string dla textarea z listy tablic (menu) lub prostych list (kategorie).
     * Obsługuje format menu: Tytuł|URL|ikona|Grupa|ID
     * Obsługuje proste listy: Wartość1\nWartość2
     */
    function tn_generuj_liste_textarea(array $lista): string {
        $output = [];
        if (empty($lista)) {
            return '';
        }

        // Sprawdź, czy lista wygląda jak struktura menu (ma klucz 'tytul' w pierwszym elemencie)
        $firstItem = reset($lista);
        if (is_array($firstItem) && isset($firstItem['tytul'])) {
            foreach ($lista as $item) {
                 $parts = [
                    htmlspecialchars(trim($item['tytul'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars(trim($item['url'] ?? '#'), ENT_QUOTES, 'UTF-8'),
                    !empty($item['ikona']) ? htmlspecialchars(trim($item['ikona']), ENT_QUOTES, 'UTF-8') : '',
                    !empty($item['grupa']) ? htmlspecialchars(trim($item['grupa']), ENT_QUOTES, 'UTF-8') : '',
                    !empty($item['id']) ? htmlspecialchars(trim($item['id']), ENT_QUOTES, 'UTF-8') : '',
                 ];
                 // Usuń puste elementy z końca, aby nie tworzyć zbędnych '|'
                 while(count($parts) > 2 && empty(end($parts))) {
                     array_pop($parts);
                 }
                 $output[] = implode('|', $parts);
            }
        } else {
            // Traktuj jako prostą listę wartości
            foreach ($lista as $item) {
                if (is_scalar($item) && !empty(trim((string)$item))) {
                    $output[] = htmlspecialchars(trim((string)$item), ENT_QUOTES, 'UTF-8');
                }
            }
        }
        return implode("\n", $output);
    }
}

if (!function_exists('selected')) {
    /** Pomocnik do zaznaczania opcji w select */
    function selected($a, $b): void {
        if ((string)$a === (string)$b) {
            echo ' selected';
        }
    }
}

if (!function_exists('checked')) {
    /** Pomocnik do zaznaczania checkboxów */
    function checked($a): void {
        if ($a) {
            echo ' checked';
        }
    }
}
// --- Koniec Funkcji Pomocniczych ---

// --- Przygotowanie danych z ustawień dla formularza ---
// Użyj operatora ??, aby zapewnić domyślne puste tablice, jeśli klucze nie istnieją
$tn_wyglad = $tn_ustawienia_globalne['wyglad'] ?? [];
$tn_magazyn_ust = $tn_ustawienia_globalne['magazyn'] ?? [];
$tn_firma_ust = $tn_ustawienia_globalne['firma'] ?? []; // Dane firmy
$tn_zwroty_ust = $tn_ustawienia_globalne['zwroty_reklamacje'] ?? [];
$tn_finanse_ust = $tn_ustawienia_globalne['finanse'] ?? []; // Ustawienia finansowe
$tn_integracje_ust = $tn_ustawienia_globalne['integracje'] ?? []; // Nowy klucz dla integracji


// Dostępne statusy (pobierz z globalnych - powinny być zdefiniowane w config.php)
$tn_dostepne_statusy_zam = $GLOBALS['tn_prawidlowe_statusy'] ?? [];
$tn_dostepne_statusy_zwrotow = $GLOBALS['tn_prawidlowe_statusy_zwrotow'] ?? [];

?>

<div class="container-fluid px-lg-4 py-4">

    <?php // Nagłówek strony ?>
    <h1 class="mt-4 mb-4"><i class="bi bi-sliders me-3"></i>Ustawienia Aplikacji</h1>

    <?php // Komunikaty flash (jeśli są) ?>
    <?php // zakomentowane, bo powinny być wyświetlane przez główny layout
    // if (function_exists('wyswietlWiadomosciFlash')) {
    //    wyswietlWiadomosciFlash();
    // }
    ?>


    <form method="POST" action="" enctype="multipart/form-data"> <?php // Dodano enctype dla uploadu plików ?>
        <input type="hidden" name="action" value="save_settings">
        <input type="hidden" name="tn_csrf_token" value="<?php echo $tn_token_csrf; ?>">

        <div class="row g-4">
            <?php // ================= KOLUMNA LEWA ================= ?>
            <div class="col-lg-6 d-flex flex-column">

                <?php // --- Sekcja: Ustawienia Ogólne i Regionalne --- ?>
                <div class="card shadow-sm mb-4 flex-grow-1">
                    <div class="card-header bg-light-subtle py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-gear-wide-connected me-2"></i>Ogólne i Regionalne</h6></div>
                    <div class="card-body p-3">
                         <fieldset class="mb-3 pb-3 border-bottom">
                            <legend class="h6 fs-sm fw-bold mb-3">Paginacja i Waluta</legend>
                            <div class="row g-3">
                                 <div class="col-md-6">
                                    <label for="tn_products_per_page" class="form-label small mb-1">Produktów na stronę</label>
                                    <input type="number" name="produkty_na_stronie" class="form-control form-control-sm" id="tn_products_per_page" value="<?php echo htmlspecialchars((string)($tn_ustawienia_globalne['produkty_na_stronie'] ?? 10)); ?>" min="1" max="100" required>
                                 </div>
                                  <div class="col-md-6">
                                    <label for="tn_orders_per_page" class="form-label small mb-1">Zamówień na stronę</label>
                                    <input type="number" name="zamowienia_na_stronie" class="form-control form-control-sm" id="tn_orders_per_page" value="<?php echo htmlspecialchars((string)($tn_ustawienia_globalne['zamowienia_na_stronie'] ?? 15)); ?>" min="1" max="100" required>
                                 </div>
                                 <div class="col-md-6">
                                    <label for="tn_returns_per_page" class="form-label small mb-1">Zwrotów/Rekl. na stronę</label>
                                    <input type="number" name="zwroty_na_stronie" class="form-control form-control-sm" id="tn_returns_per_page" value="<?php echo htmlspecialchars((string)($tn_ustawienia_globalne['zwroty_na_stronie'] ?? 15)); ?>" min="1" max="100" required>
                                 </div>
                                 <div class="col-md-6">
                                     <label for="tn_currency" class="form-label small mb-1">Waluta (Symbol)</label>
                                     <input type="text" name="waluta" class="form-control form-control-sm" id="tn_currency" value="<?php echo htmlspecialchars($tn_ustawienia_globalne['waluta'] ?? 'zł'); ?>" maxlength="5" title="Podaj symbol waluty (np. zł, €, $)" required>
                                 </div>
                                 <div class="col-md-6">
                                     <label for="tn_default_discount_rate" class="form-label small mb-1">Domyślny rabat (%)</label>
                                     <input type="number" step="0.01" min="0" max="100" name="domyslny_procent_rabatu" class="form-control form-control-sm" id="tn_default_discount_rate" value="<?php echo htmlspecialchars((string)(($tn_ustawienia_globalne['domyslny_procent_rabatu'] ?? 0))); ?>"> <?php // Wartość w % ?>
                                     <div class="form-text small mt-1">Wartość rabatu domyślnie proponowana przy dodawaniu zamówienia.</div>
                                 </div>
                             </div>
                         </fieldset>

                         <fieldset>
                            <legend class="h6 fs-sm fw-bold mb-3 pt-2">Formaty Regionalne</legend>
                             <div class="row g-3">
                                 <div class="col-md-6">
                                     <label for="tn_format_daty" class="form-label small mb-1">Format daty</label>
                                     <select name="tn_format_daty" id="tn_format_daty" class="form-select form-select-sm">
                                         <option value="d.m.Y" <?php selected($tn_ustawienia_globalne['tn_format_daty'] ?? 'd.m.Y', 'd.m.Y'); ?>>DD.MM.RRRR (np. 31.12.2023)</option>
                                         <option value="Y-m-d" <?php selected($tn_ustawienia_globalne['tn_format_daty'] ?? 'd.m.Y', 'Y-m-d'); ?>>RRRR-MM-DD (np. 2023-12-31)</option>
                                         <option value="m/d/Y" <?php selected($tn_ustawienia_globalne['tn_format_daty'] ?? 'd.m.Y', 'm/d/Y'); ?>>MM/DD/RRRR (np. 12/31/2023)</option>
                                          <option value="d/m/Y" <?php selected($tn_ustawienia_globalne['tn_format_daty'] ?? 'd.m.Y', 'd/m/Y'); ?>>DD/MM/RRRR (np. 31/12/2023)</option>
                                     </select>
                                 </div>
                                 <div class="col-md-6">
                                      <label for="tn_format_czasu" class="form-label small mb-1">Format czasu</label>
                                     <select name="tn_format_czasu" id="tn_format_czasu" class="form-select form-select-sm">
                                         <option value="H:i" <?php selected($tn_ustawienia_globalne['tn_format_czasu'] ?? 'H:i', 'H:i'); ?>>24-godzinny (np. 14:30)</option>
                                          <option value="H:i:s" <?php selected($tn_ustawienia_globalne['tn_format_czasu'] ?? 'H:i', 'H:i:s'); ?>>24-godz. z sek. (np. 14:30:05)</option>
                                         <option value="h:i A" <?php selected($tn_ustawienia_globalne['tn_format_czasu'] ?? 'H:i', 'h:i A'); ?>>12-godzinny (np. 02:30 PM)</option>
                                     </select>
                                 </div>
                             </div>
                        </fieldset>
                    </div>
                </div>

                <?php // --- Sekcja: Dane Firmy/Sklepu (Rozszerzone) --- ?>
                <div class="card shadow-sm mb-4 flex-grow-1">
                     <div class="card-header bg-light-subtle py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-building-fill me-2"></i>Dane Firmy/Sklepu</h6></div>
                     <div class="card-body p-3">
                         <fieldset>
                            <legend class="h6 fs-sm fw-bold mb-3">Informacje o Firmie</legend>
                              <div class="mb-3">
                                 <label for="tn_firma_nazwa" class="form-label small mb-1">Nazwa firmy</label>
                                 <input type="text" name="firma[nazwa]" class="form-control form-control-sm" id="tn_firma_nazwa" value="<?php echo htmlspecialchars($tn_firma_ust['nazwa'] ?? ''); ?>">
                             </div>
                             <div class="mb-3">
                                 <label for="tn_firma_nip" class="form-label small mb-1">NIP</label>
                                 <input type="text" name="firma[nip]" class="form-control form-control-sm" id="tn_firma_nip" value="<?php echo htmlspecialchars($tn_firma_ust['nip'] ?? ''); ?>">
                             </div>
                              <div class="mb-3">
                                 <label for="tn_firma_adres_ulica" class="form-label small mb-1">Adres siedziby (Ulica i numer)</label>
                                 <input type="text" name="firma[adres_ulica]" class="form-control form-control-sm" id="tn_firma_adres_ulica" value="<?php echo htmlspecialchars($tn_firma_ust['adres_ulica'] ?? ''); ?>">
                             </div>
                              <div class="row g-3 mb-3">
                                  <div class="col-md-4">
                                     <label for="tn_firma_adres_kod_pocztowy" class="form-label small mb-1">Kod pocztowy</label>
                                     <input type="text" name="firma[adres_kod_pocztowy]" class="form-control form-control-sm" id="tn_firma_adres_kod_pocztowy" value="<?php echo htmlspecialchars($tn_firma_ust['adres_kod_pocztowy'] ?? ''); ?>">
                                 </div>
                                  <div class="col-md-8">
                                     <label for="tn_firma_adres_miejscowosc" class="form-label small mb-1">Miejscowość</label>
                                     <input type="text" name="firma[adres_miejscowosc]" class="form-control form-control-sm" id="tn_firma_adres_miejscowosc" value="<?php echo htmlspecialchars($tn_firma_ust['adres_miejscowosc'] ?? ''); ?>">
                                 </div>
                             </div>
                              <div class="mb-3">
                                 <label for="tn_firma_numer_rachunku" class="form-label small mb-1">Numer rachunku bankowego</label>
                                 <input type="text" name="firma[numer_rachunku]" class="form-control form-control-sm" id="tn_firma_numer_rachunku" value="<?php echo htmlspecialchars($tn_firma_ust['numer_rachunku'] ?? ''); ?>">
                             </div>
                             <div class="mb-3">
                                 <label for="tn_firma_telefon" class="form-label small mb-1">Numer kontaktowy (telefon)</label>
                                 <input type="tel" name="firma[telefon]" class="form-control form-control-sm" id="tn_firma_telefon" value="<?php echo htmlspecialchars($tn_firma_ust['telefon'] ?? ''); ?>">
                             </div>
                             <div class="mb-3">
                                 <label for="tn_firma_email" class="form-label small mb-1">Adres e-mail firmy</label>
                                 <input type="email" name="firma[email]" class="form-control form-control-sm" id="tn_firma_email" value="<?php echo htmlspecialchars($tn_firma_ust['email'] ?? ''); ?>">
                             </div>
                             <div class="form-text small mt-1">Te dane mogą być wykorzystane np. w przyszłych modułach faktur czy wydruków.</div>
                         </fieldset>
                     </div>
                 </div>

                 <?php // --- Sekcja: Ustawienia Finansowe --- ?>
                 <div class="card shadow-sm mb-4 flex-grow-1">
                     <div class="card-header bg-light-subtle py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-currency-dollar me-2"></i>Ustawienia Finansowe</h6></div>
                     <div class="card-body p-3">
                         <fieldset>
                            <legend class="h6 fs-sm fw-bold mb-3">Obliczenia Podsumowania Sprzedaży</legend>
                            <div class="mb-3">
                                <label for="procent_prowizji" class="form-label small mb-1">Procent Prowizji (%)</label>
                                <input type="number" class="form-control form-control-sm" id="procent_prowizji" name="finanse[procent_prowizji]" value="<?php echo htmlspecialchars((string)($tn_finanse_ust['procent_prowizji'] ?? 0)); ?>" min="0" step="0.01" required>
                                <div class="form-text small mt-1">Wprowadź procent prowizji od wartości sprzedaży (np. 5 dla 5%).</div>
                            </div>

                            <div class="mb-3">
                                <label for="procent_podatku_dochodowego" class="form-label small mb-1">Procent Podatku Dochodowego (%)</label>
                                <input type="number" class="form-control form-control-sm" id="procent_podatku_dochodowego" name="finanse[procent_podatku_dochodowego]" value="<?php echo htmlspecialchars((string)($tn_finanse_ust['procent_podatku_dochodowego'] ?? 0)); ?>" min="0" step="0.01" required>
                                <div class="form-text small mt-1">Wprowadź procent podatku dochodowego, naliczany od szacowanego zysku przed opodatkowaniem.</div>
                            </div>

                            <div class="mb-3">
                                <label for="procent_kosztu_pakowania" class="form-label small mb-1">Procent Kosztu Materiałów Pakowania (%)</label>
                                <input type="number" class="form-control form-control-sm" id="procent_kosztu_pakowania" name="finanse[procent_kosztu_pakowania]" value="<?php echo htmlspecialchars((string)($tn_finanse_ust['procent_kosztu_pakowania'] ?? 0)); ?>" min="0" step="0.01" required>
                                <div class="form-text small mt-1">Wprowadź procent kosztów materiałów do pakowania, naliczany od wartości sprzedaży.</div>
                            </div>

                            <div class="mb-3">
                                <label for="koszt_magazynowania_miesieczny" class="form-label small mb-1">Miesięczny Koszt Magazynowania (<?php echo htmlspecialchars($tn_ustawienia_globalne['waluta'] ?? 'zł'); ?>)</label>
                                <input type="number" class="form-control form-control-sm" id="koszt_magazynowania_miesieczny" name="finanse[koszt_magazynowania_miesieczny]" value="<?php echo htmlspecialchars((string)($tn_finanse_ust['koszt_magazynowania_miesieczny'] ?? 0)); ?>" min="0" step="0.01" required>
                                <div class="form-text small mt-1">Wprowadź stały miesięczny koszt utrzymania magazynu.</div>
                            </div>
                         </fieldset>
                     </div>
                 </div>


            </div> <?php // === Koniec Kolumny Lewej === ?>

            <?php // ================= KOLUMNA PRAWA ================= ?>
            <div class="col-lg-6 d-flex flex-column">

                 <?php // --- Sekcja: Wygląd i Nawigacja --- ?>
                 <div class="card shadow-sm mb-4 flex-grow-1">
                     <div class="card-header bg-light-subtle py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-palette-fill me-2"></i>Wygląd i Nawigacja</h6></div>
                     <div class="card-body p-3">
                         <fieldset class="mb-3 pb-3 border-bottom">
                            <legend class="h6 fs-sm fw-bold mb-3">Branding</legend>
                              <div class="mb-3"><label for="tn_site_name" class="form-label small mb-1">Nazwa strony <span class="text-danger">*</span></label><input type="text" name="nazwa_strony" class="form-control form-control-sm" id="tn_site_name" value="<?php echo htmlspecialchars($tn_ustawienia_globalne['nazwa_strony'] ?? ''); ?>" required></div>
                              <div class="mb-3"><label for="tn_site_description" class="form-label small mb-1">Opis strony</label><input type="text" name="opis_strony" class="form-control form-control-sm" id="tn_site_description" value="<?php echo htmlspecialchars($tn_ustawienia_globalne['opis_strony'] ?? ''); ?>" placeholder="System Zarządzania Magazynem..."></div>

                              <?php // Pole do uploadu logo ?>
                              <div class="mb-3">
                                  <label for="tn_site_logo_upload" class="form-label small mb-1">Logo aplikacji (plik)</label>
                                  <input class="form-control form-control-sm" type="file" id="tn_site_logo_upload" name="logo_plik" accept="image/*">
                                  <div class="form-text small mt-1">Prześlij plik graficzny (JPG, PNG, GIF, WEBP).</div>
                                  <?php if (!empty($tn_ustawienia_globalne['logo_plik'])): ?>
                                       <div class="form-check form-check-inline mt-2">
                                           <input class="form-check-input" type="checkbox" id="usun_logo" name="usun_logo" value="tak">
                                           <label class="form-check-label small" for="usun_logo">Usuń aktualne logo (<?php echo htmlspecialchars($tn_ustawienia_globalne['logo_plik']); ?>)</label>
                                       </div>
                                       <div class="mt-2">
                                            <img src="public/uploads/logo/<?php echo htmlspecialchars($tn_ustawienia_globalne['logo_plik']); ?>" alt="Aktualne logo" style="max-height: 50px; border: 1px solid #ccc; padding: 2px; background-color: #fff;">
                                       </div>
                                  <?php endif; ?>
                              </div>

                               <?php // Pole do uploadu ikony (favicon) ?>
                              <div class="mb-3">
                                  <label for="tn_site_icon_upload" class="form-label small mb-1">Ikona aplikacji (Favicon - plik)</label>
                                  <input class="form-control form-control-sm" type="file" id="tn_site_icon_upload" name="ikona_aplikacji" accept="image/*">
                                  <div class="form-text small mt-1">Prześlij plik graficzny (np. .ico, PNG, GIF).</div>
                                   <?php if (!empty($tn_ustawienia_globalne['ikona_aplikacji'])): ?>
                                       <div class="form-check form-check-inline mt-2">
                                           <input class="form-check-input" type="checkbox" id="usun_ikone" name="usun_ikone" value="tak">
                                           <label class="form-check-label small" for="usun_ikone">Usuń aktualną ikonę (<?php echo htmlspecialchars($tn_ustawienia_globalne['ikona_aplikacji']); ?>)</label>
                                       </div>
                                        <div class="mt-2">
                                            <img src="public/uploads/icons/<?php echo htmlspecialchars($tn_ustawienia_globalne['ikona_aplikacji']); ?>" alt="Aktualna ikona" style="max-height: 32px; border: 1px solid #ccc; padding: 2px; background-color: #fff;">
                                       </div>
                                  <?php endif; ?>
                              </div>

                              <div class="mb-3"><label for="tn_footer_text" class="form-label small mb-1">Tekst w stopce</label><input type="text" name="tresc_stopki" class="form-control form-control-sm" id="tn_footer_text" value="<?php echo htmlspecialchars($tn_ustawienia_globalne['tresc_stopki'] ?? ''); ?>"></div>
                         </fieldset>
                         <fieldset class="mb-3 pb-3 border-bottom">
                            <legend class="h6 fs-sm fw-bold mb-3 pt-2">Nawigacja (Menu boczne)</legend>
                             <div class="mb-3"><label for="tn_menu_links" class="form-label small mb-1">Definicja linków</label><textarea name="linki_menu" class="form-control form-control-sm font-monospace small" id="tn_menu_links" rows="8" aria-describedby="menuHelp"><?php echo tn_generuj_liste_textarea($tn_ustawienia_globalne['linki_menu'] ?? []); ?></textarea><div id="menuHelp" class="form-text small mt-1">Format: <code>Tytuł|URL|ikona|Grupa|ID</code>. Ikona, Grupa, ID są opcjonalne.<br>URL <code>js:funkcja()</code> wywoła JS. ID jest wymagane dla podmenu i musi być unikalne i zgodne z ID strony.</div></div>
                         </fieldset>
                         <fieldset>
                            <legend class="h6 fs-sm fw-bold mb-3 pt-2">Wygląd Aplikacji</legend>
                             <div class="row g-3">
                                 <div class="col-md-6 mb-2"><label for="tn_motyw" class="form-label small mb-1">Motyw</label><select name="wyglad[tn_motyw]" id="tn_motyw" class="form-select form-select-sm"><option value="jasny" <?php selected($tn_wyglad['tn_motyw'] ?? 'jasny', 'jasny'); ?>>Jasny</option><option value="ciemny" <?php selected($tn_wyglad['tn_motyw'] ?? 'jasny', 'ciemny'); ?>>Ciemny</option><option value="auto" <?php selected($tn_wyglad['tn_motyw'] ?? 'jasny', 'auto'); ?>>Auto (systemowy)</option></select></div> <?php // Dodano motyw 'auto' ?>
                                 <div class="col-md-6 mb-2"><label for="tn_kolor_sidebar" class="form-label small mb-1">Sidebar</label><select name="wyglad[tn_kolor_sidebar]" id="tn_kolor_sidebar" class="form-select form-select-sm"><option value="ciemny" <?php selected($tn_wyglad['tn_kolor_sidebar'] ?? 'ciemny', 'ciemny'); ?>>Ciemny</option><option value="jasny" <?php selected($tn_wyglad['tn_kolor_sidebar'] ?? 'ciemny', 'jasny'); ?>>Jasny</option></select></div>
                                 <div class="col-md-6 mb-2 d-flex align-items-center"><label for="tn_kolor_akcentu" class="form-label small me-2 mb-0">Kolor akcentu</label><input type="color" name="wyglad[tn_kolor_akcentu]" class="form-control form-control-color form-control-sm" id="tn_kolor_akcentu" value="<?php echo htmlspecialchars($tn_wyglad['tn_kolor_akcentu'] ?? '#0d6efd'); ?>" title="Wybierz kolor akcentu"></div>
                                 <div class="col-md-6 mb-2"><label for="tn_font_size" class="form-label small mb-1">Rozmiar czcionki</label><input type="text" name="wyglad[rozmiar_czcionki]" class="form-control form-control-sm" id="tn_font_size" value="<?php echo htmlspecialchars($tn_wyglad['rozmiar_czcionki'] ?? '0.9rem'); ?>" placeholder="np. 13px, 0.9rem" pattern="\d+(\.\d+)?(px|rem|em|%)" title="Podaj wartość z jednostką (px, rem, em, %)"></div> <?php // Zmieniono domyślną wartość na rem ?>
                                 <div class="col-12 mt-2">
                                     <div class="form-check form-switch mb-1"><input class="form-check-input" type="checkbox" role="switch" id="tn_tabela_paskowana" name="wyglad[tn_tabela_paskowana]" value="1" <?php checked($tn_wyglad['tn_tabela_paskowana'] ?? true); ?>><label class="form-check-label small" for="tn_tabela_paskowana">Tabele paskowane (zebra)</label></div>
                                     <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="tn_tabela_krawedzie" name="wyglad[tn_tabela_krawedzie]" value="1" <?php checked($tn_wyglad['tn_tabela_krawedzie'] ?? true); ?>><label class="form-check-label small" for="tn_tabela_krawedzie">Tabele z krawędziami</label></div>
                                 </div>
                             </div>
                         </fieldset>
                     </div>
                </div>

                <?php // --- Sekcja: Moduły Główne --- ?>
                <div class="card shadow-sm mb-4 flex-grow-1">
                    <div class="card-header bg-light-subtle py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-puzzle-fill me-2"></i>Moduły Główne</h6></div>
                    <div class="card-body p-3">
                         <fieldset class="mb-3 pb-3 border-bottom">
                             <legend class="h6 fs-sm fw-bold mb-3">Produkty</legend>
                             <div class="mb-3"><label for="tn_product_categories" class="form-label small mb-1">Dostępne Kategorie</label><textarea name="kategorie_produktow" class="form-control form-control-sm" id="tn_product_categories" rows="3"><?php echo tn_generuj_liste_textarea($tn_ustawienia_globalne['kategorie_produktow'] ?? []); ?></textarea><div class="form-text small mt-1">Każda kategoria w nowej linii.</div></div>
                             <div class=""><label for="tn_prog_niskiego_stanu" class="form-label small mb-1">Próg niskiego stanu magazynowego</label><input type="number" name="tn_prog_niskiego_stanu" class="form-control form-control-sm" id="tn_prog_niskiego_stanu" value="<?php echo htmlspecialchars((string)($tn_ustawienia_globalne['tn_prog_niskiego_stanu'] ?? 5)); ?>" min="0" required><div class="form-text small mt-1">Produkty ze stanem równym lub niższym od tej wartości będą oznaczane jako "niski stan".</div></div>
                         </fieldset>

                         <fieldset class="mb-3 pb-3 border-bottom">
                            <legend class="h6 fs-sm fw-bold mb-3 pt-2">Zamówienia</legend>
                             <div class=""><label for="tn_domyslny_status_zam" class="form-label small mb-1">Domyślny status nowego zamówienia</label><select name="tn_domyslny_status_zam" id="tn_domyslny_status_zam" class="form-select form-select-sm" required><?php if(!empty($tn_dostepne_statusy_zam)): foreach($tn_dostepne_statusy_zam as $status): ?><option value="<?php echo htmlspecialchars($status); ?>" <?php selected($tn_ustawienia_globalne['tn_domyslny_status_zam'] ?? 'Nowe', $status); ?>><?php echo htmlspecialchars($status); ?></option><?php endforeach; else: ?><option value="" disabled>Brak statusów</option><?php endif; ?></select></div>
                         </fieldset>

                          <fieldset class="mb-3 pb-3 border-bottom">
                            <legend class="h6 fs-sm fw-bold mb-3 pt-2">Zwroty i Reklamacje</legend>
                             <div class=""><label for="tn_returns_default_status" class="form-label small mb-1">Domyślny status nowego zgłoszenia</label><select name="zwroty_reklamacje[domyslny_status]" id="tn_returns_default_status" class="form-select form-select-sm" required><?php if(!empty($tn_dostepne_statusy_zwrotow)): foreach($tn_dostepne_statusy_zwrotow as $status): ?><option value="<?php echo htmlspecialchars($status); ?>" <?php selected($tn_zwroty_ust['domyslny_status'] ?? 'Nowe zgłoszenie', $status); ?>><?php echo htmlspecialchars($status); ?></option><?php endforeach; else: ?><option value="" disabled>Brak statusów</option><?php endif; ?></select></div>
                         </fieldset>

                         <fieldset>
                            <legend class="h6 fs-sm fw-bold mb-3 pt-2">Magazyn</legend>
                             <div class="row g-3">
                                 <div class="col-md-6"><label for="tn_default_warehouse" class="form-label small mb-1">Domyślny nr mag.</label><input type="text" name="domyslny_magazyn" class="form-control form-control-sm" id="tn_default_warehouse" value="<?php echo htmlspecialchars($tn_ustawienia_globalne['domyslny_magazyn'] ?? 'GŁÓWNY'); ?>"></div>
                                 <div class="col-md-6"></div> <?php // Pusta kolumna dla wyrównania ?>
                                 <div class="col-md-6"><label for="tn_prefix_poziom_domyslny" class="form-label small mb-1">Prefix Poziomu (Lokalizacje)</label><input type="text" name="magazyn[tn_prefix_poziom_domyslny]" class="form-control form-control-sm" id="tn_prefix_poziom_domyslny" value="<?php echo htmlspecialchars($tn_magazyn_ust['tn_prefix_poziom_domyslny'] ?? 'S'); ?>" required pattern="^[A-Za-z0-9]+$" title="Tylko litery i cyfry"></div>
                                 <div class="col-md-6"><label for="tn_prefix_miejsca_domyslny" class="form-label small mb-1">Prefix Miejsca (Lokalizacje)</label><input type="text" name="magazyn[tn_prefix_miejsca_domyslny]" class="form-control form-control-sm" id="tn_prefix_miejsca_domyslny" value="<?php echo htmlspecialchars($tn_magazyn_ust['tn_prefix_miejsca_domyslny'] ?? 'P'); ?>" required pattern="^[A-Za-z0-9]+$" title="Tylko litery i cyfry"></div>
                             </div>
                         </fieldset>
                    </div>
                </div>

                <?php // --- Sekcja: Integracje (Struktura pod przyszłe dodatki) --- ?>
                <div class="card shadow-sm mb-4 flex-grow-1">
                    <div class="card-header bg-light-subtle py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-plug-fill me-2"></i>Integracje</h6></div>
                    <div class="card-body p-3">

                        <?php // Integracja z Allegro ?>
                        <fieldset class="mb-3 pb-3 border-bottom">
                            <legend class="h6 fs-sm fw-bold mb-3">Allegro</legend>
                            <div class="alert alert-info small py-2" role="alert">
                                <i class="bi bi-info-circle me-1"></i> Ta sekcja przygotowuje strukturę pod przyszłą integrację z Allegro. Aktualnie nie wpływa na działanie aplikacji.
                            </div>
                            <div class="mb-3">
                                <label for="integracje_allegro_client_id" class="form-label small mb-1">Client ID Allegro</label>
                                <input type="text" name="integracje[allegro][client_id]" class="form-control form-control-sm" id="integracje_allegro_client_id" value="<?php echo htmlspecialchars($tn_integracje_ust['allegro']['client_id'] ?? ''); ?>">
                            </div>
                             <div class="mb-3">
                                <label for="integracje_allegro_client_secret" class="form-label small mb-1">Client Secret Allegro</label>
                                <input type="text" name="integracje[allegro][client_secret]" class="form-control form-control-sm" id="integracje_allegro_client_secret" value="<?php echo htmlspecialchars($tn_integracje_ust['allegro']['client_secret'] ?? ''); ?>">
                            </div>
                             <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="integracje_allegro_enabled" name="integracje[allegro][enabled]" value="1" <?php checked($tn_integracje_ust['allegro']['enabled'] ?? false); ?>>
                                <label class="form-check-label small" for="integracje_allegro_enabled">Włącz integrację z Allegro</label>
                            </div>
                        </fieldset>

                         <?php // Integracja z InPost ?>
                        <fieldset class="mb-3 pb-3 border-bottom">
                            <legend class="h6 fs-sm fw-bold mb-3 pt-2">InPost</legend>
                             <div class="alert alert-info small py-2" role="alert">
                                <i class="bi bi-info-circle me-1"></i> Ta sekcja przygotowuje strukturę pod przyszłą integrację z InPost.
                            </div>
                            <div class="mb-3">
                                <label for="integracje_inpost_api_key" class="form-label small mb-1">API Key InPost</label>
                                <input type="text" name="integracje[inpost][api_key]" class="form-control form-control-sm" id="integracje_inpost_api_key" value="<?php echo htmlspecialchars($tn_integracje_ust['inpost']['api_key'] ?? ''); ?>">
                            </div>
                             <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="integracje_inpost_enabled" name="integracje[inpost][enabled]" value="1" <?php checked($tn_integracje_ust['inpost']['enabled'] ?? false); ?>>
                                <label class="form-check-label small" for="integracje_inpost_enabled">Włącz integrację z InPost</label>
                            </div>
                        </fieldset>

                         <?php // Integracja z Pocztą Polską ?>
                        <fieldset>
                            <legend class="h6 fs-sm fw-bold mb-3 pt-2">Poczta Polska</legend>
                             <div class="alert alert-info small py-2" role="alert">
                                <i class="bi bi-info-circle me-1"></i> Ta sekcja przygotowuje strukturę pod przyszłą integrację z Pocztą Polską.
                            </div>
                            <div class="mb-3">
                                <label for="integracje_poczta_polska_api_key" class="form-label small mb-1">API Key Poczta Polska</label>
                                <input type="text" name="integracje[poczta_polska][api_key]" class="form-control form-control-sm" id="integracje_poczta_polska_api_key" value="<?php echo htmlspecialchars($tn_integracje_ust['poczta_polska']['api_key'] ?? ''); ?>">
                            </div>
                             <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="integracje_poczta_polska_enabled" name="integracje[poczta_polska][enabled]" value="1" <?php checked($tn_integracje_ust['poczta_polska']['enabled'] ?? false); ?>>
                                <label class="form-check-label small" for="integracje_poczta_polska_enabled">Włącz integrację z Pocztą Polską</label>
                            </div>
                        </fieldset>

                    </div>
                </div>


            </div> <?php // === Koniec Kolumny Prawej === ?>
        </div> <?php // === Koniec .row === ?>

        <div class="mt-4 text-end border-top pt-3 bg-light sticky-bottom p-3 shadow-lg"> <?php // Stopka formularza ?>
            <a href="<?php echo tn_generuj_url('settings'); ?>" class="btn btn-secondary"><i class="bi bi-x-lg me-1"></i>Anulaj zmiany</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Zapisz wszystkie ustawienia</button>
        </div>
    </form>
</div>

<?php // Dodatkowe modale lub skrypty JS specyficzne dla tej strony (jeśli są) ?>
