<?php
include 'config.php';
$orderGroup = $_GET['orderGroup'] ?? '';
if(!$orderGroup) { echo json_encode([]); exit; }

$result = $conn->query("SELECT co.*, f.foodName, f.price FROM customerOrders co JOIN food f ON co.foodId=f.foodId WHERE co.orderGroup='$orderGroup'");
$items = [];
$customer = null;

while($row = $result->fetch_assoc()){
    $items[] = [
        'foodName'=>$row['foodName'],
        'price'=>$row['price'],
        'quantity'=>$row['quantity']
    ];
    if($row['type']=='Delivery' && !$customer){
        $cust = $conn->query("SELECT * FROM Customer WHERE CustomerId=".$row['customerId'])->fetch_assoc();
        $customer = $cust;
    }
}

echo json_encode(['items'=>$items,'customer'=>$customer]);
?>
