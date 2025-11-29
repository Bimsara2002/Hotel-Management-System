<?php
include 'config.php';

if (isset($_POST['generate_bill'])) {
    $orderGroup = $_POST['orderGroup'];
    $customerId = intval($_POST['customerId']);
    $paymentType = $_POST['paymentType'];
    $total = $_POST['total'];

    $cust = $conn->query("SELECT * FROM Customer WHERE CustomerId=$customerId")->fetch_assoc();

    // Insert billing record
    $stmt = $conn->prepare("INSERT INTO billing (orderGroup, customerId, fullName, phone, address, paymentType, totalAmount, paymentStatus) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("sissssd", $orderGroup, $customerId, $cust['Name'], $cust['Contact'], $cust['Address'], $paymentType, $total);
    $stmt->execute();

    // Update related customer orders
    $conn->query("UPDATE customerOrders SET paymentStatus='Paid', status='Completed' WHERE orderGroup='$orderGroup'");

    header("Location: print_bill.php?group=$orderGroup");
    exit;
}
?>
