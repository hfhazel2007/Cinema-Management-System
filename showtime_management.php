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

$sql = "SELECT showtime.showtime_id, showtime.start_time, showtime.date, showtime.showtime_status, movie.movie_name, hall.hall_number 
        FROM showtime 
        LEFT JOIN movie ON showtime.movie_id = movie.movie_id 
        LEFT JOIN hall ON showtime.hall_id = hall.hall_id 
        ORDER BY showtime.showtime_id ASC";
$result = $pdo->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Showtime Management</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body class="body">
    <h1>Showtime Management</h1>
    <table border="1" class="table">
        <tr>
            <th>ID</th>
            <th>Date</th>
            <th>Start Time</th>
            <th>Status</th>
            <th>Movie Name</th>
            <th>Hall Number</th>
            <?php if ($is_admin): ?>
                <th>Actions</th>
            <?php endif; ?>
        </tr>
        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['showtime_id']); ?></td>
            <td><?php echo htmlspecialchars($row['date']); ?></td>
            <td><?php echo htmlspecialchars($row['start_time']); ?></td>
            <td><?php echo htmlspecialchars($row['showtime_status']); ?></td>
            <td><?php echo htmlspecialchars($row['movie_name'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($row['hall_number'] ?? 'N/A'); ?></td>
            <?php if ($is_admin): ?>
                <td>
                    <a href="showtime_edit.php?id=<?php echo $row['showtime_id']; ?>" class="btn">Edit</a>
                    <a href="showtime_delete.php?id=<?php echo $row['showtime_id']; ?>" 
                       class="btn" 
                       onclick="return confirm('Are you sure you want to delete this showtime?');">Delete</a>
                </td>
            <?php endif; ?>
        </tr>
        <?php } ?>
    </table>
    <br>
    <?php if ($is_admin): ?>
        <a href="add_showtime.php" class="btn">Add Showtime</a>
    <?php endif; ?>
</body>
</html>
