<?php
// session_start(); // Seharusnya sudah dipanggil oleh index.php (atau file utama Anda)
if (!(isset($_SESSION['username']) && isset($_SESSION['password']))) {
    // Jika tidak ada sesi, index.php seharusnya sudah menghalangi akses ke modul ini.
    // Jika file ini bisa diakses langsung, maka header location diperlukan di sini.
    // Namun, dalam struktur Anda, index.php yang mengatur ini.
    // header('location:index.php');
    // exit();
}
?>
<title>Manajemen Penyakit - Chirexs 1.0</title>
<script type="text/javascript">
  function Blank_TextField_Validator() {
    var namaPenyakit = document.forms["text_form"]["nama_penyakit"].value;
    if (namaPenyakit.trim() == "") {
      alert("Nama Penyakit tidak boleh kosong !");
      document.forms["text_form"]["nama_penyakit"].focus();
      return false;
    }
    // Anda bisa menambahkan validasi lain di sini jika perlu
    return true;
  }

  function Blank_TextField_Validator_Cari() {
    var keyword = document.forms["text_form_cari"]["keyword"].value; 
    if (keyword.trim() == "") {
      alert("Isi dulu keyword pencarian !");
      document.forms["text_form_cari"]["keyword"].focus();
      return false;
    }
    return true;
  }
</script>

<?php
// include "config/fungsi_alert.php"; // Pastikan ini di-include di index.php jika display_alert() ada di sana
$aksi = "modul/penyakit/aksi_penyakit.php"; // Path ke file aksi

