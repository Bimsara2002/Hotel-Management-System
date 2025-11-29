<?php
session_start();
include 'config.php';

// ✅ Allow only Inventory Manager
// ✅ Allow only Inventory Manager or General Manager
if (!isset($_SESSION['staffRole']) || 
    !in_array(strtolower(trim($_SESSION['staffRole'])), ['inventory manager', 'general manager'])) {
    header("Location: login.html");
    exit;
}

/* ===========================================================
   1️⃣ FETCH STOCK DETAILS
   =========================================================== */
$sql = "
SELECT s.stock_id, i.item_name, i.price AS unit_price, s.quantity, s.reorder_level, s.expiry_date, s.updated_at, 
       sup.supplier_name
FROM Stock s
JOIN Items i ON s.item_id = i.item_id
LEFT JOIN Suppliers sup ON i.supplier_id = sup.supplier_id
ORDER BY i.item_name ASC
";
$result = $conn->query($sql);

$totalItems = $totalQuantity = $totalValue = $lowStockCount = 0;
$stockData = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $totalItems++;
        $totalQuantity += $row['quantity'];
        $totalValue += $row['quantity'] * $row['unit_price'];
        if ($row['quantity'] <= $row['reorder_level']) $lowStockCount++;
        $stockData[] = $row;
    }
}

/* ===========================================================
   2️⃣ FETCH SUPPLIER ORDERS AND PAYMENTS
   =========================================================== */
$orderSql = "
SELECT so.order_id, so.item_name, so.quantity, so.order_date, so.status AS order_status,
       sp.supplier_name, sp2.PaymentStatus, sp2.PaymentMethod
FROM StockOrders so
JOIN Suppliers sp ON so.supplier_id = sp.supplier_id
LEFT JOIN SupplierPayment sp2 ON sp2.supplier_id = sp.supplier_id
ORDER BY so.order_date DESC
";
$orderResult = $conn->query($orderSql);
$supplierOrders = [];
if ($orderResult->num_rows > 0) {
    while ($row = $orderResult->fetch_assoc()) {
        $supplierOrders[] = $row;
    }
}

