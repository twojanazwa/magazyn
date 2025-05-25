<!doctype html>
<html lang="pl" data-bs-theme="light"> <?php // Możesz dynamicznie zmieniać motyw (light/dark) ?>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TN WareXPERT - Dashboard</title>
    
    <?php // --- DOŁĄCZANIE STYLÓW --- ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <?php // Twoje własne style CSS (jeśli masz) ?>
    <link rel="stylesheet" href="assets/css/custom-styles.css"> 
    
    <style>
        /* --- Style dla responsywnego sidebara --- */
        body {
            overflow-x: hidden; /* Zapobiega poziomemu przewijaniu */
        }

        #sidebarMenu {
            height: 100vh; /* Pełna wysokość */
            position: fixed; /* Przyklejony na stałe */
            top: 0;
            left: 0;
            width: 250px; /* Szerokość sidebara */
            z-index: 1030; /* Podobny jak Offcanvas, ale niższy niż modal */
            transition: transform 0.3s ease-in-out; /* Animacja */
        }

        #main-content {
            transition: margin-left 0.3s ease-in-out; /* Animacja marginesu */
            padding-top: 56px; /* Zostaw miejsce na ewentualny górny pasek (navbar) */
        }

        /* Na ekranach LG i większych - sidebar widoczny */
        @media (min-width: 992px) {
            #sidebarMenu {
                transform: translateX(0); /* Widoczny */
            }
            #main-content {
                margin-left: 250px; /* Odsuń główną treść */
            }
             /* Ukryj przycisk offcanvas */
            .sidebar-toggler {
                display: none;
            }
        }

        /* Na ekranach mniejszych niż LG - sidebar domyślnie schowany (jak offcanvas) */
        @media (max-width: 991.98px) {
             #sidebarMenu {
                transform: translateX(-100%); /* Schowany */
                /* Dodajemy klasy offcanvas, aby przejąć jego style */
                &.offcanvas-start { 
                   /* Bootstrap może już to robić, ale dla pewności */
                   transform: translateX(-100%);
                }
                &.show {
                   transform: translateX(0); /* Pokazywanie przez JS/Bootstrap */
                }
             }
             #main-content {
                margin-left: 0; /* Treść zajmuje całą szerokość */
             }
        }

        /* Dodatkowe style dla Offcanvas (Bootstrap sam je dodaje, ale można dostosować) */
        .offcanvas {
           width: 250px; /* Upewnij się, że szerokość jest spójna */
        }
        
        /* Styl dla aktywnego linku w menu */
        .nav-link.active {
            font-weight: bold;
            color: var(--bs-primary);
        }
        .sidebar-sticky {
            position: sticky;
            top: 56px; /* Jeśli masz górny pasek */
            height: calc(100vh - 56px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto; /* Pozwala przewijać menu, jeśli jest długie */
        }
        
    </style>
</head>
<body>

<?php // --- PRZYKŁADOWY GÓRNY PASEK NAWIGACYJNY (NAVBAR) --- ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-sm">
  <div class="container-fluid">
    
    <?php // Przycisk Toggler dla Sidebara (widoczny tylko na mobile < LG) ?>
    <button class="navbar-toggler sidebar-toggler me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-label="Toggle navigation">
       <span class="navbar-toggler-icon"></span> <?php // Możesz zamienić na <i class="bi bi-list"></i> ?>
    </button>
    
    <a class="navbar-brand" href="#">TN WareXPERT</a>
    
    <?php // Przycisk Toggler dla menu w Navbar (jeśli masz) ?>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMainCollapse" aria-controls="navbarMainCollapse" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarMainCollapse">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="#">Profil</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">Wyloguj</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<?php // --- STRUKTURA ZAWIERAJĄCA SIDEBAR I GŁÓWNĄ TREŚĆ --- ?>
<div class="container-fluid">
  <div class="row">
      
    <?php // --- SIDEBAR / OFFCANVAS MENU --- ?>
    <nav id="sidebarMenu" class="col-lg-2 d-lg-block bg-light sidebar offcanvas offcanvas-start" tabindex="-1" aria-labelledby="sidebarMenuLabel">
      
      <?php // Nagłówek Offcanvas (widoczny tylko na mobile, gdy się wysunie) ?>
      <div class="offcanvas-header d-lg-none">
        <h5 class="offcanvas-title" id="sidebarMenuLabel">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>

      <?php // Ciało Offcanvas / Zawartość Sidebara ?>
      <div class="offcanvas-body d-flex flex-column p-0"> <?php // Usunięto padding dla pełnej kontroli nad listą ?>
        <div class="sidebar-sticky pt-3"> <?php // Dodano klasę do przewijania i padding ?>
            <ul class="nav flex-column">
              <li class="nav-item">
                <?php $currentPage = $_GET['page'] ?? 'dashboard'; // Przykładowe określenie aktywnej strony ?>
                <a class="nav-link <?php echo ($currentPage === 'dashboard' || $currentPage === '') ? 'active' : ''; ?>" aria-current="page" href="?page=dashboard">
                  <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'products') ? 'active' : ''; ?>" href="?page=products">
                  <i class="bi bi-box-seam me-2"></i> Produkty
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'orders') ? 'active' : ''; ?>" href="?page=orders">
                  <i class="bi bi-receipt me-2"></i> Zamówienia
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'returns_list') ? 'active' : ''; ?>" href="?page=returns_list">
                  <i class="bi bi-arrow-repeat me-2"></i> Zwroty / Reklamacje
                </a>
              </li>
               <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'stock') ? 'active' : ''; ?>" href="?page=stock">
                   <i class="bi bi-hdd-stack me-2"></i> Stan Magazynu
                 </a>
               </li>
              <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'settings') ? 'active' : ''; ?>" href="?page=settings">
                  <i class="bi bi-gear me-2"></i> Ustawienia
                </a>
              </li>
            </ul>

            <?php // Możesz dodać inne sekcje menu, np.
            /*
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
              <span>Raporty</span>
            </h6>
            <ul class="nav flex-column mb-2">
              <li class="nav-item">
                <a class="nav-link" href="#">
                  Raport sprzedaży
                </a>
              </li>
            </ul>
            */ ?>
        </div>
      </div>
    </nav>

    <?php // --- GŁÓWNA ZAWARTOŚĆ STRONY --- ?>
    <main id="main-content" class="col-lg-10 ms-sm-auto px-md-4">
        <?php 
            // Tutaj ładujesz zawartość odpowiedniej podstrony,
            // np. na podstawie parametru $_GET['page']
            // W tym przypadku ładujemy twój pulpit nawigacyjny:
            include 'templates/pages/tn_dashboard.php'; 
        ?>
    </main>
    
  </div>
</div>


<?php // --- DOŁĄCZANIE SKRYPTÓW --- ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<?php // Twoje własne skrypty JS (jeśli masz) ?>
<?php //<script src="assets/js/custom-scripts.js"></script> ?>

</body>
</html>