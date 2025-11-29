<?php
session_start();
include 'config.php';

if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['userId'];

// Fetch first 12 food items
$food_sql = "SELECT * FROM food WHERE status='have' LIMIT 12";
$food_result = $conn->query($food_sql);

// Fetch first 6 rooms
$room_sql = "SELECT * FROM room LIMIT 6";
$room_result = $conn->query($room_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
/* ===== Body & Background ===== */
body {
  min-height: 100vh;
  background: url('https://t4.ftcdn.net/jpg/02/94/26/33/360_F_294263329_1IgvqNgDbhmQNgDxkhlW433uOFuIDar4.jpg') no-repeat center center fixed;
  background-size: cover;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* ===== Sidebar ===== */
.sidebar {
  width: 220px;
  background: #a54501ff;
  color: white;
  padding: 20px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  height: 100vh;
  position: fixed;
  overflow-y: auto;
  border-right: 3px solid #004080;
}
.sidebar h2 { font-size: 22px; margin-bottom: 20px; text-align:center; }
.sidebar ul { list-style: none; padding: 0; }
.sidebar ul li {
  padding: 12px 10px;
  cursor: pointer;
  border-radius: 8px;
  transition: 0.3s;
  font-weight: 500;
}
.sidebar ul li:hover { background: rgba(255,255,255,0.2); }

/* ===== Main Content ===== */
.main-content {
  margin-left: 240px;
  padding: 20px;
}

/* ===== Topbar ===== */
.topbar {
  background: linear-gradient(135deg,#cc5400, #004080);
  padding: 12px 20px;
  color: #fff;
  display: flex;
  gap: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  align-items: center;
}
.topbar a { color: #fff; font-weight:500; text-decoration: none; }
.topbar a:hover { text-decoration: underline; }

/* ===== Food & Room Cards ===== */
.card-dashboard {
  display: grid;
  grid-template-columns: repeat(auto-fit,minmax(220px,1fr));
  gap: 20px;
}
.card {
  background: rgba(255,255,255,0.95);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  transition: 0.3s;
}
.card:hover { transform: translateY(-5px); }
.card img { width:100%; height:160px; object-fit: cover; }
.card .info { padding: 10px; }
.card h3 { margin:0 0 5px 0; font-size: 18px; }
.card p { font-size: 14px; color: #555; height: 40px; overflow: hidden; }
.card span { font-weight: bold; color: #a54501ff; }

/* ===== Section Titles ===== */
.section-title {
  color: white;
  font-size: 22px;
  margin: 20px 0 10px 0;
}

/* ===== Customer Profile Modal ===== */
.modal-content { border-radius: 12px; padding: 20px; }
.modal-body form .form-label { font-weight: 500; margin-top: 10px; }
.modal-body form .form-control { margin-bottom: 12px; }
.modal-header { background: #a54501ff; color:white; border-bottom: 2px solid #004080; border-radius: 12px 12px 0 0; }
.modal-header h5 { margin:0; }

/* ===== Buttons ===== */
.btn-primary { background: #004080; border:none; }
.btn-primary:hover { background: #00264d; }
.btn-secondary { background: #a54501ff; border:none; color:white; }
.btn-secondary:hover { background: #802f00; }

/* ===== Responsive ===== */
@media screen and (max-width:768px){
  .sidebar{width:100%; height:auto; position:relative;}
  .main-content{margin-left:0;}
  .topbar{flex-direction:column;}
}
.topbar {
  background: linear-gradient(135deg,#cc5400, #004080);
  padding: 12px 20px;
  color: #fff;
  display: flex;
  gap: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  align-items: center;
}

.topbar a {
  color: #fff;
  font-weight:500;
  text-decoration: none;
}

.topbar a.btn {
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 14px;
}

</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <h2>Customer Dashboard</h2>
  <ul>
    <li onclick="openPage('search')">Search Available Room</li>
    <li onclick="openPage('viewBooking')">View My Booking</li>
    <li onclick="openPage('order')">Place Order</li>
    <li onclick="openPage('cart')">My Cart</li>
    <li onclick="openPage('feedback')">Give Feedback</li>
    <li onclick="openPage('roomService')">Request Room Services</li>
    <li onclick="openPage('delivery')">View Delivery Status</li>
    <li onclick="openPage('history')">View Order History</li>
    <li onclick="openProfile()">My Profile</li>
  </ul>
</aside>

<!-- Main Content -->
<div class="main-content">

  <!-- Topbar -->
  <!-- Topbar -->
<header class="topbar">
  <a href="#">About Us</a>
  <a href="#">Contact</a>
  <a href="#">Feedback</a>
  <!-- Logout Button -->
  <!-- Logout Button with confirmation -->
<a href="#" onclick="confirmLogout()" class="btn btn-secondary" style="margin-left:auto;">Logout</a>
<script>
function confirmLogout(){
  if(confirm("Are you sure you want to logout?")){
    window.location.href='logout.php';
  }
}
</script>
</header>


  <!-- Food Section -->
  <div class="section-title">Available Foods</div>
  <main class="card-dashboard">
    <?php
    if ($food_result->num_rows > 0) {
      while($row = $food_result->fetch_assoc()) {
        echo '<div class="card">';
        echo '<img src="' . $row['image'] . '" alt="' . $row['foodName'] . '">';
        echo '<div class="info">';
        echo '<h3>' . $row['foodName'] . '</h3>';
        echo '<p>' . $row['discription'] . '</p>';
        echo '<span>Rs. ' . $row['price'] . '</span>';
        echo '</div></div>';
      }
    } else {
      echo "<p style='color:white; padding:20px'>No food available.</p>";
    }
    ?>
  </main>

  <!-- Room Section -->
  <div class="section-title">Available Rooms</div>
  <main class="card-dashboard">
    <?php
    if ($room_result->num_rows > 0) {
      while($row = $room_result->fetch_assoc()) {
        echo '<div class="card">';
        echo '<img src="' . $row['image'] . '" alt="Room">';
        echo '<div class="info">';
        echo '<h3>' . $row['type'] . ' Room (' . $row['ACorNot'] . ')</h3>';
        echo '<p>Status: ' . $row['status'] . ', VIP: ' . $row['vip'] . '</p>';
        echo '<span>Rs. ' . $row['price'] . '</span>';
        echo '</div></div>';
      }
    } else {
      echo "<p style='color:white; padding:20px'>No rooms available.</p>";
    }
    ?>
  </main>

</div>

<!-- Customer Profile Modal -->
<div class="modal fade" id="customerProfileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">My Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="customerProfileForm">
          <label class="form-label">Name *</label>
          <input type="text" class="form-control" name="name" required>

          <label class="form-label">Email *</label>
          <input type="email" class="form-control" name="email" required>

          <label class="form-label">Contact</label>
          <input type="text" class="form-control" name="contact">

          <label class="form-label">Address</label>
          <textarea class="form-control" name="address" rows="2"></textarea>

          <label class="form-label">New Password</label>
          <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current">

          <button type="submit" class="btn btn-primary w-100">Update Profile</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Navigation redirects
function openPage(action){
  switch(action){
    case "search": window.location.href='CustomerRoom.php'; break;
    case "reserve": window.location.href='Reservation.html'; break;
    case "viewBooking": window.location.href='ViewMyBooking.php'; break;
    case "cart": window.location.href='cart.php'; break;
    case "feedback": window.location.href='feedback.php'; break;
    case "order": window.location.href='menu.php'; break;
    case "roomService": window.location.href='RoomServices.php'; break;
    case "delivery": window.location.href='deliveryStatus.php'; break;
    case "history": window.location.href='orders.php'; break;
    default: alert("Unknown action!");
  }
}

// Open customer profile modal and load data
function openProfile(){
  const modal = new bootstrap.Modal(document.getElementById('customerProfileModal'));
  modal.show();

  fetch('customer_profile.php')
    .then(res=>res.json())
    .then(data=>{
      if(data.status==='error'){ alert(data.message); return; }
      const form = document.getElementById('customerProfileForm');
      form.name.value = data.Name;
      form.email.value = data.Email;
      form.contact.value = data.Contact;
      form.address.value = data.Address;
      form.password.value = '';
    });
}

// Update profile
document.getElementById('customerProfileForm').addEventListener('submit', function(e){
  e.preventDefault();
  const formData = new FormData(this);
  fetch('customer_profile.php', { method:'POST', body:formData })
    .then(res=>res.json())
    .then(data=>{
      alert(data.message);
      if(data.status==='success'){
        const modal = bootstrap.Modal.getInstance(document.getElementById('customerProfileModal'));
        modal.hide();
      }
    });
});
</script>
</body>
</html>
<?php $conn->close(); ?>
