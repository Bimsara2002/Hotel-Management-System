<?php
session_start();
include 'config.php';

// ✅ Allow only Transport Manager
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'transport manager') {
    header("Location: login.html");
    exit;
}

// Handle form submission to add driver
if (isset($_POST['add_driver'])) {
    $staffId = $_POST['staff_id'];

    // Get staff full name
    $staffQuery = $conn->prepare("SELECT CONCAT(FirstName, ' ', LastName) AS fullName FROM Staff WHERE StaffId = ? AND JobRole='Delivery Boy'");
    $staffQuery->bind_param("i", $staffId);
    $staffQuery->execute();
    $staffResult = $staffQuery->get_result();
    $staff = $staffResult->fetch_assoc();

    if ($staff) {
        $driverName = $staff['fullName'];

        // Check if driver already exists
        $checkQuery = $conn->prepare("SELECT * FROM Driver WHERE driver_name = ?");
        $checkQuery->bind_param("s", $driverName);
        $checkQuery->execute();
        $checkResult = $checkQuery->get_result();

        if ($checkResult->num_rows > 0) {
            $message = "⚠️ This driver is already added.";
        } else {
            $insertQuery = $conn->prepare("INSERT INTO Driver (driver_name) VALUES (?)");
            $insertQuery->bind_param("s", $driverName);
            if ($insertQuery->execute()) {
                $message = "✅ Driver added successfully!";
            } else {
                $message = "❌ Error adding driver.";
            }
        }
    } else {
        $message = "❌ Invalid Staff selected.";
    }
}

// Fetch all staff with JobRole = Delivery Boy
$staffList = $conn->query("SELECT StaffId, FirstName, LastName FROM Staff WHERE JobRole='Delivery Boy'");

// Fetch all drivers
$drivers = $conn->query("SELECT * FROM Driver ORDER BY driver_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Driver</title>
<link rel="stylesheet" href="addChef.css">
</head>
<body>
<div class="container">
    <a href="TransportManagerDashboard.php" class="back-btn">⬅ Back to Dashboard</a>
    <h2>Add Driver from Staff</h2>

    <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>

    <form method="POST" class="chef-form">
        <label>Select Staff Member:</label>
        <select name="staff_id" required>
            <option value="">-- Select Staff --</option>
            <?php while ($row = $staffList->fetch_assoc()): ?>
                <option value="<?= $row['StaffId'] ?>">
                    <?= $row['FirstName'] . " " . $row['LastName'] ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit" name="add_driver">Add Driver</button>
    </form>

    <h3>All Drivers</h3>
    <table>
        <tr>
            <th>Driver ID</th>
            <th>Driver Name</th>
        </tr>
        <?php while ($driver = $drivers->fetch_assoc()): ?>
        <tr>
            <td><?= $driver['driver_id'] ?></td>
            <td><?= $driver['driver_name'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
