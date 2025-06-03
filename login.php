<?php
session_start();
// Include your database connection file
// Make sure koneksi.php establishes a $conn variable for MySQLi connection
include "config/koneksi.php";

// Sanitize user input to prevent SQL injection
$user = mysqli_real_escape_string($conn, $_POST['username']);
$pass = md5($_POST['password']); // MD5 is not recommended for password hashing, but kept for consistency with your existing code.
                                 // For new projects, use password_hash() and password_verify().

// Prepare and execute the query using MySQLi
$login_query = mysqli_query($conn, "SELECT * FROM admin WHERE username='$user' AND password='$pass'");

// Check if a row was found
if ($login_query) { // Check if the query itself was successful
    $ketemu = mysqli_num_rows($login_query);
    $r = mysqli_fetch_array($login_query);

    if ($ketemu > 0) {
        // Login successful
        $_SESSION['username'] = $r['username'];
        $_SESSION['password'] = $r['password']; // Storing password in session is generally not recommended
        $_SESSION['nama_lengkap'] = $r['nama_lengkap'];
        header("location: index.php");
        exit(); // Always exit after a header redirect
    } else {
        // Login failed - username or password incorrect
        displayLoginFailedMessage();
    }
} else {
    // Query failed (e.g., database error)
    // You might want to log this error for debugging
    error_log("Login query failed: " . mysqli_error($conn));
    displayLoginFailedMessage(); // Show generic error to user
}

function displayLoginFailedMessage() {
    echo " <title>Login Gagal ! - Chirexs 1.0</title>";
    echo "<link href='css/font-awesome-4.2.0/font-awesome-4.2.0/css/font-awesome.min.css' rel='stylesheet'>
          <link rel='stylesheet' href='animasi/login/ayam.css'>
          <link rel='stylesheet' href='aset/cinta.css'>
          <link href='css/main.css' rel='stylesheet' type='text/css' media='all'/>
          <link rel='stylesheet' href='aset/bootstrap.css'>
          <div class='errorpage'> <center><div class='danger'><i class='fa fa-exclamation-triangle'></i></div><br><h1>LOGIN GAGAL!</h1>
          Username dan Password anda salah.<br><br><input name='submit' id='submitku' type=submit style='padding: 6px 12px;' value='ULANGI LAGI' onclick=location.href='formlogin'></a><br><p class='message'>Masih bingung, Kembali ke <a href='bantuan'>Halaman Bantuan</a></p></center></div>
          <div class='chick-wrapper-landing show'>
            <div class='wing-back'></div>
            <div class='body'>
              <div class='eye-left'></div>
              <div class='eye-right'></div>
            </div>
            <div class='wing-front'></div>
          </div>
          <div class='chick-wrapper-run run'><img class='egg-lay' src='animasi/login/lay_egg.png'/>
            <div class='legs'>
              <div class='leg-l'></div>
              <div class='leg-r'></div>
            </div>
            <div class='wing-back'> </div>
            <div class='sweat-1'></div>
            <div class='sweat-2'></div>
            <div class='sweat-3'></div>
            <div class='body'>
              <div class='eye-liner'>
                <div class='eye'></div>
              </div>
              <div class='eye-lid'></div>
              <div class='cheek'></div>
            </div>
            <div class='sweat-last'></div>
            <div class='wing-front'></div>
          </div>
          <script src='animasi/login/index.js'></script>";
}
?>