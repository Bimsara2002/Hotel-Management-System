<?php
session_start();
include 'config.php';

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$address = trim($_POST['address'] ?? '');
$password = trim($_POST['password'] ?? '');

if(!$name || !$email || !$password){
    echo json_encode(["status"=>"error","message"=>"Name, email and password are required"]);
    exit;
}

// Check if email exists
$stmt = $conn->prepare("SELECT CustomerId FROM Customer WHERE Email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if($stmt->num_rows > 0){
    echo json_encode(["status"=>"error","message"=>"Email already registered"]);
    exit;
}
$stmt->close();

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert new customer
$stmt = $conn->prepare("INSERT INTO Customer (Name, Email, Contact, Address, Password) VALUES (?,?,?,?,?)");
$stmt->bind_param("sssss", $name, $email, $contact, $address, $hashedPassword);

if($stmt->execute()){
    echo json_encode(["status"=>"success","message"=>"Registration successful! You can now login."]);
}else{
    echo json_encode(["status"=>"error","message"=>"Failed to register, try again."]);
}
$stmt->close();
$conn->close();
?>
