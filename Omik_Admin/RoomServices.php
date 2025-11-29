<?php
session_start();
include 'config.php';

// Ensure customer is logged in
if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit;
}

$customerId = $_SESSION['userId'];
$success = '';
$error = '';

// Handle form submission
if (isset($_POST['submitRequest'])) {
    $roomNumber = $_POST['roomNumber'];
    $requestDetails = trim($_POST['requestDetails']);

    if (!empty($roomNumber) && !empty($requestDetails)) {
        $stmt = $conn->prepare("INSERT INTO RoomServiceRequests (roomNumber, customerId, requestDetails) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $roomNumber, $customerId, $requestDetails);
        if ($stmt->execute()) {
            $success = "✅ Room service request submitted successfully!";
        } else {
            $error = "❌ Failed to submit request. Try again.";
        }
    } else {
        $error = "❌ Please fill in all fields.";
    }
}

// Fetch checked-in bookings for this customer
$stmt = $conn->prepare("SELECT rb.bookingId, rb.roomId FROM room_booking rb 
                        JOIN room r ON rb.roomId = r.roomId
                        WHERE rb.customerId = ? AND rb.bookingStatus='CheckedIn'");
$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();
$checkedInRooms = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Room Service Request</title>
<link rel="stylesheet" href="roomService.css">
</head>
<body>

<div class="container">
    <h2>🛎 Request Room Service</h2>

    <div class="back-btn-container">
        <a href="customerDashboard.php" class="back-btn">⬅ Back</a>
    </div>

    <?php if ($error): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert success"><?= $success ?></div>
    <?php endif; ?>

    <?php if (!empty($checkedInRooms)): ?>
        <form method="POST" class="room-service-form">
            <label for="roomNumber">Select Your Room:</label>
            <select name="roomNumber" id="roomNumber" required>
                <option value="">-- Select Room --</option>
                <?php foreach ($checkedInRooms as $room): ?>
                    <option value="<?= htmlspecialchars($room['roomNumber']) ?>">
                        <?= htmlspecialchars($room['roomNumber']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="requestDetails">Request Details:</label>
            <textarea name="requestDetails" id="requestDetails" rows="5" placeholder="Enter your request here..." required></textarea>

            <button type="submit" name="submitRequest" class="submit-btn">Submit Request</button>
        </form>
    <?php else: ?>
        <div class="no-rooms">😔 You have no active checked-in bookings.</div>
    <?php endif; ?>
</div>

</body>
</html>
