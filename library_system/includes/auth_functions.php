<?php
// includes/auth_functions.php

// Check if user is logged in
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function is_admin()
{
    return (isset($_SESSION['role']) && $_SESSION['role'] == 'admin');
}

// Get user by ID - Using PDO
function get_user_by_id($conn, $user_id)
{
    $sql = "SELECT * FROM members WHERE member_id = :member_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':member_id', $user_id, PDO::PARAM_INT);

    if ($stmt->rowCount() == 1) {
        return $stmt->fetch();
    } else {
        return null;
    }
}

// Upload file to server
function upload_file($file)
{
    // Create uploads directory if it doesn't exist
    $upload_dir = "uploads/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate unique filename
    $filename = uniqid() . '_' . basename($file["name"]);
    $target_file = $upload_dir . $filename;

    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ["success" => true, "path" => $target_file];
    } else {
        return ["error" => "Failed to upload file."];
    }
}

// Validate email format
function validate_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Clean user input
function clean_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Update member profile - Using PDO
function update_member_profile($conn, $member_id, $name, $email, $phone = null, $profile_image = null)
{
    // Check if email already exists for a different user
    $check_sql = "SELECT member_id FROM members WHERE email = :email AND member_id != :member_id";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $check_stmt->bindParam(':member_id', $member_id, PDO::PARAM_INT);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        return ["error" => "Email already in use by another member."];
    }

    // Start with basic fields
    $sql = "UPDATE members SET name = :name, email = :email";
    $params = [
        ':name' => $name,
        ':email' => $email,
        ':member_id' => $member_id
    ];

    // Add phone if provided
    if ($phone !== null) {
        $sql .= ", phone = :phone";
        $params[':phone'] = $phone;
    }

    // Add profile image if provided
    if ($profile_image !== null) {
        $sql .= ", profile_image = :profile_image";
        $params[':profile_image'] = $profile_image;
    }

    $sql .= " WHERE member_id = :member_id";

    $stmt = $conn->prepare($sql);

    try {
        $stmt->execute($params);
        return ["success" => true];
    } catch (PDOException $e) {
        return ["error" => "Failed to update profile. Please try again. " . $e->getMessage()];
    }
}

// Change password - Using PDO
function change_member_password($conn, $member_id, $current_password, $new_password)
{
    // Get current user information
    $sql = "SELECT * FROM members WHERE member_id = :member_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':member_id', $member_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch();

        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password
            $update_sql = "UPDATE members SET password = :password WHERE member_id = :member_id";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
            $update_stmt->bindParam(':member_id', $member_id, PDO::PARAM_INT);

            try {
                $update_stmt->execute();
                return ["success" => true];
            } catch (PDOException $e) {
                return ["error" => "Failed to change password. Please try again. " . $e->getMessage()];
            }
        } else {
            return ["error" => "Current password is incorrect."];
        }
    } else {
        return ["error" => "User not found."];
    }
}
