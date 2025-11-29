<?php
session_start();

// Only General Manager can access
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'general manager') {
    header("Location: login.html");
    exit;
}

include 'config.php';

// Handle Approve/Reject actions
if (isset($_GET['action'], $_GET['id'])) {
    $leaveId = intval($_GET['id']);
    $action = $_GET['action'] === 'approve' ? 'Approved' : 'Rejected';
    $stmt = $conn->prepare("UPDATE LeaveRequests SET Status=? WHERE LeaveId=?");
    $stmt->bind_param("si", $action, $leaveId);
    $stmt->execute();
    $stmt->close();
}

// Fetch all leave requests
$sql = "SELECT * FROM LeaveRequests ORDER BY RequestedDate DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Leave Requests</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f0f2f5;
    margin: 0;
    padding: 0;
    color: #333;
}
.container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 25px 30px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}
h1 {
    text-align: center;
    margin-bottom: 20px;
    color: #1f2937;
}
.button-back {
    padding: 10px 18px;
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    margin-bottom: 15px;
}
.button-back:hover {
    background: #0056b3;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    font-size: 14px;
}
table, th, td {
    border: 1px solid #e2e8f0;
}
th, td {
    padding: 12px;
    text-align: center;
}
th {
    background: #007bff;
    color: #fff;
}
tr:nth-child(even) {
    background-color: #f9fafb;
}
tr:hover {
    background-color: #e6f0ff;
}
.status-Pending {color: orange; font-weight: bold;}
.status-Approved {color: green; font-weight: bold;}
.status-Rejected {color: red; font-weight: bold;}
.action-btn {
    padding: 6px 12px;
    margin: 2px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 13px;
    color: #fff;
}
.approve {background: green;}
.approve:hover {background: darkgreen;}
.reject {background: red;}
.reject:hover {background: darkred;}
</style>
</head>
<body>
<div class="container">
    <h1>Manage Leave Requests</h1>
    <button class="button-back" onclick="window.location.href='ManagerDashboard.php'">Back to Dashboard</button>

    <table>
        <thead>
            <tr>
                <th>Leave ID</th>
                <th>Staff ID</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Requested Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $status = $row['Status'];
                echo "<tr>
                    <td>{$row['LeaveId']}</td>
                    <td>{$row['StaffId']}</td>
                    <td>{$row['StartDate']}</td>
                    <td>{$row['EndDate']}</td>
                    <td>{$row['Reason']}</td>
                    <td class='status-{$status}'>{$row['Status']}</td>
                    <td>{$row['RequestedDate']}</td>
                    <td>";
                if($row['Status'] == 'Pending'){
                    echo "<a href='?action=approve&id={$row['LeaveId']}' class='action-btn approve'>Approve</a>";
                    echo "<a href='?action=reject&id={$row['LeaveId']}' class='action-btn reject'>Reject</a>";
                } else {
                    echo "-";
                }
                echo "</td></tr>";
            }
        } else {
            echo "<tr><td colspan='9'>No leave requests found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>
