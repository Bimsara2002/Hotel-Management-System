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

// ✅ Add Vehicle
if (isset($_POST['add_vehicle'])) {
    $vehicle_number = trim($_POST['vehicle_number']);
    $status = trim($_POST['status']);
    $cost_1KM = floatval($_POST['cost_1KM']);

    if (!empty($vehicle_number) && !empty($status)) {
        $stmt = $conn->prepare("INSERT INTO Vehicle (vehicle_number, status, cost_1KM) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $vehicle_number, $status, $cost_1KM);
        if ($stmt->execute()) {
            $success = "✅ Vehicle added successfully!";
        } else {
            $error = "❌ Failed to add vehicle: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "⚠ Please fill all fields.";
    }
}

// ✅ Update Vehicle Cost per KM
if (isset($_POST['update_cost'])) {
    $vehicle_id = intval($_POST['vehicle_id']);
    $cost_1KM = floatval($_POST['cost_1KM']);

    $stmt = $conn->prepare("UPDATE Vehicle SET cost_1KM=? WHERE vehicle_id=?");
    $stmt->bind_param("di", $cost_1KM, $vehicle_id);
    if ($stmt->execute()) {
        $success = "✅ Cost updated successfully!";
    } else {
        $error = "❌ Failed to update cost.";
    }
    $stmt->close();
}

// ✅ Delete Vehicle and related maintenance
if (isset($_GET['delete'])) {
    $vehicle_id = intval($_GET['delete']);

    // Delete related maintenance records first
    $stmt = $conn->prepare("DELETE FROM VehicleMaintenance WHERE vehicle_id = ?");
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $stmt->close();

    // Then delete the vehicle
    $stmt = $conn->prepare("DELETE FROM Vehicle WHERE vehicle_id = ?");
    $stmt->bind_param("i", $vehicle_id);
    if ($stmt->execute()) {
        $success = "🚗 Vehicle and related maintenance deleted successfully!";
    } else {
        $error = "❌ Failed to delete vehicle: " . $stmt->error;
    }
    $stmt->close();
}

// ✅ Fetch all vehicles
$result = $conn->query("SELECT * FROM Vehicle ORDER BY vehicle_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Vehicles</title>
<style>
body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; margin:0; padding:0; }
.container { width: 90%; max-width: 900px; margin: 40px auto; background:#fff; padding:20px 30px; border-radius:10px; box-shadow:0 4px 15px rgba(0,0,0,0.1);}
h1, h2 { text-align:center; color:#333; }
.success { background:#d4edda; color:#155724; padding:10px; border-radius:5px; margin-bottom:15px;}
.error { background:#f8d7da; color:#721c24; padding:10px; border-radius:5px; margin-bottom:15px;}
.badge { padding:5px 8px; border-radius:5px; color:#fff; display:inline-block;}
.status-available { background:#2ecc71; }
.status-trip { background:#f39c12; }
.status-maintenance { background:#e74c3c; }
input[type=number] { width:80px; padding:5px; border-radius:5px; border:1px solid #ccc; }
button { padding:5px 10px; border:none; border-radius:5px; cursor:pointer; background:#3498db; color:#fff; }
.delete-btn { color:#e74c3c; text-decoration:none; }
.back-btn { display:inline-block; margin-bottom:15px; padding:5px 10px; background:#6c757d; color:#fff; border-radius:5px; text-decoration:none;}
</style>
</head>
<body>

<div class="container">
    <h1>🚚 Manage Vehicles</h1>
    <a href="TransportManagerDashboard.php" class="back-btn">⬅ Back to Dashboard</a>

    <?php if ($success) echo "<p class='success'>$success</p>"; ?>
    <?php if ($error) echo "<p class='error'>$error</p>"; ?>

    <!-- Add Vehicle Form -->
    <div class="form-container">
        <h2>Add New Vehicle</h2>
        <form method="POST">
            <label>Vehicle Number:</label>
            <input type="text" name="vehicle_number" placeholder="e.g. ABC-1234" required>

            <label>Status:</label>
            <select name="status" required>
                <option value="">Select Status</option>
                <option value="Available">Available</option>
                <option value="On Trip">On Trip</option>
                <option value="Under Maintenance">Under Maintenance</option>
            </select>

            <label>Cost per 1 KM:</label>
            <input type="number" step="0.01" name="cost_1KM" placeholder="e.g. 50.00" required>

            <button type="submit" name="add_vehicle">Add Vehicle</button>
        </form>
    </div>

    <!-- View Vehicles -->
    <h2>Existing Vehicles</h2>
    <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Vehicle Number</th>
                <th>Status</th>
                <th>Cost per 1 KM</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['vehicle_id'] ?></td>
                    <td><?= htmlspecialchars($row['vehicle_number']) ?></td>
                    <td>
                        <span class="badge 
                            <?= strtolower($row['status'])=='available'?'status-available':'' ?>
                            <?= strtolower($row['status'])=='on trip'?'status-trip':'' ?>
                            <?= strtolower($row['status'])=='under maintenance'?'status-maintenance':'' ?>">
                            <?= htmlspecialchars($row['status']) ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="display:flex; gap:5px;">
                            <input type="hidden" name="vehicle_id" value="<?= $row['vehicle_id'] ?>">
                            <input type="number" step="0.01" name="cost_1KM" value="<?= $row['cost_1KM'] ?>" required>
                            <button type="submit" name="update_cost">💾 Update</button>
                        </form>
                    </td>
                    <td>
                        <a href="?delete=<?= $row['vehicle_id'] ?>" class="delete-btn" onclick="return confirm('Are you sure to delete this vehicle?')">🗑 Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center;">No vehicles found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
