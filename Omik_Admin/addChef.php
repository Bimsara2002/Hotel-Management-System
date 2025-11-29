<?php
include 'config.php';

// Add Chef when button clicked
if (isset($_POST['add_chef'])) {
    $staffId = $_POST['staff_id'];

    // Get staff name from Staff table
    $staffQuery = $conn->prepare("SELECT CONCAT(FirstName, ' ', LastName) AS fullName FROM Staff WHERE StaffId = ?");
    $staffQuery->bind_param("i", $staffId);
    $staffQuery->execute();
    $staffResult = $staffQuery->get_result();
    $staff = $staffResult->fetch_assoc();

    if ($staff) {
        $chefName = $staff['fullName'];
        $status = "Available";

        // Check if chef already exists
        $checkQuery = $conn->prepare("SELECT * FROM Chef WHERE chef_name = ?");
        $checkQuery->bind_param("s", $chefName);
        $checkQuery->execute();
        $checkResult = $checkQuery->get_result();

        if ($checkResult->num_rows > 0) {
            $message = "⚠️ This chef is already added.";
        } else {
            $insertQuery = $conn->prepare("INSERT INTO Chef (chef_name, status) VALUES (?, ?)");
            $insertQuery->bind_param("ss", $chefName, $status);
            if ($insertQuery->execute()) {
                $message = "✅ Chef added successfully!";
            } else {
                $message = "❌ Error adding chef.";
            }
        }
    } else {
        $message = "❌ Invalid Staff selected.";
    }
}

// Fetch all staff who are chefs
$staffList = $conn->query("SELECT StaffId, FirstName, LastName FROM Staff WHERE JobRole = 'Chef'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Chef</title>
    <link rel="stylesheet" href="addChef.css">
</head>
<body>
<div class="container">
    <a href="RestaurantManagerDashboard.php" class="back-btn">⬅ Back to Dashboard</a>
    <h2>Add Chef from Staff</h2>

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
        <button type="submit" name="add_chef">Add Chef</button>
    </form>

    <h3>Current Chefs</h3>
    <table>
        <tr>
            <th>Chef ID</th>
            <th>Chef Name</th>
            <th>Status</th>
        </tr>
        <?php
        $chefResult = $conn->query("SELECT * FROM Chef");
        while ($chef = $chefResult->fetch_assoc()):
        ?>
        <tr>
            <td><?= $chef['chef_id'] ?></td>
            <td><?= $chef['chef_name'] ?></td>
            <td><?= $chef['status'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
