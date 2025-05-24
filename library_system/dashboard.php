<?php
/* dashboard.php - User dashboard after login */
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

// Initialize variables for conditions and query building
$where_conditions = array();
$active_filters = array();

// Process search filter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $conn->real_escape_string($_GET['search']);
    $where_conditions[] = "(b.title LIKE '%$search_term%' OR b.author LIKE '%$search_term%' OR b.isbn LIKE '%$search_term%')";
    $active_filters['search'] = $search_term;
}

// Process popular books filter
if (isset($_GET['popular']) && $_GET['popular'] == 1) {
    // For popular books, we would ideally join with loans table to count how many times a book has been borrowed
    // Since this isn't implemented in the original code, we'll use a placeholder approach
    $where_conditions[] = "(b.featured = 1 OR b.recommended = 1)"; // Assuming these columns exist
    $active_filters['popular'] = 1;
}

// Build the main books query
$books_query = "SELECT b.* FROM books b";

// Add WHERE clause if any conditions exist
if (!empty($where_conditions)) {
    $books_query .= " WHERE " . implode(" AND ", $where_conditions);
}

// Pagination settings
$items_per_page = 8;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $items_per_page;

// Get total count for pagination
$count_query = str_replace("SELECT b.*", "SELECT COUNT(*) as total", $books_query);
$count_result = $conn->query($count_query);
$total_count = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_count / $items_per_page);

// Finalize the query with order and limit for pagination
$books_query .= " ORDER BY b.title LIMIT $offset, $items_per_page";
$books_result = $conn->query($books_query);

// Check for borrowed books by this member
$loans_query = "SELECT l.*, b.title, b.author, b.isbn, b.cover_image 
                FROM loans l 
                JOIN books b ON l.book_id = b.book_id 
                WHERE l.user_id = " . $_SESSION['user_id'] . "
                ORDER BY l.loan_date DESC";
$loans_result = $conn->query($loans_query);

// Helper function to add query parameters to URLs
function add_query_params($new_params)
{
    $params = $_GET;
    foreach ($new_params as $key => $value) {
        $params[$key] = $value;
    }
    return '?' . http_build_query($params);
}

$return_success = isset($_GET['return_success']) && $_GET['return_success'] == 1;
$returned_book = isset($_GET['book']) ? $_GET['book'] : '';

include('includes/header.php');
?>

<!-- Add CSS Libraries -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

<div class="page-container">
    <!-- Top Bar: Search and Filters -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <form class="d-flex align-items-center search-form" method="GET" action="dashboard.php">
                <i class="fas fa-search text-muted me-2"></i>
                <input
                    type="search"
                    name="search"
                    class="search-input"
                    placeholder="Search books by title, author, or ISBN..."
                    aria-label="Search"
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button class="search-btn" type="submit">
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Active Filters Display -->
    <?php if (!empty($active_filters)): ?>
        <div class="d-flex align-items-center mb-4 fade-in">
            <span class="me-2 text-muted fw-bold">Active Filters:</span>
            <?php if (isset($active_filters['search'])): ?>
                <div class="filter-badge">
                    <i class="fas fa-search me-1"></i>
                    "<?php echo htmlspecialchars($active_filters['search']); ?>"
                    <a href="<?php echo add_query_params(['search' => null]); ?>" class="close-icon text-decoration-none">
                        <i class="fas fa-times-circle"></i>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (count($active_filters) > 1): ?>
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary ms-auto">
                    <i class="fas fa-times me-1"></i> Clear All Filters
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Welcome Box -->
    <div class="card welcome-card mb-4 shadow-sm fade-in">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="bg-primary text-white rounded-circle p-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user fa-2x"></i>
                    </div>
                </div>
                <div class="col-md-7">
                    <h2 class="mb-1">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
                    <div class="d-flex flex-wrap">
                        <span class="me-3 mb-2"><i class="fas fa-envelope text-muted me-2"></i><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                        <span class="me-3 mb-2"><i class="fas fa-id-card text-muted me-2"></i>ID: <?php echo htmlspecialchars($_SESSION['user_id']); ?></span>
                        <?php if ($loans_result->num_rows > 0): ?>
                            <span><i class="fas fa-book-reader text-primary me-2"></i>You have <b><?php echo $loans_result->num_rows; ?></b> books borrowed</span>
                        <?php else: ?>
                            <span><i class="fas fa-book-reader text-muted me-2"></i>No books borrowed currently</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3 text-end mt-3 mt-md-0">
                    <a href="logout.php" class="btn btn-outline-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if ($return_success): ?>
