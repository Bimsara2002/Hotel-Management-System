<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reception Dashboard</title>
<link rel="stylesheet" href="receptionDashboard.css">
</head>
<body>
<div class="dashboard-container">
<header class="dashboard-header">
    <div class="header-left">
        <h1>🏨 Reception Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION['staffName'] ?? 'Receptionist') ?>!</p>
    </div>
    <a href="logout.php" id="logoutBtn" class="logout-btn">🔒 Logout</a>
</header>

<script>
document.getElementById('logoutBtn').addEventListener('click', function(e) {
    e.preventDefault();
    if (confirm("Are you sure you want to log out?")) {
        window.location.href = this.href;
    }
});
</script>

<?php if ($success): ?><div class="alert success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= $error ?></div><?php endif; ?>

<nav class="tab-menu">
    <button onclick="openTab('viewBookings')">📋 View Bookings</button>
    <button onclick="openTab('newBooking')">➕ New Booking</button>
    <button onclick="openTab('newCustomer')">🧑‍💼 Add Customer</button>
    <button onclick="openTab('reserveTable')">🍽 Reserve Table</button>
    <button onclick="openTab('viewReservations')">📑 View Reservations</button>
</nav>

<!-- View Bookings -->
<section id="viewBookings" class="tab-content active">
    <h2>All Room Bookings</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Guest</th><th>Room</th><th>Check-In</th><th>Check-Out</th>
                <th>Guests</th><th>Status</th><th>Payment</th><th>Update</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($bookings->num_rows > 0): ?>
                <?php while($row = $bookings->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['bookingId'] ?></td>
                        <td><?= htmlspecialchars($row['customerName']) ?></td>
                        <td><?= htmlspecialchars($row['type'].' (Rs '.$row['price'].')') ?></td>
                        <td><?= $row['checkIn'] ?></td>
                        <td><?= $row['checkOut'] ?></td>
                        <td><?= $row['numGuests'] ?></td>
                        <td><span class="badge <?= strtolower($row['bookingStatus']) ?>"><?= $row['bookingStatus'] ?></span></td>
                        <td><?= $row['paymentStatus'] ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="bookingId" value="<?= $row['bookingId'] ?>">
                                <select name="bookingStatus" required>
                                    <option value="">Select</option>
                                    <option value="CheckedIn">Check-In</option>
                                    <option value="CheckedOut">Check-Out</option>
                                    <option value="Cancelled">Cancel</option>
                                </select>
                                <button type="submit" name="update_status">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="9">No bookings found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<!-- New Booking -->
<section id="newBooking" class="tab-content">
    <h2>Create New Booking</h2>
    <form method="POST" class="booking-form" id="bookingForm">
        <label>Guest:</label>
        <select name="customerId" required>
            <option value="">Select Guest</option>
            <?php while($c = $customers->fetch_assoc()): ?>
                <option value="<?= $c['CustomerId'] ?>"><?= htmlspecialchars($c['Name']) ?></option>
            <?php endwhile; ?>
        </select>

        <label>Room:</label>
        <select name="roomId" id="roomId" required>
            <option value="">Select Room</option>
            <?php
            $rooms->data_seek(0);
            while($r = $rooms->fetch_assoc()): ?>
                <option value="<?= $r['roomId'] ?>" data-price="<?= $r['price'] ?>" data-type="<?= $r['type'] ?>">
                    <?= htmlspecialchars($r['type'].' - Rs '.$r['price']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Offer (Optional):</label>
        <select name="offerId" id="offerId">
            <option value="">No Offer</option>
            <?php
            $today = date('Y-m-d');
            $offers = $conn->query("SELECT * FROM room_offers WHERE status='Active' AND valid_from <= '$today' AND valid_to >= '$today'");
            while($offer = $offers->fetch_assoc()): ?>
                <option value="<?= $offer['offerId'] ?>" data-type="<?= $offer['roomType'] ?>" data-discount="<?= $offer['discount_percent'] ?>">
                    <?= htmlspecialchars($offer['title'].' ('.$offer['discount_percent'].'% OFF - '.$offer['roomType'].')') ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Check-In:</label>
        <input type="date" name="checkIn" required>
        <label>Check-Out:</label>
        <input type="date" name="checkOut" required>
        <label>Guests:</label>
        <input type="number" name="numGuests" min="1" value="1" required>
        <label>Total Amount (Rs):</label>
        <input type="number" name="totalAmount" id="totalAmount" min="0" step="0.01" required readonly>
        <label>Payment Status:</label>
        <select name="paymentStatus" required>
            <option value="Pending">Pending</option>
            <option value="Paid">Paid</option>
        </select>
        <button type="submit" name="create_booking">Save Booking</button>
    </form>
</section>

<!-- Add Customer -->
<section id="newCustomer" class="tab-content">
    <h2>Add New Customer</h2>
    <form method="POST" class="booking-form">
        <label>Name:</label>
        <input type="text" name="name" required>
        <label>Email:</label>
        <input type="email" name="email" required>
        <label>Phone:</label>
        <input type="text" name="phone" required>
        <label>Address:</label>
        <input type="text" name="address" required>
        <button type="submit" name="add_customer">Save Customer</button>
    </form>
</section>

<!-- Reserve Table -->
<section id="reserveTable" class="tab-content">
    <h2>Reserve Table</h2>
    <form method="POST" class="booking-form">
        <label>Customer:</label>
        <select name="customerIdTable" required>
            <option value="">Select Customer</option>
            <?php
            $customers->data_seek(0);
            while($c = $customers->fetch_assoc()): ?>
                <option value="<?= $c['CustomerId'] ?>"><?= htmlspecialchars($c['Name']) ?></option>
            <?php endwhile; ?>
        </select>

        <label>Table:</label>
        <select name="tableId" required>
            <option value="">Select Table</option>
            <?php
            $tables->data_seek(0);
            while($t = $tables->fetch_assoc()):
                if($t['availability'] === 'Available'):
            ?>
                <option value="<?= $t['table_id'] ?>">Table <?= $t['table_number'] ?> (Seats: <?= $t['seats'] ?>)</option>
            <?php endif; endwhile; ?>
        </select>

        <label>Number of Guests:</label>
        <input type="number" name="numGuestsTable" min="1" required>
        <button type="submit" name="reserve_table">Reserve Table</button>
    </form>
</section>

<!-- View Reservations -->
<section id="viewReservations" class="tab-content">
    <h2>All Table Reservations</h2>
    <table>
        <thead>
            <tr>
                <th>Table No.</th><th>Seats</th><th>Status</th><th>Reserved By</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $tables->data_seek(0);
            while($row = $tables->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['table_number'] ?></td>
                    <td><?= $row['seats'] ?></td>
                    <td><?= $row['availability'] ?></td>
                    <td><?= htmlspecialchars($row['customerName'] ?? '-') ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</section>
</div>

<script>
function openTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    document.querySelectorAll('.tab-menu button').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`[onclick="openTab('${tabId}')"]`).classList.add('active');
}

// Auto price + discount calculation
const roomSelect = document.getElementById('roomId');
const offerSelect = document.getElementById('offerId');
const totalAmount = document.getElementById('totalAmount');

roomSelect.addEventListener('change', calculateTotal);
offerSelect.addEventListener('change', calculateTotal);

function calculateTotal() {
    const roomOpt = roomSelect.options[roomSelect.selectedIndex];
    const offerOpt = offerSelect.options[offerSelect.selectedIndex];
    if (!roomOpt.dataset.price) return;
    let price = parseFloat(roomOpt.dataset.price);
    let discount = 0;

    if (offerOpt && offerOpt.dataset.type === roomOpt.dataset.type) {
        discount = parseFloat(offerOpt.dataset.discount) || 0;
    }
    const finalAmount = price - (price * discount / 100);
    totalAmount.value = finalAmount.toFixed(2);
}
</script>
</body>
</html>
