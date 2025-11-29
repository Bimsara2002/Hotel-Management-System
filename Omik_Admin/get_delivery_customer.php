<?php
include 'config.php';

if(isset($_GET['orderGroup'])) {
    $orderGroup = $_GET['orderGroup'];

    $stmt = $conn->prepare("
        SELECT co.customerId, c.Name, c.Contact, c.Address
        FROM customerOrders co
        JOIN Customer c ON co.customerId = c.CustomerId
        WHERE co.orderGroup=? AND co.type='Delivery' LIMIT 1
    ");
    $stmt->bind_param("s", $orderGroup);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->num_rows > 0 ? $result->fetch_assoc() : []);
}
?>
