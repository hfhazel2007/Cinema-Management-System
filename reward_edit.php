<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';

// Ensure staff is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Validate reward_id from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: reward_management.php");
    exit();
}

$pdo = getDBConnection();
$staff_id = $_SESSION['id'];
$reward_id = intval($_GET['id']);
$error_msg = '';

// 1. Handle form submission BEFORE any HTML is output
if (isset($_POST['update'])) {
    $reward_name     = trim($_POST['reward_name']);
    $points_required = intval($_POST['points_required']);
    $exp_date        = $_POST['exp_date'];

    try {
        $stmt = $pdo->prepare("
            UPDATE reward 
            SET reward_name = :reward_name, 
                points_required = :points_required, 
                exp_date = :exp_date, 
                staff_id = :staff_id 
            WHERE reward_id = :reward_id
        ");
        $stmt->execute([
            ':reward_name'     => $reward_name,
            ':points_required' => $points_required,
            ':exp_date'        => $exp_date,
            ':staff_id'        => $staff_id,
            ':reward_id'       => $reward_id
        ]);

        header("Location: reward_management.php");
        exit();
    } catch (PDOException $e) {
        $error_msg = "Error updating reward: " . $e->getMessage();
    }
}

// 2. Fetch single reward using fetch() (NOT fetchAll())
$stmt = $pdo->prepare("SELECT * FROM reward WHERE reward_id = :reward_id");
$stmt->execute([':reward_id' => $reward_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Reward not found.");
}

// Include header.php AFTER redirect logic
require_once __DIR__ . '/header.php';
?>            
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Reward Edit</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h1>Reward Edit</h1> 

    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
    <?php endif; ?>

    <form action="reward_edit.php?id=<?php echo $reward_id; ?>" method="post">
        Name:<br>
        <input type="text" name="reward_name" value="<?php echo htmlspecialchars($row['reward_name']); ?>" required>
        <br><br>

        Points Required:<br>
        <input type="number" name="points_required" value="<?php echo htmlspecialchars($row['points_required']); ?>" min="1" required>
        <br><br>

        Expiry Date:<br>
        <input type="date" name="exp_date" value="<?php echo htmlspecialchars($row['exp_date']); ?>" required>
        <br><br>
       
        <input type="submit" name="update" value="Update" class="btn">
    </form>
</body>
</html>
