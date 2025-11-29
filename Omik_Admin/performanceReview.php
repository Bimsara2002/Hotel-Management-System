<?php
include 'Config.php';

// Fetch all staff for dropdown
$staffQuery = "SELECT StaffId, CONCAT(FirstName, ' ', LastName) AS FullName FROM Staff";
$staffResult = mysqli_query($conn, $staffQuery);

// Get filter values
$selectedStaff = isset($_GET['staffId']) ? $_GET['staffId'] : '';
$selectedDate = isset($_GET['reviewDate']) ? $_GET['reviewDate'] : '';

// Build base query for performance data
$query = "
    SELECT p.reviewId, s.FirstName AS StaffFirst, s.LastName AS StaffLast,
           r.FirstName AS ReviewerFirst, r.LastName AS ReviewerLast,
           p.reviewDate, p.rating, p.comments
    FROM Performance p
    JOIN Staff s ON p.staffId = s.StaffId
    JOIN Staff r ON p.reviewerId = r.StaffId
    WHERE 1=1
";

// Apply filters safely
if (!empty($selectedStaff)) {
    $staffId = mysqli_real_escape_string($conn, $selectedStaff);
    $query .= " AND p.staffId = '$staffId'";
}
if (!empty($selectedDate)) {
    $date = mysqli_real_escape_string($conn, $selectedDate);
    $query .= " AND p.reviewDate = '$date'";
}
$query .= " ORDER BY p.reviewDate DESC";

$result = mysqli_query($conn, $query);

// Fetch average ratings
$avgQuery = "
    SELECT s.FirstName, s.LastName, ROUND(AVG(p.rating), 2) AS avg_rating
    FROM Performance p
    JOIN Staff s ON p.staffId = s.StaffId
    GROUP BY s.StaffId
";
$avgResult = mysqli_query($conn, $avgQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Reviews</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="performance.css">
</head>
<body>
<div class="container" style="margin-top: 20px;">
    <a href="HRManagerDashboard.php" class="btn btn-outline-primary mb-3">&larr; Back to Dashboard</a>
    <h2>Performance Reviews</h2>

    <!-- Filters -->
    <form method="GET" id="filterForm" class="d-flex align-items-center gap-2 mb-4 flex-wrap">
        <select name="staffId" class="form-select" style="max-width: 250px;">
            <option value="">-- All Staff --</option>
            <?php while ($row = mysqli_fetch_assoc($staffResult)) { ?>
                <option value="<?= $row['StaffId'] ?>" <?= ($selectedStaff == $row['StaffId']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['FullName']) ?>
                </option>
            <?php } ?>
        </select>

        <input type="date" name="reviewDate" class="form-control" style="max-width: 200px;"
               value="<?= htmlspecialchars($selectedDate) ?>">

        <button type="submit" class="btn btn-primary">Filter</button>
        <button type="button" onclick="resetForm()" class="btn btn-primary" style="background:#dc3545">Reset</button>
    </form>

    <!-- Average Ratings -->
    <div class="average-box p-3 rounded mb-4">
        <h5><strong>Average Ratings</strong></h5>
        <hr>
        <?php
        if (mysqli_num_rows($avgResult) > 0) {
            while ($row = mysqli_fetch_assoc($avgResult)) {
                echo "<p><strong>{$row['FirstName']} {$row['LastName']}:</strong> {$row['avg_rating']} 
                      <span class='star-text'>&#9733;</span></p>";
            }
        } else {
            echo "<p>No ratings found.</p>";
        }
        ?>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle shadow-sm">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Staff Name</th>
                    <th>Reviewer</th>
                    <th>Date</th>
                    <th>Rating</th>
                    <th>Comments</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    $count = 1;
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>
                                <td>{$count}</td>
                                <td>{$row['StaffFirst']} {$row['StaffLast']}</td>
                                <td>{$row['ReviewerFirst']} {$row['ReviewerLast']}</td>
                                <td>{$row['reviewDate']}</td>
                                <td><span class='star-text'>{$row['rating']} &#9733;</span></td>
                                <td>{$row['comments']}</td>
                              </tr>";
                        $count++;
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center text-muted'>No performance reviews found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('filterForm').reset();
    window.location.href = 'PerformanceReview.php'; // refresh to clear filters
}
</script>
</body>
</html>
