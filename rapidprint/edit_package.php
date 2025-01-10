<?php
include('includes/session.php');
include('includes/db.php');

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM printingpackage WHERE PackageID = ?");
$stmt->execute([$id]);
$package = $stmt->fetch();

// Fetch all branches from koperasibranch
$branchStmt = $conn->prepare("SELECT * FROM koperasibranch");
$branchStmt->execute();
$branches = $branchStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $branchID = $_POST['BranchID'];
    $packageName = $_POST['PackageName'];
    $price = $_POST['Price'];
    $availabilityStatus = $_POST['AvailabilityStatus'];
    $printingColor = $_POST['PrintingColor'];

    $stmt = $conn->prepare("UPDATE printingpackage SET BranchID = ?, PackageName = ?, Price = ?, AvailabilityStatus = ?, PrintingColor = ? WHERE PackageID = ?");
    $stmt->execute([$branchID, $packageName, $price, $availabilityStatus, $printingColor, $id]);

    header('Location: admin_dashboard.php?target=printingpackage');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Printing Package</title>
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
        <h1>Edit Printing Package</h1>
        <form method="POST">
            <label for="BranchID">Select Branch:</label>
            <select id="BranchID" name="BranchID" required>
                <option value="" disabled>Select a Branch</option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?= $branch['BranchID']; ?>" <?php echo $branch['BranchID'] == $package['BranchID'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($branch['BranchName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="PackageName">Package Name:</label>
            <input type="text" id="PackageName" name="PackageName" value="<?php echo htmlspecialchars($package['PackageName']); ?>" required>

            <label for="Price">Price:</label>
            <input type="number" id="Price" name="Price" value="<?php echo htmlspecialchars($package['Price']); ?>" required>

            <label for="AvailabilityStatus">Availability Status:</label>
            <select id="AvailabilityStatus" name="AvailabilityStatus" required>
                <option value="Available" <?php echo $package['AvailabilityStatus'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                <option value="Unavailable" <?php echo $package['AvailabilityStatus'] == 'Unavailable' ? 'selected' : ''; ?>>Unavailable</option>
            </select>

            <label for="PrintingColor">Printing Color:</label>
            <input type="text" id="PrintingColor" name="PrintingColor" value="<?php echo htmlspecialchars($package['PrintingColor']); ?>" required>

            <div class="button-group">
                <button type="submit">Save Changes</button>
                <button type="reset">Reset</button>
            </div>
        </form>
        <a href="admin_dashboard.php?target=printingpackage" class="btn">Back to Dashboard</a>
    </div>
</body>
</html>
