<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';

// 1. Ensure staff is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// 2. Validate that a valid reward ID was provided (never fall back to 1)
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: reward_management.php");
    exit();
}

$reward_id = intval($_GET['id']);
$pdo = getDBConnection();
$error_msg = '';

// 3. Execute DELETE BEFORE any HTML is output so header() redirect works
try {
    $stmt = $pdo->prepare("DELETE FROM reward WHERE reward_id = :reward_id");
    $stmt->execute([':reward_id' => $reward_id]);

    header("Location: reward_management.php");
    exit();
} catch (PDOException $e) {
    // Handle foreign key violations if customers have already redeemed this reward
    if ($e->getCode() === '23503') {
        $error_msg = "Cannot delete this reward because it has already been redeemed or linked to customer records.";
    } else {
        $error_msg = "Error deleting reward: " . $e->getMessage();
    }
}

require_once __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Delete Reward</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h2>Delete Reward</h2>
    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
        <a href="reward_management.php" class="btn">Back to Reward Management</a>
    <?php endif; ?>
</body>
</html>
