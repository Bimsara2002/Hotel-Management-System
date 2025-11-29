function openPage(action) {
  switch(action) {
    case "search":
      alert("Redirecting to Search Available Room page...");
      window.location.href = 'CustomerRoom.html';
      break;

    case "reserve":
      alert("Redirecting to Reservation page...");
      window.location.href = 'Reservation.html';
      break;

    case "viewBooking":
      alert("Redirecting to View Booking page...");
      window.location.href = 'ViewBooking.html';
      break;

    case "updateBooking":
      alert("Redirecting to Update Booking page...");
      window.location.href = 'UpdateBooking.html';
      break;

    case "cancelBooking":
      alert("Redirecting to Cancel Booking page...");
      window.location.href = 'CancelBooking.html';
      break;

    case "feedback":
      alert("Redirecting to Feedback page...");
      window.location.href = 'Feedback.html';
      break;

    case "order":
      alert("Redirecting to Place Order page...");
      window.location.href = 'PlaceOrder.html';
      break;

    case "roomService":
      alert("Redirecting to Room Services page...");
      window.location.href = 'RoomServices.html';
      break;

    case "delivery":
      alert("Redirecting to Delivery Status page...");
      window.location.href = 'DeliveryStatus.html';
      break;

    case "history":
      alert("Redirecting to Order History page...");
      window.location.href = 'OrderHistory.html';
      break;

    default:
      alert("Unknown action!");
  }
}
