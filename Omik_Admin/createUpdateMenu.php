<?php
include 'config.php';

// ================= Add Food =================
if (isset($_POST['add_food'])) {
    $foodName = $_POST['foodName'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $status = $_POST['status'];
    $category = $_POST['category'];
    $meal_type = $_POST['meal_type'];

    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $targetDir = "uploads/";
        if (!file_exists($targetDir)) mkdir($targetDir);
        $image = $targetDir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $image);
    }

    $stmt = $conn->prepare("INSERT INTO food (foodName, discription, price, status, image, category, meal_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdssss", $foodName, $description, $price, $status, $image, $category, $meal_type);
    $stmt->execute();
    $foodId = $stmt->insert_id;

    if (isset($_POST['size']) && isset($_POST['sizePrice'])) {
        $sizes = $_POST['size'];
        $prices = $_POST['sizePrice'];
        for ($i = 0; $i < count($sizes); $i++) {
            if (!empty($sizes[$i]) && !empty($prices[$i])) {
                $stmt2 = $conn->prepare("INSERT INTO food_size (foodId, size, price) VALUES (?, ?, ?)");
                $stmt2->bind_param("isd", $foodId, $sizes[$i], $prices[$i]);
                $stmt2->execute();
            }
        }
    }
    echo "<script>alert('✅ Food added successfully!');</script>";
}

// ================= Update Food =================
if (isset($_POST['update_food'])) {
    $foodId = intval($_POST['foodId']);
    $foodName = $_POST['foodName'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $category = $_POST['category'];
    $meal_type = $_POST['meal_type'];

    // Handle image update
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $targetDir = "uploads/";
        if (!file_exists($targetDir)) mkdir($targetDir);
        $image = $targetDir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $image);
        $stmt = $conn->prepare("UPDATE food SET foodName=?, discription=?, status=?, category=?, meal_type=?, image=? WHERE foodId=?");
        $stmt->bind_param("ssssssi", $foodName, $description, $status, $category, $meal_type, $image, $foodId);
    } else {
        $stmt = $conn->prepare("UPDATE food SET foodName=?, discription=?, status=?, category=?, meal_type=? WHERE foodId=?");
        $stmt->bind_param("sssssi", $foodName, $description, $status, $category, $meal_type, $foodId);
    }
    $stmt->execute();

    if (isset($_POST['sizeId']) && isset($_POST['size']) && isset($_POST['sizePrice'])) {
        $sizeIds = $_POST['sizeId'];
        $sizes = $_POST['size'];
        $prices = $_POST['sizePrice'];
        for ($i = 0; $i < count($sizeIds); $i++) {
            if ($sizeIds[$i] == 0) {
                $stmt2 = $conn->prepare("INSERT INTO food_size (foodId, size, price) VALUES (?, ?, ?)");
                $stmt2->bind_param("isd", $foodId, $sizes[$i], $prices[$i]);
                $stmt2->execute();
            } else {
                $stmt2 = $conn->prepare("UPDATE food_size SET size=?, price=? WHERE sizeId=?");
                $stmt2->bind_param("sdi", $sizes[$i], $prices[$i], $sizeIds[$i]);
                $stmt2->execute();
            }
        }
    }
    echo "<script>alert('✅ Food updated successfully!');</script>";
}

// ================= Delete Size / Food =================
if (isset($_POST['delete_food_size'])) {
    $sizeId = intval($_POST['sizeId']);
    $stmt = $conn->prepare("DELETE FROM food_size WHERE sizeId=?");
    $stmt->bind_param("i", $sizeId);
    $stmt->execute();
    echo "success";
    exit;
}
if (isset($_POST['delete_entire_food'])) {
    $foodId = intval($_POST['foodId']);
    $stmt1 = $conn->prepare("DELETE FROM food_size WHERE foodId=?");
    $stmt1->bind_param("i", $foodId);
    $stmt1->execute();
    $stmt2 = $conn->prepare("DELETE FROM food WHERE foodId=?");
    $stmt2->bind_param("i", $foodId);
    $stmt2->execute();
    echo "success";
    exit;
}

// ================= Fetch Foods =================
$sql = "SELECT f.foodId, f.foodName, f.discription, f.status, f.category, f.image, f.meal_type, fs.sizeId, fs.size, fs.price
        FROM food f
        JOIN food_size fs ON f.foodId = fs.foodId
        ORDER BY f.foodId ASC";
$result = $conn->query($sql);

