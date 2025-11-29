<?php
session_start();
include 'config.php';

if (!isset($_SESSION['userId'])) {
    echo json_encode(['status'=>'error','message'=>'Not logged in']);
    exit;
}

$userId = $_SESSION['userId'];

// ===== GET: fetch current profile =====
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("SELECT Name, Email, Contact, Address FROM Customer WHERE CustomerId=?");
    $stmt->bind_param("i",$userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows>0){
        echo json_encode($res->fetch_assoc());
    } else {
        echo json_encode(['status'=>'error','message'=>'Customer not found']);
    }
    exit;
}

// ===== POST: update profile =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $address = trim($_POST['address']);
    $password = trim($_POST['password']);

    if(empty($name)||empty($email)){
        echo json_encode(['status'=>'error','message'=>'Name and Email required']);
        exit;
    }

    // Check email uniqueness
    $stmt = $conn->prepare("SELECT CustomerId FROM Customer WHERE Email=? AND CustomerId!=?");
    $stmt->bind_param("si",$email,$userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows>0){
        echo json_encode(['status'=>'error','message'=>'Email already used']);
        exit;
    }

    if(!empty($password)){
        $hashed = password_hash($password,PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE Customer SET Name=?,Email=?,Contact=?,Address=?,Password=? WHERE CustomerId=?");
        $stmt->bind_param("sssssi",$name,$email,$contact,$address,$hashed,$userId);
    } else {
        $stmt = $conn->prepare("UPDATE Customer SET Name=?,Email=?,Contact=?,Address=? WHERE CustomerId=?");
        $stmt->bind_param("ssssi",$name,$email,$contact,$address,$userId);
    }

    if($stmt->execute()){
        echo json_encode(['status'=>'success','message'=>'Profile updated successfully']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Failed to update profile']);
    }
    exit;
}
?>
