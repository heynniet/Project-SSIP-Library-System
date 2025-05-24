<?php
/* borrow.php - Handle book borrowing process */
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
    $_SESSION['cart'] = [];
}

// Debug session
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));

// Process actions from the cart page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data: " . print_r($_POST, true));
    if (!isset($_POST['action'])) {
        $_SESSION['error'] = "No action specified in POST data.";
        header("Location: borrow.php");
        exit;
    }
    try {
        $action = $_POST['action'];
        // Handle 'checkout' as 'borrow_all' to prevent error
        if ($action === 'checkout') {
            error_log("Received action=checkout, treating as borrow_all");
            $action = 'borrow_all';
        }
        switch ($action) {
            case 'borrow_all':
                error_log("Processing borrow_all action");
                // Process the actual borrowing
                if (empty($_SESSION['cart'])) {
                    $_SESSION['error'] = "Your borrowing cart is empty.";
                    header("Location: cart.php");
                    exit;
                }

                // Validate inputs
                if (!isset($_POST['borrower_name']) || empty(trim($_POST['borrower_name']))) {
                    $_SESSION['error'] = "Please enter your name.";
                    header("Location: borrow.php");
                    exit;
                }

                if (!isset($_POST['borrower_email']) || empty(trim($_POST['borrower_email'])) || !filter_var($_POST['borrower_email'], FILTER_VALIDATE_EMAIL)) {
                    $_SESSION['error'] = "Please enter a valid email address.";
                    header("Location: borrow.php");
                    exit;
                }

                if (!isset($_POST['due_date']) || empty($_POST['due_date'])) {
                    $_SESSION['error'] = "Please select a due date.";
                    header("Location: borrow.php");
                    exit;
                }

                // Validate due date
                $loan_date = date('Y-m-d');
                $selected_due_date = $_POST['due_date'];
                $max_due_date = date('Y-m-d', strtotime('+30 days'));

                if (strtotime($selected_due_date) > strtotime($max_due_date)) {
                    $_SESSION['error'] = "The due date cannot be more than 30 days from today.";
                    header("Location: borrow.php");
                    exit;
                }

                if (strtotime($selected_due_date) <= strtotime($loan_date)) {
                    $_SESSION['error'] = "The due date must be after today.";
                    header("Location: borrow.php");
                    exit;
                }

                $conn->beginTransaction();

                $due_date = $selected_due_date;
                $user_id = (int)$_SESSION['user_id'];
                $borrower_name = trim($_POST['borrower_name']);
                $borrower_email = trim($_POST['borrower_email']);
                $borrower_notes = isset($_POST['borrower_notes']) ? trim($_POST['borrower_notes']) : '';

                // Check maximum allowed books (limit to 5)
                $check_limit_query = "SELECT COUNT(*) as active_loans FROM loans WHERE user_id = ? AND return_date IS NULL";
                $check_stmt = $conn->prepare($check_limit_query);
                $check_stmt->bindValue(1, $user_id, PDO::PARAM_INT);
                $check_stmt->execute();
                $limit_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
                $current_loans = $limit_result['active_loans'];
                $new_loans = count($_SESSION['cart']);

                if (($current_loans + $new_loans) > 5) {
                    throw new Exception("You can only borrow up to 5 books at a time. You currently have {$current_loans} books borrowed.");
                }

                // Insert loans for each book in cart
                foreach ($_SESSION['cart'] as $book_id) {
                    $book_id = (int)$book_id;

                    // Check if book exists
                    $book_check_query = "SELECT 1 FROM books WHERE book_id = ?";
                    $book_check_stmt = $conn->prepare($book_check_query);
                    $book_check_stmt->bindValue(1, $book_id, PDO::PARAM_INT);
                    $book_check_stmt->execute();
                    if ($book_check_stmt->rowCount() == 0) {
                        throw new Exception("One or more books in your cart no longer exist.");
                    }

                    // Insert loan record
                    $insert_query = "INSERT INTO loans (user_id, book_id, loan_date, due_date, borrower_name, borrower_email, notes) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->bindValue(1, $user_id, PDO::PARAM_INT);
                    $insert_stmt->bindValue(2, $book_id, PDO::PARAM_INT);
                    $insert_stmt->bindValue(3, $loan_date, PDO::PARAM_STR);
                    $insert_stmt->bindValue(4, $due_date, PDO::PARAM_STR);
                    $insert_stmt->bindValue(5, $borrower_name, PDO::PARAM_STR);
                    $insert_stmt->bindValue(6, $borrower_email, PDO::PARAM_STR);
                    $insert_stmt->bindValue(7, $borrower_notes, PDO::PARAM_STR);
                    $insert_stmt->execute();
                }

                $conn->commit();
                $_SESSION['cart'] = [];
                $_SESSION['success'] = "Successfully borrowed {$new_loans} book(s). Please return by " . date('F j, Y', strtotime($due_date)) . ".";
                header("Location: dashboard.php");
                exit;

            case 'clear_cart':
                $_SESSION['cart'] = [];
                $_SESSION['success'] = "Your borrowing cart has been cleared.";
                header("Location: cart.php");
                exit;

            case 'remove_item':
                if (isset($_POST['book_id']) && in_array((int)$_POST['book_id'], $_SESSION['cart'])) {
                    $key = array_search((int)$_POST['book_id'], $_SESSION['cart']);
                    if ($key !== false) {
                        unset($_SESSION['cart'][$key]);
                        $_SESSION['cart'] = array_values($_SESSION['cart']);
                        $_SESSION['success'] = "Book removed from your borrowing cart.";
                    }
                }
                header("Location: cart.php");
                exit;

            default:
                $_SESSION['error'] = "Invalid action: " . htmlspecialchars($action);
                header("Location: borrow.php");
                exit;
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: borrow.php");
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = $e->getMessage();
        header("Location: borrow.php");
        exit;
    }
}

