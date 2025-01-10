<?php
include('includes/db.php')

if (isset($_GET['UserID'])) {
    $userId = $_GET['UserID'];

    // Fetch user information
    $queryUser = "SELECT * FROM users WHERE UserID = :userId";
    $stmtUser = $conn->prepare($queryUser);
    $stmtUser->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmtUser->execute();
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    // Fetch orders for the user
    $queryOrders = "SELECT * FROM orders WHERE UserID = :userId";
    $stmtOrders = $conn->prepare($queryOrders);
    $stmtOrders->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmtOrders->execute();
    $orders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);
} else {
    echo "UserID not provided.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Invoice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<style>
            @media print {
            body {
                font-size: 12pt;
            }
            .no-print {
                display: none;
            }
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .dashboard {
            display: flex;
        }
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: white;
            padding: 20px;
            height: 100vh;
        }
        .sidebar h2 {
            text-align: center;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .content {
            flex: 1;
            padding: 20px;
            background-color: #e9ecef;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #28a745;
            color: white;
        }
        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
        }
        .btn.accept {
            background-color: #28a745;
        }
        .btn.delete {
            background-color: #dc3545;
        }
        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h2>Staff Panel</h2>
            <a href="staff_dashboard.php">Manage Orders</a>
            <a href="update_order.php">Order Overview</a>
            <a href="invoice_overview.php">Generate Invoice</a>
            <a href="#">Bonus </a>
            <a href="#">Staff Profile</a>
        </div>
<body>
<div class="container mt-5">
    <div class="card">
        <div class="card-header text-center">
            <h2>Invoice</h2>
        </div>
        <div class="card-body">
            <?php if ($user): ?>
                <h5>User Details</h5>
                <p><strong>Name:</strong> <?= htmlspecialchars($user['Name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['Email']) ?></p>
                <p><strong>Membership Card Number:</strong> <?= htmlspecialchars($user['MembershipCardNumber']) ?></p>
                <p><strong>Points:</strong> <?= htmlspecialchars($user['Points']) ?></p>

                <h5 class="mt-4">Order Details</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Price (RM)</th>
                            <th>Total (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $grandTotal = 0; ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['OrderID']) ?></td>
                                <td><?= htmlspecialchars($order['item']) ?></td>
                                <td><?= htmlspecialchars($order['quantity']) ?></td>
                                <td><?= htmlspecialchars($order['price']) ?></td>
                                <td><?= htmlspecialchars($order['total_price']) ?></td>
                            </tr>
                            <?php $grandTotal += $order['total_price']; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <h5 class="text-end">Grand Total: RM<?= number_format($grandTotal, 2) ?></h5>

            <?php else: ?>
                <p class="text-danger">User information not found.</p>
            <?php endif; ?>
        </div>
        <div class="card-footer text-center">
            <!-- This button will not show during print -->
            <button class="btn btn-primary no-print" onclick="window.print()">Print Invoice</button>
        </div>
    </div>
</div>
</body>
</html>
