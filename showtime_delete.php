<?php
require_once __DIR__ . '/config/database.php';

// 1. Validate that a valid showtime ID was provided (never default to 1001!)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// 2. Validate that a valid reward ID was provided (never fall back to 1)
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: showtime_management.php");
    exit();
}

$showtime_id = intval($_GET['id']);
$pdo = getDBConnection();
$error_msg = '';

// 2. Run DELETE BEFORE any HTML is output
try {
    $stmt = $pdo->prepare("DELETE FROM showtime WHERE showtime_id = :showtime_id");
    $stmt->execute([':showtime_id' => $showtime_id]);

    header("Location: showtime_management.php");
    exit();
} catch (PDOException $e) {
    // Catches foreign key violations if tickets/bookings already exist for this showtime
    if ($e->getCode() === '23503') {
        $error_msg = "Cannot delete this showtime because customer tickets or bookings have already been made for it.";
    } else {
        $error_msg = "Error deleting showtime: " . $e->getMessage();
    }
}

require_once __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Showtime Delete</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h2>Delete Showtime</h2>
    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
        <a href="showtime_management.php" class="btn">Back to Showtime Management</a>
    <?php endif; ?>
</body>
</html>