$date = date("F d, Y");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory & Supplier Report</title>
<style>
body {
    font-family: "Segoe UI", Arial, sans-serif;
    margin: 30px;
    background: #fdfdfd;
    color: #333;
}
.container {
    max-width: 1150px;
    margin: 0 auto;
    background: #fff;
    padding: 35px;
    border-radius: 10px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.1);
}
.header {
    text-align: center;
    border-bottom: 3px solid #e67e22;
    margin-bottom: 25px;
    padding-bottom: 10px;
}
.header h1 { color: #2c3e50; margin-bottom: 5px; }
.header p { color: #555; font-size: 14px; }

.actions { margin-bottom: 20px; }
.back-btn, .print-btn {
    display: inline-block;
    padding: 8px 15px;
    border-radius: 5px;
    margin-right: 10px;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    color: #fff;
}
.back-btn { background: #3498db; }
.back-btn:hover { background: #2980b9; }
.print-btn { background: #27ae60; }
.print-btn:hover { background: #219150; }

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    font-size: 14px;
}
th, td {
    padding: 10px;
    border: 1px solid #ccc;
    text-align: center;
}
th { background: #e67e22; color: #fff; }
tr:nth-child(even) { background: #f9f9f9; }
.low-stock { color: #e74c3c; font-weight: bold; }
.sufficient { color: #27ae60; font-weight: bold; }

.section-title {
    margin-top: 40px;
    font-size: 20px;
    color: #2c3e50;
    border-left: 5px solid #e67e22;
    padding-left: 10px;
}

.summary-box {
    background: #ecf0f1;
    border-radius: 8px;
    padding: 15px 20px;
    margin-top: 25px;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
}
.summary-item {
    width: 48%;
    margin-bottom: 10px;
    background: #fff;
    border-left: 5px solid #e67e22;
    padding: 10px 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.summary-item h3 { margin: 5px 0; color: #2c3e50; }
.summary-item p { margin: 0; color: #555; }

.footer {
    text-align: center;
    margin-top: 30px;
    border-top: 2px solid #e67e22;
    padding-top: 10px;
    font-size: 13px;
    color: #777;
}
@media print {
    .back-btn, .print-btn { display: none; }
    body { background: white; margin: 0; }
    .container { box-shadow: none; padding: 20px; }
}
</style>
<script>function printReport(){window.print();}</script>
</head>

<body>
<div class="container">
    <div class="header">
        <h1>📦 Inventory & Supplier Report</h1>
        <p><strong>Omik Family Restaurant (PVT) Ltd</strong> — Inventory Department</p>
    </div>

    <div class="actions">
        <a href="inventoryManagerDashboard.php" class="back-btn">⬅ Back to Dashboard</a>
        <button class="print-btn" onclick="printReport()">🖨 Print Report</button>
    </div>

    <p style="text-align:right; color:#555; font-size:14px;">
        <strong>Date:</strong> <?= $date ?><br>
        <strong>Generated by:</strong> <?= htmlspecialchars($_SESSION['staffName'] ?? 'Inventory Manager') ?>
    </p>

    <!-- STOCK DETAILS -->
    <h2 class="section-title">Current Stock Overview</h2>
    <?php if (count($stockData) > 0): ?>
    <table>
        <tr>
            <th>#</th><th>Item</th><th>Supplier</th><th>Qty</th><th>Reorder Level</th><th>Status</th>
            <th>Unit Price (Rs.)</th><th>Total Value (Rs.)</th><th>Expiry</th>
        </tr>
        <?php $i=1; foreach ($stockData as $item): $isLow=$item['quantity'] <= $item['reorder_level']; ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($item['item_name']) ?></td>
            <td><?= htmlspecialchars($item['supplier_name'] ?? 'Unknown') ?></td>
            <td><?= $item['quantity'] ?></td>
            <td><?= $item['reorder_level'] ?></td>
            <td class="<?= $isLow ? 'low-stock':'sufficient' ?>">
                <?= $isLow ? 'Low Stock':'Sufficient' ?>
            </td>
            <td><?= number_format($item['unit_price'],2) ?></td>
            <td><?= number_format($item['quantity'] * $item['unit_price'],2) ?></td>
            <td><?= $item['expiry_date'] ?? '-' ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="summary-box">
        <div class="summary-item"><h3>Total Unique Items</h3><p><?= $totalItems ?></p></div>
        <div class="summary-item"><h3>Total Stock Quantity</h3><p><?= $totalQuantity ?></p></div>
        <div class="summary-item"><h3>Total Stock Value (Rs.)</h3><p><?= number_format($totalValue, 2) ?></p></div>
        <div class="summary-item"><h3>Items Below Reorder Level</h3><p><?= $lowStockCount ?></p></div>
    </div>
    <?php else: ?>
        <p style="text-align:center; color:#777;">No stock records found.</p>
    <?php endif; ?>


    <!-- SUPPLIER ORDERS -->
    <h2 class="section-title">Supplier Orders & Payment Status</h2>
    <?php if (count($supplierOrders) > 0): ?>
    <table>
        <tr>
            <th>#</th><th>Supplier</th><th>Item</th><th>Qty</th><th>Order Date</th><th>Order Status</th>
            <th>Payment Status</th><th>Payment Method</th>
        </tr>
        <?php $i=1; foreach($supplierOrders as $order): ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($order['supplier_name']) ?></td>
            <td><?= htmlspecialchars($order['item_name']) ?></td>
            <td><?= $order['quantity'] ?></td>
            <td><?= date("Y-m-d", strtotime($order['order_date'])) ?></td>
            <td><?= htmlspecialchars($order['order_status']) ?></td>
            <td><?= htmlspecialchars($order['PaymentStatus'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($order['PaymentMethod'] ?? 'N/A') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
        <p style="text-align:center; color:#777;">No supplier order or payment data found.</p>
    <?php endif; ?>

    <div class="footer">
        <p>Inventory Management System | Omik Family Restaurant (PVT) Ltd</p>
        <p>Generated on <?= date("F d, Y h:i A") ?></p>
    </div>
</div>
</body>
</html>
