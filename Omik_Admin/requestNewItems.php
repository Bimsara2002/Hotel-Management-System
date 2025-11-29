<?php
session_start();
include 'config.php';

// ✅ Allow only Stock Keeper
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'stock keeper') {
    header("Location: login.html");
    exit;
}

$success = $error = "";

// ✅ Handle new request submission
if (isset($_POST['submit_request'])) {
    $item_name = trim($_POST['item_name']);
    $quantity = intval($_POST['quantity']);

    if (empty($item_name) || $quantity <= 0) {
        $error = "⚠️ Please enter a valid item name and quantity.";
    } else {
        $stmt = $conn->prepare("INSERT INTO NewItemRequests (item_name, quantity) VALUES (?, ?)");
        $stmt->bind_param("si", $item_name, $quantity);
        if ($stmt->execute()) {
            $success = "✅ New item request submitted successfully!";
        } else {
            $error = "❌ Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ✅ Handle status update
if (isset($_POST['update_status']) && isset($_POST['status'])) {
    foreach ($_POST['status'] as $request_id => $status) {
        $stmt = $conn->prepare("UPDATE NewItemRequests SET status=? WHERE request_id=?");
        $stmt->bind_param("si", $status, $request_id);
        $stmt->execute();
        $stmt->close();
    }
    $success = "✅ Request statuses updated successfully!";
}

// ✅ Fetch all new item requests
$query = "SELECT * FROM NewItemRequests ORDER BY request_date DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Item Requests | Stock Keeper</title>
<link rel="stylesheet" href="updateStock.css"> <!-- reuse modern CSS -->
<style>
/* ===== Extra Styling for Requests Page ===== */
.form-container {
    max-width: 1100px;
    margin: 40px auto;
    padding: 30px 40px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

h2 { margin-bottom: 20px; }

.add-request-form {
    margin-bottom: 40px;
    border: 1px solid #ddd;
    padding: 20px;
    border-radius: 10px;
    background: #f9f9f9;
}

.add-request-form input[type="text"],
.add-request-form input[type="number"] {
    width: 48%;
    margin-right: 2%;
    margin-bottom: 10px;
}

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
    <h2>📝 New Item Requests</h2>

    <!-- ===== Add New Request ===== -->
    <?php if($success) echo "<p class='success'>$success</p>"; ?>
    <?php if($error) echo "<p class='error'>$error</p>"; ?>

    <div class="add-request-form">
        <form method="POST" onsubmit="return validateAddRequest();">
            <input type="text" name="item_name" placeholder="Item Name" required>
            <input type="number" name="quantity" placeholder="Quantity" min="1" required>
            <button type="submit" name="submit_request">➕ Add Request</button>
        </form>
    </div>

    <!-- ===== Search & Filter ===== -->
    <div class="search-container">
        <input type="text" id="searchInput" placeholder="🔍 Search by item name..." onkeyup="filterTable()">
    </div>

    <!-- ===== Requests Table ===== -->
    <form method="POST" action="">
        <table>
            <thead>
                <tr>
                    <th>Request ID</th>
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
                    <td><?= htmlspecialchars($row['item_name']) ?></td>
                    <td><?= htmlspecialchars($row['quantity']) ?></td>
                    <td><?= date("Y-m-d H:i", strtotime($row['request_date'])) ?></td>
                    <td>
                        <select name="status[<?= $row['request_id'] ?>]" class="status-select">
                            <option value="Pending" <?= $row['status']=='Pending'?'selected':'' ?>>Pending</option>
                            <option value="Approved" <?= $row['status']=='Approved'?'selected':'' ?>>Approved</option>
                            <option value="Rejected" <?= $row['status']=='Rejected'?'selected':'' ?>>Rejected</option>
                        </select>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No new item requests found.</td></tr>
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

    for(let i = 0; i < rows.length; i++){
        const itemNameTd = rows[i].getElementsByTagName("td")[1];
        if(itemNameTd){
            const txtValue = itemNameTd.textContent || itemNameTd.innerText;
            rows[i].style.display = txtValue.toLowerCase().includes(input) ? "" : "none";
        }
    }
}

// ===== Validate Add Request =====
function validateAddRequest() {
    const name = document.querySelector("input[name='item_name']").value.trim();
    const qty = parseInt(document.querySelector("input[name='quantity']").value);
    if(!name || qty <= 0) {
        alert("⚠️ Please enter valid item name and quantity.");
        return false;
    }
    return true;
}
</script>
</body>
</html>
