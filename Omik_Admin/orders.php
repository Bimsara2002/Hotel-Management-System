<?php
session_start();
include 'config.php';

if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['userId'];

// Fetch all orders grouped by orderGroup with aggregation
$stmt = $conn->prepare("
    SELECT b.orderGroup, 
           MAX(o.type) AS orderType,
           MAX(o.deliveryStatus) AS deliveryStatus,
           MAX(o.status) AS status,
           MAX(b.createdAt) AS createdAt,
           MAX(b.paymentType) AS paymentType,
           MAX(b.paymentStatus) AS paymentStatus,
           MAX(b.totalAmount) AS totalAmount,
           MAX(b.fullName) AS fullName,
           MAX(b.phone) AS phone,
           MAX(b.address) AS address,
           GROUP_CONCAT(CONCAT(f.foodName, ':', o.quantity, ':', o.amount) SEPARATOR '|') AS items
    FROM billing b
    JOIN customerOrders o ON b.orderGroup = o.orderGroup
    JOIN food f ON o.foodId = f.foodId
    WHERE b.customerId = ?
    GROUP BY b.orderGroup
    ORDER BY createdAt DESC
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
<title>Order History | Omik Restaurant</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="orders.css">
</head>
<body>

<div class="orders-container container mt-4">
    <a href="customerDashboard.php" class="btn btn-outline-primary back-btn">← Back</a>
    <h3 class="mb-4 text-center">📦 My Order History</h3>

    <?php if (empty($orders)) : ?>
        <div class="alert alert-info text-center">You have no orders yet.</div>
        <div class="text-center mt-3">
            <a href="menu.php" class="btn btn-primary">🍽 Order Now</a>
        </div>
    <?php else: ?>

    <div class="filter-bar mb-3 d-flex flex-wrap gap-2">
        <input type="text" id="searchInput" class="form-control" placeholder="Search by food name or payment type">
        <select id="statusFilter" class="form-select">
            <option value="">All Delivery Status</option>
            <option value="Pending">Pending</option>
            <option value="In Progress">In Progress</option>
            <option value="Delivered">Delivered</option>
            <option value="Cancelled">Cancelled</option>
        </select>
    </div>

    <?php foreach ($orders as $order): ?>
    <?php
        // Badge class for delivery status
        $statusClass = '';
        switch (strtolower($order['deliveryStatus'])) {
            case 'yes': $statusClass='status-delivered'; break;
            case 'no': $statusClass='status-pending'; break;
            case 'in progress': $statusClass='status-inprogress'; break;
            case 'cancelled': $statusClass='status-cancelled'; break;
            default: $statusClass='status-pending';
        }
        $items = explode('|', $order['items']);
    ?>
    <div class="order-card" data-status="<?= strtolower($order['deliveryStatus']) ?>" data-payment="<?= strtolower($order['paymentType']) ?>">
        <div class="order-header" onclick="this.nextElementSibling.classList.toggle('d-none')">
            <h5>Order Group: <?= htmlspecialchars($order['orderGroup']) ?> (<?= htmlspecialchars($order['orderType']) ?>)</h5>
            <span class="status-badge <?= $statusClass ?>"><?= ucfirst($order['deliveryStatus']) ?></span>
        </div>
        <div class="order-details d-none mt-2">
            <p><strong>Name:</strong> <?= htmlspecialchars($order['fullName']) ?> | 
               <strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?> | 
               <strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
            <p><strong>Payment Type:</strong> <?= htmlspecialchars($order['paymentType']) ?> | 
               <strong>Total:</strong> Rs<?= number_format($order['totalAmount'], 2) ?> | 
               <strong>Date:</strong> <?= date('Y-m-d H:i', strtotime($order['createdAt'])) ?></p>
            <table class="table table-bordered items-table mt-2">
                <thead class="table-light">
                    <tr>
                        <th>Food</th>
                        <th>Quantity</th>
                        <th>Amount (Rs)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item):
                        list($foodName, $quantity, $amount) = explode(':', $item); ?>
                        <tr>
                            <td><?= htmlspecialchars($foodName) ?></td>
                            <td><?= htmlspecialchars($quantity) ?></td>
                            <td><?= number_format($amount, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("searchInput");
    const statusFilter = document.getElementById("statusFilter");
    const orderCards = document.querySelectorAll(".order-card");

    function filterOrders() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();

        orderCards.forEach(card => {
            const itemsText = card.querySelector(".items-table").textContent.toLowerCase();
            const paymentType = card.dataset.payment.toLowerCase();
            const deliveryStatus = card.dataset.status.toLowerCase();
            const matchesSearch = itemsText.includes(searchTerm) || paymentType.includes(searchTerm);
            const matchesStatus = !statusValue || deliveryStatus.includes(statusValue);
            card.style.display = matchesSearch && matchesStatus ? "" : "none";
        });
    }

    searchInput.addEventListener("keyup", filterOrders);
    statusFilter.addEventListener("change", filterOrders);
});
</script>

</body>
</html>
