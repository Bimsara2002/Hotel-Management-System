<?php
// Make sure this file is included after DB queries in your main report file

// Set headers already sent in main file
echo "<html><head><meta charset='UTF-8'></head><body>";
echo "<h1>🚚 Transport Report</h1>";
echo "<p>Period: " . htmlspecialchars($startDate) . " to " . htmlspecialchars($endDate) . "</p>";

// ===== Vehicles & Deliveries =====
echo "<h2>Vehicles & Deliveries</h2>";
echo "<table border='1'>
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
</tr>";

$vehicles->data_seek(0);
while ($v = $vehicles->fetch_assoc()) {
    $vid = $v['vehicle_id'];
    $maint = $maintenanceCosts[$vid] ?? 0;
    $costPerKm = $v['cost_1KM'] ?? 0;

    if(isset($deliveryStats[$vid])) {
        foreach($deliveryStats[$vid] as $did => $groups) {
            foreach($groups as $og => $stats) {
                $dist = $stats['total_distance'];
                $deliveryCost = $dist * $costPerKm;
                $totalCost = $deliveryCost + $maint;
                echo "<tr>
                    <td>".htmlspecialchars($v['vehicle_number'])."</td>
                    <td>".htmlspecialchars($v['status'])."</td>
                    <td>".htmlspecialchars($drivers[$did] ?? 'Unassigned')."</td>
                    <td>".htmlspecialchars($og)."</td>
                    <td>".$stats['total_deliveries']."</td>
                    <td>".number_format($dist,2)."</td>
                    <td>".number_format($costPerKm,2)."</td>
                    <td>".number_format($deliveryCost,2)."</td>
                    <td>".number_format($maint,2)."</td>
                    <td>".number_format($totalCost,2)."</td>
                </tr>";
            }
        }
    } else { // Vehicle with no deliveries
        $totalCost = $maint;
        echo "<tr>
            <td>".htmlspecialchars($v['vehicle_number'])."</td>
            <td>".htmlspecialchars($v['status'])."</td>
            <td>—</td>
            <td>—</td>
            <td>0</td>
            <td>0.00</td>
            <td>".number_format($costPerKm,2)."</td>
            <td>0.00</td>
            <td>".number_format($maint,2)."</td>
            <td>".number_format($totalCost,2)."</td>
        </tr>";
    }
}
echo "</table>";

// ===== Other Transport Expenses =====
echo "<h2>Other Transport Expenses</h2>";
echo "<table border='1'>
<tr><th>Total Other Expenses ($)</th><td>".number_format($otherExpenses,2)."</td></tr>
</table>";

// ===== Summary =====
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

echo "<h2>Summary</h2>";
echo "<table border='1'>
<tr><th>Total Delivery Cost</th><td>$".number_format($totalDeliveryCost,2)."</td></tr>
<tr><th>Total Maintenance Cost</th><td>$".number_format($totalMaintenance,2)."</td></tr>
<tr><th>Total Other Expenses</th><td>$".number_format($otherExpenses,2)."</td></tr>
<tr><th>Grand Total</th><td>$".number_format($grandTotal,2)."</td></tr>
</table>";

echo "</body></html>";
?>
