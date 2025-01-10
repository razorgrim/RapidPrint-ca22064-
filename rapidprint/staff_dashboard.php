<?php
session_start();
include('includes/db.php');


// Check if the user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header('Location: login.php');
    exit;
}

// Fetch staff details
$stmt = $conn->prepare("SELECT UserID, Name, Email FROM User WHERE UserID = :id AND role = 'staff'");
$stmt->execute(['id' => $_SESSION['user_id']]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$staff) {
    echo "Staff details not found.";
    exit;
}

// Profile picture logic
$profilePicture ="assets/images/PP.JPEG";

// Fetch total sales for the last 12 months
$stmtSales = $conn->prepare("SELECT 
    DATE_FORMAT(OrderDate, '%Y-%m') AS month, 
    SUM(Price) AS MonthlySales 
    FROM Orders 
    WHERE OrderDate >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY month
    ORDER BY month ASC
");
$stmtSales->execute();
$salesData = $stmtSales->fetchAll(PDO::FETCH_ASSOC);

// Calculate monthly bonus based on the sales
$monthlySales = [];
$monthlyBonus = [];
foreach ($salesData as $sale) {
    $monthlySales[] = (float)$sale['MonthlySales'];
    
    // Calculate bonus
    $bonus = 0;
    if ($sale['MonthlySales'] > 450) {
        $bonus = 150;
    } elseif ($sale['MonthlySales'] > 350) {
        $bonus = 120;
    } elseif ($sale['MonthlySales'] > 280) {
        $bonus = 80;
    } elseif ($sale['MonthlySales'] > 200) {
        $bonus = 50;
    }

    $monthlyBonus[] = $bonus;
}

// Get the months for the graph
$months = array_map(function($sale) {
    return date('M Y', strtotime($sale['month']));
}, $salesData);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
/* General body styles */
body {
    background-image: url('assets/images/background.jpg'); /* Set your background image here */
    background-size: cover;
    background-position: center;
    color: #fff; /* Text color for body */
    font-family: Arial, sans-serif;
    display: flex;
    flex-direction: column; /* Stack content vertically */
    justify-content: flex-start; /* Align content at the top */
    min-height: 100vh; /* Ensure body takes up at least the full height of the screen */
    margin: 0;
}

/* Container for the whole page content */
.container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    gap: 40px;
    padding: 20px;
    max-width: 100%;  /* Ensure it's responsive */
    width: 100%;
    box-sizing: border-box;
    flex-grow: 1; /* Allow the container to take the remaining space */
}

/* Staff Profile Card Styling */
.profile-card {
    display: flex;
    align-items: center;
    gap: 30px;
    background: #fff;  /* White background for profile card */
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    flex: 1 0 60%;
    max-width: 800px; /* Limit profile card size */
}

/* Profile picture styling */
.profile-pic {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    object-fit: cover;
}

/* Profile info styling */
.profile-info h3 {
    margin: 0;
    color: #333; /* Dark color for name to ensure it's visible */
    font-size: 24px;
}

.profile-info p {
    margin: 5px 0;
    color: #555; /* Slightly lighter for the email and user ID */
    font-size: 16px;
}

/* Stats Section: Sales and Bonus */
.stats-section {
    display: flex;
    gap: 20px;
    background: #fff;  /* White background for stats section */
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    max-width: 800px; /* Limit size for better layout */
}

.stats-card {
    flex: 1;
    text-align: center;
    padding: 15px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.stats-card h4 {
    margin-bottom: 10px;
    color: #333; /* Dark color for the heading */
}

.stats-card p {
    font-size: 18px;
    color: #333; /* Dark color for the total earnings and bonus numbers */
}

/* Graph section */
.chart-section {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    width: 100%;
    max-width: 1000px; /* Ensure the chart section is not too wide */
}

.chart-container {
    flex: 1; /* Allow each chart container to take up equal space */
    height: 300px;
    padding: 20px;
    background-color: #fff; /* Set background color for chart containers to white */
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Footer styling */
footer {
    text-align: center;
    color: #fff;
    margin-top: 20px;
    padding: 20px 0;
    background-color: #333; /* Optional: adds a background to the footer */
    width: 100%;
}


</style>
</head>
<body>
    <header>
        <h1>Staff Dashboard</h1>
        <nav class="menu">
            <a href="manage_orders.php">Manage Orders</a>
            <a href="rewards.php">Rewards</a>
            <a href="staff_dashboard.php">Reports</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <div class="container">
        <!-- Profile Section -->
        <section class="profile-section">
            <div class="profile-card">
                <img src="<?= htmlspecialchars($profilePicture) ?>" alt="Profile Picture" class="profile-pic">
                <div class="profile-info">
                    <h3><?= htmlspecialchars($staff['Name']) ?></h3>
                    <p><strong>ID:</strong> <?= htmlspecialchars($staff['UserID']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($staff['Email']) ?></p>
                </div>
            </div>
        </section>

        <!-- Stats Section: Sales and Bonus -->
        <section class="stats-section">
            <div class="stats-card">
                <h4>Sales This Month</h4>
                <p>RM <?= number_format(array_sum($monthlySales), 2) ?></p>
            </div>
            <div class="stats-card">
                <h4>Total Bonus Earned</h4>
                <p>RM <?= number_format(array_sum($monthlyBonus), 2) ?></p>
            </div>
        </section>

        <!-- Reports Section: Graphs -->
        <section class="chart-section">
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
            <div class="chart-container">
                <canvas id="bonusChart"></canvas>
            </div>
        </section>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> RapidPrint. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Monthly Sales Chart
        const ctx1 = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',  // Line chart for sales
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Monthly Sales (RM)',
                    data: <?php echo json_encode($monthlySales); ?>,
                    backgroundColor: '#66CDAA',
                    borderColor: '#007BFF',
                    borderWidth: 1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Bonus Earned Chart
        const ctx2 = document.getElementById('bonusChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',  // Bar chart for bonus earned
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Bonus Earned (RM)',
                    data: <?php echo json_encode($monthlyBonus); ?>,
                    backgroundColor: '#FFD700',
                    borderColor: '#FF8C00',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
