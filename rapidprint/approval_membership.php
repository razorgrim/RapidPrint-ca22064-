<?php
include('includes/session.php');
include('includes/db.php');

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle Accept/Reject Actions
if (isset($_POST['action'])) {
    $membershipID = $_POST['membershipID'];
    $action = $_POST['action'];

    if ($action === 'Accept') {
        $applyStatus = 'Accepted';
        $membershipStatus = 'Active';
        $_SESSION['message'] = "Membership has been successfully accepted.";
    } elseif ($action === 'Reject') {
        $applyStatus = 'Rejected';
        $membershipStatus = 'Unactivated';
        $_SESSION['message'] = "Membership has been successfully rejected.";
    }

    $updateQuery = "UPDATE membership SET applyStatus = :applyStatus, MembershipStatus = :membershipStatus WHERE MembershipID = :membershipID";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bindValue(':applyStatus', $applyStatus, PDO::PARAM_STR);
    $stmt->bindValue(':membershipStatus', $membershipStatus, PDO::PARAM_STR);
    $stmt->bindValue(':membershipID', $membershipID, PDO::PARAM_INT);

    if ($stmt->execute()) {
        // Redirect to avoid form resubmission
        header('Location: approval_membership.php');
        exit();
    } else {
        echo "Error updating record.";
    }
}

// Fetch pending applications
$query = "SELECT 
            user.Name, 
            student.matricID, 
            user.MembershipCardNumber, 
            student.cardMatric, 
            membership.MembershipID 
          FROM membership
          INNER JOIN user ON membership.UserID = user.UserID
          INNER JOIN student ON membership.UserID = student.UserID
          WHERE membership.applyStatus = 'Pending'";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all results as an associative array
$rowCount = count($result); // Count the rows
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

        .message {
            background-color: #66CDAA;
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            border-radius: 4px;
            margin: 20px 0;
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
    <div class="main-content">
        <!-- Display success message -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <h1>Membership Approval</h1>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Matric ID</th>
                    <th>Membership Card Number</th>
                    <th>Card Matric</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rowCount > 0): ?>
                    <?php foreach ($result as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Name']) ?></td>
                            <td><?= htmlspecialchars($row['matricID']) ?></td>
                            <td><?= htmlspecialchars($row['MembershipCardNumber']) ?></td>
                            <td>
                                <img src="<?= htmlspecialchars($row['cardMatric']) ?>" alt="Card Matric" style="width: 100px; height: auto;">
                            </td>
                            <td>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="membershipID" value="<?= $row['MembershipID'] ?>">
                                    <button type="submit" name="action" value="Accept" class="btn">Accept</button>
                                </form>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="membershipID" value="<?= $row['MembershipID'] ?>">
                                    <button type="submit" name="action" value="Reject" class="btn btn-danger">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No pending applications.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>
