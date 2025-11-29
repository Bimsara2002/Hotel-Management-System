<?php
session_start();
include 'config.php';

// Only accountant, owner, GM can access
$role = strtolower(trim($_SESSION['staffRole'] ?? ''));
if(!in_array($role,['accountant','owner','general manager'])){
    die("❌ You do not have permission to view this page.");
}

$taxRate = 0.20; // 20%
$taxData = [];
$summary = [
    'TotalIncome' => 0,
    'TotalExpenses' => 0,
    'TaxableProfit' => 0,
    'TaxPayable' => 0
];

$incomeDetails = [];
$expenseDetails = [];

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
        $taxableProfit = $income - $totalExpenses;
        $taxPayable = $taxableProfit > 0 ? $taxableProfit * $taxRate : 0;
        $taxData[$month] = $taxPayable;

        // Update summary totals
        $summary['TotalIncome'] += $income;
        $summary['TotalExpenses'] += $totalExpenses;
        $summary['TaxableProfit'] += $taxableProfit;
        $summary['TaxPayable'] += $taxPayable;

        // Insert/Update TaxReport table
        $check = $conn->query("SELECT * FROM TaxReport WHERE MonthYear='$month'");
        if($check->num_rows > 0){
            $conn->query("UPDATE TaxReport SET 
                TotalIncome=$income,
                TotalExpenses=$totalExpenses,
                Salaries=$salaries,
                SupplierPayments=$supplier,
                TransportExpenses=$transport,
                VehicleMaintenance=$vehicle,
                KitchenMaintenance=$kitchen
                WHERE MonthYear='$month'
            ");
        } else {
            $stmt = $conn->prepare("
                INSERT INTO TaxReport 
                (MonthYear, TotalIncome, TotalExpenses, Salaries, SupplierPayments, TransportExpenses, VehicleMaintenance, KitchenMaintenance)
                VALUES (?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param("sddddddd",$month,$income,$totalExpenses,$salaries,$supplier,$transport,$vehicle,$kitchen);
            $stmt->execute();
        }

        // Detailed income and expense for this month
        $dateCondition = "DATE_FORMAT(GeneratedDate,'%Y-%m')='$month'";
        $res = $conn->query("SELECT * FROM Revenue WHERE $dateCondition");
        while($row = $res->fetch_assoc()) $incomeDetails[] = $row;

        $dateConditionPay = "DATE_FORMAT(PaymentDate,'%Y-%m')='$month'";
        $res = $conn->query("SELECT * FROM Payroll WHERE $dateConditionPay");
        while($row = $res->fetch_assoc()) $expenseDetails[] = ['type'=>'Salary','desc'=>'Salary Payment','amount'=>$row['NetPay']];

        $res = $conn->query("SELECT * FROM SupplierPayment WHERE $dateConditionPay");
        while($row = $res->fetch_assoc()) $expenseDetails[] = ['type'=>'Supplier','desc'=>$row['SupplierName'] ?? 'Supplier Payment','amount'=>$row['Amount']];

        $dateConditionExp = "DATE_FORMAT(expense_date,'%Y-%m')='$month'";
        $res = $conn->query("SELECT * FROM TransportExpenses WHERE $dateConditionExp");
        while($row = $res->fetch_assoc()) $expenseDetails[] = ['type'=>'Transport','desc'=>$row['Description'] ?? 'Transport Expense','amount'=>$row['amount']];

        $dateConditionVeh = "DATE_FORMAT(maintenance_date,'%Y-%m')='$month'";
        $res = $conn->query("SELECT * FROM VehicleMaintenance WHERE $dateConditionVeh");
        while($row = $res->fetch_assoc()) $expenseDetails[] = ['type'=>'Vehicle','desc'=>$row['Description'] ?? 'Vehicle Maintenance','amount'=>$row['cost']];

        $dateConditionKit = "DATE_FORMAT(created_at,'%Y-%m')='$month'";
        $res = $conn->query("SELECT * FROM KitchenIssue WHERE $dateConditionKit");
        while($row = $res->fetch_assoc()) $expenseDetails[] = ['type'=>'Kitchen','desc'=>$row['discription'] ?? 'Kitchen Maintenance','amount'=>$row['maintenance_cost']];
    }

    $message = "✅ Tax Report Generated Successfully!";
}

// Fetch filtered TaxReport for Monthly View
$filterSql = '';
if($filterMonth){
    $filterSql = "WHERE MonthYear='$filterMonth'";
}elseif($filterFrom && $filterTo){
    $filterSql = "WHERE MonthYear BETWEEN '$filterFrom' AND '$filterTo'";
}
$reportQuery = $conn->query("SELECT * FROM TaxReport $filterSql ORDER BY MonthYear DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tax Report</title>
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
<h3>Monthly Tax Report</h3>
<?php if($filterMonth) echo "<h4>Month: $filterMonth</h4>";
elseif($filterFrom && $filterTo) echo "<h4>Period: $filterFrom to $filterTo</h4>"; ?>
</header>

<?php if(!empty($message)): ?>
<p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="POST">
<h3>Generate / Filter Tax Report</h3>
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
<th>Taxable Profit</th>
<th>Total Tax Payable</th>
</tr>
<tr>
<td><?= number_format($summary['TotalIncome'],2) ?></td>
<td><?= number_format($summary['TotalExpenses'],2) ?></td>
<td class="<?= $summary['TaxableProfit']<0?'highlight-negative':'highlight-positive' ?>"><?= number_format($summary['TaxableProfit'],2) ?></td>
<td><?= number_format($summary['TaxPayable'],2) ?></td>
</tr>
</table>

<h3>Monthly Analyze View</h3>
<table class="report-table">
<tr>
<th>Month</th>
<th>Total Income</th>
<th>Total Expenses</th>
<th>Taxable Profit</th>
<th>Tax Rate</th>
<th>Tax Payable</th>
</tr>
<?php if($reportQuery->num_rows > 0): 
while($row = $reportQuery->fetch_assoc()):
    $month = $row['MonthYear'];
    $taxPayable = $taxData[$month] ?? 0;
?>
<tr>
<td><?= $month ?></td>
<td><?= number_format($row['TotalIncome'],2) ?></td>
<td><?= number_format($row['TotalExpenses'],2) ?></td>
<td class="<?= $row['TaxableProfit']<0?'highlight-negative':'highlight-positive' ?>"><?= number_format($row['TaxableProfit'],2) ?></td>
<td><?= ($taxRate*100) ?>%</td>
<td class="<?= $taxPayable>0?'highlight-positive':'highlight-negative' ?>"><?= number_format($taxPayable,2) ?></td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="6" style="text-align:center;">No Tax Report Available</td></tr>
<?php endif; ?>
</table>

<!-- Detailed Income & Expenses -->
<?php if($filterMonth || ($filterFrom && $filterTo)): ?>
<h3>Income Details</h3>
<table class="report-table">
<tr><th>Date</th><th>Description</th><th>Amount</th></tr>
<?php foreach($incomeDetails as $inc): ?>
<tr>
<td><?= $inc['GeneratedDate'] ?></td>
<td><?= htmlspecialchars($inc['Description'] ?? 'Revenue') ?></td>
<td><?= number_format($inc['TotalIncome'],2) ?></td>
</tr>
<?php endforeach; ?>
</table>

<h3>Expense Details</h3>
<table class="report-table">
<tr><th>Type</th><th>Description</th><th>Amount</th></tr>
<?php foreach($expenseDetails as $exp): ?>
<tr>
<td><?= htmlspecialchars($exp['type']) ?></td>
<td><?= htmlspecialchars($exp['desc']) ?></td>
<td><?= number_format($exp['amount'],2) ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<footer>
Printed on: <?= date('Y-m-d H:i') ?>
</footer>
</div>
</body>
</html>
