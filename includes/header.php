<!-- header.php -->
<?php
// Include common functions
require_once 'functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library System - <?php echo ucfirst(str_replace('.php', '', $current_page)); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <link rel="stylesheet" type="text/css" href="assets/css/header.css">
</head>

<body>
    <header>
        <div class="header-top">
            <a href="dashboard.php" class="logo">
                <i class="fas fa-book-reader"></i>
                <h1>Library Management System</h1>
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-controls">
                    <!-- Cart Icon -->
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if (isset($_SESSION['cart_count']) && $_SESSION['cart_count'] > 0): ?>
                            <span class="cart-count"><?php echo $_SESSION['cart_count']; ?></span>
                        <?php endif; ?>
                    </a>

                    <a href="profile.php" class="user-info" style="text-decoration: none; color: inherit;">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <span class="user-name"><?php echo $_SESSION['name']; ?></span>
                    </a>

                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="auth-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </a>
                    <a href="register.php" class="auth-btn">
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
            <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i> Home
            </a>

            <a href="books.php" class="<?php echo $current_page == 'books.php' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i> Browse Books
            </a>

            <a href="donate_book.php" class="<?php echo $current_page == 'donate_book.php' ? 'active' : ''; ?>">
                <i class="fas fa-gift"></i> Donate
            </a>

            <?php if (is_admin()): ?>
                <div class="dropdown">
                    <a href="#" class="<?php echo in_array($current_page, ['admin_dashboard.php', 'admin_books.php', 'member_loans.php', 'manage_members.php', 'reports.php']) ? 'active' : ''; ?>">
                        <i class="fas fa-user-shield"></i> Admin
                    </a>
                    <div class="dropdown-content">
                        <a href="admin/admin_dashboard.php" class="<?php echo $current_page == 'admin_dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a href="admin/admin_books.php" class="<?php echo $current_page == 'admin_books.php' ? 'active' : ''; ?>">
                            <i class="fas fa-book-open"></i> Books Management
                        </a>
                        <a href="admin/member_loans.php" class="<?php echo $current_page == 'member_loans.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i> Member Loans
                        </a>
                        <a href="admin/manage_members.php" class="<?php echo $current_page == 'manage_members.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-cog"></i> Members Management
                        </a>
                        <a href="admin/admin_donate.php" class="<?php echo $current_page == 'admin_donate.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i> Donate Books
                        </a>
                        <a href="admin/reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <!-- CSS for the cart icon and dropdown -->
        <style>
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

            /* Enhanced dropdown styles */
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

            /* Active state for dropdown items */
            .dropdown-content a.active {
                background-color: #e7f3ff;
                color: #0066cc;
                font-weight: bold;
            }
        </style>