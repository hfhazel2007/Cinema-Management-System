<?php
require_once __DIR__ . '/config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: fnbmanagement.php");
    exit();
}


$fnb_id = intval($_GET['id']);
$pdo = getDBConnection();
$error_msg = '';

try {
    // 1. Fetch image path before deleting
    $stmt_img = $pdo->prepare("SELECT path FROM fnb WHERE fnb_id = :fnb_id");
    $stmt_img->execute([':fnb_id' => $fnb_id]);
    $item = $stmt_img->fetch(PDO::FETCH_ASSOC);

    // 2. Delete record
    $stmt = $pdo->prepare("DELETE FROM fnb WHERE fnb_id = :fnb_id");
    $stmt->execute([':fnb_id' => $fnb_id]);

    // 3. Remove local image file if it exists
    if ($item && !empty($item['path'])) {
        $file_path = __DIR__ . '/' . ltrim($item['path'], '/');
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    header("Location: fnbmanagement.php");
    exit();
} catch (PDOException $e) {
    if ($e->getCode() === '23503') {
        $error_msg = "Cannot delete this F&B item because it is linked to existing combos or customer orders.";
    } else {
        $error_msg = "Error deleting F&B item: " . $e->getMessage();
    }
}

require_once __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Delete F&amp;B</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h2>Delete F&amp;B</h2>
    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
        <a href="fnbmanagement.php" class="btn">Back to F&amp;B Management</a>
    <?php endif; ?>
</body>
</html>
