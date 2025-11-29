<?php
session_start();

// Only HR Manager can access
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'hr manager') {
    header("Location: login.html");
    exit;
}

include 'config.php';

// Handle new session form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $sessionDate = $_POST['sessionDate'] ?? '';
    $startTime = $_POST['startTime'] ?? '';
    $staffGroup = $_POST['staffGroup'] ?? '';

    if ($title && $sessionDate && $startTime && $staffGroup) {
        $stmt = $conn->prepare("INSERT INTO TrainingSession (title, description, sessionDate, startTime, staffGroup) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $title, $description, $sessionDate, $startTime, $staffGroup);
        $stmt->execute();
        $stmt->close();
        $message = "Training session added successfully!";
    } else {
        $message = "Please fill in all required fields!";
    }
}

// Filters for viewing sessions
$filterTitle = $_GET['title'] ?? '';
$filterDate = $_GET['sessionDate'] ?? '';
$filterGroup = $_GET['staffGroup'] ?? '';

// Fetch sessions
$query = "SELECT * FROM TrainingSession WHERE 1=1";
if (!empty($filterTitle)) {
    $query .= " AND title LIKE '%".$conn->real_escape_string($filterTitle)."%'";
}
if (!empty($filterDate)) {
    $query .= " AND sessionDate = '".$conn->real_escape_string($filterDate)."'";
}
if (!empty($filterGroup)) {
    $query .= " AND staffGroup LIKE '%".$conn->real_escape_string($filterGroup)."%'";
}
$query .= " ORDER BY sessionDate DESC, startTime ASC";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Training Sessions</title>
<link rel="stylesheet" href="trainingSession.css">
</head>
<body>
<div class="container">
        <button class="button-back" onclick="window.location.href='HRManagerDashboard.php'">Back to Dashboard</button>                                      

    <h1>Organize Training Sessions</h1>


    <?php if($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <div class="two-columns">
        <!-- Left Column: Add Training Session -->
        <div class="column form-column">
            <h2>Add New Session</h2>
            <form method="POST">
                <label>Title *</label>
                <input type="text" name="title" required>

                <label>Description</label>
                <textarea name="description"></textarea>

                <label>Date *</label>
                <input type="date" name="sessionDate" required>

                <label>Start Time *</label>
                <input type="time" name="startTime" required>

                <label>Staff Group *</label>
                <select name="staffGroup" required>
                    <option value="">-- Select Group --</option>
                    <option value="All staff">All staff</option>
                    <option value="Kitchen staff">Kitchen staff</option>
                    <option value="Service staff">Service staff</option>
                    <option value="Transport staff">Transport staff</option>
                </select>

                <button type="submit" class="btn-submit">Add Session</button>
            </form>
        </div>

        <!-- Right Column: View Sessions -->
        <div class="column table-column">
            <h2>View Sessions</h2>

            <form method="GET" class="filter-form">
                <input type="text" name="title" placeholder="Search Title" value="<?= htmlspecialchars($filterTitle) ?>">
                <input type="date" name="sessionDate" value="<?= htmlspecialchars($filterDate) ?>">
                <select name="staffGroup">
                    <option value="">-- All Groups --</option>
                    <option value="All staff" <?= ($filterGroup=='All staff')?'selected':'' ?>>All staff</option>
                    <option value="Kitchen staff" <?= ($filterGroup=='Kitchen staff')?'selected':'' ?>>Kitchen staff</option>
                    <option value="Service staff" <?= ($filterGroup=='Service staff')?'selected':'' ?>>Service staff</option>
                    <option value="Transport staff" <?= ($filterGroup=='Transport staff')?'selected':'' ?>>Transport staff</option>
                </select>
                <button type="submit" class="btn-filter">Filter</button>
                <button type="button" class="btn-reset" onclick="window.location.href='trainingSession.php'">Reset</button>
            </form>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Start Time</th>
                            <th>Staff Group</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>
                                    <td>{$row['sessionId']}</td>
                                    <td>{$row['title']}</td>
                                    <td>{$row['description']}</td>
                                    <td>{$row['sessionDate']}</td>
                                    <td>{$row['startTime']}</td>
                                    <td>{$row['staffGroup']}</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No sessions found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
