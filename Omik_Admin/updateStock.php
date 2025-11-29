<?php
session_start();
include 'config.php';

// ✅ Allow only Stock Keeper
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'stock keeper') {
    header("Location: login.html");
    exit;
}

// ✅ Handle stock update
if (isset($_POST['update_stock']) && isset($_POST['stock'])) {
    foreach ($_POST['stock'] as $stock_id => $data) {
        $quantity = intval($data['quantity']);
        $expiry_date = !empty($data['expiry_date']) ? $data['expiry_date'] : NULL;
        $reorder_level = intval($data['reorder_level']);

        $stmt = $conn->prepare("UPDATE Stock SET quantity = ?, expiry_date = ?, reorder_level = ? WHERE stock_id = ?");
        $stmt->bind_param("isii", $quantity, $expiry_date, $reorder_level, $stock_id);
        $stmt->execute();
        $stmt->close();
    }

    echo "<script>
            alert('✅ Stock levels updated successfully!');
            window.location.href = 'updateStock.php';
          </script>";
    exit;
}

// ✅ Fetch all items with their stock
$query = "
    SELECT i.item_id, i.item_name,
           s.stock_id, s.quantity, s.expiry_date, s.reorder_level
    FROM Items i
    LEFT JOIN Stock s ON i.item_id = s.item_id
    ORDER BY i.item_name ASC
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Stock | Stock Keeper</title>
<link rel="stylesheet" href="updateStock.css">
</head>
<body>

<div class="form-container">
    <button class="button-back" onclick="window.location.href='StockKeeperDashboard.php'">← Back to Dashboard</button>
    <h2>📦 Update Stock Levels</h2>

    <!-- ===== Search Bar ===== -->
    <div class="search-container">
        <input type="text" id="searchInput" placeholder="🔍 Search by item name..." onkeyup="filterTable()">
    </div>

    <form method="POST" action="">
        <table>
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Expiry Date</th>
                    <th>Reorder Level</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['item_id']) ?></td>
                            <td><?= htmlspecialchars($row['item_name']) ?></td>
                            <td>
                                <input type="number" 
                                       name="stock[<?= $row['stock_id'] ?>][quantity]" 
                                       value="<?= htmlspecialchars($row['quantity'] ?? 0) ?>" 
                                       min="0" required>
                            </td>
                            <td>
                                <input type="date" 
                                       name="stock[<?= $row['stock_id'] ?>][expiry_date]" 
                                       value="<?= htmlspecialchars($row['expiry_date'] ?? '') ?>">
                            </td>
                            <td>
                                <input type="number" 
                                       name="stock[<?= $row['stock_id'] ?>][reorder_level]" 
                                       value="<?= htmlspecialchars($row['reorder_level'] ?? 5) ?>" 
                                       min="0" required>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">No items found in stock.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="btn-container">
            <button type="submit" name="update_stock">💾 Update Stock</button>
        </div>
    </form>
</div>

<script>
// ===== Filter Stock Table by Item Name =====
function filterTable() {
    const input = document.getElementById("searchInput");
    const filter = input.value.toLowerCase();
    const table = document.querySelector("table tbody");
    const rows = table.getElementsByTagName("tr");

    for (let i = 0; i < rows.length; i++) {
        const td = rows[i].getElementsByTagName("td")[1]; // Item Name column
        if (td) {
            const txtValue = td.textContent || td.innerText;
            rows[i].style.display = txtValue.toLowerCase().includes(filter) ? "" : "none";
        }
    }
}
</script>

</body>
</html>
