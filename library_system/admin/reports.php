<?php
// Set page title for header
$page_title = "Admin Reports";
$admin_page = true; // Flag for admin-specific styling/features

// Include configuration and functions
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is admin, redirect if not
if (!is_admin()) {
    $_SESSION['error'] = "You don't have permission to access the admin area.";
    header("Location: ../login.php");
    exit();
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Handle filter
$filter = isset($_GET['filter']) ? sanitize_input($_GET['filter']) : 'all';
$where_clause = '';
$params = [];

if ($filter == 'active') {
    $where_clause = 'WHERE l.return_date IS NULL AND l.due_date >= CURDATE()';
} elseif ($filter == 'overdue') {
    $where_clause = 'WHERE l.return_date IS NULL AND l.due_date < CURDATE()';
} elseif ($filter == 'returned') {
    $where_clause = 'WHERE l.return_date IS NOT NULL';
}

// Get loan statistics
$total_loans = $conn->query("SELECT COUNT(*) FROM loans")->fetchColumn();
$active_loans = $conn->query("SELECT COUNT(*) FROM loans WHERE return_date IS NULL AND due_date >= CURDATE()")->fetchColumn();
$overdue_loans = $conn->query("SELECT COUNT(*) FROM loans WHERE return_date IS NULL AND due_date < CURDATE()")->fetchColumn();
$most_borrowed = $conn->query("
    SELECT b.title, COUNT(l.loan_id) as borrow_count
    FROM loans l
    JOIN books b ON l.book_id = b.book_id
    GROUP BY l.book_id
    ORDER BY borrow_count DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

// Get loans for display
$loans = $conn->query("
    SELECT l.loan_id, l.user_id, l.book_id, l.loan_date, l.due_date, l.return_date,
           u.name AS user_name, b.title AS book_title
    FROM loans l
    JOIN users u ON l.user_id = u.user_id
    JOIN books b ON l.book_id = b.book_id
    $where_clause
    ORDER BY l.loan_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

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
                <h2><i class="fas fa-chart-bar"></i> Reports</h2>
                <div class="breadcrumb">
                    <a href="admin_dashboard.php">Admin</a> / Reports
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
                    <h3><i class="fas fa-chart-line"></i> Loan Statistics</h3>
                </div>
                <div class="card-body">
                    <div class="stats-container">
                        <div class="stat-box">
                            <i class="fas fa-book"></i>
                            <h4>Total Loans</h4>
                            <p><?php echo $total_loans; ?></p>
                        </div>
                        <div class="stat-box">
                            <i class="fas fa-book-open"></i>
                            <h4>Active Loans</h4>
                            <p><?php echo $active_loans; ?></p>
                        </div>
                        <div class="stat-box">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h4>Overdue Loans</h4>
                            <p><?php echo $overdue_loans; ?></p>
                        </div>
                        <div class="stat-box">
                            <i class="fas fa-star"></i>
                            <h4>Most Borrowed Book</h4>
                            <p><?php echo $most_borrowed ? htmlspecialchars($most_borrowed['title']) : 'N/A'; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Loan Reports</h3>
                    <div class="card-tools">
                        <form method="GET" class="filter-form">
                            <select name="filter" onchange="this.form.submit()">
                                <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Loans</option>
                                <option value="active" <?php echo $filter == 'active' ? 'selected' : ''; ?>>Active Loans</option>
                                <option value="overdue" <?php echo $filter == 'overdue' ? 'selected' : ''; ?>>Overdue Loans</option>
                                <option value="returned" <?php echo $filter == 'returned' ? 'selected' : ''; ?>>Returned Loans</option>
                            </select>
                            <button type="button" id="exportBtn" class="btn primary"><i class="fas fa-download"></i> Export to CSV</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Member</th>
                                    <th>Book</th>
                                    <th>Loan Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($loans as $loan): ?>
                                    <tr>
                                        <td><?php echo $loan['loan_id']; ?></td>
                                        <td><?php echo htmlspecialchars($loan['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($loan['book_title']); ?></td>
                                        <td><?php echo $loan['loan_date']; ?></td>
                                        <td><?php echo $loan['due_date']; ?></td>
                                        <td>
                                            <?php
                                            if ($loan['return_date']) {
                                                echo '<span class="status returned">Returned</span>';
                                            } elseif (strtotime($loan['due_date']) < time()) {
                                                echo '<span class="status overdue">Overdue</span>';
                                            } else {
                                                echo '<span class="status active">Active</span>';
                                            }
                                            ?>
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
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 200px);
            padding: 20px;
        }
        .admin-content {
            flex: 1;
            padding: 20px;
            background: #fff;
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

        /* Stats Container */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .stat-box i {
            font-size: 2rem;
            color: #0066cc;
            margin-bottom: 10px;
        }
        .stat-box h4 {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 10px;
        }
        .stat-box p {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0066cc;
        }

        /* Filter Form */
        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .filter-form select {
            padding: 8px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s;
        }
        .btn.primary {
            background: #0066cc;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }

        /* Loan Reports Table */
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
        .status {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.9rem;
            display: inline-block;
        }
        .status.active {
            background: #d4edda;
            color: #155724;
        }
        .status.overdue {
            background: #f8d7da;
            color: #721c24;
        }
        .status.returned {
            background: #e2e3e5;
            color: #383d41;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-container {
                padding: 10px;
            }
            .admin-content {
                padding: 10px;
            }
            .stats-container {
                grid-template-columns: 1fr;
            }
            .filter-form {
                flex-direction: column;
                align-items: flex-start;
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
        // Export to CSV functionality
        document.getElementById('exportBtn').addEventListener('click', function() {
            const table = document.querySelector('.admin-table');
            const rows = table.querySelectorAll('tr');
            let csv = [];
            
            // Add header
            const headers = Array.from(rows[0].querySelectorAll('th')).map(th => th.textContent);
            csv.push(headers.join(','));

            // Add rows
            for (let i = 1; i < rows.length; i++) {
                const cols = Array.from(rows[i].querySelectorAll('td')).map(td => {
                    let text = td.textContent.trim();
                    // Handle commas in text
                    if (text.includes(',')) {
                        text = `"${text}"`;
                    }
                    return text;
                });
                csv.push(cols.join(','));
            }

            // Create CSV file
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'loan_report_<?php echo date('Y-m-d'); ?>.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        });
    </script>
</body>
</html>
