<?php
session_start(); // Panggil sekali di atas

// Cek otentikasi sesi
if (!(isset($_SESSION['username']) && isset($_SESSION['password']))) {
    header('location:../../index.php'); // Asumsi index.php ada dua level di atas
    exit();
}

include "../../config/koneksi.php"; // Pastikan ini menyediakan $conn (koneksi MySQLi)
include "../../config/fungsi_alert.php"; // Untuk menampilkan pesan sukses/error

// Pastikan variabel $conn (koneksi MySQLi) ada dan valid
if (!$conn || !($conn instanceof mysqli)) {
    error_log("Koneksi database MySQLi tidak valid atau gagal dimuat di aksi_gejala.php.");
    if (function_exists('set_alert')) {
        set_alert('danger', 'Terjadi kesalahan koneksi ke database. Silakan hubungi administrator.');
    }
    header('location:../../index.php?module=gejala');
    exit();
}

$module = isset($_GET['module']) ? $_GET['module'] : '';
$act = isset($_GET['act']) ? $_GET['act'] : '';

// Hapus gejala
if ($module == 'gejala' && $act == 'hapus') {
    if (isset($_GET['id'])) {
        $kode_gejala = $_GET['id'];
        
        $stmt = $conn->prepare("DELETE FROM gejala WHERE kode_gejala = ?");
        if ($stmt) {
            $stmt->bind_param("s", $kode_gejala); // Asumsi kode_gejala bisa jadi string (misal G001)
            if ($stmt->execute()) {
                set_alert('success', 'Data gejala berhasil dihapus.');
            } else {
                error_log("Gagal menghapus gejala: " . $stmt->error);
                set_alert('danger', 'Gagal menghapus data gejala. Mungkin gejala ini masih digunakan di tabel pengetahuan.');
            }
            $stmt->close();
        } else {
            error_log("Gagal mempersiapkan statement hapus gejala: " . $conn->error);
            set_alert('danger', 'Terjadi kesalahan sistem saat akan menghapus gejala.');
        }
    } else {
        set_alert('warning', 'Kode gejala tidak ditemukan untuk dihapus.');
    }
    header('location:../../index.php?module=' . $module);
    exit();
}

// Input gejala
elseif ($module == 'gejala' && $act == 'input') {
    if (isset($_POST['nama_gejala']) && !empty(trim($_POST['nama_gejala']))) {
        $nama_gejala = trim($_POST['nama_gejala']);

        $stmt = $conn->prepare("INSERT INTO gejala (nama_gejala) VALUES (?)");
        if ($stmt) {
            $stmt->bind_param("s", $nama_gejala);
            if ($stmt->execute()) {
                set_alert('success', 'Gejala baru berhasil ditambahkan.');
            } else {
                error_log("Gagal input gejala: " . $stmt->error);
                set_alert('danger', 'Gagal menambahkan gejala baru. Mungkin ada kesalahan input atau data duplikat.');
            }
            $stmt->close();
        } else {
            error_log("Gagal mempersiapkan statement input gejala: " . $conn->error);
            set_alert('danger', 'Terjadi kesalahan sistem saat akan menambahkan gejala.');
        }
    } else {
        set_alert('warning', 'Nama gejala tidak boleh kosong.');
    }
    header('location:../../index.php?module=' . $module);
    exit();
}

// Update gejala
elseif ($module == 'gejala' && $act == 'update') {
    if (isset($_POST['id'], $_POST['nama_gejala']) && !empty(trim($_POST['nama_gejala']))) {
        $kode_gejala_update = $_POST['id']; // Ini adalah kode_gejala yang akan di-update (dari field hidden)
        $nama_gejala_baru = trim($_POST['nama_gejala']);
        
        $stmt = $conn->prepare("UPDATE gejala SET nama_gejala = ? WHERE kode_gejala = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $nama_gejala_baru, $kode_gejala_update);
            if ($stmt->execute()) {
                set_alert('success', 'Data gejala berhasil diperbarui.');
            } else {
                error_log("Gagal update gejala: " . $stmt->error);
                set_alert('danger', 'Gagal memperbarui data gejala.');
            }
            $stmt->close();
        } else {
            error_log("Gagal mempersiapkan statement update gejala: " . $conn->error);
            set_alert('danger', 'Terjadi kesalahan sistem saat akan memperbarui gejala.');
        }
    } else {
        set_alert('warning', 'Data update gejala tidak lengkap atau nama gejala kosong.');
    }
    header('location:../../index.php?module=' . $module);
    exit();
}

// Jika tidak ada $act yang cocok
set_alert('warning', 'Tindakan tidak valid untuk modul gejala.');
header('location:../../index.php?module=' . $module);
exit();

?>