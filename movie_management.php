<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();

// Determine whether the logged-in user is an Admin
$is_admin = false;
$role = $_SESSION['role'] ?? ($_SESSION['position'] ?? ($_SESSION['staff_role'] ?? ''));

if ($role !== '') {
    $is_admin = (strcasecmp(trim($role), 'Admin') === 0);
} else {
    // Fallback: fetch the role from the staff table using the logged-in staff ID
    $staff_id = $_SESSION['staff_id'] ?? ($_SESSION['id'] ?? null);
    if ($staff_id) {
        $stmt = $pdo->prepare("SELECT role FROM staff WHERE staff_id = :staff_id");
        $stmt->execute([':staff_id' => $staff_id]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($staff && isset($staff['role'])) {
            $is_admin = (strcasecmp(trim($staff['role']), 'Admin') === 0);
        }
    }
}

require_once __DIR__ . '/header.php';

// Sorted by movie_id ASC so edited movies stay in their exact row position
$sql = "SELECT movie_id, movie_name, genre, duration, language, subtitle, age_restriction, current_showing_status, path 
        FROM movie 
        ORDER BY movie_id ASC";
$result = $pdo->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Movie Management</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body class="body">
    <h1>Movie Management</h1>
    <table border="1" class="table">
        <tr>
            <th>ID</th>
            <th>Poster</th>
            <th>Movie Name</th>
            <th>Genre</th>
            <th>Duration (mins)</th>
            <th>Language / Subtitle</th>
            <th>Category</th>
            <th>Status</th>
            <?php if ($is_admin): ?>
                <th>Actions</th>
            <?php endif; ?>
        </tr>
        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['movie_id']); ?></td>
            <td>
                <?php if (!empty($row['path'])): ?>
                    <img src="<?php echo htmlspecialchars($row['path']); ?>" alt="Poster" style="width: 60px; height: auto;">
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($row['movie_name']); ?></td>
            <td><?php echo htmlspecialchars($row['genre']); ?></td>
            <td><?php echo htmlspecialchars($row['duration']); ?></td>
            <td><?php echo htmlspecialchars($row['language'] . ' / ' . $row['subtitle']); ?></td>
            <td><?php echo htmlspecialchars(ucfirst($row['age_restriction'])); ?></td>
            <td><?php echo htmlspecialchars($row['current_showing_status']); ?></td>
            <?php if ($is_admin): ?>
                <td>
                    <a href="movie_edit.php?id=<?php echo $row['movie_id']; ?>" class="btn">Edit</a>
                    <a href="movie_delete.php?id=<?php echo $row['movie_id']; ?>" 
                       class="btn" 
                       onclick="return confirm('Are you sure you want to delete this movie?');">Delete</a>
                </td>
            <?php endif; ?>
        </tr>
        <?php } ?>
    </table>
    <br>
    <?php if ($is_admin): ?>
        <a href="add_movie.php" class="btn">Add Movie</a>
    <?php endif; ?>
</body>
</html>
