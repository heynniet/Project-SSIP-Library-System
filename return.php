<?php
/* return.php - Process book returns */
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

// Database connection
$conn = new mysqli("localhost", "root", "", "library_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize response variables
$success = false;
$message = "";
$redirect = "dashboard.php";
$book_details = null;

// Check if loan_id is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $loan_id = intval($_GET['id']);
    
    // Verify this loan belongs to the current user
    $check_query = "SELECT l.*, b.title, b.author, b.isbn, b.cover_image FROM loans l 
                    JOIN books b ON l.book_id = b.book_id
                    WHERE l.loan_id = ? AND l.user_id = ? AND l.return_date IS NULL";
    
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $loan_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $loan_data = $result->fetch_assoc();
        $book_details = [
            'title' => $loan_data['title'],
            'author' => $loan_data['author'],
            'isbn' => $loan_data['isbn'],
            'book_id' => $loan_data['book_id'],
            'loan_date' => $loan_data['loan_date'],
            'due_date' => $loan_data['due_date'],
            'cover_image' => $loan_data['cover_image']
        ];
        
        // Calculate any fines
        $today = new DateTime();
        $due_date = new DateTime($loan_data['due_date']);
        $days_overdue = ($today > $due_date) ? $today->diff($due_date)->days : 0;
        $fine = 0;
        
        if ($days_overdue > 0) {
            $fine = $days_overdue * 1000; // 1000 IDR per day
        }
        
        $book_details['days_overdue'] = $days_overdue;
        $book_details['fine'] = $fine;
        
        // Process form submission for return confirmation
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_return'])) {
            // Update the loan record with return date
            $return_date = date('Y-m-d H:i:s');
            $update_query = "UPDATE loans SET return_date = ?, fine_amount = ? WHERE loan_id = ?";
            
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sdi", $return_date, $fine, $loan_id);
            
            if ($update_stmt->execute()) {
                $success = true;
                $message = "Book successfully returned!";
                
                // Record fine payment if there is any
                if ($fine > 0 && isset($_POST['payment_method'])) {
                    $payment_method = $conn->real_escape_string($_POST['payment_method']);
                    $payment_query = "INSERT INTO payments (loan_id, amount, payment_date, payment_method, user_id) 
                                     VALUES (?, ?, ?, ?, ?)";
                    
                    $payment_stmt = $conn->prepare($payment_query);
                    $payment_stmt->bind_param("idssi", $loan_id, $fine, $return_date, $payment_method, $_SESSION['user_id']);
                    $payment_stmt->execute();
                }
                
                // Redirect after successful return
                header("Location: dashboard.php?return_success=1&book=" . urlencode($book_details['title']));
                exit;
            } else {
                $message = "Error processing return: " . $conn->error;
            }
        }
    } else {
        $message = "Invalid loan ID or this book has already been returned.";
    }
} else {
    $message = "No loan ID provided.";
}

include('includes/header.php');
?>

<!-- Add CSS Libraries -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-undo me-2"></i> Return Book</h3>
                </div>
                
                <?php if ($book_details): ?>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3 text-center">
                            <?php
                            // Get cover image with proper fallback
                            $image_src = !empty($book_details['cover_image']) && file_exists($book_details['cover_image']) 
                                ? $book_details['cover_image'] 
                                : 'Uploads/placeholder.jpg';
                            ?>
                            <img src="<?php echo htmlspecialchars($image_src); ?>" 
                                class="img-fluid rounded shadow-sm" 
                                alt="<?php echo htmlspecialchars($book_details['title']); ?> Cover"
                                onerror="this.src='Uploads/placeholder.jpg'" 
                                style="max-height: 180px;">
                        </div>
                        <div class="col-md-9">
                            <h4><?php echo htmlspecialchars($book_details['title']); ?></h4>
                            <p class="text-muted mb-1"><i class="fas fa-user-edit me-2"></i><?php echo htmlspecialchars($book_details['author']); ?></p>
                            <p class="text-muted mb-1"><i class="fas fa-barcode me-2"></i>ISBN: <?php echo htmlspecialchars($book_details['isbn']); ?></p>
                            
                            <div class="mt-3">
                                <div class="d-flex justify-content-between">
                                    <span>
                                        <i class="fas fa-calendar-alt me-2 text-muted"></i>
                                        Borrowed: <?php echo date('d M Y', strtotime($book_details['loan_date'])); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-calendar-check me-2 <?php echo ($book_details['days_overdue'] > 0) ? 'text-danger' : 'text-muted'; ?>"></i>
                                        Due Date: <?php echo date('d M Y', strtotime($book_details['due_date'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($book_details['days_overdue'] > 0): ?>
                    <div class="alert alert-warning">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-exclamation-circle fa-2x text-danger"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">This book is overdue by <?php echo $book_details['days_overdue']; ?> days</h5>
                                <p class="mb-0">A fine of Rp <?php echo number_format($book_details['fine'], 0, ',', '.'); ?> will be applied.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <?php if ($book_details['fine'] > 0): ?>
                        <div class="mb-4">
                            <h5>Payment Method for Fine</h5>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="cash" value="cash" checked>
                                <label class="form-check-label" for="cash">
                                    <i class="fas fa-money-bill-wave me-2 text-success"></i> Cash
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="transfer" value="transfer">
                                <label class="form-check-label" for="transfer">
                                    <i class="fas fa-university me-2 text-primary"></i> Bank Transfer
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="e-wallet" value="e-wallet">
                                <label class="form-check-label" for="e-wallet">
                                    <i class="fas fa-wallet me-2 text-warning"></i> E-Wallet
                                </label>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                            </a>
                            <button type="submit" name="confirm_return" class="btn btn-success">
                                <i class="fas fa-check-circle me-2"></i> Confirm Return
                            </button>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <div class="card-body text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h4 class="mb-3"><?php echo $message; ?></h4>
                    <a href="dashboard.php" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i> Back to Dashboard
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
// Close database connection
$conn->close();

include('includes/footer.php');
?>