// Pastikan $conn (koneksi MySQLi) ada dan valid dari index.php -> config/koneksi.php
if (!$conn || !($conn instanceof mysqli)) {
    echo "<div class='alert alert-danger' style='margin:15px;'>Koneksi database gagal. Silakan hubungi administrator.</div>";
    // Hentikan eksekusi sisa halaman jika koneksi gagal
    // exit(); // Atau tangani dengan cara lain yang sesuai
} else { // Lanjutkan hanya jika koneksi berhasil

    // Menampilkan alert dari session jika ada (pastikan fungsi ini ada dan session_start() sudah dipanggil)
    if (function_exists('display_alert')) {
        display_alert();
    }

    $act = isset($_GET['act']) ? $_GET['act'] : 'default'; // Default action

    switch ($act) {
        // Tampil penyakit
        default:
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $limit = 15;

            echo "<form method='POST' action='index.php?module=penyakit' name='text_form_cari' onsubmit='return Blank_TextField_Validator_Cari()'>
                  <br><br><table class='table table-bordered'>
                  <tr><td>
                      <a class='btn bg-olive margin' role='button' href='index.php?module=penyakit&act=tambahpenyakit'>Tambah Penyakit</a>
                      <input type='text' name='keyword' style='margin-left: 10px; width: 250px; display: inline-block;' placeholder='Ketik nama penyakit...' class='form-control' value='" . (isset($_POST['keyword']) ? htmlspecialchars($_POST['keyword']) : '') . "' /> 
                      <input class='btn bg-olive margin' type='submit' value='Cari' name='Go'>
                  </td></tr>
                  </table></form>";

            $query_basis = "SELECT * FROM penyakit";
            $kondisi_where = "";
            $params = [];
            $types = "";

            if (isset($_POST['Go']) && !empty(trim($_POST['keyword']))) {
                $keyword_search_term = "%" . trim($_POST['keyword']) . "%";
                $kondisi_where = " WHERE nama_penyakit LIKE ?";
                $params[] = $keyword_search_term;
                $types .= "s";
            } elseif (isset($_GET['keyword_search']) && !empty(trim($_GET['keyword_search']))) { // Untuk paging setelah search
                $keyword_search_term = "%" . trim($_GET['keyword_search']) . "%";
                $kondisi_where = " WHERE nama_penyakit LIKE ?";
                $params[] = $keyword_search_term;
                $types .= "s";
            }
            
            $stmt_total = $conn->prepare($query_basis . $kondisi_where);
            $baris = 0;
            if ($stmt_total) {
                if (!empty($params)) {
                    $stmt_total->bind_param($types, ...$params);
                }
                if ($stmt_total->execute()) {
                    $stmt_total->store_result();
                    $baris = $stmt_total->num_rows;
                } else {
                    error_log("Error executing total count query: " . $stmt_total->error);
                }
                $stmt_total->close();
            } else {
                error_log("Error preparing total count query: " . $conn->error);
            }
            

            if (isset($_POST['Go']) && !empty(trim($_POST['keyword']))) {
                if ($baris > 0) {
                    if (function_exists('set_alert')) set_alert('success', 'Penyakit yang Anda cari ditemukan.');
                } else {
                    if (function_exists('set_alert')) set_alert('danger', 'Maaf, penyakit yang Anda cari tidak ditemukan.');
                }
                if (function_exists('display_alert')) display_alert(); 
            }

            if ($baris > 0) {
                echo "<table class='table table-bordered table-striped' style='overflow-x:auto;'>
                      <thead>
                        <tr>
                          <th>No</th>
                          <th>Nama Penyakit</th>
                          <th>Detail Penyakit</th>
                          <th>Saran Penyakit</th>
                          <th>Gambar</th>
                          <th>Aksi</th>
                        </tr>
                      </thead>
                      <tbody>";
                
                $query_data = $query_basis . $kondisi_where . " ORDER BY kode_penyakit LIMIT ?, ?";
                $stmt_data = $conn->prepare($query_data);
                
                if ($stmt_data) {
                    $current_offset_data = $offset; 
                    $current_limit_data = $limit;  

                    $params_data = $params; // Mulai dengan parameter pencarian (jika ada)
                    $types_data = $types;

                    $params_data[] = $current_offset_data; // Tambahkan offset untuk paging
                    $types_data .= "i";
                    $params_data[] = $current_limit_data;  // Tambahkan limit untuk paging
                    $types_data .= "i";
                    
                    $stmt_data->bind_param($types_data, ...$params_data);
                    
                    $stmt_data->execute();
                    $hasil = $stmt_data->get_result();
                    
                    $no = 1 + $offset;
                    while ($r = $hasil->fetch_assoc()) {
                        $warna = ($no % 2 == 0) ? "even" : "odd"; 
                        echo "<tr class='" . $warna . "'>
                               <td align='center'>$no</td>
                               <td>" . htmlspecialchars($r['nama_penyakit']) . "</td>
                               <td>" . nl2br(htmlspecialchars(substr($r['det_penyakit'], 0, 150))) . (strlen($r['det_penyakit']) > 150 ? '...' : '') . "</td>
                               <td>" . nl2br(htmlspecialchars(substr($r['srn_penyakit'], 0, 150))) . (strlen($r['srn_penyakit']) > 150 ? '...' : '') . "</td>
                               <td align='center'>";
                        if (!empty($r['gambar']) && file_exists("gambar/penyakit/" . $r['gambar'])) {
                            echo "<img src='gambar/penyakit/" . htmlspecialchars($r['gambar']) . "' alt='" . htmlspecialchars($r['nama_penyakit']) . "' style='width:100px; height:auto; max-height:100px; object-fit:cover;'>";
                        } else {
                            echo "Tidak ada<br>gambar";
                        }
                        echo "</td>
                               <td align='center' style='min-width:120px;'>
                                 <a type='button' class='btn btn-sm btn-success' style='margin-bottom:5px; display:block;' href='index.php?module=penyakit&act=editpenyakit&id=" . htmlspecialchars($r['kode_penyakit']) . "'><i class='fa fa-pencil-square-o'></i> Ubah</a>
                                 <a type='button' class='btn btn-sm btn-danger' style='display:block;' href='" . $aksi . "?module=penyakit&act=hapus&id=" . htmlspecialchars($r['kode_penyakit']) . "' onclick=\"return confirm('Anda yakin akan menghapus penyakit: " . htmlspecialchars($r['nama_penyakit'], ENT_QUOTES) . " ?');\">
                                 <i class='fa fa-trash-o'></i> Hapus</a>
                               </td>
                             </tr>";
                        $no++;
                    }
                    echo "</tbody></table>";
                    $stmt_data->close();

                    // Paging
                    echo "<div class='paging'>";
                    $keyword_param_paging = "";
                    if (isset($_POST['Go']) && !empty(trim($_POST['keyword']))) {
                        $keyword_param_paging = "&keyword_search=" . urlencode(trim($_POST['keyword']));
                    } elseif (isset($_GET['keyword_search']) && !empty(trim($_GET['keyword_search']))) {
                        $keyword_param_paging = "&keyword_search=" . urlencode(trim($_GET['keyword_search']));
                    }

                    if ($offset != 0) {
                        $prevoffset = $offset - $limit;
                        if ($prevoffset < 0) $prevoffset = 0;
                        echo "<span class='prevnext'> <a href='index.php?module=penyakit&offset=$prevoffset" . $keyword_param_paging . "'>Back</a></span>";
                    } else {
                        echo "<span class='disabled'>Back</span>";
                    }

                    $halaman = ceil($baris / $limit);
                    for ($i = 1; $i <= $halaman; $i++) {
                        $newoffset = $limit * ($i - 1);
                        if ($offset != $newoffset) {
                            echo "<a href='index.php?module=penyakit&offset=$newoffset" . $keyword_param_paging . "'>$i</a>";
                        } else {
                            echo "<span class='current'>$i</span>";
                        }
                    }

                    if (($offset + $limit) < $baris) {
                        $newoffset = $offset + $limit;
                        echo "<span class='prevnext'><a href='index.php?module=penyakit&offset=$newoffset" . $keyword_param_paging . "'>Next</a></span>";
                    } else {
                        echo "<span class='disabled'>Next</span>";
                    }
                    echo "</div>";

                } else {
                     error_log("Error preparing data query penyakit: " . $conn->error);
                     echo "<div class='alert alert-danger' style='margin:15px;'>Terjadi kesalahan saat mengambil data penyakit.</div>";
                }
            } else {
                 if (!(isset($_POST['Go']) && !empty(trim($_POST['keyword'])))) {
                    echo "<br><b>Data Penyakit Kosong !</b>";
                 }
            }
            break;

        case "tambahpenyakit":
            echo "<form name='text_form' method='POST' action='$aksi?module=penyakit&act=input' onsubmit='return Blank_TextField_Validator()' enctype='multipart/form-data'>
                  <br><br><table class='table table-bordered'>
                  <tr><td width='150'>Nama Penyakit</td><td><input autocomplete='off' type='text' placeholder='Masukkan penyakit baru...' class='form-control' name='nama_penyakit' size='60' required></td></tr>
                  <tr><td>Detail Penyakit</td><td><textarea rows='4' class='form-control' name='det_penyakit' placeholder='Masukkan detail penyakit baru...'></textarea></td></tr>
                  <tr><td>Saran Penyakit</td><td><textarea rows='4' class='form-control' name='srn_penyakit' placeholder='Masukkan saran penyakit baru...'></textarea></td></tr>
                  <tr><td>Gambar</td><td>Upload Gambar (Ukuran Maks = 1MB, tipe: JPG, PNG, GIF): <input type='file' class='form-control' name='gambar' accept='image/jpeg,image/png,image/gif'></td></tr>		  
                  <tr><td></td><td><input class='btn btn-success' type='submit' name='submit' value='Simpan'>
                  <input class='btn btn-danger' type='button' name='batal' value='Batal' onclick=\"window.location.href='index.php?module=penyakit';\"></td></tr>
                  </table></form>";
            break;

        case "editpenyakit":
            if (!isset($_GET['id'])) {
                if(function_exists('set_alert')) set_alert('danger', 'Kode penyakit tidak ditemukan untuk diedit.');
                // Pengalihan sebaiknya dilakukan dengan header jika memungkinkan, tapi karena mungkin sudah ada output:
                echo "<script>window.location.href='index.php?module=penyakit';</script>";
                exit();
            }
            $kode_penyakit_edit = $_GET['id'];
            $stmt_edit = $conn->prepare("SELECT * FROM penyakit WHERE kode_penyakit = ?");
            $r = null;
            if ($stmt_edit) {
                $stmt_edit->bind_param("s", $kode_penyakit_edit);
                $stmt_edit->execute();
                $result_edit = $stmt_edit->get_result();
                $r = $result_edit->fetch_assoc();
                $stmt_edit->close();

                if ($r) {
                    $gambar_path = 'gambar/noimage.png'; // Default
                    if (!empty($r['gambar']) && file_exists('gambar/penyakit/' . $r['gambar'])) {
                        $gambar_path = 'gambar/penyakit/' . htmlspecialchars($r['gambar']);
                    }

                    echo "<form name='text_form' method='POST' action='$aksi?module=penyakit&act=update' onsubmit='return Blank_TextField_Validator()' enctype='multipart/form-data'>
                          <input type='hidden' name='id' value='" . htmlspecialchars($r['kode_penyakit']) . "'>
                          <br><br><table class='table table-bordered'>
                          <tr><td width='150'>Nama Penyakit</td><td><input autocomplete='off' type='text' class='form-control' name='nama_penyakit' size='60' value=\"" . htmlspecialchars($r['nama_penyakit']) . "\" required></td></tr>
                          <tr><td>Detail Penyakit</td><td><textarea rows='4' class='form-control' name='det_penyakit'>" . htmlspecialchars($r['det_penyakit']) . "</textarea></td></tr>
                          <tr><td>Saran Penyakit</td><td><textarea rows='4' class='form-control' name='srn_penyakit'>" . htmlspecialchars($r['srn_penyakit']) . "</textarea></td></tr>
                          <tr><td>Gambar Saat Ini</td><td><img id='preview' src='$gambar_path' width='150' alt='Preview Gambar'></td></tr>    
                          <tr><td>Ganti Gambar</td><td>Upload Gambar Baru (Kosongkan jika tidak ingin ganti, Maks = 1MB, tipe: JPG, PNG, GIF): <input id='uploadImagePenyakit' type='file' class='form-control' name='gambar' accept='image/jpeg,image/png,image/gif'></td></tr>      
                          <tr><td></td><td><input class='btn btn-success' type='submit' name='submit' value='Simpan Perubahan'>
                          <input class='btn btn-danger' type='button' name='batal' value='Batal' onclick=\"window.location.href='index.php?module=penyakit';\"></td></tr>
                          </table></form>";
                } else {
                    if(function_exists('set_alert')) set_alert('danger', 'Data penyakit tidak ditemukan.');
                    echo "<div class='alert alert-danger' style='margin:15px;'>Data penyakit tidak ditemukan. <a href='index.php?module=penyakit'>Kembali</a></div>";
                }
            } else {
                error_log("Error preparing edit query penyakit: " . $conn->error);
                echo "<div class='alert alert-danger' style='margin:15px;'>Terjadi kesalahan saat mengambil data untuk diedit.</div>";
            }
            break;
    } // end switch
} // end else (if $conn)
?>

<script type="text/javascript">
// Pastikan jQuery dimuat sebelum skrip ini
// Lebih baik jika skrip ini berada di index.php setelah jQuery dimuat, atau di-load secara kondisional
if (typeof jQuery != 'undefined') {
    $(document).ready(function(){
        function readURLPenyakit(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#preview').attr('src', e.target.result).width(150); 
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#uploadImagePenyakit").change(function(){ // Ubah ID agar lebih spesifik
            readURLPenyakit(this);
        });
    });
} else {
    console.error("jQuery belum dimuat. Preview gambar penyakit tidak akan berfungsi.");
}
</script>