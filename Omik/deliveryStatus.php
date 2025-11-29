<?php
session_start();
include 'config.php';

if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['userId'];

// Fetch all orders grouped by orderGroup
$stmt = $conn->prepare("
    SELECT o.orderGroup,
           MAX(o.type) AS orderType,
           MAX(o.deliveryStatus) AS deliveryStatus,
           MAX(o.status) AS status,
           MAX(o.orderDate) AS orderDate,
           b.fullName, b.phone, b.address,
           GROUP_CONCAT(CONCAT(f.foodName, ' (x', o.quantity, ') Rs', o.amount) SEPARATOR ' | ') AS items,
           SUM(o.amount) AS totalAmount
    FROM customerOrders o 
    LEFT JOIN billing b ON o.orderGroup = b.orderGroup
    JOIN food f ON o.foodId = f.foodId
    WHERE o.customerId = ? and o.type = 'Delivery'
    GROUP BY o.orderGroup, b.fullName, b.phone, b.address
    ORDER BY MAX(o.orderDate) DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Delivery Status | Omik Restaurant</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="deliveryStatus.css">
</head>
<body>

<div class="orders-container container mt-5 position-relative">

    <!-- Back Button -->
    <a href="customerDashboard.php" id="backButton" class="btn btn-outline-primary">← Back</a>

    <h2 class="text-center mb-4">🚚 My Delivery Status</h2>

    <?php if (!empty($orders)) : ?>
    <!-- Filter Section -->
    <div class="filter-bar">
        <input type="text" id="foodSearch" class="form-control" placeholder="Search by food name...">
        <select id="statusFilter" class="form-select">
            <option value="">All Delivery Status</option>
            <option value="yes">Delivered</option>
            <option value="no">Pending</option>
            <option value="in progress">In Progress</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>
    <?php endif; ?>

    <?php if (empty($orders)) : ?>
        <div class="alert alert-info text-center">You have no orders yet.</div>
        <div class="text-center mt-3">
            <a href="menu.php" class="btn btn-primary">🍽 Order Now</a>
        </div>
    <?php else: ?>

        <?php foreach ($orders as $order): ?>
        <?php
            $statusClass = '';
            switch (strtolower($order['deliveryStatus'])) {
                case 'yes': $statusClass = 'status-delivered'; break;
                case 'no': $statusClass = 'status-pending'; break;
                case 'in progress': $statusClass = 'status-inprogress'; break;
                case 'cancelled': $statusClass = 'status-cancelled'; break;
                default: $statusClass = 'status-pending';
            }
            $items = explode(' | ', $order['items']);
        ?>
        <div class="order-card" data-delivery="<?= strtolower($order['deliveryStatus']) ?>" data-items="<?= strtolower($order['items']) ?>">
            <div class="order-header">
                <h5>Order Group: <?= htmlspecialchars($order['orderGroup']) ?></h5>
                <span class="status-badge <?= $statusClass ?>"><?= ucfirst($order['deliveryStatus']) ?></span>
            </div>
            <p><strong>Name:</strong> <?= htmlspecialchars($order['fullName']) ?> | 
               <strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?> | 
               <strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
            <p><strong>Order Type:</strong> <?= htmlspecialchars($order['orderType']) ?> | 
               <strong>Total:</strong> Rs<?= number_format($order['totalAmount'], 2) ?> | 
               <strong>Date:</strong> <?= date('Y-m-d H:i', strtotime($order['orderDate'])) ?></p>
            <div class="items-list">
                <strong>Items:</strong>
                <ul>
                    <?php foreach ($items as $item): ?>
                        <li><?= htmlspecialchars($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endforeach; ?>

    <?php endif; ?>

</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const foodInput = document.getElementById("foodSearch");
    const statusFilter = document.getElementById("statusFilter");
    const orderCards = document.querySelectorAll(".order-card");

    function filterOrders() {
        const searchTerm = foodInput.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();

        orderCards.forEach(card => {
            const itemsText = card.dataset.items.toLowerCase();
            const deliveryStatus = card.dataset.delivery.toLowerCase();
            const matchesFood = itemsText.includes(searchTerm);
            const matchesStatus = !statusValue || deliveryStatus === statusValue;
            card.style.display = matchesFood && matchesStatus ? "" : "none";
        });
    }

    foodInput.addEventListener("keyup", filterOrders);
    statusFilter.addEventListener("change", filterOrders);
});
</script>

</body>
</html>
