<?php
// session_start(); // Diasumsikan sudah dipanggil oleh index.php
// Jika file ini adalah 'password.php' yang di-include sebagai modul,
// pengecekan sesi utama seharusnya dilakukan di index.php.
// Jika ini adalah file yang berdiri sendiri, session_start() diperlukan di atas.

// Pastikan koneksi $conn sudah ada dari config/koneksi.php yang di-include oleh index.php
if (!isset($conn) || !($conn instanceof mysqli)) {
    echo "<div class='alert alert-danger' style='margin:15px;'>Koneksi database gagal. Fitur ubah password tidak dapat digunakan.</div>";
    // Sebaiknya hentikan eksekusi lebih lanjut atau tampilkan pesan error yang jelas
    // exit(); // Atau cara lain yang sesuai dengan struktur aplikasi Anda
}

// Pengecekan sesi pengguna
if (isset($_SESSION['username']) && isset($_SESSION['password'])) {

    // Menampilkan alert dari session jika ada (pastikan fungsi ini ada dan session_start() sudah dipanggil)
    if (function_exists('display_alert')) {
        display_alert();
    }

    $act = isset($_GET['act']) ? $_GET['act'] : 'default';

    switch ($act) {
        default:
            ?>
            <title>Ubah Password - Chirexs 1.0</title>
            <h2 class='text text-primary'>Ubah Password Anda</h2>
            <hr>
            <form method='post' action='index.php?module=password&act=updatepassword' name="formUbahPassword">
                <table class='table table-bordered' style="max-width: 600px;">
                    <tr>
                        <td width='220'>Masukkan Password Lama</td>
                        <td><input class='form-control' autocomplete='off' placeholder='Ketik password lama...' type='password' name='oldPass' required /></td>
                    </tr>
                    <tr>
                        <td>Masukkan Password Baru</td>
                        <td><input class='form-control' autocomplete='off' placeholder='Ketik password baru...' type='password' name='newPass1' required /></td>
                    </tr>
                    <tr>
                        <td>Ulangi Password Baru</td>
                        <td><input class='form-control' autocomplete='off' placeholder='Ulangi password baru...' type='password' name='newPass2' required /></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <input class='btn btn-success' type='submit' name='submit' title='Simpan Perubahan Password' value='Simpan Perubahan' />
                            </td>
                    </tr>
                </table>
            </form>
            <?php
            break;

        case "updatepassword":
            // config/koneksi.php seharusnya sudah di-include oleh index.php
            // Pastikan $conn sudah tersedia
            if (!isset($conn) || !($conn instanceof mysqli)) {
                 if(function_exists('set_alert')) set_alert('danger', 'Koneksi database tidak valid.');
                 header('Location: index.php?module=password');
                 exit();
            }

            if (isset($_POST['oldPass'], $_POST['newPass1'], $_POST['newPass2'])) {
                $user_session = $_SESSION['username']; // Ambil username dari sesi yang aktif
                $passwordlama = $_POST['oldPass'];
                $passwordbaru1 = $_POST['newPass1'];
                $passwordbaru2 = $_POST['newPass2'];

                $stmt_select = $conn->prepare("SELECT password FROM admin WHERE username = ?");
                if (!$stmt_select) {
                    error_log("Prepare statement SELECT password gagal: " . $conn->error);
                    if(function_exists('set_alert')) set_alert('danger', 'Terjadi kesalahan sistem (1).');
                    header('Location: index.php?module=password');
                    exit();
                }
                
                $stmt_select->bind_param("s", $user_session);
                $stmt_select->execute();
                $result_select = $stmt_select->get_result();
                
                if ($result_select->num_rows > 0) {
                    $data = $result_select->fetch_assoc();
                    
                    // Verifikasi password lama
                    // PENTING: MD5 sangat tidak aman. Pertimbangkan password_verify() jika password di DB di-hash dengan password_hash()
                    if ($data['password'] == md5($passwordlama)) {
                        if (empty($passwordbaru1)) {
                             if(function_exists('set_alert')) set_alert('warning', 'Password baru tidak boleh kosong.');
                        } elseif ($passwordbaru1 == $passwordbaru2) {
                            $passwordbaruenkrip = md5($passwordbaru1); // Sekali lagi, MD5 tidak aman
                            
                            $stmt_update = $conn->prepare("UPDATE admin SET password = ? WHERE username = ?");
                            if(!$stmt_update){
                                error_log("Prepare statement UPDATE password gagal: " . $conn->error);
                                if(function_exists('set_alert')) set_alert('danger', 'Terjadi kesalahan sistem (2).');
                                header('Location: index.php?module=password');
                                exit();
                            }

                            $stmt_update->bind_param("ss", $passwordbaruenkrip, $user_session);
                            if ($stmt_update->execute()) {
                                if(function_exists('set_alert')) set_alert('success', 'Password berhasil diubah.');
                                // Update password di session jika perlu (jika Anda menyimpannya di sana)
                                $_SESSION['password'] = $passwordbaruenkrip; // Berhati-hati menyimpan hash password di session
                            } else {
                                error_log("Eksekusi update password gagal: " . $stmt_update->error);
                                if(function_exists('set_alert')) set_alert('danger', 'Gagal mengubah password di database.');
                            }
                            $stmt_update->close();
                        } else {
                            if(function_exists('set_alert')) set_alert('danger', 'Password baru Anda tidak sama.');
                        }
                    } else {
                        if(function_exists('set_alert')) set_alert('danger', 'Password lama Anda salah.');
                    }
                } else {
                    if(function_exists('set_alert')) set_alert('danger', 'User tidak ditemukan.');
                }
                $stmt_select->close();
            } else {
                if(function_exists('set_alert')) set_alert('warning', 'Semua field password harus diisi.');
            }
            header('Location: index.php?module=password'); // Kembali ke halaman form password
            exit();
            break;
    }
} else {
    // Jika sesi tidak ada, biasanya index.php sudah menghalangi akses,
    // tapi sebagai fallback jika file ini diakses langsung:
    echo "<title>Akses Ditolak - Chirexs 1.0</title>";
    echo "<div style='text-align:center; margin-top: 50px;'>";
    echo "<h2>Akses Ditolak</h2>";
    echo "<p><strong>Anda harus login untuk dapat mengakses menu ini!</strong></p><br>";
    // Arahkan ke halaman login utama (misalnya index.php atau ?module=formlogin)
    echo "<input type='button' value='Login' onclick=\"window.location.href='index.php?module=formlogin';\" class='btn btn-primary'>";
    echo "</div>";
}
?>