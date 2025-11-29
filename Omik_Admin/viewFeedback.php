<?php
session_start();

// Check if logged in and role is General Manager
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'general manager') {
    header("Location: login.html");
    exit;
}

include 'config.php';

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedbackId'])) {
    $feedbackId = intval($_POST['feedbackId']);
    $reply = trim($_POST['reply']);

    if (!empty($reply)) {
        $stmt = $conn->prepare("UPDATE feedBack SET reply = ? WHERE feedbackId = ?");
        $stmt->bind_param("si", $reply, $feedbackId);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch all feedback
$sql = "SELECT * FROM feedBack ORDER BY fdate DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Feedback</title>
<link rel="stylesheet" href="feedback.css">
<style>.button-group {
    display: flex;
    justify-content: flex-start;
    gap: 5px;
    margin-bottom: 20px;
}
</style>
</head>
<body>
<div class="container">
    <h1>Customer Feedback</h1>

    <div class="button-group">
        <button class="button-back" onclick="window.location.href='ManagerDashboard.php'">Back to Dashboard</button>
    </div>

    <table>
        <thead>
            <tr>
                <th>Feedback ID</th>
                <th>Description</th>
                <th>Reply</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                    <td>{$row['feedbackId']}</td>
                    <td>{$row['discription']}</td>
                    <td>".(!empty($row['reply']) ? $row['reply'] : "<span class='no-reply'>No reply yet</span>")."</td>
                    <td>{$row['fdate']}</td>
                    <td>
                        <form method='POST' class='reply-form'>
                            <input type='hidden' name='feedbackId' value='{$row['feedbackId']}'>
                            <input type='text' name='reply' placeholder='Enter reply...' required>
                            <button type='submit'>Reply</button>
                        </form>
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No feedback available.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>
