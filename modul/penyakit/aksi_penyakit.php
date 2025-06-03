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
    error_log("Koneksi database MySQLi tidak valid atau gagal dimuat di aksi_penyakit.php.");
    // Set alert error jika fungsi tersedia
    if (function_exists('set_alert')) {
        set_alert('danger', 'Terjadi kesalahan koneksi ke database. Silakan hubungi administrator.');
    }
    header('location:../../index.php?module=penyakit'); // Redirect ke halaman penyakit dengan pesan error
    exit();
}

$module = isset($_GET['module']) ? $_GET['module'] : '';
$act = isset($_GET['act']) ? $_GET['act'] : '';

// Hapus penyakit
if ($module == 'penyakit' && $act == 'hapus') {
    if (isset($_GET['id'])) {
        $kode_penyakit = $_GET['id'];

        // Opsional: Hapus gambar terkait jika ada sebelum menghapus record dari DB
        $stmt_select_gambar = $conn->prepare("SELECT gambar FROM penyakit WHERE kode_penyakit = ?");
        if ($stmt_select_gambar) {
            $stmt_select_gambar->bind_param("s", $kode_penyakit);
            $stmt_select_gambar->execute();
            $result_gambar = $stmt_select_gambar->get_result();
            if ($row_gambar = $result_gambar->fetch_assoc()) {
                if (!empty($row_gambar['gambar']) && file_exists("../../gambar/penyakit/" . $row_gambar['gambar'])) {
                    unlink("../../gambar/penyakit/" . $row_gambar['gambar']);
                }
            }
            $stmt_select_gambar->close();
        }

        $stmt = $conn->prepare("DELETE FROM penyakit WHERE kode_penyakit = ?");
        if ($stmt) {
            $stmt->bind_param("s", $kode_penyakit);
            if ($stmt->execute()) {
                set_alert('success', 'Data penyakit berhasil dihapus.');
            } else {
                error_log("Gagal menghapus penyakit: " . $stmt->error);
                set_alert('danger', 'Gagal menghapus data penyakit.');
            }
            $stmt->close();
        } else {
            error_log("Gagal mempersiapkan statement hapus penyakit: " . $conn->error);
            set_alert('danger', 'Terjadi kesalahan sistem saat akan menghapus penyakit.');
        }
    } else {
        set_alert('warning', 'Kode penyakit tidak ditemukan untuk dihapus.');
    }
    header('location:../../index.php?module=' . $module);
    exit();
}

// Input penyakit
elseif ($module == 'penyakit' && $act == 'input') {
    if (isset($_POST['nama_penyakit'], $_POST['det_penyakit'], $_POST['srn_penyakit'])) {
        $nama_penyakit = $_POST['nama_penyakit'];
        $det_penyakit = $_POST['det_penyakit'];
        $srn_penyakit = $_POST['srn_penyakit'];
        $fileName = ""; // Default nama file kosong

        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = "../../gambar/penyakit/";
            $fileName = basename($_FILES['gambar']['name']);
            $targetFilePath = $uploadDir . $fileName;
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
            $allowTypes = array('jpg', 'png', 'jpeg', 'gif');

            if (in_array(strtolower($fileType), $allowTypes)) {
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $targetFilePath)) {
                    // File berhasil diupload
                } else {
                    $fileName = ""; // Gagal upload, set nama file jadi kosong lagi
                    set_alert('warning', 'Gagal mengupload file gambar.');
                    // Anda bisa memilih untuk menghentikan proses atau lanjut tanpa gambar
                }
            } else {
                $fileName = "";
                set_alert('warning', 'Hanya file JPG, JPEG, PNG, & GIF yang diizinkan untuk gambar.');
            }
        }

        $stmt = $conn->prepare("INSERT INTO penyakit (nama_penyakit, det_penyakit, srn_penyakit, gambar) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $nama_penyakit, $det_penyakit, $srn_penyakit, $fileName);
            if ($stmt->execute()) {
                set_alert('success', 'Data penyakit baru berhasil ditambahkan.');
            } else {
                error_log("Gagal input penyakit: " . $stmt->error);
                set_alert('danger', 'Gagal menambahkan data penyakit. Kode penyakit mungkin sudah ada atau ada kesalahan lain.');
            }
            $stmt->close();
        } else {
            error_log("Gagal mempersiapkan statement input penyakit: " . $conn->error);
            set_alert('danger', 'Terjadi kesalahan sistem saat akan menambahkan penyakit.');
        }
    } else {
        set_alert('warning', 'Data input penyakit tidak lengkap.');
    }
    header('location:../../index.php?module=' . $module);
    exit();
}

