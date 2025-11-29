// ==========================
// Room Booking JS
// ==========================

// Elements
const roomCards = document.querySelectorAll('.room-card');
const selectedRoomInput = document.getElementById('selectedRoomId');
const offerSelect = document.getElementById('offerId');
const totalAmountSpan = document.getElementById('totalAmount');
const checkInInput = document.getElementById('checkIn');
const checkOutInput = document.getElementById('checkOut');
const numGuestsInput = document.getElementById('numGuests');
const bookingForm = document.getElementById('roomBookingForm');

// ==========================
// Room selection
// ==========================
roomCards.forEach(card => {
    card.addEventListener('click', () => {
        roomCards.forEach(c => c.classList.remove('selected')); // remove highlight
        card.classList.add('selected'); // highlight selected room
        selectedRoomInput.value = card.getAttribute('data-roomid'); // save room id
        calculateTotal(); // update price
    });
});

// ==========================
// Total amount calculation
// ==========================
function calculateTotal() {
    const checkInVal = checkInInput.value;
    const checkOutVal = checkOutInput.value;
    const selectedRoom = document.querySelector('.room-card.selected');

    // Reset total if missing inputs
    if (!checkInVal || !checkOutVal || !selectedRoom) {
        totalAmountSpan.textContent = "0.00";
        return;
    }

    const checkIn = new Date(checkInVal);
    const checkOut = new Date(checkOutVal);

    // Validate dates
    if (isNaN(checkIn) || isNaN(checkOut) || checkOut <= checkIn) {
        totalAmountSpan.textContent = "0.00";
        return;
    }

    // Calculate days
    const days = Math.max(1, (checkOut - checkIn) / (1000 * 60 * 60 * 24));

    // Base price
    let total = parseFloat(selectedRoom.dataset.price) * days;

    // If price is per guest per night → uncomment this line
    // let total = parseFloat(selectedRoom.dataset.price) * days * (parseInt(numGuestsInput.value) || 1);

    // Apply discount
    const offerOption = offerSelect.selectedOptions[0];
    if (offerOption && offerOption.dataset.discount) {
        const discount = parseFloat(offerOption.dataset.discount);
        total -= (total * discount / 100);
    }

    // Update UI
    totalAmountSpan.textContent = total.toFixed(2);
}

// ==========================
// Event listeners
// ==========================
checkInInput.addEventListener('change', calculateTotal);
checkOutInput.addEventListener('change', calculateTotal);
numGuestsInput.addEventListener('input', calculateTotal);
offerSelect.addEventListener('change', calculateTotal);

// ==========================
// Form validation
// ==========================
bookingForm.addEventListener('submit', function (e) {
    const checkIn = new Date(checkInInput.value);
    const checkOut = new Date(checkOutInput.value);

    // Validate dates
    if (isNaN(checkIn) || isNaN(checkOut) || checkOut <= checkIn) {
        e.preventDefault();
        alert('🚨 Check-out date must be after check-in date!');
        return;
    }

    // Validate room
    if (!selectedRoomInput.value) {
        e.preventDefault();
        alert('🚨 Please select a room!');
        return;
    }
});
