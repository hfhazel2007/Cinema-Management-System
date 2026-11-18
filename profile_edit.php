<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$pdo = getDBConnection();
$id = $_SESSION['id'];
$error_msg = "";

// 1. Handle form submission BEFORE outputting HTML
if (isset($_POST["submit"])) {
    $fullname = trim($_POST["fullname"]);
    $phone_number = trim($_POST["phone_number"]);
    $email = trim($_POST["email"]);
    $date_of_birth = $_POST["date_of_birth"];

    try {
        $sql = "UPDATE customer 
                SET fullname = :fullname, 
                    phone_number = :phone_number, 
                    email = :email, 
                    date_of_birth = :date_of_birth 
                WHERE membership_id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':fullname'      => $fullname,
            ':phone_number'  => $phone_number,
            ':email'         => $email,
            ':date_of_birth' => $date_of_birth,
            ':id'            => $id
        ]);

        // Use JS redirect since header.php already outputted HTML
        echo "<script>alert('Profile updated successfully!'); window.location.href = 'aboutme.php';</script>";
        exit();
    } catch (PDOException $e) {
        $error_msg = "Error updating profile: " . htmlspecialchars($e->getMessage());
    }
}

// 2. Fetch current customer data using a prepared statement
$stmt = $pdo->prepare("SELECT * FROM customer WHERE membership_id = :id");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>CineWave - Profile Edit</title>
    </head>
    <body class="body">
        <?php if (!empty($error_msg)): ?>
            <p style="color: red;"><?php echo $error_msg; ?></p>
        <?php endif; ?>

        <form method="post">
            Full Name:<br>
            <input type="text" name="fullname" value="<?php echo htmlspecialchars($row['fullname'] ?? ''); ?>" required>
            <br><br>

            Phone Number:<br>
            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($row['phone_number'] ?? ''); ?>" required>
            <br><br>

            Email:<br>
            <input type="email" name="email" value="<?php echo htmlspecialchars($row['email'] ?? ''); ?>" required>
            <br><br>

            Date Of Birth:<br>
            <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars($row['date_of_birth'] ?? ''); ?>" required>
            <br><br>

            <input type="submit" name="submit" value="Update Customer">
        </form>
    </body>
</html>