// Update penyakit
elseif ($module == 'penyakit' && $act == 'update') {
    if (isset($_POST['id'], $_POST['nama_penyakit'], $_POST['det_penyakit'], $_POST['srn_penyakit'])) {
        $kode_penyakit = $_POST['id'];
        $nama_penyakit = $_POST['nama_penyakit'];
        $det_penyakit = $_POST['det_penyakit'];
        $srn_penyakit = $_POST['srn_penyakit'];
        $updateGambar = false;
        $newFileName = "";

        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar']['name'])) {
            // Hapus gambar lama jika ada dan jika file baru diupload
            $stmt_select_gambar_lama = $conn->prepare("SELECT gambar FROM penyakit WHERE kode_penyakit = ?");
             if ($stmt_select_gambar_lama) {
                $stmt_select_gambar_lama->bind_param("s", $kode_penyakit);
                $stmt_select_gambar_lama->execute();
                $result_gambar_lama = $stmt_select_gambar_lama->get_result();
                if ($row_gambar_lama = $result_gambar_lama->fetch_assoc()) {
                    if (!empty($row_gambar_lama['gambar']) && file_exists("../../gambar/penyakit/" . $row_gambar_lama['gambar'])) {
                        unlink("../../gambar/penyakit/" . $row_gambar_lama['gambar']);
                    }
                }
                $stmt_select_gambar_lama->close();
            }


            $uploadDir = "../../gambar/penyakit/";
            $newFileName = basename($_FILES['gambar']['name']);
            $targetFilePath = $uploadDir . $newFileName;
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
            $allowTypes = array('jpg', 'png', 'jpeg', 'gif');

            if (in_array(strtolower($fileType), $allowTypes)) {
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $targetFilePath)) {
                    $updateGambar = true;
                } else {
                    set_alert('warning', 'Gagal mengupload file gambar baru.');
                }
            } else {
                set_alert('warning', 'Hanya file JPG, JPEG, PNG, & GIF yang diizinkan untuk gambar baru.');
            }
        }

        if ($updateGambar) {
            $stmt = $conn->prepare("UPDATE penyakit SET nama_penyakit = ?, det_penyakit = ?, srn_penyakit = ?, gambar = ? WHERE kode_penyakit = ?");
            if ($stmt) {
                $stmt->bind_param("sssss", $nama_penyakit, $det_penyakit, $srn_penyakit, $newFileName, $kode_penyakit);
            }
        } else {
            $stmt = $conn->prepare("UPDATE penyakit SET nama_penyakit = ?, det_penyakit = ?, srn_penyakit = ? WHERE kode_penyakit = ?");
            if ($stmt) {
                $stmt->bind_param("ssss", $nama_penyakit, $det_penyakit, $srn_penyakit, $kode_penyakit);
            }
        }

        if ($stmt) {
            if ($stmt->execute()) {
                set_alert('success', 'Data penyakit berhasil diperbarui.');
            } else {
                error_log("Gagal update penyakit: " . $stmt->error);
                set_alert('danger', 'Gagal memperbarui data penyakit.');
            }
            $stmt->close();
        } else {
            error_log("Gagal mempersiapkan statement update penyakit: " . $conn->error);
            set_alert('danger', 'Terjadi kesalahan sistem saat akan memperbarui penyakit.');
        }
    } else {
        set_alert('warning', 'Data update penyakit tidak lengkap.');
    }
    header('location:../../index.php?module=' . $module);
    exit();
}

// Jika tidak ada $act yang cocok
set_alert('warning', 'Tindakan tidak valid.');
header('location:../../index.php?module=' . $module);
exit();

?>