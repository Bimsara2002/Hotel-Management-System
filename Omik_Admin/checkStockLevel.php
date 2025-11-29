<?php
session_start();
include 'config.php';

// ✅ Restrict access
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'inventory manager') {
    header("Location: login.html");
    exit;
}

// ✅ Fetch stock data
$sql = "SELECT 
            i.item_id,
            i.item_name,
            i.unite,
            i.price,
            s.quantity,
            s.expiry_date,
            s.reorder_level,
            sp.supplier_name
        FROM Items i
        JOIN Stock s ON i.item_id = s.item_id
        LEFT JOIN Suppliers sp ON i.supplier_id = sp.supplier_id
        ORDER BY i.item_name ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Stock Level</title>
    <link rel="stylesheet" href="checkStockLevel.css">
</head>
<body>
    <div class="container">
        <h1>📦 Check Stock Level</h1>

        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Search item by name...">
        </div>

        <table id="stockTable">
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Item Name</th>
                    <th>Unit</th>
                    <th>Price (LKR)</th>
                    <th>Quantity</th>
                    <th>Reorder Level</th>
                    <th>Expiry Date</th>
                    <th>Supplier</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): 
                        $status = ($row['quantity'] <= $row['reorder_level']) ? '⚠️ Low Stock' : '✅ In Stock';
                        $statusClass = ($row['quantity'] <= $row['reorder_level']) ? 'low-stock' : 'in-stock';
                    ?>
                    <tr class="<?= $statusClass ?>">
                        <td><?= htmlspecialchars($row['item_id']); ?></td>
                        <td><?= htmlspecialchars($row['item_name']); ?></td>
                        <td><?= htmlspecialchars($row['unite']); ?></td>
                        <td><?= number_format($row['price'], 2); ?></td>
                        <td><?= htmlspecialchars($row['quantity']); ?></td>
                        <td><?= htmlspecialchars($row['reorder_level']); ?></td>
                        <td><?= htmlspecialchars($row['expiry_date']); ?></td>
                        <td><?= htmlspecialchars($row['supplier_name']); ?></td>
                        <td><?= $status; ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9">No stock data found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <button onclick="window.location.href='inventoryManagerDashboard.php'" class="back-btn">⬅ Back to Dashboard</button>
    </div>

    <!-- ✅ JavaScript for Live Search -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('searchInput');
            const rows = document.querySelectorAll('#stockTable tbody tr');

            searchInput.addEventListener('keyup', () => {
                const filter = searchInput.value.toLowerCase();
                rows.forEach(row => {
                    const itemName = row.cells[1].textContent.toLowerCase();
                    row.style.display = itemName.includes(filter) ? '' : 'none';
                });
            });
        });
    </script>
</body>
</html>
