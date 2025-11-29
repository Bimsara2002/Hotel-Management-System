<?php
session_start();
include 'config.php';

// ✅ Allow only Delivery Boy
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'delivery boy') {
    header("Location: login.html");
    exit;
}

// Logout logic
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.html");
    exit;
}

$driverId = $_SESSION['driverId'] ?? 0;
$success = '';
$error = '';

/* ==========================================================
   ✅ 1. UPDATE DELIVERY STATUS
   ========================================================== */
if (isset($_POST['update_status'])) {
    $deliveryId = intval($_POST['delivery_id']);
    $newStatus = $_POST['status'];

    $stmt = $conn->prepare("SELECT orderGroup FROM DeliveryJobs WHERE delivery_id = ? AND driver_id = ?");
    $stmt->bind_param("ii", $deliveryId, $driverId);
    $stmt->execute();
    $stmt->bind_result($orderGroup);
    $stmt->fetch();
    $stmt->close();

    if ($orderGroup) {
        $stmt = $conn->prepare("UPDATE DeliveryJobs SET delivery_status = ? WHERE delivery_id = ? AND driver_id = ?");
        $stmt->bind_param("sii", $newStatus, $deliveryId, $driverId);
        $stmt->execute();
        $stmt->close();

        $deliveryValue = ($newStatus === 'Delivered') ? 'Yes' : 'No';

        $stmt = $conn->prepare("UPDATE customerOrders SET deliveryStatus = ? WHERE orderGroup = ?");
        $stmt->bind_param("ss", $deliveryValue, $orderGroup);
        $stmt->execute();
        $stmt->close();

        if ($newStatus === 'Delivered') {
            $stmt = $conn->prepare("UPDATE customerOrders SET paymentStatus = 'Paid' WHERE orderGroup = ? AND paymentType = 'Cash on Delivery'");
            $stmt->bind_param("s", $orderGroup);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE billing SET paymentStatus = 'Paid' WHERE orderGroup = ?");
            $stmt->bind_param("s", $orderGroup);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE DeliveryJobs SET payment_status = 'Paid' WHERE orderGroup = ?");
            $stmt->bind_param("s", $orderGroup);
            $stmt->execute();
            $stmt->close();
        }

        $success = "✅ Delivery status updated successfully!";
    } else {
        $error = "❌ Delivery not found or not assigned to you.";
    }
}

/* ==========================================================
   ✅ 2. ENTER DISTANCE
   ========================================================== */
if (isset($_POST['enter_distance'])) {
    $deliveryId = intval($_POST['delivery_id']);
    $distance = floatval($_POST['distance']);

    $stmt = $conn->prepare("UPDATE DeliveryJobs SET distance = ? WHERE delivery_id = ? AND driver_id = ?");
    $stmt->bind_param("dii", $distance, $deliveryId, $driverId);
    $success = $stmt->execute() ? "✅ Distance recorded successfully!" : "❌ Failed to record distance.";
    $stmt->close();
}

/* ==========================================================
   ✅ 3. REPORT VEHICLE ISSUE
   ========================================================== */
if (isset($_POST['report_issue'])) {
    $vehicleId = intval($_POST['vehicle_id']);
    $description = trim($_POST['description']);

    $stmt = $conn->prepare("INSERT INTO VehicleIssue (vehicle_id, status, description) VALUES (?, 'Pending', ?)");
    $stmt->bind_param("is", $vehicleId, $description);
    $success = $stmt->execute() ? "✅ Vehicle issue reported successfully!" : "❌ Failed to report issue.";
    $stmt->close();
}

/* ==========================================================
   ✅ 4. FETCH DATA
   ========================================================== */
