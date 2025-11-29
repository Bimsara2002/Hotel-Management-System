<?php
session_start();
include 'config.php';
$customerId = $_SESSION['customerId'] ?? 1;

// Fetch active offers
$offersResult = $conn->query("SELECT * FROM room_offers WHERE status='Active'");

if (isset($_POST['bookRoom'])) {
    $roomId = $_POST['roomId'];
    $checkIn = $_POST['checkIn'];
    $checkOut = $_POST['checkOut'];
    $numGuests = $_POST['numGuests'];
    $offerId = !empty($_POST['offerId']) ? $_POST['offerId'] : NULL;
    $paymentMethod = $_POST['paymentMethod'] ?? 'Cash';

    // Get room price and status
    $stmt = $conn->prepare("SELECT price, status FROM room WHERE roomId=?");
    $stmt->bind_param("i",$roomId);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if($room['status'] !== 'Available') {
        $error = "❌ This room is not available.";
    } else {
        // Calculate days
        $days = max(1,(strtotime($checkOut) - strtotime($checkIn))/(60*60*24));
        $totalAmount = $room['price'] * $days;

        // Apply offer discount if selected
        if($offerId){
            $stmt = $conn->prepare("SELECT discount_percent FROM room_offers WHERE offerId=?");
            $stmt->bind_param("i",$offerId);
            $stmt->execute();
            $offer = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $totalAmount -= ($totalAmount * $offer['discount_percent']/100);
        }

        // Default statuses
        $paymentStatus = 'Pending';
        $bookingStatus = 'Reserved';

        // Insert booking
        $stmt = $conn->prepare("
            INSERT INTO room_booking 
            (customerId, roomId, checkIn, checkOut, numGuests, offerId, totalAmount, paymentStatus, bookingStatus) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iissidsss", 
            $customerId, 
            $roomId, 
            $checkIn, 
            $checkOut, 
            $numGuests, 
            $offerId, 
            $totalAmount, 
            $paymentStatus, 
            $bookingStatus
        );

        if ($stmt->execute()) {
            // Update room status
            $updateRoom = $conn->prepare("UPDATE room SET status='Reserved' WHERE roomId=?");
            $updateRoom->bind_param("i",$roomId);
            $updateRoom->execute();
            $updateRoom->close();

            $success = "🎉 Room booked successfully! Total: Rs. ".number_format($totalAmount,2);
        } else {
            $error = "❌ Booking failed: ".$stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book a Room</title>
<link rel="stylesheet" href="RoomBooking.css">
</head>
<body>

<div class="booking-container">
    <a href="CustomerRoom.php" class="back-btn">⬅ Back</a>
    <h2>Book Your Stay</h2>
    <p class="subtitle">Comfortable rooms. Hassle-free booking. 🌿</p>

    <?php if(isset($success)) echo "<p class='success'>$success</p>"; ?>
    <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="post" id="roomBookingForm">
        <div class="date-guests">
            <div class="form-group">
                <label for="checkIn">Check-In</label>
                <input type="date" name="checkIn" id="checkIn" required>
            </div>
            <div class="form-group">
                <label for="checkOut">Check-Out</label>
                <input type="date" name="checkOut" id="checkOut" required>
            </div>
            <div class="form-group">
                <label for="numGuests">Guests</label>
                <input type="number" name="numGuests" id="numGuests" min="1" value="1" required>
            </div>
        </div>

        <h3>Select an Offer (Optional)</h3>
        <div class="form-group">
            <select name="offerId" id="offerId">
                <option value="">-- No Offer --</option>
                <?php while($offer = $offersResult->fetch_assoc()) {
                    echo "<option value='{$offer['offerId']}' data-discount='{$offer['discount_percent']}'>
                            {$offer['title']} ({$offer['discount_percent']}% off)
                          </option>";
                } ?>
            </select>
        </div>

        <h3>Payment Method</h3>
        <div class="form-group">
            <select name="paymentMethod" id="paymentMethod" required>
                <option value="Cash">Cash</option>
                <option value="Card">Card</option>
                <option value="Online">Online</option>
            </select>
        </div>

        <p>Total Amount: Rs. <span id="totalAmount">0.00</span></p>

        <h3>Select a Room</h3>
        <div class="room-grid">
            <?php
            $roomsResult = $conn->query("SELECT * FROM room WHERE status='Available'");
            while ($r = $roomsResult->fetch_assoc()) {
                echo "
                <div class='room-card' data-roomid='{$r['roomId']}' data-price='{$r['price']}'>
                    <img src='{$r['image']}' alt='{$r['type']}'>
                    <h4>{$r['type']} Room</h4>
                    <p>AC: {$r['ACorNot']} | Price: Rs. ".number_format($r['price'],2)."</p>
                    <p>VIP: {$r['vip']}</p>
                    <span class='select-btn'>Select</span>
                </div>
                ";
            }
            ?>
        </div>

        <input type="hidden" name="roomId" id="selectedRoomId" required>
        <button type="submit" name="bookRoom" class="btn-book">Reserve Now</button>
    </form>
</div>

<script>
// Room selection
const roomCards = document.querySelectorAll('.room-card');
const selectedRoomId = document.getElementById('selectedRoomId');
const totalAmountSpan = document.getElementById('totalAmount');
const numGuestsInput = document.getElementById('numGuests');
const checkInInput = document.getElementById('checkIn');
const checkOutInput = document.getElementById('checkOut');
const offerSelect = document.getElementById('offerId');

let selectedPrice = 0;

roomCards.forEach(card => {
    card.addEventListener('click', () => {
        roomCards.forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        selectedRoomId.value = card.dataset.roomid;
        selectedPrice = parseFloat(card.dataset.price);
        calculateTotal();
    });
});

function calculateTotal() {
    if(!selectedPrice) return;

    // Calculate days
    let checkIn = new Date(checkInInput.value);
    let checkOut = new Date(checkOutInput.value);
    let days = Math.max(1, (checkOut - checkIn)/(1000*60*60*24));
    
    let total = selectedPrice * days;

    // Apply offer
    let offerOpt = offerSelect.options[offerSelect.selectedIndex];
    if(offerOpt && offerOpt.value && offerOpt.dataset.discount) {
        let discount = parseFloat(offerOpt.dataset.discount) || 0;
        total -= total * discount/100;
    }

    totalAmountSpan.textContent = total.toFixed(2);
}

checkInInput.addEventListener('change', calculateTotal);
checkOutInput.addEventListener('change', calculateTotal);
offerSelect.addEventListener('change', calculateTotal);
numGuestsInput.addEventListener('change', calculateTotal);
</script>

</body>
</html>

<?php $conn->close(); ?>
