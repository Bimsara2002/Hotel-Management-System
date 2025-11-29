<?php
session_start();
include 'config.php';

// ✅ Allow only General Manager or HR Manager
if (!isset($_SESSION['staffRole']) || !in_array(strtolower(trim($_SESSION['staffRole'])), ['general manager', 'hr manager'])) {
    header("Location: login.html");
    exit;
}

$success = "";

// ===== FETCH STAFF =====
$sql = "SELECT StaffId, FirstName, LastName, JobRole, BasicSalary, OtRate, Email, Status 
        FROM Staff ORDER BY JobRole, FirstName";
$result = $conn->query($sql);

// ===== AUTO SHIFT ASSIGNMENT RULE =====
function autoAssignShift($jobRole) {
    $role = strtolower($jobRole);
    if (strpos($role, 'chef') !== false) return 'Evening';
    if (strpos($role, 'cashier') !== false) return 'Morning';
    if (strpos($role, 'room keeper') !== false) return 'Night';
    if (strpos($role, 'manager') !== false) return 'Morning';
    if (strpos($role, 'reception') !== false) return 'Morning';
    if (strpos($role, 'delivery') !== false) return 'Evening';
    return 'Morning';
}

// ===== SAVE SCHEDULE =====
if (isset($_POST['save_schedule'])) {
    $schedule_date = $_POST['schedule_date'] ?? '';

    if (!empty($schedule_date) && isset($_POST['shift']) && is_array($_POST['shift'])) {
        $stmt = $conn->prepare("INSERT INTO StaffSchedule (staff_id, shift_type, schedule_date, notes) VALUES (?, ?, ?, ?)");
        foreach ($_POST['shift'] as $staffId => $shift) {
            $note = $_POST['note'][$staffId] ?? '';
            $stmt->bind_param("isss", $staffId, $shift, $schedule_date, $note);
            $stmt->execute();
        }
        $stmt->close();
        $success = "✅ Schedule saved successfully for $schedule_date!";
    } else {
        $success = "⚠ Please select a schedule date and assign shifts.";
    }
}

// ===== FETCH SAVED SCHEDULES =====
$filterDate = $_POST['filter_date'] ?? '';
$whereClause = $filterDate ? "WHERE ss.schedule_date = '$filterDate'" : "";

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
<title>Staff Shift Schedule</title>
<style>
body { font-family: "Segoe UI", sans-serif; background:#fdfdfd; margin:30px; }
.container { max-width:1200px; margin:auto; background:white; padding:25px 40px; border-radius:10px; box-shadow:0 4px 15px rgba(0,0,0,0.1); }
h1 { text-align:center; color:#2c3e50; margin-bottom:10px; }
.actions { text-align:center; margin-bottom:20px; }
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
.success { color:green; text-align:center; margin-bottom:10px; font-weight:bold; }
.section-title { background:#ecf0f1; padding:10px; border-left:5px solid #e67e22; margin-top:30px; color:#2c3e50; font-size:16px; }
.filter-form { margin-bottom:15px; text-align:center; }
@media print {
    button, .actions, .filter-form, input, select { display:none; }
    body { margin: 0; }
    h1 { color:#000; }
    table { border:1px solid #000; }
    th { background:#ddd; color:#000; }
}
</style>
<script>
function printSchedule() { window.print(); }
</script>
</head>
<body>
<div class="container">
    <h1>🗓 Staff Shift Schedule</h1>

    <!-- Single Form: Schedule Date + Staff Table -->
    <form method="POST">
        <div class="actions">
            <label><strong>Schedule Date:</strong></label>
            <input type="date" name="schedule_date" required>
            <button type="submit" name="save_schedule">💾 Save Schedule</button>
            <button class="print-btn" type="button" onclick="printSchedule()">🖨 Print Schedule</button>
            <button class="back-btn" type="button" onclick="window.location.href='hrManagerDashboard.php'">⬅ Back to Dashboard</button>
        </div>

        <?php if (!empty($success)) echo "<p class='success'>$success</p>"; ?>

        <table>
            <tr>
                <th>#</th>
                <th>Staff Name</th>
                <th>Job Role</th>
                <th>Shift</th>
                <th>Note</th>
                <th>Status</th>
            </tr>
            <?php $i=1; while ($row = $result->fetch_assoc()): 
                $shift = autoAssignShift($row['JobRole']);
            ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></td>
                <td><?= htmlspecialchars($row['JobRole']) ?></td>
                <td>
                    <select name="shift[<?= $row['StaffId'] ?>]">
                        <option value="Morning" <?= $shift=='Morning'?'selected':'' ?>>Morning</option>
                        <option value="Evening" <?= $shift=='Evening'?'selected':'' ?>>Evening</option>
                        <option value="Night" <?= $shift=='Night'?'selected':'' ?>>Night</option>
                    </select>
                </td>
                <td><input type="text" name="note[<?= $row['StaffId'] ?>]" placeholder="Optional note"></td>
                <td><?= htmlspecialchars($row['Status']) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </form>

    <!-- Saved Schedules Section -->
    <div class="section-title">📅 View Saved Shift Schedules</div>
    <form method="POST" class="filter-form">
        <label>Filter by Date:</label>
        <input type="date" name="filter_date" value="<?= htmlspecialchars($filterDate) ?>">
        <button type="submit">🔍 Filter</button>
        <button type="button" onclick="window.location.href='generateSchedule.php'">Reset</button>
    </form>

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
