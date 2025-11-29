<?php
session_start();
include 'config.php';

// ✅ Allow only Stock Keeper or Manager
if (!isset($_SESSION['staffRole']) || !in_array(strtolower(trim($_SESSION['staffRole'])), ['stock keeper'])) {
    header("Location: login.html");
    exit;
}

$success = $error = "";

// ✅ Handle status update
if (isset($_POST['update_status']) && isset($_POST['status'])) {
    foreach ($_POST['status'] as $request_id => $status) {
        $stmt = $conn->prepare("UPDATE ItemRequests SET status=? WHERE request_id=?");
        $stmt->bind_param("si", $status, $request_id);
        $stmt->execute();
        $stmt->close();
    }
    $success = "✅ Request statuses updated successfully!";
}

// ✅ Fetch all department requests with item names
$query = "
    SELECT ir.request_id, ir.department, ir.item_id, ir.quantity, ir.request_date, ir.status, i.item_name
    FROM ItemRequests ir where ir.status='Approved'
    INNER JOIN Items i ON ir.item_id = i.item_id
    ORDER BY ir.request_date DESC
";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Department Item Requests</title>
<link rel="stylesheet" href="updateStock.css"> <!-- reuse your modern CSS -->
<style>
/* ===== Extra Styling for Department Requests ===== */
.form-container {
    max-width: 1100px;
    margin: 40px auto;
    padding: 30px 40px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

h2 { margin-bottom: 20px; }

.search-container {
    margin-bottom: 15px;
    text-align: right;
}

.search-container input {
    padding: 8px 12px;
    width: 250px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
    transition: border-color 0.3s;
}

.search-container input:focus {
    border-color: #0078d7;
    outline: none;
}

select.status-select {
    padding: 6px 8px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
    cursor: pointer;
}

select.status-select:focus {
    border-color: #0078d7;
    outline: none;
}
</style>
</head>
<body>
<div class="form-container">
    <button class="button-back" onclick="window.location.href='StockKeeperDashboard.php'">← Back to Dashboard</button>
    <h2>📄 Department Item Requests</h2>

    <?php if($success) echo "<p class='success'>$success</p>"; ?>
    <?php if($error) echo "<p class='error'>$error</p>"; ?>

    <!-- ===== Search Filter ===== -->
    <div class="search-container">
        <input type="text" id="searchInput" placeholder="🔍 Search by item or department..." onkeyup="filterTable()">
    </div>

    <!-- ===== Requests Table ===== -->
    <form method="POST" action="">
        <table>
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Department</th>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Request Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['request_id']) ?></td>
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td><?= htmlspecialchars($row['item_name']) ?></td>
                    <td><?= htmlspecialchars($row['quantity']) ?></td>
                    <td><?= date("Y-m-d H:i", strtotime($row['request_date'])) ?></td>
                    <td>
                        <select name="status[<?= $row['request_id'] ?>]" class="status-select">
                            <option value="Approved" <?= $row['status']=='Approved'?'selected':'' ?>>Approved</option>
                            <option value="Issued" <?= $row['status']=='Issued'?'selected':'' ?>>Issued</option>
                        </select>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">No department requests found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <?php if($result->num_rows > 0): ?>
        <div class="btn-container">
            <button type="submit" name="update_status">💾 Update Status</button>
        </div>
        <?php endif; ?>
    </form>
</div>

<script>
// ===== Filter Table =====
function filterTable() {
    const input = document.getElementById("searchInput").value.toLowerCase();
    const table = document.querySelector("table tbody");
    const rows = table.getElementsByTagName("tr");

    for(let i=0; i<rows.length; i++){
        const departmentTd = rows[i].getElementsByTagName("td")[1];
        const itemTd = rows[i].getElementsByTagName("td")[2];
        let txtValue = "";
        if(departmentTd && itemTd) {
            txtValue = departmentTd.textContent + " " + itemTd.textContent;
            rows[i].style.display = txtValue.toLowerCase().includes(input) ? "" : "none";
        }
    }
}
</script>
</body>
</html>
