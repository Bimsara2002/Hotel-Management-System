<?php
session_start();
include 'config.php';

$bookingId = $_GET['bookingId'] ?? null;

if (!$bookingId) {
    die("Invalid booking ID.");
}

// Delete booking permanently
$sql = "DELETE FROM room_booking WHERE bookingId = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bookingId);

if ($stmt->execute()) {
    echo "<script>alert('Booking deleted successfully!'); window.location='ViewMyBooking.php';</script>";
} else {
    echo "<script>alert('Error deleting booking.'); window.location='ViewMyBooking.php';</script>";
}
?>
