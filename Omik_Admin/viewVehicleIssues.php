<?php
session_start();
include 'config.php';

// Optional: Restrict access to specific roles
if (!isset($_SESSION['staffRole'])) {
    header("Location: login.html");
    exit;
}

// ===== Handle Add Vehicle Issue =====
if (isset($_POST['add_issue'])) {
    $vehicle_id = intval($_POST['vehicle_id']);
    $description = trim($_POST['description']);
    $status = 'Pending'; // default status

    $stmt = $conn->prepare("INSERT INTO VehicleIssue (vehicle_id, status, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $vehicle_id, $status, $description);

    if ($stmt->execute()) {
        $success = "New issue added successfully!";
    } else {
        $error = "Error adding issue.";
    }

    $stmt->close();
}

// ===== Handle Update Issue Status (excluding Resolved) =====
if (isset($_POST['update_status'])) {
    $issue_id = intval($_POST['issue_id']);
    $new_status = $_POST['status'];

    if ($new_status != 'Resolved') {
        $stmt = $conn->prepare("UPDATE VehicleIssue SET status=? WHERE issue_id=?");
        $stmt->bind_param("si", $new_status, $issue_id);

        if ($stmt->execute()) {
            $success = "Issue status updated successfully!";
        } else {
            $error = "Error updating status.";
        }

        $stmt->close();
    }
}

// ===== Handle Add Maintenance & Resolve Issue =====
if (isset($_POST['add_maintenance'])) {
    $issue_id = intval($_POST['issue_id']);
    $vehicle_id = intval($_POST['vehicle_id']);
    $cost = floatval($_POST['cost']);
    $description = trim($_POST['description']);

    // Validate and format date
    if (empty($_POST['maintenance_date'])) {
        $error = "Maintenance date is required.";
    } else {
        $dateObj = date_create($_POST['maintenance_date']);
        if ($dateObj === false) {
            $error = "Invalid maintenance date format.";
        } else {
            $maintenance_date = date_format($dateObj, "Y-m-d"); // YYYY-MM-DD
        }
    }

    if (empty($error)) {
        $stmt = $conn->prepare("INSERT INTO VehicleMaintenance (vehicle_id, maintenance_date, cost, description) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isds", $vehicle_id, $maintenance_date, $cost, $description);

        if ($stmt->execute()) {
            $stmt2 = $conn->prepare("UPDATE VehicleIssue SET status='Resolved' WHERE issue_id=?");
            $stmt2->bind_param("i", $issue_id);
            $stmt2->execute();
            $stmt2->close();

            $success = "Maintenance recorded and issue resolved!";
        } else {
            $error = "Error saving maintenance details.";
        }
        $stmt->close();
    }
}

// ===== Fetch Vehicles =====
$vehicles = $conn->query("SELECT * FROM Vehicle ORDER BY vehicle_number ASC");

// ===== Fetch Vehicle Issues =====
$issues = $conn->query("
    SELECT vi.issue_id, vi.status, vi.description, v.vehicle_number, v.vehicle_id
    FROM VehicleIssue vi
    LEFT JOIN Vehicle v ON vi.vehicle_id = v.vehicle_id
    ORDER BY vi.issue_id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vehicle Issues</title>
<link rel="stylesheet" href="viewDeliveryOrders.css"> <!-- reuse previous CSS -->
<style>
/* Modal styling */
#maintenanceModal {
    display:none;
    position:fixed;
    top:0; left:0; width:100%; height:100%;
    background:rgba(0,0,0,0.6);
    align-items:center; justify-content:center;
    z-index:1000;
}
#maintenanceModal .modal-content {
    background:#fff;
    padding:20px;
    border-radius:10px;
    width:400px;
    position:relative;
}
#maintenanceModal label { display:block; margin:10px 0 5px; }
#maintenanceModal input, #maintenanceModal textarea { width:100%; padding:8px; border-radius:5px; border:1px solid #ccc; }
#maintenanceModal button { margin-top:10px; padding:10px; border:none; border-radius:5px; cursor:pointer; background:#3498db; color:#fff; }
#maintenanceModal button[type="button"] { background:#e74c3c; }
</style>
</head>
<body>
<div class="container">
    <a href="TransportManagerDashboard.php" class="back-btn">⬅ Back to Dashboard</a>
    <h1>🚗 Vehicle Issues</h1>

    <?php if (!empty($success)) echo "<p class='success'>$success</p>"; ?>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

    <!-- ===== Vehicle Issues Table ===== -->
    <h2>Existing Vehicle Issues</h2>
    <table>
        <thead>
            <tr>
                <th>Issue ID</th>
                <th>Vehicle</th>
                <th>Description</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($issues->num_rows > 0): ?>
                <?php while ($issue = $issues->fetch_assoc()): ?>
                    <tr>
                        <td><?= $issue['issue_id'] ?></td>
                        <td><?= htmlspecialchars($issue['vehicle_number']) ?></td>
                        <td><?= htmlspecialchars($issue['description']) ?></td>
                        <td>
                            <span class="badge 
                                <?= $issue['status'] == 'Pending' ? 'status-pending' : '' ?>
                                <?= $issue['status'] == 'In Progress' ? 'status-assigned' : '' ?>
                                <?= $issue['status'] == 'Resolved' ? 'status-delivered' : '' ?>
                                <?= $issue['status'] == 'Cancelled' ? 'status-cancelled' : '' ?>
                            "><?= htmlspecialchars($issue['status']) ?></span>
                        </td>
                        <td>
                            <form method="POST" style="display:flex; gap:5px;">
                                <input type="hidden" name="issue_id" value="<?= $issue['issue_id'] ?>">
                                <select name="status" onchange="
                                    if(this.value=='Resolved') {
                                        event.preventDefault(); 
                                        openMaintenanceModal(<?= $issue['issue_id'] ?>, <?= $issue['vehicle_id'] ?>); 
                                        this.value=''; 
                                    } else { 
                                        this.form.submit(); 
                                    }" required>
                                    <option value="">Update Status</option>
                                    <option value="Pending">Pending</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Resolved">Resolved</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                                <button type="submit" name="update_status" class="updateBtn">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-center">No vehicle issues found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ===== Maintenance Modal ===== -->
<div id="maintenanceModal">
    <div class="modal-content">
        <h3>Enter Maintenance Details</h3>
        <form method="POST" id="maintenanceForm">
            <input type="hidden" name="issue_id" id="modal_issue_id">
            <input type="hidden" name="vehicle_id" id="modal_vehicle_id">
            
            <label for="maintenance_date">Date</label>
            <input type="date" name="maintenance_date" id="maintenance_date" required>
            
            <label for="cost">Cost</label>
            <input type="number" step="0.01" name="cost" id="cost" required>
            
            <label for="description">Description</label>
            <textarea name="description" id="description" rows="3" required></textarea>
            
            <button type="submit" name="add_maintenance">Save & Resolve</button>
            <button type="button" onclick="closeModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
function openMaintenanceModal(issueId, vehicleId) {
    document.getElementById('modal_issue_id').value = issueId;
    document.getElementById('modal_vehicle_id').value = vehicleId;
    document.getElementById('maintenanceModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('maintenanceModal').style.display = 'none';
}
</script>
</body>
</html>
