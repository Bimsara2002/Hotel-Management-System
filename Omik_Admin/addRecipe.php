<?php
include 'config.php';

// Handle Recipe Add Form
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_recipe'])) {
    $food_id = $_POST['food_id'];
    $recipe_name = $_POST['recipe_name'];
    $description = $_POST['description'];
    $ingredients = $_POST['ingredients'];
    $instructions = $_POST['instructions'];
    $created_by = $_POST['created_by'];

    $sql = "INSERT INTO Recipe (food_id, recipe_name, description, ingredients, instructions, created_by)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssi", $food_id, $recipe_name, $description, $ingredients, $instructions, $created_by);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Recipe added successfully!'); window.location.href='manageRecipe.php';</script>";
    } else {
        echo "<script>alert('❌ Error adding recipe');</script>";
    }
    $stmt->close();
}

// Fetch food list
$foodQuery = "SELECT foodId, foodName FROM food WHERE status='Have'";
$foods = $conn->query($foodQuery);

// Handle filters
$searchName = $_GET['searchName'] ?? '';
$searchFood = $_GET['searchFood'] ?? '';

$recipeQuery = "SELECT r.*, f.foodName FROM Recipe r JOIN food f ON r.food_id = f.foodId WHERE 1";

if (!empty($searchName)) {
    $recipeQuery .= " AND r.recipe_name LIKE '%" . $conn->real_escape_string($searchName) . "%'";
}

if (!empty($searchFood)) {
    $recipeQuery .= " AND f.foodName LIKE '%" . $conn->real_escape_string($searchFood) . "%'";
}

$recipeQuery .= " ORDER BY r.recipe_id DESC";
$recipes = $conn->query($recipeQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Recipes</title>
    <link rel="stylesheet" href="manageRecipe.css">
</head>
<body>

    <div class="container">
        <h1>🍽️ Manage Recipes</h1>

        <div class="back-btn">
            <a href="ChefDashboard.php" class="btn">⬅ Back to Dashboard</a>
        </div>

        <!-- 🔍 Filter Section -->
        <div class="filter-container">
            <h2>Filter Recipes</h2>
            <form method="GET" action="">
                <div class="filter-row">
                    <input type="text" name="searchName" placeholder="Search by Recipe Name" value="<?= htmlspecialchars($searchName) ?>">
                    <input type="text" name="searchFood" placeholder="Search by Food Name" value="<?= htmlspecialchars($searchFood) ?>">
                    <button type="submit" class="btn">Search</button>
                    <a href="manageRecipe.php" class="btn reset">Reset</a>
                </div>
            </form>
        </div>

         <!-- 📋 Table View -->
        <div class="table-container">
            <h2>All Recipes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Recipe ID</th>
                        <th>Food Name</th>
                        <th>Recipe Name</th>
                        <th>Description</th>
                        <th>Ingredients</th>
                        <th>Instructions</th>
                        <th>Chef ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recipes->num_rows > 0): ?>
                        <?php while ($row = $recipes->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['recipe_id'] ?></td>
                                <td><?= htmlspecialchars($row['foodName']) ?></td>
                                <td><?= htmlspecialchars($row['recipe_name']) ?></td>
                                <td><?= htmlspecialchars($row['description']) ?></td>
                                <td><?= nl2br(htmlspecialchars($row['ingredients'])) ?></td>
                                <td><?= nl2br(htmlspecialchars($row['instructions'])) ?></td>
                                <td><?= $row['created_by'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No recipes found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


        <!-- 🧾 Add New Recipe -->
        <div class="form-container">
            <h2>Add New Recipe</h2>
            <form method="POST" action="">
                <input type="hidden" name="add_recipe" value="1">

                <label for="food_id">Select Food:</label>
                <select name="food_id" id="food_id" required>
                    <option value="">-- Select Food Item --</option>
                    <?php while ($food = $foods->fetch_assoc()): ?>
                        <option value="<?= $food['foodId'] ?>"><?= htmlspecialchars($food['foodName']) ?></option>
                    <?php endwhile; ?>
                </select>

                <label for="recipe_name">Recipe Name:</label>
                <input type="text" name="recipe_name" id="recipe_name" required>

                <label for="description">Description:</label>
                <textarea name="description" id="description" rows="3"></textarea>

                <label for="ingredients">Ingredients:</label>
                <textarea name="ingredients" id="ingredients" rows="4" placeholder="e.g., Flour, Sugar, Eggs, Butter"></textarea>

                <label for="instructions">Instructions:</label>
                <textarea name="instructions" id="instructions" rows="5" placeholder="Step 1: Mix ingredients..."></textarea>

                <label for="created_by">Chef ID:</label>
                <input type="number" name="created_by" id="created_by" placeholder="Enter Chef ID">

                <button type="submit" class="btn">Add Recipe</button>
            </form>
        </div>

       
        
    </div>

</body>
</html>
