<?php
include('includes/db.php');
include('includes/session.php');

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
    $stmt = $conn->prepare("
        SELECT u.Name, u.Email, a.DateOfBirth, a.Gender, a.PhoneNumber, a.Address, a.matricID, a.ProfilePicture
        FROM user u
        LEFT JOIN student a ON u.UserID = a.UserID
        WHERE u.UserID = ?
    ");
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

// Handle profile updates
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];
	$matricID = $_POST['matricID'];

    // Handle file upload for profile picture
    $profile_picture = $profile['ProfilePicture'];
    if (!empty($_FILES['profile_picture']['name'])) {
        $target_dir = "uploads/profile_pictures/";
        $target_file = $target_dir . basename($_FILES['profile_picture']['name']);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate image file
        $allowed_types = ['jpg', 'jpeg', 'png'];
        if (in_array($imageFileType, $allowed_types) && $_FILES['profile_picture']['size'] < 5000000) {
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                $profile_picture = $target_file;
            } else {
                echo "<div class='error-message'>Error uploading profile picture.</div>";
            }
        } else {
            echo "<div class='error-message'>Invalid file type or size. Only JPG, JPEG, and PNG under 5MB allowed.</div>";
        }
    }

    // Update profile in the database
    try {
        $update_stmt = $conn->prepare("
            UPDATE user u
            LEFT JOIN student a ON u.UserID = a.UserID
            SET u.Name = ?, u.Email = ?, a.DateOfBirth = ?, a.Gender = ?, a.PhoneNumber = ?, a.Address = ?, a.matricID = ?, a.ProfilePicture = ?
            WHERE u.UserID = ?
        ");
        $update_stmt->execute([$name, $email, $date_of_birth, $gender, $phone_number, $address, $matricID, $profile_picture, $user_id]);

        echo "<div class='success-message'>Profile updated successfully.</div>";
        header("Refresh: 3; url=student_profile.php");
        exit();
    } catch (PDOException $e) {
        echo "<div class='error-message'>Error: " . $e->getMessage() . "</div>";
    }
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate password
    if ($new_password !== $confirm_password) {
        echo "<div class='error-message'>Passwords do not match.</div>";
    } else {
        try {
            // Verify current password
            $password_stmt = $conn->prepare("SELECT Password FROM user WHERE UserID = ?");
            $password_stmt->execute([$user_id]);
            $user = $password_stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($current_password, $user['Password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_password_stmt = $conn->prepare("UPDATE user SET Password = ? WHERE UserID = ?");
                $update_password_stmt->execute([$hashed_password, $user_id]);

                echo "<div class='success-message'>Password changed successfully.</div>";
            } else {
                echo "<div class='error-message'>Current password is incorrect.</div>";
            }
        } catch (PDOException $e) {
            echo "<div class='error-message'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Handle profile deletion
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_profile'])) {
    try {
        // Start the transaction
        $conn->beginTransaction();
    
        // Prepare and execute each DELETE statement
        $delete_student = $conn->prepare("DELETE FROM student WHERE UserID = ?");
        $delete_student->execute([$user_id]);
    
        $delete_membership = $conn->prepare("DELETE FROM membership WHERE UserID = ?");
        $delete_membership->execute([$user_id]);
    
        $delete_user = $conn->prepare("DELETE FROM user WHERE UserID = ?");
        $delete_user->execute([$user_id]);
    
        // Commit the transaction
        $conn->commit();
    
        echo "User deleted successfully.";
        session_unset();    // Unset all session variables
        session_destroy();  // Destroy the session
        header("Location: logout.php"); // Redirect to the logout page
        exit();
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollBack();
        echo "Error deleting user: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #66CDAA; /* Light green background */
            color: #333;
            margin: 0;
            padding: 0;
        }

        .profile-container {
            width: 90%;
            max-width: 600px;
            margin: 30px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border: 2px solid #4CAF50; /* Green border to match theme */
        }

        h2, h3 {
            text-align: center;
            color: #4CAF50; /* Green for headings */
        }

        form {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="date"],
        input[type="file"],
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            text-align: center;
            cursor: pointer;
            border-radius: 4px;
            font-size: 16px;
            width: 100%;
        }

        button:hover {
            background-color: #45a049;
        }

        .success-message {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .error-message {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        img {
            display: block;
            margin: 10px auto;
            max-width: 100px;
            height: auto;
            border-radius: 50%;
            border: 2px solid #ccc;
        }

        .password-section {
            display: none;
        }

        button[type="submit"]:nth-child(2) {
            background-color: #f44336;
        }

        button[type="submit"]:nth-child(2):hover {
            background-color: #e53935;
        }

        .reset-btn {
            background-color: #ffa500;
        }

        .reset-btn:hover {
            background-color: #e68900;
        }

        .back-btn {
            background-color: #007bff;
        }

        .back-btn:hover {
            background-color: #0056b3;
        }

        .delete-btn {
            background-color: #f44336;
        }

        .delete-btn:hover {
            background-color: #e53935;
        }
    </style>
    <script>
        function togglePasswordSection() {
            var passwordSection = document.querySelector('.password-section');
            passwordSection.style.display = passwordSection.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</head>
<body>
<div class="profile-container">
    <h2>Student Profile</h2>

    <!-- Profile Picture -->
    <div style="text-align:center;">
        <?php if ($profile['ProfilePicture']): ?>
            <img src="<?= htmlspecialchars($profile['ProfilePicture']) ?>" alt="Profile Picture">
        <?php else: ?>
            <img src="uploads/profile_pictures/default.jpg" alt="Default Profile Picture">
        <?php endif; ?>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($profile['Name']) ?>" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($profile['Email']) ?>" required>

        <label for="date_of_birth">Date of Birth:</label>
        <input type="date" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($profile['DateOfBirth'] ?? '') ?>">

        <label for="gender">Gender:</label>
        <select id="gender" name="gender">
            <option value="Male" <?= ($profile['Gender'] ?? 'Male') === 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= ($profile['Gender'] ?? 'Male') === 'Female' ? 'selected' : '' ?>>Female</option>
        </select>

        <label for="phone_number">Phone Number:</label>
        <input type="text" id="phone_number" name="phone_number" value="<?= htmlspecialchars($profile['PhoneNumber'] ?? '') ?>">

        <label for="address">Address:</label>
        <input type="text" id="address" name="address" value="<?= htmlspecialchars($profile['Address'] ?? '') ?>">
		
		<label for="matricID">Matric Number:</label>
        <input type="text" id="matricID" name="matricID" value="<?= htmlspecialchars($profile['matricID'] ?? '') ?>">

        <label for="profile_picture">Profile Picture:</label>
        <input type="file" id="profile_picture" name="profile_picture">

        <button type="submit" name="update_profile">Update Profile</button>
        <button type="reset" class="reset-btn">Reset</button>
        <button type="button" class="back-btn" onclick="window.location.href='student_profile.php'">Back to Profile</button>
    </form>

    <h3><button type="button" onclick="togglePasswordSection()">Change Password</button></h3>

    <div class="password-section">
        <form method="POST">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required>

            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit" name="change_password">Submit</button>
        </form>
    </div>

    <form method="POST" onsubmit="return confirm('Are you sure you want to delete your profile?');">
        <button type="submit" name="delete_profile" class="delete-btn">Delete Profile</button>
    </form>
</div>
</body>
</html>
<?php include('includes/footer.php'); ?>