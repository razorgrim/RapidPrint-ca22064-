<?php
session_start();
include('includes/header.php');

// Redirect users to respective page
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header('Location: admin_profile.php');
    exit();
}
elseif (isset($_SESSION['user_id']) && $_SESSION['role'] === 'staff') {
    header('Location: staff_dashboard.php');
    exit();
}
elseif (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student') {
    header('Location: student_profile.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapid Print</title>
</head>
<style>
    /* General styling for the page */
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 20px 0 0 0; /* Leave 20px at the top for the header */
        background: 
            linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), /* Adds a dark overlay */
            url('assets/images/background.jpg') no-repeat center center; /* Background image */
        background-size: cover; /* Ensures the background image covers the screen */
        height: 100vh; /* Set the height to match the viewport height */
        display: flex; /* Enable flexbox for layout */
        flex-direction: column; /* Stack header, main, and footer vertically */
        justify-content: flex-start; /* Ensure content stays in place */
        box-sizing: border-box;
    }

    /* Homepage info box styling */
    .homepage-info {
        text-align: center;
        font-weight: bold;
        color: #000;
        margin-top: 50px;
        padding: 20px;
        background-color: rgba(255, 255, 255, 0.85);
        border-radius: 10px;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Styling for the container with login prompt */
    .container {
        text-align: center;
        color: #fff;
        margin-top: 20px;
    }

    .container a {
        display: inline-block;
        background-color: #66CDAA;
        padding: 12px 20px;
        border-radius: 4px;
        text-decoration: none;
        color: white;
        font-size: 16px;
        transition: background-color 0.3s ease;
    }

    .container a:hover {
        background-color: #5cbf9a;
    }

</style>

<body>
    <div class="homepage-info">
        <h1>Welcome to Rapid Print</h1>
        <p>Rapid Print is your one-stop solution for all printing needs. Whether you are a student, staff member, or administrator, our platform makes it easy to manage your printing tasks with efficiency and ease. Explore our services today and simplify your workflow!</p>
    </div>

    <div class="container">
        <p>Please log in to continue:</p>
        <a href="login.php">Login</a>
    </div>

</body>
</html>

<?php include('includes/footer.php'); ?>
