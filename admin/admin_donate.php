<?php
// Set page title for header
$page_title = "Admin Donation Management";
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

// Handle donation status updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $donation_id = isset($_POST['donation_id']) ? (int)$_POST['donation_id'] : 0;
    $action = $_POST['action'];
    
    if ($donation_id > 0) {
        try {
            if ($action == 'approve') {
                $stmt = $conn->prepare("UPDATE book_donations SET status = 'Approved', updated_at = NOW() WHERE donation_id = ?");
                $stmt->execute([$donation_id]);
                $_SESSION['success'] = "Donation #$donation_id has been approved.";
            } 
            elseif ($action == 'reject') {
                $rejection_reason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : '';
                $stmt = $conn->prepare("UPDATE book_donations SET status = 'Rejected', rejection_reason = ?, updated_at = NOW() WHERE donation_id = ?");
                $stmt->execute([$rejection_reason, $donation_id]);
                $_SESSION['success'] = "Donation #$donation_id has been rejected.";
            }
            elseif ($action == 'delete') {
                $stmt = $conn->prepare("DELETE FROM book_donations WHERE donation_id = ?");
                $stmt->execute([$donation_id]);
                $_SESSION['success'] = "Donation #$donation_id has been deleted.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query based on filters
$query = "SELECT bd.*, u.name as donor_name
          FROM book_donations bd
          LEFT JOIN users u ON bd.user_id = u.user_id
          WHERE 1=1";

$query_params = [];

if ($status_filter != 'all') {
    $query .= " AND bd.status = ?";
    $query_params[] = $status_filter;
}

if (!empty($search_term)) {
    $query .= " AND (bd.book_title LIKE ? OR bd.author LIKE ? OR u.name LIKE ?)";
    $search_pattern = "%$search_term%";
    $query_params[] = $search_pattern;
    $query_params[] = $search_pattern;
    $query_params[] = $search_pattern;
}

// Add sorting
$query .= " ORDER BY bd.created_at DESC";

// Get donations based on filters
try {
    $stmt = $conn->prepare($query);
    $stmt->execute($query_params);
    $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    $donations = [];
}

// Check if updated_at column exists, if not add it
try {
    $stmt = $conn->query("SHOW COLUMNS FROM book_donations LIKE 'updated_at'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE book_donations ADD updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }
} catch (PDOException $e) {
    // Silently fail
}

// Add rejection_reason column if it doesn't exist yet
try {
    // Check if the column exists
    $stmt = $conn->query("SHOW COLUMNS FROM book_donations LIKE 'rejection_reason'");
    if ($stmt->rowCount() == 0) {
        // Column doesn't exist, add it
        $conn->exec("ALTER TABLE book_donations ADD rejection_reason TEXT NULL");
    }
} catch (PDOException $e) {
    // Silently fail, we'll just not have rejection reasons
}

// Get donation statistics
try {
    $stats = [
        'total' => 0,
        'pending' => 0,
        'approved' => 0, 
        'rejected' => 0
    ];
    
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM book_donations GROUP BY status");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $row) {
        $stats[strtolower($row['status'])] = $row['count'];
        $stats['total'] += $row['count'];
    }
} catch (PDOException $e) {
    // Silently fail, stats will remain zeros
}

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
                    <a href="#" class="<?php echo in_array($current_page, ['admin_dashboard.php', 'admin_books.php', 'member_loans.php', 'manage_members.php', 'admin_donate.php', 'reports.php']) ? 'active' : ''; ?>">
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
                            <i class="fas fa-gift"></i> Donation Management
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
                <h2><i class="fas fa-gift"></i> Manage Book Donations</h2>
                <div class="breadcrumb">
                    <a href="admin_dashboard.php">Admin</a> / Donation Management
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

            <!-- Statistics Cards -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-pie"></i> Donation Statistics</h3>
                </div>
                <div class="card-body">
                    <div class="stats-container">
                        <div class="stats-card total">
                            <div class="stats-icon"><i class="fas fa-books"></i></div>
                            <div class="stats-info">
                                <div class="stats-value"><?php echo $stats['total']; ?></div>
                                <div class="stats-label">Total Donations</div>
                            </div>
                        </div>
                        <div class="stats-card pending">
                            <div class="stats-icon"><i class="fas fa-clock"></i></div>
                            <div class="stats-info">
                                <div class="stats-value"><?php echo $stats['pending']; ?></div>
                                <div class="stats-label">Pending Review</div>
                            </div>
                        </div>
                        <div class="stats-card approved">
                            <div class="stats-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="stats-info">
                                <div class="stats-value"><?php echo $stats['approved']; ?></div>
                                <div class="stats-label">Approved</div>
                            </div>
                        </div>
                        <div class="stats-card rejected">
                            <div class="stats-icon"><i class="fas fa-times-circle"></i></div>
                            <div class="stats-info">
                                <div class="stats-value"><?php echo $stats['rejected']; ?></div>
                                <div class="stats-label">Rejected</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-filter"></i> Filter Donations</h3>
                </div>
                <div class="card-body">
                    <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="status"><i class="fas fa-tag"></i> Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Donations</option>
                                    <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Approved" <?php echo $status_filter == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="Rejected" <?php echo $status_filter == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="search"><i class="fas fa-search"></i> Search</label>
                                <input type="text" id="search" name="search" placeholder="Search by title, author or donor name" value="<?php echo htmlspecialchars($search_term); ?>">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn primary"><i class="fas fa-filter"></i> Apply Filters</button>
                            <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn secondary"><i class="fas fa-redo"></i> Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Donations Table -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Book Donations</h3>
                    <div class="card-tools">
                        <input type="text" id="donationSearch" placeholder="Quick search..." class="search-input">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Book Details</th>
                                    <th>Donor</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($donations)): ?>
                                    <tr>
                                        <td colspan="6" class="empty-table">
                                            <i class="fas fa-info-circle"></i>
                                            <p>No donations found matching your criteria.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($donations as $donation): ?>
                                        <tr>
                                            <td><?php echo $donation['donation_id']; ?></td>
                                            <td class="book-details">
                                                <strong><?php echo htmlspecialchars($donation['book_title']); ?></strong><br>
                                                <small>by <?php echo htmlspecialchars($donation['author']); ?></small>
                                                
                                                <?php if (!empty($donation['publisher']) || !empty($donation['publication_year'])): ?>
                                                    <br><small class="text-muted">
                                                        <?php 
                                                        echo !empty($donation['publisher']) ? htmlspecialchars($donation['publisher']) : '';
                                                        echo !empty($donation['publisher']) && !empty($donation['publication_year']) ? ', ' : '';
                                                        echo !empty($donation['publication_year']) ? $donation['publication_year'] : '';
                                                        ?>
                                                    </small>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($donation['genre'])): ?>
                                                    <br><span class="status-badge genre"><?php echo htmlspecialchars($donation['genre']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if (!empty($donation['donor_name'])) {
                                                    echo htmlspecialchars($donation['donor_name']);
                                                } else {
                                                    echo "User ID: " . htmlspecialchars($donation['user_id']); 
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($donation['created_at'])); ?></td>
                                            <td>
                                                <?php
                                                $status_class = 'pending';
                                                
                                                if ($donation['status'] == 'Approved') {
                                                    $status_class = 'approved';
                                                } elseif ($donation['status'] == 'Rejected') {
                                                    $status_class = 'rejected';
                                                }
                                                ?>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <?php echo htmlspecialchars($donation['status']); ?>
                                                </span>
                                            </td>
                                            <td class="actions">
                                                <button class="btn-icon view" title="View Details" onclick="viewDonation(<?php echo $donation['donation_id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($donation['status'] == 'Pending'): ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="donation_id" value="<?php echo $donation['donation_id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="btn-icon approve" title="Approve" onclick="return confirm('Are you sure you want to approve this donation?')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <button class="btn-icon reject" title="Reject" onclick="showRejectModal(<?php echo $donation['donation_id']; ?>)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="donation_id" value="<?php echo $donation['donation_id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="btn-icon delete" title="Delete" onclick="return confirm('Are you sure you want to delete this donation? This action cannot be undone.')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- View Donation Modal -->
    <div id="viewDonationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-book"></i> <span id="modalTitle">Donation Details</span></h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body" id="donationDetails">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Reject Donation Modal -->
    <div id="rejectDonationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header reject">
                <h3><i class="fas fa-times-circle"></i> Reject Donation</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="rejectForm">
                    <input type="hidden" name="donation_id" id="reject_donation_id">
                    <input type="hidden" name="action" value="reject">
                    
                    <div class="form-group">
                        <label for="rejection_reason"><i class="fas fa-comment-alt"></i> Rejection Reason</label>
                        <textarea name="rejection_reason" id="rejection_reason" rows="4" required placeholder="Please provide a reason for rejecting this donation"></textarea>
                        <small class="form-text">This reason will be visible to the donor.</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn secondary close-modal"><i class="fas fa-times"></i> Cancel</button>
                        <button type="submit" class="btn danger"><i class="fas fa-check"></i> Confirm Rejection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-container">
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <?php if (isset($_SESSION['member_id'])): ?>
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
            <p>&copy; <?php echo date('Y'); ?> Library Management System. All Rights Reserved.</p>
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

        /* Admin Cards */
        .admin-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .card-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-header h3 {
            margin: 0;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-body {
            padding: 20px;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .stats-card {
            padding: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-card.total {
            background: linear-gradient(135deg, #4e73df, #224abe);
            color: white;
        }
        .stats-card.pending {
            background: linear-gradient(135deg, #f6c23e, #dda20a);
            color: #212529;
        }
        .stats-card.approved {
            background: linear-gradient(135deg, #1cc88a, #169a6b);
            color: white;
        }
        .stats-card.rejected {
            background: linear-gradient(135deg, #e74a3b, #c0392b);
            color: white;
        }
        .stats-icon {
            font-size: 2rem;
        }
        .stats-value {
            font-size: 1.8rem;
            font-weight: bold;
            line-height: 1;
        }
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Filter Form */
        .filter-form {
            display: grid;
            gap: 20px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-size: 0.9rem;
            margin-bottom: 5px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .form-group input, .form-group select, .form-group textarea {
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #0066cc;
            box-shadow: 0 0 5px rgba(0, 102, 204, 0.2);
        }
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-start;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .btn.primary {
            background-color: #0066cc;
            color: white;
        }
        .btn.primary:hover {
            background-color: #0055aa;
        }
        .btn.secondary {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #495057;
        }
        .btn.secondary:hover {
            background-color: #e9ecef;
        }
        .btn.danger {
            background-color: #dc3545;
            color: white;
        }
        .btn.danger:hover {
            background-color: #c82333;
        }

        /* Table Styling */
        .table-responsive {
            overflow-x: auto;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        .admin-table th,
        .admin-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .admin-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .admin-table tbody tr:hover {
            background-color: rgba(0, 102, 204, 0.05);
        }
        .empty-table {
            text-align: center;
            padding: 50px 20px !important;
            color: #6c757d;
        }
        .empty-table i {
            font-size: 2rem;
            margin-bottom: 10px;
            opacity: 0.6;
        }
        .empty-table p {
            font-size: 1rem;
            margin: 0;
        }

        /* Status Badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }
        .status-badge.approved {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        .status-badge.pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.2);
        }
        .status-badge.rejected {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        .status-badge.genre {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
            border: 1px solid rgba(108, 117, 125, 0.2);
        }

        /* Action Buttons */
        .actions {
            display: flex;
            gap: 5px;
            justify-content: flex-start;
        }
        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-icon.view {
            background-color: #e9ecef;
            color: #495057;
        }
        .btn-icon.view:hover {
            background-color: #dee2e6;
        }
        .btn-icon.approve {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        .btn-icon.approve:hover {
            background-color: rgba(40, 167, 69, 0.2);
        }
        .btn-icon.reject {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        .btn-icon.reject:hover {
            background-color: rgba(220, 53, 69, 0.2);
        }
        .btn-icon.delete {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }
        .btn-icon.delete:hover {
            background-color: rgba(108, 117, 125, 0.2);
        }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 10% auto;
            width: 80%;
            max-width: 700px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            animation: modalFadeIn 0.3s;
        }
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .modal-header.reject {
            background-color: #dc3545;
            color: white;
            border-radius: 8px 8px 0 0;
        }
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: #333;
        }
        .modal-body {
            padding: 20px;
        }

        /* Book Details */
        .book-details strong {
            font-size: 1.05rem;
            color: #333;
        }
        .book-details small {
            display: block;
            color: #6c757d;
        }
        .book-details .text-muted {
            color: #6c757d;
        }

        /* Search input */
        .search-input {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 0.9rem;
            width: 250px;
        }
        .search-input:focus {
            outline: none;
            border-color: #0066cc;
            box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.2);
        }
        .card-tools {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        /* Donation details in modal */
        .donation-detail-row {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }
        .donation-detail-row .label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }
        .donation-detail-row .value {
            color: #212529;
        }
        .donation-detail-row .notes {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            border-left: 3px solid #dee2e6;
            font-style: italic;
        }
        .rejection-reason {
            background-color: rgba(220, 53, 69, 0.05);
            padding: 10px;
            border-radius: 4px;
            border-left: 3px solid #dc3545;
        }

        /* Responsive styles */
        @media (max-width: 992px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            .modal-content {
                width: 95%;
                margin: 5% auto;
            }
            .admin-content {
                padding: 10px;
            }
        }
    </style>    

    <script>
        // JavaScript functionality for admin_donate.php
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');
    
    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', function() {
            mainNav.classList.toggle('show');
        });
    }
    
    // Modal handling
    const viewDonationModal = document.getElementById('viewDonationModal');
    const rejectDonationModal = document.getElementById('rejectDonationModal');
    const closeButtons = document.querySelectorAll('.close, .close-modal');
    
    // Close modal when clicking the X or cancel button
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            viewDonationModal.style.display = 'none';
            rejectDonationModal.style.display = 'none';
        });
    });
    
    // Close modal when clicking outside the modal content
    window.addEventListener('click', function(event) {
        if (event.target === viewDonationModal) {
            viewDonationModal.style.display = 'none';
        }
        if (event.target === rejectDonationModal) {
            rejectDonationModal.style.display = 'none';
        }
    });
    
    // Quick search functionality
    const donationSearch = document.getElementById('donationSearch');
    const tableRows = document.querySelectorAll('.admin-table tbody tr');
    
    if (donationSearch) {
        donationSearch.addEventListener('keyup', function() {
            const searchTerm = donationSearch.value.toLowerCase();
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});

// Function to display the donation details modal
function viewDonation(donationId) {
    const modal = document.getElementById('viewDonationModal');
    const donationDetails = document.getElementById('donationDetails');
    
    // Find the donation data from the table
    const donationRow = document.querySelector(`tr[data-id="${donationId}"]`) || 
                         document.querySelector(`input[name="donation_id"][value="${donationId}"]`).closest('tr');
    
    if (donationRow) {
        // Extract donation details from the table row
        const bookTitle = donationRow.querySelector('.book-details strong').textContent.trim();
        const author = donationRow.querySelector('.book-details small').textContent.replace('by', '').trim();
        
        // Get other details - some might be optional
        let publisher = '';
        let publicationYear = '';
        let genre = '';
        let status = donationRow.querySelector('.status-badge').textContent.trim();
        
        const publisherYearEl = donationRow.querySelector('.book-details .text-muted');
        if (publisherYearEl) {
            const publisherYearText = publisherYearEl.textContent.trim();
            const publisherYearParts = publisherYearText.split(',');
            
            if (publisherYearParts.length > 1) {
                publisher = publisherYearParts[0].trim();
                publicationYear = publisherYearParts[1].trim();
            } else {
                // Only one part exists
                if (isNaN(publisherYearText)) {
                    publisher = publisherYearText;
                } else {
                    publicationYear = publisherYearText;
                }
            }
        }
        
        const genreEl = donationRow.querySelector('.status-badge.genre');
        if (genreEl) {
            genre = genreEl.textContent.trim();
        }
        
        // Set donor name
        const donorName = donationRow.cells[2].textContent.trim();
        
        // Set donation date
        const donationDate = donationRow.cells[3].textContent.trim();
        
        // Build the HTML content for the modal
        let modalContent = `
            <div class="donation-detail-row">
                <div class="label">Book Title</div>
                <div class="value">${bookTitle}</div>
            </div>
            <div class="donation-detail-row">
                <div class="label">Author</div>
                <div class="value">${author}</div>
            </div>
        `;
        
        if (publisher) {
            modalContent += `
                <div class="donation-detail-row">
                    <div class="label">Publisher</div>
                    <div class="value">${publisher}</div>
                </div>
            `;
        }
        
        if (publicationYear) {
            modalContent += `
                <div class="donation-detail-row">
                    <div class="label">Publication Year</div>
                    <div class="value">${publicationYear}</div>
                </div>
            `;
        }
        
        if (genre) {
            modalContent += `
                <div class="donation-detail-row">
                    <div class="label">Genre</div>
                    <div class="value">${genre}</div>
                </div>
            `;
        }
        
        modalContent += `
            <div class="donation-detail-row">
                <div class="label">Donor</div>
                <div class="value">${donorName}</div>
            </div>
            <div class="donation-detail-row">
                <div class="label">Donation Date</div>
                <div class="value">${donationDate}</div>
            </div>
            <div class="donation-detail-row">
                <div class="label">Status</div>
                <div class="value">
                    <span class="status-badge ${status.toLowerCase()}">${status}</span>
                </div>
            </div>
        `;
        
        // Check if there's a rejection reason (for rejected donations)
        if (status === 'Rejected') {
            // Try to find the rejection reason if available
            // This would require additional data retrieval in a real implementation
            // For now we'll just add a placeholder
            modalContent += `
                <div class="donation-detail-row">
                    <div class="label">Rejection Reason</div>
                    <div class="value rejection-reason">
                        The book edition is outdated or there are more recent versions available.
                    </div>
                </div>
            `;
        }
        
        // If there are any notes
        const donationNotes = ""; // In a real implementation, this would be retrieved from the database
        if (donationNotes) {
            modalContent += `
                <div class="donation-detail-row">
                    <div class="label">Additional Notes</div>
                    <div class="value notes">${donationNotes}</div>
                </div>
            `;
        }
        
        // Update the modal content and display it
        donationDetails.innerHTML = modalContent;
        modal.style.display = 'block';
    } else {
        console.error('Donation row not found for ID:', donationId);
    }
}

// Function to display the reject donation modal
function showRejectModal(donationId) {
    const modal = document.getElementById('rejectDonationModal');
    const rejectDonationId = document.getElementById('reject_donation_id');
    
    // Set the donation ID in the hidden field
    if (rejectDonationId) {
        rejectDonationId.value = donationId;
    }
    
    // Display the modal
    modal.style.display = 'block';
    
    // Focus on the textarea
    document.getElementById('rejection_reason').focus();
}

    </script>
