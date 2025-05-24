<?php
// profile.php
session_start(); // Make sure session is started
require_once 'config/config.php'; // Include database connection
require_once 'includes/auth_functions.php'; // Include auth functions

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user = get_user_by_id($conn, $user_id);

$error = "";
$success = "";

// Process profile update form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["update_profile"])) {
        // Validate email
        if (empty($_POST["email"])) {
            $error = "Email is required";
        } else {
            $email = clean_input($_POST["email"]);
            if (!validate_email($email)) {
                $error = "Invalid email format";
            }
        }

        // Validate full name (changed to name to match your database)
        if (empty($_POST["name"])) {
            $error = "Name is required";
        } else {
            $name = clean_input($_POST["name"]);
        }

        // Validate phone (added to match your database)
        $phone = !empty($_POST["phone"]) ? clean_input($_POST["phone"]) : null;

        // Update profile if no errors
        if (empty($error)) {
            // Adapt this function call to match your database schema
            $result = update_member_profile($conn, $user_id, $name, $email, $phone);

            if (isset($result["success"])) {
                $success = "Profile updated successfully";
                // Refresh user data
                $user = get_user_by_id($conn, $user_id);
            } else {
                $error = $result["error"];
            }
        }
    } elseif (isset($_POST["change_password"])) {
        // Validate current password
        if (empty($_POST["current_password"])) {
            $error = "Current password is required";
        }

        // Validate new password
        if (empty($_POST["new_password"])) {
            $error = "New password is required";
        } elseif (strlen($_POST["new_password"]) < 8) {
            $error = "New password must be at least 8 characters long";
        }

        // Validate password confirmation
        if (empty($_POST["confirm_password"])) {
            $error = "Please confirm your new password";
        } elseif ($_POST["new_password"] != $_POST["confirm_password"]) {
            $error = "New passwords do not match";
        }

        // Change password if no errors
        if (empty($error)) {
            $result = change_member_password($conn, $user_id, $_POST["current_password"], $_POST["new_password"]);

            if (isset($result["success"])) {
                $success = "Password changed successfully";
            } else {
                $error = $result["error"];
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container mt-4">
        <h2>My Profile</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Profile Information</h5>
                    </div>
                    <div class="card-body text-center">
                        <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                        <p>
                            <i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($user['email']); ?>
                        </p>
                        <?php if (!empty($user['phone'])): ?>
                            <p>
                                <i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($user['phone']); ?>
                            </p>
                        <?php endif; ?>
                        <p>
                            <i class="fas fa-calendar me-2"></i> Joined: <?php echo date('M d, Y', strtotime($users['join_date'])); ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Edit Profile</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                <div class="form-text">Optional: Enter your phone number for contact purposes</div>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="form-text">Password must be at least 8 characters long.</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

<? include('includes/footer.php'); ?>

</html>