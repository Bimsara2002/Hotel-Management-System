<?php
session_start();
include 'config.php';

// ✅ Allow only Transport Manager
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'transport manager') {
    header("Location: login.html");
    exit;
}

$success = '';
$error = '';

// ===== Add Transport Expense =====
if (isset($_POST['add_expense'])) {
    $expense_date = $_POST['expense_date'];
    $description = trim($_POST['description']);
    $amount = floatval($_POST['amount']);

    // Validation
    if (empty($expense_date)) {
        $error = "Expense date is required.";
    } elseif ($amount <= 0) {
        $error = "Amount must be greater than 0.";
    } else {
        $stmt = $conn->prepare("INSERT INTO TransportExpenses (expense_date, description, amount) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $expense_date, $description, $amount);

        if ($stmt->execute()) {
            $success = "✅ Expense recorded successfully!";
        } else {
            $error = "❌ Error saving expense.";
        }

        $stmt->close();
    }
}

// ===== Fetch all expenses =====
$result = $conn->query("SELECT * FROM TransportExpenses ORDER BY expense_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transport Expenses</title>
<style>
body { font-family: Arial, sans-serif; padding:20px; }
.container { max-width:800px; margin:auto; }
h1 { text-align:center; }
form input, form textarea, form button { width:100%; padding:8px; margin:5px 0; border-radius:5px; border:1px solid #ccc; }
form button { background:#3498db; color:#fff; border:none; cursor:pointer; }
.success { color:green; }
.error { color:red; }
table { width:100%; border-collapse: collapse; margin-top:20px; }
table, th, td { border:1px solid #ccc; }
th, td { padding:8px; text-align:left; }
</style>
</head>
<body>
<div class="container">
    <h1>💰 Transport Expenses</h1>
    <a href="TransportManagerDashboard.php">⬅ Back to Dashboard</a>

    <?php if ($success) echo "<p class='success'>$success</p>"; ?>
    <?php if ($error) echo "<p class='error'>$error</p>"; ?>

    <!-- Add Expense Form -->
    <h2>Add New Expense</h2>
    <form method="POST">
        <label for="expense_date">Date</label>
        <input type="date" name="expense_date" id="expense_date" required>

        <label for="description">Description</label>
        <textarea name="description" id="description" rows="3" placeholder="Fuel, Toll, Repair..." required></textarea>

        <label for="amount">Amount (LKR)</label>
        <input type="number" name="amount" id="amount" step="0.01" placeholder="e.g., 1500.50" required>

        <button type="submit" name="add_expense">Add Expense</button>
    </form>

    <!-- View Expenses Table -->
    <h2>Existing Expenses</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Description</th>
                <th>Amount (LKR)</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['expense_id'] ?></td>
                    <td><?= htmlspecialchars($row['expense_date']) ?></td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td><?= number_format($row['amount'], 2) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4" style="text-align:center;">No expenses recorded yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
