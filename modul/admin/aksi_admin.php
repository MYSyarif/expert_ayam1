<?php
// 1. Panggil session_start() HANYA SEKALI di paling atas.
session_start();

// 2. Cek otentikasi sesi.
if (!(isset($_SESSION['username']) && isset($_SESSION['password']))) {
    // Jika tidak ada sesi, kembalikan ke halaman utama atau login.
    // Menggunakan path absolut dari root web server lebih aman jika struktur direktori berubah.
    // Asumsi 'index.php' ada di root direktori aplikasi Anda.
    header('location: ../../index.php'); // Sesuaikan path jika 'index.php' ada di tempat lain relatif terhadap 'aksi_admin.php'
    exit();
}

// 3. Include file koneksi database.
// Pastikan file ini menggunakan MySQLi dan menyediakan variabel koneksi, misalnya $conn.
include "../../config/koneksi.php";

// Pastikan variabel $conn (koneksi MySQLi) ada dan valid
if (!$conn || !($conn instanceof mysqli)) {
    // Catat error atau tampilkan pesan error yang lebih informatif
    error_log("Koneksi database MySQLi tidak valid atau gagal dimuat di aksi_admin.php.");
    die("Terjadi kesalahan koneksi ke database. Silakan hubungi administrator.");
}

// 4. Ambil parameter module dan act dengan aman.
$module = isset($_GET['module']) ? $_GET['module'] : '';
$act = isset($_GET['act']) ? $_GET['act'] : '';

// Hapus admin
if ($module == 'admin' && $act == 'hapus') {
    if (isset($_GET['id'])) {
        $username_admin_dihapus = $_GET['id'];
        
        $stmt = $conn->prepare("DELETE FROM admin WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username_admin_dihapus);
            if (!$stmt->execute()) {
                error_log("Gagal menghapus admin: " . $stmt->error);
                // Anda bisa menambahkan pesan error ke session untuk ditampilkan di halaman berikutnya
                $_SESSION['alert'] = array('type' => 'error', 'message' => 'Gagal menghapus admin.');
            } else {
                 $_SESSION['alert'] = array('type' => 'success', 'message' => 'Admin berhasil dihapus.');
            }
            $stmt->close();
        } else {
            error_log("Gagal mempersiapkan statement hapus admin: " . $conn->error);
            $_SESSION['alert'] = array('type' => 'error', 'message' => 'Terjadi kesalahan sistem saat akan menghapus admin.');
        }
    } else {
        $_SESSION['alert'] = array('type' => 'error', 'message' => 'ID admin tidak ditemukan untuk dihapus.');
    }
    header('location:../../index.php?module=' . $module);
    exit();
}

// Input admin
elseif ($module == 'admin' && $act == 'input') {
    if (isset($_POST['username'], $_POST['nama_lengkap'], $_POST['password'])) {
        $username = $_POST['username'];
        $nama_lengkap = $_POST['nama_lengkap'];
        // PENTING: MD5 sangat tidak aman untuk password. Pertimbangkan menggunakan password_hash().
        // Namun, untuk konsistensi dengan kode login Anda yang mungkin masih MD5, saya biarkan.
        $pass = md5($_POST['password']);

        $stmt = $conn->prepare("INSERT INTO admin (username, password, nama_lengkap) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $username, $pass, $nama_lengkap);
            if (!$stmt->execute()) {
                error_log("Gagal input admin: " . $stmt->error);
                $_SESSION['alert'] = array('type' => 'error', 'message' => 'Gagal menambahkan admin. Username mungkin sudah ada.');
            } else {
                $_SESSION['alert'] = array('type' => 'success', 'message' => 'Admin baru berhasil ditambahkan.');
            }
            $stmt->close();
        } else {
            error_log("Gagal mempersiapkan statement input admin: " . $conn->error);
            $_SESSION['alert'] = array('type' => 'error', 'message' => 'Terjadi kesalahan sistem saat akan menambahkan admin.');
        }
    } else {
         $_SESSION['alert'] = array('type' => 'error', 'message' => 'Data input admin tidak lengkap.');
    }
    header('location:../../index.php?module=' . $module);
    exit();
}

// Update admin
elseif ($module == 'admin' && $act == 'update') {
    if (isset($_POST['id'], $_POST['username'], $_POST['nama_lengkap'])) {
        $current_id_admin = $_POST['id']; // Username lama atau ID unik yang digunakan untuk klausa WHERE
        $new_username = $_POST['username'];
        $nama_lengkap = $_POST['nama_lengkap'];
        
        // Logika untuk update password (jika field password diisi)
        if (!empty($_POST['password'])) {
            $pass = md5($_POST['password']); // Sekali lagi, pertimbangkan password_hash()
            $stmt = $conn->prepare("UPDATE admin SET username = ?, nama_lengkap = ?, password = ? WHERE username = ?");
            if ($stmt) {
                $stmt->bind_param("ssss", $new_username, $nama_lengkap, $pass, $current_id_admin);
            }
        } else {
            // Jika password tidak diisi, jangan update password
            $stmt = $conn->prepare("UPDATE admin SET username = ?, nama_lengkap = ? WHERE username = ?");
            if ($stmt) {
                $stmt->bind_param("sss", $new_username, $nama_lengkap, $current_id_admin);
            }
        }

        if ($stmt) {
            if (!$stmt->execute()) {
                error_log("Gagal update admin: " . $stmt->error);
                 $_SESSION['alert'] = array('type' => 'error', 'message' => 'Gagal memperbarui admin. Username baru mungkin sudah ada.');
            } else {
                $_SESSION['alert'] = array('type' => 'success', 'message' => 'Data admin berhasil diperbarui.');
                // Jika admin yang sedang login mengubah username-nya sendiri
                if (isset($_SESSION['username']) && $_SESSION['username'] == $current_id_admin && $current_id_admin != $new_username) {
                    $_SESSION['username'] = $new_username;
                }
            }
            $stmt->close();
        } else {
             error_log("Gagal mempersiapkan statement update admin: " . $conn->error);
             $_SESSION['alert'] = array('type' => 'error', 'message' => 'Terjadi kesalahan sistem saat akan memperbarui admin.');
        }
    } else {
        $_SESSION['alert'] = array('type' => 'error', 'message' => 'Data update admin tidak lengkap.');
    }
    header('location:../../index.php?module=' . $module);
    exit();
}

// Jika module atau act tidak cocok dengan kondisi di atas, kembali ke halaman utama modul
// Ini untuk menghindari halaman kosong jika parameter tidak sesuai.
// Anda bisa juga menambahkan pesan error default.
if ($debug_mode && !headers_sent()) { // Hanya untuk debug, headers_sent() untuk mencegah error jika sudah ada output
    echo "DEBUG: Tidak ada tindakan yang cocok untuk module '$module' dan act '$act'. Mengarahkan kembali.<br>";
}

// Pastikan tidak ada output lain sebelum header() jika belum ada exit() di atas.
// Namun, karena semua cabang if-elseif sudah memiliki header() dan exit(), baris di bawah ini mungkin tidak akan pernah tercapai.
if (!headers_sent()) {
    header('location:../../index.php' . ($module ? '?module=' . $module : ''));
    exit();
}

?>