<?php
include('includes/session.php');
include('includes/db.php');

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
// Fetch metrics data
$totalUsers = $conn->query("SELECT COUNT(*) FROM user")->fetchColumn();
$totalBranches = $conn->query("SELECT COUNT(*) FROM koperasibranch")->fetchColumn();
$totalPackages = $conn->query("SELECT COUNT(*) FROM printingpackage")->fetchColumn();

// Query to get user roles distribution
$query = "SELECT Role, COUNT(*) as count FROM user GROUP BY Role";
$stmt = $conn->query($query);
$userRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query to get packages per branch
$query = "SELECT b.BranchName, COUNT(p.PackageID) as count 
          FROM printingpackage p
          JOIN koperasibranch b ON p.BranchID = b.BranchID
          GROUP BY b.BranchName";
$stmt = $conn->query($query);
$packagesByBranch = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Handle CRUD actions for koperasibranch, printingpackage, and users
$action = $_GET['action'] ?? '';
$target = $_GET['target'] ?? '';
$search = $_GET['search'] ?? ''; // Get search term

function fetchAll($conn, $table, $search = '') {
    if ($table === 'koperasibranch') {
        // Updated query to fetch the staff name as ManagerName
        $query = "SELECT k.BranchID, k.BranchName, k.Address, k.PhoneNumber, k.Status, u.Name AS ManagerName
                  FROM koperasibranch k
                  LEFT JOIN staff s ON k.StaffID = s.StaffID
                  LEFT JOIN user u ON s.UserID = u.UserID";
        if ($search) {
            $query .= " WHERE k.BranchName LIKE :search OR u.Name LIKE :search";
        }
    } elseif ($table === 'printingpackage') {
        // Fetch printing package data with branch name
        $query = "SELECT p.PackageID, p.PackageName, p.Price, p.AvailabilityStatus, p.PrintingColor, b.BranchName
                  FROM printingpackage p
                  JOIN koperasibranch b ON p.BranchID = b.BranchID";
        if ($search) {
            $query .= " WHERE p.PackageName LIKE :search";
        }
    } else {
        // Fetch users data
        $query = "SELECT * FROM user";
        if ($search) {
            $query .= " WHERE Name LIKE :search OR Role LIKE :search";
        }
    }

    $stmt = $conn->prepare($query);
    if ($search) {
        $stmt->bindValue(':search', '%' . $search . '%');
    }
    $stmt->execute();
    return $stmt->fetchAll();
}


