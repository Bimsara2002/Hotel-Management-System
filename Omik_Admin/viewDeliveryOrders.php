<?php
session_start();
include 'config.php';

// ✅ Allow only Transport Manager
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'transport manager') {
    header("Location: login.html");
    exit;
}

$success = '';
$error = '';

/* ==========================================================
   ✅ 1. ASSIGN DRIVER & VEHICLE BY ORDER GROUP
   ========================================================== */
if (isset($_POST['assign_driver'])) {
    $orderGroup = $_POST['order_group'];
    $driverId = intval($_POST['driver_id']);
    $vehicleId = intval($_POST['vehicle_id']);

    // Check if group already exists in DeliveryJobs
    $check = $conn->prepare("SELECT * FROM DeliveryJobs WHERE orderGroup=?");
    $check->bind_param("s", $orderGroup);
    $check->execute();
    $checkResult = $check->get_result();

    if ($checkResult->num_rows > 0) {
        // Update existing group
        $stmt = $conn->prepare("
            UPDATE DeliveryJobs 
            SET delivery_status='Assigned', driver_id=?, vehicle_id=? 
            WHERE orderGroup=?
        ");
        $stmt->bind_param("iis", $driverId, $vehicleId, $orderGroup);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert all orders in the group
        $orders = $conn->prepare("SELECT orderId FROM customerOrders WHERE orderGroup=?");
        $orders->bind_param("s", $orderGroup);
        $orders->execute();
        $ordersResult = $orders->get_result();
        while ($order = $ordersResult->fetch_assoc()) {
            $stmt = $conn->prepare("
                INSERT INTO DeliveryJobs 
                (order_id, driver_id, vehicle_id, orderGroup, delivery_status, payment_status) 
                VALUES (?, ?, ?, ?, 'Assigned', 'Unpaid')
            ");
            $stmt->bind_param("iiis", $order['orderId'], $driverId, $vehicleId, $orderGroup);
            $stmt->execute();
            $stmt->close();
        }
        $orders->close();
    }

    // Update customerOrders deliveryStatus
    $updateOrder = $conn->prepare("UPDATE customerOrders SET deliveryStatus='Assigned' WHERE orderGroup=?");
    $updateOrder->bind_param("s", $orderGroup);
    $updateOrder->execute();
    $updateOrder->close();

    $success = "✅ Order Group $orderGroup assigned successfully!";
    $check->close();
}

/* ==========================================================
   ✅ 2. FETCH GROUPED ORDERS (Exclude Delivered by default)
   ========================================================== */
$filterStatus = $_GET['status'] ?? '';

// Base query
$sql = "
SELECT co.orderGroup,
       GROUP_CONCAT(co.orderId SEPARATOR ', ') AS orderIds,
       GROUP_CONCAT(CONCAT(c.Name) SEPARATOR ', ') AS customerNames,
       GROUP_CONCAT(co.paymentStatus) AS paymentStatuses,
       GROUP_CONCAT(co.deliveryStatus) AS deliveryStatuses,
       MAX(co.orderDate) AS orderDate
FROM customerOrders co
LEFT JOIN Customer c ON co.customerId = c.customerId
WHERE co.type='Delivery'
";

// Apply filter
if ($filterStatus != '') {
    $sql .= " AND co.deliveryStatus='". $conn->real_escape_string($filterStatus) ."'";
} else {
    $sql .= " AND co.deliveryStatus IN ('Pending','Assigned')";
}

$sql .= " GROUP BY co.orderGroup ORDER BY orderDate DESC";
$result = $conn->query($sql);

// ✅ Fetch all drivers
$drivers = $conn->query("SELECT * FROM Driver ORDER BY driver_name ASC");

// ✅ Fetch all vehicles
$vehicles = $conn->query("SELECT * FROM Vehicle ORDER BY vehicle_number ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Delivery Orders</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { font-family: 'Poppins', sans-serif; background: #f5f7fa; }
.container { width: 90%; margin: 30px auto; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
h1 { text-align: center; color: #333; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.back-btn { text-decoration: none; background: #007bff; color: #fff; padding: 8px 14px; border-radius: 8px; }
.back-btn:hover { background: #0056b3; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
table th, table td { border: 1px solid #ddd; padding: 10px; text-align: center; }
table th { background: #007bff; color: #fff; }
.badge { padding: 4px 8px; border-radius: 5px; font-size: 0.9em; }
.status-pending { background: #ffc107; color: #000; }
.status-assigned { background: #17a2b8; color: #fff; }
.status-delivered { background: #28a745; color: #fff; }
.status-cancelled { background: #dc3545; color: #fff; }
.updateBtn { background: #28a745; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; }
.updateBtn:hover { background: #218838; }
select { padding: 5px; border-radius: 5px; border: 1px solid #ccc; margin-right:5px; }
</style>
</head>
<body>
<div class="container">
    <a href="TransportManagerDashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <h1>📦 Delivery Orders</h1>

    <?php if (!empty($success)) echo "<p class='success'>$success</p>"; ?>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

    <!-- Filter -->
    <form method="GET" style="margin-bottom:20px;">
        <label for="status">Filter by Delivery Status:</label>
        <select name="status" id="status" onchange="this.form.submit()">
            <option value="">All (Pending & Assigned)</option>
            <option value="Pending" <?= $filterStatus=='Pending'?'selected':'' ?>>Pending</option>
            <option value="Assigned" <?= $filterStatus=='Assigned'?'selected':'' ?>>Assigned</option>
            <option value="Delivered" <?= $filterStatus=='Delivered'?'selected':'' ?>>Delivered</option>
            <option value="Cancelled" <?= $filterStatus=='Cancelled'?'selected':'' ?>>Cancelled</option>
        </select>
    </form>

    <table>
        <thead>
            <tr>
                <th>Group</th>
                <th>Order IDs</th>
                <th>Customer(s)</th>
                <th>Payment Status</th>
                <th>Delivery Status</th>
                <th>Date</th>
                <th>Driver</th>
                <th>Vehicle</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['orderGroup']) ?></td>
                    <td><?= htmlspecialchars($row['orderIds']) ?></td>
                    <td><?= htmlspecialchars($row['customerNames']) ?></td>
                    <td>
                        <?php
                        $payments = explode(',', $row['paymentStatuses']);
                        $firstPayment = trim($payments[0]);
                        ?>
                        <span class="badge <?= $firstPayment=='Paid'?'status-delivered':'status-pending' ?>">
                            <?= $firstPayment ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        $statuses = explode(',', $row['deliveryStatuses']);
                        $firstStatus = trim($statuses[0]);
                        $statusClass = '';
                        switch ($firstStatus) {
                            case 'Pending': $statusClass='status-pending'; break;
                            case 'Assigned': $statusClass='status-assigned'; break;
                            case 'Delivered': $statusClass='status-delivered'; break;
                            case 'Cancelled': $statusClass='status-cancelled'; break;
                        }
                        ?>
                        <span class="badge <?= $statusClass ?>"><?= $firstStatus ?></span>
                    </td>
                    <td><?= $row['orderDate'] ?></td>
                    <td colspan="2">
                        <?php if($firstStatus != 'Assigned'): ?>
                        <form method="POST" style="display:flex; gap:5px; justify-content:center; flex-wrap:wrap;">
                            <input type="hidden" name="order_group" value="<?= $row['orderGroup'] ?>">

                            <select name="driver_id" required>
                                <option value="">Select Driver</option>
                                <?php
                                $drivers->data_seek(0);
                                while($driver = $drivers->fetch_assoc()): ?>
                                    <option value="<?= $driver['driver_id'] ?>"><?= htmlspecialchars($driver['driver_name']) ?></option>
                                <?php endwhile; ?>
                            </select>

                            <select name="vehicle_id" required>
                                <option value="">Select Vehicle</option>
                                <?php
                                $vehicles->data_seek(0);
                                while($v = $vehicles->fetch_assoc()): ?>
                                    <option value="<?= $v['vehicle_id'] ?>"><?= htmlspecialchars($v['vehicle_number']) ?></option>
                                <?php endwhile; ?>
                            </select>

                            <button type="submit" name="assign_driver" class="updateBtn">Assign</button>
                        </form>
                        <?php else: ?>
                            ✅ Assigned
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="9" class="text-center">No delivery orders found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
