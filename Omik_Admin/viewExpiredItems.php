<?php
session_start();
include 'config.php';

// ✅ Allow only Stock Keeper
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'stock keeper') {
    header("Location: login.html");
    exit;
}

// ✅ Fetch only expired items
$today = date('Y-m-d');
$query = "
    SELECT 
        i.item_id, i.item_name,
        s.stock_id, s.quantity, s.expiry_date, s.reorder_level
    FROM Stock s
    INNER JOIN Items i ON s.item_id = i.item_id
    WHERE s.expiry_date IS NOT NULL AND s.expiry_date < ?
    ORDER BY s.expiry_date ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Expired Items | Stock Keeper</title>
<link rel="stylesheet" href="updateStock.css">
</head>
<body>

<div class="form-container">
    <button class="button-back" onclick="window.location.href='StockKeeperDashboard.php'">← Back to Dashboard</button>
    <h2>⚠️ Expired Stock Items</h2>

    <!-- ===== Search Bar ===== -->
    <div class="search-container">
        <input type="text" id="searchInput" placeholder="🔍 Search by item name..." onkeyup="filterTable()">
    </div>

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
                        <td><?= htmlspecialchars($row['quantity']) ?></td>
                        <td><?= htmlspecialchars($row['expiry_date']) ?></td>
                        <td><?= htmlspecialchars($row['reorder_level']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No expired items found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// ===== Filter Expired Items Table by Name =====
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
