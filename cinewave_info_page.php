<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();

// Determine whether the logged-in user is a Manager
$is_manager = false;
$role = $_SESSION['role'] ?? ($_SESSION['position'] ?? ($_SESSION['staff_role'] ?? ''));

if ($role !== '') {
    $is_manager = (strcasecmp(trim($role), 'Manager') === 0);
} else {
    // Fallback: fetch the role from the staff table using the logged-in staff ID
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

require_once __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html>

<head>
    <title>Cinewave_info_page</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>

<body class="body">
    <h1><a href="index.php">Home</a></h1>
    <section class="container">
        <div class="card">
            <a href="booking_management.php">Booking Management</a>
        </div>
        <div class="card">
            <a href="movie_management.php">Movie Management</a>
        </div>
        <div class="card">
            <a href="fnbmanagement.php">FNB Management</a>
        </div>
        <div class="card">
            <a href="showtime_management.php">Showtime Management</a>
        </div>
        <div class="card">
            <a href="hall_management.php">Hall Management</a>
        </div>
        <div class="card">
            <a href="promotion_management.php">Promotion Management</a>
        </div>
        <div class="card">
            <a href="reward_management.php">Reward Management</a>
        </div>

        <?php if ($is_manager): ?>
            <div class="card">
                <a href="report.php">Report generator</a>
            </div>
        <?php endif; ?>
    </section>
</body>

</html>
