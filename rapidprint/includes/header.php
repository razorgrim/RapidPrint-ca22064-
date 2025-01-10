<?php
// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
</head>
<style>
    header {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        background-color: #66CDAA; /* Header background color */
        z-index: 1000; /* Keep the header above other elements */
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1); /* Optional: Add shadow for better visibility */
        padding: 10px 20px 10px 20px; /* Adjust padding: top/right/bottom/left */
        display: flex; /* Flexbox for layout */
        justify-content: space-between; /* Space between left and right sections */
        align-items: center; /* Center items vertically */
    }

    body {
        margin: 0; /* Remove default margin */
        padding-top: 70px; /* Add padding to prevent content from being hidden behind the header */
    }

    .logo {
        height: 40px; /* Adjust the logo size if necessary */
        margin-right: 10px; /* Space between the logo and the title */
    }

    header h1 {
        color: white; /* Make the header text white */
        margin: 0; /* Remove default margin for better alignment */
        font-size: 24px; /* Adjust size as needed */
    }

    .menu {
        display: flex; /* Display links horizontally */
        gap: 15px; /* Add spacing between links */
        margin-right: 30px; /* Add space on the right side */
    }

    .menu a {
        text-decoration: none;
        color: white; /* White text color */
        font-weight: bold;
        padding: 8px 15px; /* Add padding for better button appearance */
        border-radius: 4px; /* Rounded corners */
        transition: background-color 0.3s ease; /* Smooth hover effect */
    }

    .menu a:hover {
        background-color: rgba(255, 255, 255, 0.2); /* Light background on hover */
    }
</style>

<body>

    <!-- Header Menu -->
    <header>
        <!-- Logo and Title (on the left side) -->
        <div style="display: flex; align-items: center;">
            <!-- Make the logo clickable and redirect to UMPSA website -->
            <a href="https://www.umpsa.edu.my" target="_blank">
                <img src="assets/images/umpsa.png" alt="RapidPrint Logo" class="logo"> <!-- Replace with actual logo path -->
            </a>
            <h1>RapidPrint</h1>
        </div>
        
        <!-- Right side menu with Login/Register or Logout based on session -->
        <div class="menu">
            <a href="index.php">Home</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] === 'student'): ?>
                    <!-- Student-specific menu -->
                    <?php if ($current_page === 'student_profile.php'): ?>
                        <a href="student_dashboard.php?action=orders">Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php">Logout</a>
                <?php elseif ($_SESSION['role'] === 'staff'): ?>
                    <!-- Staff-specific menu -->
                    <?php if ($current_page === 'staff_profile.php'): ?>
                        <a href="staff_dashboard.php">Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php">Logout</a>
                <?php elseif ($_SESSION['role'] === 'admin'): ?>
                    <!-- Admin-specific menu -->
                    <?php if ($current_page === 'admin_profile.php'): ?>
                        <a href="admin_dashboard.php">Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php">Logout</a>
                <?php endif; ?>
            <?php else: ?>
                <!-- If not logged in, show Register/Login links -->
                <?php if ($current_page !== 'login.php'): ?>
                    <a href="login.php">Login</a>
                <?php endif; ?>
                <?php if ($current_page !== 'index.php' && $current_page !== 'login.php'): ?>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </header>
    
</body>
</html>
