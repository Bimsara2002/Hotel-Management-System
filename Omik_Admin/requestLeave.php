<?php
session_start();
include 'config.php';

// ✅ Check if staff is logged in
if (!isset($_SESSION['staffId'])) {
    header("Location: login.html");
    exit;
}

$staffId = $_SESSION['staffId'];
$role = strtolower(trim($_SESSION['staffRole']));
$message = "";

// Handle leave submission (for everyone now)
if (isset($_POST['submit_leave'])) {
    $start = $_POST['startDate'];
    $end = $_POST['endDate'];
    $reason = trim($_POST['reason']);
    $type = trim($_POST['type']);

    if ($start && $end && $reason && $type) {
        $stmt = $conn->prepare("INSERT INTO LeaveRequests (StaffId, StartDate, EndDate, Reason, Type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $staffId, $start, $end, $reason, $type);
        if ($stmt->execute()) {
            $message = "✅ Leave request submitted successfully.";
        } else {
            $message = "❌ Error submitting leave request.";
        }
    } else {
        $message = "❌ Please fill in all fields.";
    }
}

// Fetch leave requests (everyone sees their own requests)
$query = "SELECT * FROM LeaveRequests WHERE StaffId=$staffId ORDER BY RequestedDate DESC";
$leaveRequests = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leave Requests</title>
<style>
body { font-family: Arial; background: #f4f6f8; padding: 20px; }
.container { max-width: 900px; margin: 20px auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow:0 4px 15px rgba(0,0,0,0.1);}
h2 { text-align:center; margin-bottom:20px; color:#2c3e50;}
form label { display:block; margin-top:10px; font-weight:bold; }
form input, form textarea { width:100%; padding:8px 10px; margin-top:5px; border-radius:5px; border:1px solid #ccc; }
form textarea { min-height:80px; resize: vertical; }
form button { margin-top:20px; padding:10px; width:100%; background:#3498db; color:#fff; border:none; border-radius:5px; cursor:pointer; }
form button:hover { background:#2980b9; }
.message { margin-top:15px; padding:10px; border-radius:5px; text-align:center; font-weight:bold; }
.success { background:#2ecc71; color:#fff; }
.error { background:#e74c3c; color:#fff; }
.table-leave { width:100%; border-collapse: collapse; margin-top:20px; }
.table-leave th, .table-leave td { border:1px solid #ccc; padding:8px; text-align:center; }
.table-leave th { background:#bdc3c7; }
.back-btn {
    display:inline-block;
    margin-top:10px;
    padding:8px 15px;
    background:#95a5a6;
    color:white;
    border:none;
    border-radius:5px;
    cursor:pointer;
}
.back-btn:hover {
    background:#7f8c8d;
}
</style>
</head>
<body>
<div class="container">
<button class="back-btn" onclick="window.history.back()">⬅ Back</button>
    <h2>📨 Leave Requests</h2>

    <?php if($message): ?>
        <div class="message <?= strpos($message,'✅') !== false ? 'success':'error' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <!-- Show form for all staff (including managers) -->
    <form method="POST">
        <label>Leave Type:</label>
        <input type="text" name="type" placeholder="e.g., Sick Leave, Casual Leave" required>

        <label>Start Date:</label>
        <input type="date" name="startDate" required>

        <label>End Date:</label>
        <input type="date" name="endDate" required>

        <label>Reason:</label>
        <textarea name="reason" placeholder="Explain the reason for leave" required></textarea>

        <button type="submit" name="submit_leave">Submit Leave Request</button>
    </form>

    <!-- Leave Requests Table -->
    <table class="table-leave">
        <tr>
            <th>Leave Type</th><th>Start Date</th><th>End Date</th><th>Status</th><th>Requested On</th>
        </tr>
        <?php while($row = $leaveRequests->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['Type']) ?></td>
            <td><?= htmlspecialchars($row['StartDate']) ?></td>
            <td><?= htmlspecialchars($row['EndDate']) ?></td>
            <td><?= htmlspecialchars($row['Status']) ?></td>
            <td><?= htmlspecialchars($row['RequestedDate']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
