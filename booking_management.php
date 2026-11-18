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

$sql = "SELECT DISTINCT ON (gb.group_booking_id)
            gb.group_booking_id,
            gb.group_booking_name,
            gb.group_booking_status,
            gb.staff_id,
            gb.booking_date,
            s.start_time,
            m.movie_name
        FROM group_booking gb
        LEFT JOIN movie m ON gb.movie_id = m.movie_id
        LEFT JOIN total_order t_o ON gb.group_booking_id = t_o.group_booking_id
        LEFT JOIN ticket t ON t_o.ticket_id = t.ticket_id
        LEFT JOIN showtime s ON t.showtime_id = s.showtime_id
        ORDER BY gb.group_booking_id ASC";

$result = $pdo->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Booking Management</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body class="body">
    <h1>Booking Management</h1>
    <table border="1" class="table">
        <tr>
            <th>ID</th>
            <th>Group Name</th>
            <th>Movie Name</th>
            <th>Booking Date</th>
            <th>Start Time</th>
            <th>Status</th>
            <th>Staff ID</th>
            <?php if ($is_admin): ?>
                <th>Actions</th>
            <?php endif; ?>
        </tr>
        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['group_booking_id']); ?></td>
            <td><?php echo htmlspecialchars($row['group_booking_name']); ?></td>
            <td><?php echo htmlspecialchars($row['movie_name'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($row['booking_date'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($row['start_time'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($row['group_booking_status']); ?></td>
            <td><?php echo htmlspecialchars($row['staff_id'] ?? 'Unassigned'); ?></td>
            <?php if ($is_admin): ?>
                <td>
                    <a href="booking_edit.php?id=<?php echo $row['group_booking_id']; ?>" class="btn">Edit</a>
                    <a href="booking_delete.php?id=<?php echo $row['group_booking_id']; ?>" 
                       class="btn" 
                       onclick="return confirm('Are you sure you want to delete this group booking?');">Delete</a>
                </td>
            <?php endif; ?>
        </tr>
        <?php } ?>
    </table>
    <br>
    <?php if ($is_admin): ?>
        <a href="add_booking.php" class="btn">Add Booking</a>
    <?php endif; ?>
</body>
</html>
