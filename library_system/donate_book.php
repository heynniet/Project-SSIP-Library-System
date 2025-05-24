<?php
/* donate_book.php - Book donation page */
session_start();
include 'config/config.php';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['donate'])) {
    $user_id = $_SESSION['user_id'];
    $book_title = trim($_POST['book_title']);
    $author = trim($_POST['author']);
    $publisher = !empty($_POST['publisher']) ? trim($_POST['publisher']) : null;
    $publication_year = !empty($_POST['publication_year']) ? $_POST['publication_year'] : null;
    $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
    $book_cover = !empty($_POST['book_cover']) ? trim($_POST['book_cover']) : null;
    $status = 'Pending'; // Default status is Pending

    try {
        // Prepare SQL statement to prevent SQL injection
        $sql = "INSERT INTO book_donations (user_id, book_title, author, publisher, publication_year, description, book_cover, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id, $book_title, $author, $publisher, $publication_year, $description, $book_cover, $status]);

        $success_message = "Thank you! Your book donation has been submitted successfully and is pending review.";

        // Clear form data after successful submission
        $_POST = array();
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get user's previous donations
$user_donations = [];
if (isset($_SESSION['user_id'])) {
    try {
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT * FROM book_donations WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $user_donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Silently fail
    }
}

// Include header - this should come before any HTML output
require_once('includes/header.php');
?>

<!-- Add CSS for this page -->
<style>
    .donation-form {
        background-color: #f8f9fa;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .donation-history {
        background-color: #ffffff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
    }

    .status-pending {
        color: #ffc107;
    }

    .status-approved {
        color: #28a745;
    }

    .status-rejected {
        color: #dc3545;
    }

    .form-section {
        margin-bottom: 20px;
    }

    .form-section-title {
        border-bottom: 2px solid #4CAF50;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
</style>

<!-- Main Content -->
<div class="container mt-4">
    <h1 class="mb-4"><i class="fas fa-book-medical"></i> Donate Books</h1>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> You need to <a href="login.php">login</a> to donate books.
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-7">
                <div class="donation-form">
                    <h2 class="form-section-title"><i class="fas fa-gift"></i> Donate a Book</h2>
                    <p class="text-muted mb-4">Fill out the form below to donate your book to our library. Required fields are marked with *</p>

                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="form-section">
                            <h4>Book Information</h4>

                            <div class="mb-3">
                                <label for="book_title" class="form-label">Book Title *</label>
                                <input type="text" class="form-control" id="book_title" name="book_title" required
                                    value="<?php echo isset($_POST['book_title']) ? htmlspecialchars($_POST['book_title']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="author" class="form-label">ISBN *</label>
                                <input type="text" class="form-control" id="isbn" name="isbn" required
                                    value="<?php echo isset($_POST['isbn']) ? htmlspecialchars($_POST['isbn']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="author" class="form-label">Author *</label>
                                <input type="text" class="form-control" id="author" name="author" required
                                    value="<?php echo isset($_POST['author']) ? htmlspecialchars($_POST['author']) : ''; ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="publisher" class="form-label">Publisher</label>
                                    <input type="text" class="form-control" id="author" name="author"
                                        value="<?php echo isset($_POST['author']) ? htmlspecialchars($_POST['author']) : ''; ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="publication_year" class="form-label">Publication Year</label>
                                    <select class="form-select" id="publication_year" name="publication_year">
                                        <option value="">Select Year</option>
                                        <?php
                                        $current_year = date("Y");
                                        for ($year = $current_year; $year >= 1900; $year--) {
                                            $selected = (isset($_POST['publication_year']) && $_POST['publication_year'] == $year) ? 'selected' : '';
                                            echo "<option value=\"$year\" $selected>$year</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>


                            <div class="mb-3">
                                <label for="book_cover" class="form-label">Book Cover URL</label>
                                <input type="url" class="form-control" id="book_cover" name="book_cover" placeholder="https://example.com/book-cover.jpg"
                                    value="<?php echo isset($_POST['book_cover']) ? htmlspecialchars($_POST['book_cover']) : ''; ?>">
                                <div class="form-text">Enter a URL to an image of the book cover (optional)</div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    I confirm that I own this book and willingly donate it to the library
                                </label>
                            </div>
                        </div>

                        <button type="submit" name="donate" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane"></i> Submit Donation
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-5">
                <div class="donation-history">
                    <h2 class="form-section-title"><i class="fas fa-history"></i> Your Donation History</h2>

                    <?php if (empty($user_donations)): ?>
                        <div class="text-center p-4">
                            <i class="fas fa-book fa-3x mb-3 text-muted"></i>
                            <p class="text-muted">You haven't donated any books yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Book Title</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_donations as $donation): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($donation['book_title']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($donation['created_at'])); ?></td>
                                            <td>
                                                <?php
                                                $status_class = 'status-pending';
                                                $status_icon = 'clock';

                                                if ($donation['status'] == 'Approved') {
                                                    $status_class = 'status-approved';
                                                    $status_icon = 'check-circle';
                                                } elseif ($donation['status'] == 'Rejected') {
                                                    $status_class = 'status-rejected';
                                                    $status_icon = 'times-circle';
                                                }
                                                ?>
                                                <span class="<?php echo $status_class; ?>">
                                                    <i class="fas fa-<?php echo $status_icon; ?>"></i>
                                                    <?php echo htmlspecialchars($donation['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <h5><i class="fas fa-info-circle"></i> Donation Process</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item bg-transparent"><i class="fas fa-clock text-warning"></i> <strong>Pending</strong>: Your donation is being reviewed</li>
                            <li class="list-group-item bg-transparent"><i class="fas fa-check-circle text-success"></i> <strong>Approved</strong>: Please bring the book to the library</li>
                            <li class="list-group-item bg-transparent"><i class="fas fa-times-circle text-danger"></i> <strong>Rejected</strong>: Unfortunately, we cannot accept this book</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Bootstrap CSS and JS links -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- JavaScript for menu toggle -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menuToggle');
        const mainNav = document.getElementById('mainNav');

        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                mainNav.classList.toggle('show');
            });
        }
    });
</script>

<?php
// Include footer
require_once('includes/footer.php');
?>