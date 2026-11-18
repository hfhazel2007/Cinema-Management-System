<?php 
require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();
$error_msg = '';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: showtime_management.php");
    exit();
}
// 1. Handle form submission BEFORE any HTML is output
if (isset($_POST['add'])) {
    $date            = $_POST['date'];
    $start_time      = $_POST['start_time'];
    $showtime_status = $_POST['showtime_status'];
    $hall_id         = trim($_POST['hall_id']);
    $movie_id        = trim($_POST['movie_id']);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO showtime (date, start_time, showtime_status, movie_id, hall_id) 
            VALUES (:date, :start_time, :showtime_status, :movie_id, :hall_id)
        ");
        $stmt->execute([
            ':date'            => $date,
            ':start_time'      => $start_time,
            ':showtime_status' => $showtime_status,
            ':movie_id'        => $movie_id,
            ':hall_id'         => $hall_id
        ]);

        header("Location: showtime_management.php");
        exit();
    } catch (PDOException $e) {
        $error_msg = "Error adding showtime: " . $e->getMessage();
    }
}

// 2. Fetch movies (filter by 'Now Showing' or 'Coming Soon' if desired, or all movies)
$stmt_movie = $pdo->query("SELECT movie_id, movie_name FROM movie ORDER BY movie_name ASC");
$movies = $stmt_movie->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch Available halls along with their cinema branch address
$stmt_hall = $pdo->query("
    SELECT hall.hall_id, hall.hall_number, cinema.address 
    FROM hall 
    LEFT JOIN cinema ON hall.cinema_id = cinema.cinema_id 
    WHERE hall.hall_status = 'Available'
    ORDER BY cinema.address ASC, hall.hall_number ASC
");
$halls = $stmt_hall->fetchAll(PDO::FETCH_ASSOC);

// Include header.php AFTER redirect logic
require_once __DIR__ . '/header.php';
?>            
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Add Showtime</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h1>Add Showtime</h1> 

    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
    <?php endif; ?>

    <form action="add_showtime.php" method="post">
        Date:<br>
        <input type="date" name="date" required>
        <br><br>

        Start Time:<br>
        <input type="time" name="start_time" required>
        <br><br>

        Showtime Status:<br>
        <select name="showtime_status" required>
            <option value="Open for Booking">Open for Booking</option>
            <option value="Scheduled">Scheduled</option>
            <option value="Sold Out">Sold Out</option>
            <option value="Cancelled">Cancelled</option>
        </select>
        <br><br>

        Movie:<br>
        <select name="movie_id" required>
            <?php foreach ($movies as $movie): ?>
                <option value="<?php echo htmlspecialchars($movie['movie_id']); ?>">
                    <?php echo htmlspecialchars($movie['movie_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        Hall:<br>
        <select name="hall_id" required>
            <?php foreach ($halls as $hall): ?>
                <option value="<?php echo htmlspecialchars($hall['hall_id']); ?>">
                    Hall <?php echo htmlspecialchars($hall['hall_number']); ?>
                    <?php echo !empty($hall['address']) ? ' (' . htmlspecialchars($hall['address']) . ')' : ''; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        <input type="submit" name="add" value="Add Showtime" class="btn">
    </form>
</body>
</html>
