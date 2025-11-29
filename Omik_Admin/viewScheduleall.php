<?php
session_start();
include 'config.php';

// ✅ Ensure staff is logged in
if(!isset($_SESSION['staffId'])){
    header("Location: login.html");
    exit;
}

$staffId = $_SESSION['staffId']; // logged-in staff
$dateFilter = $_POST['date'] ?? '';

// Sanitize date input
$where = '';
if($dateFilter){
    $dateFilter = $conn->real_escape_string($dateFilter);
    $where = "AND schedule_date = '$dateFilter'";
}

// Fetch staff schedule
$scheduleQuery = $conn->query("
    SELECT shift_type, schedule_date, notes 
    FROM StaffSchedule 
    WHERE staff_id = $staffId $where
    ORDER BY schedule_date ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Schedule</title>
<style>
body { font-family: Arial, sans-serif; background:#f4f4f4; padding:20px; }
.container { max-width: 700px; margin:0 auto; background:#fff; padding:25px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1);}
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
form input { width:100%; padding:8px; margin-top:5px; border-radius:4px; border:1px solid #ccc; }
form button { margin-top:10px; padding:10px; width:100%; background:#3498db; border:none; color:#fff; border-radius:4px; cursor:pointer; }
form button:hover { background:#217dbb; }
table { width:100%; border-collapse: collapse; margin-top:20px; }
table th, table td { border:1px solid #ccc; padding:8px; text-align:center; }
table th { background:#3498db; color:#fff; }
</style>
</head>
<body>
<div class="container">
    <button class="back-btn" onclick="window.history.back()">⬅ Back</button>
    <h2>My Schedule</h2>

    <form method="POST">
        <label>Filter by Date:</label>
        <input type="date" name="date" value="<?= htmlspecialchars($dateFilter) ?>">
        <button type="submit">Filter</button>
    </form>

    <?php if($scheduleQuery->num_rows > 0): ?>
    <table>
        <tr>
            <th>Date</th>
            <th>Shift</th>
            <th>Notes</th>
        </tr>
        <?php while($row = $scheduleQuery->fetch_assoc()): ?>
        <tr>
            <td><?= $row['schedule_date'] ?></td>
            <td><?= $row['shift_type'] ?></td>
            <td><?= htmlspecialchars($row['notes']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <p style="text-align:center; margin-top:20px;">No schedule records found.</p>
    <?php endif; ?>
</div>
</body>
</html>
