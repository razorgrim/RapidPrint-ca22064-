<?php
session_start();
include('includes/db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['accept_order']) && isset($_POST['order_id'])) {
        $orderId = $_POST['order_id'];

        // Fetch the current status of the order
        $stmt = $conn->prepare("SELECT Status FROM Orders WHERE OrderID = ?");
        $stmt->execute([$orderId]);
        $currentStatus = $stmt->fetchColumn();

        // Debugging: Echo the current status to ensure you're fetching it correctly
        echo "Current Status: " . $currentStatus . "<br>";

        if ($currentStatus === false) {
            echo "<script>alert('Order ID not found in the database.'); window.location.href='manage_orders.php';</script>";
        } else {
            // Ensure the status is 'Pending' before updating
            if ($currentStatus === 'pending'||'Pending') {
                // Update the status to 'Accepted'
                $stmt = $conn->prepare("UPDATE Orders SET Status = 'Accepted' WHERE OrderID = ?");
                if ($stmt->execute([$orderId])) {
                    echo "<script>alert('Order accepted successfully.'); window.location.href='manage_orders.php';</script>";
                } else {
                    echo "<script>alert('Failed to update order status.'); window.location.href='manage_orders.php';</script>";
                }
                exit;
            } elseif ($currentStatus === 'Accepted') {
                echo "<script>alert('This order is already accepted.'); window.location.href='manage_orders.php';</script>";
            } else {
                echo "<script>alert('Invalid order status: " . addslashes($currentStatus) . "'); window.location.href='manage_orders.php';</script>";
            }
        }
    }
}

// Fetch all orders
$stmt = $conn->prepare("SELECT * FROM Orders");
$stmt->execute();
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function handleGenerateInvoice(form) {
            const status = form.closest('tr').querySelector('td:nth-child(5)').textContent.trim();
            if (status.toLowerCase() !== 'accepted') {
                alert('Please accept the order first.');
                return false; // Prevent form submission
            }
            return true; // Allow form submission
        }
    </script>
</head>
<style>
/* Styling as previously provided */
body {
            background-image: url('assets/images/background.jpg'); /* Set your background image here */
            background-size: cover;
            background-position: center;
            color: black; /* Text color */
            font-family: Arial, sans-serif;
        }
.container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 80vh;
}
table {
    width: 80%;
    border-collapse: collapse;
    margin: 0 auto;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}
table thead {
    background-color: #66CDAA;
    color: white;
}
table th, table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}
table tr:hover {
    background-color: #f4f4f4;
}
table th {
    font-weight: bold;
}
table td:last-child {
    text-align: center;
}
.btn-primary, .btn-secondary {
    display: inline-block;
    padding: 8px 12px;
    margin: 5px;
    font-size: 14px;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}
.btn-primary {
    background-color: #007BFF;
}
.btn-primary:hover {
    background-color: #0056b3;
}
.btn-secondary {
    background-color: #FF6666;
}
.btn-secondary:hover {
    background-color: #e55c5c;
}
</style>
<body>
<header>
    <h1>Manage Orders</h1>
    <nav class="menu">
        <a href="manage_orders.php">Manage Orders</a>
        <a href="rewards.php">Rewards</a>
        <a href="staff_dashboard.php">Reports</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>
<div class="container">
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer ID</th>
                <th>Order Date</th>
                <th>File Name</th>
                <th>Status</th>
                <th>Accept</th>
                <th>Generate Invoice</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['OrderID']); ?></td>
                    <td><?= htmlspecialchars($order['UserID']); ?></td>
                    <td><?= htmlspecialchars($order['OrderDate']); ?></td>
                    <td><?= htmlspecialchars($order['FileName']); ?></td>
                    <td><?= htmlspecialchars($order['Status']); ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="order_id" value="<?= $order['OrderID']; ?>">
                            <button type="submit" name="accept_order" class="btn-primary">
                                Accept
                            </button>
                        </form>
                    </td>
                    <td>
                        <form method="post" action="generate_invoice.php" onsubmit="return handleGenerateInvoice(this)">
                            <input type="hidden" name="order_id" value="<?= $order['OrderID']; ?>">
                            <button type="submit" class="btn-secondary">
                                Generate Invoice
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<footer>
    <p>&copy; <?= date("Y"); ?> RapidPrint. All rights reserved.</p>
</footer>
</body>
</html>
