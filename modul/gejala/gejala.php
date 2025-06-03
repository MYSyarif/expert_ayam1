<?php
// session_start(); // Diasumsikan sudah dipanggil oleh index.php
if (!(isset($_SESSION['username']) && isset($_SESSION['password']))) {
    // index.php akan menghalangi akses jika sesi tidak ada
}
?>
<title>Manajemen Gejala - Chirexs 1.0</title>
<script type="text/javascript">
//<![CDATA[
function Blank_TextField_Validator() {
    var namaGejala = document.forms["text_form"]["nama_gejala"].value;
    if (namaGejala.trim() === "") { // Gunakan trim() dan perbandingan ketat
        alert("Nama Gejala tidak boleh kosong !");
        document.forms["text_form"]["nama_gejala"].focus();
        return false;
    }
    return true;
}

function Blank_TextField_Validator_Cari() {
    var keyword = document.forms["text_form_cari"]["keyword"].value; // Sesuaikan nama form jika perlu
    if (keyword.trim() === "") { // Gunakan trim() dan perbandingan ketat
        alert("Isi dulu keyword pencarian !");
        document.forms["text_form_cari"]["keyword"].focus();
        return false;
    }
    return true;
}
//]]>
</script>

<?php
// include "config/fungsi_alert.php"; // Diasumsikan sudah di-include oleh index.php
$aksi = "modul/gejala/aksi_gejala.php";

