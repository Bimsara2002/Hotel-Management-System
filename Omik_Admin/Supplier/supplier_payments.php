<?php
session_start();
include 'config.php';

if (!isset($_SESSION['supplier_id'])) {
    header("Location: supplier_login.php");
    exit;
}

$supplier_id = $_SESSION['supplier_id'];

// ✅ Handle filters
$paymentFilter = $_GET['payment_status'] ?? '';
$approvalFilter = $_GET['approval_status'] ?? '';
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';

// Build query dynamically
$query = "SELECT * FROM SupplierPayment WHERE supplier_id = ?";
$params = [$supplier_id];
$types = "i";

if ($paymentFilter) {
    $query .= " AND PaymentStatus = ?";
    $types .= "s";
    $params[] = $paymentFilter;
}

if ($approvalFilter) {
    $query .= " AND Status = ?";
    $types .= "s";
    $params[] = $approvalFilter;
}

if ($fromDate) {
    $query .= " AND DATE(PaymentDate) >= ?";
    $types .= "s";
    $params[] = $fromDate;
}

if ($toDate) {
    $query .= " AND DATE(PaymentDate) <= ?";
    $types .= "s";
    $params[] = $toDate;
}

$query .= " ORDER BY PaymentDate DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Supplier Payments</title>
<link rel="stylesheet" href="supplier.css">
<style>
.payments-container {
    padding: 40px;
    max-width: 1200px;
    margin: 0 auto;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.payments-container h2 {
    color: #1a73e8;
    margin-bottom: 20px;
    text-align: center;
}

.filters {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.filters select, .filters input {
    padding: 6px 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

.filters button {
    background: #1a73e8;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
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

.status-badge {
    padding: 6px 10px;
    border-radius: 8px;
    font-weight: bold;
    color: white;
}

.status-Pending { background: #ffb74d; }
.status-Paid { background: #42a5f5; }
.status-NotPaid { background: #ef5350; }
.status-Approved { background: #42a5f5; }
.status-Rejected { background: #ef5350; }

.back-link {
    display: inline-block;
    margin-bottom: 20px;
    color: #1a73e8;
    text-decoration: none;
    font-weight: 600;
}
.back-link:hover { text-decoration: underline; }
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

<div class="payments-container">
    <a href="SupplierDashboard.php" class="back-link">⬅ Back to Dashboard</a>
    <h2>💵 My Payments</h2>

    <!-- ===== Filters ===== -->
    <form method="GET" class="filters">
        <select name="payment_status">
            <option value="">All Payment Status</option>
            <option value="Pending" <?php if($paymentFilter=='Pending') echo 'selected'; ?>>Pending</option>
            <option value="Paid" <?php if($paymentFilter=='Paid') echo 'selected'; ?>>Paid</option>
            <option value="Not Paid" <?php if($paymentFilter=='Not Paid') echo 'selected'; ?>>Not Paid</option>
        </select>

        <select name="approval_status">
            <option value="">All Approval Status</option>
            <option value="Pending" <?php if($approvalFilter=='Pending') echo 'selected'; ?>>Pending</option>
            <option value="Approved" <?php if($approvalFilter=='Approved') echo 'selected'; ?>>Approved</option>
            <option value="Rejected" <?php if($approvalFilter=='Rejected') echo 'selected'; ?>>Rejected</option>
        </select>

        <input type="date" name="from_date" value="<?php echo $fromDate; ?>" placeholder="From">
        <input type="date" name="to_date" value="<?php echo $toDate; ?>" placeholder="To">

        <button type="submit">Filter</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Payment ID</th>
                <th>Amount</th>
                <th>Payment Date</th>
                <th>Payment Status</th>
                <th>Approval Status</th>
                <th>Payment Method</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['PaymentId']; ?></td>
                        <td><?php echo number_format($row['Amount'],2); ?></td>
                        <td><?php echo $row['PaymentDate']; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo str_replace(' ', '', $row['PaymentStatus']); ?>">
                                <?php echo $row['PaymentStatus']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo str_replace(' ', '', $row['Status']); ?>">
                                <?php echo $row['Status']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($row['PaymentMethod']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">No payments found for the selected filters.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
