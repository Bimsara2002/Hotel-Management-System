<?php
session_start();
include 'config.php';

// ✅ Allow only Inventory Manager
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'inventory manager') {
    header("Location: login.html");
    exit;
}

// ✅ Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);
    $supplier_id = intval($_POST['supplier_id']);

    // Fetch item name from Items table
    $itemQuery = $conn->prepare("SELECT item_name FROM Items WHERE item_id = ?");
    $itemQuery->bind_param("i", $item_id);
    $itemQuery->execute();
    $itemResult = $itemQuery->get_result();
    $item = $itemResult->fetch_assoc();
    $item_name = $item['item_name'] ?? '';

    if ($item_name && $quantity > 0) {
        $stmt = $conn->prepare("INSERT INTO StockOrders (item_id, item_name, quantity, supplier_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isii", $item_id, $item_name, $quantity, $supplier_id);
        if ($stmt->execute()) {
            $message = "✅ Supplier order placed successfully!";
        } else {
            $message = "❌ Failed to place order. Please try again.";
        }
        $stmt->close();
    } else {
        $message = "⚠️ Invalid item or quantity.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Supplier Orders</title>
    <link rel="stylesheet" href="inventoryOrder.css">
</head>
<body>
<div class="container">
    <h1>🛒 Place Supplier Orders</h1>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form id="orderForm" method="POST" onsubmit="return validateForm()">
        <label for="item_id">Select Item:</label>
        <select name="item_id" id="item_id" required>
            <option value="">-- Select Item --</option>
            <?php
            $items = $conn->query("SELECT item_id, item_name FROM Items");
            while ($row = $items->fetch_assoc()) {
                echo "<option value='{$row['item_id']}'>{$row['item_name']}</option>";
            }
            ?>
        </select>

        <label for="quantity">Quantity:</label>
        <input type="number" name="quantity" id="quantity" placeholder="Enter quantity" min="1" required>

        <label for="supplier_id">Select Supplier:</label>
        <select name="supplier_id" id="supplier_id" required>
            <option value="">-- Select Supplier --</option>
            <?php
            $suppliers = $conn->query("SELECT supplier_id, supplier_name FROM Suppliers WHERE status='Active'");
            while ($row = $suppliers->fetch_assoc()) {
                echo "<option value='{$row['supplier_id']}'>{$row['supplier_name']}</option>";
            }
            ?>
        </select>

        <button type="submit">Place Order</button>
    </form>

    <a href="inventoryManagerDashboard.php" class="back-btn">⬅ Back to Dashboard</a>
</div>

<script>
function validateForm() {
    const item = document.getElementById('item_id').value;
    const quantity = document.getElementById('quantity').value;
    const supplier = document.getElementById('supplier_id').value;

    if (!item || !quantity || !supplier) {
        alert("⚠️ Please fill in all fields.");
        return false;
    }

    if (quantity <= 0) {
        alert("⚠️ Quantity must be greater than zero.");
        return false;
    }

    return confirm("✅ Confirm to place this supplier order?");
}
</script>

</body>
</html>
