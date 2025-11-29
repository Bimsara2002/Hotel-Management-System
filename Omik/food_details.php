<?php
session_start();
include 'config.php';

// Validate and sanitize foodId
$foodId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($foodId <= 0) {
    die("Invalid food item.");
}

// ✅ Fetch food info safely
$food_sql = "SELECT * FROM food WHERE foodId = ?";
$stmt = $conn->prepare($food_sql);
$stmt->bind_param("i", $foodId);
$stmt->execute();
$food = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$food) {
    die("Food item not found.");
}

$success = '';

// ✅ Handle "Add to Cart"
if (isset($_POST['add_to_cart'])) {
    $size = $_POST['size'] ?? '';
    $quantity = intval($_POST['qty'] ?? 1);

    // Validate size & quantity
    if ($size === '' || $quantity < 1) {
        $success = "Please select a valid size and quantity.";
    } else {
        // ✅ Get price of selected size
        $price_sql = "SELECT price FROM food_size WHERE foodId=? AND size=?";
        $stmt = $conn->prepare($price_sql);
        $stmt->bind_param("is", $foodId, $size);
        $stmt->execute();
        $price_row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$price_row) {
            $success = "Invalid size selected.";
        } else {
            $price = $price_row['price'];
            $foodName = $food['foodName'] . " ($size)";
            $foodImage = $food['image'];

            $userId = $_SESSION['userId'] ?? null;  // ✅ Logged-in user
            $sessionId = session_id();              // For guest users

            if ($userId) {
                $stmt = $conn->prepare("
                    INSERT INTO cart (foodId, foodName, foodImage, quantity, price, userId) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("issdis", $foodId, $foodName, $foodImage, $quantity, $price, $userId);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO cart (foodId, foodName, foodImage, quantity, price, sessionId) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("issdis", $foodId, $foodName, $foodImage, $quantity, $price, $sessionId);
            }

            if ($stmt->execute()) {
                $success = "Added to cart successfully!";
            } else {
                $success = "Failed to add to cart. Please try again.";
            }
            $stmt->close();
        }
    }
}

// ✅ Fetch available sizes and prices
$sizes_sql = "SELECT size, price FROM food_size WHERE foodId = ?";
$stmt = $conn->prepare($sizes_sql);
$stmt->bind_param("i", $foodId);
$stmt->execute();
$sizes = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($food['foodName']); ?> - Details</title>
<link rel="stylesheet" href="food_detail.css">
<style>
body {
  font-family: 'Poppins', Arial, sans-serif;
  background: #fdf2e9;
  margin: 10px;
  margin-top: 30px;
  padding: 0;
}

.details-container {
    margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 40px;
  padding: 40px;
  max-width: 1000px;
  margin: auto;
  background: #fff;
  border-radius: 15px;
  box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}

.details-container img {
  width: 350px;
  height: 250px;
  border-radius: 10px;
  object-fit: cover;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.details-info {
  flex: 1;
  min-width: 280px;
}

.details-info h2 {
  font-size: 28px;
  margin-bottom: 10px;
  color: #333;
}

.details-info p {
  font-size: 15px;
  color: #555;
  line-height: 1.5;
  margin-bottom: 20px;
}

form {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

select, input[type="number"] {
  padding: 8px;
  font-size: 14px;
  border-radius: 6px;
  border: 1px solid #ccc;
}

button {
  padding: 10px 20px;
  border: none;
  border-radius: 25px;
  font-weight: bold;
  cursor: pointer;
  transition: 0.3s;
}

button[name="add_to_cart"] {
  background-color: #e67e22;
  color: white;
}

button[name="add_to_cart"]:hover {
  background-color: #cf711f;
  transform: scale(1.05);
}

.back-btn {
  background-color: #007bff;
  color: #fff;
}

.back-btn:hover {
  background-color: #0056b3;
  transform: scale(1.05);
}
</style>
</head>

<body>

<div class="details-container">
  <img src="<?php echo htmlspecialchars($food['image']); ?>" alt="<?php echo htmlspecialchars($food['foodName']); ?>">
  <div class="details-info">
      <h2><?php echo htmlspecialchars($food['foodName']); ?></h2>
      <p><?php echo htmlspecialchars($food['discription']); ?></p>
      <p><strong>Category:</strong> <?php echo htmlspecialchars($food['category']); ?></p>
      <p><strong>Meal Type:</strong> <?php echo htmlspecialchars($food['meal_type']); ?></p>

      <!-- Success message -->
      <?php if($success != ''): ?>
          <script>alert('<?php echo addslashes($success); ?>');</script>
      <?php endif; ?>

      <!-- Add to Cart Form -->
      <form method="POST">
          <label for="size">Choose Size:</label>
          <select name="size" id="size" required>
              <?php while($row = $sizes->fetch_assoc()): ?>
                  <option value="<?php echo htmlspecialchars($row['size']); ?>">
                      <?php echo htmlspecialchars($row['size']); ?> - Rs. <?php echo number_format($row['price'], 2); ?>
                  </option>
              <?php endwhile; ?>
          </select>

          <label for="qty">Quantity:</label>
          <input type="number" name="qty" id="qty" value="1" min="1" required>

          <button type="submit" name="add_to_cart">Add to Cart</button>
      </form>
<a href="menu.php">
      <button type="button" class="back-btn" >← Back</button></a>
  </div>
</div>

</body>
</html>
