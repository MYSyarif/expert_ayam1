<?php
session_start(); // Panggil sekali di atas

// Cek otentikasi sesi
if (!(isset($_SESSION['username']) && isset($_SESSION['password']))) {
    header('location:../../index.php');
    exit();
}

include "../../config/koneksi.php";
include "../../config/fungsi_alert.php"; // Untuk menampilkan pesan

// Pastikan variabel $conn (koneksi MySQLi) ada dan valid
if (!$conn || !($conn instanceof mysqli)) {
    error_log("Koneksi database MySQLi tidak valid atau gagal dimuat di aksi_post.php.");
    if (function_exists('set_alert')) {
        set_alert('danger', 'Terjadi kesalahan koneksi ke database. Silakan hubungi administrator.');
    }
    header('location:../../index.php?module=post');
    exit();
}

$module = isset($_GET['module']) ? $_GET['module'] : '';
$act = isset($_GET['act']) ? $_GET['act'] : '';

// Hapus post
if ($module == 'post' && $act == 'hapus') {
    if (isset($_GET['id'])) {
        $kode_post = $_GET['id'];

        // Opsional: Hapus gambar terkait
        $stmt_select_gambar = $conn->prepare("SELECT gambar FROM post WHERE kode_post = ?");
        if ($stmt_select_gambar) {
            $stmt_select_gambar->bind_param("s", $kode_post); // Asumsi kode_post bisa string
            $stmt_select_gambar->execute();
            $result_gambar = $stmt_select_gambar->get_result();
            if ($row_gambar = $result_gambar->fetch_assoc()) {
                if (!empty($row_gambar['gambar'])) {
                    $gambarPath = "../../gambar/" . $row_gambar['gambar']; // Path gambar untuk post
                    if (file_exists($gambarPath)) {
                        unlink($gambarPath);
                    }
                }
            }
            $stmt_select_gambar->close();
        }

        $stmt = $conn->prepare("DELETE FROM post WHERE kode_post = ?");
        if ($stmt) {
            $stmt->bind_param("s", $kode_post);
            if ($stmt->execute()) {
                set_alert('success', 'Data post berhasil dihapus.');
            } else {
                error_log("Gagal menghapus post: " . $stmt->error);
                set_alert('danger', 'Gagal menghapus data post.');
            }
            $stmt->close();
        } else {
            error_log("Gagal mempersiapkan statement hapus post: " . $conn->error);
            set_alert('danger', 'Terjadi kesalahan sistem saat akan menghapus post.');
        }
    } else {
        set_alert('warning', 'Kode post tidak ditemukan untuk dihapus.');
    }
    header('location:../../index.php?module=' . $module);
    exit();
}

// Input post
elseif ($module == 'post' && $act == 'input') {
    if (isset($_POST['nama_post'], $_POST['det_post'], $_POST['srn_post'])) {
        $nama_post = trim($_POST['nama_post']);
        $det_post = $_POST['det_post']; // CKEditor biasanya sudah menghasilkan HTML, sanitasi lebih lanjut mungkin perlu saat display
        $srn_post = $_POST['srn_post']; // Sama seperti det_post
        $fileName = "";

        // Validasi dasar
        if (empty($nama_post)) {
            set_alert('warning', 'Nama post tidak boleh kosong.');
        } else {
            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar']['name'])) {
                $uploadDir = "../../gambar/"; // Path untuk gambar post
                $fileName = time() . '_' . basename($_FILES['gambar']['name']); // Tambahkan timestamp untuk unik
                $targetFilePath = $uploadDir . $fileName;
                $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
                $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
                $maxFileSize = 1 * 1024 * 1024; // 1MB

                if (in_array($fileType, $allowTypes)) {
                    if ($_FILES['gambar']['size'] <= $maxFileSize) {
                        if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $targetFilePath)) {
                            $fileName = "";
                            set_alert('warning', 'Gagal mengupload file gambar.');
                        }
                    } else {
                        $fileName = "";
                        set_alert('warning', 'Ukuran file gambar maksimal adalah 1MB.');
                    }
                } else {
                    $fileName = "";
                    set_alert('warning', 'Hanya file JPG, JPEG, PNG, & GIF yang diizinkan untuk gambar.');
                }
            } elseif (isset($_FILES['gambar']) && $_FILES['gambar']['error'] != UPLOAD_ERR_NO_FILE) {
                // Jika ada file tapi error selain 'NO_FILE'
                set_alert('warning', 'Terjadi masalah saat mengupload gambar: error code ' . $_FILES['gambar']['error']);
            }


            $stmt = $conn->prepare("INSERT INTO post (nama_post, det_post, srn_post, gambar) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssss", $nama_post, $det_post, $srn_post, $fileName);
                if ($stmt->execute()) {
                    set_alert('success', 'Post baru berhasil ditambahkan.');
                } else {
                    error_log("Gagal input post: " . $stmt->error);
                    set_alert('danger', 'Gagal menambahkan post baru.');
                }
                $stmt->close();
            } else {
                error_log("Gagal mempersiapkan statement input post: " . $conn->error);
                set_alert('danger', 'Terjadi kesalahan sistem saat akan menambahkan post.');
            }
        }
    } else {
        set_alert('warning', 'Data input post tidak lengkap.');
    }
    header('location:../../index.php?module=' . $module);
    exit();
}

