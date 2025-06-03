<?php
// session_start(); // Diasumsikan sudah dipanggil oleh index.php
if (!(isset($_SESSION['username']) && isset($_SESSION['password']))) {
    // index.php akan menghalangi akses
}
?>
<title>Manajemen Post - Chirexs 1.0</title>
<script type="text/javascript">
//<![CDATA[
function Blank_TextField_Validator() {
    var namaPost = document.forms["text_form"]["nama_post"].value;
    if (namaPost.trim() === "") {
        alert("Nama Post tidak boleh kosong !");
        document.forms["text_form"]["nama_post"].focus();
        return false;
    }
    // CKEditor validation (simple check if it's empty)
    if (typeof CKEDITOR !== 'undefined') {
        var detPost = CKEDITOR.instances.editor1.getData();
        var srnPost = CKEDITOR.instances.editor2.getData();
        if (detPost.trim() === '') {
            alert("Detail Post tidak boleh kosong !");
            // CKEDITOR.instances.editor1.focus(); // Focusing CKEditor is a bit more complex
            return false;
        }
        // Anda bisa menambahkan validasi untuk srnPost jika diperlukan
    }
    return true;
}

function Blank_TextField_Validator_Cari() {
    var keyword = document.forms["text_form_cari"]["keyword"].value;
    if (keyword.trim() === "") {
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
$aksi = "modul/post/aksi_post.php";

if (!$conn || !($conn instanceof mysqli)) {
    echo "<div class='alert alert-danger' style='margin:15px;'>Koneksi database gagal. Silakan hubungi administrator.</div>";
} else {

    if (function_exists('display_alert')) {
        display_alert();
    }

    $act = isset($_GET['act']) ? $_GET['act'] : 'default';

    switch ($act) {
        default:
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $limit = 15; // Jumlah data per halaman

            echo "<form method='POST' action='index.php?module=post' name='text_form_cari' onsubmit='return Blank_TextField_Validator_Cari()'>
                  <br><br><table class='table table-bordered'>
                  <tr><td>
                      <a class='btn bg-olive margin' role='button' href='index.php?module=post&act=tambahpost'>Tambah Post</a>
                      <input type='text' name='keyword' style='margin-left: 10px; width: 250px; display: inline-block;' placeholder='Ketik nama post...' class='form-control' value='" . (isset($_POST['keyword']) ? htmlspecialchars($_POST['keyword']) : (isset($_GET['keyword_search']) ? htmlspecialchars($_GET['keyword_search']) : '')) . "' /> 
                      <input class='btn bg-olive margin' type='submit' value='Cari' name='Go'>
                  </td></tr>
                  </table></form>";

            $query_basis = "SELECT * FROM post";
            $kondisi_where = "";
            $params_total = [];
            $types_total = "";
            $keyword_for_paging = "";

            if (isset($_POST['Go']) && !empty(trim($_POST['keyword']))) {
                $keyword_search_term = "%" . trim($_POST['keyword']) . "%";
                $kondisi_where = " WHERE nama_post LIKE ?";
                $params_total[] = $keyword_search_term;
                $types_total .= "s";
                $keyword_for_paging = urlencode(trim($_POST['keyword']));
            } elseif (isset($_GET['keyword_search']) && !empty(trim($_GET['keyword_search']))) { 
                $keyword_search_term = "%" . trim($_GET['keyword_search']) . "%";
                $kondisi_where = " WHERE nama_post LIKE ?";
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
                } else { error_log("Post - Error executing total count query: " . $stmt_total->error); }
                $stmt_total->close();
            } else { error_log("Post - Error preparing total count query: " . $conn->error); }

            if (isset($_POST['Go']) && !empty(trim($_POST['keyword']))) {
                if ($baris > 0) {
                    if(function_exists('set_alert')) set_alert('success', 'Post yang Anda cari ditemukan.');
                } else {
                    if(function_exists('set_alert')) set_alert('danger', 'Maaf, Post yang Anda cari tidak ditemukan.');
                }
                if(function_exists('display_alert')) display_alert();
            }

            if ($baris > 0) {
                echo "<table class='table table-bordered table-striped' style='overflow-x:auto;'>
                      <thead>
                        <tr>
                          <th>No</th>
                          <th>Nama Post</th>
                          <th>Detail Post (Ringkasan)</th>
                          <th>Saran Post (Ringkasan)</th>
                          <th>Gambar</th>
                          <th width='15%'>Aksi</th>
                        </tr>
                      </thead>
                      <tbody>";
                
                $query_data = $query_basis . $kondisi_where . " ORDER BY kode_post DESC LIMIT ?, ?"; // Biasanya post terbaru di atas
                $stmt_data = $conn->prepare($query_data);
                
                if ($stmt_data) {
                    $current_offset_data = $offset;
                    $current_limit_data = $limit;
                    $params_data = $params_total; 
                    $types_data = $types_total;
                    $params_data[] = $current_offset_data; $types_data .= "i";
                    $params_data[] = $current_limit_data; $types_data .= "i";
                    
                    $stmt_data->bind_param($types_data, ...$params_data);
                    
                    if($stmt_data->execute()){
                        $hasil = $stmt_data->get_result();
                        $no = 1 + $offset;
                        while ($r = $hasil->fetch_assoc()) {
                            $warna = ($no % 2 == 0) ? "even-row" : "odd-row";
                            // Strip tags dan batasi panjang untuk preview
                            $detail_preview = strip_tags($r['det_post']);
                            $saran_preview = strip_tags($r['srn_post']);
                            $detail_preview = strlen($detail_preview) > 100 ? substr($detail_preview, 0, 97) . "..." : $detail_preview;
                            $saran_preview = strlen($saran_preview) > 100 ? substr($saran_preview, 0, 97) . "..." : $saran_preview;

                            echo "<tr class='" . $warna . "'>
                                   <td align='center'>$no</td>
                                   <td>" . htmlspecialchars($r['nama_post']) . "</td>
                                   <td>" . htmlspecialchars($detail_preview) . "</td>
                                   <td>" . htmlspecialchars($saran_preview) . "</td>
                                   <td align='center'>";
                            if (!empty($r['gambar']) && file_exists("gambar/" . $r['gambar'])) { // Sesuaikan path jika folder gambar post berbeda
                                echo "<img src='gambar/" . htmlspecialchars($r['gambar']) . "' alt='" . htmlspecialchars($r['nama_post']) . "' style='width:100px; height:auto; max-height:70px; object-fit:cover;'>";
                            } else {
                                echo "Tidak ada<br>gambar";
                            }
                            echo "</td>
                                   <td align='center' style='min-width:120px;'>
                                     <a type='button' class='btn btn-sm btn-success margin' href='index.php?module=post&act=editpost&id=" . htmlspecialchars($r['kode_post']) . "'><i class='fa fa-pencil-square-o'></i> Ubah</a>
                                     <a type='button' class='btn btn-sm btn-danger margin' href='" . $aksi . "?module=post&act=hapus&id=" . htmlspecialchars($r['kode_post']) . "' onclick=\"return confirm('Anda yakin akan menghapus post: " . htmlspecialchars($r['nama_post'], ENT_QUOTES) . " ?');\"><i class='fa fa-trash-o'></i> Hapus</a>
                                   </td>
                                 </tr>";
                            $no++;
                        }
                    } else {
                        error_log("Post - Error executing data query: " . $stmt_data->error);
                        echo "<tr><td colspan='6' class='alert alert-danger'>Terjadi kesalahan saat mengambil data post.</td></tr>";
                    }
                    $stmt_data->close();
                } else {
                    error_log("Post - Error preparing data query: " . $conn->error);
                    echo "<tr><td colspan='6' class='alert alert-danger'>Terjadi kesalahan sistem.</td></tr>";
                }
                echo "</tbody></table>";

                // Paging
                echo "<div class='paging text-center'>";
                $keyword_param_for_paging_link = !empty($keyword_for_paging) ? "&keyword_search=" . $keyword_for_paging : "";

                if ($offset != 0) {
                    $prevoffset = $offset - $limit;
                    if ($prevoffset < 0) $prevoffset = 0;
                    echo "<span class='prevnext'> <a href='index.php?module=post&offset=$prevoffset" . $keyword_param_for_paging_link . "'>Back</a></span>";
                } else {
                    echo "<span class='disabled'>Back</span>";
                }

                $halaman = ceil($baris / $limit);
                for ($i = 1; $i <= $halaman; $i++) {
                    $newoffset = $limit * ($i - 1);
                    if ($offset != $newoffset) {
                        echo "<a href='index.php?module=post&offset=$newoffset" . $keyword_param_for_paging_link . "'>$i</a>";
                    } else {
                        echo "<span class='current'>$i</span>";
                    }
                }

                if (($offset + $limit) < $baris) {
                    $newoffset = $offset + $limit;
                    echo "<span class='prevnext'><a href='index.php?module=post&offset=$newoffset" . $keyword_param_for_paging_link . "'>Next</a></span>";
                } else {
                    echo "<span class='disabled'>Next</span>";
                }
                echo "</div>";

            } else {
                 if (!(isset($_POST['Go']) && !empty(trim($_POST['keyword'])))) {
                    echo "<br><b>Data Post Kosong !</b>";
                 }
            }
            break;
        
        case "tambahpost":
            echo "<form name='text_form' method='POST' action='$aksi?module=post&act=input' onsubmit='return Blank_TextField_Validator()' enctype='multipart/form-data'>
                  <br><br><table class='table table-bordered'>
                  <tr><td width='120'>Nama Post</td><td><input autocomplete='off' type='text' placeholder='Masukkan judul post...' class='form-control' name='nama_post' size='60' required></td></tr>
                  <tr><td>Detail Post</td><td> <textarea id='editor1' rows='10' class='form-control' name='det_post' placeholder='Masukkan detail post baru...'></textarea></td></tr>
                  <tr><td>Saran Post</td><td><textarea id='editor2' rows='6' class='form-control' name='srn_post' placeholder='Masukkan saran post baru...'></textarea></td></tr>
                  <tr><td>Gambar Post</td><td>Upload Gambar (Maks 1MB, JPG/PNG/GIF): <input type='file' class='form-control' name='gambar' accept='image/jpeg,image/png,image/gif'></td></tr>
                  <tr><td></td><td><input class='btn btn-success' type='submit' name='submit' value='Simpan'>
                  <input class='btn btn-danger' type='button' name='batal' value='Batal' onclick=\"window.location.href='index.php?module=post';\"></td></tr>
                  </table></form>";
            break;
            
        case "editpost":
            if (!isset($_GET['id'])) {
                if(function_exists('set_alert')) set_alert('danger', 'Kode post tidak ditemukan untuk diedit.');
                echo "<script>window.location.href='index.php?module=post';</script>";
                exit();
            }
            $kode_post_edit = $_GET['id'];
            $stmt_edit = $conn->prepare("SELECT * FROM post WHERE kode_post = ?");
            $r_edit_post = null;
            if ($stmt_edit) {
                $stmt_edit->bind_param("s", $kode_post_edit);
                if ($stmt_edit->execute()) {
                    $result_edit = $stmt_edit->get_result();
                    $r_edit_post = $result_edit->fetch_assoc();
                } else { error_log("Post - Error executing edit query: " . $stmt_edit->error); }
                $stmt_edit->close();
            } else { error_log("Post - Error preparing edit query: " . $conn->error); }

            if ($r_edit_post) {
                $gambar_path_edit = 'gambar/noimage.png'; 
                if (!empty($r_edit_post['gambar']) && file_exists('gambar/' . $r_edit_post['gambar'])) { // Sesuaikan path jika perlu
                    $gambar_path_edit = 'gambar/' . htmlspecialchars($r_edit_post['gambar']);
                }

                echo "<form name='text_form' method='POST' action='$aksi?module=post&act=update' onsubmit='return Blank_TextField_Validator()' enctype='multipart/form-data'>
                      <input type='hidden' name='id' value='" . htmlspecialchars($r_edit_post['kode_post']) . "'>
                      <br><br><table class='table table-bordered'>
                      <tr><td width='120'>Nama Post</td><td><input autocomplete='off' type='text' class='form-control' name='nama_post' size='60' value=\"" . htmlspecialchars($r_edit_post['nama_post']) . "\" required></td></tr>
                      <tr><td>Detail Post</td><td><textarea id='editor1' rows='10' class='form-control' name='det_post'>" . htmlspecialchars($r_edit_post['det_post']) . "</textarea></td></tr>
                      <tr><td>Saran Post</td><td><textarea id='editor2' rows='6' class='form-control' name='srn_post'>" . htmlspecialchars($r_edit_post['srn_post']) . "</textarea></td></tr>
                      <tr><td>Gambar Saat Ini</td><td><img id='previewPost' src='$gambar_path_edit' width='200' alt='Preview Gambar'></td></tr>          
                      <tr><td>Ganti Gambar</td><td>Upload Gambar Baru (Kosongkan jika tidak ingin ganti, Maks 1MB, JPG/PNG/GIF): <input id='uploadPostImage' type='file' class='form-control' name='gambar' accept='image/jpeg,image/png,image/gif'></td></tr>
                      <tr><td></td><td><input class='btn btn-success' type='submit' name='submit' value='Simpan Perubahan'>
                      <input class='btn btn-danger' type='button' name='batal' value='Batal' onclick=\"window.location.href='index.php?module=post';\"></td></tr>
                      </table></form>";
            } else {
                if(function_exists('set_alert')) set_alert('danger', 'Data post tidak ditemukan.');
                if(function_exists('display_alert')) display_alert();
                echo "<div class='alert alert-danger' style='margin:15px;'>Data post tidak ditemukan atau terjadi kesalahan. <a href='index.php?module=post'>Kembali</a></div>";
            }
            break;  
    } // end switch
} // end else ($conn)
?>

<script type="text/javascript">
if (typeof jQuery != 'undefined') {
    $(document).ready(function(){
        function readURLPost(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#previewPost').attr('src', e.target.result).width(200);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#uploadPostImage").change(function(){ // ID spesifik untuk upload gambar post
            readURLPost(this);
        });

        // Inisialisasi CKEditor jika elemen ada di halaman
        if (typeof CKEDITOR !== 'undefined') {
            if (document.getElementById('editor1')) {
                CKEDITOR.replace('editor1');
            }
            if (document.getElementById('editor2')) {
                CKEDITOR.replace('editor2');
            }
            // Hapus 'editor1a' dan 'editor2a' jika tidak digunakan atau pastikan elemennya ada
            // if (document.getElementById('editor1a')) {
            //     CKEDITOR.replace('editor1a');
            // }
            // if (document.getElementById('editor2a')) {
            //     CKEDITOR.replace('editor2a');
            // }
        } else {
            console.warn("CKEditor belum dimuat. Textarea tidak akan menjadi rich text editor.");
        }
    });
} else {
    console.error("jQuery belum dimuat. Fungsi preview gambar dan inisialisasi CKEditor mungkin tidak berfungsi.");
}
</script>