<?php
/**
 * templates/pages/tn_help_page.php
 * Ulepszona i szczegółowa strona pomocy aplikacji TN® iMAG.
 * Wersja: 2.1 (Zaktualizowano treści i dodano szczegóły)
 *
 * Zawiera spis treści, podział na sekcje, FAQ i szczegółowe opisy.
 */

// Upewnij się, że funkcje pomocnicze są dostępne (powinny być załadowane w index.php)
if (!function_exists('tn_generuj_url')) {
    // Prosta definicja zastępcza, jeśli funkcja nie została załadowana
    function tn_generuj_url(string $id, array $p = []){
        return '?page=' . urlencode($id) . '&' . http_build_query($p);
    }
}
// Zakładamy, że tn_mapuj_status_zam_na_kolor i tn_mapuj_status_zwr_na_kolor są dostępne
// Jeśli nie, można dodać proste definicje zastępcze lub upewnić się, że są ładowane globalnie.
if (!function_exists('tn_mapuj_status_zam_na_kolor')) {
     function tn_mapuj_status_zam_na_kolor(string $status): string { return 'secondary'; } // Domyślny kolor szary
}
if (!function_exists('tn_mapuj_status_zwr_na_kolor')) {
     function tn_mapuj_status_zwr_na_kolor(string $status): string { return 'secondary'; } // Domyślny kolor szary
}


// Przygotowanie linków do głównych sekcji aplikacji
$link_dashboard = tn_generuj_url('dashboard'); // Zakładamy, że 'dashboard' to ID strony pulpitu
$link_produkty = tn_generuj_url('products'); // Zakładamy, że 'products' to ID strony listy produktów
$link_zamowienia = tn_generuj_url('orders'); // Zakładamy, że 'orders' to ID strony listy zamówień
$link_magazyn = tn_generuj_url('warehouse_view'); // Zakładamy, że 'warehouse_view' to ID strony widoku magazynu
$link_zwroty = tn_generuj_url('returns_list'); // Zakładamy, że 'returns_list' to ID strony listy zwrotów
$link_kurierzy = tn_generuj_url('couriers_list'); // Zakładamy, że 'couriers_list' to ID strony listy kurierów
$link_ustawienia = tn_generuj_url('settings'); // Zakładamy, że 'settings' to ID strony ustawień
$link_profil = tn_generuj_url('profile'); // Zakładamy, że 'profile' to ID strony profilu
$link_info = tn_generuj_url('info'); // Zakładamy, że 'info' to ID strony informacji systemowych
$link_return_form_new = tn_generuj_url('return_form_new'); // Zakładamy, że 'return_form_new' to ID strony formularza nowego zwrotu


?>

