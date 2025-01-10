<?php
include('includes/session.php');
include('includes/db.php');

// Check if the user is a student
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header('Location: login.php');
    exit();
}

// Function to fetch orders for the logged-in student
function fetchStudentOrders($conn, $userId) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE UserID = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Function to delete an order
function deleteOrder($conn, $orderId) {
    $stmt = $conn->prepare("DELETE FROM orders WHERE OrderID = ?");
    return $stmt->execute([$orderId]);
}

// Function to update an order
function updateOrder($conn, $orderId, $packageType, $description) {
    $stmt = $conn->prepare("UPDATE orders SET PackageType = ?, Description = ? WHERE OrderID = ?");
    return $stmt->execute([$packageType, $description, $orderId]);
}

// Function to create a payment entry
function makePayment($conn, $orderId, $paymentmethod, $paymentDate) {
    $stmt = $conn->prepare("INSERT INTO payment (OrderID, PaymentMethod, PaymentDateTime) VALUES (?, ?, ?)");
    return $stmt->execute([$orderId, $paymentmethod, $paymentDate]);
}
   
   
   
   
// Function to update payment status
function updatePaymentStatus($conn, $orderId, $status) {
    $stmt = $conn->prepare("UPDATE payment SET PaymentStatus = ? WHERE OrderID = ?");
    return $stmt->execute([$status, $orderId]);
}






// Handle order creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_order'])) {
    // Fetch the UserID from the session
    $userId = $_SESSION['user_id'];

    $packageType = $_POST['package_type'];
    $description = $_POST['description'];
    $orderDate = date('Y-m-d H:i:s');
    $status = 'Pending';

    // Set price based on package type (example logic for price based on package type)
    $price = 0;

    // Handle file upload
    $fileName = '';
    if (isset($_FILES['file_to_print']) && $_FILES['file_to_print']['error'] == 0) {
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
        $file_type = $_FILES['file_to_print']['type'];
        $file_size = $_FILES['file_to_print']['size'];
    
        // Check for allowed file types
        if (!in_array($file_type, $allowed_types)) {
            echo "Error: Invalid file type. Only PDF, JPG, and PNG are allowed.";
            exit();
        }
    
        // Check for file size (e.g., limit to 10MB)
        if ($file_size > 10 * 1024 * 1024) {
            echo "Error: File size exceeds the maximum limit of 10MB.";
            exit();
        }
    
        $fileTmpName = $_FILES['file_to_print']['tmp_name'];
        $fileName = 'uploads/' . $_FILES['file_to_print']['name'];
        if (move_uploaded_file($fileTmpName, $fileName)) {
            echo "File uploaded successfully.";
        } else {
            echo "Error uploading file.";
        }
    }

    // Insert the order into the database with UserID and StaffID as NULL
    $stmt = $conn->prepare("INSERT INTO orders (UserID, PackageType, Description, Price, status, OrderDate, FileName, StaffID) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, NULL)");
    if ($stmt->execute([$userId, $packageType, $description, $price, $status, $orderDate, $fileName])) {
        echo "Order successfully inserted.";
    } else {
        echo "Error: " . implode(", ", $stmt->errorInfo());
    }

    header("Location: student_dashboard.php?action=orders");
    exit();
}

// Handle order update or delete
// Handle order update or delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    $orderId = $_POST['order_id'];
    $packageType = $_POST['package_type'];
    $description = $_POST['description'];

    if (updateOrder($conn, $orderId, $packageType, $description)) {
        echo "Order updated successfully.";
    } else {
        echo "Error updating order.";
    }

    // Redirect back to "My Orders" after updating
    header("Location: student_dashboard.php?action=orders");
    exit();
}

if (isset($_GET['delete_order'])) {
    $orderId = $_GET['delete_order'];

    if (deleteOrder($conn, $orderId)) {
        echo "Order deleted successfully.";
    } else {
        echo "Error deleting order.";
    }

    // Redirect back to "My Orders" after deleting
    header("Location: student_dashboard.php?action=orders");
    exit();
}

$userId = $_SESSION['user_id'];
// Fetch orders for the logged-in student
$orders = fetchStudentOrders($conn, $userId);

