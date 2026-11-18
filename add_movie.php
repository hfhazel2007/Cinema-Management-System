<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';

// Support either $_SESSION['staff_id'] or $_SESSION['id']
$staff_id = $_SESSION['staff_id'] ?? ($_SESSION['id'] ?? null);
if (!$staff_id) {
    header("Location: login.php");
    exit();
}

$pdo = getDBConnection();
$error_msg = '';

// 1. Handle form submission BEFORE any HTML is output
if (isset($_POST['submit'])) {
    $movie_name        = trim($_POST['movie_name']);
    $movie_duration    = intval($_POST['movie_duration']);
    $movie_category    = trim($_POST['movie_category']);
    $movie_genre       = trim($_POST['movie_genre']);
    $movie_description = trim($_POST['movie_description']);
    $movie_lang        = trim($_POST['movie_language']);
    $movie_subtitle    = trim($_POST['movie_subtitle']);
    $movie_status      = trim($_POST['movie_status']);

    if (!isset($_FILES['movie_img']) || $_FILES['movie_img']['error'] !== UPLOAD_ERR_OK) {
        $error_msg = "Please upload a valid PNG poster.";
    } else {
        $file     = $_FILES['movie_img'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file_ext !== 'png') {
            $error_msg = "Only PNG format is supported.";
        } else {
            try {
                // Insert movie first and retrieve new movie_id via PostgreSQL RETURNING
                $stmt = $pdo->prepare("
                    INSERT INTO movie (movie_name, synopsis, genre, duration, language, subtitle, path, current_showing_status, age_restriction, staff_id)
                    VALUES (:movie_name, :synopsis, :genre, :duration, :language, :subtitle, '', :status, :age_restriction, :staff_id)
                    RETURNING movie_id
                ");
                $stmt->execute([
                    ':movie_name'      => $movie_name,
                    ':synopsis'        => $movie_description,
                    ':genre'           => $movie_genre,
                    ':duration'        => $movie_duration,
                    ':language'        => $movie_lang,
                    ':subtitle'        => $movie_subtitle,
                    ':status'          => $movie_status,
                    ':age_restriction' => $movie_category,
                    ':staff_id'        => $staff_id
                ]);
                $new_movie_id = $stmt->fetchColumn();

                $upload_dir = __DIR__ . '/assets/movie/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $relative_path = 'assets/movie/' . $new_movie_id . '.png';

                if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_movie_id . '.png')) {
                    $update_stmt = $pdo->prepare("UPDATE movie SET path = :path WHERE movie_id = :movie_id");
                    $update_stmt->execute([
                        ':path'     => $relative_path,
                        ':movie_id' => $new_movie_id
                    ]);

                    header("Location: movie_management.php");
                    exit();
                } else {
                    // Roll back the row if file upload fails
                    $del_stmt = $pdo->prepare("DELETE FROM movie WHERE movie_id = :movie_id");
                    $del_stmt->execute([':movie_id' => $new_movie_id]);
                    $error_msg = "Failed to save image file. Check directory write permissions.";
                }
            } catch (PDOException $e) {
                $error_msg = "Database Error: " . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Add Movie</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h1>Add Movie</h1>

    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
    <?php endif; ?>

    <form action="add_movie.php" method="post" enctype="multipart/form-data">
        Movie Name:<br>
        <input type="text" name="movie_name" required>
        <br><br>

        Duration (minutes):<br>
        <input type="number" name="movie_duration" min="1" required>
        <br><br>

        Category:<br>
        <select name="movie_category" required>
            <option value="">-- Select Category --</option>
            <option value="adult">Adult</option>
            <option value="child">Child</option>
        </select>
        <br><br>

        Genre:<br>
        <input type="text" name="movie_genre" required>
        <br><br>

        Language:<br>
        <input type="text" name="movie_language" required>
        <br><br>

        Subtitle:<br>
        <input type="text" name="movie_subtitle" required>
        <br><br>

        Current Showing Status:<br>
        <select name="movie_status" required>
            <option value="">-- Select Status --</option>
            <option value="Upcoming">Upcoming</option>
            <option value="Now Showing">Now Showing</option>
            <option value="Ended">Ended</option>
        </select>
        <br><br>

        Synopsis:<br>
        <textarea rows="5" cols="40" name="movie_description" required></textarea>
        <br><br>

        Poster (PNG only):<br>
        <input type="file" name="movie_img" accept=".png,image/png" required>
        <br><br>

        <input type="submit" name="submit" value="Submit" class="btn">
    </form>
</body>
</html>
