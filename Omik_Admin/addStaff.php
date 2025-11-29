<?php
session_start();
include 'config.php'; // Your DB connection

// Only HR Manager can access
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'hr manager') {
    header("Location: login.html");
    exit;
}

$staffMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['addStaff'])) {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $accountNumber = $_POST['accountNumber'] ?: 0;
    $jobRole = $_POST['jobRole'];
    $otRate = $_POST['otRate'] ?: 0;
    $basicSalary = $_POST['basicSalary'] ?: 0;
    $email = trim($_POST['email']);
    $status = $_POST['status'];
    $password = trim($_POST['password']); 

    // Validate required fields
    if (!empty($firstName) && !empty($lastName) && !empty($email) && !empty($password) && !empty($jobRole)) {

        // Check if email already exists
        $stmt = $conn->prepare("SELECT StaffId FROM Staff WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $staffMessage = "⚠ This email is already registered.";
        } else {
            $stmt->close();
  $stmt = $conn->prepare("INSERT INTO Staff (FirstName, LastName, AccountNumber, JobRole, OtRate, BasicSalary, Email, Status, Password) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssisddsss", $firstName, $lastName, $accountNumber, $jobRole, $otRate, $basicSalary, $email, $status, $password);

            $staffMessage = $stmt->execute() ? "✅ Staff added successfully!" : "❌ Error: " . $stmt->error;
            $stmt->close();
        }

    } else {
        $staffMessage = "⚠ Please fill in all required fields.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add New Staff - HR Manager</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, sans-serif;
    background: #f4f6f9;
    margin: 0; padding: 0;
    display: flex; justify-content: center; align-items: flex-start;
    min-height: 100vh;
}
.container {
    background: #fff; margin-top: 40px; width: 90%; max-width: 700px;
    padding: 30px 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    animation: fadeIn 0.4s ease-in;
}
@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
h1 { text-align: center; color: #333; margin-bottom: 10px; }
.subtitle { text-align: center; color: #777; margin-bottom: 25px; }
.message { text-align: center; padding: 10px; margin-bottom: 20px; border-radius: 5px; font-weight: 500; }
.message.success { background: #d4edda; color: #155724; }
.message.error { background: #f8d7da; color: #721c24; }
form { display: flex; flex-wrap: wrap; gap: 15px; }
.form-group { flex: 1 1 48%; display: flex; flex-direction: column; }
.form-group label { font-weight: 600; color: #555; margin-bottom: 5px; }
.form-group input, .form-group select {
    padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; transition: all 0.2s;
}
.form-group input:focus, .form-group select:focus {
    border-color: #007bff; outline: none; box-shadow: 0 0 4px rgba(0,123,255,0.3);
}
button { background: #007bff; color: white; border: none; padding: 12px 18px; font-size: 16px; border-radius: 8px; cursor: pointer; transition: 0.2s; margin-top: 10px; }
button:hover { background: #0056b3; }
.actions { display: flex; justify-content: space-between; margin-top: 25px; }
.back-btn { background: #6c757d; }
.back-btn:hover { background: #5a6268; }
@media (max-width: 768px) { .form-group { flex: 1 1 100%; } .actions { flex-direction: column; gap: 10px; } }
</style>
</head>
<body>

<div class="container">
    <h1>Add New Staff</h1>
    <p class="subtitle">Human Resources Management - Omik Family Restaurant (PVT) Ltd</p>

    <?php if ($staffMessage): ?>
        <div class="message <?= str_contains($staffMessage, '✅') ? 'success' : 'error' ?>">
            <?= htmlspecialchars($staffMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validateAddStaffForm()">
        <input type="hidden" name="addStaff" value="1">

        <div class="form-group">
            <label>First Name *</label>
            <input type="text" name="firstName" placeholder="Enter first name">
        </div>

        <div class="form-group">
            <label>Last Name *</label>
            <input type="text" name="lastName" placeholder="Enter last name">
        </div>

        <div class="form-group">
            <label>Account Number</label>
            <input type="number" name="accountNumber" placeholder="Enter account number">
        </div>

        <div class="form-group">
            <label>Job Role *</label>
            <select name="jobRole">
                <option value="">-- Select Job Role --</option>
                <option value="Owner">Owner</option>
                <option value="General Manager">General Manager</option>
                <option value="HR Manager">HR Manager</option>
                <option value="Accountant">Accountant</option>
                <option value="Restaurant Manager">Restaurant Manager</option>
                <option value="Inventory Manager">Inventory Manager</option>
                <option value="Stock Keeper">Stock Keeper</option>
                <option value="Transport Manager">Transport Manager</option>
                <option value="Chef">Chef</option>
                <option value="Cashier">Cashier</option>
                <option value="Receptionist">Receptionist</option>
                <option value="Room Keeper">Room Keeper</option>
                <option value="Supervisor">Supervisor</option>
                <option value="Delivery Boy">Delivery Boy</option>
            </select>
        </div>

        <div class="form-group">
            <label>OT Rate</label>
            <input type="number" step="0.01" name="otRate" placeholder="Enter OT rate">
        </div>

        <div class="form-group">
            <label>Basic Salary</label>
            <input type="number" step="0.01" name="basicSalary" placeholder="Enter basic salary">
        </div>

        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" placeholder="Enter email">
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
        </div>

        <div class="form-group" style="flex:1 1 100%;">
            <label>Password *</label>
            <input type="password" name="password" placeholder="Enter password">
        </div>

        <div class="actions">
            <button type="button" class="back-btn" onclick="window.location.href='HRManagerDashboard.php'">⬅ Back to Dashboard</button>
            <button type="submit">Add Staff</button>
        </div>
    </form>
</div>

<script>
function validateAddStaffForm() {
    const required = ['firstName','lastName','jobRole','email','password'];
    for (let field of required) {
        const el = document.querySelector(`[name="${field}"]`);
        if (el.value.trim() === '') {
            alert('Please fill in all required fields.');
            el.focus();
            return false;
        }
    }
    return true;
}
</script>

</body>
</html>
