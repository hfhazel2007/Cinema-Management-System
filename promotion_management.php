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

$sql = "SELECT * FROM promotion ORDER BY promotion_id ASC";
$result = $pdo->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Promotion Management</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body class="body">
    <h1>Promotion Management</h1>
    <table border="1" class="table">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Poster</th>
            <th>Discount Percentage</th>
            <th>Condition</th>
            <?php if ($is_admin): ?>
                <th>Actions</th>
            <?php endif; ?>
        </tr>
        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['promotion_id']); ?></td>
            <td><?php echo htmlspecialchars($row['promotion_name']); ?></td>
            <td>
                <?php if (!empty($row['path'])): ?>
                    <img src="<?php echo htmlspecialchars($row['path']); ?>" alt="<?php echo htmlspecialchars($row['promotion_name']); ?>" width="250px">
                <?php else: ?>
                    No Image
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($row['discount_percentage']); ?></td>
            <td><?php echo htmlspecialchars($row['conditions']); ?></td>
            <?php if ($is_admin): ?>
                <td>
                    <a href="promotion_edit.php?id=<?php echo $row['promotion_id']; ?>" class="btn">Edit</a>
                    <a href="promotion_delete.php?id=<?php echo $row['promotion_id']; ?>" 
                       class="btn" 
                       onclick="return confirm('Are you sure you want to delete this promotion?');">Delete</a>
                </td>
            <?php endif; ?>
        </tr>
        <?php } ?>
    </table>
    <br>
    <?php if ($is_admin): ?>
        <a href="add_promotion.php" class="btn">Add Promotion</a>
    <?php endif; ?>
</body>
</html>
