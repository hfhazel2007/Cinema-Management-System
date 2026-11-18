<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();
$default_staff_id = $_SESSION['staff_id'] ?? ($_SESSION['id'] ?? '');
$error_msg = '';

// 1. Handle form submission BEFORE any HTML is output
if (isset($_POST['submit'])) {
    $group_booking_name   = trim($_POST['group_booking_name']);
    $movie_id             = intval($_POST['movie_id']);
    $booking_date         = $_POST['booking_date'];
    $group_booking_status = $_POST['group_booking_status'];
    $staff_id             = intval($_POST['staff_id']);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO group_booking (group_booking_name, movie_id, booking_date, group_booking_status, staff_id) 
            VALUES (:name, :movie_id, :booking_date, :status, :staff_id)
        ");
        $stmt->execute([
            ':name'         => $group_booking_name,
            ':movie_id'     => $movie_id,
            ':booking_date' => $booking_date,
            ':status'       => $group_booking_status,
            ':staff_id'     => $staff_id
        ]);

        header("Location: booking_management.php");
        exit();
    } catch (PDOException $e) {
        if ($e->getCode() === '23503') {
            $error_msg = "Save Failed: Invalid Staff ID or Movie ID.";
        } else {
            $error_msg = "Error adding booking: " . $e->getMessage();
        }
    }
}

// 2. Fetch movies for dropdown
$stmt_movies = $pdo->query("SELECT movie_id, movie_name FROM movie ORDER BY movie_name ASC");
$movies = $stmt_movies->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Add Booking</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h1>Add Group Booking</h1>

    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
    <?php endif; ?>

    <form action="add_booking.php" method="post">
        Group Name:<br>
        <input type="text" name="group_booking_name" required>
        <br><br>

        Movie:<br>
        <select name="movie_id" required>
            <option value="">-- Select Movie --</option>
            <?php foreach ($movies as $movie): ?>
                <option value="<?php echo htmlspecialchars($movie['movie_id']); ?>">
                    <?php echo htmlspecialchars($movie['movie_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        Booking Date:<br>
        <input type="date" name="booking_date" required>
        <br><br>

        Status:<br>
        <select name="group_booking_status" required>
            <option value="Pending">Pending</option>
            <option value="Confirmed">Confirmed</option>
            <option value="Cancelled">Cancelled</option>
        </select>
        <br><br>

        Staff ID:<br>
        <input type="number" name="staff_id" value="<?php echo htmlspecialchars($default_staff_id); ?>" min="1" required>
        <br><br>

        <input type="submit" name="submit" value="Submit" class="btn">
    </form>
</body>
</html>
