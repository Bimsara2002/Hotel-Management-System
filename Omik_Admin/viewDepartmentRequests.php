<?php
session_start();
include 'config.php';

// ✅ Allow only Inventory Manager
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'inventory manager') {
    exit("Unauthorized");
}

// ===== Handle AJAX POST for status update =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['status'])) {
    $request_id = intval($_POST['request_id']);
    $status = $_POST['status'] === 'Approved' ? 'Approved' : 'Rejected';

    $stmt = $conn->prepare("UPDATE ItemRequests SET status=? WHERE request_id=?");
    $stmt->bind_param("si", $status, $request_id);

    if ($stmt->execute()) {
        echo "Request $status successfully";
    } else {
        echo "Failed: " . $conn->error;
    }
    $stmt->close();
    exit;
}

// ===== Fetch all requests =====
$sql = "SELECT ir.request_id, ir.department, i.item_name, ir.quantity, ir.request_date, ir.status
        FROM ItemRequests ir
        JOIN Items i ON ir.item_id = i.item_id
        ORDER BY ir.request_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Item Requests</title>
<style>
/* ===== General Page Styles ===== */
body { font-family: Arial, sans-serif; margin: 20px; background: #f9f9f9; color: #333; }
.container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
h1 { text-align: center; margin-bottom: 10px; color: #2c3e50; }
.message { text-align: center; color: green; margin-bottom: 15px; font-weight: bold; }
.back-btn { display: inline-block; padding: 8px 15px; background: #3498db; color: #fff; border-radius: 5px; margin-bottom: 20px; transition: background 0.3s; }
.back-btn:hover { background: #2980b9; }
table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
table th, table td { padding: 10px; border: 1px solid #ccc; text-align: center; }
table th { background: #0145acff; color: #fff; }
table tr:nth-child(even) { background: #f4f4f4; }
.status { font-weight: bold; padding: 4px 8px; border-radius: 4px; display: inline-block; }
.status.Pending { background: #f1c40f; color: #fff; }
.status.Approved { background: #2ecc71; color: #fff; }
.status.Rejected { background: #e74c3c; color: #fff; }
.approve-btn, .reject-btn { padding: 5px 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; transition: 0.3s; }
.approve-btn { background: #2ecc71; color: #fff; margin-right: 5px; }
.approve-btn:hover { background: #27ae60; }
.reject-btn { background: #e74c3c; color: #fff; }
.reject-btn:hover { background: #c0392b; }
em { color: #777; font-style: normal; }
</style>
</head>
<body>
<div class="container">
    <a href="inventoryManagerDashboard.php" class="back-btn">⬅ Back to Dashboard</a>
    <h1>📝 View Item Requests</h1>

    <?php if ($result->num_rows > 0): ?>
    <table>
        <tr>
            <th>Request ID</th>
            <th>Department</th>
            <th>Item Name</th>
            <th>Quantity</th>
            <th>Request Date</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['request_id'] ?></td>
            <td><?= htmlspecialchars($row['department']) ?></td>
            <td><?= htmlspecialchars($row['item_name']) ?></td>
            <td><?= $row['quantity'] ?></td>
            <td><?= $row['request_date'] ?></td>
            <td><span class="status <?= $row['status'] ?>"><?= $row['status'] ?></span></td>
            <td>
                <?php if ($row['status'] === 'Pending'): ?>
                    <button class="approve-btn" onclick="updateStatus(<?= $row['request_id'] ?>,'Approved')">Approve</button>
                    <button class="reject-btn" onclick="updateStatus(<?= $row['request_id'] ?>,'Rejected')">Reject</button>
                <?php else: ?>
                    <em>No action</em>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <p style="text-align:center; color:#777;">No item requests found.</p>
    <?php endif; ?>
</div>

<script>
function updateStatus(id, status) {
    if(confirm(`Are you sure to mark as ${status}?`)){
        var formData = new FormData();
        formData.append('request_id', id);
        formData.append('status', status);

        fetch('<?= $_SERVER['PHP_SELF'] ?>', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(msg => {
            alert(msg);
            location.reload();
        })
        .catch(err => alert('Error: ' + err));
    }
}
</script>
</body>
</html>
