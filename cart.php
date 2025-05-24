<?php
/* cart.php - Display and manage borrowing cart */
require_once 'config/config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Initialize the cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Add to cart action
    if ($_POST['action'] === 'add_to_cart' && isset($_POST['book_id'])) {
        $book_id = intval($_POST['book_id']);

        // Check if the book is already in the cart
        if (!in_array($book_id, $_SESSION['cart'])) {
            try {
                $_SESSION['cart'][] = $book_id;
                $_SESSION['success'] = "Book added to your borrowing cart.";
            } catch (Exception $e) {
                $_SESSION['error'] = "Error: " . $e->getMessage();
            }
        } else {
            $_SESSION['info'] = "This book is already in your cart.";
        }

        // Redirect back to the referring page or dashboard
        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            header("Location: dashboard.php");
        }
        exit;
    }

    // Remove from cart action
    elseif ($_POST['action'] === 'remove_item' && isset($_POST['book_id'])) {
        $book_id = intval($_POST['book_id']);
        $key = array_search($book_id, $_SESSION['cart']);
        if ($key !== false) {
            unset($_SESSION['cart'][$key]);
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex the array
            $_SESSION['success'] = "Book removed from your cart.";
        }
    }

    // Clear cart action
    elseif ($_POST['action'] === 'clear_cart') {
        $_SESSION['cart'] = array();
        $_SESSION['success'] = "Your borrowing cart has been cleared.";
    }
}

// Fetch books in the cart
$cart_books = array();
if (!empty($_SESSION['cart'])) {
    $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));

    try {
        $books_query = "SELECT b.* 
                       FROM books b
                       WHERE b.book_id IN ($placeholders)";
        $books_stmt = $conn->prepare($books_query);
        // Bind parameters for PDO
        foreach ($_SESSION['cart'] as $index => $book_id) {
            $books_stmt->bindValue($index + 1, $book_id, PDO::PARAM_INT);
        }
        $books_stmt->execute();
        $cart_books = $books_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
}

// Check loan limits
$user_id = $_SESSION['user_id'];
$check_limit_query = "SELECT COUNT(*) as active_loans FROM loans 
                     WHERE user_id = ? AND return_date IS NULL";
$check_stmt = $conn->prepare($check_limit_query);
$check_stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$check_stmt->execute();
$limit_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
$current_loans = $limit_result['active_loans'];
$cart_count = count($_SESSION['cart']);
$available_slots = 5 - $current_loans; // Maximum 5 books can be borrowed at once

include('includes/header.php');
?>