<div class="container-fluid px-lg-4 py-4">

    <?php // Nagłówek strony ?>
    <h1 class="mt-4 mb-4"><i class="bi bi-question-circle me-3"></i>Pomoc i Wsparcie</h1>

    <div class="row g-4">

        <?php // Lewa kolumna - Spis treści (widoczny na większych ekranach) ?>
        <div class="col-lg-3 d-none d-lg-block">
            <div class="position-sticky" style="top: 80px;"> <?php // Utrzymuje spis treści na widoku podczas przewijania ?>
                <div class="card shadow-sm">
                    <div class="card-header py-2 bg-light-subtle"><h6 class="mb-0 fw-semibold">Spis treści:</h6></div>
                    <div class="list-group list-group-flush small" style="max-height: 70vh; overflow-y: auto;"> <?php // Ogranicza wysokość i dodaje przewijanie ?>
                        <a href="#sekcja-wprowadzenie" class="list-group-item list-group-item-action py-2">Wprowadzenie</a>
                        <a href="#sekcja-dashboard" class="list-group-item list-group-item-action py-2">Pulpit (Dashboard)</a>
                        <a href="#sekcja-produkty" class="list-group-item list-group-item-action py-2">Zarządzanie Produktami</a>
                        <a href="#sekcja-zamowienia" class="list-group-item list-group-item-action py-2">Obsługa Zamówień</a>
                        <a href="#sekcja-magazyn" class="list-group-item list-group-item-action py-2">Zarządzanie Magazynem</a>
                        <a href="#sekcja-zwroty" class="list-group-item list-group-item-action py-2">Zwroty i Reklamacje</a>
                        <a href="#sekcja-kurierzy" class="list-group-item list-group-item-action py-2">Zarządzanie Kurierami</a>
                        <a href="#sekcja-ustawienia" class="list-group-item list-group-item-action py-2">Ustawienia Aplikacji</a>
                        <a href="#sekcja-profil" class="list-group-item list-group-item-action py-2">Mój Profil</a>
                        <a href="#sekcja-faq" class="list-group-item list-group-item-action py-2">Najczęściej Zadawane Pytania (FAQ)</a>
                        <a href="#sekcja-problemy" class="list-group-item list-group-item-action py-2">Rozwiązywanie Problemów</a>
                        <a href="#sekcja-kontakt" class="list-group-item list-group-item-action py-2">Kontakt i Wsparcie</a>
                    </div>
                </div>
            </div>
        </div>

        <?php // Prawa kolumna - Treść pomocy ?>
        <div class="col-lg-9">

            <?php // Sekcja: Wprowadzenie ?>
            <section id="sekcja-wprowadzenie" class="mb-5">
                <h2 class="h4 border-bottom pb-2 mb-3"><i class="bi bi-info-circle me-2"></i>Wprowadzenie</h2>
                <p>Witaj w systemie <strong>TN® iMAG</strong> - Twoim kompleksowym narzędziu do zarządzania magazynem, produktami, zamówieniami oraz zwrotami i reklamacjami. Ta strona pomocy ma na celu przybliżyć Ci funkcjonalności aplikacji i ułatwić codzienną pracę.</p>
                <p>System został zaprojektowany z myślą o prostocie i efektywności, pomagając Ci śledzić stany magazynowe, zarządzać procesem realizacji zamówień i kontrolować kluczowe wskaźniki Twojej działalności.</p>
                <div class="alert alert-primary small py-2" role="alert">
                    <i class="bi bi-lightbulb me-1"></i> Na większych ekranach po lewej stronie znajdziesz interaktywny spis treści, który pozwoli Ci szybko nawigować między sekcjami pomocy.
                </div>
            </section>

            <?php // Sekcja: Pulpit (Dashboard) ?>
            <section id="sekcja-dashboard" class="mb-5">
                <h2 class="h4 border-bottom pb-2 mb-3"><i class="bi bi-speedometer2 me-2"></i>Pulpit (Dashboard)</h2>
                <p>Pulpit to pierwsza strona, którą widzisz po zalogowaniu. Stanowi on skondensowane centrum informacji o stanie Twojego magazynu i sprzedaży.</p>
                <ul>
                    <li><strong>Kluczowe Wskaźniki (KPI):</strong> Zestaw kart prezentujących najważniejsze dane liczbowe, takie jak:
                        <ul>
                            <li>Łączna liczba produktów w bazie.</li>
                            <li>Łączna liczba wszystkich zamówień.</li>
                            <li>Liczba nowych, nieprzetworzonych zamówień.</li>
                            <li>Łączna liczba zgłoszeń zwrotów/reklamacji.</li>
                            <li>Szacowana wartość całego magazynu (na podstawie cen produktów i ich stanów).</li>
                            <li>Procentowe zapełnienie lokalizacji magazynowych.</li>
                        </ul>
                    </li>
                     <li><strong>Podsumowanie Sprzedaży:</strong> Szczegółowa tabela prezentująca kluczowe wskaźniki finansowe obliczone na podstawie zrealizowanych zamówień i ustawień aplikacji, w tym:
                        <ul>
                            <li>Całkowita wartość sprzedaży.</li>
                            <li>Szacowana prowizja (na podstawie procentu z ustawień).</li>
                            <li>Szacowane koszty wysyłki.</li>
                            <li>Szacowany koszt materiałów do pakowania (na podstawie procentu z ustawień).</li>
                            <li>Miesięczny koszt magazynowania (wartość stała z ustawień).</li>
                            <li>Szacowany zysk przed opodatkowaniem.</li>
                            <li>Szacowany podatek dochodowy (na podstawie procentu z ustawień i zysku przed opodatkowaniem).</li>
                            <li>Szacowany zysk netto.</li>
                        </ul>
                        <div class="alert alert-info small py-1 mt-2" role="alert"><i class="bi bi-info-circle me-1"></i> Wskaźniki finansowe są szacunkowe i bazują na danych wprowadzonych do systemu oraz konfiguracji w Ustawieniach.</div>
                    </li>
                    <li><strong>Szybkie Notatki:</strong> Obszar tekstowy do zapisywania krótkich notatek lub przypomnień. Notatki są automatycznie zapisywane podczas pisania.</li>
                    <li><strong>Alerty Systemowe:</strong> Sekcje informujące o ważnych zdarzeniach, takich jak:
                        <ul>
                            <li>Produkty, których stan magazynowy spadł poniżej ustalonego progu niskiego stanu.</li>
                            <li>Nowe zamówienia oczekujące na przetworzenie.</li>
                             <li>Nowe zgłoszenia zwrotów lub reklamacji.</li>
                        </ul>
                    </li>
                    <li><strong>Ostatnie Aktywności:</strong> Listy prezentujące ostatnio dodane zamówienia, zwroty/reklamacje oraz produkty, umożliwiające szybki podgląd szczegółów.</li>
                     <li><strong>Top Sprzedające się Produkty:</strong> Lista produktów, które sprzedały się w największej liczbie sztuk (na podstawie zrealizowanych zamówień).</li>
                     <li><strong>Informacje o Połączeniu i Systemie:</strong> Podstawowe dane techniczne dotyczące Twojego połączenia i środowiska, w którym działa aplikacja.</li>
                </ul>
                <p><a href="<?php echo $link_dashboard; ?>" class="btn btn-sm btn-outline-secondary">Przejdź do Pulpitu <i class="bi bi-arrow-right-short"></i></a></p>
            </section>

            <?php // Sekcja: Produkty ?>
            <section id="sekcja-produkty" class="mb-5">
                <h2 class="h4 border-bottom pb-2 mb-3"><i class="bi bi-boxes me-2"></i>Zarządzanie Produktami</h2>
                <p>Moduł produktów pozwala na kompleksowe zarządzanie katalogiem części samochodowych i innych towarów w Twoim magazynie.</p>
                <ul>
                    <li><strong>Lista Produktów:</strong> Główny widok prezentuje tabelę wszystkich produktów z kluczowymi informacjami (ID, Nazwa, Producent, Cena, Stan, Kategoria).
                        <ul>
                            <li><strong>Sortowanie i Wyszukiwanie:</strong> Kliknij nagłówki kolumn, aby posortować listę. Pole wyszukiwania (jeśli zaimplementowane) pozwala szybko znaleźć produkty po nazwie, numerze katalogowym itp.</li>
                        </ul>
                    </li>
                    <li><strong>Dodawanie Nowego Produktu:</strong> Użyj przycisku <button class="btn btn-primary btn-sm py-0 px-1 disabled"><i class="bi bi-plus-circle me-1"></i>Dodaj Produkt</button>. Wypełnij formularz podając:
                        <ul>
                            <li><code>Nazwa części</code> (wymagane)</li>
                            <li><code>Numer katalogowy części</code> (wymagane, unikalny identyfikator produktu)</li>
                            <li><code>Numery katalogowe zamienników</code> (opcjonalnie)</li>
                            <li><code>Numery katalogowe oryginału</code> (opcjonalnie)</li>
                            <li><code>Producent</code> (wymagane)</li>
                            <li><code>Cena</code> (wymagane, wartość sprzedaży w <?php echo $tn_waluta; ?>)</li>
                            <li><code>Koszt wysyłki</code> (wymagane, domyślny koszt wysyłki dla tego produktu)</li>
                            <li><code>Ilość</code> (wymagane, początkowy stan magazynowy)</li>
                            <li><code>Jednostka miary</code> (np. szt., kpl.)</li>
                            <li><code>Kategoria</code> (wybierz z listy zdefiniowanej w Ustawieniach)</li>
                            <li><strong>Zdjęcia:</strong> Możesz przesłać do 5 zdjęć produktu w popularnych formatach (JPG, PNG, GIF, WEBP) o maksymalnym rozmiarze 1MB każde. Po przesłaniu zobaczysz podgląd i będziesz mógł wybrać zdjęcie główne.</li>
                            <li><strong>Opis produktu:</strong> Szczegółowy opis tekstowy.</li>
                            <li><strong>Pasuje do pojazdów:</strong> Informacje o kompatybilności z modelami samochodów.</li>
                            <li><strong>Parametry:</strong> Dodatkowe pola do specyfikacji technicznych.</li>
                        </ul>
                         Przy dodawaniu możesz od razu przypisać produkt do wolnej lokalizacji magazynowej.
                    </li>
                    <li><strong>Edycja Produktu:</strong> Na liście produktów kliknij ikonę <button class="btn btn-warning btn-sm py-0 px-1 disabled"><i class="bi bi-pencil"></i></button> przy wybranym produkcie. Otworzy się formularz edycji z aktualnymi danymi. Pamiętaj o zapisaniu zmian.</li>
                    <li><strong>Usuwanie Produktu:</strong> Kliknij ikonę <button class="btn btn-danger btn-sm py-0 px-1 disabled"><i class="bi bi-trash"></i></button> na liście produktów. Zostaniesz poproszony o potwierdzenie. Pamiętaj, że usunięcie produktu jest **nieodwracalne** i może wpłynąć na historyczne dane zamówień i zwrotów.</li>
                    <li><strong>Podgląd Produktu:</strong> Kliknięcie na nazwę produktu lub ikonę <button class="btn btn-info btn-sm py-0 px-1 disabled"><i class="bi bi-eye"></i></button> przenosi do szczegółowego widoku produktu, gdzie znajdziesz wszystkie informacje, w tym przypisane lokalizacje magazynowe. Nieużywane sekcje opisu/parametrów są ukryte.</li>
                    <li><strong>Import Produktów:</strong> Użyj przycisku <button class="btn btn-success btn-sm py-0 px-1 disabled"><i class="bi bi-upload me-1"></i>Importuj z JSON</button>, aby zaimportować dane produktów z pliku JSON. Sprawdź wymagany format pliku opisany w modalu importu. System zaktualizuje istniejące produkty po ID i doda nowe.</li>
                    <li><strong>Stany Magazynowe:</strong> Kolumna "Stan" na liście pokazuje aktualną ilość produktu. Stan magazynowy jest automatycznie aktualizowany podczas realizacji zamówień (status "Zrealizowane") oraz podczas obsługi zwrotów (jeśli ta funkcja zostanie w pełni zaimplementowana).</li>
                </ul>
                 <p><a href="<?php echo $link_produkty; ?>" class="btn btn-sm btn-outline-secondary">Przejdź do Produktów <i class="bi bi-arrow-right-short"></i></a></p>
            </section>

             <?php // Sekcja: Zamówienia ?>
            <section id="sekcja-zamowienia" class="mb-5">
                <h2 class="h4 border-bottom pb-2 mb-3"><i class="bi bi-receipt me-2"></i>Obsługa Zamówień</h2>
                <p>Ten moduł służy do zarządzania zamówieniami klientów, od momentu ich złożenia do realizacji i wysyłki.</p>
                 <ul>
                    <li><strong>Lista Zamówień:</strong> Wyświetla wszystkie zamówienia z kluczowymi informacjami (ID, Data, Klient, Produkt, Ilość, Status). Możesz filtrować listę według statusu za pomocą przycisków nad tabelą.</li>
                    <li><strong>Dodawanie Nowego Zamówienia:</strong> Kliknij <button class="btn btn-primary btn-sm py-0 px-1 disabled"><i class="bi bi-plus-circle me-1"></i>Dodaj Zamówienie</button>. W formularzu:
                        <ul>
                            <li>Wybierz produkt z listy dostępnych (tylko te z dodatnim stanem magazynowym).</li>
                            <li>Podaj zamówioną ilość.</li>
                            <li>Wprowadź dane klienta (imię, nazwisko, adres, kontakt).</li>
                            <li>Wybierz <strong>Status realizacji</strong> (np. Nowe, W przygotowaniu, Zrealizowane, Anulowane).</li>
                            <li>Wybierz opcjonalnie <strong>Status płatności</strong> (np. Opłacone, Nieopłacone).</li>
                            <li>Możesz przypisać <strong>Kuriera</strong> (z listy aktywnych kurierów zdefiniowanych w Ustawieniach) i podać <strong>Numer przesyłki</strong>.</li>
                        </ul>
                    </li>
                    <li><strong>Edycja Zamówienia:</strong> Użyj ikony <button class="btn btn-warning btn-sm py-0 px-1 disabled"><i class="bi bi-pencil"></i></button> na liście. Możesz modyfikować wszystkie dane zamówienia.</li>
                    <li><strong>Usuwanie Zamówienia:</strong> Ikona <button class="btn btn-danger btn-sm py-0 px-1 disabled"><i class="bi bi-trash"></i></button>. Usunięcie zamówienia jest **nieodwracalne**.</li>
                    <li><strong>Podgląd Zamówienia:</strong> Kliknij ID zamówienia lub ikonę <button class="btn btn-info btn-sm py-0 px-1 disabled"><i class="bi bi-eye"></i></button>, aby zobaczyć wszystkie szczegóły zamówienia, w tym dane produktu, klienta, adres wysyłki, statusy oraz link do śledzenia przesyłki (jeśli kurier i numer są przypisane).</li>
                    <li><strong>Statusy Realizacji:</strong> Kluczowe dla zarządzania procesem.
                        <ul>
                            <li><code>Nowe</code>: Domyślny status po dodaniu zamówienia.</li>
                            <li><code>W przygotowaniu</code>: Zamówienie jest kompletowane w magazynie.</li>
                            <li><code>Zrealizowane</code>: Zamówienie zostało skompletowane i wysłane. **Uwaga:** Ustawienie tego statusu **automatycznie zmniejsza stan magazynowy** zamówionego produktu o zamówioną ilość.</li>
                            <li><code>Anulowane</code>: Zamówienie zostało anulowane. Stan magazynowy **nie jest zmieniany** lub jest przywracany, jeśli zamówienie było wcześniej "Zrealizowane".</li>
                        </ul>
                         Statusy można dodawać i edytować w Ustawieniach.
                    </li>
                    <li><strong>Statusy Płatności:</strong> Dodatkowe pole do śledzenia statusu płatności. Statusy można konfigurować w Ustawieniach.</li>
                    <li><strong>Kurier i Śledzenie:</strong> Po przypisaniu aktywnego kuriera i numeru przesyłki, system wygeneruje link do śledzenia na podstawie wzorca URL zdefiniowanego dla kuriera w Ustawieniach.</li>
                    <li><strong>Zgłoszenie Zwrotu/Reklamacji:</strong> Z poziomu podglądu zamówienia możesz szybko przejść do formularza dodawania nowego zgłoszenia zwrotu/reklamacji powiązanego z tym zamówieniem.</li>
                </ul>
                 <p><a href="<?php echo $link_zamowienia; ?>" class="btn btn-sm btn-outline-secondary">Przejdź do Zamówień <i class="bi bi-arrow-right-short"></i></a></p>
            </section>

            <?php // Sekcja: Magazyn ?>
            <section id="sekcja-magazyn" class="mb-5">
                <h2 class="h4 border-bottom pb-2 mb-3"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Zarządzanie Magazynem</h2>
                <p>Sekcja magazynu pozwala na wizualizację struktury magazynu, zarządzanie regałami, lokalizacjami oraz przypisywanie produktów do konkretnych miejsc.</p>
                 <ul>
                    <li><strong>Widok Magazynu:</strong> Główny ekran przedstawia graficzną reprezentację Twoich regałów i wszystkich zdefiniowanych lokalizacji. Każda lokalizacja jest oznaczona kolorem wskazującym jej aktualny status:
                        <ul>
                            <li><span class="badge bg-success">Wolne</span>: Lokalizacja jest pusta i gotowa do przyjęcia produktu.</li>
                            <li><span class="badge bg-secondary">Zajęte</span>: W lokalizacji znajduje się produkt. Kliknięcie na zajętą lokalizację wyświetli szczegóły przypisanego produktu (nazwa, ID, ilość, miniatura zdjęcia).</li>
                            <li><span class="badge bg-danger">Zablokowane</span>: Lokalizacja jest tymczasowo wyłączona z użytku (np. uszkodzona). Nie można do niej przypisać produktu.</li>
                            <li><span class="badge bg-warning text-dark">Błąd</span>: Wystąpiła niespójność danych (np. lokalizacja ma przypisany produkt o ID, które nie istnieje w bazie produktów).</li>
                        </ul>
                    </li>
                     <li><strong>Zarządzanie Regałami:</strong> Dostępne z poziomu widoku magazynu.
                         <ul>
                             <li><strong>Dodawanie Regału:</strong> Kliknij <button class="btn btn-dark btn-sm py-0 px-1 disabled"><i class="bi bi-plus-lg me-1"></i>Dodaj Regał</button>. Podaj unikalne <code>ID Regału</code> (np. R01, A-1 - używaj tylko liter, cyfr i myślników/podkreślników; ID jest niezmienialne po utworzeniu) i opcjonalny opis.</li>
                             <li><strong>Edycja Regału:</strong> Kliknij ID regału w tabeli "Zdefiniowane Regały", aby edytować jego opis.</li>
                              <li><strong>Usuwanie Regału:</strong> Kliknij ikonę <button class="btn btn-danger btn-sm py-0 px-1 disabled"><i class="bi bi-trash"></i></button> obok regału. **Uwaga:** Usunięcie regału jest **nieodwracalne** i **usunie również wszystkie lokalizacje** do niego przypisane oraz zwolni powiązane produkty z tych lokalizacji w danych magazynowych!</li>
                         </ul>
                    </li>
                     <li><strong>Zarządzanie Lokalizacjami:</strong> Dostępne z poziomu zarządzania regałami.
                        <ul>
                             <li><strong>Generowanie Lokalizacji:</strong> Kliknij <button class="btn btn-info btn-sm py-0 px-1 disabled"><i class="bi bi-magic me-1"></i>Generuj Lokalizacje</button>. Wybierz istniejący regał, podaj liczbę poziomów (np. 5) i liczbę miejsc na każdym poziomie (np. 10). System automatycznie utworzy unikalne ID lokalizacji (np. R01-S01-P01 do R01-S05-P10), używając domyślnych prefixów z Ustawień.</li>
                             <li><strong>ID Lokalizacji:</strong> Składa się z ID regału, prefixu i numeru poziomu oraz prefixu i numeru miejsca (np. <code>R01-S02-P05</code> oznacza Regał R01, Sekcja 02, Poziom 05).</li>
                             <li><strong>Zmiana Statusu Lokalizacji:</strong> Kliknięcie na lokalizację w widoku magazynu (inną niż "Zajęta") pozwala na zmianę jej statusu (np. na "Zablokowane").</li>
                        </ul>
                    </li>
                     <li><strong>Przypisywanie Produktu do Lokalizacji:</strong> W widoku magazynu kliknij na **wolną** lokalizację (zieloną). Otworzy się modal, w którym wybierzesz produkt z listy (tylko produkty z dodatnim stanem magazynowym) i podasz ilość sztuk tego produktu, którą chcesz umieścić w tej konkretnej lokalizacji. Kliknij "Przypisz". Lokalizacja zmieni kolor na szary ("Zajęte"), a informacja o przypisaniu pojawi się w szczegółach produktu.</li>
                     <li><strong>Zdejmowanie Produktu z Lokalizacji:</strong> W widoku magazynu kliknij na **zajętą** lokalizację (szarą). Pojawi się informacja o produkcie i przycisk <button class="btn btn-danger btn-sm py-0 px-1 disabled">Zwolnij Miejsce</button>. Kliknięcie zwolni lokalizację, ustawiając jej status na "Wolne", ale **nie wpłynie** na ogólny stan magazynowy produktu w bazie danych. Ta akcja służy do fizycznego usunięcia produktu z konkretnego miejsca w magazynie, np. w celu wysyłki lub przeniesienia.</li>
                     <li><strong>Przesuwanie Produktu (Manualne):</strong> Aby przesunąć produkt z jednej lokalizacji do drugiej, musisz najpierw "Zwolnić Miejsce" w dotychczasowej lokalizacji, a następnie "Przypisać Produkt" do nowej wolnej lokalizacji. Pamiętaj o ręcznej korekcie stanu magazynowego produktu, jeśli przesuwasz tylko część ilości.</li>
                </ul>
                 <p><a href="<?php echo $link_magazyn; ?>" class="btn btn-sm btn-outline-secondary">Przejdź do Magazynu <i class="bi bi-arrow-right-short"></i></a></p>
            </section>

             <?php // Sekcja: Zwroty i Reklamacje ?>
             <section id="sekcja-zwroty" class="mb-5">
                <h2 class="h4 border-bottom pb-2 mb-3"><i class="bi bi-arrow-repeat me-2"></i>Zwroty i Reklamacje</h2>
                <p>Moduł ten umożliwia rejestrowanie i śledzenie zgłoszeń zwrotów towarów oraz reklamacji od klientów.</p>
                 <ul>
                    <li><strong>Lista Zgłoszeń:</strong> Wyświetla wszystkie zarejestrowane zgłoszenia zwrotów i reklamacji z kluczowymi informacjami (ID, Typ, Data, Klient, Powiązane Zamówienie, Status).</li>
                    <li><strong>Dodawanie Nowego Zgłoszenia:</strong> Kliknij przycisk <a href="<?php echo $link_return_form_new; ?>" class="btn btn-warning btn-sm py-0 px-1 text-dark disabled"><i class="bi bi-journal-plus me-1"></i>Dodaj Zgłoszenie</a>. W formularzu:
                        <ul>
                            <li>Wybierz <strong>Typ zgłoszenia</strong> (Zwrot lub Reklamacja).</li>
                            <li>Wybierz <strong>Powiązane zamówienie</strong> (opcjonalnie, ale zalecane dla lepszego śledzenia).</li>
                            <li>Wybierz <strong>Produkt</strong>, którego dotyczy zgłoszenie.</li>
                            <li>Podaj <strong>Ilość</strong> zwracanego/reklamowanego produktu.</li>
                            <li>Wprowadź dane klienta (imię, nazwisko, kontakt).</li>
                            <li>Opisz <strong>Przyczynę zgłoszenia</strong>.</li>
                            <li>Opisz <strong>Oczekiwane rozwiązanie</strong> (np. zwrot pieniędzy, wymiana produktu).</li>
                            <li>Wybierz <strong>Status zgłoszenia</strong> (domyślnie "Nowe zgłoszenie", statusy można konfigurować w Ustawieniach).</li>
                            <li>Podaj <strong>Kwotę zwrotu (refund_amount)</strong>, jeśli dokonano lub planowany jest zwrot środków. Ta wartość jest sumowana na pulpicie.</li>
                        </ul>
                    </li>
                    <li><strong>Edycja Zgłoszenia:</strong> Na liście zgłoszeń kliknij ikonę <button class="btn btn-warning btn-sm py-0 px-1 disabled"><i class="bi bi-pencil"></i></button>. Możesz modyfikować wszystkie dane zgłoszenia.</li>
                    <li><strong>Podgląd Zgłoszenia:</strong> Kliknij ID zgłoszenia lub ikonę <button class="btn btn-info btn-sm py-0 px-1 disabled"><i class="bi bi-eye"></i></button> aby zobaczyć szczegóły, w tym powiązane zamówienie i produkt.</li>
                    <li><strong>Statusy Zgłoszeń:</strong> Pozwalają śledzić postęp obsługi zgłoszenia (np. <code>Nowe zgłoszenie</code>, <code>W trakcie rozpatrywania</code>, <code>Zaakceptowany - oczekuje na zwrot</code>, <code>Produkt otrzymany</code>, <code>Zwrot przetworzony / zakończony</code>, <code>Odrzucony</code>, <code>Anulowany</code>). Statusy można dodawać i edytować w Ustawieniach.</li>
                    <li><strong>Wpływ na Stan Magazynowy:</strong> **Ważna uwaga:** Aktualnie obsługa zgłoszeń zwrotów/reklamacji **nie modyfikuje automatycznie** stanu magazynowego produktów. Po fizycznym otrzymaniu zwracanego towaru i podjęciu decyzji o ponownym przyjęciu go na stan, należy **ręcznie skorygować stan magazynowy** odpowiedniego produktu w module Produkty.</li>
                 </ul>
                <p><a href="<?php echo $link_zwroty; ?>" class="btn btn-sm btn-outline-secondary">Przejdź do Zwrotów <i class="bi bi-arrow-right-short"></i></a></p>
            </section>

            <?php // Sekcja: Kurierzy ?>
            <section id="sekcja-kurierzy" class="mb-5">
                <h2 class="h4 border-bottom pb-2 mb-3"><i class="bi bi-truck me-2"></i>Zarządzanie Kurierami</h2>
                <p>Ten moduł pozwala na zdefiniowanie firm kurierskich, z których korzystasz, co umożliwia ich przypisywanie do zamówień i automatyczne generowanie linków do śledzenia przesyłek.</p>
                 <ul>
                    <li><strong>Lista Kurierów:</strong> Wyświetla wszystkich zdefiniowanych kurierów z ich kluczowymi danymi.</li>
                    <li><strong>Dodawanie Nowego Kuriera:</strong> Kliknij <button class="btn btn-primary btn-sm py-0 px-1 disabled"><i class="bi bi-plus-circle me-1"></i>Dodaj Kuriera</button>. W formularzu:
                        <ul>
                            <li>Podaj unikalne <strong>Tekstowe ID</strong> (np. <code>dpd_polska</code>, <code>inpost_kurier</code> - używaj tylko małych liter, cyfr i podkreślników; ID jest niezmienialne po utworzeniu). To ID jest używane wewnętrznie w systemie.</li>
                            <li>Podaj <strong>Nazwę Kuriera</strong> (np. DPD Polska, InPost Kurier). Ta nazwa będzie wyświetlana w aplikacji.</li>
                            <li>Wprowadź <strong>Wzorzec URL Śledzenia</strong> (<code>tracking_url_pattern</code>). Jest to pełny adres strony śledzenia danej firmy, w którym używasz znacznika <code>{tracking_number}</code> w miejscu, gdzie system ma wstawić numer przesyłki. Przykład: <code>https://inpost.pl/sledzenie-przesylek?number={tracking_number}</code>. Upewnij się, że wzorzec jest poprawny.</li>
                            <li>Zaznacz opcję "<strong>Aktywny</strong>", aby kurier był dostępny do wyboru na liście kurierów podczas dodawania lub edycji zamówienia.</li>
                        </ul>
                    </li>
                    <li><strong>Edycja Kuriera:</strong> Użyj ikony <button class="btn btn-warning btn-sm py-0 px-1 disabled"><i class="bi bi-pencil"></i></button> na liście. Możesz modyfikować nazwę, wzorzec URL i status aktywności. Tekstowego ID nie można zmienić.</li>
                    <li><strong>Usuwanie Kuriera:</strong> Kliknij ikonę <button class="btn btn-danger btn-sm py-0 px-1 disabled"><i class="bi bi-trash"></i></button> na liście kurierów. Zostaniesz poproszony o potwierdzenie. Pamiętaj, że usunięcie kuriera może wpłynąć na istniejące zamówienia, które miały go przypisanego.</li>
                    <li><strong>Generowanie Linku Śledzenia:</strong> Gdy kurier z poprawnym wzorcem URL i numer przesyłki zostaną przypisane do zamówienia, w podglądzie zamówienia pojawi się aktywny link do śledzenia przesyłki.</li>
                 </ul>
                 <p><a href="<?php echo $link_kurierzy; ?>" class="btn btn-sm btn-outline-secondary">Przejdź do Kurierów <i class="bi bi-arrow-right-short"></i></a></p>
            </section>

             <?php // Sekcja: Ustawienia ?>
            <section id="sekcja-ustawienia" class="mb-5">
                <h2 class="h4 border-bottom pb-2 mb-3"><i class="bi bi-sliders me-2"></i>Ustawienia Aplikacji</h2>
                <p>W tej sekcji możesz dostosować globalne ustawienia aplikacji, wpływając na jej wygląd, zachowanie i domyślne wartości.</p>
                <ul>
                    <li><strong>Ustawienia Ogólne:</strong>
                        <ul>
                            <li><code>Nazwa strony</code>: Tekst wyświetlany w tytule przeglądarki dla każdej strony.</li>
                            <li><code>Opis strony</code>: Krótki opis aplikacji (może być używany w metatagach).</li>
                            <li><code>Ikona aplikacji (Favicon)</code>: Prześlij mały obrazek (np. .ico, .png) do wyświetlenia w karcie przeglądarki.</li>
                            <li><code>Logo aplikacji</code>: Prześlij plik graficzny z logo, które może być wyświetlane w interfejsie.</li>
                            <li><code>Treść stopki</code>: Tekst wyświetlany w stopce na dole każdej strony.</li>
                            <li><code>Waluta</code>: Symbol waluty używanej w aplikacji (np. zł, EUR, USD).</li>
                            <li><code>Format daty</code>: Określa sposób wyświetlania dat (np. d.m.Y, Y-m-d).</li>
                        </ul>
                    </li>
                    <li><strong>Wygląd:</strong>
                        <ul>
                            <li>Włącz/wyłącz paski wierszy w tabelach dla lepszej czytelności.</li>
                            <li>Włącz/wyłącz obramowania tabel.</li>
                            <li>Ustaw motyw kolorystyczny interfejsu (Jasny, Ciemny, Auto - dostosowuje się do ustawień systemu operacyjnego użytkownika).</li>
                        </ul>
                    </li>
                    <li><strong>Powiadomienia:</strong>
                        <ul>
                            <li><code>Próg niskiego stanu magazynowego</code>: Ustaw liczbę sztuk, poniżej której produkt zostanie oznaczony jako mający "niski stan" i pojawi się w alertach na pulpicie.</li>
                        </ul>
                    </li>
                    <li><strong>Domyślne Wartości:</strong>
                        <ul>
                            <li><code>Domyślny status nowego zamówienia</code>: Status, który jest automatycznie przypisywany nowo dodanym zamówieniom.</li>
                            <li><code>Domyślny status nowego zgłoszenia zwrotu/reklamacji</code>: Status, który jest automatycznie przypisywany nowym zgłoszeniom zwrotów/reklamacji.</li>
                        </ul>
                    </li>
                    <li><strong>Magazyn:</strong>
                        <ul>
                            <li><code>Prefix dla poziomów lokalizacji</code>: Domyślny prefix używany podczas generowania ID lokalizacji (np. S dla Sekcji).</li>
                            <li><code>Prefix dla miejsc lokalizacji</code>: Domyślny prefix używany podczas generowania ID lokalizacji (np. P dla Poziomu).</li>
                        </ul>
                    </li>
                    <li><strong>Statusy:</strong> Zarządzaj listami dostępnych statusów dla:
                        <ul>
                            <li>Zamówień</li>
                            <li>Płatności (dla zamówień)</li>
                            <li>Zwrotów/Reklamacji</li>
                        </ul>
                        Możesz dodawać nowe statusy lub usuwać istniejące. **Ważne:** Usuwanie statusu, który jest aktualnie używany w istniejących zamówieniach lub zgłoszeniach, może prowadzić do niespójności danych. Zawsze upewnij się, że żaden element nie ma przypisanego statusu przed jego usunięciem.
                    </li>
                     <li><strong>Kategorie Produktów:</strong> Zarządzaj listą kategorii, które są dostępne do wyboru podczas dodawania/edycji produktu. Podobnie jak w przypadku statusów, usuwaj kategorie ostrożnie.</li>
                     <li><strong>Ustawienia Finansowe:</strong>
                         <ul>
                             <li><code>Procent Prowizji (%)</code>: Wartość procentowa używana do szacowania prowizji od sprzedaży na pulpicie.</li>
                             <li><code>Procent Podatku Dochodowego (%)</code>: Wartość procentowa używana do szacowania podatku dochodowego od szacowanego zysku na pulpicie.</li>
                             <li><code>Procent Kosztu Materiałów Pakowania (%)</code>: Wartość procentowa używana do szacowania kosztów materiałów pakowania od wartości sprzedaży na pulpicie.</li>
                             <li><code>Miesięczny Koszt Magazynowania (zł)</code>: Stała wartość miesięcznych kosztów utrzymania magazynu używana w obliczeniach na pulpicie.</li>
                         </ul>
                     </li>
                </ul>
                <div class="alert alert-warning small py-2" role="alert">
                    <i class="bi bi-exclamation-triangle me-1"></i> Zachowaj ostrożność podczas modyfikacji statusów, kategorii i ustawień finansowych, ponieważ zmiany mogą wpłynąć na filtrowanie, wyświetlanie i obliczenia w całej aplikacji.
                </div>
                 <p><a href="<?php echo $link_ustawienia; ?>" class="btn btn-sm btn-outline-secondary">Przejdź do Ustawień <i class="bi bi-arrow-right-short"></i></a></p>
            </section>

             <?php // Sekcja: Profil ?>
            <section id="sekcja-profil" class="mb-5">
                <h2 class="h4 border-bottom pb-2 mb-3"><i class="bi bi-person-badge me-2"></i>Mój Profil</h2>
                <p>W tej sekcji możesz zarządzać swoimi danymi osobowymi, hasłem oraz zdjęciem profilowym (avatarem).</p>
                <ul>
                    <li><strong>Edycja Danych:</strong> Możesz zmienić swoje Imię i Nazwisko oraz adres e-mail przypisany do Twojego konta użytkownika. Pamiętaj, że nazwa użytkownika (login) jest niezmienna.</li>
                    <li><strong>Zmiana Hasła:</strong> Aby zmienić hasło dostępu do aplikacji, musisz wypełnić wszystkie trzy pola w sekcji "Zmiana Hasła":
                        <ul>
                            <li><strong>Bieżące hasło:</strong> Twoje aktualne hasło logowania.</li>
                            <li><strong>Nowe hasło:</strong> Wprowadź nowe hasło (musi mieć minimum 8 znaków).</li>
                            <li><strong>Potwierdź nowe:</strong> Wprowadź nowe hasło ponownie, aby upewnić się, że nie popełniłeś błędu.</li>
                        </ul>
                        Jeśli nie chcesz zmieniać hasła, pozostaw te pola puste podczas zapisywania profilu.
                    </li>
                    <li><strong>Zmiana Avatara:</strong> Możesz przesłać własne zdjęcie profilowe, które będzie wyświetlane w aplikacji. Kliknij przycisk "Przeglądaj" lub "Wybierz plik", aby wybrać plik graficzny z dysku (obsługiwane formaty: JPG, PNG, GIF, WEBP; maksymalny rozmiar pliku: 1MB). Nowy avatar zastąpi poprzedni.</li>
                </ul>
                 <p><a href="<?php echo $link_profil; ?>" class="btn btn-sm btn-outline-secondary">Przejdź do Profilu <i class="bi bi-arrow-right-short"></i></a></p>
            </section>

            <?php // Sekcja: FAQ ?>
            <section id="sekcja-faq" class="mb-5">
                <h2 class="h4 border-bottom pb-2 mb-3"><i class="bi bi-patch-question-fill me-2"></i>Najczęściej Zadawane Pytania (FAQ)</h2>
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="faqHeading1">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="false" aria-controls="faqCollapse1">
                                Jak dodać nowy produkt do magazynu?
                            </button>
                        </h3>
                        <div id="faqCollapse1" class="accordion-collapse collapse" aria-labelledby="faqHeading1" data-bs-parent="#faqAccordion">
                            <div class="accordion-body small">
                                Aby dodać nowy produkt, przejdź do sekcji <a href="<?php echo $link_produkty; ?>">Produkty</a> i kliknij przycisk <button class="btn btn-primary btn-sm py-0 px-1 disabled"><i class="bi bi-plus-circle me-1"></i>Dodaj Produkt</button>. Wypełnij wszystkie wymagane pola formularza, w tym nazwę, producenta, cenę, koszt wysyłki i początkową ilość. Możesz również dodać zdjęcia, opis, parametry i informacje o kompatybilności. Po dodaniu produktu, możesz go od razu przypisać do wolnej lokalizacji magazynowej lub zrobić to później z poziomu <a href="<?php echo $link_magazyn; ?>">Magazynu</a>.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="faqHeading2">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
                                Jak zrealizować zamówienie i automatycznie zmniejszyć stan magazynowy?
                            </button>
                        </h3>
                        <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHeading2" data-bs-parent="#faqAccordion">
                            <div class="accordion-body small">
                                Przejdź do sekcji <a href="<?php echo $link_zamowienia; ?>">Zamówienia</a>. Znajdź zamówienie, które chcesz zrealizować, i kliknij ikonę edycji <button class="btn btn-warning btn-sm py-0 px-1 disabled"><i class="bi bi-pencil"></i></button>. W oknie edycji zamówienia zmień <strong>Status realizacji</strong> na <code>Zrealizowane</code> i zapisz zmiany. System automatycznie odejmie zamówioną ilość produktu od jego ogólnego stanu magazynowego. Jeśli później zmienisz status z powrotem na inny (np. Anulowane), stan magazynowy zostanie przywrócony.
                           </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                         <h3 class="accordion-header" id="faqHeading3">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">
                                Jak przypisać produkt do konkretnego miejsca w magazynie?
                            </button>
                        </h3>
                        <div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faqHeading3" data-bs-parent="#faqAccordion">
                            <div class="accordion-body small">
                                Przejdź do widoku <a href="<?php echo $link_magazyn; ?>">Magazynu</a>. Znajdź wolną lokalizację (oznaczoną kolorem zielonym) na graficznym układzie regałów i kliknij na nią. Otworzy się okno dialogowe (modal), w którym będziesz mógł wybrać produkt z listy (dostępne są tylko produkty z dodatnim stanem magazynowym) oraz podać ilość sztuk, którą chcesz umieścić w tej konkretnej lokalizacji. Po kliknięciu "Przypisz", lokalizacja zmieni status na "Zajęte" (kolor szary) i będzie zawierać informację o przypisanym produkcie.
                           </div>
                        </div>
                    </div>
                     <div class="accordion-item">
                         <h3 class="accordion-header" id="faqHeading4">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse4" aria-expanded="false" aria-controls="faqCollapse4">
                                Jak zmienić swoje hasło logowania?
                            </button>
                        </h3>
                        <div id="faqCollapse4" class="accordion-collapse collapse" aria-labelledby="faqHeading4" data-bs-parent="#faqAccordion">
                            <div class="accordion-body small">
                                Aby zmienić swoje hasło, przejdź do sekcji <a href="<?php echo $link_profil; ?>">Mój Profil</a>. W sekcji "Zmiana Hasła" wypełnij wszystkie trzy pola: <strong>Bieżące hasło</strong> (Twoje aktualne hasło używane do logowania), <strong>Nowe hasło</strong> (wpisz nowe hasło, które musi mieć co najmniej 8 znaków) oraz <strong>Potwierdź nowe</strong> (wpisz nowe hasło ponownie). Następnie kliknij przycisk "Zapisz zmiany w profilu" na dole strony. Jeśli nie chcesz zmieniać hasła, pozostaw te trzy pola puste.
                           </div>
                        </div>
                    </div>
                     <div class="accordion-item">
                         <h3 class="accordion-header" id="faqHeading5">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse5" aria-expanded="false" aria-controls="faqCollapse5">
                                Co oznacza "Niski Stan Magazynowy" na pulpicie?
                            </button>
                        </h3>
                        <div id="faqCollapse5" class="accordion-collapse collapse" aria-labelledby="faqHeading5" data-bs-parent="#faqAccordion">
                            <div class="accordion-body small">
                                Sekcja "Niski Stan Magazynowy" na <a href="<?php echo $link_dashboard; ?>">Pulpicie</a> wyświetla produkty, których aktualny stan magazynowy (liczba sztuk) spadł poniżej progu ustalonego w <a href="<?php echo $link_ustawienia; ?>">Ustawieniach</a> (domyślnie 5 sztuk). Jest to alert informujący, że dany produkt może wymagać uzupełnienia zapasów.
                           </div>
                        </div>
                    </div>
                     <div class="accordion-item">
                         <h3 class="accordion-header" id="faqHeading6">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse6" aria-expanded="false" aria-controls="faqCollapse6">
                                Czy obsługa zwrotów automatycznie przywraca stan magazynowy?
                            </button>
                        </h3>
                        <div id="faqCollapse6" class="accordion-collapse collapse" aria-labelledby="faqHeading6" data-bs-parent="#faqAccordion">
                            <div class="accordion-body small">
                                **Ważna uwaga:** Obecnie system **nie modyfikuje automatycznie** stanu magazynowego produktów podczas rejestrowania lub zmiany statusu zgłoszenia zwrotu/reklamacji. Po fizycznym otrzymaniu zwracanego towaru i podjęciu decyzji o ponownym przyjęciu go na stan, musisz **ręcznie skorygować stan magazynowy** odpowiedniego produktu w module <a href="<?php echo $link_produkty; ?>">Produkty</a>.
                           </div>
                        </div>
                    </div>
                    <?php // Dodaj więcej pytań i odpowiedzi w razie potrzeby ?>
                </div>
            </section>

            <?php // Sekcja: Rozwiązywanie Problemów ?>
            <section id="sekcja-problemy" class="mb-5">
                 <h2 class="h4 border-bottom pb-2 mb-3"><i class="bi bi-wrench-adjustable-circle-fill me-2"></i>Rozwiązywanie Problemów</h2>
                <p>Jeśli napotkasz problemy podczas korzystania z aplikacji, oto kilka kroków, które możesz podjąć, aby spróbować je rozwiązać:</p>
                 <ul>
                     <li><strong>Sprawdź Komunikaty Błędów:</strong> Po każdej akcji wykonanej w aplikacji, zwracaj uwagę na komunikaty wyświetlane na górze strony (tzw. "dymki"). Często zawierają one informacje o tym, czy operacja zakończyła się sukcesem, czy wystąpił błąd, a także wskazują na jego przyczynę (np. błąd walidacji formularza, brak wymaganych danych).</li>
                     <li><strong>Sprawdź Logi Serwera:</strong> W przypadku poważniejszych problemów, które uniemożliwiają normalne działanie aplikacji (np. biała strona, błąd "500 Internal Server Error"), sprawdź plik logów błędów PHP na swoim serwerze. Lokalizacja tego pliku zależy od konfiguracji serwera (np. w pliku <code>php.ini</code> lub konfiguracji serwera WWW, jak Apache/Nginx). W środowisku developerskim, jeśli skonfigurowano logowanie do pliku, może to być np. <code>/logs/php-error-dev.log</code> w katalogu głównym aplikacji. Logi te zawierają szczegółowe informacje o błędach PHP, które mogą pomóc zidentyfikować problem.</li>
                     <li><strong>Sprawdź Konsolę Deweloperską Przeglądarki:</strong> Jeśli problem dotyczy interfejsu użytkownika (np. przyciski nie działają, okna się nie otwierają, elementy nie ładują się poprawnie), otwórz narzędzia deweloperskie przeglądarki (zazwyczaj klawisz F12) i przejdź do zakładki "Console". Poszukaj tam czerwonych komunikatów o błędach JavaScript lub błędach ładowania zasobów (CSS, JS, obrazki).</li>
                     <li><strong>Wyczyść Pamięć Podręczną Przeglądarki:</strong> Czasami przeglądarki przechowują stare wersje plików aplikacji (CSS, JavaScript, HTML), co może powodować nieprawidłowe działanie po aktualizacji. Spróbuj wyczyścić pamięć podręczną przeglądarki (zazwyczaj Ctrl+F5 lub Shift+F5, lub opcja w ustawieniach przeglądarki "Wyczyść dane przeglądania").</li>
                     <li><strong>Sprawdź Uprawnienia do Plików/Katalogów:</strong> Upewnij się, że proces serwera WWW (np. użytkownik `www-data`) ma odpowiednie uprawnienia do zapisu w katalogach, w których aplikacja przechowuje dane i pliki:
                        <ul>
                            <li><code>/data/</code> (do zapisu plików JSON)</li>
                            <li><code>/public/uploads/images/</code> (do zapisu zdjęć produktów)</li>
                            <li><code>/public/uploads/icons/</code> (do zapisu ikon aplikacji)</li>
                            <li><code>/public/uploads/logo/</code> (do zapisu logo aplikacji)</li>
                             <li><code>/logs/</code> (jeśli logowanie do pliku jest włączone)</li>
                        </ul>
                        Typowe uprawnienia do zapisu to 775 lub 777 (choć 777 jest mniej bezpieczne i powinno być używane tylko w środowisku developerskim).</li>
                     <li><strong>Sprawdź Pliki Danych (JSON):</strong> Upewnij się, że pliki JSON w katalogu <code>/data/</code> nie zostały uszkodzone (np. przez ręczną edycję) i są poprawnie sformatowane (np. za pomocą walidatora JSON online). Nieprawidłowy format pliku JSON może powodować błędy podczas próby jego odczytu.</li>
                </ul>
                 <p>Jeśli po wykonaniu powyższych kroków problem nadal występuje, zbierz jak najwięcej informacji (treść błędu z ekranu, wpisy z logów serwera i konsoli przeglądarki) i skontaktuj się z administratorem systemu lub osobą odpowiedzialną za wsparcie techniczne.</p>
                 <p><a href="<?php echo $link_info; ?>" class="btn btn-sm btn-outline-secondary">Przejdź do Informacji Systemowych <i class="bi bi-arrow-right-short"></i></a></p>
            </section>

             <?php // Sekcja: Kontakt ?>
            <section id="sekcja-kontakt" class="mb-5">
                <h5 class="h4 border-bottom pb-2 mb-3"><i class="bi bi-headset me-2"></i>Kontakt i Wsparcie</h5>
                <p>Jeśli potrzebujesz dalszej pomocy, masz pytania dotyczące działania aplikacji lub chcesz zgłosić błąd, skontaktuj się z nami:</p>
                <ul>
                   <li><strong>Developer:</strong> Paweł Plichta</li>
                   
                </ul>
              
                  <strong>TN® iMAG</strong>.
                 <p class="text-muted small">Wersja aplikacji: 1.7.1</p> <?php // Wyświetl wersję aplikacji zdefiniowaną w konfiguracji ?>
            </section>

        </div> <?php // Koniec .col-lg-9 ?>
    </div> <?php // Koniec .row ?>
