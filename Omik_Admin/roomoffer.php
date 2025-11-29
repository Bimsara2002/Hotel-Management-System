<?php
include 'config.php';

$message = "";

// ===== Add New Offer =====
if(isset($_POST['addOffer'])){
    $roomType = $_POST['roomType'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $discount = $_POST['discount'];
    $validFrom = $_POST['valid_from'];
    $validTo = $_POST['valid_to'];

    $stmt = $conn->prepare("INSERT INTO room_offers (roomType, title, description, discount_percent, valid_from, valid_to) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdss", $roomType, $title, $description, $discount, $validFrom, $validTo);
    
    if($stmt->execute()){
        $message = "Offer added successfully!";
    } else {
        $message = "Error: ".$conn->error;
    }
    $stmt->close();
}

// ===== Update Offer =====
if(isset($_POST['updateOffer'])){
    $offerId = $_POST['offerId'];
    $roomType = $_POST['roomType'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $discount = $_POST['discount'];
    $validFrom = $_POST['valid_from'];
    $validTo = $_POST['valid_to'];

    $stmt = $conn->prepare("UPDATE room_offers SET roomType=?, title=?, description=?, discount_percent=?, valid_from=?, valid_to=? WHERE offerId=?");
    $stmt->bind_param("sssddsi", $roomType, $title, $description, $discount, $validFrom, $validTo, $offerId);
    
    if($stmt->execute()){
        $message = "Offer updated successfully!";
    } else {
        $message = "Error: ".$conn->error;
    }
    $stmt->close();
}

// ===== Delete Offer =====
if(isset($_POST['deleteOffer'])){
    $offerId = $_POST['offerId'];
    $stmt = $conn->prepare("DELETE FROM room_offers WHERE offerId=?");
    $stmt->bind_param("i", $offerId);
    if($stmt->execute()){
        $message = "Offer deleted successfully!";
    } else {
        $message = "Error: ".$conn->error;
    }
    $stmt->close();
}

// Fetch all offers for display
$offers = $conn->query("SELECT * FROM room_offers ORDER BY offerId DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Room Offers</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body { font-family: 'Inter', sans-serif; background: #f5f6fa; color: #333; margin:0; padding:0; }
.container { max-width: 1000px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 12px; box-shadow:0 4px 15px rgba(0,0,0,0.08);}
h2 { text-align: center; color:#004080; margin-bottom:20px;}
form div { margin-bottom: 12px; }
label { font-weight:600; display:block; margin-bottom:5px;}
input, select, textarea { width:100%; padding:10px; border-radius:8px; border:1px solid #ccc; font-size:14px;}
button { padding:10px 20px; background:#004080; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600; margin-right:10px; }
button:hover { background:#00264d; }
.message { text-align:center; color:green; margin-bottom:10px;}
table { width:100%; border-collapse: collapse; margin-top:20px;}
table, th, td { border:1px solid #ccc; }
th, td { padding:10px; text-align:center; }
.update-btn { background:#28a745; }
.update-btn:hover { background:#1e7e34; }
.delete-btn { background:#d9534f; }
.delete-btn:hover { background:#b02a2a; }
</style>
</head>
<body>

<div class="container">
<h2>Manage Room Offers</h2>

<?php if($message) echo "<p class='message'>$message</p>"; ?>

<!-- Add Offer Form -->
<form method="POST">
    <div>
        <label>Room Type:</label>
        <select name="roomType" required>
            <option value="">Select Room Type</option>
            <option value="Single">Single</option>
            <option value="Double">Double</option>
            <option value="Suite">Suite</option>
        </select>
    </div>
    <div>
        <label>Title:</label>
        <input type="text" name="title" required>
    </div>
    <div>
        <label>Description:</label>
        <textarea name="description" rows="3" required></textarea>
    </div>
    <div>
        <label>Discount %:</label>
        <input type="number" name="discount" min="0" max="100" step="0.1" required>
    </div>
    <div>
        <label>Valid From:</label>
        <input type="date" name="valid_from" required>
    </div>
    <div>
        <label>Valid To:</label>
        <input type="date" name="valid_to" required>
    </div>
    <button type="submit" name="addOffer">Add Offer</button>
</form>

<!-- Offers Table -->
<table>
    <tr>
        <th>ID</th>
        <th>Room Type</th>
        <th>Title</th>
        <th>Description</th>
        <th>Discount %</th>
        <th>Valid From</th>
        <th>Valid To</th>
        <th>Actions</th>
    </tr>
    <?php while($offer = $offers->fetch_assoc()): ?>
    <tr>
        <form method="POST">
            <td><?= $offer['offerId'] ?><input type="hidden" name="offerId" value="<?= $offer['offerId'] ?>"></td>
            <td>
                <select name="roomType" required>
                    <option value="Single" <?= $offer['roomType']=='Single'?'selected':'' ?>>Single</option>
                    <option value="Double" <?= $offer['roomType']=='Double'?'selected':'' ?>>Double</option>
                    <option value="Suite" <?= $offer['roomType']=='Suite'?'selected':'' ?>>Suite</option>
                </select>
            </td>
            <td><input type="text" name="title" value="<?= htmlspecialchars($offer['title']) ?>" required></td>
            <td><textarea name="description" rows="2" required><?= htmlspecialchars($offer['description']) ?></textarea></td>
            <td><input type="number" name="discount" min="0" max="100" step="0.1" value="<?= $offer['discount_percent'] ?>" required></td>
            <td><input type="date" name="valid_from" value="<?= $offer['valid_from'] ?>" required></td>
            <td><input type="date" name="valid_to" value="<?= $offer['valid_to'] ?>" required></td>
            <td>
                <button type="submit" name="updateOffer" class="update-btn">Update</button>
                <button type="submit" name="deleteOffer" class="delete-btn" onclick="return confirm('Are you sure?')">Delete</button>
            </td>
        </form>
    </tr>
    <?php endwhile; ?>
</table>
</div>

</body>
</html>
