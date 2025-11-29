<?php
session_start();
include 'config.php';

// ✅ Only logged-in suppliers
if (!isset($_SESSION['supplier_id'])) {
    header("Location: supplier_login.php");
    exit;
}

$supplier_id = $_SESSION['supplier_id'];

// ✅ Handle status update
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE StockOrders SET status = ? WHERE order_id = ? AND supplier_id = ?");
    $stmt->bind_param("sii", $status, $order_id, $supplier_id);
    $stmt->execute();
}

// ✅ Fetch orders for the logged-in supplier
$stmt = $conn->prepare("SELECT * FROM StockOrders WHERE supplier_id = ? ORDER BY order_date DESC");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Supplier Orders</title>
<link rel="stylesheet" href="supplier.css">
<style>
.orders-container {
    padding: 40px;
    max-width: 1000px;
    margin: 0 auto;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.orders-container h2 {
    color: #1a73e8;
    margin-bottom: 25px;
    text-align: center;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 15px;
}

th, td {
    border-bottom: 1px solid #ddd;
    padding: 12px;
    text-align: center;
}

th {
    background: #1a73e8;
    color: white;
}

tr:hover {
    background: #f9f9f9;
}

select {
    padding: 5px 8px;
    border-radius: 5px;
    border: 1px solid #ccc;
}

button {
    background: #1a73e8;
    color: white;
    border: none;
    padding: 6px 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.3s;
}

button:hover {
    background: #155bc5;
}

.status-badge {
    padding: 6px 10px;
    border-radius: 8px;
    font-weight: bold;
    color: white;
}

.status-Pending { background: #ffb74d; }
.status-Approved { background: #42a5f5; }
.status-Delivered { background: #66bb6a; }
.status-Cancelled { background: #ef5350; }

.back-link {
    display: inline-block;
    margin-bottom: 20px;
    color: #1a73e8;
    text-decoration: none;
    font-weight: 600;
}
.back-link:hover {
    text-decoration: underline;
}
</style>
</head>
<body>

<nav>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['supplier_name']); ?> 👋</h2>
    <div>
        <a href="SupplierDashboard.php">🏠 Dashboard</a>
        <a href="supplier_orders.php">📦 Orders</a>
        <a href="supplier_payments.php">💵 Payments</a>
        <a href="supplier_logout.php" class="logout">🚪 Logout</a>
    </div>
</nav>

<div class="orders-container">
    <a href="SupplierDashboard.php" class="back-link">⬅ Back to Dashboard</a>
    <h2>📦 My Orders</h2>

    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Item Name</th>
                <th>Quantity</th>
                <th>Order Date</th>
                <th>Current Status</th>
                <th>Update Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['order_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                        <td><?php echo $row['quantity']; ?></td>
                        <td><?php echo $row['order_date']; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $row['status']; ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?php echo $row['order_id']; ?>">
                                <select name="status">
                                    <option value="Pending" <?php if($row['status']=='Pending') echo 'selected'; ?>>Pending</option>
                                    <option value="Approved" <?php if($row['status']=='Approved') echo 'selected'; ?>>Approved</option>
                                    <option value="Delivered" <?php if($row['status']=='Delivered') echo 'selected'; ?>>Delivered</option>
                                    <option value="Cancelled" <?php if($row['status']=='Cancelled') echo 'selected'; ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_status">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">No orders found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
