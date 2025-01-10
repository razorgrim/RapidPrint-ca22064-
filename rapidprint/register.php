<?php
include('includes/db.php');
include('includes/session.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<div class='error-message'>You do not have authorization to access this page.</div>";
    header("Refresh: 3; url=index.php"); // Redirect to the homepage after 3 seconds
    exit();
}

// Generate a 10-digit random membership card number
function generateMembershipCardNumber() {
    return str_pad(mt_rand(1, 9999999999), 10, '0', STR_PAD_LEFT);
}

$message = ""; // Initialize message
$message_type = ""; // Initialize message type (success or error)

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user input
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $date_of_birth = $_POST['date_of_birth'] ?? null; // Optional
    $gender = $_POST['gender'] ?? 'Male'; // Default to Male
    $phone_number = $_POST['phone_number'] ?? null;
    $address = $_POST['address'] ?? null;

    // Check if passwords match
    if ($password !== $confirm_password) {
        $message = "Passwords do not match. Please try again.";
        $message_type = 'error';
    } else {
        try {
            // Check if email already exists
            $check_stmt = $conn->prepare("SELECT * FROM user WHERE Email = ?");
            $check_stmt->execute([$email]);
            if ($check_stmt->rowCount() > 0) {
                $message = "Email already registered. Please use another email.";
                $message_type = 'error';
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Generate a random 10-digit membership card number
                $membership_card_number = generateMembershipCardNumber();

                // Insert user data into the User table
                $stmt = $conn->prepare("
                    INSERT INTO user (Name, Email, Password, Role, MembershipCardNumber)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $email, $hashed_password, $role, $membership_card_number]);

                // Get the UserID of the newly inserted user
                $user_id = $conn->lastInsertId();

                // Insert additional data based on role
                if ($role === 'admin') {
                    $admin_stmt = $conn->prepare("
                        INSERT INTO admin (UserID, DateOfBirth, Gender, PhoneNumber, Address)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $admin_stmt->execute([$user_id, $date_of_birth, $gender, $phone_number, $address]);
                } elseif ($role === 'staff') {
                    $staff_stmt = $conn->prepare("
                        INSERT INTO staff (UserID, DateOfBirth, Gender, PhoneNumber, Address)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $staff_stmt->execute([$user_id, $date_of_birth, $gender, $phone_number, $address]);
                } elseif ($role === 'student') {
                    $matricID = "0"; // Default value for Matric ID
                    $student_stmt = $conn->prepare("
                        INSERT INTO student (UserID, DateOfBirth, Gender, PhoneNumber, Address, MatricID)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $student_stmt->execute([$user_id, $date_of_birth, $gender, $phone_number, $address, $matricID]);
                }

                // Insert data into the Membership table
                $default_status = "Unactivated"; // Default status
                $default_balance = 0.00; // Default card balance
                $qr_code = "Generated_QR_" . uniqid(); // Placeholder for QR code generation

                $membership_stmt = $conn->prepare("
                    INSERT INTO membership (UserID, CardBalance, MembershipStatus, QRCode)
                    VALUES (?, ?, ?, ?)
                ");
                $membership_stmt->execute([$user_id, $default_balance, $default_status, $qr_code]);

                $message = "Registration successful! Your Membership Card Number is: <strong>$membership_card_number</strong>.";
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: #66CDAA;
            background-size: cover;
            color: #333;
            line-height: 1.6;
        }

        .back-to-dashboard {
            display: inline-block;
            margin: 20px;
            padding: 12px 20px;
            background-color:rgb(3, 157, 14);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .back-to-dashboard:hover {
            background-color: #003f8a;
        }

        .register-container {
            max-width: 500px;
            margin: 80px auto;
            padding: 40px 30px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .register-container h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: bold;
            color: #66CDAA;
        }

        .register-container label {
            font-size: 14px;
            font-weight: bold;
            color: #444;
        }

        .success-message, .error-message {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-label i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <a href="admin_dashboard.php?target=users" class="btn btn-primary back-to-dashboard">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    <div class="container">
        <div class="register-container shadow-lg">
            <h2><i class="fas fa-user-plus"></i> Register</h2>

            <?php
            if (!empty($message)) {
                echo "<div class='" . ($message_type === 'success' ? 'alert alert-success' : 'alert alert-danger') . "'>$message</div>";
            }
            ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label"><i class="fas fa-user"></i> Name:</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email:</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label"><i class="fas fa-lock"></i> Password:</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label"><i class="fas fa-lock"></i> Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="date_of_birth" class="form-label"><i class="fas fa-calendar-alt"></i> Date of Birth:</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-control">
                </div>

                <div class="mb-3">
                    <label for="gender" class="form-label"><i class="fas fa-venus-mars"></i> Gender:</label>
                    <select id="gender" name="gender" class="form-select">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="phone_number" class="form-label"><i class="fas fa-phone"></i> Phone Number:</label>
                    <input type="text" id="phone_number" name="phone_number" class="form-control">
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label"><i class="fas fa-home"></i> Address:</label>
                    <input type="text" id="address" name="address" class="form-control">
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label"><i class="fas fa-user-tag"></i> User Type:</label>
                    <select id="role" name="role" class="form-select" required>
                        <option value="student">Student</option>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-success w-100">
                    <i class="fas fa-check-circle"></i> Register
                </button>
            </form>
        </div>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include('includes/footer.php'); ?>
