<?php
include('includes/db.php')
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module 4: Manage Printing</title>
    <style>
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
        .btn.generate-invoice {
            background-color: #007bff; /* Blue color for the button */
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
            <a href="invoice_overview.php">Generated Invoice</a>
            <a href="#">Bonus Overview</a>
            <a href="#">Staff Profile</a>
        </div>
        <div class="content">
            <h1>Staff Dashboard</h1>
<?php

// Handle actions (removed the accept and delete actions)
if (isset($_GET['action']) && isset($_GET['order_id'])) {
    $action = $_GET['action'];
    $orderId = $_GET['order_id'];

    try {
        switch ($action) {
            case 'collect':
                $query = "UPDATE orders SET status = 'Collected' WHERE OrderID = :orderId";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':orderId', $orderId, PDO::PARAM_INT);
                $stmt->execute();
                echo "Order $orderId has been marked as collected.<br>";
                break;

            default:
                echo "Invalid action.<br>";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Display orders grouped by their statuses
try {
    $query = "SELECT o.OrderID, o.PackageType, o.quantity, o.price, o.total_price, o.status, u.UserID, u.Name AS UserName 
              FROM orders o 
              JOIN user u ON o.UserID = u.UserID 
              ORDER BY o.status";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Order Status Overview</h2>";
    echo "<table border='5' style='width: 100%; text-align: center; border-collapse: collapse;'>";
    echo "<tr>
            <th>Order Number</th>
            <th>User ID</th>
            <th>User Name</th>
            <th>Item</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Total Price</th>
            <th>Status</th>
            <th>Action</th>
          </tr>";

    foreach ($orders as $order) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($order['OrderID']) . "</td>";
        echo "<td>" . htmlspecialchars($order['UserID']) . "</td>"; // Display UserID
        echo "<td>" . htmlspecialchars($order['UserName']) . "</td>";
        echo "<td>" . htmlspecialchars($order['item']) . "</td>";
        echo "<td>" . htmlspecialchars($order['quantity']) . "</td>";
        echo "<td>RM" . htmlspecialchars($order['price']) . "</td>";
        echo "<td>RM" . htmlspecialchars($order['total_price']) . "</td>";
        echo "<td>" . htmlspecialchars($order['status']) . "</td>";
        echo "<td>
                <a href='invoice.php?UserID=" . htmlspecialchars($order['UserID']) . "' class='btn generate-invoice'>Generate</a>
              </td>";
        echo "</tr>";
    }

    echo "</table>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Close the database connection
$conn = null;
?>
        </div>
    </div>
</body>
</html>
