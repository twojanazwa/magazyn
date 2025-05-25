<?php
// templates/pages/tn_profile.php
/**
 * Strona profilu użytkownika - wyświetla dane i formularz edycji.
 * Wersja: 1.1 (Oparta na dostarczonym kodzie, dodane komentarze)
 *
 * Oczekuje zmiennych z index.php:
 * @var array $tn_ustawienia_globalne Ustawienia globalne (nieużywane bezpośrednio, ale dostępne).
 * @var string $tn_token_csrf Token CSRF.
 * @var array $tn_dane_uzytkownika Dane aktualnie zalogowanego użytkownika
 * ['id'], ['username'], ['tn_imie_nazwisko'], ['email'], ['avatar']
 */

// Upewnij się, że funkcje pomocnicze istnieją (powinny być załadowane przez index.php)
if (!function_exists('tn_get_avatar_path')) {
     error_log("Krytyczny błąd: Brak funkcji tn_get_avatar_path() w tn_profile.php.");
     echo '<div class="alert alert-danger">Wystąpił błąd krytyczny. Skontaktuj się z administratorem.</div>';
     return;
}
if (!function_exists('tn_generuj_url')) {
    function tn_generuj_url(string $id, array $p=[]) { return '?page='.$id; } // Prosty fallback
}


// Przygotuj dane użytkownika do wyświetlenia, zabezpieczając przed XSS
$user_id = $tn_dane_uzytkownika['id'] ?? 0;
$username = htmlspecialchars($tn_dane_uzytkownika['username'] ?? 'Brak danych');
$fullname = htmlspecialchars($tn_dane_uzytkownika['tn_imie_nazwisko'] ?? '');
$email = htmlspecialchars($tn_dane_uzytkownika['email'] ?? '');
// Użyj funkcji pomocniczej do pobrania ścieżki avatara (z placeholderem)
$avatar_path = tn_get_avatar_path($tn_dane_uzytkownika['avatar'] ?? null);

?>
<h1 class="mb-4"><i class="bi bi-person-badge me-2"></i>Mój Profil</h1>

