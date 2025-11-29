<?php
session_start();
include 'config.php';

// ✅ Allow only Cashier
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'cashier') {
    header("Location: login.html");
    exit;
}

if (!isset($_GET['orderGroup'])) {
    die("Invalid request. No order group specified.");
}

$orderGroup = $_GET['orderGroup'];

// Fetch billing info
$billQuery = $conn->prepare("SELECT * FROM billing WHERE orderGroup = ?");
$billQuery->bind_param("s", $orderGroup);
$billQuery->execute();
$billResult = $billQuery->get_result();

if ($billResult->num_rows === 0) {
    die("No bill found for this order group.");
}

$bill = $billResult->fetch_assoc();

// Fetch ordered items
$itemsQuery = $conn->prepare("
    SELECT co.*, f.foodName
    FROM customerOrders co
    JOIN food f ON co.foodId = f.foodId
    WHERE co.orderGroup = ?
");
$itemsQuery->bind_param("s", $orderGroup);
$itemsQuery->execute();
$itemsResult = $itemsQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Print Bill - <?= htmlspecialchars($orderGroup) ?></title>
<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f9f9f9; }
.invoice { width: 700px; margin: auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
h2, h3 { text-align: center; margin-bottom: 10px; }
table { width: 100%; border-collapse: collapse; margin-top: 15px; }
th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
th { background: #007bff; color: white; }
.total { text-align: right; font-size: 18px; font-weight: bold; }
.info { margin-top: 20px; }
.info p { margin: 5px 0; }
.print-btn { margin-top: 20px; text-align: center; }
button { padding: 10px 20px; font-size: 16px; cursor: pointer; background: #28a745; color: white; border: none; border-radius: 5px; }
button:hover { background: #218838; }
@media print {
    .print-btn { display: none; }
}
</style>
</head>
<body>

<div class="invoice">
    <h2>Omik Family Restaurant</h2>
    <h3>Bill</h3>

    <div class="info">
        <p><strong>Bill ID:</strong> <?= $bill['billingId'] ?></p>
        <p><strong>Order Group:</strong> <?= htmlspecialchars($bill['orderGroup']) ?></p>
        <p><strong>Customer Name:</strong> <?= htmlspecialchars($bill['fullName']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($bill['phone']) ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($bill['address']) ?></p>
        <p><strong>Order Type:</strong> 
            <?php
                $orderTypeRow = $conn->query("SELECT type FROM customerOrders WHERE orderGroup='{$bill['orderGroup']}' LIMIT 1")->fetch_assoc();
                echo htmlspecialchars($orderTypeRow['type']);
            ?>
        </p>
        <p><strong>Payment Type:</strong> <?= htmlspecialchars($bill['paymentType']) ?></p>
        <p><strong>Payment Status:</strong> <?= htmlspecialchars($bill['paymentStatus']) ?></p>
        <p><strong>Date:</strong> <?= $bill['createdAt'] ?></p>
    </div>

    <h3>Ordered Items</h3>
    <table>
        <tr>
            <th>Food Name</th>
            <th>Quantity</th>
            <th>Amount (Rs.)</th>
        </tr>
        <?php 
        $totalAmount = 0;
        while ($row = $itemsResult->fetch_assoc()):
            $totalAmount += $row['amount'];
        ?>
        <tr>
            <td><?= htmlspecialchars($row['foodName']) ?></td>
            <td><?= $row['quantity'] ?></td>
            <td><?= number_format($row['amount'], 2) ?></td>
        </tr>
        <?php endwhile; ?>
        <tr>
            <td colspan="2" class="total">Total Amount</td>
            <td><?= number_format($totalAmount, 2) ?></td>
        </tr>
    </table>

    <div class="print-btn">
        <button onclick="window.print()">Print Bill</button>
    </div>
</div>
<script>
window.onafterprint = function() {
    // Redirect after printing
    window.location.href = 'view_bills.php'; // <-- your select/view bills page
};
</script>

</body>
</html>