<div class="alert alert-success alert-dismissible fade show mb-4 animate__animated animate__fadeIn" role="alert">
    <div class="d-flex align-items-center">
        <div class="me-3">
            <i class="fas fa-check-circle fa-2x"></i>
        </div>
        <div>
            <h5 class="mb-0">Book Successfully Returned!</h5>
            <?php if (!empty($returned_book)): ?>
            <p class="mb-0">Thank you for returning "<?php echo htmlspecialchars($returned_book); ?>". We hope you enjoyed it!</p>
            <?php endif; ?>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

    <!-- Books Display Section -->
    <div class="card section-card mb-4 shadow-sm slide-up">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><i class="fas fa-book me-2"></i> Library Books</h3>
            <div>
                <span class="badge bg-light text-dark"><?php echo $total_count; ?> books found</span>
            </div>
        </div>

        <!-- Filter buttons -->
        <div class="bg-light px-3 py-2 border-bottom">
            <div class="d-flex flex-wrap">
                <a href="dashboard.php" class="filter-btn btn <?php echo !isset($_GET['new']) && !isset($_GET['popular']) ? 'active' : 'btn-light'; ?>">
                    <i class="fas fa-th-large me-2"></i>All Books
                </a>
            </div>
        </div>

        <div class="row g-4">
            <?php
            // Cek apakah query berhasil dan ada data sebelum melakukan iterasi
            if ($books_result && $books_result->num_rows > 0):
                // Pre-load all borrowed books to array untuk performa
                $borrowed_books = isset($borrowed_books) && is_array($borrowed_books) ? $borrowed_books : [];

                // Definisikan tahun untuk buku baru di luar loop
                $current_year = (int)date('Y');
                $new_book_year = $current_year - 1;

                // Loop buku
                while ($book = $books_result->fetch_assoc()):
                    // Validasi data buku
                    $book_id = isset($book['book_id']) ? (int)$book['book_id'] : 0;
                    $title = isset($book['title']) ? htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') : 'Unknown Title';
                    $author = isset($book['author']) ? htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8') : 'Unknown Author';
                    $isbn = isset($book['isbn']) ? htmlspecialchars($book['isbn'], ENT_QUOTES, 'UTF-8') : '0000000000';
                    $published_year = isset($book['published_year']) ? (int)$book['published_year'] : 0;
                
                    // Pengecekan status buku
                    $is_borrowed = in_array($book_id, $borrowed_books);
                    $is_new = ($published_year >= $new_book_year);
                
                    // Gambar path menggunakan kolom cover_image
                    $image_src = !empty($book['cover_image']) && file_exists($book['cover_image']) 
                        ? $book['cover_image'] 
                        : 'Uploads/placeholder.jpg';
                ?>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="card h-100 book-card shadow-sm">
                        <?php if ($is_new): ?>
                            <div class="badge-corner bg-danger text-white">
                                <i class="fas fa-fire"></i> New
                            </div>
                        <?php endif; ?>
                        <div class="position-relative">
                            <img src="<?php echo htmlspecialchars($image_src); ?>"
                                 class="card-img-top"
                                 alt="<?php echo $title; ?> Cover"
                                 onerror="this.src='Uploads/placeholder.jpg'">
                            <div class="position-absolute bottom-0 end-0 m-2">
                                <span class="badge <?php echo $is_borrowed ? 'bg-secondary' : 'bg-success'; ?>">
                                    <?php echo $is_borrowed ? 'Checked Out' : 'Available'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo $title; ?></h5>
                            <p class="card-text text-muted mb-2">
                                <i class="fas fa-user-edit me-1"></i><?php echo $author; ?>
                            </p>
                            <div class="small text-muted mt-auto mb-2">
                                <div><i class="fas fa-barcode me-1"></i> ISBN: <?php echo $isbn; ?></div>
                                <div><i class="fas fa-calendar-alt me-1"></i> <?php echo $published_year; ?></div>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0 pt-0">
                            <div class="d-flex justify-content-between">
                                <?php if ($is_borrowed): ?>
                                    <a href="reserve.php?id=<?php echo $book_id; ?>" class="btn btn-waitlist">
                                        <i class="fas fa-clock me-1"></i> Waitlist
                                    </a>
                                <?php else: ?>
                                    <a href="borrow.php?id=<?php echo $book_id; ?>" class="btn btn-borrow">
                                        <i class="fas fa-hand-holding me-1"></i> Borrow
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                <div class="col-12 text-center">
                    <i class="fas fa-book-open no-books-icon"></i>
                    <h4 class="text-muted">No books found matching your criteria</h4>
                    <a href="dashboard.php" class="btn btn-primary mt-3">View All Books</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center mt-5">
                <nav aria-label="page-navigation" class="page-pagination">
                    <ul class="pagination">
                        <!-- Previous page button -->
                        <li class="page-item prev-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo ($page > 1) ? add_query_params(['page' => $page - 1]) : '#'; ?>" aria-label="Previous">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>

                        <?php
                        // Determine which page numbers to show
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        // Always show first page
                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo add_query_params(['page' => 1]); ?>">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled ellipsis">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Page numbers -->
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo add_query_params(['page' => $i]); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Always show last page -->
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled ellipsis">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo add_query_params(['page' => $total_pages]); ?>"><?php echo $total_pages; ?></a>
                            </li>
                        <?php endif; ?>

                        <!-- Next page button -->
                        <li class="page-item next-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo ($page < $total_pages) ? add_query_params(['page' => $page + 1]) : '#'; ?>" aria-label="Next">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- My Borrowed Books Section -->
