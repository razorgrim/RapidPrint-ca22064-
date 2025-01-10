<?php
session_start();
include('includes/db.php');

// Check if an order ID is provided
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id'])) {
    $orderId = $_POST['order_id'];

    // Fetch the UserID using the given OrderID
    $userQuery = "SELECT UserID FROM Orders WHERE OrderID = :orderId";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->execute(['orderId' => $orderId]);
    $userId = $userStmt->fetchColumn();

    if (!$userId) {
        echo "<h2>No user found for OrderID: $orderId</h2>";
        exit;
    }

    // Fetch all orders for the same UserID
    $query = "
        SELECT 
            Orders.OrderID,
            Orders.UserID,
            Orders.OrderDate,
            Orders.Price,
            OrderLine.OrderLineID,
            PrintingPackage.PackageName AS ProductName,
            OrderLine.Quantity,
            PrintingPackage.Price,
            (OrderLine.Quantity * PrintingPackage.Price) AS LineTotal
        FROM 
            Orders
        JOIN 
            OrderLine ON Orders.OrderID = OrderLine.OrderID
        JOIN 
            PrintingPackage ON OrderLine.PackageID = PrintingPackage.PackageID
        WHERE 
            Orders.UserID = :userId";
    
    $stmt = $conn->prepare($query); // Prepare the query
    $stmt->execute(['userId' => $userId]); // Execute with parameter
    $invoiceData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if data exists
    if (empty($invoiceData)) {
        echo "<h2>No data found for UserID: $userId</h2>";
        exit;
    }

    // Calculate total dynamically
    $totalAmount = 0;
    foreach ($invoiceData as $line) {
        $totalAmount += $line['LineTotal'];
    }
} else {
    echo "<h2>Invalid Request</h2>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body { 
        margin-top: 20px; 
        background-color: #eee; 
        background-image: url('assets/images/background.jpg'); 
        background-size: cover; 
        background-position: center; 
    }

    .card { 
        box-shadow: 0 20px 27px 0 rgb(0 0 0 / 5%); 
        border-radius: 1rem; 
    }
    </style>
</head>
<header>
<link rel="stylesheet" href="style.css">
        <h1>Staff Dashboard</h1>
        <nav class="menu">
            <a href="manage_orders.php">Manage Orders</a>
            <a href="rewards.php">Rewards</a>
            <a href="staff_dashboard.php">Reports</a>
            <a href="logout.php">Logout</a>
        </nav>
</header>
<body>
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="invoice-title">
                        <h4 class="float-end font-size-15">Invoice for UserID: <?= htmlspecialchars($userId); ?></h4>
                        <div class="mb-4">
                            <h2 class="mb-1 text-muted">RapidPrint</h2>
                        </div>
                        <div class="text-muted">
                            <p class="mb-1">UMPSA</p>
                            <p class="mb-1"><i class="uil uil-envelope-alt me-1"></i> STAFF@rapidprint.com</p>
                            <p><i class="uil uil-phone me-1"></i> 012-345-6789</p>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="py-2">
                        <h5 class="font-size-15">Order Summary</h5>
                        <div class="table-responsive">
                            <table class="table align-middle table-nowrap table-centered mb-0">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Item</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th class="text-end">Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($invoiceData as $line):
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($line['OrderID']); ?></td>
                                        <td><?= htmlspecialchars($line['ProductName']); ?></td>
                                        <td>RM <?= number_format($line['Price'], 2); ?></td>
                                        <td>
                                            <form onsubmit="return updateInvoice(this);">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($line['OrderID']); ?>">
                                                <input type="hidden" name="order_line_id" value="<?= htmlspecialchars($line['OrderLineID']); ?>">
                                                <div class="input-group">
                                                    <input type="number" name="quantity" value="<?= htmlspecialchars($line['Quantity']); ?>" min="1" class="form-control">
                                                    <button type="submit" class="btn btn-primary">Update</button>
                                                </div>
                                            </form>
                                        </td>
                                        <td class="text-end">RM <?= number_format($line['LineTotal'], 2); ?></td>
                                        <td>
                                            <form method="post" action="delete_invoice.php">
                                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($line['OrderID']); ?>">
                                                <input type="hidden" name="order_line_id" value="<?= htmlspecialchars($line['OrderLineID']); ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <th colspan="4" class="text-end">Total</th>
                                        <td class="text-end">
                                            <h4 class="m-0 fw-semibold">RM <?= number_format($totalAmount, 2); ?></h4>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-print-none mt-4">
                            <div class="float-end">
                                <a href="javascript:window.print()" class="btn btn-success me-1"><i class="fa fa-print"></i> Print</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateInvoice(form) {
    const formData = new FormData(form);

    fetch('update_invoice.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the order.');
    });

    return false; // Prevent default form submission
}
</script>
</body>
</html>
