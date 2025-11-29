<?php
session_start();
include 'config.php'; // Your DB connection

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['description'])) {
    $description = $_POST['description'];
    $rating = $_POST['rating'] ?? 0;
    if (!empty($description)) {
        $stmt = $conn->prepare("INSERT INTO feedBack (discription, fdate) VALUES (?, NOW())");
        $stmt->bind_param("s", $description);
        $stmt->execute();
        // Optional: You can store rating in a separate column if you add one
        // ALTER TABLE feedBack ADD COLUMN rating INT DEFAULT 0;
        $lastId = $conn->insert_id;
        if ($rating > 0) {
            $conn->query("UPDATE feedBack SET rating = $rating WHERE feedbackId = $lastId");
        }
        header("Location: feedback.php");
        exit;
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $conn->query("DELETE FROM feedBack WHERE feedbackId = $deleteId");
    header("Location: feedback.php");
    exit;
}

// Fetch all feedbacks
$result = $conn->query("SELECT * FROM feedBack ORDER BY fdate DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Feedback - Omik Restaurant</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #fdfbfb, #ebedee);
    margin: 0;
    padding: 0;
}
header {
    background: #2c3e50;
    color: #fff;
    padding: 20px;
    text-align: center;
    font-size: 24px;
    font-weight: 600;
    letter-spacing: 1px;
}
.container {
    display: flex;
    flex-wrap: wrap;
    max-width: 1200px;
    margin: 30px auto;
    gap: 30px;
}
.feedback-form {
    flex: 0.4;
    min-width: 250px;
    background: rgba(255,255,255,0.9);
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    height: fit-content;
}
.feedback-view {
    flex: 0.6;
    min-width: 500px;
    background: rgba(255,255,255,0.95);
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    height: fit-content;
}
h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #34495e;
    font-size: 22px;
}
form {
    display: flex;
    flex-direction: column;
}
textarea {
    resize: none;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid #ccc;
    font-size: 15px;
    transition: 0.3s;
}
textarea:focus {
    border-color: #3498db;
    box-shadow: 0 0 8px rgba(52,152,219,0.3);
    outline: none;
}
button {
    margin-top: 15px;
    padding: 12px;
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: #fff;
    font-weight: 600;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-size: 15px;
    transition: 0.3s;
}
button:hover {
    background: linear-gradient(135deg, #2980b9, #2471a3);
    transform: translateY(-2px);
}

/* 5-star rating */
.rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    margin-bottom: 10px;
    padding: 13px;
}
.rating input {
    display: none;
}
.rating label {
    position: relative;
    width: 25px;
    font-size: 25px;
    color: #ccc;
    cursor: pointer;
}
.rating label::before {
    content: "★";
    position: absolute;
}
.rating input:checked ~ label,
.rating label:hover,
.rating label:hover ~ label {
    color: #f1c40f;
}

/* Table */
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}
th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}
th {
    background: #34495e;
    color: #fff;
    border-radius: 8px;
}
tr:hover {
    background-color: #f1f1f1;
}
.reply {
    color: #27ae60;
    font-weight: 500;
}
.delete-btn {
    background: #e74c3c;
    color: #fff;
    padding: 5px 10px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 13px;
}
.delete-btn:hover {
    background: #c0392b;
}
.no-feedback {
    text-align: center;
    padding: 40px;
    color: #888;
    font-size: 16px;
}
@media (max-width: 900px) {
    .container {
        flex-direction: column;
    }
}

.back-btn {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: #fff;
    padding: 5x;
    font-size: 13px;
    font-weight: 500;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.back-btn:hover {
    background: linear-gradient(135deg, #2980b9, #2471a3);
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.2);
}

.back-btn:active {
    transform: translateY(0);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
</style>
</head>
<body>

<header>Feedback</header>
<div style="padding:15px;">
  <button onclick="history.back()" class="back-btn">⬅ Back</button>
</div>

<div class="container">
  <!-- Feedback form -->
  <div class="feedback-form">
    <h2>Submit Feedback</h2>
    <form method="POST" id="feedbackForm">
      <textarea name="description" rows="5" placeholder="Write your feedback here..." required></textarea>
      <div class="rating">
        <input type="radio" name="rating" id="star5" value="5"><label for="star5"></label>
        <input type="radio" name="rating" id="star4" value="4"><label for="star4"></label>
        <input type="radio" name="rating" id="star3" value="3"><label for="star3"></label>
        <input type="radio" name="rating" id="star2" value="2"><label for="star2"></label>
        <input type="radio" name="rating" id="star1" value="1"><label for="star1"></label>
      </div>
      <button type="submit">Submit Feedback</button>
    </form>
  </div>

  <!-- Feedback view -->
  <div class="feedback-view">
    <h2>All Feedback</h2>
    <?php if ($result->num_rows > 0): ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Feedback</th>
            <th>Reply</th>
            <th>Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $row['feedbackId'] ?></td>
              <td><?= htmlspecialchars($row['discription']) ?>
                <?php if(isset($row['rating'])): ?>
                  <span>⭐ <?= $row['rating'] ?></span>
                <?php endif; ?>
              </td>
              <td class="reply"><?= htmlspecialchars($row['reply'] ?? '—') ?></td>
              <td><?= date('d M Y, H:i', strtotime($row['fdate'])) ?></td>
              <td>
                <a href="feedback.php?delete=<?= $row['feedbackId'] ?>" 
                   onclick="return confirm('Are you sure to delete this feedback?')" class="delete-btn">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="no-feedback">No feedback yet. Be the first to submit!</div>
    <?php endif; ?>
  </div>
</div>

<script>
  // Clear textarea after submission
  document.getElementById('feedbackForm').addEventListener('submit', function() {
    setTimeout(() => { this.reset(); }, 100);
  });
</script>

</body>
</html>
<?php $conn->close(); ?>
