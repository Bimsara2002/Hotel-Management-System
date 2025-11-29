<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data safely
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        echo json_encode([
            "status" => "error",
            "message" => "Email and password are required"
        ]);
        exit;
    }

    // Fetch customer by email
    $sql = "SELECT * FROM Customer WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify hashed password
        if (password_verify($password, $user['Password'])) {
            $_SESSION['userId']     = $user['CustomerId'];
            $_SESSION['customerId'] = $user['CustomerId'];
            $_SESSION['userName']   = $user['Name'];

            echo json_encode([
                "status" => "success",
                "message" => "Login successful",
                "type" => "customer"
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid password"
            ]);
        }
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Email not registered"
        ]);
    }

    $stmt->close();
} else {
    // GET request -> show login form
    header("Location: login.html"); // or your HTML login page
    exit;
}

$conn->close();
