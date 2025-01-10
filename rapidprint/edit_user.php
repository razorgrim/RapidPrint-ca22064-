<?php
include('includes/db.php');
include('includes/session.php');

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<div class='error-message'>You do not have authorization to access this page.</div>";
    header("Refresh: 3; url=index.php");
    exit();
}

// Fetch user data if ID is provided
if (!isset($_GET['id'])) {
    echo "<div class='error-message'>No user ID specified.</div>";
    exit();
}

$user_id = intval($_GET['id']);

try {
    // Fetch user data
    $stmt = $conn->prepare("SELECT * FROM user WHERE UserID = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<div class='error-message'>User not found.</div>";
        exit();
    }

    // Fetch additional data based on role
    $additional_data = [];
    if ($user['Role'] === 'admin') {
        $role_stmt = $conn->prepare("SELECT * FROM admin WHERE UserID = ?");
    } elseif ($user['Role'] === 'staff') {
        $role_stmt = $conn->prepare("SELECT * FROM staff WHERE UserID = ?");
    } elseif ($user['Role'] === 'student') {
        $role_stmt = $conn->prepare("SELECT * FROM student WHERE UserID = ?");
    }

    if (isset($role_stmt)) {
        $role_stmt->execute([$user_id]);
        $additional_data = $role_stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    echo "<div class='error-message'>Error: " . $e->getMessage() . "</div>";
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $new_role = $_POST['role'];
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $gender = $_POST['gender'] ?? 'Male';
    $phone_number = $_POST['phone_number'] ?? null;
    $address = $_POST['address'] ?? null;
    $matric_id = $_POST['matric_id'] ?? null;

    try {
        $conn->beginTransaction();

        // Step 1: Update the `user` table
        $update_user_stmt = $conn->prepare("UPDATE user SET Name = ?, Email = ?, Role = ? WHERE UserID = ?");
        $update_user_stmt->execute([$name, $email, $new_role, $user_id]);

        // Step 2: Remove the user from the old role-specific table
        if ($user['Role'] === 'admin') {
            $delete_admin_stmt = $conn->prepare("DELETE FROM admin WHERE UserID = ?");
            $delete_admin_stmt->execute([$user_id]);
        } elseif ($user['Role'] === 'staff') {
            $delete_staff_stmt = $conn->prepare("DELETE FROM staff WHERE UserID = ?");
            $delete_staff_stmt->execute([$user_id]);
        } elseif ($user['Role'] === 'student') {
            $delete_student_stmt = $conn->prepare("DELETE FROM student WHERE UserID = ?");
            $delete_student_stmt->execute([$user_id]);
        }

        // Step 3: Insert the user into the new role-specific table
        if ($new_role === 'admin') {
            $insert_admin_stmt = $conn->prepare("
                INSERT INTO admin (UserID, DateOfBirth, Gender, PhoneNumber, Address)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insert_admin_stmt->execute([$user_id, $date_of_birth, $gender, $phone_number, $address]);
        } elseif ($new_role === 'staff') {
            $insert_staff_stmt = $conn->prepare("
                INSERT INTO staff (UserID, DateOfBirth, Gender, PhoneNumber, Address)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insert_staff_stmt->execute([$user_id, $date_of_birth, $gender, $phone_number, $address]);
        } elseif ($new_role === 'student') {
            $insert_student_stmt = $conn->prepare("
                INSERT INTO student (UserID, DateOfBirth, Gender, PhoneNumber, Address, MatricID)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insert_student_stmt->execute([$user_id, $date_of_birth, $gender, $phone_number, $address, $matric_id]);
        }

        $conn->commit();

        echo "<div class='success-message'>User updated successfully.</div>";
        header("Refresh: 3; url=admin_dashboard.php?target=users");
        exit();
    } catch (PDOException $e) {
        $conn->rollBack();
        echo "<div class='error-message'>Error: " . $e->getMessage() . "</div>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8f9fa;
        }
        .edit-user-container {
            width: 100%;
            max-width: 600px;
            margin: 80px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .edit-user-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        .edit-user-container label {
            display: block;
            margin-bottom: 8px;
            color: #555;
        }
        .edit-user-container input[type="text"],
        .edit-user-container input[type="email"],
        .edit-user-container select,
        .edit-user-container input[type="date"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .edit-user-container .form-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .edit-user-container input[type="submit"],
        .edit-user-container input[type="reset"],
        .edit-user-container .back-button {
            padding: 12px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
        }
        .edit-user-container input[type="submit"] {
            background-color: #28a745;
            color: white;
            border: none;
        }
        .edit-user-container input[type="reset"] {
            background-color: #ffc107;
            color: white;
            border: none;
        }
        .edit-user-container .back-button {
            background-color: #007bff;
            color: white;
            text-align: center;
            display: inline-block;
        }
        .success-message, .error-message {
            text-align: center;
            margin: 10px auto;
            width: 80%;
            padding: 10px;
            border-radius: 4px;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            margin-top: 60px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            margin-top: 60px;
        }
    </style>
</head>
<body>
    <div class="edit-user-container">
        <h2>Edit User</h2>
        <form method="POST">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['Name']) ?>" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['Email']) ?>" required>

            <label for="role">User Type:</label>
            <select id="role" name="role" required>
                <option value="student" <?= $user['Role'] === 'student' ? 'selected' : '' ?>>Student</option>
                <option value="staff" <?= $user['Role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="admin" <?= $user['Role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>

            <label for="date_of_birth">Date of Birth:</label>
            <input type="date" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($additional_data['DateOfBirth'] ?? '') ?>">

            <label for="gender">Gender:</label>
            <select id="gender" name="gender">
                <option value="Male" <?= ($additional_data['Gender'] ?? 'Male') === 'Male' ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= ($additional_data['Gender'] ?? 'Male') === 'Female' ? 'selected' : '' ?>>Female</option>
            </select>

            <label for="phone_number">Phone Number:</label>
            <input type="text" id="phone_number" name="phone_number" value="<?= htmlspecialchars($additional_data['PhoneNumber'] ?? '') ?>">

            <label for="address">Address:</label>
            <input type="text" id="address" name="address" value="<?= htmlspecialchars($additional_data['Address'] ?? '') ?>">

            <?php if ($user['Role'] === 'student'): ?>
                <label for="matric_id">Matric ID:</label>
                <input type="text" id="matric_id" name="matric_id" value="<?= htmlspecialchars($additional_data['matricID'] ?? '') ?>">
            <?php endif; ?>

            <div class="form-buttons">
                <input type="submit" value="Save Changes">
                <input type="reset" value="Reset">
                <a href="admin_dashboard.php?target=users" class="back-button">Back to Dashboard</a>
            </div>
        </form>
    </div>
</body>
</html>

<?php include('includes/footer.php'); ?>
