<?php
include('includes/db.php')
// Fetch all invoices from the database
$queryInvoices = "SELECT * FROM invoice";
$stmtInvoices = $conn->prepare($queryInvoices);
$stmtInvoices->execute();
$invoices = $stmtInvoices->fetchAll(PDO::FETCH_ASSOC);

// Calculate the grand total of all invoices
$grandTotal = 0;
foreach ($invoices as $invoice) {
    // Replace 'total_amount' with the correct column name from the database
    $grandTotal += $invoice['TotalAmount']; // Change this if the column name is different
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Overview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
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
            <a href="invoice_overview.php">Generated Invoice</a>
            <a href="#">Bonus </a>
            <a href="#">Staff Profile</a>
        </div>
<body>
<div class="container mt-5">
    <div class="card">
        <div class="card-header text-center">
            <h2>Invoice Overview</h2>
        </div>
        <div class="card-body">
            <h5>Invoices</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Invoice ID</th>
                        <th>User ID</th>
                        <th>Total Amount (RM)</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td><?= htmlspecialchars($invoice['InvoiceID']) ?></td>
                            <td><?= htmlspecialchars($invoice['UserID']) ?></td>
                            <td><?= number_format($invoice['TotalAmount'], 2) ?></td> <!-- Ensure this column exists -->
                            <td><?= htmlspecialchars($invoice['InvoiceDate']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <h5 class="text-end">Grand Total: RM<?= number_format($grandTotal, 2) ?></h5>
        </div>
    </div>
</div>
</body>
</html>
