<?php 
require_once __DIR__ . '/config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: showtime_management.php");
    exit();
}

$pdo = getDBConnection();
$showtime_id = intval($_GET['id']);
$error_msg = '';

// 1. Handle form submission BEFORE any HTML is output
if (isset($_POST['update'])) {
    $date            = $_POST['date'];
    $start_time      = $_POST['start_time'];
    $showtime_status = $_POST['showtime_status'];
    $hall_id         = trim($_POST['hall_id']);
    $movie_id        = trim($_POST['movie_id']);

    try {
        $stmt = $pdo->prepare("
            UPDATE showtime 
            SET date = :date, 
                start_time = :start_time, 
                showtime_status = :showtime_status, 
                hall_id = :hall_id, 
                movie_id = :movie_id 
            WHERE showtime_id = :showtime_id
        ");
        $stmt->execute([
            ':date'            => $date,
            ':start_time'      => $start_time,
            ':showtime_status' => $showtime_status,
            ':hall_id'         => $hall_id,
            ':movie_id'        => $movie_id,
            ':showtime_id'     => $showtime_id
        ]);

        header("Location: showtime_management.php");
        exit();
    } catch (PDOException $e) {
        $error_msg = "Error updating showtime: " . $e->getMessage();
    }
}

// 2. Fetch existing showtime details
$stmt = $pdo->prepare("SELECT * FROM showtime WHERE showtime_id = :showtime_id");
$stmt->execute([':showtime_id' => $showtime_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Showtime not found.");
}

// 3. Fetch Now Showing movies (plus the currently assigned movie in case its status changed)
$stmt_movie = $pdo->prepare("
    SELECT movie_id, movie_name 
    FROM movie 
    WHERE current_showing_status = 'Now Showing' OR movie_id = :current_movie_id
    ORDER BY movie_name ASC
");
$stmt_movie->execute([':current_movie_id' => $row['movie_id']]);
$movies = $stmt_movie->fetchAll(PDO::FETCH_ASSOC);

// 4. Fetch Available halls with their cinema branch (plus the currently assigned hall)
$stmt_hall = $pdo->prepare("
    SELECT hall.hall_id, hall.hall_number, cinema.address 
    FROM hall 
    LEFT JOIN cinema ON hall.cinema_id = cinema.cinema_id 
    WHERE hall.hall_status = 'Available' OR hall.hall_id = :current_hall_id
    ORDER BY cinema.address ASC, hall.hall_number ASC
");
$stmt_hall->execute([':current_hall_id' => $row['hall_id']]);
$halls = $stmt_hall->fetchAll(PDO::FETCH_ASSOC);

// Include header.php AFTER redirect logic
require_once __DIR__ . '/header.php';
?>            
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Showtime Edit</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h1>Showtime Edit</h1> 

    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
    <?php endif; ?>

    <form action="showtime_edit.php?id=<?php echo $showtime_id; ?>" method="post">
        Date:<br>
        <input type="date" name="date" value="<?php echo htmlspecialchars($row['date']); ?>" required>
        <br><br>

        Start Time:<br>
        <input type="time" name="start_time" value="<?php echo htmlspecialchars($row['start_time']); ?>" required>
        <br><br>

        Showtime Status:<br>
        <select name="showtime_status" required>
            <?php 
            $statuses = ['Open for Booking', 'Scheduled', 'Sold Out', 'Cancelled'];
            foreach ($statuses as $status): 
            ?>
                <option value="<?php echo $status; ?>" <?php echo ($row['showtime_status'] === $status) ? 'selected' : ''; ?>>
                    <?php echo $status; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        Movie:<br>
        <select name="movie_id" required>
            <?php foreach ($movies as $movie): ?>
                <option value="<?php echo htmlspecialchars($movie['movie_id']); ?>" <?php echo ($movie['movie_id'] == $row['movie_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($movie['movie_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        Hall:<br>
        <select name="hall_id" required>
            <?php foreach ($halls as $hall): ?>
                <option value="<?php echo htmlspecialchars($hall['hall_id']); ?>" <?php echo ($hall['hall_id'] == $row['hall_id']) ? 'selected' : ''; ?>>
                    Hall <?php echo htmlspecialchars($hall['hall_number']); ?>
                    <?php echo !empty($hall['address']) ? ' (' . htmlspecialchars($hall['address']) . ')' : ''; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        <input type="submit" name="update" value="Update" class="btn">
    </form>
</body>
</html>
