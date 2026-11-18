<?php 
require_once __DIR__ . '/config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: booking_management.php");
    exit();
}

$pdo = getDBConnection();
$group_booking_id = intval($_GET['id']);
$error_msg = '';

// 1. Handle form submission BEFORE any HTML is output
if (isset($_POST['update'])) {
    $group_booking_name   = trim($_POST['group_booking_name']);
    $movie_id             = !empty($_POST['movie_id']) ? intval($_POST['movie_id']) : null;
    $booking_date         = !empty($_POST['booking_date']) ? $_POST['booking_date'] : null;
    $group_booking_status = $_POST['group_booking_status'];
    $staff_id             = intval($_POST['staff_id']);

    try {
        $stmt = $pdo->prepare("
            UPDATE group_booking 
            SET group_booking_name = :name, 
                movie_id = :movie_id,
                booking_date = :booking_date,
                group_booking_status = :status,
                staff_id = :staff_id
            WHERE group_booking_id = :gb_id
        ");
        $stmt->execute([
            ':name'         => $group_booking_name,
            ':movie_id'     => $movie_id,
            ':booking_date' => $booking_date,
            ':status'       => $group_booking_status,
            ':staff_id'     => $staff_id,
            ':gb_id'        => $group_booking_id
        ]);

        header("Location: booking_management.php");
        exit();
    } catch (PDOException $e) {
        if ($e->getCode() === '23503') {
            $error_msg = "Update Failed: Invalid Staff ID or Movie ID.";
        } else {
            $error_msg = "Error updating booking: " . $e->getMessage();
        }
    }
}

// 2. Fetch existing booking details
$stmt = $pdo->prepare("
    SELECT DISTINCT ON (gb.group_booking_id)
        gb.group_booking_id,
        gb.group_booking_name,
        gb.group_booking_status,
        gb.staff_id,
        gb.movie_id,
        gb.booking_date,
        s.start_time
    FROM group_booking gb
    LEFT JOIN total_order t_o ON gb.group_booking_id = t_o.group_booking_id
    LEFT JOIN ticket t ON t_o.ticket_id = t.ticket_id
    LEFT JOIN showtime s ON t.showtime_id = s.showtime_id
    WHERE gb.group_booking_id = :gb_id
");
$stmt->execute([':gb_id' => $group_booking_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Group booking not found.");
}

// 3. Fetch all movies for the dropdown
$stmt_movies = $pdo->query("SELECT movie_id, movie_name FROM movie ORDER BY movie_name ASC");
$movies = $stmt_movies->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Edit Booking</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h1>Edit Group Booking</h1>

    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
    <?php endif; ?>

    <form action="booking_edit.php?id=<?php echo $group_booking_id; ?>" method="post">
        Group Name:<br>
        <input type="text" name="group_booking_name" value="<?php echo htmlspecialchars($row['group_booking_name']); ?>" required>
        <br><br>

        Movie:<br>
        <select name="movie_id" required>
            <option value="">-- Select Movie --</option>
            <?php foreach ($movies as $movie): ?>
                <option value="<?php echo htmlspecialchars($movie['movie_id']); ?>" <?php echo ($movie['movie_id'] == $row['movie_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($movie['movie_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        Booking Date:<br>
        <input type="date" name="booking_date" value="<?php echo htmlspecialchars($row['booking_date'] ?? ''); ?>" required>
        <br><br>

        Start Time (Read-only):<br>
        <input type="text" value="<?php echo htmlspecialchars($row['start_time'] ?? 'N/A'); ?>" readonly>
        <br><br>

        Status:<br>
        <select name="group_booking_status" required>
            <?php 
            $statuses = ['Pending', 'Confirmed', 'Cancelled'];
            foreach ($statuses as $status): 
            ?>
                <option value="<?php echo $status; ?>" <?php echo ($row['group_booking_status'] === $status) ? 'selected' : ''; ?>>
                    <?php echo $status; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        Staff ID:<br>
        <input type="number" name="staff_id" value="<?php echo htmlspecialchars($row['staff_id'] ?? ''); ?>" min="1" required>
        <br><br>

        <input type="submit" name="update" value="Update" class="btn">
    </form>
</body>
</html>
