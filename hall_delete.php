<?php
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: hall_management.php");
    exit();
}


$hall_id = intval($_GET['id']);
$pdo = getDBConnection();
$error_msg = '';

// 2. Run DELETE BEFORE any HTML is output so header() redirect works
try {
    $stmt = $pdo->prepare("DELETE FROM hall WHERE hall_id = :hall_id");
    $stmt->execute([':hall_id' => $hall_id]);

    header("Location: hall_management.php");
    exit();
} catch (PDOException $e) {
    // Catches foreign key violations (e.g., if seats or showtimes still reference this hall)
    if ($e->getCode() === '23503') {
        $error_msg = "Cannot delete this hall because it still has seats or showtimes assigned to it.";
    } else {
        $error_msg = "Error deleting hall: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Hall Delete</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h2>Delete Hall</h2>
    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
        <a href="hall_management.php" class="btn">Back to Hall Management</a>
    <?php endif; ?>
</body>
</html>
