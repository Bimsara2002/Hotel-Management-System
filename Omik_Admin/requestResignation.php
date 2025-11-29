<?php
session_start();
include 'config.php';

// ✅ Allow only logged-in staff
if (!isset($_SESSION['staffId'])) {
    header("Location: login.html");
    exit;
}

$staffId = $_SESSION['staffId'];
$staffRole = $_SESSION['staffRole'] ?? 'Staff';
$message = "";

// ✅ Handle resignation submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $reason = trim($_POST['reason']);

    if (!empty($reason)) {
        $stmt = $conn->prepare("INSERT INTO Resignation (staffId, resignationDate, reason, status) VALUES (?, NOW(), ?, 'Pending')");
        $stmt->bind_param("is", $staffId, $reason);

        if ($stmt->execute()) {
            $message = "✅ Your resignation request has been submitted successfully!";
        } else {
            $message = "❌ Error submitting request: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "⚠ Please enter your reason for resignation.";
    }
}

// ✅ Fetch previous resignation requests by this staff
$sql = "SELECT * FROM Resignation WHERE staffId = ? ORDER BY resignationDate DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staffId);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Request Resignation</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f0f2f5;
    margin: 0;
    padding: 0;
    color: #333;
}
.container {
    max-width: 900px;
    margin: 50px auto;
    background: #fff;
    border-radius: 16px;
    padding: 30px 40px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
h1 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 20px;
}
.message {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 12px 18px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
}
.message.error {
    background: #fdecea;
    color: #c62828;
}
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 40px;
}
textarea {
    resize: none;
    min-height: 120px;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 10px;
    font-size: 15px;
}
button {
    background: #007bff;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 10px;
    font-size: 16px;
    cursor: pointer;
    transition: 0.3s ease;
}
button:hover {
    background: #0056b3;
}
.button-back {
    background: #6c757d;
    margin-bottom: 20px;
}
.button-back:hover {
    background: #495057;
}
.table-container {
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
th, td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid #ddd;
}
th {
    background: #007bff;
    color: white;
}
tr:hover {
    background-color: #f1f1f1;
}
.status {
    font-weight: 600;
    padding: 6px 10px;
    border-radius: 8px;
    text-transform: capitalize;
}
.status-Pending {
    color: #856404;
    background: #fff3cd;
}
.status-Approved {
    color: #155724;
    background: #d4edda;
}
.status-Rejected {
    color: #721c24;
    background: #f8d7da;
}
</style>
</head>
<body>
<div class="container">
    <button class="button-back" onclick="history.back()">⬅ Back to Dashboard</button>
    <h1>Request Resignation</h1>

    <?php if ($message): ?>
        <div class="message <?= strpos($message, 'Error') !== false ? 'error' : '' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label for="reason"><strong>Reason for Resignation:</strong></label>
        <textarea id="reason" name="reason" placeholder="Briefly describe your reason..." required></textarea>
        <button type="submit">Submit Resignation</button>
    </form>

    <h2 style="margin-bottom:10px;">Your Resignation History</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Resignation ID</th>
                    <th>Date</th>
                    <th>Reason</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['resignationId'] ?></td>
                            <td><?= $row['resignationDate'] ?></td>
                            <td><?= htmlspecialchars($row['reason']) ?></td>
                            <td><span class="status status-<?= htmlspecialchars($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4">No resignation requests found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
