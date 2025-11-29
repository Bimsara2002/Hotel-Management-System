<?php
session_start();
include 'config.php';


// ✅ Allow only Inventory Manager or General Manager
if (!isset($_SESSION['staffRole']) || 
    !in_array(strtolower(trim($_SESSION['staffRole'])), ['restaurant manager', 'general manager'])) {
    header("Location: login.html");
    exit;
}


// ===== Filter by date (default to current month) =====
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate   = $_GET['end_date'] ?? date('Y-m-t');

// ===== Fetch Orders & Kitchen Jobs =====
$ordersQuery = "
SELECT co.orderId, co.orderGroup, co.foodId, co.quantity, co.amount, co.type AS orderType,
       co.sizeId, kj.chef_id, kj.status AS kitchenStatus
FROM customerOrders co
LEFT JOIN KitchenJob kj ON co.orderId = kj.order_id
WHERE co.orderDate BETWEEN '$startDate' AND '$endDate'
ORDER BY co.orderId ASC
";
$ordersResult = $conn->query($ordersQuery);

// ===== Fetch Food & Size info =====
$foodData = [];
$foodRes = $conn->query("SELECT * FROM food");
while($f = $foodRes->fetch_assoc()){
    $foodData[$f['foodId']] = $f;
}

$sizeData = [];
$sizeRes = $conn->query("SELECT * FROM food_size");
while($s = $sizeRes->fetch_assoc()){
    $sizeData[$s['sizeId']] = $s;
}

// ===== Fetch Chefs =====
$chefs = [];
$chefRes = $conn->query("SELECT chef_id, chef_name FROM Chef");
while($c = $chefRes->fetch_assoc()){
    $chefs[$c['chef_id']] = $c['chef_name'];
}

// ===== Fetch Resolved Kitchen Issues with maintenance cost =====
$issuesRes = $conn->query("SELECT * FROM KitchenIssue WHERE status='Resolved'");

