<?php
session_start();
include 'config.php';

// ✅ Restrict access
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'inventory manager') {
    header("Location: login.html");
    exit;
}

$message = "";

// ✅ Handle Approve or Mark as Paid Actions
if (isset($_POST['approve'])) {
    $id = intval($_POST['payment_id']);
    $conn->query("UPDATE SupplierPayment SET Status='Approved' WHERE PaymentId=$id");
    $message = "Payment request approved successfully.";
}

if (isset($_POST['mark_paid'])) {
    $id = intval($_POST['payment_id']);
    $conn->query("UPDATE SupplierPayment SET PaymentStatus='Paid', Status='Approved' WHERE PaymentId=$id");
    $message = "Payment marked as Paid successfully.";
}

// ✅ Fetch All Payments
$sql = "SELECT * FROM SupplierPayment ORDER BY PaymentDate DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Supplier Payment Management</title>
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f6f8fa;
    margin: 0;
    padding: 0;
}
.container {
    width: 90%;
    margin: 40px auto;
    background: #fff;
    padding: 25px 30px;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}
h1 {
    text-align: center;
    color: #333;
    margin-bottom: 25px;
}
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
button, .btn {
    border: none;
    padding: 8px 14px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: 0.3s;
}
.btn-approve {
    background: #28a745;
    color: white;
}
.btn-approve:hover {
    background: #218838;
}
.btn-paid {
    background: #007bff;
    color: white;
}
.btn-paid:hover {
    background: #0056b3;
}
.btn-back {
    background: #6c757d;
    color: white;
}
.btn-back:hover {
    background: #5a6268;
}
.btn-new {
    background: #17a2b8;
    color: white;
}
.btn-new:hover {
    background: #138496;
}
.table-wrapper {
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
    text-align: center;
    font-size: 15px;
}
th {
    background: #343a40;
    color: white;
    padding: 12px 10px;
}
td {
    background: #fff;
    padding: 10px;
    border-bottom: 1px solid #eee;
}
tr:hover td {
    background: #f1f1f1;
}
.status-badge {
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
}
.status-pending {
    background: #ffc10733;
    color: #856404;
}
.status-approved {
    background: #28a74533;
    color: #155724;
}
.status-paid {
    background: #007bff33;
    color: #004085;
}
.message {
    background: #d4edda;
    color: #155724;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
}
form {
    display: inline;
}
</style>
</head>
<body>
<div class="container">
    <div class="top-bar">
        <h1>Supplier Payment Management</h1>
        <div>
 <button onclick="window.location.href='inventoryManagerDashboard.php'" class="back-btn">⬅ Back to Dashboard</button>            <button class="btn btn-new" onclick="window.location.href='AddSupplierPayment.php'">➕ New Payment</button>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Supplier ID</th>
                    <th>Supplier Name</th>
                    <th>Amount (Rs.)</th>
                    <th>Payment Date</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Payment Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['PaymentId'] ?></td>
                    <td><?= $row['supplier_id'] ?></td>
                    <td><?= htmlspecialchars($row['SupplierName']) ?></td>
                    <td><?= number_format($row['Amount'], 2) ?></td>
                    <td><?= $row['PaymentDate'] ?></td>
                    <td><?= htmlspecialchars($row['PaymentMethod']) ?></td>
                    <td>
                        <span class="status-badge 
                            <?= strtolower($row['Status']) == 'approved' ? 'status-approved' : 
                               (strtolower($row['Status']) == 'pending' ? 'status-pending' : '') ?>">
                            <?= ucfirst($row['Status']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge 
                            <?= strtolower($row['PaymentStatus']) == 'paid' ? 'status-paid' : 'status-pending' ?>">
                            <?= ucfirst($row['PaymentStatus']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if (strtolower($row['Status']) == 'pending'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="payment_id" value="<?= $row['PaymentId'] ?>">
                                <button type="submit" name="approve" class="btn btn-approve">Approve</button>
                            </form>
                        <?php endif; ?>

                        <?php if (strtolower($row['Status']) == 'approved' && strtolower($row['PaymentStatus']) != 'paid'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="payment_id" value="<?= $row['PaymentId'] ?>">
                                <button type="submit" name="mark_paid" class="btn btn-paid">Mark as Paid</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="9">No supplier payments found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>

<?php $conn->close(); ?>
