<?php
include('includes/db.php');
include('includes/session.php');
include('includes/sidepanel_student.php');

// Check if the user is logged in and is a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    echo "<div class='error-message'>Unauthorized access. Redirecting...</div>";
    header("Refresh: 3; url=index.php");
    exit();
}

// Get the logged-in student's UserID
$user_id = $_SESSION['user_id'];

// Fetch student details
$studentQuery = "SELECT * FROM student WHERE UserID = :userId";
$studentStmt = $conn->prepare($studentQuery);
$studentStmt->bindValue(':userId', $user_id, PDO::PARAM_INT);
$studentStmt->execute();
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);

// Fetch membership details
$membershipQuery = "SELECT * FROM membership WHERE UserID = :userId";
$membershipStmt = $conn->prepare($membershipQuery);
$membershipStmt->bindValue(':userId', $user_id, PDO::PARAM_INT);
$membershipStmt->execute();
$membership = $membershipStmt->fetch(PDO::FETCH_ASSOC);

// Fetch user details (needed for MembershipCardNumber)
$userQuery = "SELECT * FROM user WHERE UserID = :userId";
$userStmt = $conn->prepare($userQuery);
$userStmt->bindValue(':userId', $user_id, PDO::PARAM_INT);
$userStmt->execute();
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

// Handle file upload and status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cardMatric'])) {
    $targetDir = "uploads/";
    $fileName = basename($_FILES["cardMatric"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

    // Allow only certain file types
    $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'];
    if (in_array(strtolower($fileType), $allowedTypes)) {
        if (move_uploaded_file($_FILES["cardMatric"]["tmp_name"], $targetFilePath)) {
            // Update the cardMatric in the database
            $updateSql = "UPDATE student SET cardMatric = :cardMatric WHERE UserID = :userId";
            $stmt = $conn->prepare($updateSql);
            $stmt->bindValue(':cardMatric', $targetFilePath, PDO::PARAM_STR);
            $stmt->bindValue(':userId', $user_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                // Update applyStatus to 'Pending'
                $applySql = "UPDATE membership SET applyStatus = 'Pending' WHERE UserID = :userId";
                $applyStmt = $conn->prepare($applySql);
                $applyStmt->bindValue(':userId', $user_id, PDO::PARAM_INT);
                $applyStmt->execute();

                echo "<div class='success-message'>Card uploaded and application submitted successfully!</div>";
            } else {
                echo "<div class='error-message'>Error updating the database.</div>";
            }
        } else {
            echo "<div class='error-message'>Error uploading file.</div>";
        }
    } else {
        echo "<div class='error-message'>Invalid file type. Only JPG, JPEG, PNG, and PDF files are allowed.</div>";
    }
}

// Handle terminate membership
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['terminateCard'])) {
    $terminateSql = "UPDATE membership SET MembershipStatus = 'Unactivated', applyStatus = NULL WHERE UserID = :userId";
    $terminateStmt = $conn->prepare($terminateSql);
    $terminateStmt->bindValue(':userId', $user_id, PDO::PARAM_INT);
    if ($terminateStmt->execute()) {
        echo "<script>alert('You have terminated your membership.');</script>";
        // header("Refresh: 0");
        //header("Refresh: 1; url=membership.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #66CDAA;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .profile-container {
            width: 90%;
            max-width: 700px;
            margin: 40px auto;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            overflow: hidden;
        }
        h1 {
            text-align: center;
            margin-top: 50px;
            font-size: 28px;
            color: #333;
        }
        h2 {
            text-align: center;
            color: #4CAF50;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .card {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            text-align: center;
        }
        .card h3 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        .card p {
            margin: 10px 0;
            font-size: 18px;
            color: #555;
        }
        .card-number {
            font-size: 22px;
            font-weight: bold;
            color: #4CAF50;
            margin-top: 15px;
        }
        .card-balance {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-top: 15px;
        }
        button {
            display: inline-block;
            font-size: 16px;
            font-weight: bold;
            color: #fff;
            background-color: #4CAF50;
            border: none;
            border-radius: 25px;
            padding: 10px 20px;
            margin: 15px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        button:hover {
            background-color: #3e8e41;
            transform: scale(1.05);
        }
        button.terminate {
            background-color: #ff5c5c;
        }
        button.terminate:hover {
            background-color: #e04c4c;
        }
        label {
            font-size: 16px;
            color: #555;
            display: block;
            margin-bottom: 10px;
        }
        input[type="file"] {
            margin-bottom: 15px;
        }
        .error-message {
            text-align: center;
            color: red;
            font-weight: bold;
        }
        .success-message {
            text-align: center;
            color: green;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Rapid Print</h1>
    <div class="profile-container">
        <h2><i class="fas fa-address-card"></i> Membership Details</h2>
        <?php if ($membership['MembershipStatus'] === 'Unactivated'): ?>
            <?php if ($membership['applyStatus'] === 'Pending'): ?>
                <p>Your application status is: <strong>Pending</strong></p>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data">
                    <label for="cardMatric">Upload Matric Card:</label>
                    <input type="file" name="cardMatric" required>
                    <button type="submit">Apply</button>
                </form>
            <?php endif; ?>
        <?php elseif ($membership['MembershipStatus'] === 'Active'): ?>
            <div class="card">
                <h3>Membership Card</h3>
                <div class="card-number"><?= $user['MembershipCardNumber'] ?></div>
                <div class="card-balance"><?= number_format($membership['CardBalance'], 2) ?></div>
            </div>
            <form method="POST">
                <button type="submit" name="terminateCard" class="terminate" onclick="return confirm('Are you sure you want to terminate your membership?')">Terminate Membership</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

