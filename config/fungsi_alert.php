<?php
// File: config/fungsi_alert.php

// Pastikan session sudah dimulai di file utama (index.php)
// Jika belum, Anda bisa tambahkan if (!isset($_SESSION)) { session_start(); } di sini,
// tapi lebih baik session_start() hanya ada sekali di index.php dan sudah Anda lakukan.

function set_alert($type, $message) {
    if (session_status() == PHP_SESSION_NONE) { // Mulai session jika belum aktif
        session_start();
    }
    $_SESSION['alert'] = array('type' => $type, 'message' => $message);
}

function display_alert() {
    if (session_status() == PHP_SESSION_NONE) { // Mulai session jika belum aktif
        session_start();
    }
    if (isset($_SESSION['alert'])) {
        $alert_type = $_SESSION['alert']['type'];
        // Sesuaikan class alert dengan framework CSS Anda (misal Bootstrap)
        $bootstrap_alert_class = 'alert-info'; // Default
        if ($alert_type == 'success') $bootstrap_alert_class = 'alert-success';
        if ($alert_type == 'danger' || $alert_type == 'error') $bootstrap_alert_class = 'alert-danger';
        if ($alert_type == 'warning') $bootstrap_alert_class = 'alert-warning';

        // Anda mungkin ingin menambahkan ini di lokasi yang tepat di layout HTML Anda
        // misalnya di index.php sebelum include content.php
        echo '<div class="alert ' . $bootstrap_alert_class . ' alert-dismissible" role="alert" style="margin:15px;">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                ' . htmlspecialchars($_SESSION['alert']['message']) . '
              </div>';
        unset($_SESSION['alert']); // Hapus alert setelah ditampilkan
    }
}
?>