$foods = [];
while ($row = $result->fetch_assoc()) {
    $foodId = $row['foodId'];
    if (!isset($foods[$foodId])) {
        $foods[$foodId] = [
            'foodId' => $foodId,
            'foodName' => $row['foodName'],
            'description' => $row['discription'],
            'status' => $row['status'],
            'category' => $row['category'],
            'meal_type' => $row['meal_type'],
            'image' => $row['image'],
            'sizes' => []
        ];
    }
    $foods[$foodId]['sizes'][] = [
        'sizeId' => $row['sizeId'],
        'size' => $row['size'],
        'price' => $row['price']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menu Management - Omik Restaurant</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.food-cards { display:flex; flex-wrap:wrap; gap:20px; margin-top:20px; }
.food-card { width:250px; border:1px solid #ccc; border-radius:12px; padding:10px; cursor:pointer; transition:0.2s; }
.food-card:hover { transform:translateY(-5px); box-shadow:0 5px 15px rgba(0,0,0,0.2); }
.food-card img { width:100%; height:150px; object-fit:cover; border-radius:10px; }
.badge { margin:2px 5px 2px 0; }
.sizeRow { display:flex; gap:10px; margin-bottom:10px; align-items:center; }
.sizeRow input { flex:1; }
.removeBtn { background:#dc3545; color:#fff; border:none; padding:3px 8px; cursor:pointer; border-radius:5px; }
.back-dashboard { position: absolute; top: 20px; right: 20px; }
</style>
</head>
<body class="p-4">

<div class="container position-relative">
    <h1>🍴 Menu Management</h1>    
    <a href="RestaurantManagerDashboard.php" class="btn btn-outline-primary back-dashboard">&larr; Back to Dashboard</a>

    <!-- Filter Section -->
    <div class="row my-3 g-2">
        <div class="col-md-3">
            <input type="text" id="filterName" class="form-control" placeholder="Search Food Name" onkeyup="applyFilter()">
        </div>
        <div class="col-md-3">
            <select id="filterMealType" class="form-select" onchange="applyFilter()">
                <option value="">All Meal Types</option>
                <option>Lunch</option>
                <option>Breakfast</option>
                <option>Dinner</option>
                <option>Anytime</option>
            </select>
        </div>
        <div class="col-md-3">
            <select id="filterCategory" class="form-select" onchange="applyFilter()">
                <option value="">All Categories</option>
                <option>Chineese</option>
                <option>Pizza</option>
                <option>Burger</option>
                <option>Sides</option>
                <option>Drinks</option>
                <option>Dessert</option>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-secondary w-100" onclick="resetFilter()">Reset Filter</button>
        </div>
    </div>

    <button class="btn btn-success my-2" data-bs-toggle="modal" data-bs-target="#addFoodModal">Add New Food</button>

    <div class="food-cards" id="foodCardsContainer">
        <?php foreach($foods as $food) { ?>
            <div class="food-card" id="foodCard<?php echo $food['foodId']; ?>" 
                 data-name="<?php echo strtolower($food['foodName']); ?>"
                 data-meal="<?php echo strtolower($food['meal_type']); ?>"
                 data-category="<?php echo strtolower($food['category']); ?>"
                 onclick='openUpdateModal(<?php echo json_encode($food); ?>)'>
                <?php if($food['image']): ?><img src="<?php echo $food['image']; ?>"><?php endif; ?>
                <h5><?php echo htmlspecialchars($food['foodName']); ?></h5>
                <p><?php echo htmlspecialchars($food['description']); ?></p>
                <span class="badge bg-<?php echo $food['status']=='have'?'success':'danger'; ?>"><?php echo ucfirst($food['status']); ?></span>
                <span class="badge bg-secondary"><?php echo $food['category']; ?></span>
                <span class="badge bg-info"><?php echo $food['meal_type']; ?></span>
                <ul>
                    <?php foreach($food['sizes'] as $s) { ?>
                        <li><?php echo $s['size'] . ' - $' . number_format($s['price'],2); ?></li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
    </div>
</div>

<!-- Add Food Modal -->
<div class="modal fade" id="addFoodModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content p-3">
<h5>Add New Food</h5>
<form method="POST" enctype="multipart/form-data">
    <input class="form-control my-2" type="text" name="foodName" placeholder="Food Name" required>
    <textarea class="form-control my-2" name="description" placeholder="Description" required></textarea>
    <input class="form-control my-2" type="number" step="0.01" name="price" placeholder="Base Price" required>
    <select class="form-select my-2" name="status">
        <option value="have">Have</option>
        <option value="not have">Not Have</option>
    </select>
    <select class="form-select my-2" name="category">
        <option>Pizza</option><option>Burger</option><option>Sides</option><option>Drinks</option><option>Dessert</option>
    </select>
    <select class="form-select my-2" name="meal_type">
        <option>Lunch</option><option>Breakfast</option><option>Dinner</option><option>Anytime</option>
    </select>
    <input class="form-control my-2" type="file" name="image" accept="image/*">
    <div id="addSizeContainer"></div>
    <button type="button" class="btn btn-outline-primary mb-2" onclick="addSizeRow('addSizeContainer')">➕ Add Size</button>
    <button type="submit" name="add_food" class="btn btn-success w-100">Add Food</button>
</form>
</div></div></div>

<!-- Update Food Modal -->
<div class="modal fade" id="updateFoodModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-lg">
<div class="modal-content p-3">
<h5>Update Food</h5>
<form method="POST" id="updateFoodForm" enctype="multipart/form-data">
    <input type="hidden" name="foodId" id="updateFoodId">
    <input class="form-control my-2" type="text" name="foodName" id="updateFoodName" placeholder="Food Name" required>
    <textarea class="form-control my-2" name="description" id="updateDescription" placeholder="Description" required></textarea>
    <select class="form-select my-2" name="status" id="updateStatus">
        <option value="have">Have</option><option value="not have">Not Have</option>
    </select>
    <select class="form-select my-2" name="category" id="updateCategory">
        <option>Pizza</option><option>Burger</option><option>Sides</option><option>Drinks</option><option>Dessert</option>
    </select>
    <select class="form-select my-2" name="meal_type" id="updateMealType">
        <option>Lunch</option><option>Breakfast</option><option>Dinner</option><option>Anytime</option>
    </select>
    <input class="form-control my-2" type="file" name="image" accept="image/*">
    <div id="updateSizeContainer"></div>
    <button type="button" class="btn btn-outline-primary mb-2" onclick="addSizeRow('updateSizeContainer')">➕ Add Size</button>
    <button type="submit" name="update_food" class="btn btn-success w-100">Update Food</button>
    <button type="button" class="btn btn-danger w-100 mt-2" onclick="deleteFoodModal()">🗑 Delete Food</button>
</form>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function addSizeRow(containerId){
    const container = document.getElementById(containerId);
    const div = document.createElement('div');
    div.classList.add('sizeRow');
    div.innerHTML = `
        <input type="hidden" name="sizeId[]" value="0">
        <input class="form-control" type="text" name="size[]" placeholder="Size" required>
        <input class="form-control" type="number" step="0.01" name="sizePrice[]" placeholder="Price" required>
        <button type="button" class="removeBtn" onclick="this.parentElement.remove()">❌</button>
    `;
    container.appendChild(div);
}

function openUpdateModal(food){
    document.getElementById('updateFoodId').value = food.foodId;
    document.getElementById('updateFoodName').value = food.foodName;
    document.getElementById('updateDescription').value = food.description;
    document.getElementById('updateStatus').value = food.status;
    document.getElementById('updateCategory').value = food.category;
    document.getElementById('updateMealType').value = food.meal_type;

    const container = document.getElementById('updateSizeContainer');
    container.innerHTML = '';
    food.sizes.forEach(s => {
        const div = document.createElement('div');
        div.classList.add('sizeRow');
        div.innerHTML = `
            <input type="hidden" name="sizeId[]" value="${s.sizeId}">
            <input class="form-control" type="text" name="size[]" value="${s.size}" required>
            <input class="form-control" type="number" step="0.01" name="sizePrice[]" value="${s.price}" required>
            <button type="button" class="removeBtn" onclick="this.parentElement.remove()">❌</button>
        `;
        container.appendChild(div);
    });

    const modal = new bootstrap.Modal(document.getElementById('updateFoodModal'));
    modal.show();
}

function deleteFoodModal() {
    const foodId = document.getElementById('updateFoodId').value;
    if(confirm("Are you sure you want to delete this food?")) {
        const formData = new FormData();
        formData.append('delete_entire_food', true);
        formData.append('foodId', foodId);

        fetch('', { method:'POST', body:formData })
        .then(res=>res.text())
        .then(data=>{
            if(data.trim()==='success'){
                alert('✅ Food deleted successfully!');
                document.getElementById('foodCard'+foodId).remove();
                const modalEl = document.getElementById('updateFoodModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
            } else {
                alert('❌ Something went wrong!');
            }
        })
        .catch(err=>alert('❌ Error: '+err));
    }
}

// ============ Filter Logic ============
function applyFilter(){
    const nameFilter = document.getElementById('filterName').value.toLowerCase();
    const mealFilter = document.getElementById('filterMealType').value.toLowerCase();
    const categoryFilter = document.getElementById('filterCategory').value.toLowerCase();

    const cards = document.querySelectorAll('.food-card');
    cards.forEach(card => {
        const name = card.getAttribute('data-name');
        const meal = card.getAttribute('data-meal');
        const category = card.getAttribute('data-category');

        if ((name.includes(nameFilter)) &&
            (mealFilter === '' || meal === mealFilter) &&
            (categoryFilter === '' || category === categoryFilter)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function resetFilter(){
    document.getElementById('filterName').value = '';
    document.getElementById('filterMealType').value = '';
    document.getElementById('filterCategory').value = '';
    applyFilter();
}
</script>
</body>
</html>