<div class="row g-4"> <?php // Dodano odstępy g-4 ?>

    <?php // --- Kolumna Lewa: Avatar i Podstawowe Info --- ?>
    <div class="col-lg-4">
        <div class="card shadow-sm text-center position-sticky" style="top: 80px;"> <?php // Przyklejona karta ?>
            <div class="card-body p-4">
                <img src="<?php echo $avatar_path; ?>" alt="<?php echo $username; ?>"
                     class="rounded-circle img-thumbnail mb-3 tn-profile-avatar bg-body-tertiary" <?php // Dodano tło dla placeholdera ?>
                     style="width: 150px; height: 150px; object-fit: cover; border-width: 3px;"> <?php // Pogrubiona ramka ?>
                <h5 class="card-title mb-1"><?php echo $fullname ?: $username; ?></h5>
                <p class="card-text text-muted mb-2"><?php echo $username; ?></p>
                <p class="card-text text-muted small">
                    <i class="bi bi-envelope me-1"></i> <?php echo $email ?: '<span class="fst-italic">Brak adresu e-mail</span>'; ?>
                </p>
                <hr class="my-3">
                <p class="card-text small text-muted">ID Użytkownika: <?php echo $user_id; ?></p>
            </div>
        </div>
    </div>

    <?php // --- Kolumna Prawa: Formularz Edycji --- ?>
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-light-subtle py-2"><h6 class="mb-0 fw-normal"><i class="bi bi-pencil-square me-2"></i>Edytuj Dane Profilu</h6></div>
            <div class="card-body p-4"> <?php // Zwiększono padding ?>
                <form method="POST" action="<?php echo tn_generuj_url('profile'); // Wysyłamy do tej samej strony ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile"> <?php // Akcja dla routera w index.php ?>
                    <input type="hidden" name="tn_csrf_token" value="<?php echo $tn_token_csrf; ?>">

                    <?php // Sekcja Danych Podstawowych ?>
                    <fieldset class="mb-4 pb-3 border-bottom">
                        <legend class="h6 fs-sm fw-bold mb-3">Dane Podstawowe</legend>
                        <div class="mb-3 row align-items-center">
                            <label for="tn_profile_username" class="col-sm-4 col-lg-3 col-form-label col-form-label-sm text-sm-end">Nazwa użytk.</label>
                            <div class="col-sm-8 col-lg-9">
                                <?php // Nazwa użytkownika jest tylko do odczytu ?>
                                <input type="text" readonly class="form-control-plaintext form-control-sm ps-2" id="tn_profile_username" value="<?php echo $username; ?>">
                                <div class="form-text small mt-0 text-muted">Nazwy użytkownika nie można zmienić.</div>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="tn_profile_fullname" class="col-sm-4 col-lg-3 col-form-label col-form-label-sm text-sm-end">Imię i Nazwisko</label>
                            <div class="col-sm-8 col-lg-9">
                                <input type="text" class="form-control form-control-sm" id="tn_profile_fullname" name="tn_imie_nazwisko" value="<?php echo $fullname; ?>" placeholder="Podaj imię i nazwisko">
                            </div>
                        </div>
                         <div class="mb-3 row">
                            <label for="tn_profile_email" class="col-sm-4 col-lg-3 col-form-label col-form-label-sm text-sm-end">Adres e-mail</label>
                            <div class="col-sm-8 col-lg-9">
                                <input type="email" class="form-control form-control-sm" id="tn_profile_email" name="email" value="<?php echo $email; ?>" placeholder="np. adres@email.com">
                            </div>
                        </div>
                    </fieldset>

                     <?php // Sekcja Zmiany Hasła ?>
                     <fieldset class="mb-4 pb-3 border-bottom">
                        <legend class="h6 fs-sm fw-bold mb-3">Zmiana Hasła (pozostaw puste, jeśli nie zmieniasz)</legend>
                        <div class="mb-3 row">
                            <label for="tn_profile_current_password" class="col-sm-4 col-lg-3 col-form-label col-form-label-sm text-sm-end">Bieżące hasło</label>
                            <div class="col-sm-8 col-lg-9">
                                <input type="password" class="form-control form-control-sm" id="tn_profile_current_password" name="current_password" aria-describedby="currentPasswordHelp" autocomplete="current-password" placeholder="Wymagane tylko do zmiany hasła">
                                <div id="currentPasswordHelp" class="form-text small text-muted"></div>
                            </div>
                        </div>
                         <div class="mb-3 row">
                            <label for="tn_profile_new_password" class="col-sm-4 col-lg-3 col-form-label col-form-label-sm text-sm-end">Nowe hasło</label>
                            <div class="col-sm-8 col-lg-9">
                                <input type="password" class="form-control form-control-sm" id="tn_profile_new_password" name="new_password" autocomplete="new-password" aria-describedby="newPasswordHelp">
                                <div id="newPasswordHelp" class="form-text small text-muted">Min. 8 znaków.</div>
                            </div>
                        </div>
                         <div class="mb-3 row">
                            <label for="tn_profile_confirm_password" class="col-sm-4 col-lg-3 col-form-label col-form-label-sm text-sm-end">Potwierdź nowe</label>
                            <div class="col-sm-8 col-lg-9">
                                <input type="password" class="form-control form-control-sm" id="tn_profile_confirm_password" name="confirm_password" autocomplete="new-password">
                            </div>
                        </div>
                    </fieldset>

                     <?php // Sekcja Zmiany Avatara ?>
                     <fieldset class="mb-4">
                        <legend class="h6 fs-sm fw-bold mb-3">Avatar</legend>
                        <div class="mb-3 row align-items-center">
                             <label for="tn_profile_avatar" class="col-sm-4 col-lg-3 col-form-label col-form-label-sm text-sm-end">Zmień avatar</label>
                             <div class="col-sm-8 col-lg-9">
                                <?php // Pole do wyboru pliku avatara ?>
                                <input class="form-control form-control-sm" type="file" id="tn_profile_avatar" name="avatar_file" accept="image/jpeg,image/png,image/gif,image/webp">
                                <div class="form-text small text-muted">Wybierz plik (JPG, PNG, GIF, WEBP, max 1MB). Zastąpi obecny.</div>
                            </div>
                        </div>
                    </fieldset>

                    <hr class="my-4"> <?php // Separator przed przyciskiem ?>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Zapisz zmiany w profilu</button>
                    </div>
                </form>
            </div> <?php // .card-body ?>
        </div> <?php // .card ?>
    </div> <?php // .col-lg-8 ?>
</div> <?php // .row ?>

<?php // Style dla avatara (mogą być w CSS) ?>
<style>
.tn-profile-avatar {
    border: 3px solid var(--bs-primary-bg-subtle);
    box-shadow: 0 0.125rem 0.5rem rgba(var(--bs-body-color-rgb), 0.15);
}
</style>