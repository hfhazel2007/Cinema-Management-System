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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: movie_management.php");
    exit();
}

$pdo = getDBConnection();
$movie_id = intval($_GET['id']);
$error_msg = '';

// 1. Fetch existing movie details
$stmt = $pdo->prepare("SELECT * FROM movie WHERE movie_id = :movie_id");
$stmt->execute([':movie_id' => $movie_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Movie not found.");
}

// 2. Handle form submission BEFORE any HTML is output
if (isset($_POST['update'])) {
    $movie_name        = trim($_POST['movie_name']);
    $movie_duration    = intval($_POST['movie_duration']);
    $movie_category    = trim($_POST['movie_category']);
    $movie_genre       = trim($_POST['movie_genre']);
    $movie_description = trim($_POST['movie_description']);
    $movie_lang        = trim($_POST['movie_language']);
    $movie_subtitle    = trim($_POST['movie_subtitle']);
    $movie_status      = trim($_POST['movie_status']);
    $path              = $row['path']; // Keep existing image pathway by default

    // Check if a new PNG file was uploaded
    if (isset($_FILES['movie_img']) && $_FILES['movie_img']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['movie_img'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file_ext !== 'png') {
            $error_msg = "Only PNG format is supported.";
        } else {
            $upload_dir = __DIR__ . '/assets/movie/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $relative_path = 'assets/movie/' . $movie_id . '.png';
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $movie_id . '.png')) {
                $path = $relative_path;
            } else {
                $error_msg = "Failed to save image file. Check directory write permissions.";
            }
        }
    } elseif (isset($_FILES['movie_img']) && $_FILES['movie_img']['error'] !== UPLOAD_ERR_NO_FILE) {
        $error_msg = "File upload error code: " . $_FILES['movie_img']['error'];
    }

    if ($error_msg === '') {
        try {
            $stmt = $pdo->prepare("
                UPDATE movie 
                SET movie_name = :movie_name,
                    synopsis = :synopsis,
                    genre = :genre,
                    duration = :duration,
                    language = :language,
                    subtitle = :subtitle,
                    path = :path,
                    current_showing_status = :status,
                    age_restriction = :age_restriction,
                    staff_id = :staff_id
                WHERE movie_id = :movie_id
            ");
            $stmt->execute([
                ':movie_name'      => $movie_name,
                ':synopsis'        => $movie_description,
                ':genre'           => $movie_genre,
                ':duration'        => $movie_duration,
                ':language'        => $movie_lang,
                ':subtitle'        => $movie_subtitle,
                ':path'            => $path,
                ':status'          => $movie_status,
                ':age_restriction' => $movie_category,
                ':staff_id'        => $staff_id,
                ':movie_id'        => $movie_id
            ]);

            header("Location: movie_management.php");
            exit();
        } catch (PDOException $e) {
            $error_msg = "Error updating movie: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Edit Movie</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h1>Edit Movie</h1>

    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
    <?php endif; ?>

    <form action="edit_movie.php?id=<?php echo $movie_id; ?>" method="post" enctype="multipart/form-data">
        Movie Name:<br>
        <input type="text" name="movie_name" value="<?php echo htmlspecialchars($row['movie_name']); ?>" required>
        <br><br>

        Duration (minutes):<br>
        <input type="number" name="movie_duration" value="<?php echo htmlspecialchars($row['duration']); ?>" min="1" required>
        <br><br>

        Category:<br>
        <select name="movie_category" required>
            <option value="adult" <?php echo (strtolower($row['age_restriction']) === 'adult') ? 'selected' : ''; ?>>Adult</option>
            <option value="child" <?php echo (strtolower($row['age_restriction']) === 'child') ? 'selected' : ''; ?>>Child</option>
        </select>
        <br><br>

        Genre:<br>
        <input type="text" name="movie_genre" value="<?php echo htmlspecialchars($row['genre']); ?>" required>
        <br><br>

        Language:<br>
        <input type="text" name="movie_language" value="<?php echo htmlspecialchars($row['language']); ?>" required>
        <br><br>

        Subtitle:<br>
        <input type="text" name="movie_subtitle" value="<?php echo htmlspecialchars($row['subtitle']); ?>" required>
        <br><br>

        Current Showing Status:<br>
        <select name="movie_status" required>
            <?php 
            $statuses = ['Upcoming', 'Now Showing', 'Ended'];
            foreach ($statuses as $status): 
            ?>
                <option value="<?php echo $status; ?>" <?php echo (strcasecmp($row['current_showing_status'], $status) === 0) ? 'selected' : ''; ?>>
                    <?php echo $status; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        Synopsis:<br>
        <input type="text" name="movie_description" value="<?php echo htmlspecialchars($row['synopsis']); ?>" required>
        <br><br>

        Current Image:<br>
        <?php if (!empty($row['path'])): ?>
            <img src="<?php echo htmlspecialchars($row['path']); ?>" alt="Movie Image" style="max-width: 120px; display: block; margin-top: 5px; margin-bottom: 10px;">
        <?php endif; ?>

        Upload New Image (optional):<br>
        <input type="file" name="movie_img" accept=".png,image/png">
        <br><br>

        <input type="submit" name="update" value="Update" class="btn">
    </form>
</body>
</html>
