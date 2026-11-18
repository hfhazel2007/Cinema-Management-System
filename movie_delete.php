<?php
require_once __DIR__ . '/config/database.php';

// 1. Validate that a valid movie ID was provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: movie_management.php");
    exit();
}

$movie_id = intval($_GET['id']);
$pdo = getDBConnection();
$error_msg = '';

// 2. Run DELETE BEFORE any HTML is output
try {
    $stmt = $pdo->prepare("DELETE FROM movie WHERE movie_id = :movie_id");
    $stmt->execute([':movie_id' => $movie_id]);

    header("Location: movie_management.php");
    exit();
} catch (PDOException $e) {
    // Catches foreign key violations if showtimes already reference this movie
    if ($e->getCode() === '23503') {
        $error_msg = "Cannot delete this movie because it still has showtimes scheduled for it.";
    } else {
        $error_msg = "Error deleting movie: " . $e->getMessage();
    }
}

require_once __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Delete Movie</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h2>Delete Movie</h2>
    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
        <a href="movie_management.php" class="btn">Back to Movie Management</a>
    <?php endif; ?>
</body>
</html>
