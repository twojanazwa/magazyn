<?php
// templates/pages/tn_biuro_doc_list.php
/**
 * Widok: Lista zapisanych faktur/rachunków.
 *
 * Ten plik jest WYŁĄCZNIE widokiem.
 * Oczekuje, że zmienna $all_invoices (tablica wszystkich faktur/rachunków)
 * zostanie mu przekazana z kontrolera (np. tn_biuro.php).
 *
 * Zakłada dostępność funkcji pomocniczych:
 * tn_generuj_url() (jeśli używana do generowania linków)
 * format_currency() (jeśli używana do formatowania kwot)
 */

// Zabezpieczenie przed bezpośrednim wywołaniem pliku szablonu
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403); // Ustaw kod błędu 403 Forbidden
    die('Access Denied - View file.');
}

// Upewnij się, że zmienna $all_invoices jest dostępna i jest tablicą
$all_invoices = $all_invoices ?? [];

// Upewnij się, że funkcja format_currency jest dostępna (jeśli nie, zdefiniuj ją jako fallback)
if (!function_exists('format_currency')) {
    function format_currency($amount, $currency = 'PLN') {
        return number_format((float)$amount, 2, ',', ' ') . ' ' . htmlspecialchars($currency, ENT_QUOTES, 'UTF-8');
    }
}

// Upewnij się, że funkcja tn_generuj_url jest dostępna (jeśli nie, zdefiniuj placeholder)
if (!function_exists('tn_generuj_url')) {
    function tn_generuj_url($page, $params = []) {
        // Prosty placeholder - dostosuj do swojego systemu routingu
        $url = './biuro'; // Domyślny adres dla biura
        $query = [];

        if ($page === 'view_invoice' && isset($params['d'])) {
            $query['d'] = $params['d'];
        } elseif ($page === 'add_invoice') {
             $query['action'] = 'add';
        } elseif ($page === 'settings') {
             $query['action'] = 'settings';
        } elseif ($page === 'list_invoices') {
             $query['action'] = 'list';
        } elseif ($page === 'summary') {
             // Brak akcji lub action=summary to domyślna strona
        }

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        return $url;
    }
}

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista Faktur/Rachunków</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body {
            font-size: 0.9rem;
            line-height: 1.4;
            color: #333;
            background-color: #f8f9fa;
            padding-bottom: 50px;
        }
        .container-fluid {
            margin-top: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
        .invoice-list-table th, .invoice-list-table td {
            vertical-align: middle;
            padding: 8px;
        }
         .invoice-list-table th {
             background-color: #e9ecef;
         }
         .invoice-list-table tbody tr:nth-child(even) {
             background-color: #f8f9fa;
         }
    </style>
</head>
<body>

    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-header bg-light py-3">
                <h5 class="mb-0"><i class="bi bi-list-columns-reverse me-2"></i>Lista Faktur / Rachunków</h5>
            </div>
            <div class="card-body p-4">

                <?php if (empty($all_invoices)): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle me-2"></i> Brak zapisanych faktur/rachunków.
                    </div>
                <?php else: ?>
                    <table class="table table-bordered invoice-list-table">
                        <thead>
                            <tr>
                                <th>Numer</th>
                                <th>Data</th>
                                <th>Nabywca</th>
                                <th class="text-end">Suma brutto</th>
                                <th>Waluta</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_invoices as $invoice): ?>
                                <?php
                                    // Oblicz sumę brutto dla wyświetlenia na liście
                                    $total_gross = 0;
                                    if (isset($invoice['items']) && is_array($invoice['items'])) {
                                        foreach ($invoice['items'] as $item) {
                                            $item_quantity = $item['quantity'] ?? 0;
                                            $item_unit_price_net = $item['unit_price_net'] ?? 0;
                                            $item_tax_rate = $item['tax_rate'] ?? 0;
                                            $item_net = (float)$item_unit_price_net * (float)$item_quantity;
                                            $item_tax = $item_net * ((float)$item_tax_rate / 100);
                                            $total_gross += $item_net + $item_tax;
                                        }
                                    }
                                    $currency = $invoice['currency'] ?? 'PLN';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($invoice['invoice_number'] ?? 'BRAK', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['order_date'] ?? 'BRAK', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['buyer']['name'] ?? 'BRAK', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-end"><?php echo format_currency($total_gross, ''); ?></td>
                                    <td><?php echo htmlspecialchars($currency, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <a href="<?php echo tn_generuj_url('view_invoice', ['d' => $invoice['invoice_number'] ?? '']); ?>" class="btn btn-info btn-sm" title="Wyświetl dokument"><i class="bi bi-eye"></i></a>
                                        <button class="btn btn-outline-primary btn-sm" title="Edytuj dokument" data-bs-toggle="modal" data-bs-target="#editDocumentModal" data-bs-invoice-number="<?php echo htmlspecialchars($invoice['invoice_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-outline-danger btn-sm" title="Usuń dokument" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" data-bs-invoice-number="<?php echo htmlspecialchars($invoice['invoice_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                 <div class="mt-4">
                     <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addDocumentModal"><i class="bi bi-file-earmark-plus me-1"></i> Dodaj Nowy Dokument</button>
                      <a href="<?php echo tn_generuj_url('summary'); ?>" class="btn btn-secondary"><i class="bi bi-house me-1"></i> Wróć do Strony Głównej</a>
                      <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#settingsModal"><i class="bi bi-gear me-1"></i> Ustawienia Biura</button>
                 </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

    <?php // Skrypt JS dla tego widoku (jeśli potrzebny) ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tutaj można dodać skrypty specyficzne dla widoku listy dokumentów,
            // np. inicjalizację tooltipów, obsługę przycisków usuwania (przed otwarciem modala potwierdzenia) itp.

            // Przyciski Edytuj i Usuń w tabeli listy dokumentów
            const editButtons = document.querySelectorAll('.invoice-list-table .btn-outline-primary');
            const deleteButtons = document.querySelectorAll('.invoice-list-table .btn-outline-danger');

            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Ten event listener jest teraz głównie do celów debugowania/testowania,
                    // ponieważ otwarcie modala jest obsługiwane przez atrybuty data-bs-*.
                    // Realna logika ładowania danych do modala edycji jest w skrypcie głównego pliku (tn_biuro.php).
                    console.log('Kliknięto Edytuj dla dokumentu:', this.getAttribute('data-bs-invoice-number'));
                    // Modal edycji zostanie otwarty automatycznie przez Bootstrap
                });
            });

             deleteButtons.forEach(button => {
                 button.addEventListener('click', function() {
                     // Ten event listener jest teraz głównie do celów debugowania/testowania,
                     // ponieważ otwarcie modala potwierdzenia jest obsługiwane przez atrybuty data-bs-*.
                     // Realna logika potwierdzenia i wysłania żądania usuwania będzie w skrypcie głównego pliku (tn_biuro.php).
                     console.log('Kliknięto Usuń dla dokumentu:', this.getAttribute('data-bs-invoice-number'));
                     // Modal potwierdzenia usunięcia zostanie otwarty automatycznie przez Bootstrap
                 });
             });

            // Opcjonalnie: Inicjalizacja tooltipów dla przycisków akcji w tabeli
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('.invoice-list-table [data-bs-toggle="tooltip"]'));
             tooltipTriggerList.map(function (tooltipTriggerEl) {
                 const existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                 if (existingTooltip) {
                     existingTooltip.dispose();
                 }
                 return new bootstrap.Tooltip(tooltipTriggerEl);
             });
        });
    </script>

</body>
</html>
