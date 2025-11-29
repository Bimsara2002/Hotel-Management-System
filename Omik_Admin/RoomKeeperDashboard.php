<?php
session_start();
include 'config.php';

// ✅ Allow only Room Keeper
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'room keeper') {
    header("Location: login.html");
    exit;
}

$statusMessage = '';
$noteMessage = '';

// ===== Update Request Status =====
if (isset($_POST['update_status'])) {
    $requestId = intval($_POST['request_id']);
    $newStatus = $_POST['status'];

    $stmt = $conn->prepare("UPDATE RoomServiceRequests SET status=? WHERE requestId=?");
    $stmt->bind_param("si", $newStatus, $requestId);

    if ($stmt->execute()) {
        $statusMessage = "Request status updated successfully!";
    } else {
        $statusMessage = "Failed to update status.";
    }
}

// ===== Add Service Note =====
if (isset($_POST['add_note'])) {
    $requestId = intval($_POST['request_id']);
    $serviceNote = $_POST['service_note'];

    $stmt = $conn->prepare("UPDATE RoomServiceRequests SET serviceNote=? WHERE requestId=?");
    $stmt->bind_param("si", $serviceNote, $requestId);

    if ($stmt->execute()) {
        $noteMessage = "Service note added successfully!";
    } else {
        $noteMessage = "Failed to add service note.";
    }
}

// ===== Fetch all room service requests =====
$requests = $conn->query("SELECT r.*, c.Name AS customerName 
                          FROM RoomServiceRequests r 
                          JOIN Customer c ON r.customerId = c.CustomerId 
                          ORDER BY r.requestDate DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Room Keeper Dashboard</title>
<link rel="stylesheet" href="room_keeper_style.css">
</head>
<body>
<div class="dashboard-container">
    <nav class="sidebar">
        <h2>Room Keeper Dashboard</h2>
        <ul>
            <li onclick="openPage('viewRequests')">🛏 View Requests</li>
        <li onclick="openPage('updateStatus')">✅ Update Status</li>
        <li onclick="openPage('addNote')">📝 Add Service Note</li>
        <!-- Add buttons for existing pages -->
        <li><a href="addAttendance.php">📋 Add Attendance</a></li>
        <li><a href="requestLeave.php">🛑 Request Leave</a></li>
        <li><a href="viewScheduleall.php">📅 View Schedule</a></li>
        <li><a href="logout.php">🚪 Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <!-- View Requests -->
        <div id="viewRequests" class="page">
            <h3>Room Service Requests</h3>
            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Room Number</th>
                        <th>Customer</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th>Service Note</th>
                        <th>Request Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $requests->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['requestId']; ?></td>
                        <td><?php echo $row['roomNumber']; ?></td>
                        <td><?php echo $row['customerName']; ?></td>
                        <td><?php echo $row['requestDetails']; ?></td>
                        <td><?php echo $row['status']; ?></td>
                        <td><?php echo $row['serviceNote']; ?></td>
                        <td><?php echo $row['requestDate']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Update Request Status -->
        <div id="updateStatus" class="page" style="display:none">
            <h3>Update Request Status</h3>
            <?php if($statusMessage) echo "<p class='message'>$statusMessage</p>"; ?>
            <form method="POST">
                <label>Select Request:</label>
                <select name="request_id" required>
                    <option value="">-- Select Request --</option>
                    <?php
                    $requestsResult = $conn->query("SELECT requestId, roomNumber, status FROM RoomServiceRequests ORDER BY requestDate DESC");
                    while($req = $requestsResult->fetch_assoc()) {
                        echo "<option value='".$req['requestId']."'>Request #".$req['requestId']." (Room ".$req['roomNumber'].", ".$req['status'].")</option>";
                    }
                    ?>
                </select>
                <label>Status:</label>
                <select name="status" required>
                    <option value="Pending">Pending</option>
                    <option value="Completed">Completed</option>
                </select>
                <button type="submit" name="update_status">Update Status</button>
            </form>
        </div>

        <!-- Add Service Note -->
        <div id="addNote" class="page" style="display:none">
            <h3>Add Service Note</h3>
            <?php if($noteMessage) echo "<p class='message'>$noteMessage</p>"; ?>
            <form method="POST">
                <label>Select Request:</label>
                <select name="request_id" required>
                    <option value="">-- Select Request --</option>
                    <?php
                    $requestsResult = $conn->query("SELECT requestId, roomNumber FROM RoomServiceRequests ORDER BY requestDate DESC");
                    while($req = $requestsResult->fetch_assoc()) {
                        echo "<option value='".$req['requestId']."'>Request #".$req['requestId']." (Room ".$req['roomNumber'].")</option>";
                    }
                    ?>
                </select>
                <label>Service Note:</label>
                <textarea name="service_note" required></textarea>
                <button type="submit" name="add_note">Add Note</button>
            </form>
        </div>
    </div>
</div>

<script>
function openPage(pageId) {
    const pages = document.querySelectorAll('.page');
    pages.forEach(p => p.style.display = 'none');
    document.getElementById(pageId).style.display = 'block';
}
</script>
</body>
</html>