<div class="card section-card shadow-sm slide-up">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h3 class="mb-0"><i class="fas fa-book-reader me-2"></i> My Borrowed Books</h3>
        <?php if ($loans_result->num_rows > 0): ?>
            <span class="badge bg-light text-dark"><?php echo $loans_result->num_rows; ?> books</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($loans_result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover table-loans">
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Borrowed On</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($loan = $loans_result->fetch_assoc()):
                            // Calculate days overdue
                            $today = new DateTime();
                            $due_date = new DateTime($loan['due_date']);
                            $days_overdue = ($today > $due_date && $loan['return_date'] === NULL) ? $today->diff($due_date)->days : 0;

                            // Calculate fine based on days overdue
                            $fine = 0;
                            if ($days_overdue > 0) {
                                $fine = $days_overdue * 1000; // 1000 IDR per day
                            }
                            
                            // Get cover image with proper fallback
                            $image_src = !empty($loan['cover_image']) && file_exists($loan['cover_image']) 
                                ? $loan['cover_image'] 
                                : 'Uploads/placeholder.jpg';
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($image_src); ?>"
                                            class="loan-book-cover me-3"
                                            alt="<?php echo htmlspecialchars($loan['title']); ?> Cover"
                                            onerror="this.src='Uploads/placeholder.jpg'">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($loan['title']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($loan['author']); ?></small>
                                            <small class="d-block text-muted"><i class="fas fa-barcode me-1"></i> <?php echo htmlspecialchars($loan['isbn']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted"><i class="far fa-calendar-alt me-1"></i><?php echo date('d M Y', strtotime($loan['loan_date'])); ?></span>
                                </td>
                                <td>
                                    <span class="<?php echo ($days_overdue > 0 && $loan['return_date'] === NULL) ? 'text-danger fw-bold' : 'text-muted'; ?>">
                                        <i class="far fa-calendar-check me-1"></i><?php echo date('d M Y', strtotime($loan['due_date'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($loan['return_date'] === NULL): ?>
                                        <?php if ($days_overdue > 0): ?>
                                            <div>
                                                <span class="loan-status-badge bg-danger text-white">
                                                    <i class="fas fa-exclamation-circle me-1"></i>
                                                    Overdue (<?php echo $days_overdue; ?> days)
                                                </span>
                                            </div>
                                            <div class="mt-1">
                                                <span class="loan-status-badge bg-warning text-dark">
                                                    <i class="fas fa-coins me-1"></i>
                                                    Fine: Rp <?php echo number_format($fine, 0, ',', '.'); ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <span class="loan-status-badge bg-primary text-white">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Current
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="loan-status-badge bg-success text-white">
                                            <i class="fas fa-check-double me-1"></i>
                                            Returned
                                            <div class="mt-1 text-muted small">
                                                <?php echo date('d M Y', strtotime($loan['return_date'])); ?>
                                            </div>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($loan['return_date'] === NULL): ?>
                                        <div class="btn-group">
                                            <a href="return.php?id=<?php echo $loan['loan_id']; ?>" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-undo me-1"></i> Return
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="animate__animated animate__fadeIn">
                    <i class="fas fa-book-reader fa-4x mb-3 text-muted"></i>
                    <h4 class="text-muted mb-3">You don't have any books borrowed yet</h4>
                    <p class="text-muted mb-4">Browse our collection and borrow books that interest you</p>
                    <a href="#" class="btn btn-primary px-4 py-2">
                        <i class="fas fa-search me-2"></i> Browse Books
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>
</div>



<!-- Bootstrap JS and additional scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add fade-in effect to search results
        const bookCards = document.querySelectorAll('.book-card');
        bookCards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('fade-in');
            }, index * 100);
        });

        // Show tooltip for book titles that might be truncated
        const cardTitles = document.querySelectorAll('.card-title');
        cardTitles.forEach(title => {
            if (title.scrollWidth > title.clientWidth) {
                // Title is truncated, add tooltip
                title.setAttribute('title', title.textContent);
            }
        });
    });
