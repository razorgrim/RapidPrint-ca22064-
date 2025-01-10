<?php
include('includes/session.php');
include('includes/db.php');

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $branchName = $_POST['BranchName'];
    $address = $_POST['Address'];
    $phoneNumber = $_POST['PhoneNumber'];
    $status = $_POST['Status'];

    $stmt = $conn->prepare("INSERT INTO koperasibranch (BranchName, Address, PhoneNumber, Status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$branchName, $address, $phoneNumber, $status]);

    header('Location: admin_dashboard.php?target=koperasibranch');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Branch</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #66CDAA;
        }

        .form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 400px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #2d3a3f;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #2d3a3f;
        }

        input, select {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        button {
            padding: 10px;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 10px;
        }

        button[type="submit"] {
            background-color: #66CDAA;
            color: white;
        }

        button[type="submit"]:hover {
            opacity: 0.9;
        }

        button[type="reset"] {
            background-color: #e55c5c;
            color: white;
        }

        button[type="reset"]:hover {
            opacity: 0.9;
        }

        a.btn {
            text-decoration: none;
            color: white;
            background-color: #2d3a3f;
            padding: 10px 15px;
            border-radius: 4px;
            display: inline-block;
            text-align: center;
        }

        a.btn:hover {
            opacity: 0.8;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Add New Branch</h1>
        <form method="POST">
            <label for="BranchName">Branch Name:</label>
            <input type="text" id="BranchName" name="BranchName" required>

            <label for="Address">Address:</label>
            <input type="text" id="Address" name="Address" required>

            <label for="PhoneNumber">Phone Number:</label>
            <input type="text" id="PhoneNumber" name="PhoneNumber" required>

            <label for="Status">Status:</label>
            <select id="Status" name="Status" required>
                <option value="Active">Active</option>
                <option value="Unavailable">Unavailable</option>
            </select>

            <div class="button-group">
                <button type="submit">Add Branch</button>
                <button type="reset">Reset</button>
            </div>
        </form>
        <a href="admin_dashboard.php?target=koperasibranch" class="btn">Back to Dashboard</a>
    </div>
</body>
</html>
