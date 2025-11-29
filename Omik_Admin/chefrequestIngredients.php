<?php
session_start();
include 'config.php';

// ✅ Only allow Chef
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'chef') {
    header("Location: login.html");
    exit;
}

// ✅ Handle new request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_name'], $_POST['quantity'])) {
    $item_name = trim($_POST['item_name']);
    $quantity = intval($_POST['quantity']);

    if (!empty($item_name) && $quantity > 0) {
        $stmt = $conn->prepare("INSERT INTO KitchenRequest (item_name, quantity, status) VALUES (?, ?, 'Pending')");
        $stmt->bind_param("si", $item_name, $quantity);
        $stmt->execute();
        $message = "Item request submitted successfully!";
    } else {
        $message = "Please enter valid item details.";
    }
}

// ✅ Fetch all kitchen requests
$result = $conn->query("SELECT * FROM KitchenRequest ORDER BY request_id DESC");
$requests = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kitchen Item Request</title>
<link rel="stylesheet" href="kitchenRequest.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="container">
    <a href="ChefDashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    <h1>🍲 Kitchen Item Requests</h1>

    <div class="content">
        <!-- Left: Add Request -->
        <div class="form-section">
            <h2>Add New Request</h2>
            <?php if(isset($message)) echo "<p class='msg'>$message</p>"; ?>

            <form method="POST" action="">
                <label>Item Name:</label>
                <input type="text" name="item_name" placeholder="Enter item name" required>

                <label>Quantity:</label>
                <input type="number" name="quantity" min="1" required>

                <button type="submit">Submit Request</button>
            </form>
        </div>

        <!-- Right: View Requests -->
        <div class="table-section">
            <h2>View Requests</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item Name</th>
                        <th>Quantity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($requests)): ?>
                        <tr><td colspan="4" class="no-data">No requests found.</td></tr>
                    <?php else:
                        foreach($requests as $req):
                            $statusClass = strtolower(str_replace(' ', '-', $req['status']));
                    ?>
                    <tr>
                        <td><?= $req['request_id'] ?></td>
                        <td><?= htmlspecialchars($req['item_name']) ?></td>
                        <td><?= $req['quantity'] ?></td>
                        <td class="status-<?= $statusClass ?>"><?= $req['status'] ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
