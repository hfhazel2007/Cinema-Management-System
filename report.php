<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();

// 1. Verify Manager role BEFORE outputting any HTML
$is_manager = false;
$role = $_SESSION['role'] ?? ($_SESSION['position'] ?? ($_SESSION['staff_role'] ?? ''));

if ($role !== '') {
    $is_manager = (strcasecmp(trim($role), 'Manager') === 0);
} else {
    $staff_id = $_SESSION['staff_id'] ?? ($_SESSION['id'] ?? null);
    if ($staff_id) {
        $stmt = $pdo->prepare("SELECT role FROM staff WHERE staff_id = :staff_id");
        $stmt->execute([':staff_id' => $staff_id]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($staff && isset($staff['role'])) {
            $is_manager = (strcasecmp(trim($staff['role']), 'Manager') === 0);
        }
    }
}

if (!$is_manager) {
    header("Location: cinewave_info_page.php");
    exit();
}

require_once __DIR__ . '/header.php';

// 2. Fetch all report datasets into arrays
$monthlyRows = $pdo->query("
    SELECT TO_CHAR(payment_datetime, 'YYYY-MM') AS month,
           SUM(amount_paid) AS total_sales,
           COUNT(payment_id) AS tx_count
    FROM payment
    WHERE payment_status = 'Paid'
    GROUP BY month
    ORDER BY month DESC
")->fetchAll(PDO::FETCH_ASSOC);

$yearlyRows = $pdo->query("
    SELECT EXTRACT(YEAR FROM payment_datetime)::INT AS year,
           SUM(amount_paid) AS total_sales,
           COUNT(payment_id) AS tx_count
    FROM payment
    WHERE payment_status = 'Paid'
    GROUP BY year
    ORDER BY year ASC
")->fetchAll(PDO::FETCH_ASSOC);

$movieRows = $pdo->query("
    SELECT m.movie_name,
           COUNT(t.ticket_id) AS sold,
           SUM(t.ticket_price) AS rev
    FROM payment p
    JOIN total_order o ON p.total_order_id = o.total_order_id
    JOIN ticket t ON o.ticket_id = t.ticket_id
    JOIN showtime s ON t.showtime_id = s.showtime_id
    JOIN movie m ON s.movie_id = m.movie_id
    WHERE p.payment_status = 'Paid'
    GROUP BY m.movie_id, m.movie_name
    ORDER BY sold DESC
")->fetchAll(PDO::FETCH_ASSOC);

$ticketRows = $pdo->query("
    SELECT t.ticket_type,
           COUNT(t.ticket_id) AS sold,
           SUM(t.ticket_price) AS rev
    FROM payment p
    JOIN total_order o ON p.total_order_id = o.total_order_id
    JOIN ticket t ON o.ticket_id = t.ticket_id
    WHERE p.payment_status = 'Paid'
    GROUP BY t.ticket_type
    ORDER BY sold DESC
")->fetchAll(PDO::FETCH_ASSOC);

$fnbRows = $pdo->query("
    SELECT f2.fnb_name,
           SUM(f1.quantity) AS sold,
           SUM(f1.fnb_total_price) AS rev
    FROM payment p
    JOIN total_order o ON p.total_order_id = o.total_order_id
    JOIN fnb_item f1 ON o.fnb_item_id = f1.fnb_item_id
    JOIN fnb f2 ON f1.fnb_id = f2.fnb_id
    WHERE p.payment_status = 'Paid'
    GROUP BY f2.fnb_id, f2.fnb_name
    ORDER BY sold DESC
")->fetchAll(PDO::FETCH_ASSOC);

// 3. Calculate section totals
$monthlyTotalTx  = array_sum(array_column($monthlyRows, 'tx_count'));
$monthlyTotalRev = array_sum(array_column($monthlyRows, 'total_sales'));

$yearlyTotalTx   = array_sum(array_column($yearlyRows, 'tx_count'));
$yearlyTotalRev  = array_sum(array_column($yearlyRows, 'total_sales'));

$movieTotalSold  = array_sum(array_column($movieRows, 'sold'));
$movieTotalRev   = array_sum(array_column($movieRows, 'rev'));

$ticketTotalSold = array_sum(array_column($ticketRows, 'sold'));
$ticketTotalRev  = array_sum(array_column($ticketRows, 'rev'));

$fnbTotalSold    = array_sum(array_column($fnbRows, 'sold'));
$fnbTotalRev     = array_sum(array_column($fnbRows, 'rev'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>CineWave - Analytical Report</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>

<body class="body">
    <div class="report-wrapper">
        <div class="report-header">
            <h1>CineWave Annual &amp; Analytical Report</h1>
        </div>

        <div class="report-grid">
            <!-- Monthly Sales -->
            <div class="report-card">
                <h2>Monthly Sales</h2>
                <?php if (empty($monthlyRows)): ?>
                    <p class="empty-state">No paid transactions recorded yet.</p>
                <?php else: ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="num">Transactions</th>
                                <th class="num">Total Sales (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthlyRows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['month']) ?></td>
                                    <td class="num"><?= htmlspecialchars($row['tx_count']) ?></td>
                                    <td class="num"><?= number_format((float)$row['total_sales'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total Revenue</td>
                                <td class="num"><?= number_format($monthlyTotalTx) ?></td>
                                <td class="num">RM <?= number_format((float)$monthlyTotalRev, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Annual Sales -->
            <div class="report-card">
                <h2>Annual Sales</h2>
                <?php if (empty($yearlyRows)): ?>
                    <p class="empty-state">No annual sales records available.</p>
                <?php else: ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Year</th>
                                <th class="num">Transactions</th>
                                <th class="num">Total Sales (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($yearlyRows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['year']) ?></td>
                                    <td class="num"><?= htmlspecialchars($row['tx_count']) ?></td>
                                    <td class="num"><?= number_format((float)$row['total_sales'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total Revenue</td>
                                <td class="num"><?= number_format($yearlyTotalTx) ?></td>
                                <td class="num">RM <?= number_format((float)$yearlyTotalRev, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Popular Movies (Full Width) -->
            <div class="report-card full-width">
                <h2>Popular Movies Performance</h2>
                <?php if (empty($movieRows)): ?>
                    <p class="empty-state">No movie ticket sales recorded yet.</p>
                <?php else: ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Movie Name</th>
                                <th class="num">Tickets Sold</th>
                                <th class="num">Revenue (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movieRows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['movie_name']) ?></td>
                                    <td class="num"><?= htmlspecialchars($row['sold']) ?></td>
                                    <td class="num"><?= number_format((float)$row['rev'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total Movie Revenue</td>
                                <td class="num"><?= number_format($movieTotalSold) ?></td>
                                <td class="num">RM <?= number_format((float)$movieTotalRev, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Ticket Sales by Type -->
            <div class="report-card">
                <h2>Ticket Sales by Category</h2>
                <?php if (empty($ticketRows)): ?>
                    <p class="empty-state">No ticket category data available.</p>
                <?php else: ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Ticket Type</th>
                                <th class="num">Quantity Sold</th>
                                <th class="num">Revenue (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ticketRows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['ticket_type']) ?></td>
                                    <td class="num"><?= htmlspecialchars($row['sold']) ?></td>
                                    <td class="num"><?= number_format((float)$row['rev'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total Ticket Revenue</td>
                                <td class="num"><?= number_format($ticketTotalSold) ?></td>
                                <td class="num">RM <?= number_format((float)$ticketTotalRev, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Food & Beverage Sales -->
            <div class="report-card">
                <h2>Food &amp; Beverage Sales</h2>
                <?php if (empty($fnbRows)): ?>
                    <p class="empty-state">No F&amp;B sales recorded yet.</p>
                <?php else: ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th class="num">Quantity Sold</th>
                                <th class="num">Revenue (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fnbRows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['fnb_name']) ?></td>
                                    <td class="num"><?= htmlspecialchars($row['sold']) ?></td>
                                    <td class="num"><?= number_format((float)$row['rev'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total F&amp;B Revenue</td>
                                <td class="num"><?= number_format($fnbTotalSold) ?></td>
                                <td class="num">RM <?= number_format((float)$fnbTotalRev, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>

