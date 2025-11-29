<?php
session_start();
include 'config.php';

// ✅ Only Accountant
if(!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'accountant'){
    header("Location: login.html");
    exit;
}

$filterDays = 'today';
$customFrom = '';
$customTo = '';

if(isset($_GET['days'])) $filterDays = $_GET['days'];
if(isset($_GET['from'])) $customFrom = $_GET['from'];
if(isset($_GET['to'])) $customTo = $_GET['to'];

// Build SQL filter
if($filterDays === 'custom' && $customFrom && $customTo){
    $filterSQL = "WHERE DATE(PaymentDate) BETWEEN '$customFrom' AND '$customTo'";
} else {
    switch($filterDays){
        case '7': $filterSQL = "WHERE PaymentDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"; break;
        case '30': $filterSQL = "WHERE PaymentDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"; break;
        default: $filterSQL = "WHERE DATE(PaymentDate) = CURDATE()"; break;
    }
}

// Fetch supplier payments with supplier name from Suppliers table
$paymentsQuery = $conn->query("
    SELECT sp.*, s.supplier_name AS supplierName 
    FROM SupplierPayment sp
    JOIN Suppliers s ON sp.supplier_id = s.supplier_id
    $filterSQL
    ORDER BY PaymentDate DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Supplier Payments</title>
<style>
body { font-family: Arial; background:#f5f5f5; padding:20px; }
.container { max-width:1000px; margin:0 auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1);}
h2 { text-align:center; margin-bottom:15px;}
.filter-form { margin-bottom:20px; display:flex; gap:10px; flex-wrap: wrap; align-items:flex-end;}
.filter-form label { font-weight:bold; }
.filter-form input, .filter-form select { padding:6px; border-radius:4px; border:1px solid #ccc; }
.filter-form button { padding:8px 15px; background:#1abc9c; border:none; color:#fff; border-radius:4px; cursor:pointer; }
.filter-form button:hover { background:#16a085; }
.payments-table { width:100%; border-collapse: collapse; }
.payments-table th, .payments-table td { border:1px solid #ccc; padding:8px; text-align:left; }
.payments-table th { background:#bdc3c7; }
.back-btn { position:absolute; left:20px; top:20px; padding:8px 15px; background:#3498db; color:#fff; border:none; border-radius:5px; cursor:pointer; }
.back-btn:hover { background:#2980b9; }
.total { font-weight:bold; margin-top:10px; }
</style>
<script>
function goBack(){ window.history.back(); }
</script>
</head>
<body>
<button class="back-btn" onclick="window.location.href='AccountantDashboard.php'">← Back</button>

<div class="container">
    <h2>Supplier Payments</h2>

    <!-- Filter -->
    <form method="GET" class="filter-form">
        <label>Filter Days:</label>
        <select name="days" onchange="this.form.submit()">
            <option value="today" <?= $filterDays==='today'?'selected':'' ?>>Today</option>
            <option value="7" <?= $filterDays==='7'?'selected':'' ?>>Last 7 Days</option>
            <option value="30" <?= $filterDays==='30'?'selected':'' ?>>Last 30 Days</option>
            <option value="custom" <?= $filterDays==='custom'?'selected':'' ?>>Custom</option>
        </select>

        <label>From:</label>
        <input type="date" name="from" value="<?= htmlspecialchars($customFrom) ?>">

        <label>To:</label>
        <input type="date" name="to" value="<?= htmlspecialchars($customTo) ?>">

        <button type="submit">Apply</button>
    </form>

    <!-- Payments Table -->
    <?php
    $totalAmount = 0;
    if($paymentsQuery->num_rows > 0): ?>
    <table class="payments-table">
        <tr>
            <th>Payment ID</th>
            <th>Supplier Name</th>
            <th>Amount</th>
            <th>Payment Date</th>
            <th>Payment Status</th>
            <th>Method</th>
        </tr>
        <?php while($p = $paymentsQuery->fetch_assoc()): 
            $totalAmount += floatval($p['Amount']);
        ?>
        <tr>
            <td><?= htmlspecialchars($p['PaymentId']) ?></td>
            <td><?= htmlspecialchars($p['supplierName']) ?></td>
            <td>Rs <?= number_format($p['Amount'],2) ?></td>
            <td><?= $p['PaymentDate'] ?></td>
            <td><?= htmlspecialchars($p['PaymentStatus']) ?></td>
            <td><?= htmlspecialchars($p['PaymentMethod'] ?? '-') ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <p class="total">Total Payments: Rs <?= number_format($totalAmount,2) ?></p>
    <?php else: ?>
        <p>No supplier payments found for selected period.</p>
    <?php endif; ?>
</div>
</body>
</html>