// Process add to cart action
if (isset($_GET['id'])) {
    try {
        $book_id = (int)$_GET['id'];

        // Check if book exists
        $book_query = "SELECT title FROM books WHERE book_id = ?";
        $stmt = $conn->prepare($book_query);
        $stmt->bindValue(1, $book_id, PDO::PARAM_INT);
        $stmt->execute();
        $book = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$book) {
            $_SESSION['error'] = "Book not found.";
            header("Location: dashboard.php");
            exit;
        }

        // Check if book is already in cart
        if (in_array($book_id, $_SESSION['cart'])) {
            $_SESSION['info'] = "This book is already in your borrowing cart.";
            header("Location: cart.php");
            exit;
        }

        // Check loan limits
        $user_id = (int)$_SESSION['user_id'];
        $check_limit_query = "SELECT COUNT(*) as active_loans FROM loans WHERE user_id = ? AND return_date IS NULL";
        $check_stmt = $conn->prepare($check_limit_query);
        $check_stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $check_stmt->execute();
        $limit_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        $current_loans = $limit_result['active_loans'];
        $cart_count = count($_SESSION['cart']);

        if (($current_loans + $cart_count + 1) > 5) {
            $_SESSION['error'] = "You can only borrow up to 5 books at a time.";
            header("Location: dashboard.php");
            exit;
        }

        // Add book to cart
        $_SESSION['cart'][] = $book_id;
        $_SESSION['success'] = "Book added to your borrowing cart.";
        header("Location: cart.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: dashboard.php");
        exit;
    }
}

