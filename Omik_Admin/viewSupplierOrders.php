<?php
session_start();
include 'config.php';

// ✅ Allow only Inventory Manager
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'inventory manager') {
    header("Location: login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Supplier Orders</title>
<link rel="stylesheet" href="viewInventoryOrders.css">
</head>
<body>
<div class="container">
    <h1>🚚 View Supplier Orders</h1>

    <!-- Filter Buttons -->
    <div class="filter-buttons">
        <button onclick="loadOrders('Pending')">🕓 Pending</button>
        <button onclick="loadOrders('Approved')">✅ Approved</button>
        <button onclick="loadOrders('Delivered')">📦 Delivered</button>
        <button onclick="loadOrders('Cancelled')">❌ Cancelled</button>
    </div>

    <!-- Orders Table -->
    <div id="ordersTable">
        <p style="text-align:center; color:#777;">Select a status to view orders</p>
    </div>

    <a href="inventoryManagerDashboard.php" class="back-btn">⬅ Back to Dashboard</a>
</div>

<script>
// Fetch orders by status using AJAX
function loadOrders(status) {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "fetchOrders.php?status=" + status, true);
    xhr.onload = function() {
        if (this.status === 200) {
            document.getElementById("ordersTable").innerHTML = this.responseText;
        } else {
            alert("⚠️ Failed to load orders!");
        }
    };
    xhr.send();
}
</script>

</body>
</html>
