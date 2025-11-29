<?php
include 'config.php';
session_start();

// ✅ Only Inventory Manager can access
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'inventory manager') {
    header("Location: login.html");
    exit;
}

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $supplier_name = trim($_POST['supplier_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = trim($_POST['password']); // Or auto-generate

    // ✅ Basic validation
    if (empty($supplier_name) || empty($email) || empty($password)) {
        $error = "Please fill all required fields.";
    } else {
        // ✅ Check if email already exists
        $stmt = $conn->prepare("SELECT * FROM Suppliers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already registered!";
        } else {
            // ✅ Hash password for security
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // ✅ Insert supplier data
            $stmt = $conn->prepare("INSERT INTO Suppliers (supplier_name, email, phone, address, password, status) VALUES (?, ?, ?, ?, ?, 'Active')");
            $stmt->bind_param("sssss", $supplier_name, $email, $phone, $address, $hashedPassword);

            if ($stmt->execute()) {
                $success = "Supplier added successfully!";
            } else {
                $error = "Error adding supplier.";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Supplier</title>
<style>
/* ===== Same CSS as before ===== */
body {
    background: #f4f6f8;
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: Arial, sans-serif;
    margin: 0;
    min-height: 100vh;
}
.register-container {
    background-color: #fff;
    padding: 30px 40px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    width: 100%;
    max-width: 500px;
}
.register-container h2 { text-align: center; margin-bottom: 25px; color: #0d6efd; font-size: 28px; }
.success { color: green; text-align: center; margin-bottom: 15px; }
.error { color: red; text-align: center; margin-bottom: 15px; }
.register-container form { display: flex; flex-direction: column; }
.register-container form label { margin-bottom: 5px; font-weight: 500; }
.register-container form input, .register-container form textarea { padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; width: 100%; }
.register-container form button { background-color: #0d6efd; color: white; font-weight: 600; padding: 12px 0; border: none; border-radius: 8px; cursor: pointer; transition: 0.3s; margin-top: 10px; }
.register-container form button:hover { background-color: #084298; }
</style>
</head>
<body>
<div class="register-container">
    <h2>Add Supplier</h2>

    <?php if ($success): ?>
        <p class="success"><?php echo $success; ?></p>
    <?php elseif ($error): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Supplier Name *</label>
        <input type="text" name="supplier_name" required>

        <label>Email *</label>
        <input type="email" name="email" required>

        <label>Phone</label>
        <input type="text" name="phone">

        <label>Address</label>
        <textarea name="address" rows="3"></textarea>

        <label>Password *</label>
        <input type="text" name="password" required placeholder="Temporary password">

        <button type="submit">Add Supplier</button>
    </form>
</div>
</body>
</html>