// Handle deletion of data
if ($action === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $table = $target === 'koperasibranch' ? 'koperasibranch' : ($target === 'printingpackage' ? 'printingpackage' : 'users');
    $column = $target === 'koperasibranch' ? 'BranchID' : ($target === 'printingpackage' ? 'PackageID' : 'UserID');

    // Check if deleting user and get their role
    if ($target === 'users') {
        // Fetch the user role from the users table
        $stmt = $conn->prepare("SELECT Role FROM user WHERE UserID = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if ($user) {
            $role = $user['Role'];

            // Depending on the role, delete from the corresponding table
            if ($role === 'admin') {
                $delete_membership = $conn->prepare("DELETE FROM membership WHERE UserID = ?");
                $delete_membership->execute([$id]);
                $stmt = $conn->prepare("DELETE FROM admin WHERE UserID = ?");
                $stmt->execute([$id]);
            } elseif ($role === 'staff') {
                $delete_membership = $conn->prepare("DELETE FROM membership WHERE UserID = ?");
                $delete_membership->execute([$id]);
                $stmt = $conn->prepare("DELETE FROM staff WHERE UserID = ?");
                $stmt->execute([$id]);
            } elseif ($role === 'student') {
                $stmt = $conn->prepare("DELETE FROM student WHERE UserID = ?");
                $stmt->execute([$id]);
                $stmt = $conn->prepare("DELETE FROM orders WHERE UserID = ?");
                $stmt->execute([$id]);
                $delete_membership = $conn->prepare("DELETE FROM membership WHERE UserID = ?");
                $delete_membership->execute([$id]);
            }

            // Finally, delete the user from the users table
            $stmt = $conn->prepare("DELETE FROM user WHERE UserID = ?");
            $stmt->execute([$id]);
        }
    } else {
        // For other targets like koperasibranch or printingpackage
        $stmt = $conn->prepare("DELETE FROM $table WHERE $column = ?");
        $stmt->execute([$id]);
    }

    header("Location: admin_dashboard.php?target=$target");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Admin Dashboard</title>

    <style>
        /* General styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            background-color: #66CDAA;
            background-size: cover;
        }
        .chart-container {
            width: 80%;
            margin: 20px auto;
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .chart-title {
            text-align: center;
            font-size: 18px;
            color: #2d3a3f;
            margin-bottom: 15px;
        }

        .sidebar {
            width: 200px;
            background-color: #2d3a3f;
            color: white;
            padding: 20px;
            height: 100vh; /* Full viewport height */
            position: fixed; /* Make the sidebar fixed on the page */
            top: 0; /* Keep it at the top */
            left: 0; /* Keep it on the left side */
            z-index: 1000; /* Ensure the sidebar stays on top of other content */
            overflow-y: auto; /* Allows scrolling if the sidebar content exceeds the height */
        }

        .sidebar h2 {
            color: #66CDAA;
            text-align: center;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 15px 0;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            border-radius: 4px;
        }

        .sidebar ul li a:hover {
            background-color: #66CDAA;
        }

        .main-content {
            margin-left: 230px; /* Add a left margin to the main content to make room for the sidebar */
            padding: 20px;
            flex: 1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden; /* Ensures rounded corners for the table */
        }

        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border: 1px solid #e0e0e0;
        }

        table th {
            background-color: #2d3a3f;
            color: white;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table td {
            background-color: #fafafa;
            color: #333;
        }

        table tr:hover {
            background-color: #f4f4f4; /* Subtle hover effect */
        }

        table td a {
            text-decoration: none;
            color: #2d3a3f;
            font-weight: bold;
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        table td a:hover {
            background-color: #66CDAA;
            color: white;
        }

        table td a.btn-danger {
            background-color: #e55c5c;
        }

        table td a.btn-danger:hover {
            background-color: #cc4f4f;
        }


        a.btn {
            text-decoration: none;
            color: white;
            background-color:rgb(69, 189, 42);
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
            transition: background-color 0.3s ease;
        }

        a.btn-danger {
            background-color: #e55c5c;
        }

        a.btn:hover {
            background-color: #58b94e; /* Darker shade for hover effect */
        }

        a.btn-danger:hover {
            background-color: #cc4f4f;
        }

        .logo {
            width: 50px;
            height: auto;
            margin-bottom: 5px;
        }

        .search-form {
            margin-bottom: 20px;
        }

        .search-form input {
            padding: 8px;
            margin-right: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .search-form button {
            padding: 8px;
            border: none;
            border-radius: 4px;
            background-color: #66CDAA;
            color: white;
            transition: background-color 0.3s ease;
        }

        .search-form button:hover {
            background-color: #58b94e;
        }

        .back-btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #2d3a3f;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 10px;
            transition: background-color 0.3s ease;
        }

        .back-btn:hover {
            background-color: #66CDAA;
        }
        i {
        margin-right: 8px;
    }
    .dashboard-metrics .metric i {
        color: #66CDAA;
        margin-bottom: 10px;
    }
    .chart-container {
        width: 40%;  /* Make container smaller */
        margin: 30px auto;
        background: #f9f9f9;  /* Lighter background for modern feel */
        padding: 20px;
        border-radius: 12px;  /* More rounded corners */
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);  /* Softer, modern shadow */
        max-width: 400px;  /* Limit maximum width for very large screens */
        text-align: center;  /* Center align the content */
    }

    canvas {
        width: 100% !important;  /* Canvas takes full width of container */
        height: 250px !important;  /* Make the chart shorter */
        display: block;  /* Ensures canvas behaves properly */
        margin: 0 auto;  /* Center canvas within container */
    }


    </style>
    
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
    <a href="https://www.umpsa.edu.my" target="_blank">
        <img src="assets/images/umpsa.png" alt="RapidPrint Logo" class="logo">
    </a>
    <h2><i class="fa-solid fa-user-tie"></i>Admin Panel</h2>
    <ul>
        <li><a href="admin_profile.php"><i class="fas fa-user"></i> Profile</a></li>
        <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="admin_dashboard.php?target=users"><i class="fas fa-users"></i> Manage Users</a></li>
        <li><a href="admin_dashboard.php?target=koperasibranch"><i class="fas fa-building"></i> Manage Branch</a></li>
        <li><a href="admin_dashboard.php?target=printingpackage"><i class="fas fa-print"></i> Manage Package</a></li>
        <li><a href="topup_membership.php"><i class="fa fa-address-card"></i> Topup Membership</a></li>
        <li><a href="approval_membership.php"><i class="fas fa-address-card"></i> Approval Card</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>


    <!-- Main Content -->
    <div class="main-content">
        <h1>Admin Dashboard</h1>
        
        <?php if ($target === 'koperasibranch' || $target === 'printingpackage' || $target === 'users'): ?>
            <h2>Manage <?= ucfirst($target); ?></h2>

            <!-- Search Form -->
            <form class="search-form" method="GET">
                <input type="text" name="search" placeholder="Search by <?= $target === 'koperasibranch' ? 'Branch Name' : ($target === 'users' ? 'Name or Role' : 'Package Name'); ?>" value="<?= htmlspecialchars($search); ?>">
                <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                <input type="hidden" name="target" value="<?= $target; ?>">
            </form>

            <!-- Back Button -->
            <a href="admin_dashboard.php?target=<?= $target; ?>" class="back-btn">Back to Full View</a>

            <?php
            // Fetch and display records
            $data = fetchAll($conn, $target, $search);
            ?>

            <table>
                <thead>
                    <?php if ($target === 'koperasibranch'): ?>
                        <a href="add_branch.php?target=<?= $target; ?>" class="btn">Add New Record</a>
                        <tr>
                            <th>ID</th>
                            <th>Branch</th>
                            <th>Manager</th>
                            <th>Address</th>
                            <th>Phone Number</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    <?php elseif ($target === 'printingpackage'): ?>
                        <a href="add_package.php?target=<?= $target; ?>" class="btn">Add New Record</a>
                        <tr>
                            <th>Package ID</th>
                            <th>Branch Name</th>
                            <th>Package Name</th>
                            <th>Price</th>
                            <th>Availability Status</th>
                            <th>Printing Color</th>
                            <th>Actions</th>
                        </tr>
                    <?php else: ?>
                        <a href="register.php?target=users" class="btn">Register New User</a>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    <?php endif; ?>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr>
                            <td colspan="<?= $target === 'koperasibranch' ? 5 : ($target === 'users' ? 5 : 7); ?>">No records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <?php if ($target === 'koperasibranch'): ?>
                                    <td><?= $row['BranchID']; ?></td>
                                    <td><?= $row['BranchName']; ?></td>
                                    <td><?= $row['ManagerName'] ?: 'Unassigned'; ?></td> 
                                    <td><?= $row['Address']; ?></td>
                                    <td><?= $row['PhoneNumber']; ?></td>
                                    <td><?= $row['Status']; ?></td>
                                <?php elseif ($target === 'printingpackage'): ?>
                                    <td><?= $row['PackageID']; ?></td>
                                    <td><?= htmlspecialchars($row['BranchName']); ?></td>
                                    <td><?= $row['PackageName']; ?></td>
                                    <td><?= $row['Price']; ?></td>
                                    <td><?= $row['AvailabilityStatus']; ?></td>
                                    <td><?= $row['PrintingColor']; ?></td>
                                <?php else: ?>
                                    <td><?= $row['UserID']; ?></td>
                                    <td><?= $row['Name']; ?></td>
                                    <td><?= $row['Email']; ?></td>
                                    <td><?= $row['Role']; ?></td>
                                <?php endif; ?>
                                <td>
                                    <?php if ($target === 'koperasibranch'): ?>
                                        <a href="edit_branch.php?id=<?= $row['BranchID']; ?>&target=<?= $target; ?>" class="btn">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="admin_dashboard.php?action=delete&id=<?= $row['BranchID']; ?>&target=<?= $target; ?>" 
                                        class="btn btn-danger" 
                                        onclick="return confirm('Are you sure?');">
                                        <i class="fa-solid fa-trash-can"></i></i>
                                        </a>

                                    <?php elseif ($target === 'printingpackage'): ?>
                                        <a href="edit_package.php?id=<?= $row['PackageID']; ?>&target=<?= $target; ?>" class="btn"><i class="fas fa-edit"></i></a>
                                        <a href="admin_dashboard.php?action=delete&id=<?= $row['PackageID']; ?>&target=<?= $target; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?');"><i class="fa-solid fa-trash-can"></i></a>
                                    <?php else: ?>
                                        <a href="edit_user.php?id=<?= $row['UserID']; ?>&target=<?= $target; ?>" class="btn"><i class="fas fa-edit"></i></a>
                                        <a href="admin_dashboard.php?action=delete&id=<?= $row['UserID']; ?>&target=<?= $target; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?');"><i class="fa-solid fa-trash-can"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php else: ?>
    <h2>Dashboard</h2>
    
    <div class="dashboard-metrics">
        <?php
        // Fetch metrics data
        $totalUsers = $conn->query("SELECT COUNT(*) FROM user")->fetchColumn();
        $totalBranches = $conn->query("SELECT COUNT(*) FROM koperasibranch")->fetchColumn();
        $totalPackages = $conn->query("SELECT COUNT(*) FROM printingpackage")->fetchColumn();
        ?>
        <div class="metric">
        <i class="fas fa-users fa-2x"></i>
        <h3>Total Users</h3>
        <p><?= $totalUsers; ?></p>
    </div>
    <div class="metric">
        <i class="fas fa-building fa-2x"></i>
        <h3>Total Branches</h3>
        <p><?= $totalBranches; ?></p>
    </div>
    <div class="metric">
        <i class="fas fa-print fa-2x"></i>
        <h3>Total Printing Packages</h3>
        <p><?= $totalPackages; ?></p>
    </div>
</div>
<!-- User Roles Pie Chart -->
<div class="chart-container">
        <h2 class="chart-title">User Roles Distribution</h2>
        <canvas id="userRolesChart"></canvas>
</div>

    <!-- Packages per Branch Pie Chart -->
    <div class="chart-container">
        <h2 class="chart-title">Packages per Branch</h2>
        <canvas id="packagesPerBranchChart"></canvas>
    </div>
    <script>
        // Fetch user roles data from PHP
        const userRolesData = <?php echo json_encode($userRoles); ?>;
        const roles = userRolesData.map(role => role.Role);
        const roleCounts = userRolesData.map(role => role.count);

        // Fetch packages by branch data from PHP
        const packagesByBranchData = <?php echo json_encode($packagesByBranch); ?>;
        const branchNames = packagesByBranchData.map(branch => branch.BranchName);
        const packageCounts = packagesByBranchData.map(branch => branch.count);

        // Render the user roles pie chart
        const ctx1 = document.getElementById('userRolesChart').getContext('2d');
        new Chart(ctx1, {
            type: 'pie',
            data: {
                labels: roles,
                datasets: [{
                    data: roleCounts,
                    backgroundColor: ['#66CDAA', '#58b94e', '#2d3a3f'],
                    borderColor: ['#4caf50', '#3e8e41', '#1e282c'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });

        // Render the packages per branch pie chart
        const ctx2 = document.getElementById('packagesPerBranchChart').getContext('2d');
        new Chart(ctx2, {
            type: 'pie',
            data: {
                labels: branchNames,
                datasets: [{
                    data: packageCounts,
                    backgroundColor: ['#2d3a3f', '#66CDAA', '#58b94e'],
                    borderColor: ['#1e282c', '#4caf50', '#3e8e41'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });
    </script>
<div class="chart-container">
            <h2 class="chart-title">Overview Metrics</h2>
            <canvas id="adminChart"></canvas>
        </div>

    <style>
        .dashboard-metrics {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .metric {
            flex: 1;
            text-align: center;
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }

        .metric h3 {
            font-size: 18px;
            color: #2d3a3f;
        }

        .metric p {
            font-size: 24px;
            font-weight: bold;
            color: #66CDAA;
        }
    </style>
<?php endif; ?>

<script>
            // Get data from PHP
            const metrics = {
                users: <?= $totalUsers; ?>,
                branches: <?= $totalBranches; ?>,
                packages: <?= $totalPackages; ?>
            };

            // Render Chart
            const ctx = document.getElementById('adminChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar', // Change to 'pie', 'line', etc., if needed
                data: {
                    labels: ['Users', 'Branches', 'Packages'],
                    datasets: [{
                        label: 'Total Count',
                        data: [metrics.users, metrics.branches, metrics.packages],
                        backgroundColor: [
                            '#66CDAA', '#2d3a3f', '#58b94e'
                        ],
                        borderColor: [
                            '#4caf50', '#1e282c', '#3e8e41'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>

    </div>
</body>
</html>
