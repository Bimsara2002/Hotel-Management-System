<?php
session_start();
include 'config.php';

// ✅ Restrict access to Inventory Manager or Accountant
if (!isset($_SESSION['staffRole']) || !in_array(strtolower(trim($_SESSION['staffRole'])), ['inventory manager', 'accountant'])) {
    header("Location: login.html");
    exit;
}

$success = '';
$error = '';

// ===== FETCH SUPPLIERS =====
$suppliers = $conn->query("SELECT supplier_id, supplier_name FROM Suppliers ORDER BY supplier_name");

// ===== SAVE PAYMENT =====
if (isset($_POST['add_payment'])) {
    $supplierId = intval($_POST['supplier_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $paymentDate = $_POST['payment_date'] ?? '';
    $paymentMethod = $_POST['payment_method'] ?? '';
    $note = $_POST['note'] ?? '';

    // ✅ Fetch supplier name based on supplier_id
    $supplierName = '';
    if ($supplierId > 0) {
        $stmtName = $conn->prepare("SELECT supplier_name FROM Suppliers WHERE supplier_id = ?");
        $stmtName->bind_param("i", $supplierId);
        $stmtName->execute();
        $stmtName->bind_result($supplierName);
        $stmtName->fetch();
        $stmtName->close();
    }

    // ✅ Validation
    if ($supplierId > 0 && $amount > 0 && !empty($paymentDate) && !empty($paymentMethod) && !empty($supplierName)) {
        $stmt = $conn->prepare("INSERT INTO SupplierPayment (supplier_id, SupplierName, Amount, PaymentDate, PaymentMethod, PaymentStatus, Status) VALUES (?, ?, ?, ?, ?, 'pending', 'Pending')");
        $stmt->bind_param("isdss", $supplierId, $supplierName, $amount, $paymentDate, $paymentMethod);

        if ($stmt->execute()) {
            $success = "✅ Payment recorded successfully!";
        } else {
            $error = "⚠ Error saving payment: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "⚠ Please fill in all required fields correctly.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Supplier Payment</title>
<style>
body { font-family: "Segoe UI", sans-serif; background:#fdfdfd; margin:30px; }
.container { max-width:700px; margin:auto; background:white; padding:25px 40px; border-radius:10px; box-shadow:0 4px 15px rgba(0,0,0,0.1); }
h1 { text-align:center; color:#2c3e50; margin-bottom:20px; }
form { display:flex; flex-direction:column; gap:15px; }
label { font-weight:bold; }
input, select, textarea { padding:8px; border-radius:5px; border:1px solid #ccc; width:100%; }
button { background:#27ae60; color:white; border:none; padding:10px; border-radius:5px; font-weight:bold; cursor:pointer; }
button:hover { background:#219150; }
.success { color:green; text-align:center; font-weight:bold; }
.error { color:red; text-align:center; font-weight:bold; }
.back-btn { background:#2980b9; margin-top:10px; }
.back-btn:hover { background:#1c6694; }
</style>
</head>
<body>
<div class="container">
    <h1>💰 Add Supplier Payment</h1>

    <?php if ($success) echo "<p class='success'>$success</p>"; ?>
    <?php if ($error) echo "<p class='error'>$error</p>"; ?>

    <form method="POST">
        <label for="supplier_id">Supplier:</label>
        <select name="supplier_id" id="supplier_id" required>
            <option value="">-- Select Supplier --</option>
            <?php while ($row = $suppliers->fetch_assoc()): ?>
                <option value="<?= $row['supplier_id'] ?>"><?= htmlspecialchars($row['supplier_name']) ?></option>
            <?php endwhile; ?>
        </select>

        <label for="amount">Amount (Rs.):</label>
        <input type="number" name="amount" id="amount" step="0.01" min="0" required>

        <label for="payment_date">Payment Date:</label>
        <input type="date" name="payment_date" id="payment_date" required value="<?= date('Y-m-d') ?>">

        <label for="payment_method">Payment Method:</label>
        <select name="payment_method" id="payment_method" required>
            <option value="">-- Select Method --</option>
            <option value="Cash">Cash</option>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Cheque">Cheque</option>
        </select>

        <label for="note">Note (Optional):</label>
        <textarea name="note" id="note" rows="3" placeholder="Any additional notes"></textarea>

        <button type="submit" name="add_payment">💾 Add Payment</button>
    </form>

    <button class="back-btn" onclick="window.location.href='inventoryManagerDashboard.php'">⬅ Back to Dashboard</button>
</div>
</body>
</html>
