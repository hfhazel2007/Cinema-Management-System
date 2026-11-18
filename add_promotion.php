<?php 
require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();
$error_msg = '';

// 1. Handle form submission BEFORE any HTML is output
if (isset($_POST["submit"])) {
    $promotion_name      = trim($_POST['promotion_name']);
    $discount_percentage = floatval($_POST['discount_percentage']);
    $conditions          = trim($_POST['conditions']);
    $path                = '';

    // Validate and upload PNG file into assets/promotion/
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['image'];
        $filename = basename($file['name']);
        $filetemp = $file['tmp_name'];
        $fileExt  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($fileExt !== 'png') {
            $error_msg = "Only PNG files are allowed.";
        } else {
            $target_dir = __DIR__ . "/assets/promotion/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            $clean_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
            $relative_path  = "assets/promotion/" . $clean_filename;

            if (move_uploaded_file($filetemp, $target_dir . $clean_filename)) {
                $path = $relative_path;
            } else {
                $error_msg = "Failed to move uploaded file. Check directory write permissions.";
            }
        }
    } else {
        $error_msg = "Please upload a valid PNG image.";
    }

    // 2. Insert promotion details and file pathway into the database
    if ($error_msg === '') {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO promotion (promotion_name, discount_percentage, conditions, path) 
                VALUES (:promotion_name, :discount_percentage, :conditions, :path)
            ");
            $stmt->execute([
                ':promotion_name'      => $promotion_name,
                ':discount_percentage' => $discount_percentage,
                ':conditions'          => $conditions,
                ':path'                => $path
            ]);

            header("Location: promotion_management.php");
            exit();
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}

// Include header.php AFTER redirect logic
require_once __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Add Promotion</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h1>Add Promotion</h1>

    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
    <?php endif; ?>

    <form action="add_promotion.php" method="post" enctype="multipart/form-data">
        Promotion Name:<br>
        <input type="text" name="promotion_name" required>
        <br><br>

        Discount Percentage:<br>
        <input type="number" name="discount_percentage" min="0" max="100" step="0.01" required>
        <br><br>

        Photo:<br>
        <input type="file" name="image" id="imageSelect" accept=".png,image/png" required>
        <br><br>

        Conditions:<br>
        <input type="text" name="conditions" required>
        <br><br>

        <input type="submit" name="submit" value="Submit" class="btn">
    </form>
</body>
</html>