// Check action type
$action = isset($_GET['action']) ? $_GET['action'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
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

        .sidebar {
            width: 200px;
            background-color: #2d3a3f;
            color: white;
            padding: 20px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
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
            flex: 1;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        table th {
            background-color: #2d3a3f;
            color: white;
        }

        a.btn {
            text-decoration: none;
            color: white;
            background-color: #66CDAA;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
        }

        a.btn:hover {
            opacity: 0.8;
        }

        .logo {
            width: 50px;
            height: auto;
            margin-bottom: 5px;
        }

        .order-form {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .order-form select, .order-form input, .order-form textarea, .order-form button {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
        }

        .order-form button {
            background-color: #66CDAA;
            color: white;
            border: none;
        }

        .order-form button:hover {
            background-color: #4CAF50;
        }

        .order-form label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="https://www.umpsa.edu.my" target="_blank">
            <img src="assets/images/umpsa.png" alt="Logo" class="logo">
        </a>
        <h2>Student Panel</h2>
        <ul>
            <li><a href="student_profile.php">Profile</a></li>
            <li><a href="student_dashboard.php?action=orders">My Orders</a></li>
            <li><a href="student_dashboard.php?action=create">Request Order</a></li>
			<li><a href="student_dashboard.php?action=payment">Payment</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <?php if ($action == 'create'): ?>
            <h1>Request a New Order</h1>

            <!-- Request Order Form -->
            <div class="order-form">
                <h3>Order Details</h3>
                <form method="POST" action="" enctype="multipart/form-data">
                    <label for="package_type">Package Type:</label>
                    <select name="package_type" id="package_type" required>
                        <option value="Black and White">Black and White</option>
                        <option value="Color">Color</option>
                    </select>

                    <label for="description">Description:</label>
                    <textarea name="description" id="description" required></textarea>

                    <label for="file_to_print">Upload File:</label>
                    <input type="file" name="file_to_print" id="file_to_print" required>

                    <!-- No price field as it will be set by admin/staff -->
                    <button type="submit" name="create_order">Create Order</button>
                </form>
            </div>

        <?php elseif ($action == 'payment'): ?>
        <h1>Make a Payment</h1>

<!-- Payment Form -->
<div class="order-form">
    <h3>Payment Details</h3>
    <form method="POST" action="">
        <label for="order_id">Order ID:</label>
        <select name="order_id" id="order_id" required>
            <?php foreach ($orders as $order): ?>
                <?php if ($order['PaymentStatus'] !== 'Paid'): ?>
                    <option value="<?php echo htmlspecialchars($order['OrderID']); ?>">
                        <?php echo htmlspecialchars($order['OrderID']); ?>
                    </option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>

        <label for="payment_method">Payment Method:</label>
        <select name="payment_method" id="payment_method" required>
            <option value="Membership Card">Membership Card</option>
            <option value="Cash">Cash</option>
        </select>

        <label for="amount">Amount:</label>
        <input type="text" name="amount" id="amount" required>

        <button type="submit" name="make_payment">Submit Payment</button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_payment'])) {
        $orderId = htmlspecialchars($_POST['order_id']);
        $paymentMethod = htmlspecialchars($_POST['payment_method']);
        $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
        $paymentDate = date('Y-m-d H:i:s');

        if ($amount > 0) {
            if (makePayment($conn, $orderId, $paymentMethod, $paymentDate)) {
                updatePaymentStatus($conn, $orderId, 'Paid');
                echo "<p>Payment successfully recorded, and Order ID $orderId has been updated.</p>";
            } else {
                echo "<p>Failed to record payment. Please try again.</p>";
            }
        } else {
            echo "<p>Invalid payment amount. Please enter a positive number.</p>";
        }
    }
    ?>
</div>



    
        <?php elseif ($action == 'orders'): ?>
            <h1>My Orders</h1>

            <!-- Orders Table -->
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Package Type</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Order Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7">No orders found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['OrderID']; ?></td>
                                <td><?php echo $order['PackageType']; ?></td>
                                <td><?php echo $order['Description']; ?></td>
                                <td><?php echo $order['Price']; ?></td>
                                <td><?php echo $order['Status']; ?></td>
                                <td><?php echo $order['OrderDate']; ?></td>
                                <td>
                                    <a href="student_dashboard.php?action=edit&order_id=<?php echo $order['OrderID']; ?>" class="btn">Edit</a>
                                    <a href="student_dashboard.php?delete_order=<?php echo $order['OrderID']; ?>" class="btn" onclick="return confirm('Are you sure you want to delete this order?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php elseif ($action == 'edit' && isset($_GET['order_id'])): ?>
            <?php 
            $orderId = $_GET['order_id'];
            $stmt = $conn->prepare("SELECT * FROM orders WHERE OrderID = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            ?>
            <h1>Edit Order</h1>
            <div class="order-form">
                <form method="POST" action="">
                    <input type="hidden" name="order_id" value="<?php echo $order['OrderID']; ?>">

                    <label for="package_type">Package Type:</label>
                    <select name="package_type" id="package_type" required>
                        <option value="Black and White" <?php echo $order['PackageType'] == 'Black and White' ? 'selected' : ''; ?>>Black and White</option>
                        <option value="Color" <?php echo $order['PackageType'] == 'Color' ? 'selected' : ''; ?>>Color</option>
                    </select>

                    <label for="description">Description:</label>
                    <textarea name="description" id="description" required><?php echo $order['Description']; ?></textarea>

                    <button type="submit" name="update_order">Update Order</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
