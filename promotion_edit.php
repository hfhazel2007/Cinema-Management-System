<?php 
require_once __DIR__ . '/config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: promotion_management.php");
    exit();
}

$pdo = getDBConnection();
$promotion_id = intval($_GET['id']);
$error_msg = '';

// 1. Fetch existing promotion details first
$stmt = $pdo->prepare("SELECT * FROM promotion WHERE promotion_id = :promotion_id");
$stmt->execute([':promotion_id' => $promotion_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Promotion not found.");
}

// 2. Handle form submission BEFORE any HTML is output
if (isset($_POST['update'])) {
    $promotion_name      = trim($_POST['promotion_name']);
    $discount_percentage = floatval($_POST['discount_percentage']);
    $conditions          = trim($_POST['conditions']);
    $path                = $row['path']; // Keep existing image path by default

    // Check if a new PNG file was uploaded
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
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $error_msg = "File upload error code: " . $_FILES['image']['error'];
    }

    if ($error_msg === '') {
        try {
            $stmt = $pdo->prepare("
                UPDATE promotion 
                SET promotion_name = :promotion_name, 
                    discount_percentage = :discount_percentage, 
                    conditions = :conditions, 
                    path = :path 
                WHERE promotion_id = :promotion_id
            ");
            $stmt->execute([
                ':promotion_name'      => $promotion_name,
                ':discount_percentage' => $discount_percentage,
                ':conditions'          => $conditions,
                ':path'                => $path,
                ':promotion_id'        => $promotion_id
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
    <title>CineWave - Promotion Edit</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h1>Promotion Edit</h1>

    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
    <?php endif; ?>

    <form action="promotion_edit.php?id=<?php echo $promotion_id; ?>" method="post" enctype="multipart/form-data">
        Promotion Name:<br>
        <input type="text" name="promotion_name" value="<?php echo htmlspecialchars($row['promotion_name']); ?>" required>
        <br><br>

        Discount Percentage:<br>
        <input type="number" name="discount_percentage" value="<?php echo htmlspecialchars($row['discount_percentage']); ?>" min="0" max="100" step="0.01" required>
        <br><br>

        Current Photo:<br>
        <?php if (!empty($row['path'])): ?>
            <img src="<?php echo htmlspecialchars($row['path']); ?>" alt="Promotion Image" style="max-width: 150px; display: block; margin-bottom: 8px;">
        <?php endif; ?>
        Upload New Photo (optional, PNG only):<br>
        <input type="file" name="image" id="imageSelect" accept=".png,image/png">
        <br><br>

        Conditions:<br>
        <input type="text" name="conditions" value="<?php echo htmlspecialchars($row['conditions']); ?>" required>
        <br><br>

        <input type="submit" name="update" value="Update" class="btn">
    </form>
</body>
</html>