</script>

<script>
    // Script untuk lazy loading gambar
    document.addEventListener("DOMContentLoaded", function() {
        let lazyImages = document.querySelectorAll("img.lazy-load");

        if ("IntersectionObserver" in window) {
            let imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        let image = entry.target;
                        if (image.dataset.src) {
                            let tmpImg = new Image();
                            tmpImg.src = image.dataset.src;
                            tmpImg.onload = function() {
                                image.src = image.dataset.src;
                            }
                            tmpImg.onerror = function() {
                                // Jika gambar error, gunakan placeholder
                                // Placeholder sudah ditetapkan sebagai src awal
                            }

                            delete image.dataset.src;
                        }
                        imageObserver.unobserve(image);
                    }
                });
            });

            lazyImages.forEach(function(image) {
                imageObserver.observe(image);
            });
        } else {
            // Fallback untuk browser yang tidak mendukung IntersectionObserver
            let lazyLoadThrottleTimeout;

            function lazyLoad() {
                if (lazyLoadThrottleTimeout) {
                    clearTimeout(lazyLoadThrottleTimeout);
                }

                lazyLoadThrottleTimeout = setTimeout(function() {
                    let scrollTop = window.pageYOffset;
                    lazyImages.forEach(function(img) {
                        if (img.offsetTop < (window.innerHeight + scrollTop)) {
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                delete img.dataset.src;
                            }
                        }
                    });
                    if (lazyImages.length == 0) {
                        document.removeEventListener("scroll", lazyLoad);
                        window.removeEventListener("resize", lazyLoad);
                        window.removeEventListener("orientationChange", lazyLoad);
                    }
                }, 20);
            }

            document.addEventListener("scroll", lazyLoad);
            window.addEventListener("resize", lazyLoad);
            window.addEventListener("orientationChange", lazyLoad);
            lazyLoad();
        }
    });
</script>

<?php
// Close database connection
$conn->close();

include('includes/footer.php');
?>