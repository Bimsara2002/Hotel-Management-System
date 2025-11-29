<?php
include 'config.php';

$status = $_GET['status'] ?? 'Pending';

$sql = "SELECT so.order_id, so.item_name, so.quantity, so.order_date, s.supplier_name 
        FROM StockOrders so 
        JOIN Suppliers s ON so.supplier_id = s.supplier_id
        WHERE so.status = ?
        ORDER BY so.order_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $status);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table class='orders-table'>
            <tr>
                <th>Order ID</th>
                <th>Item Name</th>
                <th>Quantity</th>
                <th>Supplier</th>
                <th>Order Date</th>
                <th>Status</th>
            </tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['order_id']}</td>
                <td>{$row['item_name']}</td>
                <td>{$row['quantity']}</td>
                <td>{$row['supplier_name']}</td>
                <td>{$row['order_date']}</td>
                <td>{$status}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p style='text-align:center; color:#999;'>No $status orders found.</p>";
}
$stmt->close();
?>
