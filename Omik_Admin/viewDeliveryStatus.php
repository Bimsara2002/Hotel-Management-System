<?php
session_start();
include 'config.php';

// ✅ Allow only Transport Manager
if (!isset($_SESSION['staffRole'])) {
    header("Location: login.html");
    exit;
}

// ✅ Get filters
$filterStatus = $_GET['status'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// ✅ Build SQL query with billing join
$sql = "
SELECT dj.delivery_id, dj.order_id, dj.delivery_status, dj.created_at,
       co.customerId, co.foodId, co.quantity, co.amount, co.orderDate, f.foodName,
       c.Name AS customerFirst,
       d.driver_name,
       b.paymentStatus AS billingPaymentStatus
FROM DeliveryJobs dj
LEFT JOIN customerOrders co ON dj.order_id = co.orderId
LEFT JOIN food f ON co.foodId = f.foodId
LEFT JOIN Customer c ON co.customerId = c.customerId
LEFT JOIN Driver d ON dj.driver_id = d.driver_id
LEFT JOIN billing b ON co.orderGroup = b.orderGroup
WHERE 1
";

// ✅ Apply delivery status filter
if ($filterStatus != '') {
    $sql .= " AND dj.delivery_status='". $conn->real_escape_string($filterStatus) ."'";
}

// ✅ Apply search filter
if (!empty($searchTerm)) {
    $searchTermEsc = $conn->real_escape_string($searchTerm);
    $sql .= " AND (dj.order_id LIKE '%$searchTermEsc%' OR 
                   CONCAT(c.Name) LIKE '%$searchTermEsc%')";
}

$sql .= " ORDER BY dj.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Delivery Status</title>
<link rel="stylesheet" href="viewDeliveryStatus.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f5f7fa;
}
.container {
    width: 95%;
    margin: 30px auto;
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
h1 {
    text-align: center;
    color: #333;
    margin-bottom: 20px;
}
.back-btn {
    text-decoration: none;
    background: #007bff;
    color: #fff;
    padding: 8px 14px;
    border-radius: 8px;
}
.back-btn:hover { background: #0056b3; }
.filter-form {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}
.filter-form select, .filter-form input {
    padding: 6px;
    border-radius: 5px;
    border: 1px solid #ccc;
}
.filter-form button {
    padding: 6px 12px;
    border-radius: 5px;
    border: none;
    background: #28a745;
    color: #fff;
    cursor: pointer;
}
.filter-form button:hover { background: #218838; }
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
table th, table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
}
table th {
    background: #007bff;
    color: #fff;
}
.badge {
    padding: 4px 8px;
    border-radius: 5px;
    font-size: 0.9em;
}
.status-pending { background: #ffc107; color: #000; }
.status-assigned { background: #17a2b8; color: #fff; }
.status-delivered { background: #28a745; color: #fff; }
.status-cancelled { background: #dc3545; color: #fff; }
</style>
</head>
<body>
<div class="container">
    <a href="TransportManagerDashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    <h1>🚚 Delivery Status</h1>

    <!-- Filter and Search -->
    <form method="GET" class="filter-form">
        <label for="status">Filter by Status:</label>
        <select name="status" id="status" onchange="this.form.submit()">
            <option value="">All</option>
            <option value="Pending" <?= $filterStatus=='Pending'?'selected':'' ?>>Pending</option>
            <option value="Assigned" <?= $filterStatus=='Assigned'?'selected':'' ?>>Assigned</option>
            <option value="Delivered" <?= $filterStatus=='Delivered'?'selected':'' ?>>Delivered</option>
            <option value="Cancelled" <?= $filterStatus=='Cancelled'?'selected':'' ?>>Cancelled</option>
        </select>

        <input type="text" name="search" placeholder="Search by Order ID or Customer" value="<?= htmlspecialchars($searchTerm) ?>">
        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Delivery ID</th>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Food</th>
                <th>Quantity</th>
                <th>Amount</th>
                <th>Driver</th>
                <th>Delivery Status</th>
                <th>Payment Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['delivery_id'] ?></td>
                        <td><?= $row['order_id'] ?></td>
                        <td><?= htmlspecialchars($row['customerFirst']) ?></td>
                        <td><?= htmlspecialchars($row['foodName']) ?></td>
                        <td><?= $row['quantity'] ?></td>
                        <td><?= number_format($row['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($row['driver_name'] ?? 'Unassigned') ?></td>
                        <td>
                            <?php
                            $statusClass = '';
                            switch ($row['delivery_status']) {
                                case 'Pending': $statusClass='status-pending'; break;
                                case 'Assigned': $statusClass='status-assigned'; break;
                                case 'Delivered': $statusClass='status-delivered'; break;
                                case 'Cancelled': $statusClass='status-cancelled'; break;
                            }
                            ?>
                            <span class="badge <?= $statusClass ?>"><?= $row['delivery_status'] ?></span>
                        </td>
                        <td>
                            <?php
                            $paymentStatus = $row['billingPaymentStatus'] ?? 'Pending';
                            $paymentClass = ($paymentStatus=='Paid')?'status-delivered':'status-pending';
                            ?>
                            <span class="badge <?= $paymentClass ?>"><?= $paymentStatus ?></span>
                        </td>
                        <td><?= $row['created_at'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="10" class="text-center">No delivery jobs found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
