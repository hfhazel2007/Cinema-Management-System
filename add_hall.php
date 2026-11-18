<?php 
require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();
$error_msg = '';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: hall_management.php");
    exit();
}
// 1. Handle form submission BEFORE any HTML is output
if (isset($_POST['submit'])) {
    $hall_number = trim($_POST['hall_number']);
    $capacity    = intval($_POST['capacity']);
    $hall_row    = intval($_POST['hall_row']);
    $hall_status = $_POST['hall_status'];
    $cinema_id   = isset($_POST['cinema_id']) ? trim($_POST['cinema_id']) : '';

    if ($cinema_id === '') {
        $error_msg = "Please select a valid cinema branch.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO hall (hall_number, capacity, hall_row, hall_status, cinema_id) 
                VALUES (:hall_number, :capacity, :hall_row, :hall_status, :cinema_id)
            ");
            $stmt->execute([
                ':hall_number' => $hall_number,
                ':capacity'    => $capacity,
                ':hall_row'    => $hall_row,
                ':hall_status' => $hall_status,
                ':cinema_id'   => $cinema_id
            ]);

            header("Location: hall_management.php");
            exit();
        } catch (PDOException $e) {
            $error_msg = "Error adding hall: " . $e->getMessage();
        }
    }
}

// 2. Fetch all cinemas for the radio buttons
$stmt_cinema = $pdo->query("SELECT cinema_id, address FROM cinema ORDER BY cinema_id ASC");
$cinemas = $stmt_cinema->fetchAll(PDO::FETCH_ASSOC);

// Include header.php AFTER redirect logic
require_once __DIR__ . '/header.php';
?>            
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Add Hall</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h1>Add Hall</h1> 

    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
    <?php endif; ?>

    <form action="add_hall.php" method="post">
        Hall Number:<br>
        <input type="text" name="hall_number" required>
        <br><br>

        Capacity:<br>
        <input type="number" name="capacity" min="1" required>
        <br><br>

        Number of Rows:<br>
        <input type="number" name="hall_row" min="1" max="26" required>
        <br><br>

        Hall Status:<br>
        <select name="hall_status" required>
            <option value="Available">Available</option>
            <option value="Under Maintenance">Under Maintenance</option>
            <option value="Unavailable">Unavailable</option>
        </select>
        <br><br>
        
        Branch:<br>
        <select name="cinema_id" required>
            <option value="">Select a branch</option>
            <?php foreach ($cinemas as $cinema): ?>
                <option value="<?php echo htmlspecialchars($cinema['cinema_id']); ?>">
                    <?php echo htmlspecialchars($cinema['address']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        <input type="submit" name="submit" value="Submit" class="btn">
    </form>
</body>
</html>
