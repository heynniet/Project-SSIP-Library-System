<?php
/* login.php - User login page */
session_start();
include 'config/config.php';

$login_error = "";

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare SQL statement to prevent SQL injection
    $sql = "SELECT user_id, name, email, role FROM users WHERE email = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email, $password]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Set session variables
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['name'] = $row['name'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['role'] = $row['role']; // Store the role in session

        // For compatibility with existing code
        if ($row['role'] == 'admin') {
            $_SESSION['is_admin'] = true;
        } else {
            $_SESSION['is_admin'] = false;
        }

        // Redirect to dashboard
        header("Location: dashboard.php");
        exit;
    } else {
        $login_error = "Invalid email or password";
    }
}

// Remove the duplicated DOCTYPE, head section will come from header include
include('includes/header.php');
?>

<main>
    <div class="login-container">
        <h2><i class="fas fa-sign-in-alt"></i> Login</h2>

        <?php if ($login_error != ""): ?>
            <div class="error"><?php echo $login_error; ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" name="login" class="btn"><i class="fas fa-lock-open"></i> Login</button>

            <div class="register-link">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </form>
    </div>
</main>

<style>
    .login-container {
        max-width: 400px;
        margin: 20px auto;
        background: white;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .form-group {
        margin-bottom: 15px;
    }

    label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    input[type="email"],
    input[type="password"] {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }

    .btn {
        background-color: #4CAF50;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        width: 100%;
    }

    .btn:hover {
        background-color: #45a049;
    }

    .error {
        color: red;
        margin-bottom: 15px;
        padding: 10px;
        background-color: #ffecec;
        border-left: 4px solid #ff5252;
    }

    .register-link {
        text-align: center;
        margin-top: 15px;
    }
</style>

<?php include('includes/footer.php'); ?>