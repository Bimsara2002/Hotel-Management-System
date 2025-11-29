<?php
session_start();
include 'config.php';

// ✅ Allow only Receptionist
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'receptionist') {
    header("Location: login.html");
    exit;
}

// ===== Process POST Actions =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Create New Booking ---
    if (isset($_POST['create_booking'])) {
        $customerId = intval($_POST['customerId']);
        $roomId = intval($_POST['roomId']);
        $checkIn = $_POST['checkIn'];
        $checkOut = $_POST['checkOut'];
        $numGuests = intval($_POST['numGuests']);
        $totalAmount = doubleval($_POST['totalAmount']);
        $paymentStatus = $_POST['paymentStatus'];
        $offerId = !empty($_POST['offerId']) ? intval($_POST['offerId']) : null;

        $stmt = $conn->prepare("
            INSERT INTO room_booking (customerId, roomId, checkIn, checkOut, numGuests, totalAmount, paymentStatus, offerId, bookingStatus)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Reserved')
        ");
        $stmt->bind_param("iissdssi", $customerId, $roomId, $checkIn, $checkOut, $numGuests, $totalAmount, $paymentStatus, $offerId);

        if ($stmt->execute()) {
            $updateRoom = $conn->prepare("UPDATE room SET status = 'Reserved' WHERE roomId = ?");
            $updateRoom->bind_param("i", $roomId);
            $updateRoom->execute();
            $updateRoom->close();
            $_SESSION['success'] = "✅ Booking created successfully and room marked as Reserved!";
        } else {
            $_SESSION['error'] = "❌ Failed to create booking: " . $stmt->error;
        }
        $stmt->close();

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // --- Update Booking Status ---
    if (isset($_POST['update_status'])) {
        $bookingId = intval($_POST['bookingId']);
        $newStatus = $_POST['bookingStatus'];

        $roomQuery = $conn->prepare("SELECT roomId FROM room_booking WHERE bookingId = ?");
        $roomQuery->bind_param("i", $bookingId);
        $roomQuery->execute();
        $roomQuery->bind_result($roomId);
        $roomQuery->fetch();
        $roomQuery->close();

        $stmt = $conn->prepare("UPDATE room_booking SET bookingStatus = ? WHERE bookingId = ?");
        $stmt->bind_param("si", $newStatus, $bookingId);

        if ($stmt->execute()) {
            $roomStatus = '';
            if ($newStatus === 'CheckedIn') $roomStatus = 'Occupied';
            if (in_array($newStatus, ['CheckedOut', 'Cancelled'])) $roomStatus = 'Available';

            if ($roomStatus) {
                $updateRoom = $conn->prepare("UPDATE room SET status = ? WHERE roomId = ?");
                $updateRoom->bind_param("si", $roomStatus, $roomId);
                $updateRoom->execute();
                $updateRoom->close();
            }
            $_SESSION['success'] = "✅ Booking status updated!";
        } else {
            $_SESSION['error'] = "❌ Failed to update booking status.";
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // --- Add New Customer ---
    if (isset($_POST['add_customer'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);

        $stmt = $conn->prepare("SELECT CustomerId FROM Customer WHERE Email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $_SESSION['error'] = "❌ Customer with this email already exists!";
        } else {
            $randomPassword = bin2hex(random_bytes(4));
            $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
            $stmtInsert = $conn->prepare("INSERT INTO Customer (Name, Email, Contact, Address, Password) VALUES (?, ?, ?, ?, ?)");
            $stmtInsert->bind_param("sssss", $name, $email, $phone, $address, $hashedPassword);
            if ($stmtInsert->execute()) {
                $_SESSION['success'] = "✅ Customer added successfully! Password: <strong>$randomPassword</strong>";
            } else {
                $_SESSION['error'] = "❌ Failed to add customer.";
            }
            $stmtInsert->close();
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // --- Reserve Table ---
    if (isset($_POST['reserve_table'])) {
        $customerId = intval($_POST['customerIdTable']);
        $tableId = intval($_POST['tableId']);
        $numGuests = intval($_POST['numGuestsTable']);

        $stmtCheck = $conn->prepare("SELECT availability FROM tables WHERE table_id=?");
        $stmtCheck->bind_param("i", $tableId);
        $stmtCheck->execute();
        $stmtCheck->bind_result($availability);
        $stmtCheck->fetch();
        $stmtCheck->close();

        if ($availability !== 'Available') {
            $_SESSION['error'] = "❌ Table is already reserved!";
        } else {
            $stmt = $conn->prepare("UPDATE tables SET customer_id=?, availability='Reserved' WHERE table_id=?");
            $stmt->bind_param("ii", $customerId, $tableId);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = "✅ Table reserved successfully!";
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // --- Update Table Status ---
    if (isset($_POST['update_table_status'])) {
        $tableId = intval($_POST['tableId']);
        $newStatus = $_POST['tableStatus'];
        $stmt = $conn->prepare("UPDATE tables SET availability=? WHERE table_id=?");
        $stmt->bind_param("si", $newStatus, $tableId);
        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ Table status updated successfully!";
        } else {
            $_SESSION['error'] = "❌ Failed to update table status.";
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// ===== Fetch Data =====
$bookings = $conn->query("
    SELECT rb.*, c.Name as customerName, r.type, r.price
    FROM room_booking rb
    LEFT JOIN Customer c ON rb.customerId = c.CustomerId
    LEFT JOIN room r ON rb.roomId = r.roomId
    ORDER BY rb.createdAt DESC
");

$rooms = $conn->query("SELECT * FROM room WHERE status = 'Available' ORDER BY roomId ASC");
$customers = $conn->query("SELECT * FROM Customer ORDER BY Name ASC");
$tables = $conn->query("SELECT t.*, c.Name as customerName FROM tables t LEFT JOIN Customer c ON t.customer_id=c.CustomerId ORDER BY t.table_number ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reception Dashboard</title>
<link rel="stylesheet" href="receptionDashboard.css">
<style>
/* ✅ Quick Actions (Common Buttons) */
.quick-actions {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin: 25px auto;
    flex-wrap: wrap;
}
.quick-actions button {
    background-color: #0d6efd;
    color: white;
    padding: 15px 30px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.25s ease-in-out;
}
.quick-actions button:hover {
    background-color: #0b5ed7;
    transform: translateY(-5px);
}
.reseverve-table-btn {
    background-color: #198754;
}
</style>
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

<!-- ✅ Common Buttons -->
<div class="quick-actions">
    <button onclick="window.location.href='addAttendance.php'">📝 Add Attendance</button>
    <button onclick="window.location.href='requestLeave.php'">📨 Request Leave</button>
    <button onclick="window.location.href='viewScheduleall.php'">📅 View Schedule</button>
</div>

<script>
document.getElementById('logoutBtn').addEventListener('click', function(e) {
    e.preventDefault();
    if (confirm("Are you sure you want to log out?")) {
        window.location.href = this.href;
    }
});
</script>

<?php 
if(isset($_SESSION['success'])) {
    echo "<div class='alert success'>{$_SESSION['success']}</div>";
    unset($_SESSION['success']);
}
if(isset($_SESSION['error'])) {
    echo "<div class='alert error'>{$_SESSION['error']}</div>";
    unset($_SESSION['error']);
}
?>

<!-- ===== Existing Navigation & Sections Remain the Same ===== -->
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

<!-- View Reservations / Table Status -->
<section id="viewReservations" class="tab-content">
    <h2>All Table Reservations</h2>
    <table>
        <thead>
            <tr>
                <th>Table No.</th><th>Seats</th><th>Status</th><th>Reserved By</th><th>Update Status</th>
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
                    <td>
                        <form method="POST">
                            <input type="hidden" name="tableId" value="<?= $row['table_id'] ?>">
                            <select name="tableStatus" required>
                                <option value="">Select</option>
                                <option value="Available">Available</option>
                                <option value="Reserved">Reserved</option>
                                <option value="Occupied">Occupied</option>
                            </select>
                            <button type="submit" name="update_table_status">Update</button>
                        </form>
                    </td>
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
</script>
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
<!-- (All your existing tab content remains unchanged below) -->
<!-- ... your sections: viewBookings, newBooking, newCustomer, reserveTable, viewReservations ... -->

<script>
function openTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    document.querySelectorAll('.tab-menu button').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`[onclick="openTab('${tabId}')"]`).classList.add('active');
}
</script>

</div>
</body>
</html>

<?php $conn->close(); ?>
