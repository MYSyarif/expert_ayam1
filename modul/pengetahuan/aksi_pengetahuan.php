<?php
session_start(); // Panggil sekali di atas

// Cek otentikasi sesi
if (!(isset($_SESSION['username']) && isset($_SESSION['password']))) {
    header('location:../../index.php');
    exit();
}

include "../../config/koneksi.php"; // Pastikan ini menyediakan $conn (koneksi MySQLi)
include "../../config/fungsi_alert.php"; // Untuk menampilkan pesan sukses/error

// Pastikan variabel $conn (koneksi MySQLi) ada dan valid
if (!$conn || !($conn instanceof mysqli)) {
    error_log("Koneksi database MySQLi tidak valid atau gagal dimuat di aksi_pengetahuan.php.");
    if (function_exists('set_alert')) {
        set_alert('danger', 'Terjadi kesalahan koneksi ke database. Silakan hubungi administrator.');
    }
    header('location:../../index.php?module=pengetahuan');
    exit();
}

$module = isset($_GET['module']) ? $_GET['module'] : '';
$act = isset($_GET['act']) ? $_GET['act'] : '';

// Hapus pengetahuan
if ($module == 'pengetahuan' && $act == 'hapus') {
    if (isset($_GET['id'])) {
        $kode_pengetahuan = $_GET['id'];
        
        $stmt = $conn->prepare("DELETE FROM basis_pengetahuan WHERE kode_pengetahuan = ?");
        if ($stmt) {
            $stmt->bind_param("s", $kode_pengetahuan); // Asumsi kode_pengetahuan adalah string atau int, sesuaikan tipenya jika perlu
            if ($stmt->execute()) {
                set_alert('success', 'Data basis pengetahuan berhasil dihapus.');
            } else {
                error_log("Gagal menghapus basis pengetahuan: " . $stmt->error);
                set_alert('danger', 'Gagal menghapus data basis pengetahuan.');
            }
            $stmt->close();
        } else {
            error_log("Gagal mempersiapkan statement hapus basis pengetahuan: " . $conn->error);
            set_alert('danger', 'Terjadi kesalahan sistem saat akan menghapus basis pengetahuan.');
        }
    } else {
        set_alert('warning', 'Kode pengetahuan tidak ditemukan untuk dihapus.');
    }
    header('location:../../index.php?module=' . $module);
    exit();
}

// Input pengetahuan
elseif ($module == 'pengetahuan' && $act == 'input') {
    if (isset($_POST['kode_penyakit'], $_POST['kode_gejala'], $_POST['mb'], $_POST['md'])) {
        $kode_penyakit = $_POST['kode_penyakit'];
        $kode_gejala = $_POST['kode_gejala'];
        $mb = (float)$_POST['mb']; // Pastikan MB dan MD adalah float/double
        $md = (float)$_POST['md'];

        // Validasi dasar (Anda bisa tambahkan validasi lebih lanjut)
        if (empty($kode_penyakit) || empty($kode_gejala)) {
            set_alert('warning', 'Penyakit dan Gejala harus dipilih.');
        } elseif (!is_numeric($_POST['mb']) || !is_numeric($_POST['md'])) {
             set_alert('warning', 'Nilai MB dan MD harus berupa angka.');
        } else {
            $stmt = $conn->prepare("INSERT INTO basis_pengetahuan (kode_penyakit, kode_gejala, mb, md) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssdd", $kode_penyakit, $kode_gejala, $mb, $md); // s=string, d=double
                if ($stmt->execute()) {
                    set_alert('success', 'Data basis pengetahuan baru berhasil ditambahkan.');
                } else {
                    error_log("Gagal input basis pengetahuan: " . $stmt->error);
                    set_alert('danger', 'Gagal menambahkan basis pengetahuan. Mungkin kombinasi penyakit dan gejala sudah ada.');
                }
                $stmt->close();
            } else {
                error_log("Gagal mempersiapkan statement input basis pengetahuan: " . $conn->error);
                set_alert('danger', 'Terjadi kesalahan sistem saat akan menambahkan basis pengetahuan.');
            }
        }
    } else {
        set_alert('warning', 'Data input basis pengetahuan tidak lengkap.');
    }
    header('location:../../index.php?module=' . $module);
    exit();
}

// Update pengetahuan
elseif ($module == 'pengetahuan' && $act == 'update') {
    if (isset($_POST['id'], $_POST['kode_penyakit'], $_POST['kode_gejala'], $_POST['mb'], $_POST['md'])) {
        $kode_pengetahuan_update = $_POST['id'];
        $kode_penyakit = $_POST['kode_penyakit'];
        $kode_gejala = $_POST['kode_gejala'];
        $mb = (float)$_POST['mb'];
        $md = (float)$_POST['md'];

        if (empty($kode_penyakit) || empty($kode_gejala)) {
            set_alert('warning', 'Penyakit dan Gejala harus dipilih.');
        } elseif (!is_numeric($_POST['mb']) || !is_numeric($_POST['md'])) {
             set_alert('warning', 'Nilai MB dan MD harus berupa angka.');
        } else {
            $stmt = $conn->prepare("UPDATE basis_pengetahuan SET kode_penyakit = ?, kode_gejala = ?, mb = ?, md = ? WHERE kode_pengetahuan = ?");
            if ($stmt) {
                $stmt->bind_param("ssdds", $kode_penyakit, $kode_gejala, $mb, $md, $kode_pengetahuan_update);
                if ($stmt->execute()) {
                    set_alert('success', 'Data basis pengetahuan berhasil diperbarui.');
                } else {
                    error_log("Gagal update basis pengetahuan: " . $stmt->error);
                    set_alert('danger', 'Gagal memperbarui data basis pengetahuan.');
                }
                $stmt->close();
            } else {
                error_log("Gagal mempersiapkan statement update basis pengetahuan: " . $conn->error);
                set_alert('danger', 'Terjadi kesalahan sistem saat akan memperbarui basis pengetahuan.');
            }
        }
    } else {
        set_alert('warning', 'Data update basis pengetahuan tidak lengkap.');
    }
    header('location:../../index.php?module=' . $module);
    exit();
}

// Jika tidak ada $act yang cocok
set_alert('warning', 'Tindakan tidak valid untuk modul pengetahuan.');
header('location:../../index.php?module=' . $module);
exit();

?>