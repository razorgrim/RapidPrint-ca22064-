<?php
include('includes/db.php');
$user_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("SELECT ProfilePicture
                             FROM student           
                             WHERE UserID = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
}
catch (PDOException $e) {
    echo "<div class='error-message'>Error: " . $e->getMessage() . "</div>";
    exit();
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        /* Sidepanel styling */
        .sidepanel {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #2d3a3f;
            overflow-x: hidden;
            transition: width 0.3s ease;
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }

        .sidepanel.shrink {
            width: 70px;
        }

        .sidepanel .logo {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .sidepanel.shrink .logo img {
            width: 30px;
            height: 30px;
        }

        .sidepanel.shrink .logo span {
            display: none;
        }

        .sidepanel .logo img {
            width: 40px;
            height: 40px;
        }

        .sidepanel .logo span {
            font-size: 20px;
            font-weight: bold;
            color: white;
        }

        .sidepanel button {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            text-align: left;
            padding: 15px 20px;
            width: 100%;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .sidepanel button:hover {
            background-color: #57b59a;
        }

        .sidepanel button i {
            font-size: 20px;
        }

        .sidepanel button span {
            transition: opacity 0.3s ease;
        }

        .sidepanel.shrink button span {
            opacity: 0;
            pointer-events: none;
        }

        .sidepanel .toggle-btn {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: background 0.3s ease;
        }

        .sidepanel .toggle-btn:hover {
            background-color:rgb(35, 50, 35);
        }
    </style>
    <script>
        function toggleSidePanel() {
            const sidePanel = document.querySelector('.sidepanel');
            sidePanel.classList.toggle('shrink');
        }
    </script>
</head>
<body>
    <div class="sidepanel">
        <div class="logo">
            <img src=<?= htmlspecialchars($profile['ProfilePicture']) ?> alt="RapidPrint Logo">
            <span>Student Profile</span>
        </div>
        <button onclick="window.location.href='student_dashboard.php'">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </button>
        <button onclick="window.location.href='student_edit.php'">
            <i class="fas fa-edit"></i>
            <span>Edit Profile</span>
        </button>
        <form method="POST" action="student_profile.php" onsubmit="return confirm('Are you sure you want to delete your profile?');">
            <button type="submit" name="delete_profile" style="color: #f44336;">
                <i class="fas fa-trash"></i>
                <span>Delete Profile</span>
            </button>
        </form>
			<button onclick="window.location.href='membership.php'">
				<i class="fas fa-id-card"></i>
				<span>Membership</span>
			</button>
        <button onclick="window.location.href='logout.php'">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </button>
        
        <button class="toggle-btn" onclick="toggleSidePanel()">
            <i class="fa-solid fa-list"></i>
        </button>
    </div>
</body>
</html>
