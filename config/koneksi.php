<?php
// config/koneksi.php

// Your database connection details
$server = "localhost";
$username = "root";
$password = ""; // Make sure this is correct for your XAMPP MySQL setup
$database = "sistem_pakar"; // Make sure this is your actual database name

// Establish the MySQLi connection
// The connection object will be stored in the $conn variable
$conn = mysqli_connect($server, $username, $password, $database);

// Check if the connection was successful
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// You might also want to set the character set for proper character encoding
// mysqli_set_charset($conn, "utf8"); // Uncomment and use if needed

// IMPORTANT: Do NOT close the connection here (e.g., mysqli_close($conn);)
// It will be implicitly closed at the end of the script execution or when no longer needed.
?>