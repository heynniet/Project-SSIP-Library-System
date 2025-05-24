<?php

/**
 * Global functions for the Library Management System
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the current user is an admin
 * 
 * @return boolean True if the user is an admin, false otherwise
 */
function is_admin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

/**
 * Check if user is logged in
 * 
 * @return boolean True if the user is logged in, false otherwise
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

/**
 * Format a date in a readable format
 * 
 * @param string $date Date in Y-m-d format
 * @return string Formatted date
 */
function format_date($date)
{
    $timestamp = strtotime($date);
    return date('F j, Y', $timestamp);
}

/**
 * Sanitize user input
 * 
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
