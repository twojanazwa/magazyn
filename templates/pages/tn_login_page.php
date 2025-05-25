<?php
/**
 * Szablon strony logowania - układ dwukolumnowy.
 * Wersja: 1.4 - Ulepszona interaktywność JS.
 * @autor: Paweł Plichta/TN iMAG
 * @licencja: wszelkie prawa zastrzeżone
 * @wersja: 1.1.1
 * @app: tnApp
 */


$tn_site_name = $tn_ustawienia_globalne['nazwa_strony'] ?? 'TN iMAG';

$tn_favicon_path = '/TNimg/favicon.ico';
$tn_logo_path = $tn_ustawienia_globalne['logo_login_path'] ?? '/TNimg/logo_login2.png'; 
$tn_login_css_path = '/public/css/tn_login_styles.css';
$tn_login_js_path = '/public/js/tn_login_scripts.js';

// Wersjonowanie CSS/JS dla cache busting
$tn_css_version = '';
$css_file_path = defined('TN_KORZEN_APLIKACJI') ? TN_KORZEN_APLIKACJI . $tn_login_css_path : __DIR__ . '/../..' . $tn_login_css_path;
if (file_exists($css_file_path)) {
    $tn_css_version = '?v=' . filemtime($css_file_path);
}

$tn_js_version = '';
$js_file_path = defined('TN_KORZEN_APLIKACJI') ? TN_KORZEN_APLIKACJI . $tn_login_js_path : __DIR__ . '/../..' . $tn_login_js_path;
if (file_exists($js_file_path)) {
    $tn_js_version = '?v=' . filemtime($js_file_path);
}

// Inicjalizacja tokenu CSRF (przykład - faktyczna logika generowania powinna być w kontrolerze)
if (!isset($tn_token_csrf)) {
    // Generuj token CSRF jeśli nie został wcześniej ustawiony
    // Ta logika powinna być częścią kontrolera, który ładuje ten szablon
     $_SESSION['tn_csrf_token'] = bin2hex(random_bytes(32)); 
     $tn_token_csrf = $_SESSION['tn_csrf_token'];
    $tn_token_csrf = 'tokentest010120392130'; // Zastąp to prawdziwą logiką
}

?>
<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie do Panelu TN® iMAG </title>
    <link rel="icon" href="<?= $tn_favicon_path; ?>" sizes="any">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= $tn_login_css_path . $tn_css_version; ?>">
</head>
<body class="tn-login-page-body">

    <div class="container-fluid">
        <div class="row min-vh-100 align-items-stretch">

        
            <div class="col-lg-5 col-md-6 d-flex align-items-center justify-content-center tn-login-form-col">
                <div class="tn-login-form-wrapper">

                    <div class="tn-login-header">
                        <img src="<?= $tn_logo_path; ?>" alt="<?= htmlspecialchars($tn_site_name); ?>" style="opacity: 99%" class="tn-login-logo">

                      
                    </div>

                    <div class="tn-flash-container-login">
                    <?php
                   
                    $komunikaty_do_wyswietlenia = $GLOBALS['tn_komunikaty_flash'] ?? ($_SESSION['tn_komunikaty_flash'] ?? []);
                    if (!empty($komunikaty_do_wyswietlenia) && is_array($komunikaty_do_wyswietlenia)) {
                        foreach ($komunikaty_do_wyswietlenia as $tn_komunikat) {
                            if (is_array($tn_komunikat)) {
                                $tn_typ = htmlspecialchars($tn_komunikat['type'] ?? 'danger');
                                $tn_wiadomosc = htmlspecialchars($tn_komunikat['message'] ?? ''); // Zabezpiecz wiadomość
                                $tn_ikona = ($tn_typ === 'danger' || $tn_typ === 'warning') ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill';
                                $alert_class = 'alert-' . $tn_typ;
                                ?>
                                <div class="alert <?= $alert_class; ?> alert-dismissible fade show d-flex align-items-center" role="alert">
                                    <i class="bi <?= $tn_ikona; ?> me-2"></i>
                                    <div class="flex-grow-1"><?= $tn_wiadomosc; ?></div>
                                    <button type="button" class="btn-close btn-sm p-1" data-bs-dismiss="alert" aria-label="Zamknij"></button>
                                </div>
                                <?php
                            }
                        }
                        
                         unset($GLOBALS['tn_komunikaty_flash']);
                         unset($_SESSION['tn_komunikaty_flash']);
                    }
                    ?>
                    </div>

                    <form method="POST" action="" novalidate class="needs-validation" id="loginForm">
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="tn_csrf_token" value="<?= htmlspecialchars($tn_token_csrf, ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control form-control-sm" id="tn_username" name="tn_username" placeholder="Nazwa użytkownika" required autofocus autocomplete="username">
                            <label for="tn_username"><i class="bi bi-person me-1"></i>Nazwa użytkownika</label>
                            <div class="invalid-feedback">Podaj nazwę użytkownika.</div>
                            <?php // Można tu dodać komunikat błędu z backendu, np. <div class="invalid-feedback d-block">Błąd logowania: Nieprawidłowy login.</div> ?>
                        </div>

                        <div class="form-floating mb-4 position-relative">
                            <input type="password" class="form-control form-control-sm" id="tn_password" name="tn_password" placeholder="Hasło" required autocomplete="current-password">
                            <label for="tn_password"><i class="bi bi-key me-1"></i>Hasło</label>
                            <button type="button" class="tn-password-toggle" id="tn-toggle-password" aria-label="Pokaż/ukryj hasło">
                                <i class="bi bi-eye-slash" aria-hidden="true"></i>
                            </button>
                            <div class="invalid-feedback">Podaj hasło.</div>
                             <?php // Można tu dodać komunikat błędu z backendu, np. <div class="invalid-feedback d-block">Błąd logowania: Nieprawidłowe hasło.</div> ?>
                        </div>

                        <div class="d-grid mt-4">
                            <button class="btn btn-primary btn-login btn-sm" type="submit" id="loginSubmitBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                Zaloguj się
                            </button>
                        </div>
                    </form>

                    <div class="tn-login-footer">
                        <Small>&copy; <?= date('Y'); ?> tnApp. Wszelkie prawa zastrzeżone.</small>
                    </div>

                </div> <?php // .tn-login-form-wrapper ?>
            </div> <?php // .tn-login-form-col ?>


            <?php // --- PRAWA KOLUMNA: POWITANIE --- ?>
            <div class="col-lg-7 col-md-6 d-none d-md-flex align-items-center justify-content-center tn-welcome-col">
                <div class="tn-welcome-content">
                    <i class="bi bi-shield-lock-fill tn-welcome-icon"></i> <?php // Lub inna ikona np. bi-building ?>

		<h3>TNiMAG </h3><h5>Twoje centrum dowodzenia e-commerce i logistyką</h5>


                    <span class="tn-welcome-text">
                     
                    </span>
                     <small> Kompleksowe narzędzie do efektywnego zarządzania magazynem, produktami, zamówieniami oraz zwrotami i reklamacjami.<Br />  Zwiększ kontrolę i oszczędź czas dzięki intuicyjnemu pulpitowi, szczegółowym modułom i łatwemu śledzeniu procesów.</small>
                </div>
            </div> <?php // .tn-welcome-col ?>

        </div> <?php // .row ?>
    </div> <?php // .container-fluid ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <script src="<?= $tn_login_js_path . $tn_js_version; ?>"></script>

</body>
</html>