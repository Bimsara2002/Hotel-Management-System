<?php
session_start();
include 'config.php';

// ✅ Ensure user is logged in
if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit;
}

// Get the logged-in customer ID
$customerId = $_SESSION['customerId']; // Assuming customerId is stored in session

// Fetch bookings only for the logged-in customer
$sql = "SELECT rb.bookingId, rb.checkIn, rb.checkOut, rb.numGuests, rb.totalAmount, rb.paymentStatus, rb.bookingStatus, 
               r.type, r.ACorNot, r.vip, r.image,
               ro.title AS offerTitle
        FROM room_booking rb
        JOIN room r ON rb.roomId = r.roomId
        LEFT JOIN room_offers ro ON rb.offerId = ro.offerId
        WHERE rb.customerId = ?
        ORDER BY rb.checkIn DESC"; // latest bookings first

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Bookings - Omik Restaurant</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f7fa;
    margin: 0;
    padding: 0;
}
header { background: #2c3e50; color: #fff; padding: 15px; text-align: center; } 
h1 { margin: 0; } 

.back-btn {
    display: inline-block;
    background: #7f8c8d;
    color: #fff;
    padding: 8px 14px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: bold;
    text-decoration: none;
    margin: 15px;
    transition: 0.3s;
}
.back-btn:hover { background: #606060; }

.container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}

table {
    width: 100%;
    border-collapse: collapse;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
th, td {
    padding: 12px 15px;
    text-align: center;
    font-size: 14px;
}
th {
    background: #34495e;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
tbody tr:nth-child(even) { background: #f8f9fa; }
tbody tr:hover { background: #e8f0fe; }

table img {
    width: 100px;
    height: 70px;
    object-fit: cover;
    border-radius: 6px;
}

.badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: capitalize;
}
.pending { background: #ffe08a; color: #8a6d3b; }
.paid { background: #c8f7c5; color: #2d862d; }
.cancelled { background: #f8d7da; color: #721c24; }
.reserved { background: #d1ecf1; color: #0c5460; }
.checkedin { background: #c3e6cb; color: #155724; }
.checkedout { background: #e2e3e5; color: #383d41; }

.price { font-weight: bold; color: #e67e22; }

td .btn {
    display: inline-block;
    margin: 3px 4px;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    color: #fff;
    transition: 0.2s;
}
.view-btn { background: #3498db; } .view-btn:hover { background: #2980b9; }
.update-btn { background: #27ae60; } .update-btn:hover { background: #1e8449; }
.cancel-btn { background: #e74c3c; } .cancel-btn:hover { background: #c0392b; }

td { white-space: nowrap; }

.no-bookings {
    text-align: center;
    padding: 60px;
    font-size: 18px;
    color: #888;
}

@media (max-width: 768px) {
    table, thead, tbody, th, td, tr { display: block; width: 100%; }
    thead { display: none; }
    tbody tr {
        margin-bottom: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        padding: 10px;
        background: #fff;
    }
    tbody td {
        text-align: right;
        padding: 10px;
        border: none;
        position: relative;
    }
    tbody td::before {
        content: attr(data-label);
        float: left;
        font-weight: 600;
        text-transform: capitalize;
    }
}
</style>
</head>
<body>

<header>
  <h1>My Bookings</h1>
</header>

<div style="padding:15px;">
  <button onclick="history.back()" class="back-btn">⬅ Back</button>
</div>

<div class="container">
<?php if ($result->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Image</th>
          <th>Room Type</th>
          <th>Guests</th>
          <th>Check-in</th>
          <th>Check-out</th>
          <th>Offer</th>
          <th>Total Amount</th>
          <th>Payment Status</th>
          <th>Booking Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
<?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['type']) ?>"></td>
          <td><?= htmlspecialchars($row['type']) ?> (<?= htmlspecialchars($row['ACorNot']) ?>)</td>
          <td><?= $row['numGuests'] ?></td>
          <td><?= $row['checkIn'] ?></td>
          <td><?= $row['checkOut'] ?></td>
          <td><?= $row['offerTitle'] ?? 'None' ?></td>
          <td class="price">Rs. <?= number_format($row['totalAmount'], 2) ?></td>
          <td><span class="badge <?= strtolower($row['paymentStatus']) ?>"><?= $row['paymentStatus'] ?></span></td>
          <td><span class="badge <?= strtolower($row['bookingStatus']) ?>"><?= $row['bookingStatus'] ?></span></td>
          <td>
            <div><button class="btn view-btn" onclick="alert('Booking ID: <?= $row['bookingId'] ?>')">View</button></div>
            <div><a href="updateBooking.php?bookingId=<?= $row['bookingId'] ?>" class="btn update-btn">Update</a></div>
            <div><a href="cancelBooking.php?bookingId=<?= $row['bookingId'] ?>" class="btn cancel-btn" onclick="return confirm('Are you sure you want to cancel this booking?')">Cancel</a></div>
          </td>
        </tr>
<?php endwhile; ?>
      </tbody>
    </table>
<?php else: ?>
    <div class="no-bookings">😔 You have no bookings yet.</div>
<?php endif; ?>
</div>

</body>
</html>
<?php $conn->close(); ?>
