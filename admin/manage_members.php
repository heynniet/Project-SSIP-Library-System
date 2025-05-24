<?php
// Include common functions
require_once '../includes/functions.php';
require_once '../config/config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin, redirect if not
if (!is_admin()) {
    $_SESSION['error'] = "You don't have permission to access the admin area.";
    header("Location: ../login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_member'])) {
        // Add new member
        $name = sanitize_input($_POST['name']);
        $email = sanitize_input($_POST['email']);
        $password = sanitize_input($_POST['password']);
        
        // Validate inputs
        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['error'] = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format.";
        } else {
            try {
                // Check if email already exists
                $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
                $stmt->execute([':email' => $email]);
                if ($stmt->fetchColumn() > 0) {
                    $_SESSION['error'] = "Email already exists.";
                } else {
                    // Insert new member with join_date
                    $sql = "INSERT INTO users (name, email, password, join_date) VALUES (:name, :email, :password, :join_date)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':password' => $password, // Store password as plain text
                        ':join_date' => date('Y-m-d') // Set join_date to current date
                    ]);
                    $_SESSION['success'] = "Member added successfully.";
                    header("Location: manage_members.php");
                    exit();
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error adding member: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['edit_member'])) {
        // Edit existing member
        $user_id = sanitize_input($_POST['user_id']);
        $name = sanitize_input($_POST['name']);
        $email = sanitize_input($_POST['email']);
        
        // Validate inputs
        if (empty($name) || empty($email)) {
            $_SESSION['error'] = "Name and email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format.";
        } else {
            try {
                // Check if email is taken by another user
                $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND user_id != :user_id");
                $stmt->execute([':email' => $email, ':user_id' => $user_id]);
                if ($stmt->fetchColumn() > 0) {
                    $_SESSION['error'] = "Email already in use by another member.";
                } else {
                    $sql = "UPDATE users SET name = :name, email = :email WHERE user_id = :user_id";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':user_id' => $user_id
                    ]);
                    $_SESSION['success'] = "Member updated successfully.";
                    header("Location: manage_members.php");
                    exit();
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error updating member: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete_member'])) {
        // Delete member
        $user_id = sanitize_input($_POST['user_id']);
        try {
            $sql = "DELETE FROM users WHERE user_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':user_id' => $user_id]);
            $_SESSION['success'] = "Member deleted successfully.";
            header("Location: manage_members.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting member: " . $e->getMessage();
        }
    }
}

// Fetch all members
$members = $conn->query("SELECT user_id, name, email, join_date FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library System - Admin - <?php echo ucfirst(str_replace('.php', '', $current_page)); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/header.css">
</head>
<body>
    <header>
        <div class="header-top">
            <a href="../dashboard.php" class="logo">
                <i class="fas fa-book-reader"></i>
                <h1>Library Management System</h1>
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-controls">
                    <a href="../cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if (isset($_SESSION['cart_count']) && $_SESSION['cart_count'] > 0): ?>
                            <span class="cart-count"><?php echo $_SESSION['cart_count']; ?></span>
                        <?php endif; ?>
                    </a>

                    <a href="../profile.php" class="user-info" style="text-decoration: none; color: inherit;">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <span class="user-name"><?php echo $_SESSION['name']; ?></span>
                    </a>

                    <a href="../logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="../login.php" class="auth-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </a>
                    <a href="../register.php" class="auth-btn">
                        <i class="fas fa-user-plus"></i>
                        Register
                    </a>
                </div>
            <?php endif; ?>

            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <nav id="mainNav">
            <a href="../dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i> Home
            </a>
            <a href="../books.php" class="<?php echo $current_page == 'books.php' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i> Browse Books
            </a>
            <a href="../donate_book.php" class="<?php echo $current_page == 'donate_book.php' ? 'active' : ''; ?>">
                <i class="fas fa-gift"></i> Donate
            </a>
            <?php if (is_admin()): ?>
                <div class="dropdown">
                    <a href="#" class="<?php echo in_array($current_page, ['admin_dashboard.php', 'admin_books.php', 'member_loans.php', 'manage_members.php', 'reports.php']) ? 'active' : ''; ?>">
                        <i class="fas fa-user-shield"></i> Admin
                    </a>
                    <div class="dropdown-content">
                        <a href="admin_dashboard.php" class="<?php echo $current_page == 'admin_dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a href="admin_books.php" class="<?php echo $current_page == 'admin_books.php' ? 'active' : ''; ?>">
                            <i class="fas fa-book-open"></i> Books Management
                        </a>
                        <a href="member_loans.php" class="<?php echo $current_page == 'member_loans.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i> Member Loans
                        </a>
                        <a href="manage_members.php" class="<?php echo $current_page == 'manage_members.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-cog"></i> Members Management
                        </a>
                         <a href="admin_donate.php" class="<?php echo $current_page == 'admin_donate.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i> Donate Books
                        </a>
                        <a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </nav>
    </header>

    <main class="admin-container">
        <div class="admin-content">
            <div class="admin-header">
                <h2><i class="fas fa-user-cog"></i> Members Management</h2>
                <div class="breadcrumb">
                    <a href="admin_dashboard.php">Admin</a> / Members Management
                </div>
            </div>

            <?php if(isset($_SESSION['success'])): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>

            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-user-plus"></i> Add New Member</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="add_member" value="1">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn primary"><i class="fas fa-save"></i> Add Member</button>
                    </form>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Members List</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Join Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td><?php echo $member['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($member['name']); ?></td>
                                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                                        <td><?php echo $member['join_date']; ?></td>
                                        <td>
                                            <button class="btn primary edit-btn" data-id="<?php echo $member['user_id']; ?>" data-name="<?php echo htmlspecialchars($member['name']); ?>" data-email="<?php echo htmlspecialchars($member['email']); ?>"><i class="fas fa-edit"></i> Edit</button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this member?');">
                                                <input type="hidden" name="user_id" value="<?php echo $member['user_id']; ?>">
                                                <input type="hidden" name="delete_member" value="1">
                                                <button type="submit" class="btn danger"><i class="fas fa-trash"></i> Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-container">
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="../dashboard.php"><i class="fas fa-chevron-right"></i> Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="../login.php"><i class="fas fa-chevron-right"></i> Login</a></li>
                        <li><a href="../register.php"><i class="fas fa-chevron-right"></i> Register</a></li>
                        <li><a href="../donate_book.php"><i class="fas fa-chevron-right"></i> Donate Books</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="footer-column">
                <h3>Library Hours</h3>
                <ul class="footer-links">
                    <li><i class="fas fa-clock"></i> Monday - Friday: 8:00 AM - 8:00 PM</li>
                    <li><i class="fas fa-clock"></i> Saturday: 10:00 AM - 6:00 PM</li>
                    <li><i class="fas fa-clock"></i> Sunday: 12:00 PM - 5:00 PM</li>
                    <li><i class="fas fa-info-circle"></i> Closed on Public Holidays</li>
                </ul>
            </div>

            <div class="footer-column">
                <h3>Contact Us</h3>
                <ul class="footer-contact">
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <div>123 Library Street<br>Book City, BC 12345</div>
                    </li>
                    <li>
                        <i class="fas fa-phone"></i>
                        <div>(123) 456-7890</div>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <div>contact@librarysystem.com</div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© <?php echo date('Y'); ?> Library Management System. All Rights Reserved.</p>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
            </div>
        </div>
    </footer>

    <style>
        /* Admin Container */
        .admin-container {
            padding: 20px;
            min-height: calc(100vh - 200px);
        }
        .admin-content {
            background: #fff;
            padding: 20px;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .admin-header h2 {
            font-size: 1.8rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .breadcrumb a {
            color: #0066cc;
            text-decoration: none;
        }
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        .admin-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .card-header h3 {
            margin: 0;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-body {
            padding: 20px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-control:focus {
            outline: none;
            border-color: #0066cc;
            box-shadow: 0 0 5px rgba(0, 102, 204, 0.3);
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .admin-table th, .admin-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .admin-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        .admin-table tr:hover {
            background: #f1f1f1;
        }

        /* Button Styles */
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .btn.primary {
            background: #0066cc;
            color: white;
        }
        .btn.primary:hover {
            background: #005bb5;
        }
        .btn.danger {
            background: #e74c3c;
            color: white;
        }
        .btn.danger:hover {
            background: #c0392b;
        }

        /* Alert Styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert.success {
            background: #d4edda;
            color: #155724;
        }
        .alert.error {
            background: #f8d7da;
            color: #721c24;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-container {
                padding: 10px;
            }
            .admin-content {
                padding: 10px;
            }
            .admin-table th, .admin-table td {
                font-size: 0.9rem;
                padding: 8px;
            }
        }

        /* Cart Icon and Dropdown */
        .cart-icon {
            position: relative;
            display: inline-flex;
            margin-right: 15px;
            font-size: 1.2rem;
            color: #333;
            text-decoration: none;
        }
        .cart-icon:hover {
            color: #0066cc;
        }
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #e74c3c;
            color: white;
            font-size: 0.7rem;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 220px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .dropdown-content a:last-child {
            border-bottom: none;
        }
        .dropdown-content a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
        .dropdown-content a.active {
            background-color: #e7f3ff;
            color: #0066cc;
            font-weight: bold;
        }
    </style>

    <script>
        // Edit member modal functionality
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const email = this.getAttribute('data-email');

                const form = document.createElement('div');
                form.className = 'modal';
                form.innerHTML = `
                    <div class="modal-content">
                        <h3><i class="fas fa-edit"></i> Edit Member</h3>
                        <form method="POST">
                            <input type="hidden" name="edit_member" value="1">
                            <input type="hidden" name="user_id" value="${id}">
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" id="name" name="name" class="form-control" value="${name}" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" value="${email}" required>
                            </div>
                            <button type="submit" class="btn primary"><i class="fas fa-save"></i> Save Changes</button>
                            <button type="button" class="btn danger" onclick="this.closest('.modal').remove()"><i class="fas fa-times"></i> Cancel</button>
                        </form>
                    </div>
                `;
                document.body.appendChild(form);
            });
        });
    </script>

    <style>
        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        .modal-content h3 {
            margin: 0 0 15px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</body>
</html>
