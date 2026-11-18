<?php 
require_once __DIR__ . '/config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: fnbmanagement.php");
    exit();
}

$pdo = getDBConnection();
$fnb_id = intval($_GET['id']);
$error_msg = '';

// 1. Fetch existing item
$stmt = $pdo->prepare("SELECT * FROM fnb WHERE fnb_id = :fnb_id");
$stmt->execute([':fnb_id' => $fnb_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("F&B item not found.");
}

// 2. Handle update BEFORE HTML output
if (isset($_POST['update'])) {
    $name        = trim($_POST['fnb_name']);
    $price       = floatval($_POST['fnb_price']);
    $staff       = intval($_POST['staff_id']);
    $description = trim($_POST['description']);
    $path        = $row['path'];

    // Optional new image upload
    if (isset($_FILES['fnb_img']) && $_FILES['fnb_img']['error'] === UPLOAD_ERR_OK) {
        $folder_path = "asset/fnbpicture/";
        if (!is_dir(__DIR__ . '/' . $folder_path)) {
            mkdir(__DIR__ . '/' . $folder_path, 0755, true);
        }

        $db_path = $folder_path . $fnb_id . ".png";
        if (move_uploaded_file($_FILES['fnb_img']['tmp_name'], __DIR__ . '/' . $db_path)) {
            $path = $db_path;
        } else {
            $error_msg = "Failed to upload new image.";
        }
    }

    if ($error_msg === '') {
        try {
            $stmt = $pdo->prepare("
                UPDATE fnb 
                SET fnb_name = :name, 
                    fnb_price = :price, 
                    staff_id = :staff, 
                    description = :description, 
                    path = :path 
                WHERE fnb_id = :fnb_id
            ");
            $stmt->execute([
                ':name'        => $name,
                ':price'       => $price,
                ':staff'       => $staff,
                ':description' => $description,
                ':path'        => $path,
                ':fnb_id'      => $fnb_id
            ]);

            header("Location: fnbmanagement.php");
            exit();
        } catch (PDOException $e) {
            $error_msg = "Error updating F&B item: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Edit F&amp;B</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h1>Edit F&amp;B</h1>

    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
    <?php endif; ?>

    <form action="fnb_edit.php?id=<?php echo $fnb_id; ?>" method="post" enctype="multipart/form-data">
        Item Name:<br>
        <input type="text" name="fnb_name" value="<?php echo htmlspecialchars($row['fnb_name']); ?>" required>
        <br><br>

        Price (RM):<br>
        <input type="number" step="0.01" min="0" name="fnb_price" value="<?php echo htmlspecialchars($row['fnb_price']); ?>" required>
        <br><br>

        Staff ID:<br>
        <input type="number" name="staff_id" value="<?php echo htmlspecialchars($row['staff_id']); ?>" required>
        <br><br>

        Description:<br>
        <input type="text" name="description" value="<?php echo htmlspecialchars($row['description']); ?>" required>
        <br><br>

        Current Image:<br>
        <?php if (!empty($row['path'])): ?>
            <img src="<?php echo htmlspecialchars($row['path']); ?>" alt="Current Image" style="max-width: 120px; display: block; margin-bottom: 8px;">
        <?php endif; ?>
        Upload New Image (optional):<br>
        <input type="file" name="fnb_img" accept="image/*">
        <br><br>

        <input type="submit" name="update" value="Update" class="btn">
    </form>
</body>
</html>
