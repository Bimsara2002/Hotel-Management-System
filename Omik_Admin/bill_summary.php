<?php
session_start();
include 'config.php';

// ✅ Allow only Cashier
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'cashier') {
    header("Location: login.html");
    exit;
}

if (!isset($_GET['orderGroup'])) {
    die("Invalid request. No order group selected.");
}

$orderGroup = $_GET['orderGroup'];

// ===== Fetch all items in the selected order group =====
$stmt = $conn->prepare("
    SELECT co.*, f.foodName
    FROM customerOrders co
    JOIN food f ON co.foodId = f.foodId
    WHERE co.orderGroup = ?
");
$stmt->bind_param("s", $orderGroup);
$stmt->execute();
$items = $stmt->get_result();

if ($items->num_rows === 0) {
    die("No items found for this order group.");
}

$total = 0;
$customerId = null;
$paymentType = '';
$orderType = '';
$fullName = '';
$phone = '';
$address = '';

// Get details from first row
$firstRow = $items->fetch_assoc();
$customerId = $firstRow['customerId'];
$paymentType = $firstRow['paymentType'];
$orderType = $firstRow['type'];
$total += $firstRow['amount'];

// Fetch customer info
$cust = $conn->query("SELECT * FROM customer WHERE customerId = $customerId");
if ($cust && $cust->num_rows > 0) {
    $c = $cust->fetch_assoc();
    $fullName = $c['Name'];
    $phone = $c['Contact'];
    $address = $c['Address'];
}

// Reset pointer to show all rows in table
$items->data_seek(0);

// ====== Handle Bill Creation ======
if (isset($_POST['generate_bill'])) {
    $totalAmount = floatval($_POST['totalAmount']);
    $paymentType = $_POST['paymentType'];
    $paymentStatus = 'Paid';

    // Insert into billing table
    $billStmt = $conn->prepare("
        INSERT INTO billing (orderGroup, customerId, fullName, phone, address, paymentType, paymentStatus, totalAmount)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $billStmt->bind_param("sisssssd", $orderGroup, $customerId, $fullName, $phone, $address, $paymentType, $paymentStatus, $totalAmount);
    $billStmt->execute();

    // Update payment status for all orders in the group
    $conn->query("UPDATE customerOrders SET paymentStatus='Paid' WHERE orderGroup='$orderGroup'");

    echo "<script>
            alert('Bill generated successfully!');
            window.location.href='print_bill.php?orderGroup=$orderGroup';
          </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bill Summary</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f6f8; padding: 20px; }
h2, h3 { color: #333; margin-bottom: 5px; }
table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 15px; }
th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
th { background: #007bff; color: white; }
.summary { margin-top: 20px; background: #fff; padding: 20px; border-radius: 8px; }
button { background: #28a745; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 5px; }
button:hover { background: #218838; }
.back-btn { background: #6c757d; margin-right: 10px; }
.info { margin-bottom: 10px; }
.info span { display: inline-block; width: 150px; font-weight: bold; }

.back-btn {
    position: fixed;       /* fixed on screen */
    top: 20px;             /* distance from top */
    left: 20px;            /* distance from left */
    background: #6c757d;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    z-index: 1000;         /* make sure it’s above other elements */
    font-size: 14px;
}
.back-btn:hover {
    background: #5a6268;
}

</style>
</head>
<body>
<button class="back-btn" onclick="goBack()">← Back</button>
<script>
function goBack() {
    window.history.back();
    setTimeout(() => { location.reload(); }, 100);
}
</script>


<h2>Bill Summary for Order Group: <?= htmlspecialchars($orderGroup) ?></h2>

<h3>Customer Details</h3>
<div class="info">
    <p><span>Name:</span> <?= htmlspecialchars($fullName) ?></p>
    <p><span>Phone:</span> <?= htmlspecialchars($phone) ?></p>
    <p><span>Address:</span> <?= htmlspecialchars($address) ?></p>
    <p><span>Order Type:</span> <?= htmlspecialchars($orderType) ?></p>
    <p><span>Payment Type:</span> <?= htmlspecialchars($paymentType) ?></p>
</div>

<h3>Ordered Items</h3>
<table>
<tr>
    <th>Food Name</th>
    <th>Quantity</th>
    <th>Amount</th>
</tr>
<?php while ($row = $items->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['foodName']) ?></td>
    <td><?= $row['quantity'] ?></td>
    <td><?= number_format($row['amount'], 2) ?></td>
</tr>
<?php $total += $row['amount']; endwhile; ?>
<tr>
    <td colspan="2" style="text-align:right"><strong>Total Amount</strong></td>
    <td><strong>Rs. <?= number_format($total,2) ?></strong></td>
</tr>
</table>

<div class="summary">
    <form method="POST">
        <input type="hidden" name="totalAmount" value="<?= $total ?>">
        <input type="hidden" name="paymentType" value="<?= htmlspecialchars($paymentType) ?>">
        <button type="submit" name="generate_bill">Generate & Print Bill</button>
        <button type="button" class="back-btn" onclick="window.history.back()">Back</button>
    </form>
</div>

</body>
</html>