<div class="page-container">
    <!-- Page Header -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="d-flex align-items-center">
                <i class="fas fa-shopping-cart text-primary me-3 fa-2x"></i>
                <h2 class="mb-0">Borrowing Cart</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="d-flex justify-content-end">
                <a href="dashboard.php" class="btn filter-btn d-flex align-items-center shadow-sm">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Display alerts -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['info'])): ?>
        <div class="alert alert-info alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
            <i class="fas fa-info-circle me-2"></i> <?php echo $_SESSION['info']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['info']); ?>
    <?php endif; ?>

    <!-- Cart Info Box -->
    <div class="card welcome-card mb-4 shadow-sm fade-in">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h4 class="mb-3"><i class="fas fa-info-circle text-primary me-2"></i> Borrowing Information</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><i class="fas fa-calendar-alt text-muted me-2"></i> Loan period: <strong>up to 30 days</strong></li>
                                <li class="mb-2"><i class="fas fa-book text-muted me-2"></i> Maximum limit: <strong>5 books</strong> per user</li>
                                <li class="mb-0"><i class="fas fa-exclamation-circle text-muted me-2"></i> Late fee: <strong>Rp 1.000</strong> per day</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><i class="fas fa-undo text-muted me-2"></i> Returns: <strong>Library front desk</strong></li>
                                <li class="mb-2"><i class="fas fa-clock text-muted me-2"></i> Extensions: <strong>Up to 2 times</strong></li>
                                <li class="mb-0"><i class="fas fa-calendar-check text-muted me-2"></i> Default due date: <strong><?php echo date('d M Y', strtotime('+14 days')); ?></strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 border-start">
                    <div class="text-center">
                        <h4 class="mb-3">Your Borrowing Status</h4>
                        <div class="d-flex justify-content-center">
                            <div class="px-4 border-end">
                                <div class="h2 mb-0 text-primary"><?php echo $current_loans; ?></div>
                                <small class="text-muted">Currently Borrowed</small>
                            </div>
                            <div class="px-4">
                                <div class="h2 mb-0 <?php echo ($available_slots <= 1) ? 'text-warning' : 'text-success'; ?>"><?php echo $available_slots; ?></div>
                                <small class="text-muted">Available Slots</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($available_slots <= 1 && $available_slots > 0): ?>
        <div class="alert alert-warning mb-4 fade-in">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle me-3 fa-2x"></i>
                <div>
                    <h5 class="alert-heading mb-1">Heads up!</h5>
                    <p class="mb-0">You can only borrow <?php echo $available_slots; ?> more book(s) to reach your limit of 5 books.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Main Cart Items -->
        <div class="col-lg-8">
            <div class="card section-card shadow-sm slide-up">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-shopping-cart me-2"></i> Books in Cart</h3>
                    <?php if (count($cart_books) > 0): ?>
                        <span class="badge bg-light text-dark"><?php echo count($cart_books); ?> books</span>
                    <?php endif; ?>
                </div>

                <?php if (count($cart_books) > 0): ?>
                    <div class="bg-light px-3 py-2 border-bottom">
                        <div class="d-flex justify-content-end">
                            <form action="cart.php" method="post">
                                <input type="hidden" name="action" value="clear_cart">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash-alt me-1"></i> Clear Cart
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card-body">
                    <?php if (count($cart_books) > 0): ?>
                        <div class="row g-4">
                            <?php foreach ($cart_books as $book): ?>
                                <div class="col-12">
                                    <div class="card book-card shadow-sm animate__animated animate__fadeIn">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-md-2 mb-3 mb-md-0">
                                                    <?php
                                                    $image_src = !empty($book['cover_image']) && file_exists($book['cover_image']) 
                                                        ? $book['cover_image'] 
                                                        : 'Uploads/placeholder.jpg';
                                                    ?>
                                                    <img src="<?php echo htmlspecialchars($image_src); ?>"
                                                         class="img-fluid rounded"
                                                         alt="<?php echo htmlspecialchars($book['title']); ?> Cover"
                                                         onerror="this.src='Uploads/placeholder.jpg'">
                                                </div>
                                                <div class="col-md-8">
                                                    <h5 class="mb-1"><?php echo htmlspecialchars($book['title']); ?></h5>
                                                    <p class="mb-2 text-muted">
                                                        <i class="fas fa-user-edit me-1"></i> <?php echo htmlspecialchars($book['author']); ?>
                                                    </p>
                                                    <div class="d-flex flex-wrap">
                                                        <span class="me-3 mb-2"><i class="fas fa-calendar-alt text-muted me-1"></i> <?php echo htmlspecialchars($book['published_year']); ?></span>
                                                        <span class="mb-2"><i class="fas fa-barcode text-muted me-1"></i> <?php echo htmlspecialchars($book['isbn']); ?></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-2 text-end mt-3 mt-md-0">
                                                    <form action="cart.php" method="post">
                                                        <input type="hidden" name="action" value="remove_item">
                                                        <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                                            <i class="fas fa-times me-1"></i> Remove
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="animate__animated animate__fadeIn">
                                <i class="fas fa-shopping-cart fa-4x mb-3 text-muted"></i>
                                <h4 class="text-muted mb-3">Your borrowing cart is empty</h4>
                                <p class="text-muted mb-4">Browse our collection and add books to your cart</p>
                                <a href="books.php" class="btn btn-primary px-4 py-2">
                                    <i class="fas fa-book me-2"></i> Browse Books
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Cart Summary -->
        <div class="col-lg-4">
            <div class="card section-card shadow-sm slide-up">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0"><i class="fas fa-clipboard-list me-2"></i> Checkout Summary</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Books in cart:</span>
                        <span class="fw-bold"><?php echo count($cart_books); ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Loan period:</span>
                        <span class="fw-bold">Up to 30 days</span>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Default due date:</span>
                        <span class="fw-bold"><?php echo date('d M Y', strtotime('+14 days')); ?></span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Currently borrowed:</span>
                        <span class="fw-bold"><?php echo $current_loans; ?> / 5</span>
                    </div>

                    <div class="d-flex justify-content-between mb-4">
                        <span>After this checkout:</span>
                        <span class="fw-bold <?php echo ($current_loans + count($cart_books) > 5) ? 'text-danger' : ''; ?>">
                            <?php echo min(5, $current_loans + count($cart_books)); ?> / 5
                        </span>
                    </div>

                    <?php if ($current_loans + count($cart_books) > 5): ?>
                        <div class="alert alert-danger mb-4">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            You've exceeded the maximum borrowing limit. Please remove
                            <strong><?php echo ($current_loans + count($cart_books)) - 5; ?></strong>
                            book(s) from your cart.
                        </div>
                    <?php endif; ?>

                    <form action="borrow.php" method="post">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn btn-primary btn-lg w-100"
                            <?php echo (count($cart_books) == 0 || $current_loans + count($cart_books) > 5) ? 'disabled' : ''; ?>>
                            <i class="fas fa-check me-2"></i> Complete Borrowing
                        </button>
                    </form>

                    <a href="books.php" class="btn btn-outline-secondary w-100 mt-3">
                        <i class="fas fa-plus me-2"></i> Add More Books
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Main color variables */
    :root {
        --primary-light: #cfe2ff;
        --primary-very-light: #ebf2ff;
        --secondary-color: #6c757d;
        --success-color: #198754;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --light-color: #f8f9fa;
        --dark-color: #212529;
        --body-bg: #f6f8fc;
        --white: #ffffff;
    }

    /* General styles */
    body {
        background-color: var(--body-bg);
        font-family: 'Roboto', 'Segoe UI', sans-serif;
        color: var(--dark-color);
    }

    .page-container {
        padding: 2rem 1rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Cards and sections */
    .card {
        border: none;
        border-radius: 0.75rem;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 10px 20px rgba(13, 110, 253, 0.1) !important;
    }

    .card-header {
        padding: 1rem 1.5rem;
        font-weight: 500;
    }

    .welcome-card {
        background-color: var(--primary-very-light);
        border-left: 5px solid var(--primary-color);
    }

    .section-card {
        margin-bottom: 1.5rem;
    }

    /* Book cards */
    .book-card {
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .book-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 15px rgba(13, 110, 253, 0.15) !important;
    }

    /* Buttons */
    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-primary:hover,
    .btn-primary:focus {
        background-color: var(--primary-dark);
        border-color: var(--primary-dark);
    }

    .btn-outline-primary {
        color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-outline-primary:hover {
        background-color: var(--primary-color);
        color: var(--white);
    }

    .btn-success {
        background-color: var(--success-color);
        border-color: var(--success-color);
    }

    .filter-btn {
        background-color: var(--white);
        color: var(--primary-color);
        border: 1px solid rgba(13, 110, 253, 0.2);
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
        transition: all 0.3s ease;
    }

    .filter-btn:hover {
        background-color: var(--primary-very-light);
        color: var(--primary-dark);
    }

    /* Alerts */
    .alert {
        border-radius: 0.75rem;
        border: none;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
    }

    .alert-warning {
        background-color: #fff3cd;
        color: #856404;
    }

    .alert-info {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    /* Badges */
    .badge {
        padding: 0.5em 0.8em;
        font-weight: 500;
        border-radius: 0.5rem;
    }

    .bg-primary {
        background-color: var(--primary-color) !important;
    }

    .bg-success {
        background-color: var(--success-color) !important;
    }

    .bg-warning {
        background-color: var(--warning-color) !important;
    }

    .text-primary {
        color: var(--primary-color) !important;
    }

    /* Animations */
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }

    .slide-up {
        animation: slideUp 0.5s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideUp {
        from {
            transform: translateY(20px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* Utility classes */
    .shadow-sm {
        box-shadow: 0 0.125rem 0.25rem rgba(13, 110, 253, 0.1) !important;
    }

    /* Icons */
    .fas {
        color: var(--primary-color);
    }

    .text-muted .fas {
        color: var(--secondary-color);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .card-body {
            padding: 1rem;
        }

        h2 {
            font-size: 1.5rem;
        }

        h3 {
            font-size: 1.25rem;
        }
    }

    /* Cart empty state */
    .text-center.py-5 {
        padding: 3rem 0;
    }

    .text-center.py-5 .fas {
        color: var(--secondary-color);
    }
</style>

<!-- Bootstrap JS and additional scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animate cart items with staggered delay
        const cartItems = document.querySelectorAll('.book-card');
        cartItems.forEach((item, index) => {
            setTimeout(() => {
                item.classList.add('animate__animated', 'animate__fadeIn');
            }, index * 100);
        });

        // Auto dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const closeButton = alert.querySelector('.btn-close');
                if (closeButton) {
                    closeButton.click();
                }
            }, 5000);
        });
    });
</script>

<?php include('includes/footer.php'); ?>