</div> <?php // Koniec .container-fluid ?>

<?php // Style specyficzne dla strony pomocy (opcjonalne) ?>
<style>
/* Ulepszone style dla sekcji FAQ */
#sekcja-faq .accordion-button {
    font-size: 1rem; /* Nieco większa czcionka dla nagłówków FAQ */
    font-weight: 600; /* Pogrubienie nagłówków */
    color: var(--bs-emphasis-color); /* Kolor zgodny z motywem */
    background-color: var(--bs-light); /* Jasne tło nagłówka */
    border-bottom: 1px solid var(--bs-border-color); /* Delikatna linia pod nagłówkiem */
    padding: 0.75rem 1.25rem; /* Wewnętrzne odstępy */
}

[data-bs-theme="dark"] #sekcja-faq .accordion-button {
    background-color: var(--bs-secondary-bg);
    color: var(--bs-body-color);
    border-bottom-color: var(--bs-border-color-translucent);
}


#sekcja-faq .accordion-button:not(.collapsed) {
    color: var(--bs-primary-text-emphasis); /* Kolor po rozwinięciu */
    background-color: var(--bs-primary-bg-subtle); /* Tło po rozwinięciu */
    border-bottom-color: var(--bs-primary); /* Kolor linii po rozwinięciu */
}

