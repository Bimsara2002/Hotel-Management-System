<?php
session_start();
include 'config.php';

if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['userId'];

// Fetch latest order group
$stmt = $conn->prepare("
    SELECT b.orderGroup, b.fullName, b.phone, b.address, b.paymentType, b.paymentStatus, b.totalAmount,
           o.foodId, o.quantity, o.amount
    FROM billing b
    JOIN customerOrders o ON b.orderGroup = o.orderGroup
    WHERE b.customerId = ?
    AND b.createdAt = (
        SELECT MAX(createdAt) FROM billing WHERE customerId = ?
    )
");
$stmt->bind_param("ii", $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();

$orderItems = [];
$orderInfo = null;
while ($row = $result->fetch_assoc()) {
    $orderInfo = [
        'orderGroup' => $row['orderGroup'],
        'fullName' => $row['fullName'],
        'phone' => $row['phone'],
        'address' => $row['address'],
        'paymentType' => $row['paymentType'],
        'paymentStatus' => $row['paymentStatus'],
        'totalAmount' => $row['totalAmount']
    ];
    $orderItems[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Success</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="success.css">
</head>
<body>

<div class="success-container">
    <div class="success-icon">✅</div>
    <h3 class="text-success">Order Placed Successfully!</h3>
    <p class="text-muted">Thank you for your order. We’re preparing it for you!</p>

    <?php if ($orderInfo): ?>
    <div class="details border rounded p-3 mt-4">
        <h5>Order Details</h5>
        <p><strong>Order Group:</strong> <?= htmlspecialchars($orderInfo['orderGroup']) ?></p>
        <p><strong>Name:</strong> <?= htmlspecialchars($orderInfo['fullName']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($orderInfo['phone']) ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($orderInfo['address']) ?></p>
        <p><strong>Payment Type:</strong> <?= htmlspecialchars($orderInfo['paymentType']) ?></p>
        <p><strong>Payment Status:</strong> <?= htmlspecialchars($orderInfo['paymentStatus']) ?></p>
        <p><strong>Total Amount:</strong> Rs<?= number_format($orderInfo['totalAmount'], 2) ?></p>

        <h6 class="mt-3">Ordered Items:</h6>
        <ul>
            <?php foreach ($orderItems as $item): 
                $foodName = $conn->query("SELECT foodName FROM food WHERE foodId=".(int)$item['foodId'])->fetch_assoc()['foodName'];
            ?>
                <li><?= htmlspecialchars($foodName) ?> × <?= $item['quantity'] ?> = Rs<?= number_format($item['amount'], 2) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="btn-group mt-3">
        <a href="menu.php" class="btn btn-primary">🍽 Continue Shopping</a>
        <a href="orders.php" class="btn btn-outline-secondary">📦 View My Orders</a>
    </div>
</div>

</body>
</html>
