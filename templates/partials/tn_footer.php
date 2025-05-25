<?php
/**
 * Stopka aplikacji.
 * Zawiera informacje o prawach autorskich, wersję aplikacji
 * oraz dołącza niezbędne skrypty JavaScript.
 * Wersja: 1.55 (Szczegółowe informacje o autorze i anegdota)
 *
 * Oczekuje zmiennych z index.php:
 * @var array $tn_ustawienia_globalne
 */

$tn_tekst_stopki = $tn_ustawienia_globalne['tekst_stopki'] ?? ('' . date('Y') . ' TN® iMAG ');
$tn_wersja = defined('TN_WERSJA_APLIKACJI') ? htmlspecialchars(TN_WERSJA_APLIKACJI) : '1.7.0';
$tn_base_url = htmlspecialchars(rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));

?>
        <footer class="mt-auto py-3 px-4 tn-stopka border-top bg-body-tertiary">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start small text-muted">
                        <?php echo $tn_tekst_stopki; ?>
                    </div>
                    <div class="col-md-6 text-center text-md-end small text-muted">
                        Powered by <span class="link-secondary" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#authorInfoModal">
                            <img src="https://twoja-nazwa.pl/TNimg/logo5.png" width="45px" alt="Logo Autora">
                        </span>
                    </div>
                </div>
            </div>
        </footer>
    </div> </div>    <div class="modal fade" id="authorInfoModal" tabindex="-1" aria-labelledby="authorInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fs-5" id="authorInfoModalLabel"><i class="bi bi-person-circle me-2"></i> O Autorze</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <img src="https://twoja-nazwa.pl/TNimg/logo.png" class="rounded-circle mb-3 border border-secondary border-3" alt="TN" width="150px">
                    <h4 class="mt-2">Paweł "Drake" Plichta</h4>
                    <p class="text-muted">Developer, Programista</p>
                    <a href="https://twoja-nazwa.pl" class="btn btn-outline-primary btn-sm mt-2" target="_blank">Oficjalna strona</a>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">O mnie</h5>
                        <p class="card-text">
                            Zajmuję się tworzeniem nowoczesnych aplikacji internetowych, z naciskiem na systemy zarządzania
                            i optymalizację procesów biznesowych. Moja pasja to projektowanie i implementacja
                            rozwiązań, które są zarówno funkcjonalne, intuicyjne, jak i skalowalne.
                        </p>
                        <p class="card-text">
                            Szczególnie interesuję się optymalizacją baz danych, architekturą oprogramowania i tworzeniem
                            interfejsów użytkownika, które zwiększają produktywność i komfort pracy użytkowników.
                        </p>
                    </div>
                </div>

                <h6 class="mt-4">Technologie i narzędzia:</h6>
                <ul class="list-unstyled small text-muted">
                    <li><i class="bi bi-filetype-php me-2"></i> PHP</li>
                    <li><i class="bi bi-code-slash me-2"></i> Frontend: HTML, CSS, JavaScript</li>
                    <li><i class="bi bi-bootstrap me-2"></i> CSS: Bootstrap 5</li>
                    <li><i class="bi bi-database me-2"></i> Baza Danych</li>
                    <li><i class="bi bi-server me-2"></i> Serwer: Apache</li>
                    <li><i class="bi bi-code me-2"></i> Przyjazne URL, Routing, Funkcje pomocnicze, Akcje</li>
                    <li><i class="bi bi-git me-2"></i> System kontroli wersji: Git</li>
                    <li><i class="bi bi-github me-2"></i> GitHub</li>
                    <li><i class="bi bi-terminal me-2"></i> Konsola/Terminal</li>
                    <li><i class="bi bi-wordpress me-2"></i> WordPress</li>
                    <li><i class="bi bi-joystick me-2"></i> Symulacje/Gry 2D/3D</li>
                </ul>

                <h6 class="mt-4">Projekty:</h6>
                <ul class="list-unstyled small text-muted">
                    <li><a href="https://twoja-nazwa.pl" class="text-decoration-none link-secondary" target="_blank">TN iMAG</a> - System zarządzania magazynem</li>
                    <li class="mt-2">
                        <strong class="text-primary">Opis Projektu TN iMAG:</strong><br>
                        TN iMAG to autorski projekt systemu zarządzania magazynem, który powstał z potrzeby stworzenia
                        kompleksowego narzędzia optymalizującego procesy logistyczne i magazynowe. System ten
                        charakteryzuje się modułową budową, co pozwala na jego elastyczne dostosowanie do specyficznych
                        wymagań różnych przedsiębiorstw.
                    </li>
                    <li class="mt-2">
                       Główne założenia projektu obejmują:
                       <ul class="list-unstyled ms-3">
                            <li><i class="bi bi-check-circle-fill me-1 text-success"></i>  Efektywne zarządzanie produktami i ich lokalizacją w magazynie.</li>
                            <li><i class="bi bi-check-circle-fill me-1 text-success"></i>  Obsługa zamówień z automatyczną aktualizacją stanów magazynowych.</li>
                            <li><i class="bi bi-check-circle-fill me-1 text-success"></i>  Wsparcie dla zwrotów i reklamacji z uwzględnieniem korekty stanów.</li>
                            <li><i class="bi bi-check-circle-fill me-1 text-success"></i>  Intuicyjny interfejs użytkownika, ułatwiający codzienną pracę magazynierów i administratorów.</li>
                            <li><i class="bi bi-check-circle-fill me-1 text-success"></i>  Raportowanie i analiza danych, wspierające podejmowanie decyzji biznesowych.</li>
                       </ul>
                    </li>
                    <li class="mt-2">
                        System został zaprojektowany z myślą o skalowalności i możliwości integracji z innymi systemami
                        używanymi w przedsiębiorstwie, takimi jak systemy ERP czy księgowe.
                    </li>
                    </ul>

                <h6 class="mt-4">Anegdota:</h6>
                <p class="text-muted small">
                    Pamiętam, jak podczas tworzenia modułu zarządzania lokalizacjami w magazynie, spędziłem kilka godzin
                    nad optymalizacją algorytmu wyszukiwania najefektywniejszej ścieżki dla magazyniera. Po wielu
                    próbach i testach, udało się osiągnąć zadowalający rezultat, a system zaczął generować optymalne
                    trasy w ułamku sekundy. Satysfakcja z pokonania tego wyzwania była ogromna i utwierdziła mnie
                    w przekonaniu, że programowanie to nie tylko praca, ale przede wszystkim pasja i ciągłe
                    poszukiwanie najlepszych rozwiązań.
                </p>

                <h6 class="mt-4">Kontakt:</h6>
                <ul class="list-unstyled small text-muted">
                    <li><i class="bi bi-envelope me-1"></i> Email: <a href="mailto:drake@twoja-nazwa.pl" class="link-secondary">drake@twoja-nazwa.pl</a></li>
                    <li><i class="bi bi-globe me-1"></i> Strona WWW: <a href="https://twoja-nazwa.pl" class="link-secondary" target="_blank">https://twoja-nazwa.pl</a></li>
                    <li><i class="bi bi-github me-1"></i> GitHub: <a href="https://github.com/twojanazwa" class="link-secondary" target="_blank">https://github.com/twojanazwa</a></li>
                </ul>

                <hr class="my-4">
                <div class="text-center small text-muted">
                    <p>
                        Zgłaszanie błędów: <i class="bi bi-envelope me-1"></i>
                        <a href="mailto:pomoc@twoja-nazwa.pl" class="link-secondary">pomoc@twoja-nazwa.pl</a>
                    </p>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Zamknij</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<?php
$tn_sciezka_js = 'public/js/tn_scripts.js';
$tn_pełna_sciezka_js = __DIR__ . '/../../' . $tn_sciezka_js;
$tn_wersja_js = file_exists($tn_pełna_sciezka_js) ? filemtime($tn_pełna_sciezka_js) : time();
?>
<script src="../../public/js/tn_scripts.js?v=<?php echo htmlspecialchars($tn_wersja_js); ?>"></script>
</body>
</html>
