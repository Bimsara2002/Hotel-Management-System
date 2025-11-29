<?php
session_start();
include 'config.php';


// ✅ Allow only Inventory Manager or General Manager
if (!isset($_SESSION['staffRole']) || 
    !in_array(strtolower(trim($_SESSION['staffRole'])), ['transport manager', 'general manager'])) {
    header("Location: login.html");
    exit;
}


// ===== Optional: Filter by date =====
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate   = $_GET['end_date'] ?? date('Y-m-t');

// ===== Fetch all vehicles =====
$vehicles = $conn->query("SELECT * FROM Vehicle ORDER BY vehicle_number ASC");

// ===== Fetch delivery stats per vehicle =====
$deliveries = $conn->query("
    SELECT vehicle_id, driver_id, orderGroup,
           COUNT(*) AS total_deliveries, 
           SUM(distance) AS total_distance
    FROM DeliveryJobs
    WHERE delivery_status='Delivered' 
      AND created_at BETWEEN '$startDate' AND '$endDate'
    GROUP BY vehicle_id, driver_id, orderGroup
");

$deliveryStats = [];
while ($row = $deliveries->fetch_assoc()) {
    $vid = $row['vehicle_id'];
    $did = $row['driver_id'];
    $og  = $row['orderGroup'] ?? 'No Group';
    $deliveryStats[$vid][$did][$og] = $row;
}

// ===== Fetch maintenance costs per vehicle =====
$maintenance = $conn->query("
    SELECT vehicle_id, SUM(cost) AS total_maintenance
    FROM VehicleMaintenance
    WHERE maintenance_date BETWEEN '$startDate' AND '$endDate'
    GROUP BY vehicle_id
");
$maintenanceCosts = [];
while ($row = $maintenance->fetch_assoc()) {
    $maintenanceCosts[$row['vehicle_id']] = $row['total_maintenance'];
}

// ===== Fetch other transport expenses =====
$expenses = $conn->query("
    SELECT SUM(amount) AS total_expenses
    FROM TransportExpenses
    WHERE expense_date BETWEEN '$startDate' AND '$endDate'
");
$otherExpenses = $expenses->fetch_assoc()['total_expenses'] ?? 0;

// ===== Fetch drivers =====
$drivers = [];
$driverRes = $conn->query("SELECT StaffId, FirstName, LastName FROM Staff WHERE JobRole='Driver'");
while ($d = $driverRes->fetch_assoc()) {
    $drivers[$d['StaffId']] = $d['FirstName'] . ' ' . $d['LastName'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transport Report</title>
<style>
/* ===== General ===== */
body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; color: #333; }
h1, h2, h3 { text-align:center; margin: 5px 0; }
h1 { font-size: 28px; color: #2c3e50; }
h2 { font-size: 22px; margin-top: 30px; color: #34495e; }
h3 { font-size: 18px; margin-bottom: 20px; }

/* ===== Table ===== */
table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
th, td { padding: 10px; border: 1px solid #555; text-align: center; }
th { background:#3498db; color:#fff; }
tr:nth-child(even) { background:#ecf0f1; }

/* ===== Header & Footer ===== */
.header, .footer { text-align: center; margin: 20px 0; }
.footer { font-size: 14px; color: #555; }

/* ===== Buttons ===== */
.back-btn, .print-btn {
    display: inline-block;
    margin: 10px 5px;
    padding: 8px 12px;
    background: #e74c3c;
    color: #fff;
    border-radius: 5px;
    text-decoration: none;
    cursor: pointer;
    border: none;
    font-size: 14px;
}

/* ===== Print Styles ===== */
@media print {
    .back-btn, .print-btn, form { display: none; }
    body { margin: 0; }
}
</style>
<script>
function printReport() {
    window.print();
}
</script>
</head>
<body>

<!-- ===== Header ===== -->
<div class="header">
    <h1>Omik Transport Department</h1>
    <p>Transport Report</p>
    <p>Period: <?= htmlspecialchars($startDate) ?> to <?= htmlspecialchars($endDate) ?></p>
    <p>Generated on: <?= date('Y-m-d H:i') ?></p>
</div>

<!-- ===== Buttons ===== -->
<a href="TransportManagerDashboard.php" class="back-btn">⬅ Back to Dashboard</a>
<button class="print-btn" onclick="printReport()">🖨 Print Report</button>

<!-- ===== Filter Form ===== -->
<form method="GET">
    <label>Start Date: <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>"></label>
    <label>End Date: <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>"></label>
    <button type="submit">Filter</button>
</form>

<!-- ===== Vehicles & Deliveries ===== -->
<h2>Vehicles & Deliveries</h2>
<table>
    <thead>
        <tr>
            <th>Vehicle</th>
            <th>Status</th>
            <th>Driver</th>
            <th>Order Group</th>
            <th>Total Deliveries</th>
            <th>Total Distance (Km)</th>
            <th>Cost per Km ($)</th>
            <th>Delivery Cost ($)</th>
            <th>Maintenance Cost ($)</th>
            <th>Total Cost ($)</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($vehicles->num_rows > 0): ?>
            <?php while ($v = $vehicles->fetch_assoc()): 
                $vid = $v['vehicle_id'];
                $maint = $maintenanceCosts[$vid] ?? 0;
                $costPerKm = $v['cost_1KM'] ?? 0;

                if(isset($deliveryStats[$vid])) {
                    foreach($deliveryStats[$vid] as $did => $groups) {
                        foreach($groups as $og => $stats) {
                            $dist = $stats['total_distance'];
                            $deliveryCost = $dist * $costPerKm;
                            $totalCost = $deliveryCost + $maint;
            ?>
            <tr>
                <td><?= htmlspecialchars($v['vehicle_number']) ?></td>
                <td><?= htmlspecialchars($v['status']) ?></td>
                <td><?= htmlspecialchars($drivers[$did] ?? 'Unassigned') ?></td>
                <td><?= htmlspecialchars($og) ?></td>
                <td><?= $stats['total_deliveries'] ?></td>
                <td><?= number_format($dist,2) ?></td>
                <td><?= number_format($costPerKm,2) ?></td>
                <td><?= number_format($deliveryCost,2) ?></td>
                <td><?= number_format($maint,2) ?></td>
                <td><?= number_format($totalCost,2) ?></td>
            </tr>
            <?php
                        }
                    }
                } else {
                    $totalCost = $maint;
            ?>
            <tr>
                <td><?= htmlspecialchars($v['vehicle_number']) ?></td>
                <td><?= htmlspecialchars($v['status']) ?></td>
                <td>—</td>
                <td>—</td>
                <td>0</td>
                <td>0.00</td>
                <td><?= number_format($costPerKm,2) ?></td>
                <td>0.00</td>
                <td><?= number_format($maint,2) ?></td>
                <td><?= number_format($totalCost,2) ?></td>
            </tr>
            <?php } ?>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="10">No vehicles found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- ===== Other Expenses ===== -->
<h2>Other Transport Expenses</h2>
<table>
    <tr>
        <th>Total Other Expenses ($)</th>
        <td><?= number_format($otherExpenses,2) ?></td>
    </tr>
</table>

<!-- ===== Summary ===== -->
<h2>Summary</h2>
<table>
<?php
$totalDeliveryCost = $totalMaintenance = 0;
$vehicles->data_seek(0);
while ($v = $vehicles->fetch_assoc()) {
    $vid = $v['vehicle_id'];
    $costPerKm = $v['cost_1KM'] ?? 0;
    if(isset($deliveryStats[$vid])) {
        foreach($deliveryStats[$vid] as $did => $groups) {
            foreach($groups as $og => $stats) {
                $dist = $stats['total_distance'];
                $totalDeliveryCost += $dist * $costPerKm;
            }
        }
    }
    $totalMaintenance += $maintenanceCosts[$vid] ?? 0;
}
$grandTotal = $totalDeliveryCost + $totalMaintenance + $otherExpenses;
?>
<tr><th>Total Delivery Cost</th><td>$<?= number_format($totalDeliveryCost,2) ?></td></tr>
<tr><th>Total Maintenance Cost</th><td>$<?= number_format($totalMaintenance,2) ?></td></tr>
<tr><th>Total Other Expenses</th><td>$<?= number_format($otherExpenses,2) ?></td></tr>
<tr><th>Grand Total</th><td>$<?= number_format($grandTotal,2) ?></td></tr>
</table>

<!-- ===== Footer ===== -->
<div class="footer">
    <p>Omik Transport Department - Official Report</p>
</div>

</body>
</html>
