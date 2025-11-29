<?php
session_start();
include 'config.php';

// ✅ Allow only Cashier
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'cashier') {
    header("Location: login.html");
    exit;
}

// Fetch all bills
$result = $conn->query("SELECT * FROM billing ORDER BY createdAt DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View All Bills</title>
<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f4f6f8; }
h2 { text-align: center; color: #333; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
th { background: #007bff; color: white; }
tr:hover { background: #f1f1f1; cursor: pointer; }

.modal {
    display: none; 
    position: fixed; 
    z-index: 999; 
    left: 0;
    top: 0;
    width: 100%; 
    height: 100%; 
    overflow: auto;
    background-color: rgba(0,0,0,0.5); 
}
.modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 20px;
    border-radius: 8px;
    width: 70%;
    max-width: 700px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}
.close:hover { color: black; }

.back-btn {
    top: 20px;             /* distance from top */
    left: 20px;            /* distance from left */
    background: #6c757d;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    z-index: 1000;         /* make sure it’s above other elements */
    font-size: 14px;
}
.back-btn:hover {
    background: #5a6268;
}

</style>
<script>
function openModal(billId){
    // Fetch bill details via AJAX
    fetch('view_bill_popup.php?billId=' + billId)
        .then(response => response.text())
        .then(data => {
            document.getElementById('modalBody').innerHTML = data;
            document.getElementById('billModal').style.display = 'block';
        });
}

function closeModal(){
    document.getElementById('billModal').style.display = 'none';
}
</script>
</head>
<body>
<button class="back-btn" onclick="goBack()">← Back</button>
<script>
function goBack() {
    window.history.back();
    setTimeout(() => { location.reload(); }, 100);
}
</script>


<h2>All Bills</h2>

<table>
<tr>
    <th>Bill ID</th>
    <th>Order Group</th>
    <th>Customer Name</th>
    <th>Total Amount</th>
    <th>Payment Status</th>
    <th>Created At</th>
</tr>
<?php while($row = $result->fetch_assoc()): ?>
<tr onclick="openModal('<?= $row['billingId'] ?>')">
    <td><?= $row['billingId'] ?></td>
    <td><?= htmlspecialchars($row['orderGroup']) ?></td>
    <td><?= htmlspecialchars($row['fullName']) ?></td>
    <td>Rs. <?= number_format($row['totalAmount'],2) ?></td>
    <td><?= $row['paymentStatus'] ?></td>
    <td><?= $row['createdAt'] ?></td>
</tr>
<?php endwhile; ?>
</table>

<!-- Modal -->
<div id="billModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <div id="modalBody">Loading...</div>
    </div>
</div>

</body>
</html>