#sekcja-faq .accordion-body {
    font-size: 0.9rem; /* Czytelniejsza czcionka dla treści FAQ */
    line-height: 1.6; /* Zwiększony odstęp między wierszami */
    padding: 1rem 1.25rem; /* Wewnętrzne odstępy */
    background-color: var(--bs-body-bg); /* Tło treści */
    border-top: 0; /* Usuń górną linię */
}

#sekcja-faq .accordion-item {
    border: 1px solid var(--bs-border-color); /* Obramowanie elementu FAQ */
    margin-bottom: 0.5rem; /* Odstęp między elementami FAQ */
    border-radius: var(--bs-border-radius); /* Zaokrąglone rogi */
    overflow: hidden; /* Ukryj wystające obramowanie */
}

[data-bs-theme="dark"] #sekcja-faq .accordion-item {
     border-color: var(--bs-border-color-translucent);
}


/* Style dla małych przycisków w tekście pomocy */
.btn-sm.py-0.px-1.disabled {
    vertical-align: baseline; /* Wyrównaj do linii bazowej tekstu */
    pointer-events: none; /* Wyłącz interakcję myszką */
    opacity: 0.65; /* Zmniejsz przezroczystość */
}

/* Style dla badge'y statusów w tekście pomocy */
.badge.bg-success,
.badge.bg-secondary,
.badge.bg-danger,
.badge.bg-warning.text-dark {
    vertical-align: baseline; /* Wyrównaj do linii bazowej tekstu */
    padding: 0.3em 0.6em; /* Odstępy wewnątrz badge'a */
    font-size: 0.8em; /* Zmniejsz rozmiar czcionki */
}

</style>
