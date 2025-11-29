<?php
session_start();
include 'config.php';

// Check if user is logged in
if(!isset($_SESSION['staffRole'])){
    header("Location: login.html");
    exit;
}

$role = strtolower(trim($_SESSION['staffRole']));
$message = "";

// Handle saving morning and end cash (Cashier only)
if($role === 'cashier' && isset($_POST['save_cash'])){
    $morningCash = floatval($_POST['morningCash']);
    $endCash = floatval($_POST['endCash']);
    $date = date('Y-m-d');

    $check = $conn->query("SELECT * FROM daily_cash WHERE cashDate='$date'");
    if($check->num_rows > 0){
        $conn->query("UPDATE daily_cash SET morningCash=$morningCash, endCash=$endCash WHERE cashDate='$date'");
    } else {
        $stmt = $conn->prepare("INSERT INTO daily_cash (cashDate, morningCash, endCash) VALUES (?,?,?)");
        $stmt->bind_param("sdd",$date, $morningCash, $endCash);
        $stmt->execute();
    }
    $message = "✅ Cash record saved for today.";
}

// Determine filter for Accountant and General Manager (read-only)
$filterDays = 'today';
$customFrom = '';
$customTo = '';
if(in_array($role, ['accountant','general manager'])){
    if(isset($_GET['days'])) $filterDays = $_GET['days'];
    if(isset($_GET['from'])) $customFrom = $_GET['from'];
    if(isset($_GET['to'])) $customTo = $_GET['to'];
}

// Build SQL filter
if($filterDays === 'custom' && $customFrom && $customTo){
    $filterSQL = "WHERE cashDate BETWEEN '$customFrom' AND '$customTo'";
} else {
    switch($filterDays){
        case '7': $filterSQL = "WHERE cashDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"; break;
        case '30': $filterSQL = "WHERE cashDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"; break;
        default: $filterSQL = "WHERE cashDate = CURDATE()"; break;
    }
}

// Fetch filtered daily cash records
$reportQuery = $conn->query("SELECT * FROM daily_cash $filterSQL ORDER BY cashDate DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daily Cash Report</title>
<style>
body { font-family: Arial; background:#f5f5f5; padding:20px; }
.container { max-width:900px; margin:0 auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1);}
h2 { text-align:center; margin-bottom:15px;}
form label { display:block; margin-top:10px; font-weight: bold; }
form input, form select { width:100%; padding:8px; margin-top:5px; border-radius:4px; border:1px solid #ccc; }
form button { margin-top:15px; padding:10px; width:100%; background:#1abc9c; border:none; color:#fff; border-radius:4px; cursor:pointer; }
form button:hover { background:#16a085; }
.message { padding:10px; margin-bottom:10px; border-radius:4px; background:#2ecc71; color:#fff; text-align:center; }
.result { margin-top:20px; padding:10px; background:#ecf0f1; border-radius:4px; }
.result p { margin:5px 0; }
.back-btn { position: absolute; left: 20px; top: 20px; padding:8px 15px; background:#3498db; color:#fff; border:none; border-radius:5px; cursor:pointer; }
.back-btn:hover { background:#2980b9; }
.filter-form { margin-bottom:15px; display:flex; gap:10px; flex-wrap: wrap; }
.bills-table { width:100%; border-collapse: collapse; margin-top:10px; }
.bills-table th, .bills-table td { border:1px solid #ccc; padding:6px 8px; text-align:left; }
.bills-table th { background:#bdc3c7; }
</style>
<script>
function goBack() {
    window.history.back();
}
</script>
</head>
<body>
<button class="back-btn" onclick="goBack()">← Back</button>
<div class="container">
    <h2>Daily Cash Report</h2>
    <?php if($message) echo "<p class='message'>$message</p>"; ?>

    <!-- Accountant & GM Filter -->
    <?php if(in_array($role,['accountant','general manager'])): ?>
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
    <?php endif; ?>

    <?php while($row = $reportQuery->fetch_assoc()): 
        $morningCash = $row['morningCash'] ?? 0;
        $endCash = $row['endCash'] ?? 0;

        // Total paid sales from billing
        $paidSalesQuery = $conn->query("SELECT * FROM billing WHERE DATE(createdAt)='".$row['cashDate']."' AND paymentStatus='Paid'");
        $totalSales = 0;
        $bills = [];
        while($bill = $paidSalesQuery->fetch_assoc()){
            $totalSales += floatval($bill['totalAmount']);
            $bills[] = $bill;
        }

        $expectedEndCash = $morningCash + $totalSales;
        $discrepancy = $endCash - $expectedEndCash;
    ?>
    <div class="result">
        <p><strong>Date:</strong> <?= $row['cashDate'] ?></p>
        <p><strong>Cash in Drawer (Morning):</strong> Rs <?= number_format($morningCash,2) ?></p>
        <p><strong>Cash in Drawer (End of Day):</strong> Rs <?= number_format($endCash,2) ?></p>
        <p><strong>Total Paid Sales:</strong> Rs <?= number_format($totalSales,2) ?></p>
        <p><strong>Expected End Cash:</strong> Rs <?= number_format($expectedEndCash,2) ?></p>
        <p><strong>Discrepancy:</strong> <span style="color: <?= $discrepancy < 0 ? 'red' : 'green' ?>; font-weight:bold;">Rs <?= number_format($discrepancy,2) ?></span></p>

        <!-- Daily Bills Table -->
        <?php if(count($bills) > 0): ?>
        <table class="bills-table">
            <tr>
                <th>Bill ID</th>
                <th>Customer</th>
                <th>Total Amount</th>
                <th>Payment Status</th>
                <th>Created At</th>
            </tr>
            <?php foreach($bills as $b): ?>
            <tr>
                <td><?= htmlspecialchars($b['billId'] ?? $b['orderGroup']) ?></td>
                <td><?= htmlspecialchars($b['customerName'] ?? 'Walk-in') ?></td>
                <td>Rs <?= number_format($b['totalAmount'],2) ?></td>
                <td><?= htmlspecialchars($b['paymentStatus']) ?></td>
                <td><?= $b['createdAt'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
        <p>No paid bills for this day.</p>
        <?php endif; ?>
    </div>
    <?php endwhile; ?>

    <!-- Cashier Input Form (hidden for Accountant/GM) -->
    <?php if($role === 'cashier'): ?>
    <form method="POST">
        <label>Cash in Drawer (Morning) Rs:</label>
        <input type="number" name="morningCash" step="0.01" value="<?= $morningCash ?>" required>

        <label>Cash in Drawer (End of Day) Rs:</label>
        <input type="number" name="endCash" step="0.01" value="<?= $endCash ?>" required>

        <button type="submit" name="save_cash">Save Record</button>
    </form>
    <?php endif; ?>

</div>
</body>
</html>
