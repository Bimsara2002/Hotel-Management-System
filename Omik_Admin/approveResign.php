<?php
session_start();

// Only HR Manager can access
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'hr manager') {
    header("Location: login.html");
    exit;
}

include 'config.php';

// Handle Approve/Reject actions
if (isset($_GET['action'], $_GET['id'])) {
    $resignId = intval($_GET['id']);
    $action = $_GET['action'] === 'approve' ? 'Approved' : 'Rejected';

    $stmt = $conn->prepare("UPDATE Resignation SET status=? WHERE resignationId=?");
    $stmt->bind_param("si", $action, $resignId);
    $stmt->execute();
    $stmt->close();
}

// Fetch all resignations with staff names (LEFT JOIN to show even if staff missing)
$sql = "SELECT r.*, s.FirstName, s.LastName 
        FROM Resignation r
        LEFT JOIN Staff s ON r.staffId = s.StaffId
        ORDER BY resignationDate DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Approve Resignations</title>
<link rel="stylesheet" href="approveResign.css">
<script>
function confirmAction(action) {
    return confirm("Are you sure you want to " + action + " this resignation?");
}
</script>
</head>
<body>
<div class="container">
    <h1>Approve Resignations</h1>
    <button class="button-back" onclick="window.location.href='HRManagerDashboard.php'">Back to Dashboard</button>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Staff Name</th>
                <th>Resignation Date</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $status = $row['status'] ?? 'Pending';
                $staffName = ($row['FirstName'] && $row['LastName']) ? "{$row['FirstName']} {$row['LastName']}" : "Unknown Staff";
                echo "<tr>
                    <td>{$row['resignationId']}</td>
                    <td>{$staffName}</td>
                    <td>{$row['resignationDate']}</td>
                    <td>{$row['reason']}</td>
                    <td class='status-{$status}'>{$status}</td>
                    <td>";
                if($status === 'Pending'){
                    echo "<a href='?action=approve&id={$row['resignationId']}' class='action-btn approve' onclick='return confirmAction(\"approve\")'>Approve</a>";
                    echo "<a href='?action=reject&id={$row['resignationId']}' class='action-btn reject' onclick='return confirmAction(\"reject\")'>Reject</a>";
                } else {
                    echo "-";
                }
                echo "</td></tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No resignations found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>