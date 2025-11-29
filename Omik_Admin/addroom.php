<?php
session_start();
include 'config.php'; 

if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'restaurant manager') {
    header("Location: login.html");
    exit;
}

$message = "";
$editing = false;
$room = [
    'roomId' => '',
    'ACorNot' => '',
    'status' => '',
    'type' => '',
    'price' => '',
    'vip' => '',
    'image' => ''
];

// ===== Load room for editing =====
if (isset($_GET['edit'])) {
    $editing = true;
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM room WHERE roomId = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ===== Save (Add or Update) =====
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ACorNot = $_POST['ACorNot'];
    $status = $_POST['status'];
    $type = $_POST['type'];
    $price = $_POST['price'];
    $vip = $_POST['vip'];

    // Handle image upload
    $imagePath = $room['image']; // keep old image by default
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true); // create folder if not exists
    }

    if (!empty($_FILES["image"]["name"])) {
        $fileName = basename($_FILES["image"]["name"]);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            // Delete old image if exists
            if ($room['image'] && file_exists($room['image'])) {
                unlink($room['image']);
            }
            $imagePath = $targetFile;
        } else {
            $message = "Failed to upload image.";
        }
    }

    if (!empty($_POST['roomId'])) {
        // Update
        $id = intval($_POST['roomId']);
        $stmt = $conn->prepare("UPDATE room SET ACorNot=?, status=?, type=?, price=?, vip=?, image=? WHERE roomId=?");
        $stmt->bind_param("ssssssi", $ACorNot, $status, $type, $price, $vip, $imagePath, $id);
        $stmt->execute();
        $stmt->close();
        $message = "Room updated successfully!";
    } else {
        // Add
        $stmt = $conn->prepare("INSERT INTO room (ACorNot, status, type, price, vip, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $ACorNot, $status, $type, $price, $vip, $imagePath);
        $stmt->execute();
        $stmt->close();
        $message = "New room added successfully!";
    }
}

// ===== Handle Delete =====
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT image FROM room WHERE roomId = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $img = $stmt->get_result()->fetch_assoc()['image'];
    $stmt->close();

    if ($img && file_exists($img)) {
        unlink($img);
    }

    $stmt = $conn->prepare("DELETE FROM room WHERE roomId = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// ===== Fetch all rooms =====
$result = $conn->query("SELECT * FROM room ORDER BY roomId DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Room Management</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; margin:0; padding:0; background:#f0f2f5; }
.container { max-width: 1000px; margin: 40px auto; background:#fff; border-radius:15px; padding:30px; box-shadow:0 8px 30px rgba(0,0,0,0.15); }
h2 { text-align:center; margin-bottom:25px; color:#4e54c8; }
form { display:grid; grid-template-columns: 1fr 1fr; gap:20px 30px; margin-bottom:40px; }
form label { font-weight:600; margin-bottom:5px; display:block; }
form input, form select, form button { width:100%; padding:10px; border-radius:8px; border:1px solid #ddd; font-size:15px; }
form input[type="file"] { border:none; }
form button { grid-column:1 / -1; background:#4e54c8; color:#fff; font-weight:600; border:none; cursor:pointer; transition:.3s; }
form button:hover { background:#3a3fc1; }
.message { text-align:center; color:green; margin-bottom:15px; font-weight:bold; }
.preview { grid-column:1 / -1; text-align:center; }
.preview img { max-width:100%; max-height:200px; object-fit:cover; border-radius:10px; border:1px solid #ccc; }
table { width:100%; border-collapse: collapse; }
table th, table td { border:1px solid #ddd; padding:12px; text-align:center; font-size:14px; }
table th { background:#4e54c8; color:#fff; }
table img { width:100px; height:60px; object-fit:cover; border-radius:6px; }
.action-buttons a { padding:6px 12px; border-radius:6px; color:#fff; text-decoration:none; margin:0 3px; font-size:13px; }
.edit-btn { background:#28a745; } .edit-btn:hover { background:#218838; }
.delete-btn { background:#dc3545; } .delete-btn:hover { background:#c82333; }
.add-btn { display:inline-block; margin-bottom:15px; padding:10px 18px; background:#4e54c8; color:#fff; text-decoration:none; border-radius:8px; font-weight:600; }
.add-btn:hover { background:#3a3fc1; }
@media(max-width:768px){ form { grid-template-columns:1fr; } table img { width:60px; height:40px; } table th, table td { font-size:12px; padding:8px; } }
</style>
</head>
<body>
<div class="container">
    <h2><?= $editing ? 'Update Room' : 'Add New Room' ?></h2>
    <?php if($message): ?><p class="message"><?= $message ?></p><?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="roomId" value="<?= htmlspecialchars($room['roomId']) ?>">
        <label>AC or Non-AC:</label>
        <select name="ACorNot" required>
            <option value="">Select</option>
            <option value="AC" <?= ($room['ACorNot']=='AC')?'selected':'' ?>>AC</option>
            <option value="Non-AC" <?= ($room['ACorNot']=='Non-AC')?'selected':'' ?>>Non-AC</option>
        </select>
        <label>Status:</label>
        <select name="status" required>
            <option value="">Select</option>
            <option value="Available" <?= ($room['status']=='Available')?'selected':'' ?>>Available</option>
            <option value="Occupied" <?= ($room['status']=='Occupied')?'selected':'' ?>>Occupied</option>
        </select>
        <label>Type:</label>
        <input type="text" name="type" value="<?= htmlspecialchars($room['type']) ?>" required>
        <label>Price:</label>
        <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($room['price']) ?>" required>
        <label>VIP:</label>
        <select name="vip" required>
            <option value="">Select</option>
            <option value="Yes" <?= ($room['vip']=='Yes')?'selected':'' ?>>Yes</option>
            <option value="No" <?= ($room['vip']=='No')?'selected':'' ?>>No</option>
        </select>
        <label>Room Image:</label>
        <input type="file" name="image" accept="image/*" onchange="previewImage(event)">
        <div class="preview">
            <?php if($room['image'] && file_exists($room['image'])): ?>
                <img id="preview" src="<?= htmlspecialchars($room['image']) ?>" alt="Room Image">
            <?php else: ?>
                <img id="preview" style="display:none;">
            <?php endif; ?>
        </div>
        <button type="submit"><?= $editing ? 'Update Room' : 'Add Room' ?></button>
    </form>

    <h2>Room List</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Image</th><th>AC / Non-AC</th><th>Status</th><th>Type</th><th>Price</th><th>VIP</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['roomId'] ?></td>
                <td><?php if($row['image'] && file_exists($row['image'])): ?><img src="<?= $row['image'] ?>" alt="Room"><?php else: ?>No Image<?php endif; ?></td>
                <td><?= htmlspecialchars($row['ACorNot']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= htmlspecialchars($row['type']) ?></td>
                <td>Rs. <?= number_format($row['price'],2) ?></td>
                <td><?= htmlspecialchars($row['vip']) ?></td>
                <td class="action-buttons">
                    <a href="?edit=<?= $row['roomId'] ?>" class="edit-btn">Edit</a>
                    <a href="?delete=<?= $row['roomId'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this room?');">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8">No rooms found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function previewImage(event){
    const reader = new FileReader();
    reader.onload = function(){
        const output = document.getElementById('preview');
        output.src = reader.result;
        output.style.display = 'block';
    };
    reader.readAsDataURL(event.target.files[0]);
}
</script>
</body>
</html>
