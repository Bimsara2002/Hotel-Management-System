<?php
session_start();
include 'config.php';

// ✅ Allow only Receptionist or Restaurant Manager
if (!isset($_SESSION['staffRole']) || !in_array(strtolower(trim($_SESSION['staffRole'])), ['receptionist','restaurant manager'])) {
    header("Location: login.html");
    exit;
}

$success = '';
$error = '';

// ===== Add New Table =====
if (isset($_POST['add_table'])) {
    $table_number = $_POST['table_number'];
    $seats = intval($_POST['seats']);
    $availability = $_POST['availability'];

    $stmt = $conn->prepare("INSERT INTO tables (table_number, seats, availability) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $table_number, $seats, $availability);

    if ($stmt->execute()) {
        $success = "✅ Table added successfully!";
    } else {
        $error = "❌ Failed to add table.";
    }
    $stmt->close();
}

// ===== Fetch Existing Tables =====
$tables = $conn->query("SELECT * FROM tables ORDER BY table_number ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Tables</title>
<link rel="stylesheet" href="style.css">
<style>
.dashboard-container { max-width: 1000px; margin: auto; padding: 20px; font-family: Arial, sans-serif; }
header { text-align: center; margin-bottom: 20px; }
h1 { font-size: 28px; color: #333; }
.alert { padding: 10px; margin-bottom: 15px; border-radius: 5px; }
.success { background-color: #d4edda; color: #155724; }
.error { background-color: #f8d7da; color: #721c24; }
form { margin-bottom: 30px; display: grid; gap: 15px; }
form label { font-weight: bold; }
form input, form select, form button { padding: 10px; font-size: 16px; border-radius: 5px; border: 1px solid #ccc; }
form button { background-color: #007BFF; color: #fff; border: none; cursor: pointer; }
form button:hover { background-color: #0056b3; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
table, th, td { border: 1px solid #ddd; }
th, td { padding: 12px; text-align: center; }
th { background-color: #007BFF; color: white; }
.badge { padding: 5px 10px; border-radius: 5px; color: white; }
.reserved { background-color: #ffc107; }
.available { background-color: #28a745; }
.occupied { background-color: #dc3545; }
</style>
</head>
<body>

<div class="dashboard-container">
    <header>
        <h1>🍽 Manage Tables</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION['staffName'] ?? 'Receptionist') ?>!</p>
    </header>

    <?php if ($success): ?><div class="alert success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= $error ?></div><?php endif; ?>

    <!-- Add Table Form -->
    <form method="POST">
        <label>Table Number:</label>
        <input type="text" name="table_number" placeholder="e.g. T1, T2" required>

        <label>Number of Seats:</label>
        <input type="number" name="seats" min="1" value="2" required>

        <label>Availability:</label>
        <select name="availability" required>
            <option value="Available">Available</option>
            <option value="Occupied">Occupied</option>
        </select>

        <button type="submit" name="add_table">Add Table</button>
    </form>

    <!-- Existing Tables -->
    <h2>Existing Tables</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Table Number</th>
                <th>Seats</th>
                <th>Availability</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($tables->num_rows > 0): ?>
                <?php while($row = $tables->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['table_id'] ?></td>
                        <td><?= htmlspecialchars($row['table_number']) ?></td>
                        <td><?= $row['seats'] ?></td>
                        <td>
                            <span class="badge <?= strtolower($row['availability']) ?>">
                                <?= $row['availability'] ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No tables found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
