<?php
include 'config.php';

if(isset($_GET['orderId'])) {
    $orderId = intval($_GET['orderId']);

    // Get customerId from the order
    $orderResult = $conn->query("SELECT customerId FROM customerOrders WHERE orderId=$orderId");
    if($orderResult->num_rows > 0){
        $order = $orderResult->fetch_assoc();
        $customerId = $order['customerId'];

        // Get customer details
        $customerResult = $conn->query("SELECT Name, Contact, Address FROM Customer WHERE CustomerId=$customerId");
        if($customerResult->num_rows > 0){
            $customer = $customerResult->fetch_assoc();
            echo json_encode($customer);
        } else {
            echo json_encode([]);
        }
    } else {
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
