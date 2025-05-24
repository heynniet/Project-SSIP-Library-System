<?php
/* register.php - User registration page */
session_start();
include 'config/config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$registration_error = "";
$registration_success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? null;
    $password = $_POST['password'];
    $join_date = date('Y-m-d'); // Current date
    $role = 'user';  // Default role is 'user'

    // Check if email already exists
    $check_sql = "SELECT * FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$email]);
    $result = $check_stmt->fetchAll();

    if (count($result) > 0) {
        $registration_error = "Email already registered. Please use a different email.";
    } else {
        // Insert new user with role
        $sql = "INSERT INTO users (name, email, phone, password, join_date, role) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt->execute([$name, $email, $phone, $password, $join_date, $role])) {
            $registration_success = "Registration successful! Please login.";
            // Redirect to login page after 2 seconds
            header("refresh:2; url=login.php");
        } else {
            $registration_error = "Error: Registration failed";
        }
    }
}

// Remove duplicated DOCTYPE and head section
include('includes/header.php');
?>

<main>
    <div class="register-container">
        <h2><i class="fas fa-user-plus"></i> Register</h2>

        <?php if ($registration_error != ""): ?>
            <div class="error"><?php echo $registration_error; ?></div>
        <?php endif; ?>

        <?php if ($registration_success != ""): ?>
            <div class="success"><?php echo $registration_success; ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone (optional):</label>
                <input type="tel" id="phone" name="phone">
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>

            <button type="submit" name="register" class="btn"><i class="fas fa-sign-in-alt"></i> Register</button>

            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </form>
    </div>
</main>

<style>
    .register-container {
        max-width: 500px;
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

    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="tel"] {
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

    .success {
        color: green;
        margin-bottom: 15px;
        padding: 10px;
        background-color: #e7f7e7;
        border-left: 4px solid #4CAF50;
    }

    .login-link {
        text-align: center;
        margin-top: 15px;
    }
</style>

<?php include('includes/footer.php'); ?>