// ===== Fetch Ingredient Usage with Cost =====
$ingredientQuery = "
SELECT kr.request_id, kr.item_name, kr.quantity, kr.status, i.price AS unit_price, (kr.quantity * i.price) AS total_cost
FROM KitchenRequest kr
LEFT JOIN Items i ON kr.item_name = i.item_name
WHERE kr.status='Approved'
";
$ingredientRes = $conn->query($ingredientQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kitchen Daily Report</title>
<style>
body { font-family: Arial, sans-serif; margin: 20px; background:#fdfdfd; }
h1, h2 { text-align:center; color:#2c3e50; }
table { width:100%; border-collapse: collapse; margin-bottom:30px; }
th, td { padding:10px; border:1px solid #ccc; text-align:center; }
th { background:#e67e22; color:#fff; }
tr:nth-child(even) { background:#f9f9f9; }
.back-btn, .print-btn { display:inline-block; margin-bottom:10px; padding:5px 10px; color:#fff; border-radius:5px; text-decoration:none; }
.back-btn { background:#3498db; }
.print-btn { background:#27ae60; cursor:pointer; }
.status-completed { background:#2ecc71; color:#fff; padding:3px 6px; border-radius:3px; }
.status-pending { background:#f1c40f; color:#fff; padding:3px 6px; border-radius:3px; }
.status-issue { background:#e74c3c; color:#fff; padding:3px 6px; border-radius:3px; }

/* ===== PRINT STYLES ===== */
@media print {
    .no-print { display: none; }
}
</style>
<script>
function printReport() {
    window.print();
}
</script>
</head>
<body>

<!-- ===== FILTER FORM ===== -->
<div class="no-print" style="text-align:center; margin-bottom:15px;">
    <form method="get" style="display:inline-block;">
        <label>Start Date: <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>"></label>
        <label>End Date: <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>"></label>
        <button type="submit" style="padding:5px 10px; background:#e67e22; color:#fff; border:none; border-radius:5px;">Filter</button>
    </form>
</div>

<a href="RestaurantManagerDashboard.php" class="back-btn no-print">⬅ Back to Dashboard</a>
<button onclick="printReport()" class="print-btn no-print">🖨 Print Report</button>

<h1>🍴 Kitchen Daily Report</h1>
<p style="text-align:center;">From: <?= htmlspecialchars($startDate) ?> To: <?= htmlspecialchars($endDate) ?></p>

<!-- ===== Orders Prepared ===== -->
<h2>Orders Prepared</h2>
<table>
<thead>
<tr>
<th>Order ID</th>
<th>Order Group</th>
<th>Food Name</th>
<th>Size</th>
<th>Quantity</th>
<th>Amount ($)</th>
<th>Chef</th>
<th>Status</th>
<th>Order Type</th>
</tr>
</thead>
<tbody>
<?php if($ordersResult->num_rows>0): ?>
    <?php while($o = $ordersResult->fetch_assoc()):
        $foodName = $foodData[$o['foodId']]['foodName'] ?? 'Unknown';
        $sizeName = $sizeData[$o['sizeId']]['size'] ?? '-';
        $chefName = $chefs[$o['chef_id']] ?? 'Unassigned';
        $statusClass = strtolower($o['kitchenStatus'])=='completed' ? 'status-completed' : 'status-pending';
    ?>
    <tr>
        <td><?= $o['orderId'] ?></td>
        <td><?= htmlspecialchars($o['orderGroup']) ?></td>
        <td><?= htmlspecialchars($foodName) ?></td>
        <td><?= htmlspecialchars($sizeName) ?></td>
        <td><?= $o['quantity'] ?></td>
        <td><?= number_format($o['amount'],2) ?></td>
        <td><?= htmlspecialchars($chefName) ?></td>
        <td><span class="<?= $statusClass ?>"><?= htmlspecialchars($o['kitchenStatus']??'Pending') ?></span></td>
        <td><?= htmlspecialchars($o['orderType']) ?></td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="9">No orders found.</td></tr>
<?php endif; ?>
</tbody>
</table>

<!-- ===== Ingredients Used ===== -->
<h2>Ingredients Used</h2>
<table>
<thead>
<tr>
<th>Item Name</th>
<th>Quantity</th>
<th>Unit Price ($)</th>
<th>Total Cost ($)</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php 
$totalIngredientCost = 0;
if($ingredientRes->num_rows>0): 
    while($ing = $ingredientRes->fetch_assoc()):
        $totalIngredientCost += $ing['total_cost'];
?>
<tr>
<td><?= htmlspecialchars($ing['item_name']) ?></td>
<td><?= $ing['quantity'] ?></td>
<td><?= number_format($ing['unit_price'],2) ?></td>
<td><?= number_format($ing['total_cost'],2) ?></td>
<td><span class="status-completed"><?= htmlspecialchars($ing['status']) ?></span></td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="5">No ingredients issued.</td></tr>
<?php endif; ?>
</tbody>
</table>

<!-- ===== Kitchen Issues / Wastage ===== -->
<h2>Kitchen Issues / Wastage</h2>
<table>
<thead>
<tr>
<th>Issue ID</th>
<th>Description</th>
<th>Status</th>
<th>Maintenance Cost ($)</th>
</tr>
</thead>
<tbody>
<?php 
$totalMaintenanceCost = 0;
if($issuesRes->num_rows>0): 
    while($is = $issuesRes->fetch_assoc()):
        $totalMaintenanceCost += $is['maintenance_cost'];
        $statusClass = strtolower($is['status'])=='resolved' ? 'status-completed' : 'status-issue';
?>
<tr>
<td><?= $is['issuse_id'] ?></td>
<td><?= htmlspecialchars($is['description']) ?></td>
<td><span class="<?= $statusClass ?>"><?= htmlspecialchars($is['status']) ?></span></td>
<td><?= number_format($is['maintenance_cost'],2) ?></td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="4">No issues reported.</td></tr>
<?php endif; ?>
</tbody>
</table>

<!-- ===== Summary / Net Profit ===== -->
<h2>Financial Summary</h2>
<?php
$ordersResult->data_seek(0);
$totalOrders = $totalQuantity = $totalRevenue = 0;
while($o = $ordersResult->fetch_assoc()){
    $totalOrders++;
    $totalQuantity += $o['quantity'];
    $totalRevenue += $o['amount'];
}

$ingredientRes->data_seek(0);
$issuesRes->data_seek(0);

$netProfit = $totalRevenue - ($totalIngredientCost + $totalMaintenanceCost);
?>
<table>
<tr>
<th>Total Orders Prepared</th><td><?= $totalOrders ?></td>
</tr>
<tr>
<th>Total Food Quantity</th><td><?= $totalQuantity ?></td>
</tr>
<tr>
<th>Total Revenue ($)</th><td><?= number_format($totalRevenue,2) ?></td>
</tr>
<tr>
<th>Total Ingredients Cost ($)</th><td><?= number_format($totalIngredientCost,2) ?></td>
</tr>
<tr>
<th>Total Maintenance Cost ($)</th><td><?= number_format($totalMaintenanceCost,2) ?></td>
</tr>
<tr>
<th>Net Profit ($)</th>
<td style="font-weight:bold; color:<?= $netProfit>=0 ? 'green' : 'red'; ?>;">
<?= number_format($netProfit,2) ?></td>
</tr>
</table>

<p style="text-align:center; margin-top:30px;">Generated by Kitchen Management System</p>

</body>
</html>
