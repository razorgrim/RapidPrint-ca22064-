<?php
session_start();
include('includes/db.php');


// Check if the user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header('Location: login.php');
    exit;
}

// Fetch total monthly sales
$currentMonth = date('Y-m'); // Get current year and month
$stmt = $conn->prepare("
    SELECT SUM(Price) AS MonthlySales 
    FROM Orders 
    WHERE OrderDate LIKE :currentMonth
");
$stmt->execute(['currentMonth' => "$currentMonth%"]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$monthlySales = $row['MonthlySales'] ?? 0; // If null, default to 0

// Calculate bonus based on tiers
$bonusEarned = 0;
if ($monthlySales > 450) {
    $bonusEarned = 150;
} elseif ($monthlySales > 350) {
    $bonusEarned = 120;
} elseif ($monthlySales > 280) {
    $bonusEarned = 80;
} elseif ($monthlySales > 200) {
    $bonusEarned = 50;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
<style>   
 /* General styles for the body */
body {
    font-family: 'Arial', sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
    text-align: center;
}

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

/* Header Styles */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #66CDAA;
    color: white;
    padding: 15px 30px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* The Dashboard specific container (For graphs and layout) */
.dashboard-container {
    display: flex;
    flex-direction: column;
    gap: 40px;
    padding: 20px;
}

/* Make sure the chart section uses flex to align items side by side */
.chart-section {
    display: flex; /* Use flexbox to arrange charts side by side */
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;  /* Ensure the charts wrap on smaller screens */
}

/* Ensure each chart container takes equal width and height */
.chart-container {
    flex: 1 0 48%;  /* Adjust to take 48% width, leaving space between them */
    height: 600px;
    background-color: #fff; /* White background for graphs */
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Specific container for graphs */
#chart-container {
    max-width: 100%;
}

/* Additional styles for the page container */
.container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 80vh;
}

/* Make the profile section take up 60% of the width */
.profile-section {
    flex: 1 0 60%;
    margin-bottom: 20px;
}

/* Profile Card and other layout adjustments */
.profile-card {
    display: flex;
    align-items: center;
    gap: 30px;
    background: #f8f9fa;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

/* Rewards Card Styling */
.rewards-card {
    background: rgba(255, 255, 255, 0.9); /* White background with 10% transparency */
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    margin: 20px auto;
    width: 80%; /* Makes the rewards card responsive */
    max-width: 600px; /* Ensures the card isn't too wide */
}

/* Styling for individual sections inside the rewards card */
.rewards-card div {
    margin-bottom: 20px;
}

.rewards-card h5 {
    font-size: 18px;
    color: #333; /* Dark color for headings */
}

.rewards-card .highlight {
    font-size: 22px;
    font-weight: bold;
    color: #007BFF; /* Use a contrasting color for the reward amount */
}

/* QR Code Styling */
.qr-code {
    margin-top: 30px;
}

.qr-code img {
    width: 150px;
}

.qr-code p {
    font-size: 14px;
    color: #6c757d; /* Muted text for description */
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
    <h1>Rewards</h1>
    <nav class="menu">
        <a href="manage_orders.php">Manage Orders</a>
        <a href="rewards.php">Rewards</a>
        <a href="staff_dashboard.php">Reports</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <section id="rewards">
        <h2 class="text-center">Your Rewards Summary</h2>

        <div class="rewards-card">
            <div>
                <h5>Total Monthly Sales</h5>
                <p class="highlight">RM <?php echo number_format($monthlySales, 2); ?></p>
            </div>
            <div>
                <h5>Bonus Earned</h5>
                <p class="highlight">RM <?php echo number_format($bonusEarned, 2); ?></p>
            </div>
        </div>

        <div class="qr-code">
            <img src="assets/qrcodes/QR_Staff1.PNG" alt="Your QR Code" width="150">
            <p class="text-muted">Scan to view detailed rewards.</p>
        </div>
    </section>
</div>

<footer class="text-center mt-4">
    <p>&copy; <?php echo date("Y"); ?> RapidPrint. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
