<?php
session_start();
include 'config.php';

// ✅ Allow only General Manager
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'general manager') {
    die("❌ You do not have permission to view this page.");
}

$filterDate = $_POST['filter_date'] ?? '';
$whereClause = $filterDate ? "WHERE ss.schedule_date = '$filterDate'" : "";

// Fetch saved schedules
$savedSchedules = $conn->query("
    SELECT ss.schedule_date, ss.shift_type, ss.notes, s.FirstName, s.LastName, s.JobRole 
    FROM StaffSchedule ss
    JOIN Staff s ON ss.staff_id = s.StaffId
    $whereClause
    ORDER BY ss.schedule_date DESC, s.JobRole, s.FirstName
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Staff Shift Schedule</title>
<style>
body { font-family: "Segoe UI", sans-serif; background:#fdfdfd; margin:30px; }
.container { max-width:1200px; margin:auto; background:white; padding:25px 40px; border-radius:10px; box-shadow:0 4px 15px rgba(0,0,0,0.1); }
h1 { text-align:center; color:#2c3e50; margin-bottom:10px; }
.filter-form { text-align:center; margin-bottom:15px; }
button, input[type=date], .btn { background:#27ae60; color:white; border:none; padding:8px 15px; border-radius:5px; font-weight:bold; cursor:pointer; }
button:hover, .btn:hover { background:#219150; }
.back-btn { background:#2980b9; margin-right:10px; }
.back-btn:hover { background:#1c6694; }
.print-btn { background:#e67e22; }
.print-btn:hover { background:#cf711b; }
table { width:100%; border-collapse:collapse; margin-top:15px; font-size:14px; }
th, td { padding:10px; border:1px solid #ccc; text-align:center; }
th { background:#e67e22; color:#fff; }
tr:nth-child(even) { background:#f9f9f9; }
@media print {
    button, .filter-form, input, select { display:none; }
    body { margin: 0; }
    h1 { color:#000; }
    table { border:1px solid #000; }
    th { background:#ddd; color:#000; }
}
</style>
<script>
function printSchedule() {
    window.print();
}
</script>
</head>
<body>
<div class="container">
    <h1>📅 Staff Shift Schedules</h1>

    <div class="filter-form">
        <form method="POST">
            <label>Filter by Date:</label>
            <input type="date" name="filter_date" value="<?= htmlspecialchars($filterDate) ?>">
            <button type="submit">🔍 Filter</button>
            <button type="button" onclick="window.location.href='viewSchedule.php'">Reset</button>
        </form>
        <button class="print-btn" onclick="printSchedule()">🖨 Print Schedule</button>
        <button class="back-btn" onclick="window.location.href='ManagerDashboard.php'">⬅ Back to Dashboard</button>
    </div>

    <?php if ($savedSchedules->num_rows > 0): ?>
    <table>
        <tr>
            <th>Date</th>
            <th>Staff Name</th>
            <th>Job Role</th>
            <th>Shift</th>
            <th>Note</th>
        </tr>
        <?php while($row = $savedSchedules->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['schedule_date']) ?></td>
            <td><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></td>
            <td><?= htmlspecialchars($row['JobRole']) ?></td>
            <td><?= htmlspecialchars($row['shift_type']) ?></td>
            <td><?= htmlspecialchars($row['notes']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <p style="text-align:center; color:#777;">No saved schedules found.</p>
    <?php endif; ?>
</div>
</body>
</html>
