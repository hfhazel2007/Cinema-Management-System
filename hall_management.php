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

// Added ORDER BY hall.hall_id ASC so updated rows stay in place
$sql = "SELECT hall.hall_id, hall.hall_number, hall.capacity, hall.hall_status, cinema.address 
        FROM hall 
        LEFT JOIN cinema ON hall.cinema_id = cinema.cinema_id 
        ORDER BY hall.hall_id ASC";
$result = $pdo->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Hall Management</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body class="body">
    <h1>Hall Management</h1>
    <table border="1" class="table">
        <tr>
            <th>ID</th>
            <th>Number</th>
            <th>Capacity</th>
            <th>Hall Status</th>
            <th>Branch</th>
            <?php if ($is_admin): ?>
                <th>Actions</th>
            <?php endif; ?>
        </tr>
        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['hall_id']); ?></td>
            <td><?php echo htmlspecialchars($row['hall_number']); ?></td>
            <td><?php echo htmlspecialchars($row['capacity']); ?></td>
            <td><?php echo htmlspecialchars($row['hall_status']); ?></td>
            <td><?php echo htmlspecialchars($row['address'] ?? 'N/A'); ?></td>
            <?php if ($is_admin): ?>
                <td>
                    <a href="hall_edit.php?id=<?php echo $row['hall_id']; ?>" class="btn">Edit</a>
                    <a href="hall_delete.php?id=<?php echo $row['hall_id']; ?>" 
                       class="btn" 
                       onclick="return confirm('Are you sure you want to delete this hall?');">Delete</a>
                </td>
            <?php endif; ?>
        </tr>
        <?php } ?>
    </table>
    <br>
    <?php if ($is_admin): ?>
        <a href="add_hall.php" class="btn">Add Hall</a>
    <?php endif; ?>
</body>
</html>
