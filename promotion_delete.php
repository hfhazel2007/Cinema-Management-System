<?php
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: promotion_management.php");
    exit();
}

$promotion_id = intval($_GET['id']);
$pdo = getDBConnection();
$error_msg = '';

// 2. Run DELETE BEFORE any HTML is output so header() redirect works
try {
    $stmt = $pdo->prepare("DELETE FROM promotion WHERE promotion_id = :promotion_id");
    $stmt->execute([':promotion_id' => $promotion_id]);

    header("Location: promotion_management.php");
    exit();
} catch (PDOException $e) {
    // Catches foreign key violations if orders/bookings still reference this promotion
    if ($e->getCode() === '23503') {
        $error_msg = "Cannot delete this promotion because it is still linked to existing bookings or orders.";
    } else {
        $error_msg = "Error deleting promotion: " . $e->getMessage();
    }
}

require_once __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Promotion Delete</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h2>Delete Promotion</h2>
    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
        <a href="promotion_management.php" class="btn">Back to Promotion Management</a>
    <?php endif; ?>
</body>
</html>
