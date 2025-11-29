<?php
session_start();
include 'config.php';

$staffId = $_SESSION['staffId']; // logged-in staff
$message = "";

if(isset($_POST['submit_attendance'])){
    $status = $_POST['status'];
    $checkIn = $_POST['checkIn'] ?: null;
    $checkOut = $_POST['checkOut'] ?: null;
    $date = date('Y-m-d');

    // Calculate OT hours automatically
    $otHours = 0;
    if($checkIn && $checkOut){
        $in = strtotime($checkIn);
        $out = strtotime($checkOut);
        $workedHours = ($out - $in) / 3600; // convert seconds to hours
        $otHours = max(0, $workedHours - 8); // OT only if >8 hours
    }

    // Check if attendance already exists for today
    $checkStmt = $conn->prepare("SELECT * FROM Attendance WHERE StaffId=? AND Date=?");
    $checkStmt->bind_param("is", $staffId, $date);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if($result->num_rows > 0){
        $message = "⚠ Attendance for today has already been recorded.";
    } else {
        $stmt = $conn->prepare("INSERT INTO Attendance (StaffId, Date, Status, CheckIn, CheckOut, OThours) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssi", $staffId, $date, $status, $checkIn, $checkOut, $otHours);
        $stmt->execute();
        $message = "✅ Attendance recorded for today. OT Hours: $otHours";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance</title>
<style>
body { font-family: Arial, sans-serif; padding:20px; background:#f4f4f4; }
.container { max-width: 600px; margin:0 auto; background:#fff; padding:25px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
.back-btn {
    display:inline-block;
    margin-bottom:20px;
    padding:8px 15px;
    background:#95a5a6;
    color:white;
    border:none;
    border-radius:5px;
    cursor:pointer;
    text-decoration:none;
}
.back-btn:hover { background:#7f8c8d; }
form label { display:block; margin-top:10px; font-weight:bold; }
form input, form select { width:100%; padding:8px; margin-top:5px; border-radius:4px; border:1px solid #ccc; }
form button { margin-top:15px; padding:10px; width:100%; background:#1abc9c; border:none; color:#fff; border-radius:4px; cursor:pointer; }
form button:hover { background:#16a085; }
.message { padding:10px; margin-bottom:10px; border-radius:4px; background:#2ecc71; color:#fff; text-align:center; }
</style>
</head>
<body>
<div class="container">
    <button class="back-btn" onclick="window.history.back()">⬅ Back</button>
    <h2>Record Attendance</h2>
    <?php if($message) echo "<p class='message'>$message</p>"; ?>

    <form method="POST">
        <label>Status:</label>
        <select name="status" required>
            <option value="Present">Present</option>
            <option value="Absent">Absent</option>
            <option value="Late">Late</option>
            <option value="On Leave">On Leave</option>
        </select>

        <label>Check-In Time:</label>
        <input type="time" name="checkIn" required>

        <label>Check-Out Time:</label>
        <input type="time" name="checkOut" required>

        <button type="submit" name="submit_attendance">Submit Attendance</button>
    </form>
</div>
</body>
</html>