// Pastikan $conn (koneksi MySQLi) ada dan valid
if (!$conn || !($conn instanceof mysqli)) {
    echo "<div class='alert alert-danger' style='margin:15px;'>Koneksi database gagal. Silakan hubungi administrator.</div>";
} else { // Lanjutkan hanya jika koneksi berhasil

    // Menampilkan alert dari session jika ada
    if (function_exists('display_alert')) {
        display_alert();
    }

    $act = isset($_GET['act']) ? $_GET['act'] : 'default';

    switch ($act) {
        default:
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $limit = 15;

            // Form Pencarian - pastikan nama form konsisten dengan JS (text_form_cari)
            echo "<form method='POST' action='index.php?module=gejala' name='text_form_cari' onsubmit='return Blank_TextField_Validator_Cari()'>
                  <br><br><table class='table table-bordered'>
                  <tr><td>
                      <a class='btn bg-olive margin' role='button' href='index.php?module=gejala&act=tambahgejala'>Tambah Gejala</a>
                      <input type='text' name='keyword' style='margin-left: 10px; width: 250px; display: inline-block;' placeholder='Ketik nama gejala...' class='form-control' value='" . (isset($_POST['keyword']) ? htmlspecialchars($_POST['keyword']) : (isset($_GET['keyword_search']) ? htmlspecialchars($_GET['keyword_search']) : '')) . "' /> 
                      <input class='btn bg-olive margin' type='submit' value='Cari' name='Go'>
                  </td></tr>
                  </table></form>";

            $query_basis = "SELECT * FROM gejala";
            $kondisi_where = "";
            $params_total = []; // Parameter untuk query total
            $types_total = "";  // Tipe parameter untuk query total

            $keyword_for_paging = ""; // Untuk dibawa di link paging

            if (isset($_POST['Go']) && !empty(trim($_POST['keyword']))) {
                $keyword_search_term = "%" . trim($_POST['keyword']) . "%";
                $kondisi_where = " WHERE nama_gejala LIKE ?";
                $params_total[] = $keyword_search_term;
                $types_total .= "s";
                $keyword_for_paging = urlencode(trim($_POST['keyword']));
            } elseif (isset($_GET['keyword_search']) && !empty(trim($_GET['keyword_search']))) { 
                // Untuk mempertahankan pencarian saat paging
                $keyword_search_term = "%" . trim($_GET['keyword_search']) . "%";
                $kondisi_where = " WHERE nama_gejala LIKE ?";
                $params_total[] = $keyword_search_term;
                $types_total .= "s";
                $keyword_for_paging = urlencode(trim($_GET['keyword_search']));
            }
            
            $stmt_total = $conn->prepare($query_basis . $kondisi_where);
            $baris = 0;
            if ($stmt_total) {
                if (!empty($params_total)) {
                    $stmt_total->bind_param($types_total, ...$params_total);
                }
                if ($stmt_total->execute()) {
                    $stmt_total->store_result();
                    $baris = $stmt_total->num_rows;
                } else {
                    error_log("Gejala - Error executing total count query: " . $stmt_total->error);
                }
                $stmt_total->close();
            } else {
                error_log("Gejala - Error preparing total count query: " . $conn->error);
            }
            
            if (isset($_POST['Go']) && !empty(trim($_POST['keyword']))) {
                if ($baris > 0) {
                    if (function_exists('set_alert')) set_alert('success', 'Gejala yang Anda cari ditemukan.');
                } else {
                    if (function_exists('set_alert')) set_alert('danger', 'Maaf, gejala yang Anda cari tidak ditemukan.');
                }
                if (function_exists('display_alert')) display_alert();
            }

            if ($baris > 0) {
                echo "<table class='table table-bordered table-striped' style='overflow-x:auto;'>
                      <thead>
                        <tr>
                          <th>No</th>
                          <th>Nama Gejala</th>
                          <th width='21%'>Aksi</th>
                        </tr>
                      </thead>
                      <tbody>";
                
                $query_data = $query_basis . $kondisi_where . " ORDER BY kode_gejala LIMIT ?, ?";
                $stmt_data = $conn->prepare($query_data);
                
                if ($stmt_data) {
                    $current_offset_data = $offset;
                    $current_limit_data = $limit;

                    $params_data = $params_total; // Mulai dengan parameter pencarian (jika ada)
                    $types_data = $types_total;

                    $params_data[] = $current_offset_data;
                    $types_data .= "i";
                    $params_data[] = $current_limit_data;
                    $types_data .= "i";
                    
                    $stmt_data->bind_param($types_data, ...$params_data);
                    
                    if ($stmt_data->execute()){
                        $hasil = $stmt_data->get_result();
                        $no = 1 + $offset;
                        while ($r = $hasil->fetch_assoc()) {
                            $warna = ($no % 2 == 0) ? "even-row" : "odd-row"; // Gunakan class CSS
                            echo "<tr class='" . $warna . "'>
                                   <td align='center'>$no</td>
                                   <td>" . htmlspecialchars($r['nama_gejala']) . "</td>
                                   <td align='center' style='min-width:180px;'>
                                     <a type='button' class='btn btn-sm btn-success margin' href='index.php?module=gejala&act=editgejala&id=" . htmlspecialchars($r['kode_gejala']) . "'><i class='fa fa-pencil-square-o'></i> Ubah</a>
                                     <a type='button' class='btn btn-sm btn-danger margin' href='" . $aksi . "?module=gejala&act=hapus&id=" . htmlspecialchars($r['kode_gejala']) . "' onclick=\"return confirm('Anda yakin akan menghapus gejala: " . htmlspecialchars($r['nama_gejala'], ENT_QUOTES) . " ?');\"><i class='fa fa-trash-o'></i> Hapus</a>
                                   </td>
                                 </tr>";
                            $no++;
                        }
                    } else {
                         error_log("Gejala - Error executing data query: " . $stmt_data->error);
                         echo "<tr><td colspan='3' class='alert alert-danger'>Terjadi kesalahan saat mengambil data gejala.</td></tr>";
                    }
                    $stmt_data->close();
                } else {
                    error_log("Gejala - Error preparing data query: " . $conn->error);
                    echo "<tr><td colspan='3' class='alert alert-danger'>Terjadi kesalahan sistem.</td></tr>";
                }
                echo "</tbody></table>";

                // Paging
                echo "<div class='paging text-center'>";
                $keyword_param_for_paging_link = !empty($keyword_for_paging) ? "&keyword_search=" . $keyword_for_paging : "";

                if ($offset != 0) {
                    $prevoffset = $offset - $limit;
                    if ($prevoffset < 0) $prevoffset = 0;
                    echo "<span class='prevnext'> <a href='index.php?module=gejala&offset=$prevoffset" . $keyword_param_for_paging_link . "'>Back</a></span>";
                } else {
                    echo "<span class='disabled'>Back</span>";
                }

                $halaman = ceil($baris / $limit);
                for ($i = 1; $i <= $halaman; $i++) {
                    $newoffset = $limit * ($i - 1);
                    if ($offset != $newoffset) {
                        echo "<a href='index.php?module=gejala&offset=$newoffset" . $keyword_param_for_paging_link . "'>$i</a>";
                    } else {
                        echo "<span class='current'>$i</span>";
                    }
                }

                if (($offset + $limit) < $baris) {
                    $newoffset = $offset + $limit;
                    echo "<span class='prevnext'><a href='index.php?module=gejala&offset=$newoffset" . $keyword_param_for_paging_link . "'>Next</a></span>";
                } else {
                    echo "<span class='disabled'>Next</span>";
                }
                echo "</div>";

            } else {
                 if (!(isset($_POST['Go']) && !empty(trim($_POST['keyword'])))) {
                     echo "<br><b>Data Gejala Kosong !</b>";
                 }
            }
            break;
        
        case "tambahgejala":
            echo "<form name='text_form' method='POST' action='$aksi?module=gejala&act=input' onsubmit='return Blank_TextField_Validator()'>
                  <br><br><table class='table table-bordered'>
                  <tr><td width='120'>Nama Gejala</td><td><input type='text' autocomplete='off' placeholder='Masukkan gejala baru...' class='form-control' name='nama_gejala' size='60' required></td></tr>
                  <tr><td></td><td><input class='btn btn-success' type='submit' name='submit' value='Simpan'>
                  <input class='btn btn-danger' type='button' name='batal' value='Batal' onclick=\"window.location.href='index.php?module=gejala';\"></td></tr>
                  </table></form>";
            break;
            
        case "editgejala":
            if (!isset($_GET['id'])) {
                 if(function_exists('set_alert')) set_alert('danger', 'Kode gejala tidak ditemukan untuk diedit.');
                echo "<script>window.location.href='index.php?module=gejala';</script>";
                exit();
            }
            $kode_gejala_edit = $_GET['id'];
            $stmt_edit = $conn->prepare("SELECT * FROM gejala WHERE kode_gejala = ?");
            $r_edit = null;
            if ($stmt_edit) {
                $stmt_edit->bind_param("s", $kode_gejala_edit);
                if ($stmt_edit->execute()) {
                    $result_edit = $stmt_edit->get_result();
                    $r_edit = $result_edit->fetch_assoc();
                } else {
                     error_log("Gejala - Error executing edit query: " . $stmt_edit->error);
                }
                $stmt_edit->close();
            } else {
                error_log("Gejala - Error preparing edit query: " . $conn->error);
            }

            if ($r_edit) {
                echo "<form name='text_form' method='POST' action='$aksi?module=gejala&act=update' onsubmit='return Blank_TextField_Validator()'>
                      <input type='hidden' name='id' value='" . htmlspecialchars($r_edit['kode_gejala']) . "'>
                      <br><br><table class='table table-bordered'>
                      <tr><td width='120'>Nama Gejala</td><td><input autocomplete='off' type='text' class='form-control' name='nama_gejala' size='60' value=\"" . htmlspecialchars($r_edit['nama_gejala']) . "\" required></td></tr>
                      <tr><td></td><td><input class='btn btn-success' type='submit' name='submit' value='Simpan Perubahan'>
                      <input class='btn btn-danger' type='button' value='Batal' onclick=\"window.location.href='index.php?module=gejala';\"></td></tr>
                      </table></form>";
            } else {
                 if(function_exists('set_alert')) set_alert('danger', 'Data gejala tidak ditemukan.');
                 if(function_exists('display_alert')) display_alert();
                 echo "<div class='alert alert-danger' style='margin:15px;'>Data gejala tidak ditemukan atau terjadi kesalahan. <a href='index.php?module=gejala'>Kembali</a></div>";
            }
            break;  
    } // end switch
} // end else ($conn)
?>