<?php
session_start();
include 'config.php';

$bookingId = $_GET['bookingId'] ?? null;

if (!$bookingId) {
    die("Invalid booking ID.");
}

// Fetch booking details
$sql = "SELECT * FROM room_booking WHERE bookingId = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    die("Booking not found.");
}

// Handle update form submit
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $checkIn = $_POST['checkIn'];
    $checkOut = $_POST['checkOut'];
    $numGuests = $_POST['numGuests'];

    $updateSql = "UPDATE room_booking 
                  SET checkIn = ?, checkOut = ?, numGuests = ? 
                  WHERE bookingId = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("ssii", $checkIn, $checkOut, $numGuests, $bookingId);

    if ($updateStmt->execute()) {
        echo "<script>alert('Booking updated successfully!'); window.location='ViewMyBooking.php';</script>";
    } else {
        echo "<script>alert('Error updating booking.'); window.location='ViewMyBooking.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Update Booking</title>
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 0;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background: linear-gradient(135deg, #a8edea, #fed6e3);
    }

    .container {
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px);
      border-radius: 20px;
      padding: 40px 30px;
      width: 100%;
      max-width: 480px;
      box-shadow: 0px 8px 25px rgba(0,0,0,0.15);
      animation: fadeIn 0.8s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    h2 {
      text-align: center;
      margin-bottom: 25px;
      font-size: 26px;
      color: #34495e;
    }

    label {
      display: block;
      margin-bottom: 6px;
      font-weight: 500;
      color: #2c3e50;
      font-size: 14px;
    }

    input {
      width: 100%;
      padding: 12px;
      margin-bottom: 18px;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 14px;
      transition: 0.3s;
    }

    input:focus {
      border-color: #6a11cb;
      box-shadow: 0px 0px 8px rgba(106,17,203,0.3);
      outline: none;
    }

    .btn {
      padding: 12px 20px;
      border: none;
      border-radius: 10px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: 0.3s;
      margin: 5px;
      display: inline-block;
    }

    .btn-update {
      background: linear-gradient(135deg, #6a11cb, #2575fc);
      color: #fff;
    }

    .btn-update:hover {
      box-shadow: 0 0 12px rgba(106, 17, 203, 0.6);
      transform: translateY(-2px);
    }

    .btn-back {
      background: #f1f1f1;
      color: #333;
    }

    .btn-back:hover {
      background: #e0e0e0;
      transform: translateY(-2px);
    }

    .btn-group {
      text-align: center;
      margin-top: 10px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Update Your Booking</h2>
    <form method="POST">
      <label>Check-in Date:</label>
      <input type="date" name="checkIn" value="<?= htmlspecialchars($booking['checkIn']) ?>" required>

      <label>Check-out Date:</label>
      <input type="date" name="checkOut" value="<?= htmlspecialchars($booking['checkOut']) ?>" required>

      <label>Number of Guests:</label>
      <input type="number" name="numGuests" min="1" value="<?= htmlspecialchars($booking['numGuests']) ?>" required>

      <div class="btn-group">
        <button type="submit" class="btn btn-update">Update</button>
        <a href="ViewMyBooking.php" class="btn btn-back">Back</a>
      </div>
    </form>
  </div>
</body>
</html>
