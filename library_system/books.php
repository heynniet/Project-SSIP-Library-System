<?php
/* books.php - Display and browse books with search, sort, and cart functionality */
require_once 'config/config.php';
session_start();

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Pagination setup
$books_per_page = 12;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $books_per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$base_query = "SELECT b.* FROM books b WHERE 1=1";
$base_count_query = "SELECT COUNT(*) as total FROM books b WHERE 1=1";
$params = [];

if (!empty($search)) {
    $base_query .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
    $base_count_query .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

// Sorting
$valid_sorts = ['title_asc', 'title_desc', 'author_asc', 'author_desc', 'newest', 'oldest'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $valid_sorts) ? $_GET['sort'] : 'title_asc';
switch ($sort) {
    case 'title_desc':
        $base_query .= " ORDER BY b.title DESC";
        break;
    case 'author_asc':
        $base_query .= " ORDER BY b.author ASC";
        break;
    case 'author_desc':
        $base_query .= " ORDER BY b.author DESC";
        break;
    case 'newest':
        $base_query .= " ORDER BY b.published_year DESC";
        break;
    case 'oldest':
        $base_query .= " ORDER BY b.published_year ASC";
        break;
    default:
        $base_query .= " ORDER BY b.title ASC";
}

// Add pagination
$query = $base_query . " LIMIT ? OFFSET ?";
$count_query = $base_count_query;
$params[] = $books_per_page;
$params[] = $offset;

// Fetch total books
try {
    $stmt = $conn->prepare($count_query);
    $stmt->execute(array_slice($params, 0, count($params) - 2));
    $total_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_books = $total_result['total'] ?? 0;
    $total_pages = max(1, ceil($total_books / $books_per_page));

    // Validate page number
    if ($page > $total_pages) {
        $page = $total_pages;
        $offset = ($page - 1) * $books_per_page;
        $params[count($params) - 1] = $offset;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    $total_books = 0;
    $total_pages = 1;
}

// Fetch books for current page
$books = [];
try {
    $stmt = $conn->prepare($query);
    for ($i = 0; $i < count($params) - 2; $i++) {
        $stmt->bindValue($i + 1, $params[$i], PDO::PARAM_STR);
    }
    $stmt->bindValue(count($params) - 1, $books_per_page, PDO::PARAM_INT);
    $stmt->bindValue(count($params), $offset, PDO::PARAM_INT);
    $stmt->execute();
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

// Handle add to cart action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart']) && isset($_SESSION['user_id'])) {
    $book_id = (int)$_POST['book_id'];

    try {
        if (!in_array($book_id, $_SESSION['cart'])) {
            // Check book existence
            $book_check_query = "SELECT 1 FROM books WHERE book_id = ?";
            $stmt = $conn->prepare($book_check_query);
            $stmt->bindValue(1, $book_id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                $_SESSION['error'] = "Book not found.";
                header("Location: books.php?" . http_build_query($_GET));
                exit;
            }

            // Check loan limits
            $check_limit_query = "SELECT COUNT(*) as active_loans FROM loans 
                                 WHERE user_id = ? AND return_date IS NULL";
            $check_stmt = $conn->prepare($check_limit_query);
            $check_stmt->bindValue(1, (int)$_SESSION['user_id'], PDO::PARAM_INT);
            $check_stmt->execute();
            $limit_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $current_loans = $limit_result['active_loans'];
            $cart_count = count($_SESSION['cart']);

            if (($current_loans + $cart_count + 1) <= 5) {
                $_SESSION['cart'][] = $book_id;
                $_SESSION['success'] = "Book added to your borrowing cart.";
            } else {
                $_SESSION['error'] = "You can only borrow up to 5 books at a time.";
            }
        } else {
            $_SESSION['info'] = "This book is already in your cart.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }

    header("Location: books.php?" . http_build_query($_GET));
    exit;
}

include('includes/header.php');
?>

<div class="books-container">
    <h2><i class="fas fa-book"></i> Browse Books</h2>

    <!-- Display alerts -->
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

    <!-- Search and Filter Bar -->
    <div class="search-filter-container">
        <form method="GET" class="search-form">
            <div class="search-box">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title, author or ISBN...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </div>

            <div class="filters">
                <select name="sort">
                    <option value="title_asc" <?php echo ($sort == 'title_asc') ? 'selected' : ''; ?>>Title (A-Z)</option>
                    <option value="title_desc" <?php echo ($sort == 'title_desc') ? 'selected' : ''; ?>>Title (Z-A)</option>
                    <option value="author_asc" <?php echo ($sort == 'author_asc') ? 'selected' : ''; ?>>Author (A-Z)</option>
                    <option value="author_desc" <?php echo ($sort == 'author_desc') ? 'selected' : ''; ?>>Author (Z-A)</option>
                    <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Newest</option>
                    <option value="oldest" <?php echo ($sort == 'oldest') ? 'selected' : ''; ?>>Oldest</option>
                </select>

                <button type="submit" class="apply-filters">Apply Filters</button>
                <a href="books.php" class="reset-filters">Reset</a>
            </div>
        </form>
    </div>

    <!-- Display search results or all books -->
    <div class="results-info">
        <p>Showing <?php echo count($books); ?> of <?php echo $total_books; ?> books</p>
    </div>

    <!-- Books Grid -->
    <div class="books-grid">
        <?php if (empty($books)): ?>
            <div class="no-results">
                <i class="fas fa-exclamation-circle"></i>
                <p>No books found matching your criteria.</p>
                <a href="books.php" class="btn">View All Books</a>
            </div>
        <?php else: ?>
            <?php foreach ($books as $book): ?>
                <div class="book-card">
                    <div class="book-cover">
                        <?php
                        $image_src = !empty($book['cover_image']) && file_exists($book['cover_image'])
                            ? $book['cover_image']
                            : 'Uploads/placeholder.jpg';
                        ?>
                        <img src="<?php echo htmlspecialchars($image_src); ?>"
                             alt="<?php echo htmlspecialchars($book['title']); ?> cover"
                             onerror="this.src='Uploads/placeholder.jpg'">
                    </div>
                    <div class="book-info">
                        <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                        <p class="author">by <?php echo htmlspecialchars($book['author']); ?></p>
                        <p class="year"><?php echo htmlspecialchars($book['published_year']); ?></p>
                        <div class="book-actions">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <form method="POST">
                                    <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                    <button type="submit" name="add_to_cart" class="add-to-cart">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="prev-page">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>

            <div class="page-numbers">
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current-page"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>

            <?php if ($page < $total_pages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="next-page">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .books-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .search-filter-container {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .search-form {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .search-box {
        margin-left: 15px;
        display: flex;
        width: 95%;
    }

    .search-box input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-right: none;
        border-radius: 4px 0 0 4px;
    }

    .search-box button {
        padding: 10px 15px;
        background-color: #0066cc;
        color: white;
        border: none;
        border-radius: 0 4px 4px 0;
        cursor: pointer;
    }

    .filters {
        margin-left: 15px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }

    .filters select {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: white;
    }

    .apply-filters {
        padding: 8px 15px;
        background-color: #0066cc;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .reset-filters {
        padding: 8px 15px;
        background-color: #f8f9fa;
        color: #333;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-decoration: none;
    }

    .results-info {
        margin-bottom: 15px;
        color: #666;
    }

    .books-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }

    .book-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .book-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .book-cover {
        height: 200px;
        overflow: hidden;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #f5f5f5;
    }

    .book-cover img {
        max-width: 100%;
        max-height: 100%;
        object-fit: cover;
    }

    .book-info {
        padding: 15px;
    }

    .book-info h3 {
        margin: 0 0 5px 0;
        font-size: 1rem;
        line-height: 1.3;
        height: 2.6rem;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .author {
        font-style: italic;
        margin: 0 0 5px 0;
        color: #666;
        font-size: 0.9rem;
    }

    .year {
        margin: 3px 0;
        font-size: 0.8rem;
        color: #888;
    }

    .book-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 10px;
    }

    .add-to-cart {
        padding: 8px;
        background-color: #0066cc;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 5px;
    }

    .no-results {
        grid-column: 1 / -1;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 50px 20px;
        text-align: center;
    }

    .no-results i {
        font-size: 3rem;
        color: #ccc;
        margin-bottom: 20px;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 30px;
        gap: 10px;
    }

    .page-numbers {
        display: flex;
        gap: 5px;
    }

    .page-numbers a,
    .prev-page,
    .next-page {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-decoration: none;
        color: #333;
    }

    .current-page {
        padding: 8px 12px;
        background-color: #0066cc;
        color: white;
        border-radius: 4px;
    }

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

    .alert-info {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    @media (max-width: 768px) {
        .books-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }

        .filters {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const closeButton = alert.querySelector('.btn-close');
                if (closeButton) closeButton.click();
            }, 5000);
        });
    });
</script>

<?php include('includes/footer.php'); ?>