<?php
session_start();
include 'config.php';

// ✅ Allow only Cashier
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'cashier') {
    header("Location: login.html");
    exit;
}

// Handle filter
$orderTypeFilter = isset($_GET['orderType']) ? $_GET['orderType'] : 'all';

// Fetch pending order groups
$sql = "SELECT orderGroup, ANY_VALUE(customerId) AS customerId, ANY_VALUE(type) AS type, ANY_VALUE(paymentType) AS paymentType, SUM(amount) AS total
        FROM customerOrders
        WHERE paymentStatus='Pending'";
if ($orderTypeFilter !== 'all') {
    $sql .= " AND type=?";
}
$sql .= " GROUP BY orderGroup ORDER BY MAX(orderDate) DESC";

$stmt = $conn->prepare($sql);
if ($orderTypeFilter !== 'all') {
    $stmt->bind_param("s", $orderTypeFilter);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Select Order Group</title>
<style>
body { font-family: Arial; padding: 20px; background: #f9f9f9; }
h2 { color: #333; 
margin-top: 50px;}
table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; }
th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
th { background: #007bff; color: white; }
button, select { padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; }
button { background: #28a745; color: white; margin-top: 10px; }
button:hover { background: #218838; }
select { margin-left: 10px; }

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

<h2>Select Order Group to Generate Bill</h2>
<button class="back-btn" onclick="goBack()">Back</button>
<script>
function goBack() {
    // Go back one page
    window.history.back();

    // Optional: force refresh after 100ms
    setTimeout(() => {
        location.reload();
    }, 100);
}
</script>



<form method="GET" style="margin-bottom: 20px;">
    <label>Filter by Order Type:</label>
    <select name="orderType" onchange="this.form.submit()">
        <option value="all" <?= $orderTypeFilter==='all'?'selected':'' ?>>All</option>
        <option value="Dine-in" <?= $orderTypeFilter==='Dine-in'?'selected':'' ?>>Dine-in</option>
        <option value="Takeaway" <?= $orderTypeFilter==='Takeaway'?'selected':'' ?>>Takeaway</option>
        <option value="Delivery" <?= $orderTypeFilter==='Delivery'?'selected':'' ?>>Delivery</option>
    </select>
</form>

<form method="GET" action="bill_summary.php">
<table>
<tr>
    <th>Select</th>
    <th>Order Group</th>
    <th>Customer ID</th>
    <th>Order Type</th>
    <th>Payment Type</th>
    <th>Total Amount</th>
</tr>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><input type="radio" name="orderGroup" value="<?= htmlspecialchars($row['orderGroup']) ?>" required></td>
    <td><?= htmlspecialchars($row['orderGroup']) ?></td>
    <td><?= $row['customerId'] ?></td>
    <td><?= htmlspecialchars($row['type']) ?></td>
    <td><?= htmlspecialchars($row['paymentType']) ?></td>
    <td><?= number_format($row['total'],2) ?></td>
</tr>
<?php endwhile; ?>
</table>
<button type="submit">View Summary & Generate Bill</button>
</form>

</body>
</html>
