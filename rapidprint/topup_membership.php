<?php
include('includes/session.php');
include('includes/db.php');

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $membershipCardNumber = $_POST['membershipCardNumber'] ?? '';
    $amount = $_POST['amount'] ?? 0;

    if (!empty($membershipCardNumber) && is_numeric($amount) && $amount > 0) {
        // Fetch the UserID associated with the Membership Card Number
        $stmt = $conn->prepare("SELECT UserID FROM user WHERE MembershipCardNumber = ?");
        $stmt->execute([$membershipCardNumber]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $userID = $user['UserID'];

            // Fetch the current CardBalance
            $stmt = $conn->prepare("SELECT CardBalance FROM membership WHERE UserID = ?");
            $stmt->execute([$userID]);
            $membership = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($membership) {
                $newBalance = $membership['CardBalance'] + $amount;

                // Update the CardBalance
                $stmt = $conn->prepare("UPDATE membership SET CardBalance = ? WHERE UserID = ?");
                $stmt->execute([$newBalance, $userID]);

                $message = "Top-up successful! New balance: RM" . number_format($newBalance, 2);
            } else {
                $message = "Membership not found for the provided card number.";
            }
        } else {
            $message = "Invalid Membership Card Number.";
        }
    } else {
        $message = "Please enter a valid card number and amount.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Admin Dashboard</title>

    <style>
        /* General styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            background-color: #66CDAA;
            background-size: cover;
        }

        .chart-container {
            width: 80%;
            margin: 20px auto;
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .chart-title {
            text-align: center;
            font-size: 18px;
            color: #2d3a3f;
            margin-bottom: 15px;
        }

        .sidebar {
            width: 200px;
            background-color: #2d3a3f;
            color: white;
            padding: 20px;
            height: 100vh;
            /* Full viewport height */
            position: fixed;
            /* Make the sidebar fixed on the page */
            top: 0;
            /* Keep it at the top */
            left: 0;
            /* Keep it on the left side */
            z-index: 1000;
            /* Ensure the sidebar stays on top of other content */
            overflow-y: auto;
            /* Allows scrolling if the sidebar content exceeds the height */
        }

        .sidebar h2 {
            color: #66CDAA;
            text-align: center;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 15px 0;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            border-radius: 4px;
        }

        .sidebar ul li a:hover {
            background-color: #66CDAA;
        }

        .main-content {
            margin-left: 230px;
            /* Add a left margin to the main content to make room for the sidebar */
            padding: 20px;
            flex: 1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            /* Ensures rounded corners for the table */
        }

        table th,
        table td {
            padding: 12px 15px;
            text-align: left;
            border: 1px solid #e0e0e0;
        }

        table th {
            background-color: #2d3a3f;
            color: white;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table td {
            background-color: #fafafa;
            color: #333;
        }

        table tr:hover {
            background-color: #f4f4f4;
            /* Subtle hover effect */
        }

        table td a {
            text-decoration: none;
            color: #2d3a3f;
            font-weight: bold;
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        table td a:hover {
            background-color: #66CDAA;
            color: white;
        }

        table td a.btn-danger {
            background-color: #e55c5c;
        }

        table td a.btn-danger:hover {
            background-color: #cc4f4f;
        }


        a.btn {
            text-decoration: none;
            color: white;
            background-color: rgb(69, 189, 42);
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
            transition: background-color 0.3s ease;
        }

        a.btn-danger {
            background-color: #e55c5c;
        }

        a.btn:hover {
            background-color: #58b94e;
            /* Darker shade for hover effect */
        }

        a.btn-danger:hover {
            background-color: #cc4f4f;
        }

        .logo {
            width: 50px;
            height: auto;
            margin-bottom: 5px;
        }

        .search-form {
            margin-bottom: 20px;
        }

        .search-form input {
            padding: 8px;
            margin-right: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .search-form button {
            padding: 8px;
            border: none;
            border-radius: 4px;
            background-color: #66CDAA;
            color: white;
            transition: background-color 0.3s ease;
        }

        .search-form button:hover {
            background-color: #58b94e;
        }

        .back-btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #2d3a3f;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 10px;
            transition: background-color 0.3s ease;
        }

        .back-btn:hover {
            background-color: #66CDAA;
        }

        i {
            margin-right: 8px;
        }

        .dashboard-metrics .metric i {
            color: #66CDAA;
            margin-bottom: 10px;
        }

        .chart-container {
            width: 40%;
            /* Make container smaller */
            margin: 30px auto;
            background: #f9f9f9;
            /* Lighter background for modern feel */
            padding: 20px;
            border-radius: 12px;
            /* More rounded corners */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            /* Softer, modern shadow */
            max-width: 400px;
            /* Limit maximum width for very large screens */
            text-align: center;
            /* Center align the content */
        }

        canvas {
            width: 100% !important;
            /* Canvas takes full width of container */
            height: 250px !important;
            /* Make the chart shorter */
            display: block;
            /* Ensures canvas behaves properly */
            margin: 0 auto;
            /* Center canvas within container */
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 10px;
            font-weight: bold;
        }
        input {
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            padding: 10px;
            background-color: #66CDAA;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #58b94e;
        }
        .message {
            text-align: center;
            margin-top: 20px;
            font-size: 16px;
            color: #2d3a3f;
        }
    </style>

</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <a href="https://www.umpsa.edu.my" target="_blank">
            <img src="assets/images/umpsa.png" alt="RapidPrint Logo" class="logo">
        </a>
        <h2><i class="fa-solid fa-user-tie"></i>Admin Panel</h2>
        <ul>
            <li><a href="admin_profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="admin_dashboard.php?target=users"><i class="fas fa-users"></i> Manage Users</a></li>
            <li><a href="admin_dashboard.php?target=koperasibranch"><i class="fas fa-building"></i> Manage Branch</a></li>
            <li><a href="admin_dashboard.php?target=printingpackage"><i class="fas fa-print"></i> Manage Package</a></li>
            <li><a href="topup_membership.php"><i class="fa fa-address-card"></i> Topup Membership</a></li>
            <li><a href="approval_membership.php"><i class="fas fa-address-card"></i> Approval Card</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>


    <!-- Main Content -->
    <div class="main-content">
        <h1>Admin Dashboard</h1>

        <!-- topup membership -->
        <div class="container">
            <h1>Top-up Membership</h1>
            <form method="POST" action="">
                <label for="membershipCardNumber">Membership Card Number</label>
                <input type="text" id="membershipCardNumber" name="membershipCardNumber" placeholder="Enter Membership Card Number" required>

                <label for="amount">Top-up Amount (RM)</label>
                <input type="number" id="amount" name="amount" placeholder="Enter amount to top-up" required>

                <button type="submit">Top Up</button>
            </form>
            <?php if (!empty($message)): ?>
                <div class="message">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</body>

</html>