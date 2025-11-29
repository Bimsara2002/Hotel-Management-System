<?php
session_start();
include 'config.php';

// ✅ Only Restaurant Manager can access
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'restaurant manager') {
    header("Location: login.html");
    exit;
}

// ✅ Handle status update and cost update
if (isset($_POST['update_cost'], $_POST['issue_id'])) {
    $issue_id = intval($_POST['issue_id']);
    $cost = floatval($_POST['maintenance_cost']);

    // Update cost and mark as Resolved
    $stmt = $conn->prepare("UPDATE KitchenIssue SET maintenance_cost=?, status='Resolved' WHERE issuse_id=?");
    $stmt->bind_param("di", $cost, $issue_id);
    $stmt->execute();
    $stmt->close();
}

// ✅ Handle status change to In Progress
if (isset($_GET['action'], $_GET['id'])) {
    $issue_id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'progress') {
        $new_status = 'In Progress';
        $stmt = $conn->prepare("UPDATE KitchenIssue SET status=? WHERE issuse_id=?");
        $stmt->bind_param("si", $new_status, $issue_id);
        $stmt->execute();
        $stmt->close();
    }
}

// ✅ Fetch all issues
$result = $conn->query("SELECT * FROM KitchenIssue ORDER BY issuse_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Kitchen Issues</title>
<link rel="stylesheet" href="manageKitchenIssue.css">
<style>
.btn { padding: 5px 10px; text-decoration:none; border-radius:5px; color:#fff; }
.progress { background:#f39c12; }
.resolve { background:#27ae60; }
.cost-form input { width:80px; padding:3px; }
</style>
</head>
<body>
<div class="container">
    <a href="RestaurantManagerDashboard.php" class="back-btn">⬅ Back to Dashboard</a>
    <h1>🍽 Manage Kitchen Issues</h1>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Description</th>
                <th>Status</th>
                <th>Maintenance Cost ($)</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['issuse_id'] ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                        <td>
                            <?php if ($row['status'] === 'In Progress'): ?>
                                <form method="POST" class="cost-form">
                                    <input type="hidden" name="issue_id" value="<?= $row['issuse_id'] ?>">
                                    <input type="number" step="0.01" name="maintenance_cost" value="<?= $row['maintenance_cost'] ?>" required>
                                    <button type="submit" name="update_cost" class="btn resolve">Save & Resolve</button>
                                </form>
                            <?php else: ?>
                                <?= number_format($row['maintenance_cost'],2) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['status'] === 'Pending'): ?>
                                <a href="?action=progress&id=<?= $row['issuse_id'] ?>" class="btn progress">Mark In Progress</a>
                            <?php elseif ($row['status'] === 'Resolved'): ?>
                                ✅ Resolved
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No kitchen issues reported yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
