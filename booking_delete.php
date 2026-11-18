<?php
require_once __DIR__ . '/config/database.php';

// 1. Validate that a valid group booking ID was provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: booking_management.php");
    exit();
}

$group_booking_id = intval($_GET['id']);
$pdo = getDBConnection();
$error_msg = '';

// 2. Run DELETE BEFORE any HTML is output so header() redirect works
try {
    $stmt = $pdo->prepare("DELETE FROM group_booking WHERE group_booking_id = :gb_id");
    $stmt->execute([':gb_id' => $group_booking_id]);

    header("Location: booking_management.php");
    exit();
} catch (PDOException $e) {
    // Catches foreign key violations if total_order records still reference this group booking
    if ($e->getCode() === '23503') {
        $error_msg = "Cannot delete this group booking because it is still linked to existing order records.";
    } else {
        $error_msg = "Error deleting group booking: " . $e->getMessage();
    }
}

require_once __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Delete Booking</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h2>Delete Group Booking</h2>
    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
        <a href="booking_management.php" class="btn">Back to Booking Management</a>
    <?php endif; ?>
</body>
</html>