// Fetch books in the cart for display
$cart_books = [];
if (!empty($_SESSION['cart'])) {
    try {
        $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
        $books_query = "SELECT book_id, title, author, isbn, published_year, cover_image 
                        FROM books WHERE book_id IN ($placeholders)";
        $stmt = $conn->prepare($books_query);
        foreach ($_SESSION['cart'] as $index => $book_id) {
            $stmt->bindValue($index + 1, (int)$book_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $cart_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        $cart_books = [];
    }
}

// Check loan limits
$user_id = (int)$_SESSION['user_id'];
$current_loans = 0;
$available_slots = 5;
try {
    $check_limit_query = "SELECT COUNT(*) as active_loans FROM loans WHERE user_id = ? AND return_date IS NULL";
    $check_stmt = $conn->prepare($check_limit_query);
    $check_stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $check_stmt->execute();
    $limit_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    $current_loans = $limit_result['active_loans'];
    $available_slots = 5 - $current_loans;
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

// Calculate min and max dates for due date picker
$min_date = date('Y-m-d', strtotime('+1 day'));
$max_date = date('Y-m-d', strtotime('+30 days'));

include('includes/header.php');
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

<style>
.borrowing-form {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.loan-info {
    background-color: #f8f9fa;
    border-left: 4px solid #0d6efd;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.limit-warning {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.cart-item {
    transition: all 0.3s ease;
}

.cart-item:hover {
    background-color: rgba(0,0,0,0.02);
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.date-info {
    font-size: 0.875rem;
    color: #6c757d;
}

.required-label::after {
    content: " *";
    color: #dc3545;
}

.loan-book-cover {
    width: 60px;
    height: 90px;
    object-fit: cover;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.availability-badge {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
}
</style>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-book-reader text-primary me-2"></i> Borrow Books</h2>
        <div>
            <a href="cart.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Cart
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['info'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i> <?php echo $_SESSION['info']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['info']); ?>
    <?php endif; ?>

    <?php if (empty($_SESSION['cart'])): ?>
        <div class="alert alert-warning" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> Your cart is empty. Please add books to your cart before proceeding to borrowing.
        </div>
        <div class="text-center mt-4">
            <a href="books.php" class="btn btn-primary">
                <i class="fas fa-book me-2"></i> Browse Books
            </a>
        </div>
    <?php else: ?>
        <div class="loan-info">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-2"><i class="fas fa-info-circle me-2"></i> Borrowing Information</h5>
                    <p class="mb-1">- Loan period: <strong>up to 30 days</strong> from today</p>
                    <p class="mb-1">- Maximum borrowing limit: <strong>5 books</strong> per user</p>
                    <p class="mb-0">- Late returns incur a fee of <strong>Rp 1.000</strong> per day</p>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-md-end mt-3 mt-md-0">
                        <div class="me-4">
                            <span class="text-muted">Currently Borrowed:</span>
                            <h5 class="mb-0"><?php echo $current_loans; ?> book(s)</h5>
                        </div>
                        <div>
                            <span class="text-muted">Available Slots:</span>
                            <h5 class="mb-0"><?php echo $available_slots; ?> of 5</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($available_slots <= 1 && $available_slots > 0): ?>
            <div class="limit-warning">
                <i class="fas fa-exclamation-triangle me-2"></i> 
                <strong>Heads up!</strong> You can only borrow <?php echo $available_slots; ?> more book(s) to reach your limit of 5 books.
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            Books to Borrow
                            <span class="ms-2 badge bg-primary rounded-pill"><?php echo count($cart_books); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($cart_books as $index => $book): 
                                $current_year = date('Y');
                                $is_new = ($book['published_year'] >= ($current_year - 1));
                            ?>
                                <div class="list-group-item cart-item animate__animated animate__fadeIn" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                    <div class="row align-items-center">
                                        <div class="col-md-2 mb-2 mb-md-0 position-relative">
                                            <img src="<?php echo htmlspecialchars($book['cover_image'] ?: 'Uploads/placeholder.jpg'); ?>"
                                                 class="img-fluid rounded loan-book-cover"
                                                 alt="<?php echo htmlspecialchars($book['title']); ?> Cover"
                                                 onerror="this.src='Uploads/placeholder.jpg'">
                                            <?php if ($is_new): ?>
                                                <span class="position-absolute top-0 start-0 translate-middle-y badge bg-danger">
                                                    <i class="fas fa-fire"></i> New
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-10 mb-2 mb-md-0">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($book['title']); ?></h5>
                                            <p class="mb-1 text-muted">
                                                <i class="fas fa-user-edit me-1"></i> <?php echo htmlspecialchars($book['author']); ?>
                                            </p>
                                            <div class="d-flex flex-wrap">
                                                <span class="me-3"><i class="fas fa-calendar-alt text-secondary me-1"></i> <?php echo htmlspecialchars($book['published_year']); ?></span>
                                                <span><i class="fas fa-barcode text-secondary me-1"></i> <?php echo htmlspecialchars($book['isbn']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <p class="text-muted mb-0">Total books: <strong><?php echo count($cart_books); ?></strong></p>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i> Borrower Information</h5>
                    </div>
                    <div class="card-body">
                        <form action="borrow.php" method="post">
                            <input type="hidden" name="action" value="borrow_all">
                            <div class="mb-3">
                                <label for="borrower_name" class="form-label required-label">Full Name</label>
                                <input type="text" class="form-control" id="borrower_name" name="borrower_name" value="<?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="borrower_email" class="form-label required-label">Email Address</label>
                                <input type="email" class="form-control" id="borrower_email" name="borrower_email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" required>
                                <div class="form-text">We'll use this email to send you reminders about due dates.</div>
                            </div>
                            <div class="mb-3">
                                <label for="due_date" class="form-label required-label">Return Due Date</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" 
                                       min="<?php echo $min_date; ?>" 
                                       max="<?php echo $max_date; ?>" required>
                                <div class="date-info mt-1">
                                    <i class="fas fa-info-circle me-1"></i> Select a return date (maximum 30 days from today)
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="borrower_notes" class="form-label">Additional Notes</label>
                                <textarea class="form-control" id="borrower_notes" name="borrower_notes" rows="3" placeholder="Any special requirements or notes for this loan..."></textarea>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" <?php echo ($current_loans + count($cart_books) > 5) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-check-circle me-2"></i> Confirm Borrowing
                                </button>
                                <a href="cart.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Back to Cart
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dueDateInput = document.getElementById('due_date');
        if (dueDateInput) {
            const defaultDueDate = new Date();
            defaultDueDate.setDate(defaultDueDate.getDate() + 14);
            const year = defaultDueDate.getFullYear();
            const month = String(defaultDueDate.getMonth() + 1).padStart(2, '0');
            const day = String(defaultDueDate.getDate()).padStart(2, '0');
            dueDateInput.value = `${year}-${month}-${day}`;
        }

        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const closeButton = alert.querySelector('.btn-close');
                if (closeButton) closeButton.click();
            }, 5000);
        });

        // Debug form submission
        const form = document.querySelector('form[action="borrow.php"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                console.log('Form submitting with data:');
                const formData = new FormData(form);
                for (let [key, value] of formData.entries()) {
                    console.log(`${key}: ${value}`);
                }
            });
        }
    });
</script>

<?php include('includes/footer.php'); ?>