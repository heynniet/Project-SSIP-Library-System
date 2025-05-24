<?php
// Set page title for header
$page_title = "Admin Books Management";
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

// Process form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add'])) {
        $title = sanitize_input($_POST['title']);
        $author = sanitize_input($_POST['author']);
        $isbn = sanitize_input($_POST['isbn']);
        $published_year = sanitize_input($_POST['published_year']);

        $isbn = str_replace(['-', ' '], '', $isbn);
        if (strlen($isbn) > 13 || !preg_match('/^[0-9]{10,13}$/', $isbn)) {
            $_SESSION['error'] = 'Invalid ISBN. It must be 10 or 13 digits.';
        } else {
            $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, published_year) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $author, $isbn, $published_year]);
            $_SESSION['success'] = "Book added successfully!";
        }
    } elseif (isset($_POST['update'])) {
        $book_id = $_POST['book_id'];
        $title = sanitize_input($_POST['title']);
        $author = sanitize_input($_POST['author']);
        $isbn = sanitize_input($_POST['isbn']);
        $published_year = sanitize_input($_POST['published_year']);

        $isbn = str_replace(['-', ' '], '', $isbn);
        if (strlen($isbn) > 13 || !preg_match('/^[0-9]{10,13}$/', $isbn)) {
            $_SESSION['error'] = 'Invalid ISBN. It must be 10 or 13 digits.';
        } else {
            $stmt = $conn->prepare("UPDATE books SET title=?, author=?, isbn=?, published_year=? WHERE book_id=?");
            $stmt->execute([$title, $author, $isbn, $published_year, $book_id]);
            $_SESSION['success'] = "Book updated successfully!";
        }
    } elseif (isset($_POST['delete'])) {
        $book_id = $_POST['book_id'];
        $stmt = $conn->prepare("DELETE FROM books WHERE book_id=?");
        $stmt->execute([$book_id]);
        $_SESSION['success'] = "Book deleted successfully!";
    }
}

// Get all books for display
$books = $conn->query("SELECT * FROM books ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

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
                <h2><i class="fas fa-book-open"></i> Manage Books</h2>
                <div class="breadcrumb">
                    <a href="admin_dashboard.php">Admin</a> / Books Management
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
                    <h3><i class="fas fa-plus"></i> Add/Edit Book</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="book_id" id="book_id">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="title"><i class="fas fa-book"></i> Title</label>
                                <input type="text" name="title" id="title" required placeholder="Enter book title">
                            </div>
                            <div class="form-group">
                                <label for="author"><i class="fas fa-user-edit"></i> Author</label>
                                <input type="text" name="author" id="author" required placeholder="Enter author name">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="isbn"><i class="fas fa-barcode"></i> ISBN</label>
                                <input type="text" name="isbn" id="isbn" required pattern="[0-9\-]{10,17}" title="Enter a 10 or 13-digit ISBN (with or without hyphens)" placeholder="Enter ISBN">
                            </div>
                            <div class="form-group">
                                <label for="published_year"><i class="fas fa-calendar-alt"></i> Published Year</label>
                                <input type="number" name="published_year" id="published_year" required min="1900" max="<?= date('Y') ?>" placeholder="Enter year">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="add" id="addBtn" class="btn primary"><i class="fas fa-plus"></i> Add Book</button>
                            <button type="submit" name="update" id="updateBtn" class="btn success" style="display:none;"><i class="fas fa-edit"></i> Update Book</button>
                            <button type="button" id="cancelBtn" class="btn secondary" style="display:none;"><i class="fas fa-times"></i> Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Book Inventory</h3>
                    <div class="card-tools">
                        <input type="text" id="bookSearch" placeholder="Search books..." class="search-input">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>ISBN</th>
                                    <th>Year</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($books as $book): ?>
                                    <tr>
                                        <td><?php echo $book['book_id']; ?></td>
                                        <td><?php echo $book['title']; ?></td>
                                        <td><?php echo $book['author']; ?></td>
                                        <td><?php echo $book['isbn']; ?></td>
                                        <td><?php echo $book['published_year']; ?></td>
                                        <td class="actions">
                                            <button onclick="editBook(<?php echo $book['book_id']; ?>, '<?php echo addslashes($book['title']); ?>', '<?php echo addslashes($book['author']); ?>', '<?php echo $book['isbn']; ?>', '<?php echo $book['published_year']; ?>')" class="btn-icon edit" title="Edit"><i class="fas fa-edit"></i></button>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                                <button type="submit" name="delete" class="btn-icon delete" title="Delete" onclick="return confirm('Are you sure you want to delete this book?')"><i class="fas fa-trash"></i></button>
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

        /* Admin Form (Manage Books) */
        .admin-form {
            display: grid;
            gap: 20px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
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
        .form-group input {
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #0066cc;
            box-shadow: 0 0 5px rgba(0, 102, 204, 0.2);
        }
        .form-group input:invalid:focus {
            border-color: #e74c3c;
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
        .btn.success {
            background: #28a745;
            color: white;
        }
        .btn.secondary {
            background: #6c757d;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }

        /* Book Inventory Table */
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
        .admin-table .actions {
            display: flex;
            gap: 10px;
        }
        .btn-icon {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            padding: 5px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .btn-icon.edit {
            color: #0066cc;
        }
        .btn-icon.delete {
            color: #e74c3c;
        }
        .btn-icon:hover {
            background: #e7f3ff;
        }
        .search-input {
            padding: 8px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 0.9rem;
            width: 200px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-container {
                padding: 10px;
            }
            .admin-content {
                padding: 10px;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
            .admin-table th, .admin-table td {
                font-size: 0.9rem;
                padding: 8px;
            }
            .search-input {
                width: 100%;
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
        // Book search functionality
        document.getElementById('bookSearch').addEventListener('keyup', function() {
            let searchTerm = this.value.toLowerCase();
            let rows = document.querySelectorAll('table.admin-table tbody tr');
            
            rows.forEach(function(row) {
                let title = row.cells[1].textContent.toLowerCase();
                let author = row.cells[2].textContent.toLowerCase();
                let isbn = row.cells[3].textContent.toLowerCase();
                
                if (title.includes(searchTerm) || author.includes(searchTerm) || isbn.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Edit book functionality
        function editBook(id, title, author, isbn, published_year) {
            document.getElementById('book_id').value = id;
            document.getElementById('title').value = title;
            document.getElementById('author').value = author;
            document.getElementById('isbn').value = isbn;
            document.getElementById('published_year').value = published_year;
            
            document.getElementById('updateBtn').style.display = 'inline-block';
            document.getElementById('cancelBtn').style.display = 'inline-block';
            document.getElementById('addBtn').style.display = 'none';
            
            document.querySelector('.admin-form').scrollIntoView({ behavior: 'smooth' });
        }
        
        // Cancel edit functionality
        document.getElementById('cancelBtn').addEventListener('click', function() {
            document.getElementById('book_id').value = '';
            document.getElementById('title').value = '';
            document.getElementById('author').value = '';
            document.getElementById('isbn').value = '';
            document.getElementById('published_year').value = '';
            
            document.getElementById('updateBtn').style.display = 'none';
            document.getElementById('cancelBtn').style.display = 'none';
            document.getElementById('addBtn').style.display = 'inline-block';
        });
    </script>
</body>
</html>