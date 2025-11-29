<?php
session_start();
include 'config.php';

// Only accountant, owner, GM can access
$role = strtolower(trim($_SESSION['staffRole'] ?? ''));
if(!in_array($role,['accountant','owner','general manager'])){
    die("❌ You do not have permission to view this page.");
}

// ====== Initialize Variables ======
$financeData = [];
$summary = [
    'TotalIncome' => 0,
    'TotalExpenses' => 0,
    'ProfitLoss' => 0
];

// Filter values
$filterMonth = $_POST['filter_month'] ?? '';
$filterFrom = $_POST['filter_from'] ?? '';
$filterTo = $_POST['filter_to'] ?? '';

$message = "";

// ====== Generate Report ======
if(isset($_POST['generate_report'])){
    $months = [];
    if($filterMonth){
        $months[] = $filterMonth;
    } elseif($filterFrom && $filterTo){
        $start = strtotime($filterFrom.'-01');
        $end = strtotime($filterTo.'-01');
        for($ts=$start; $ts<=$end; $ts = strtotime('+1 month', $ts)){
            $months[] = date('Y-m', $ts);
        }
    } else {
        $res = $conn->query("
            SELECT DISTINCT DATE_FORMAT(GeneratedDate,'%Y-%m') AS MonthYear FROM Revenue
            UNION
            SELECT DISTINCT DATE_FORMAT(PaymentDate,'%Y-%m') AS MonthYear FROM Payroll
            UNION
            SELECT DISTINCT DATE_FORMAT(PaymentDate,'%Y-%m') AS MonthYear FROM SupplierPayment
            UNION
            SELECT DISTINCT DATE_FORMAT(expense_date,'%Y-%m') AS MonthYear FROM TransportExpenses
            UNION
            SELECT DISTINCT DATE_FORMAT(maintenance_date,'%Y-%m') AS MonthYear FROM VehicleMaintenance
            UNION
            SELECT DISTINCT DATE_FORMAT(created_at,'%Y-%m') AS MonthYear FROM KitchenIssue
            ORDER BY MonthYear DESC
        ");
        while($m = $res->fetch_assoc()){
            $months[] = $m['MonthYear'];
        }
    }

    foreach($months as $month){
        // --- Income ---
        $income = $conn->query("SELECT SUM(TotalIncome) as income FROM Revenue WHERE DATE_FORMAT(GeneratedDate,'%Y-%m')='$month'")->fetch_assoc()['income'] ?? 0;

        // --- Expenses ---
        $salaries = $conn->query("SELECT SUM(NetPay) as salaries FROM Payroll WHERE DATE_FORMAT(PaymentDate,'%Y-%m')='$month'")->fetch_assoc()['salaries'] ?? 0;
        $supplier = $conn->query("SELECT SUM(Amount) as supplier FROM SupplierPayment WHERE DATE_FORMAT(PaymentDate,'%Y-%m')='$month'")->fetch_assoc()['supplier'] ?? 0;
        $transport = $conn->query("SELECT SUM(amount) as transport FROM TransportExpenses WHERE DATE_FORMAT(expense_date,'%Y-%m')='$month'")->fetch_assoc()['transport'] ?? 0;
        $vehicle = $conn->query("SELECT SUM(cost) as vehicle FROM VehicleMaintenance WHERE DATE_FORMAT(maintenance_date,'%Y-%m')='$month'")->fetch_assoc()['vehicle'] ?? 0;
        $kitchen = $conn->query("SELECT SUM(maintenance_cost) as kitchen FROM KitchenIssue WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'")->fetch_assoc()['kitchen'] ?? 0;

        $totalExpenses = $salaries + $supplier + $transport + $vehicle + $kitchen;
        $profitLoss = $income - $totalExpenses;

        $financeData[$month] = [
            'Income' => $income,
            'Expenses' => $totalExpenses,
            'ProfitLoss' => $profitLoss,
            'Details' => [
                'Salaries' => $salaries,
                'SupplierPayments' => $supplier,
                'TransportExpenses' => $transport,
                'VehicleMaintenance' => $vehicle,
                'KitchenMaintenance' => $kitchen
            ]
        ];

        $summary['TotalIncome'] += $income;
        $summary['TotalExpenses'] += $totalExpenses;
        $summary['ProfitLoss'] += $profitLoss;
    }

    $message = "✅ Finance Report Generated Successfully!";
}

// Prepare chart data
$chartLabels = json_encode(array_keys($financeData));
$chartIncome = json_encode(array_map(fn($d)=>$d['Income'],$financeData));
$chartExpenses = json_encode(array_map(fn($d)=>$d['Expenses'],$financeData));
$chartProfit = json_encode(array_map(fn($d)=>$d['ProfitLoss'],$financeData));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Finance Report</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { font-family: Arial; background:#f5f5f5; padding:20px; }
.container { max-width:1200px; margin:0 auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1);}
h2,h3,h4 { text-align:center; margin-bottom:10px;}
.message { padding:10px; margin-bottom:10px; border-radius:4px; background:#2ecc71; color:#fff; text-align:center; }
.back-btn, .print-btn, .generate-btn { padding:8px 15px; background:#3498db; color:#fff; border:none; border-radius:5px; cursor:pointer; margin-bottom:10px; }
.back-btn:hover, .print-btn:hover, .generate-btn:hover { background:#2980b9; }
.report-table { width:100%; border-collapse: collapse; margin-top:20px; page-break-inside: avoid; }
.report-table th, .report-table td { border:1px solid #ccc; padding:8px; text-align:right; }
.report-table th { background:#bdc3c7; text-align:center; }
.highlight-negative { color:red; font-weight:bold; }
.highlight-positive { color:green; font-weight:bold; }

/* ===== Print Styling ===== */
@media print {
    body { background: #fff; padding:0; }
    .back-btn, .print-btn, .generate-btn, form, .message { display:none; }
    .container { box-shadow:none; border:none; width:100%; margin:0; padding-top:120px; padding-bottom:50px; }
    h2,h3,h4 { margin:0; }
    table { page-break-inside: auto; width:100%; }
    tr { page-break-inside: avoid; page-break-after: auto; }
    thead { display: table-header-group; }
    tfoot { display: table-footer-group; }
    header { position: fixed; top:0; width:100%; text-align:center; font-size:14px; padding-top:10px; }
    footer { position: fixed; bottom:0; width:100%; text-align:center; font-size:12px; padding-bottom:10px; }
    .page-break { page-break-after: always; }
}
header { margin-bottom:20px; }
</style>
<script>
function printReport() { window.print(); }
</script>
</head>
<body>
<button class="back-btn" onclick="window.location.href='AccountantDashboard.php'">⬅ Back to Dashboard</button>
<button class="print-btn" onclick="printReport()">🖨 Print Report</button>

<div class="container">
<header>
<h2>Omik Family Restaurant Pvt Ltd</h2>
<h3>Finance Report</h3>
<?php if($filterMonth) echo "<h4>Month: $filterMonth</h4>";
elseif($filterFrom && $filterTo) echo "<h4>Period: $filterFrom to $filterTo</h4>"; ?>
</header>

<?php if(!empty($message)): ?>
<p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="POST">
<h3>Generate / Filter Finance Report</h3>
<label>Single Month:</label>
<select name="filter_month">
<option value="">-- Select Month --</option>
<?php
$monthsRes = $conn->query("SELECT DISTINCT DATE_FORMAT(GeneratedDate,'%Y-%m') as MonthYear FROM Revenue ORDER BY MonthYear DESC");
while($m = $monthsRes->fetch_assoc()){
    $selected = ($filterMonth==$m['MonthYear']) ? 'selected' : '';
    echo "<option value='{$m['MonthYear']}' $selected>{$m['MonthYear']}</option>";
}
?>
</select>
<br><br>
<label>Or Select Range:</label>
From: <input type="month" name="filter_from" value="<?= $filterFrom ?>">
To: <input type="month" name="filter_to" value="<?= $filterTo ?>">
<br><br>
<button type="submit" name="generate_report" class="generate-btn">✅ Generate & Filter</button>
</form>

<h3>Summary View</h3>
<table class="report-table">
<tr>
<th>Total Income</th>
<th>Total Expenses</th>
<th>Profit / Loss</th>
</tr>
<tr>
<td><?= number_format($summary['TotalIncome'],2) ?></td>
<td><?= number_format($summary['TotalExpenses'],2) ?></td>
<td class="<?= $summary['ProfitLoss']<0?'highlight-negative':'highlight-positive' ?>"><?= number_format($summary['ProfitLoss'],2) ?></td>
</tr>
</table>

<h3>Monthly Analyze View</h3>
<?php foreach($financeData as $month=>$data): ?>
<table class="report-table">
<tr>
<th colspan="2"><?= $month ?></th>
</tr>
<tr><th>Income</th><td><?= number_format($data['Income'],2) ?></td></tr>
<tr><th>Total Expenses</th><td><?= number_format($data['Expenses'],2) ?></td></tr>
<tr><th>Profit / Loss</th><td class="<?= $data['ProfitLoss']<0?'highlight-negative':'highlight-positive' ?>"><?= number_format($data['ProfitLoss'],2) ?></td></tr>
<tr><th colspan="2">Expenses Breakdown</th></tr>
<tr><td>Salaries</td><td><?= number_format($data['Details']['Salaries'],2) ?></td></tr>
<tr><td>Supplier Payments</td><td><?= number_format($data['Details']['SupplierPayments'],2) ?></td></tr>
<tr><td>Transport Expenses</td><td><?= number_format($data['Details']['TransportExpenses'],2) ?></td></tr>
<tr><td>Vehicle Maintenance</td><td><?= number_format($data['Details']['VehicleMaintenance'],2) ?></td></tr>
<tr><td>Kitchen Maintenance</td><td><?= number_format($data['Details']['KitchenMaintenance'],2) ?></td></tr>
</table>
<br class="page-break">
<?php endforeach; ?>

<h3>Finance Chart</h3>
<canvas id="financeChart" height="150"></canvas>

<footer>
Printed on: <?= date('Y-m-d H:i') ?>
</footer>

<script>
const ctx = document.getElementById('financeChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= $chartLabels ?>,
        datasets: [
            { label: 'Income', data: <?= $chartIncome ?>, backgroundColor: 'green' },
            { label: 'Expenses', data: <?= $chartExpenses ?>, backgroundColor: 'red' },
            { label: 'Profit/Loss', data: <?= $chartProfit ?>, backgroundColor: 'blue' }
        ]
    },
    options: { responsive:true, scales:{ y:{ beginAtZero:true } } }
});
</script>
</div>
</body>
</html>
