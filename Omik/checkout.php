<?php
session_start();
include 'config.php';

// ✅ Ensure user is logged in
if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit;
}
$userId = $_SESSION['userId'];

// ✅ Fetch all cart items for this user
$stmt = $conn->prepare("SELECT * FROM cart WHERE userId=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$cartItems = $result->fetch_all(MYSQLI_ASSOC);

// ✅ Calculate grand total
$grandTotal = 0;
foreach ($cartItems as $item) {
    $grandTotal += $item['quantity'] * $item['price'];
}

$error = "";

// ✅ Handle checkout submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $address = htmlspecialchars(trim($_POST['address'] ?? ''));
    $paymentType = htmlspecialchars(trim($_POST['paymentType'] ?? ''));
    $orderType = htmlspecialchars(trim($_POST['OrderType'] ?? 'Delivery'));

    if (empty($cartItems)) {
        $error = "Your cart is empty!";
    } else {
        // ✅ Adjust invalid payment type
        if ($orderType === 'Delivery' && $paymentType === 'To Cashier') {
            $paymentType = 'Cash on Delivery';
        }

        // ✅ Set statuses
        $paymentStatus = ($paymentType === 'Cash on Delivery' || $paymentType === 'To Cashier') ? 'Pending' : 'Paid';
        $deliveryStatus = ($orderType === 'Delivery') ? 'Pending' : 'N/A';
        $status = 'Pending';

        // ✅ Generate one orderGroup ID for this checkout
        $orderGroupCode = uniqid("ORD_");

        // ✅ Insert each cart item into customerOrders
        $stmtOrder = $conn->prepare("
            INSERT INTO customerOrders 
            (customerId, foodId, quantity, amount, paymentType, paymentStatus, type, deliveryStatus, status, orderGroup) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($cartItems as $item) {
            $foodId = $item['foodId'];
            $quantity = $item['quantity'];
            $amount = $quantity * $item['price'];

            $stmtOrder->bind_param(
                "iiidssssss",
                $userId,
                $foodId,
                $quantity,
                $amount,
                $paymentType,
                $paymentStatus,
                $orderType,
                $deliveryStatus,
                $status,
                $orderGroupCode
            );
            $stmtOrder->execute();
        }

        // ✅ Insert a single record into billing table for the group
        $stmtBilling = $conn->prepare("
            INSERT INTO billing 
            (orderGroup, customerId, fullName, phone, address, paymentType, paymentStatus, totalAmount) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtBilling->bind_param(
            "sisssssd",
            $orderGroupCode,
            $userId,
            $name,
            $phone,
            $address,
            $paymentType,
            $paymentStatus,
            $grandTotal
        );
        $stmtBilling->execute();

        // ✅ Clear user's cart after successful order
        $stmtClear = $conn->prepare("DELETE FROM cart WHERE userId=?");
        $stmtClear->bind_param("i", $userId);
        $stmtClear->execute();

        // ✅ Redirect to success page
        header("Location: success.php?orderGroup=$orderGroupCode");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Checkout</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.checkout-container { max-width: 900px; margin: 50px auto; }
</style>
</head>
<body>
<div class="checkout-container">
    <h3 class="mb-4">🧾 Checkout</h3>

    <?php if (!empty($error)) : ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if (empty($cartItems)) : ?>
        <div class="alert alert-info">Your cart is empty!</div>
    <?php else: ?>
    <form method="post" id="checkoutForm">
        <h5>Billing Details</h5>
        <div class="mb-3">
            <label>Full Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Address</label>
            <textarea name="address" class="form-control" rows="3" required></textarea>
        </div>
        <div class="mb-3">
            <label>Order Type</label>
            <select name="OrderType" class="form-select" id="orderType" required>
                <option value="Delivery">Delivery</option>
                <option value="Takeaway">Takeaway</option>
                <option value="Dine-in">Dine-in</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Payment Type</label>
            <select name="paymentType" class="form-select" id="paymentType" required>
                <option value="Cash on Delivery">Cash on Delivery</option>
                <option value="Card">Card</option>
                <option value="Online">Online Payment</option>
                <option value="To Cashier" id="cashierOption">To Cashier</option>
            </select>
        </div>

        <h5>Order Summary</h5>
        <table class="table table-striped">
            <thead class="table-light">
                <tr>
                    <th>Food</th>
                    <th>Quantity</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartItems as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['foodName']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>Rs<?= number_format($item['quantity'] * $item['price'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="mb-3 text-end">
            <strong>Grand Total: Rs<?= number_format($grandTotal, 2) ?></strong>
        </div>

        <button type="submit" class="btn btn-success w-100">Place Order</button>
    </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const orderTypeSelect = document.getElementById("orderType");
    const cashierOption = document.getElementById("cashierOption");
    const paymentSelect = document.getElementById("paymentType");

    function toggleCashierOption() {
        const type = orderTypeSelect.value;
        if (type === "Takeaway" || type === "Dine-in") {
            cashierOption.style.display = "block";
        } else {
            cashierOption.style.display = "none";
            if (paymentSelect.value === "To Cashier") {
                paymentSelect.value = "Cash on Delivery";
            }
        }
    }

    orderTypeSelect.addEventListener("change", toggleCashierOption);
    toggleCashierOption();
});
</script>
</body>
</html>
