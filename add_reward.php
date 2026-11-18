<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';

// 1. Ensure staff is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: reward_management.php");
    exit();
}
$pdo = getDBConnection();
$staff_id = $_SESSION['id'];
$error_msg = '';

// 2. Handle form submission BEFORE any HTML is output
if (isset($_POST['submit'])) {
    $reward_name     = trim($_POST['reward_name']);
    $points_required = intval($_POST['points_required']);
    $exp_date        = $_POST['exp_date'];

    try {
        $stmt = $pdo->prepare("
            INSERT INTO reward (reward_name, points_required, exp_date, staff_id) 
            VALUES (:reward_name, :points_required, :exp_date, :staff_id)
        ");
        $stmt->execute([
            ':reward_name'     => $reward_name,
            ':points_required' => $points_required,
            ':exp_date'        => $exp_date,
            ':staff_id'        => $staff_id
        ]);

        header("Location: reward_management.php");
        exit();
    } catch (PDOException $e) {
        $error_msg = "Error adding reward: " . $e->getMessage();
    }
}

// Include header.php AFTER redirect logic
require_once __DIR__ . '/header.php';
?>            
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Add Reward</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h1>Add Reward</h1> 

    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
    <?php endif; ?>

    <form action="add_reward.php" method="post">
        Name:<br>
        <input type="text" name="reward_name" required>
        <br><br>

        Points Required:<br>
        <input type="number" name="points_required" min="1" required>
        <br><br>

        Expiry Date:<br>
        <input type="date" name="exp_date" required>
        <br><br>
       
        <input type="submit" name="submit" value="Submit" class="btn">
    </form>
</body>
</html>
