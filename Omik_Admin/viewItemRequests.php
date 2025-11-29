<?php
include 'config.php';

// Fetch all item requests
$sql = "SELECT * FROM NewItemRequests ORDER BY request_date DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Item Requests</title>
    <link rel="stylesheet" href="ItemRequestsStyle.css">
</head>
<body>
    <div class="container">
         <button class="button-back" onclick="window.location.href='inventoryManagerDashboard.php'">Back to Dashboard</button>
        <h1>📋 View Item Requests</h1>
        <p class="subtitle">All item requests submitted by the Stock Keeper</p>

        <table>
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Request Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['request_id'] ?></td>
                            <td><?= htmlspecialchars($row['item_name']) ?></td>
                            <td><?= $row['quantity'] ?></td>
                            <td><?= $row['request_date'] ?></td>
                            <td><span class="status <?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
                            <td>
                                <?php if (strtolower($row['status']) === 'pending'): ?>
                                    <button class="approve-btn" onclick="updateRequestStatus(<?= $row['request_id'] ?>, 'Approved')">Approve</button>
                                    <button class="reject-btn" onclick="updateRequestStatus(<?= $row['request_id'] ?>, 'Rejected')">Reject</button>
                                <?php else: ?>
                                    <em>No action</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">No requests found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function updateRequestStatus(id, newStatus) {
            if (confirm(`Are you sure you want to mark this request as ${newStatus}?`)) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "update_request_status.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        alert(xhr.responseText);
                        location.reload();
                    }
                };
                xhr.send(`id=${id}&status=${newStatus}`);
            }
        }
    </script>
</body>
</html>