// Update post
elseif ($module == 'post' && $act == 'update') {
    if (isset($_POST['id'], $_POST['nama_post'], $_POST['det_post'], $_POST['srn_post'])) {
        $kode_post = $_POST['id'];
        $nama_post = trim($_POST['nama_post']);
        $det_post = $_POST['det_post'];
        $srn_post = $_POST['srn_post'];
        
        if (empty($nama_post)) {
             set_alert('warning', 'Nama post tidak boleh kosong.');
        } else {
            $newFileName = "";
            $updateGambarQueryPart = "";
            $params = [$nama_post, $det_post, $srn_post];
            $types = "sss";

            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar']['name'])) {
                // Hapus gambar lama
                $stmt_select_old_gambar = $conn->prepare("SELECT gambar FROM post WHERE kode_post = ?");
                if ($stmt_select_old_gambar) {
                    $stmt_select_old_gambar->bind_param("s", $kode_post);
                    $stmt_select_old_gambar->execute();
                    $result_old_gambar = $stmt_select_old_gambar->get_result();
                    if ($row_old_gambar = $result_old_gambar->fetch_assoc()) {
                        if (!empty($row_old_gambar['gambar'])) {
                            $oldGambarPath = "../../gambar/" . $row_old_gambar['gambar'];
                            if (file_exists($oldGambarPath)) {
                                unlink($oldGambarPath);
                            }
                        }
                    }
                    $stmt_select_old_gambar->close();
                }

                // Upload gambar baru
                $uploadDir = "../../gambar/";
                $newFileName = time() . '_' . basename($_FILES['gambar']['name']);
                $targetFilePath = $uploadDir . $newFileName;
                $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
                $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
                $maxFileSize = 1 * 1024 * 1024; // 1MB

                if (in_array($fileType, $allowTypes)) {
                     if ($_FILES['gambar']['size'] <= $maxFileSize) {
                        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $targetFilePath)) {
                            $updateGambarQueryPart = ", gambar = ?";
                            $params[] = $newFileName;
                            $types .= "s";
                        } else {
                            set_alert('warning', 'Gagal mengupload file gambar baru.');
                            // Tidak menghentikan update data lain jika gambar gagal diupload
                        }
                    } else {
                         set_alert('warning', 'Ukuran file gambar baru maksimal adalah 1MB.');
                    }
                } else {
                    set_alert('warning', 'Hanya file JPG, JPEG, PNG, & GIF yang diizinkan untuk gambar baru.');
                }
            } elseif (isset($_FILES['gambar']) && $_FILES['gambar']['error'] != UPLOAD_ERR_NO_FILE) {
                 set_alert('warning', 'Terjadi masalah saat mengupload gambar untuk update: error code ' . $_FILES['gambar']['error']);
            }


            $params[] = $kode_post; // Tambahkan kode_post untuk klausa WHERE
            $types .= "s";

            $sql = "UPDATE post SET nama_post = ?, det_post = ?, srn_post = ? $updateGambarQueryPart WHERE kode_post = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    set_alert('success', 'Data post berhasil diperbarui.');
                } else {
                    error_log("Gagal update post: " . $stmt->error);
                    set_alert('danger', 'Gagal memperbarui data post.');
                }
                $stmt->close();
            } else {
                error_log("Gagal mempersiapkan statement update post: " . $conn->error . " SQL: " . $sql);
                set_alert('danger', 'Terjadi kesalahan sistem saat akan memperbarui post.');
            }
        }
    } else {
        set_alert('warning', 'Data update post tidak lengkap.');
    }
    header('location:../../index.php?module=' . $module);
    exit();
}

// Default fallback
set_alert('warning', 'Tindakan tidak valid untuk modul post.');
header('location:../../index.php?module=' . $module);
exit();

?>