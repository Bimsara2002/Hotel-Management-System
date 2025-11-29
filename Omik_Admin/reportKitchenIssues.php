<?php
session_start();
include 'config.php';

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description']);

    if (!empty($description)) {
        $stmt = $conn->prepare("INSERT INTO KitchenIssue (description, status) VALUES (?, 'Pending')");
        $stmt->bind_param("s", $description);

        if ($stmt->execute()) {
            $success = "Issue reported successfully!";
        } else {
            $error = "Error reporting issue. Please try again.";
        }
        $stmt->close();
    } else {
        $error = "Please enter a description for the issue.";
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
<title>Report Kitchen Issue</title>
<link rel="stylesheet" href="reportKitchenIssue.css">
</head>
<body>
<div class="container">
    <a href="ChefDashboard.php" class="back-btn">⬅ Back to Dashboard</a>
    <h1>🍳 Report Kitchen Issue</h1>

    <div class="columns">
        <!-- ✅ Left Column: Report Form -->
        <div class="form-section">
            <h2>Report New Issue</h2>
            <?php if (!empty($success)): ?>
                <p class="success"><?= htmlspecialchars($success) ?></p>
            <?php elseif (!empty($error)): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST">
                <label for="description">Issue Description:</label>
                <textarea id="description" name="description" rows="5" placeholder="Describe the issue..." required></textarea>

                <button type="submit">Submit Issue</button>
            </form>
        </div>

        <!-- ✅ Right Column: View All Issues -->
        <div class="view-section">
            <h2>View Reported Issues</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Description</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['issuse_id'] ?></td>
                                <td><?= htmlspecialchars($row['description']) ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3">No issues reported yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
