<?php
include 'config.php';

// Handle Approve/Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['paymentId'];
    $action = $_POST['action'];
    $newStatus = ($action === 'approve') ? 'Approved' : 'Rejected';
    $query = "UPDATE SupplierPayment SET Status = '$newStatus' WHERE PaymentId = $id";
    $conn->query($query);
}

// Fetch Data
$sql = "SELECT * FROM SupplierPayment ORDER BY PaymentDate DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Supplier Payments Approval</title>
<link rel="stylesheet" href="supplierPayments.css">
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>

<div class="container">
    <h2>Supplier Payment Approval</h2>
     <button class="back-btn" onclick="window.location.href='ManagerDashboard.php'"><i class="fas fa-arrow-left"></i> Back to Dashboard</button>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Supplier Name</th>
                <th>Amount (LKR)</th>
                <th>Method</th>
                <th>Status</th>
                <th>Payment Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= $row['PaymentId'] ?></td>
                    <td><?= htmlspecialchars($row['SupplierName']) ?></td>
                    <td><?= number_format($row['Amount'], 2) ?></td>
                    <td><?= htmlspecialchars($row['PaymentMethod']) ?></td>
                    <td class="status <?= strtolower($row['Status']) ?>">
                        <?= htmlspecialchars($row['Status']) ?>
                    </td>
                    <td><?= $row['PaymentDate'] ?></td>
                    <td>
                        <?php if ($row['Status'] === 'Pending') { ?>
                            <form method="POST" class="action-form">
                                <input type="hidden" name="paymentId" value="<?= $row['PaymentId'] ?>">
                                <button type="submit" name="action" value="approve" class="approve-btn"><i class="fas fa-check"></i> Approve</button>
                                <button type="submit" name="action" value="reject" class="reject-btn"><i class="fas fa-times"></i> Reject</button>
                            </form>
                        <?php } else { ?>
                            <span class="completed"><?= $row['Status'] ?></span>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

   
</div>

</body>
</html>
