<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();
$default_staff = isset($_SESSION['id']) ? $_SESSION['id'] : 1001;
$error_msg = '';

if (isset($_POST['submit'])) {
    $name        = trim($_POST['fnb_name']);
    $price       = floatval($_POST['fnb_price']);
    $staff       = intval($_POST['staff_id']);
    $description = trim($_POST['description']);

    try {
        // 1. Insert item and retrieve the generated fnb_id
        $stmt = $pdo->prepare("
            INSERT INTO fnb (fnb_name, fnb_price, staff_id, description, path)
            VALUES (:name, :price, :staff, :description, '')
            RETURNING fnb_id
        ");
        $stmt->execute([
            ':name'        => $name,
            ':price'       => $price,
            ':staff'       => $staff,
            ':description' => $description
        ]);
        $new_id = $stmt->fetchColumn();

        // 2. Upload image using the new fnb_id as filename
        if (isset($_FILES['fnb_img']) && $_FILES['fnb_img']['error'] === UPLOAD_ERR_OK) {
            $folder_path = "asset/fnbpicture/";
            if (!is_dir(__DIR__ . '/' . $folder_path)) {
                mkdir(__DIR__ . '/' . $folder_path, 0755, true);
            }

            $db_path = $folder_path . $new_id . ".png";
            if (move_uploaded_file($_FILES['fnb_img']['tmp_name'], __DIR__ . '/' . $db_path)) {
                $stmt_img = $pdo->prepare("UPDATE fnb SET path = :path WHERE fnb_id = :id");
                $stmt_img->execute([':path' => $db_path, ':id' => $new_id]);
            }
        }

        header("Location: fnbmanagement.php");
        exit();
    } catch (PDOException $e) {
        $error_msg = "Error adding F&B item: " . $e->getMessage();
    }
}

require_once __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineWave - Add F&amp;B</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <h1>Add F&amp;B</h1>

    <?php if ($error_msg): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
    <?php endif; ?>

    <form action="add_fnb.php" method="post" enctype="multipart/form-data">
        Item Name:<br>
        <input type="text" name="fnb_name" required>
        <br><br>

        Price (RM):<br>
        <input type="number" step="0.01" min="0" name="fnb_price" required>
        <br><br>

        Staff ID:<br>
        <input type="number" name="staff_id" value="<?php echo htmlspecialchars($default_staff); ?>" required>
        <br><br>

        Description:<br>
        <input type="text" name="description" required>
        <br><br>

        F&amp;B Image:<br>
        <input type="file" name="fnb_img" accept="image/*" required>
        <br><br>

        <input type="submit" name="submit" value="Submit" class="btn">
    </form>
</body>
</html>
