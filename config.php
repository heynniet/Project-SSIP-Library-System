<?php
/* db_config.php - Database connection configuration */
$host = "localhost";
$username = "root";
$password = "";
$dbname = "library_db";
try {
// Create PDO connection
 $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
// Set the PDO error mode to exception
 $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// Set default fetch mode to associative array
 $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
die("Connection failed: " . $e->getMessage());
}