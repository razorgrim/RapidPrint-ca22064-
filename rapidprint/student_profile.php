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

// Fetch student profile data
try {
    $stmt = $conn->prepare("SELECT u.Name, u.Email, a.DateOfBirth, a.Gender, a.PhoneNumber, a.Address, a.matricID, a.ProfilePicture
                             FROM user u
                             LEFT JOIN student a ON u.UserID = a.UserID
                             WHERE u.UserID = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        echo "<div class='error-message'>Profile not found.</div>";
        exit();
    }
} catch (PDOException $e) {
    echo "<div class='error-message'>Error: " . $e->getMessage() . "</div>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
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

        .profile-info {
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 15px;
            font-size: 16px;
            line-height: 1.6;
        }

        .profile-info div {
            display: flex;
            align-items: center;
        }

        .profile-info label {
            font-weight: 600;
            margin-right: 10px;
        }

        .profile-info i {
            margin-right: 12px;
            color: #4CAF50;
            font-size: 20px;
        }

        img {
            display: block;
            margin: 20px auto;
            max-width: 140px;
            height: auto;
            border-radius: 50%;
            border: 4px solid #4CAF50;
            transition: transform 0.3s ease;
        }

        img:hover {
            transform: scale(1.1);
        }

        .error-message {
            text-align: center;
            color: red;
            font-weight: bold;
        }

    </style>
</head>
<body>
    <h1>Welcome to Student Profile</h1>

    <div class="profile-container">
        <h2><i class="fas fa-user"></i> Student Profile</h2>

        <!-- Profile Picture -->
        <div style="text-align:center;">
            <?php if ($profile['ProfilePicture']): ?>
                <img src="<?= htmlspecialchars($profile['ProfilePicture']) ?>" alt="Profile Picture">
            <?php else: ?>
                <img src="uploads/profile_pictures/default.jpg" alt="Default Profile Picture">
            <?php endif; ?>
        </div>

        <div class="profile-info">
            <div><i class="fas fa-user"></i><label>Name:</label> <?= htmlspecialchars($profile['Name']) ?></div>
			<div><i class="fas fa-id-card"></i><label>Matric Number:</label> <?= htmlspecialchars($profile['matricID']) ?></div>
            <div><i class="fas fa-envelope"></i><label>Email:</label> <?= htmlspecialchars($profile['Email']) ?></div>
            <div><i class="fas fa-calendar-alt"></i><label>Date of Birth:</label> <?= htmlspecialchars($profile['DateOfBirth'] ?? 'N/A') ?></div>
            <div><i class="fas fa-venus-mars"></i><label>Gender:</label> <?= htmlspecialchars($profile['Gender'] ?? 'N/A') ?></div>
            <div><i class="fas fa-phone"></i><label>Phone Number:</label> <?= htmlspecialchars($profile['PhoneNumber'] ?? 'N/A') ?></div>
            <div><i class="fas fa-map-marker-alt"></i><label>Address:</label> <?= htmlspecialchars($profile['Address'] ?? 'N/A') ?></div>
        </div>

    </div>
</body>
</html>
