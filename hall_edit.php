<?php
require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();
$hall_id = isset($_GET['id']) ? intval($_GET['id']) : 1;
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
if (isset($_POST['update'])) {
    $hall_number = trim($_POST['hall_number']);
    $capacity    = intval($_POST['capacity']);
    $hall_status = $_POST['hall_status'];
    $cinema_id   = isset($_POST['cinema_id']) ? trim($_POST['cinema_id']) : '';

    if ($cinema_id === '') {
        $error_msg = "Please select a valid cinema branch.";
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE hall 
                SET hall_number = :hall_number, 
                    capacity = :capacity, 
                    hall_status = :hall_status, 
                    cinema_id = :cinema_id 
                WHERE hall_id = :hall_id
            ");
            $stmt->execute([
                ':hall_number' => $hall_number,
                ':capacity'    => $capacity,
                ':hall_status' => $hall_status,
                ':cinema_id'   => $cinema_id,
                ':hall_id'     => $hall_id
            ]);

            header("Location: hall_management.php");
            exit();
        } catch (PDOException $e) {
            $error_msg = "Error updating hall: " . $e->getMessage();
        }
    }
}

// 2. Fetch ONLY the single hall being edited (keep WHERE hall_id = :hall_id here!)
$stmt = $pdo->prepare("SELECT * FROM hall WHERE hall_id = :hall_id");
$stmt->execute([':hall_id' => $hall_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Hall not found.");
}

// 3. Fetch all cinemas
$stmt_cinema = $pdo->query("SELECT cinema_id, address FROM cinema ORDER BY cinema_id ASC");
$cinemas = $stmt_cinema->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Hall Edit</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h1>Hall Edit</h1>

    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
    <?php endif; ?>

    <form action="hall_edit.php?id=<?php echo htmlspecialchars($hall_id); ?>" method="post">
        Hall Number:<br>
        <input type="text" name="hall_number" value="<?php echo htmlspecialchars($row['hall_number']); ?>" required>
        <br><br>

        Capacity:<br>
        <input type="number" name="capacity" value="<?php echo htmlspecialchars($row['capacity']); ?>" min="1" required>
        <br><br>

        Hall Status:<br>
        <select name="hall_status" required>
            <option value="Available" <?php echo ($row['hall_status'] === 'Available') ? 'selected' : ''; ?>>Available</option>
            <option value="Unavailable" <?php echo ($row['hall_status'] === 'Unavailable') ? 'selected' : ''; ?>>Unavailable</option>
        </select>
        <br><br>

        Branch:<br>
        <select name="cinema_id" required>
            <?php foreach ($cinemas as $cinema): ?>
                <option value="<?php echo htmlspecialchars($cinema['cinema_id']); ?>" <?php echo ($cinema['cinema_id'] == $row['cinema_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cinema['address']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        <input type="submit" name="update" value="Update" class="btn">
    </form>
</body>
</html>
