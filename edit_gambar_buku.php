<?php
// Include PDO connection
require_once 'config/config.php';

// Ambil daftar buku untuk dropdown
$books_query = "SELECT book_id, title FROM books ORDER BY title";
$stmt = $conn->prepare($books_query);
$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Proses form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $book_id = $_POST['book_id'];
    
    // Validasi book_id
    $check_query = "SELECT book_id FROM books WHERE book_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$book_id]);
    
    if ($check_stmt->rowCount() == 0) {
        echo "<p>Buku tidak ditemukan.</p>";
        exit;
    }
    
    // Proses upload gambar
    $target_dir = "Uploads/";
    // Pastikan folder uploads ada
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $original_filename = basename($_FILES["gambar"]["name"]);
    $imageFileType = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
    // Buat nama file unik untuk menghindari overwrite
    $new_filename = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    
    // Validasi file
    if (!in_array($imageFileType, $allowed_types)) {
        echo "<p>Format file tidak diizinkan. Gunakan JPG, JPEG, PNG, atau GIF.</p>";
        exit;
    }
    
    if ($_FILES["gambar"]["size"] > 5000000) { // Batas 5MB
        echo "<p>Ukuran file terlalu besar. Maksimum 5MB.</p>";
        exit;
    }
    
    if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
        // Update cover_image di database
        $query = "UPDATE books SET cover_image = ? WHERE book_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$target_file, $book_id]);
        
        if ($stmt->rowCount() > 0) {
            echo "<p>Gambar untuk buku berhasil diperbarui! Path: $target_file</p>";
        } else {
            echo "<p>Gagal memperbarui gambar.</p>";
        }
    } else {
        echo "<p>Gagal mengupload gambar. Error: " . $_FILES["gambar"]["error"] . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Gambar Buku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Edit Gambar Buku</h2>
        <form method="post" enctype="multipart/form-data" class="mt-3">
            <div class="mb-3">
                <label for="book_id" class="form-label">Pilih Buku</label>
                <select name="book_id" id="book_id" class="form-select" required>
                    <option value="">-- Pilih Buku --</option>
                    <?php foreach ($books as $book): ?>
                        <option value="<?php echo $book['book_id']; ?>">
                            <?php echo htmlspecialchars($book['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="gambar" class="form-label">Upload Gambar</label>
                <input type="file" name="gambar" id="gambar" class="form-control" accept="image/*" required>
                <div class="form-text">Format: JPG, JPEG, PNG, GIF. Maksimum 5MB.</div>
            </div>
            
            <button type="submit" class="btn btn-primary">Simpan Gambar</button>
            <a href="books.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// PDO connection is closed automatically when script ends
?>