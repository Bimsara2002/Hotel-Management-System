<?php
session_start();
include 'config.php';

// ✅ Allow only Stock Keeper
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'stock keeper') {
    header("Location: login.html");
    exit;
}

// Handle form submission
$success = $error = '';
if (isset($_POST['submit'])) {
    $item_name = trim($_POST['item_name']);
    $description = trim($_POST['description']);
    $unit = trim($_POST['unit']);
    $price = trim($_POST['price']);
    $supplier_id = $_POST['supplier_id'];

    // Basic validation
    if (empty($item_name) || empty($unit) || empty($price) || empty($supplier_id)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO Items (item_name, description, unit, price, supplier_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdi", $item_name, $description, $unit, $price, $supplier_id);

        if ($stmt->execute()) {
            $item_id = $stmt->insert_id; // ✅ Get the new item ID

            // ✅ Automatically create stock record for this new item
            $stock_stmt = $conn->prepare("INSERT INTO Stock (item_id, quantity, reorder_level) VALUES (?, 0, 5)");
            $stock_stmt->bind_param("i", $item_id);
            $stock_stmt->execute();
            $stock_stmt->close();

            $success = "✅ New item added successfully and stock record created!";
        } else {
            $error = "❌ Error adding item: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch suppliers for dropdown
$suppliers = $conn->query("SELECT supplier_id, supplier_name FROM Suppliers WHERE status='Active' ORDER BY supplier_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add New Stock Item</title>
<link rel="stylesheet" href="stock_keeper.css">
<script>
function validateForm() {
    const name = document.forms["addItemForm"]["item_name"].value.trim();
    const unit = document.forms["addItemForm"]["unit"].value.trim();
    const price = document.forms["addItemForm"]["price"].value.trim();
    const supplier = document.forms["addItemForm"]["supplier_id"].value;

    if (!name || !unit || !price || !supplier) {
        alert("⚠️ Please fill in all required fields.");
        return false;
    }
    if (isNaN(price) || parseFloat(price) <= 0) {
        alert("⚠️ Price must be a positive number.");
        return false;
    }
    return true;
}
</script>
</head>
<body>
<div class="form-container">
         <button class="button-back" onclick="window.location.href='StockKeeperDashboard.php'">Back to Dashboard</button>

    <h2>Add New Stock Item</h2>

    <?php if($success) echo "<p class='success'>$success</p>"; ?>
    <?php if($error) echo "<p class='error'>$error</p>"; ?>

    <form name="addItemForm" method="POST" onsubmit="return validateForm();">
        <label for="item_name">Item Name <span class="required">*</span></label>
        <input type="text" name="item_name" id="item_name" placeholder="Enter item name">

        <label for="description">Description</label>
        <textarea name="description" id="description" placeholder="Optional"></textarea>

        <label for="unit">Unit <span class="required">*</span></label>
        <input type="text" name="unit" id="unit" placeholder="e.g., pcs, kg">

        <label for="price">Price <span class="required">*</span></label>
        <input type="text" name="price" id="price" placeholder="e.g., 250.00">

        <label for="supplier_id">Supplier <span class="required">*</span></label>
        <select name="supplier_id" id="supplier_id">
            <option value="">-- Select Supplier --</option>
            <?php while($row = $suppliers->fetch_assoc()): ?>
                <option value="<?= $row['supplier_id']; ?>"><?= htmlspecialchars($row['supplier_name']); ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit" name="submit">Add Item</button>
    </form>
</div>
</body>
</html>