$activeJobs = $conn->prepare("
    SELECT dj.delivery_id, dj.orderGroup, dj.delivery_status, dj.distance,
           co.customerId, co.paymentType, 
           f.foodName, c.Name
    FROM DeliveryJobs dj
    LEFT JOIN customerOrders co ON dj.orderGroup = co.orderGroup
    LEFT JOIN food f ON co.foodId = f.foodId
    LEFT JOIN Customer c ON co.customerId = c.CustomerId
    WHERE dj.driver_id = ? AND dj.delivery_status != 'Delivered'
    ORDER BY dj.delivery_id DESC
");
$activeJobs->bind_param("i", $driverId);
$activeJobs->execute();
$active = $activeJobs->get_result();
$activeJobs->close();

$historyJobs = $conn->prepare("
    SELECT dj.delivery_id, dj.orderGroup, dj.delivery_status, dj.distance,
           co.customerId, co.paymentType, 
           f.foodName, c.Name
    FROM DeliveryJobs dj
    LEFT JOIN customerOrders co ON dj.orderGroup = co.orderGroup
    LEFT JOIN food f ON co.foodId = f.foodId
    LEFT JOIN Customer c ON co.customerId = c.CustomerId
    WHERE dj.driver_id = ? AND dj.delivery_status = 'Delivered'
    ORDER BY dj.delivery_id DESC
");
$historyJobs->bind_param("i", $driverId);
$historyJobs->execute();
$history = $historyJobs->get_result();
$historyJobs->close();

$vehicles = $conn->query("SELECT * FROM Vehicle ORDER BY vehicle_number ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delivery Boy Dashboard</title>
<style>
body { font-family: 'Poppins', sans-serif; background: #eef2f7; margin:0; padding:0; }
.container { width: 90%; margin: 40px auto; background: #fff; border-radius: 15px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); position: relative; }
h1 { color: #2c3e50; text-align: center; margin-bottom: 25px; }
.logout-btn { position: absolute; top: 20px; right: 20px; background: #e74c3c; color: #fff; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: bold; transition: background 0.3s; }
.logout-btn:hover { background: #c0392b; }
.section-title { margin-top: 40px; font-size: 20px; font-weight: bold; color: #34495e; border-left: 5px solid #007bff; padding-left: 10px; }
.success { color: #27ae60; font-weight: bold; }
.error { color: #e74c3c; font-weight: bold; }
table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
table th, table td { border: 1px solid #ddd; padding: 10px; text-align: center; }
table th { background: #007bff; color: #fff; }
.status-pending { background: #f1c40f; color: #000; padding: 5px 10px; border-radius: 8px; }
.status-assigned { background: #17a2b8; color: #fff; padding: 5px 10px; border-radius: 8px; }
.status-delivered { background: #28a745; color: #fff; padding: 5px 10px; border-radius: 8px; }
button { background: #007bff; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; transition: background 0.3s; }
button:hover { background: #0056b3; }
input, select { padding: 6px; border-radius: 6px; border: 1px solid #ccc; }
form.inline-form { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
hr { border: none; height: 2px; background: #007bff; margin: 40px 0; }

/* ===== Quick Action Cards ===== */
.quick-actions { text-align: center; margin-top: 20px; }
.cards { display: flex; justify-content: center; flex-wrap: wrap; gap: 20px; margin-top: 15px; }
.card {
    background-color: #007bff;
    color: white;
    padding: 18px 24px;
    border-radius: 10px;
    font-size: 16px;
    cursor: pointer;
    flex: 1 1 220px;
    max-width: 250px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}
.card:hover { background-color: #0056b3; transform: translateY(-5px); }
.card i { font-size: 22px; margin-right: 10px; }
</style>
</head>
<body>
<div class="container">
    <a href="?logout=1" class="logout-btn">Logout</a>
    <h1>🚚 Delivery Boy Dashboard</h1>

    <?php if ($success) echo "<p class='success'>$success</p>"; ?>
    <?php if ($error) echo "<p class='error'>$error</p>"; ?>

    <!-- ✅ Quick Action Buttons -->
    <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="cards">
            <div class="card" onclick="window.location.href='addAttendance.php'">
                <i>📝</i> Add Attendance
            </div>
            <div class="card" onclick="window.location.href='requestLeave.php'">
                <i>📨</i> Request Leave
            </div>
            <div class="card" onclick="window.location.href='viewScheduleall.php'">
                <i>📅</i> View Schedule
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Active Deliveries</div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Order Group</th>
                    <th>Customer</th>
                    <th>Food</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Distance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($active->num_rows > 0): ?>
                <?php while ($job = $active->fetch_assoc()): ?>
                <tr>
                    <td><?= $job['delivery_id'] ?></td>
                    <td><?= htmlspecialchars($job['orderGroup']) ?></td>
                    <td><?= htmlspecialchars($job['Name']) ?></td>
                    <td><?= htmlspecialchars($job['foodName']) ?></td>
                    <td><?= htmlspecialchars($job['paymentType']) ?></td>
                    <td>
                        <span class="<?= $job['delivery_status']=='Pending'?'status-pending':'' ?>
                            <?= $job['delivery_status']=='Assigned'?'status-assigned':'' ?>">
                            <?= $job['delivery_status'] ?>
                        </span>
                    </td>
                    <td><?= $job['distance'] ? number_format($job['distance'], 2).' km' : '-' ?></td>
                    <td>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="delivery_id" value="<?= $job['delivery_id'] ?>">
                            <select name="status" required>
                                <option value="">Change</option>
                                <option value="Assigned">Assigned</option>
                                <option value="Delivered">Delivered</option>
                            </select>
                            <button type="submit" name="update_status">Update</button>
                        </form>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="delivery_id" value="<?= $job['delivery_id'] ?>">
                            <input type="number" step="0.01" name="distance" placeholder="km" required>
                            <button type="submit" name="enter_distance">Save</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8">No active deliveries.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <hr>

    <div class="section">
        <div class="section-title">Delivery History</div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Order Group</th>
                    <th>Customer</th>
                    <th>Food</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Distance</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($history->num_rows > 0): ?>
                <?php while ($job = $history->fetch_assoc()): ?>
                <tr>
                    <td><?= $job['delivery_id'] ?></td>
                    <td><?= htmlspecialchars($job['orderGroup']) ?></td>
                    <td><?= htmlspecialchars($job['Name']) ?></td>
                    <td><?= htmlspecialchars($job['foodName']) ?></td>
                    <td><?= htmlspecialchars($job['paymentType']) ?></td>
                    <td><span class="status-delivered">Delivered</span></td>
                    <td><?= $job['distance'] ? number_format($job['distance'], 2).' km' : '-' ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No deliveries in history.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <hr>

    <div class="section">
        <div class="section-title">Report Vehicle Issue</div>
        <form method="POST" class="inline-form">
            <select name="vehicle_id" required>
                <option value="">Select Vehicle</option>
                <?php while($v = $vehicles->fetch_assoc()): ?>
                    <option value="<?= $v['vehicle_id'] ?>"><?= htmlspecialchars($v['vehicle_number']) ?></option>
                <?php endwhile; ?>
            </select>
            <input type="text" name="description" placeholder="Describe issue" required>
            <button type="submit" name="report_issue">Report</button>
        </form>
    </div>
</div>
</body>
</html>
