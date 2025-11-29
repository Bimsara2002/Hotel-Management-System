<?php
session_start();
include 'config.php'; // go up one level

// Fetch selected filters safely
$category = $_GET['category'] ?? 'All';
$mealType = $_GET['meal_type'] ?? 'All';

// ---------- Build SQL dynamically (safe version) ----------
$sql = "SELECT f.foodId, f.foodName, f.discription, f.image, f.category, f.meal_type, MIN(fs.price) AS minPrice
        FROM food f
        LEFT JOIN food_size fs ON f.foodId = fs.foodId
        WHERE f.status = 'have'";

$params = [];
$types = "";

// Add category filter
if ($category !== 'All') {
    $sql .= " AND f.category = ?";
    $params[] = $category;
    $types .= "s";
}

// Add meal type filter
if ($mealType !== 'All') {
    $sql .= " AND f.meal_type = ?";
    $params[] = $mealType;
    $types .= "s";
}

$sql .= " GROUP BY f.foodId, f.foodName, f.discription, f.image, f.category, f.meal_type";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// ---------- Fetch distinct filters ----------
$catResult = $conn->query("SELECT DISTINCT category FROM food WHERE status='have'");
$mealResult = $conn->query("SELECT DISTINCT meal_type FROM food WHERE status='have'");

$categories = [];
$mealTypes = [];

while ($catRow = $catResult->fetch_assoc()) {
    $categories[] = $catRow['category'];
}
while ($mealRow = $mealResult->fetch_assoc()) {
    $mealTypes[] = $mealRow['meal_type'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Our Menu | Omik Restaurant</title>
<style>
:root {
  --primary-color: #e67e22;
  --primary-hover: #cf711f;
  --background-color: #fff;
  --background-soft: #fdf2e9;
  --text-color: #333;
  --btn-color: #007bff;
  --btn-hover: #0056b3;
}

/* ---------- Body & Header ---------- */
body {
  font-family: 'Poppins', Arial, sans-serif;
  background: var(--background-soft);
  margin: 0;
  padding: 0;
  color: var(--text-color);
}

header {
  text-align: center;
  padding: 20px 10px;
  background: linear-gradient(135deg, #cc5400, #004080);
  color: #fff;
}

.menu-title {
  margin: 0;
  font-size: 32px;
  letter-spacing: 1px;
}

/* ---------- Back Button ---------- */
.back-btn-container {
  text-align: center;
  margin: 15px 0;
}

.back-btn {
  padding: 10px 20px;
  background: var(--btn-color);
  color: #fff;
  border: none;
  border-radius: 25px;
  font-weight: bold;
  cursor: pointer;
  transition: 0.3s;
  text-decoration: none;
}
.back-btn:hover {
  background: var(--btn-hover);
  transform: scale(1.05);
}

/* ---------- Filter Bars ---------- */
.filter-section {
  text-align: center;
  margin: 10px 0 20px 0;
}

.filter-label {
  font-size: 18px;
  font-weight: bold;
  color: #222;
  margin-bottom: 8px;
}

.filter-bar {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 10px;
}

.filter-bar a {
  text-decoration: none;
  padding: 8px 18px;
  border-radius: 25px;
  background: var(--primary-color);
  color: #fff;
  transition: 0.3s;
  font-weight: 500;
}

.filter-bar a.active,
.filter-bar a:hover {
  background: var(--primary-hover);
  transform: scale(1.05);
}

/* ---------- Food Cards ---------- */
.menu-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 25px;
  padding: 20px;
  max-width: 1200px;
  margin: auto;
}

.food-card {
  background: var(--background-color);
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 6px 18px rgba(0,0,0,0.1);
  cursor: pointer;
  transition: transform 0.3s, box-shadow 0.3s;
  display: flex;
  flex-direction: column;
}

.food-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

.food-card img {
  width: 100%;
  height: 180px;
  object-fit: cover;
  transition: transform 0.3s;
}

.food-card:hover img {
  transform: scale(1.05);
}

.food-card-content {
  padding: 15px;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.food-card-content h3 {
  margin: 5px 0;
  font-size: 22px;
  color: var(--text-color);
}

.food-card-content p {
  margin: 5px 0;
  color: #555;
  font-size: 14px;
}

.food-card-content .price {
  font-weight: bold;
  color: var(--primary-color);
  margin-top: 10px;
}

/* ---------- No food message ---------- */
.no-food {
  text-align: center;
  font-size: 18px;
  grid-column: 1 / -1;
  color: #777;
}
</style>
</head>
<body>

<header>
  <h1 class="menu-title">🍽 Our Menu</h1>
</header>

<!-- Back Button -->
<div class="back-btn-container">
  <a href="customerDashboard.php" class="back-btn">← Back</a>
</div>

<!-- Category Filter -->
<div class="filter-section">
  <div class="filter-label">Filter by Category</div>
  <div class="filter-bar">
      <a href="?category=All&meal_type=<?= urlencode($mealType) ?>" class="<?= $category=='All' ? 'active' : '' ?>">All</a>
      <?php foreach($categories as $cat): ?>
          <a href="?category=<?= urlencode($cat) ?>&meal_type=<?= urlencode($mealType) ?>" class="<?= $category==$cat ? 'active' : '' ?>">
              <?= htmlspecialchars($cat) ?>
          </a>
      <?php endforeach; ?>
  </div>
</div>

<!-- Meal Type Filter -->
<div class="filter-section">
  <div class="filter-label">Filter by Meal Type</div>
  <div class="filter-bar">
      <a href="?category=<?= urlencode($category) ?>&meal_type=All" class="<?= $mealType=='All' ? 'active' : '' ?>">All</a>
      <?php foreach($mealTypes as $meal): ?>
          <a href="?category=<?= urlencode($category) ?>&meal_type=<?= urlencode($meal) ?>" class="<?= $mealType==$meal ? 'active' : '' ?>">
              <?= htmlspecialchars($meal) ?>
          </a>
      <?php endforeach; ?>
  </div>
</div>

<!-- Food Cards -->
<div class="menu-container">
  <?php if($result->num_rows > 0): ?>
   <?php while($row = $result->fetch_assoc()): ?>
  <?php
    // If image in DB already has folder, use it directly
    $imageFile = $row['image'] ?? '';
    if (empty($imageFile) || !file_exists(__DIR__ . '/' . $imageFile)) {
        $webPath = 'images/no-image.jpg';
    } else {
        $webPath = $imageFile; // Use DB path as is
    }
  ?>
  <div class="food-card" onclick="window.location.href='food_details.php?id=<?= $row['foodId']; ?>'">
    <img src="<?= htmlspecialchars($webPath); ?>" alt="<?= htmlspecialchars($row['foodName']); ?>">
    <div class="food-card-content">
      <h3><?= htmlspecialchars($row['foodName']); ?></h3>
      <p><?= htmlspecialchars($row['discription']); ?></p>
      <p><b>Category:</b> <?= htmlspecialchars($row['category']); ?></p>
      <p><b>Meal Type:</b> <?= htmlspecialchars($row['meal_type']); ?></p>
      <p class="price">From Rs. <?= number_format($row['minPrice'], 2); ?></p>
    </div>
  </div>
<?php endwhile; ?>
s
  <?php else: ?>
    <p class="no-food">No food available for this filter.</p>
  <?php endif; ?>
</div>

</body>
</html>
