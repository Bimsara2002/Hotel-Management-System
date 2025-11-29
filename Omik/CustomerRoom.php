<?php
include 'config.php';

// Function to get image path with fallback
function getImagePath($imageFile) {
    if (empty($imageFile) || !file_exists(__DIR__.'/uploads/'.$imageFile)) {
        return 'images/no-image.jpg'; // fallback image
    }
    return 'uploads/'.$imageFile;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer View For Rooms</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
/* ===== General ===== */
body { font-family: 'Inter', sans-serif; background: #f5f6fa; margin: 0; padding: 0; color: #333; }
a { text-decoration: none; }

/* ===== Back Button ===== */
.back-btn { display: inline-block; margin: 20px; padding: 10px 18px; background: #004080; color: #fff; border-radius: 8px; font-weight: 600; transition: 0.3s; }
.back-btn:hover { background: #00264d; }

/* ===== Room Offers ===== */
.room-offers { max-width: 1000px; margin: 20px auto; }
.room-offers h2 { color: #004080; margin-bottom: 15px; text-align: center; }
.offer-card { background: linear-gradient(135deg, #f7a4a4ff, #ffffff); border-left: 6px solid #fd2222ff; padding: 20px; border-radius: 12px; margin-bottom: 15px; transition: 0.3s; }
.offer-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
.offer-card h3 { margin-top: 0; font-size: 18px; color: #004080; }
.offer-card p { margin: 8px 0; }
.offer-card .valid { font-weight: 600; color: #d9534f; }

/* ===== Search Room Form ===== */
.search-room { background: #fff; padding: 25px; margin: 20px auto; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); max-width: 900px; }
.search-room h2 { text-align: center; margin-bottom: 20px; color: #004080; }
.search-room form { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }
.search-room label { font-weight: 600; margin-bottom: 5px; display: block; }
.search-room select, .search-room input { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; font-size: 14px; }
.search-room button { grid-column: span 2; padding: 12px; background: #28a745; color: #fff; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.3s; width: 350px; height: 45px; margin-top: 20px; margin-left: 100px; }
.search-room button:hover { background: #1e7e34; }

/* ===== Room Cards ===== */
.room-cards { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; max-width: 1200px; margin: 20px auto; }
.room-card { background: #fff; border-radius: 12px; width: 280px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); overflow: hidden; transition: 0.3s; text-align: center; }
.room-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
.room-card img { width: 100%; height: 180px; object-fit: cover; }
.room-card h3 { margin: 12px 0 6px 0; color: #004080; font-size: 18px; }
.room-card p { font-size: 14px; color: #555; margin-bottom: 10px; }
.room-card .price { font-weight: 600; color: #d9534f; display: block; margin-bottom: 12px; }
.room-card button { margin-bottom: 15px; padding: 10px 18px; background: #004080; color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; }
.room-card button:hover { background: #00264d; }

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .room-cards { flex-direction: column; align-items: center; }
  .search-room form { grid-template-columns: 1fr; }
  .search-room button { grid-column: span 1; margin-left: 0; width: 100%; }
}
</style>
</head>
<body>

<!-- Back Button -->
<a href="CustomerDashboard.php" class="back-btn">⬅ Back to Dashboard</a>

<!-- Room Offers -->
<div class="room-offers">
  <h2>Current Room Offers</h2>
  <?php
  $today = date('Y-m-d');
  $offerQuery = "SELECT * FROM room_offers WHERE status='Active' AND valid_from <= '$today' AND valid_to >= '$today'";
  $offerResult = $conn->query($offerQuery);

  if($offerResult->num_rows > 0){
      while($offer = $offerResult->fetch_assoc()){
          echo '<div class="offer-card">
                  <h3>'.$offer['title'].' ('.$offer['roomType'].' Room)</h3>
                  <p>'.$offer['description'].'</p>
                  <span class="valid">Discount: '.$offer['discount_percent'].'% | Valid: '.$offer['valid_from'].' to '.$offer['valid_to'].'</span>
                </div>';
      }
  } else {
      echo "<p style='text-align:center;'>No active offers at the moment.</p>";
  }
  ?>
</div>

<!-- Search Form -->
<section class="search-room" id="searchRoom">
  <h2>Search Available Rooms</h2>
  <form method="POST">
    <div>
      <label>AC / Non-AC:</label>
      <select name="ACorNot">
        <option value="">Any</option>
        <option value="AC">AC</option>
        <option value="Non-AC">Non-AC</option>
      </select>
    </div>
    <div>
      <label>Room Type:</label>
      <select name="type">
        <option value="">Any</option>
        <option value="Single">Single</option>
        <option value="Double">Double</option>
        <option value="Suite">Suite</option>
      </select>
    </div>
    <div>
      <label>VIP Package:</label>
      <select name="vip">
        <option value="">Any</option>
        <option value="vip">VIP</option>
        <option value="standard">Standard</option>
      </select>
    </div>
    <div>
      <label>Max Price:</label>
      <input type="number" name="maxPrice" placeholder="Enter max price">
    </div>
    <button type="submit" name="searchRoom">Search</button>
  </form>
</section>

<!-- Room Results -->
<div class="room-cards">
<?php
if (isset($_POST['searchRoom'])) {
    $ACorNot = $_POST['ACorNot'];
    $type = $_POST['type'];
    $vip = $_POST['vip'];
    $maxPrice = $_POST['maxPrice'];

    $query = "SELECT * FROM room WHERE status='Available'";
    if ($ACorNot) $query .= " AND ACorNot='$ACorNot'";
    if ($type) $query .= " AND type='$type'";
    if ($vip) $query .= " AND vip='$vip'";
    if ($maxPrice) $query .= " AND price <= $maxPrice";

    $result = $conn->query($query);
} else {
    $result = $conn->query("SELECT * FROM room WHERE status='Available' LIMIT 6");
}

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $imgPath = getImagePath($row['image']);
        echo '<div class="room-card">
                <img src="'.$imgPath.'" alt="'.htmlspecialchars($row['type']).'">
                <h3>'.$row['type'].' Room</h3>
                <p>'.($row['ACorNot']=="AC" ? "Air Conditioned" : "Non-AC").' | '.ucfirst($row['vip']).' Package</p>
                <span class="price">Rs. '.number_format($row['price'],2).' / night</span>
                <button onclick="window.location.href=\'RoomBooking.php?roomId='.$row['roomId'].'\'">Book Now</button>
              </div>';
    }
} else {
    echo "<p style='text-align:center;color:#004080;'>No rooms available at the moment.</p>";
}

$conn->close();
?>
</div>

</body>
</html>
