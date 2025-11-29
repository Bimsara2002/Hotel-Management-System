<?php
session_start();
include 'config.php';

// ✅ Only logged-in suppliers
if (!isset($_SESSION['supplier_id'])) {
    header("Location: supplier_login.php");
    exit;
}

// ✅ Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $id = $_SESSION['supplier_id'];
    $name = trim($_POST['supplier_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    if ($password) {
        $stmt = $conn->prepare("UPDATE Suppliers SET supplier_name=?, email=?, phone=?, address=?, password=? WHERE supplier_id=?");
        $stmt->bind_param("sssssi", $name, $email, $phone, $address, $password, $id);
    } else {
        $stmt = $conn->prepare("UPDATE Suppliers SET supplier_name=?, email=?, phone=?, address=? WHERE supplier_id=?");
        $stmt->bind_param("ssssi", $name, $email, $phone, $address, $id);
    }

    if ($stmt->execute()) {
        $_SESSION['supplier_name'] = $name;
        $success_msg = "✅ Profile updated successfully!";
    } else {
        $error_msg = "❌ Failed to update profile.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Supplier Dashboard</title>
<link rel="stylesheet" href="supplier.css">
</head>
<body>

<nav>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['supplier_name']); ?> 👋</h2>
    <div>
        <a href="supplier_dashboard.php">🏠 Dashboard</a>
        <a href="supplier_orders.php">📦 Orders</a>
        <a href="supplier_payments.php">💵 Payments</a>
        <a href="#" id="openProfile">⚙️ Profile</a>
        <a href="supplier_logout.php" class="logout">🚪 Logout</a>
    </div>
</nav>

<div class="dashboard">
    <h1>Supplier Dashboard</h1>
    <?php if (isset($success_msg)) echo "<p class='success'>$success_msg</p>"; ?>
    <?php if (isset($error_msg)) echo "<p class='error'>$error_msg</p>"; ?>

   <div class="card-container">
    <div class="card" onclick="window.location.href='supplier_orders.php'">
        <h3>📦 Orders</h3>
        <p>View and manage your supply orders</p>
    </div>
    <div class="card" onclick="window.location.href='supplier_payments.php'">
        <h3>💵 Payments</h3>
        <p>Check payment status for your supplies</p>
    </div>
    <div class="card" id="openProfileCard">
        <h3>⚙️ Profile</h3>
        <p>Update your contact information</p>
    </div>
</div>

</div>

<!-- ===== Profile Update Modal ===== -->
<div class="modal" id="profileModal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <h2>Update Profile</h2>
        <form method="POST" class="profile-form">
            <label>Name:</label>
            <input type="text" name="supplier_name" value="<?php echo htmlspecialchars($_SESSION['supplier_name']); ?>" required>

            <label>Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" required>

            <label>Phone:</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>">

            <label>Address:</label>
            <textarea name="address" rows="3"><?php echo htmlspecialchars($_SESSION['address'] ?? ''); ?></textarea>

            <label>New Password (optional):</label>
            <input type="password" name="password" placeholder="Enter new password">

            <button type="submit" name="update_profile">💾 Save Changes</button>
        </form>
    </div>
</div>

<script>
// ===== Modal Script =====
const modal = document.getElementById('profileModal');
const openBtn = document.getElementById('openProfile');
const openCard = document.getElementById('openProfileCard');
const closeBtn = document.getElementById('closeModal');

openBtn.onclick = () => modal.style.display = 'flex';
openCard.onclick = () => modal.style.display = 'flex';
closeBtn.onclick = () => modal.style.display = 'none';
window.onclick = (e) => { if (e.target === modal) modal.style.display = 'none'; };
</script>

</body>
</html>
