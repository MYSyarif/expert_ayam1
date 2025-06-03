<?php
// session_start(); // Diasumsikan sudah dipanggil oleh index.php
if (!(isset($_SESSION['username']) && isset($_SESSION['password']))) {
    // index.php akan menghalangi akses jika sesi tidak ada
}
?>
<title>Manajemen Basis Pengetahuan - Chirexs 1.0</title>
<script type="text/javascript">
//<![CDATA[
function Blank_TextField_Validator() {
    if (document.forms["text_form"]["kode_penyakit"].value === "") { // Perbandingan ketat dan akses form yang benar
        alert("Pilih dulu penyakit !");
        document.forms["text_form"]["kode_penyakit"].focus();
        return false;
    }
    if (document.forms["text_form"]["kode_gejala"].value === "") {
        alert("Pilih dulu gejala !");
        document.forms["text_form"]["kode_gejala"].focus();
        return false;
    }
    if (document.forms["text_form"]["mb"].value.trim() === "") {
        alert("Isi dulu MB !");
        document.forms["text_form"]["mb"].focus();
        return false;
    }
    if (isNaN(parseFloat(document.forms["text_form"]["mb"].value))) {
        alert("MB harus berupa angka!");
        document.forms["text_form"]["mb"].focus();
        return false;
    }
    if (document.forms["text_form"]["md"].value.trim() === "") {
        alert("Isi dulu MD !");
        document.forms["text_form"]["md"].focus();
        return false;
    }
    if (isNaN(parseFloat(document.forms["text_form"]["md"].value))) {
        alert("MD harus berupa angka!");
        document.forms["text_form"]["md"].focus();
        return false;
    }
    return true;
}

function Blank_TextField_Validator_Cari() {
    if (document.forms["text_form_cari"]["keyword"].value.trim() === "") { // Ganti nama form jika perlu
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
$aksi = "modul/pengetahuan/aksi_pengetahuan.php";

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
            $limit = 15;

            echo "<form method='POST' action='index.php?module=pengetahuan' name='text_form_cari' onsubmit='return Blank_TextField_Validator_Cari()'>
                  <br><br><table class='table table-bordered'>
                  <tr><td>
                      <a class='btn bg-olive margin' role='button' href='index.php?module=pengetahuan&act=tambahpengetahuan'>Tambah Basis Pengetahuan</a>
                      <input type='text' name='keyword' style='margin-left: 10px; width: 250px; display: inline-block;' placeholder='Cari berdasarkan nama penyakit...' class='form-control' value='" . (isset($_POST['keyword']) ? htmlspecialchars($_POST['keyword']) : (isset($_GET['keyword_search']) ? htmlspecialchars($_GET['keyword_search']) : '')) . "' /> 
                      <input class='btn bg-olive margin' type='submit' value='Cari' name='Go'>
                  </td></tr>
                  </table></form>";

            $query_basis = "SELECT bp.*, p.nama_penyakit, g.nama_gejala 
                            FROM basis_pengetahuan bp 
                            JOIN penyakit p ON bp.kode_penyakit = p.kode_penyakit 
                            JOIN gejala g ON bp.kode_gejala = g.kode_gejala";
            $kondisi_where = "";
            $params_total = [];
            $types_total = "";
            $keyword_for_paging = "";

            if (isset($_POST['Go']) && !empty(trim($_POST['keyword']))) {
                $keyword_search_term = "%" . trim($_POST['keyword']) . "%";
                $kondisi_where = " WHERE p.nama_penyakit LIKE ?";
                $params_total[] = $keyword_search_term;
                $types_total .= "s";
                $keyword_for_paging = urlencode(trim($_POST['keyword']));
            } elseif (isset($_GET['keyword_search']) && !empty(trim($_GET['keyword_search']))) {
                $keyword_search_term = "%" . trim($_GET['keyword_search']) . "%";
                $kondisi_where = " WHERE p.nama_penyakit LIKE ?";
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
                    error_log("Pengetahuan - Error executing total count query: " . $stmt_total->error);
                }
                $stmt_total->close();
            } else {
                error_log("Pengetahuan - Error preparing total count query: " . $conn->error);
            }

            if (isset($_POST['Go']) && !empty(trim($_POST['keyword']))) {
                if ($baris > 0) {
                    if(function_exists('set_alert')) set_alert('success', 'Basis Pengetahuan yang Anda cari ditemukan.');
                } else {
                    if(function_exists('set_alert')) set_alert('danger', 'Maaf, Basis Pengetahuan yang Anda cari tidak ditemukan.');
                }
                if(function_exists('display_alert')) display_alert();
            }

            if ($baris > 0) {
                echo "<table class='table table-bordered table-striped' style='overflow-x:auto;'>
                      <thead>
                        <tr>
                          <th>No</th>
                          <th>Penyakit</th>
                          <th>Gejala</th>
                          <th>MB</th>
                          <th>MD</th>
                          <th width='21%'>Aksi</th>
                        </tr>
                      </thead>
                      <tbody>";
                
                $query_data = $query_basis . $kondisi_where . " ORDER BY bp.kode_pengetahuan LIMIT ?, ?";
                $stmt_data = $conn->prepare($query_data);
                
                if ($stmt_data) {
                    $current_offset_data = $offset;
                    $current_limit_data = $limit;

                    $params_data = $params_total; 
                    $types_data = $types_total;
                    $params_data[] = $current_offset_data;
                    $types_data .= "i";
                    $params_data[] = $current_limit_data;
                    $types_data .= "i";
                    
                    $stmt_data->bind_param($types_data, ...$params_data);
                    
                    if($stmt_data->execute()){
                        $hasil = $stmt_data->get_result();
                        $no = 1 + $offset;
                        while ($r = $hasil->fetch_assoc()) {
                            $warna = ($no % 2 == 0) ? "even-row" : "odd-row";
                            echo "<tr class='" . $warna . "'>
                                   <td align='center'>$no</td>
                                   <td>" . htmlspecialchars($r['nama_penyakit']) . "</td>
                                   <td>" . htmlspecialchars($r['nama_gejala']) . "</td>
                                   <td align='center'>" . htmlspecialchars($r['mb']) . "</td>
                                   <td align='center'>" . htmlspecialchars($r['md']) . "</td>
                                   <td align='center' style='min-width:180px;'>
                                     <a type='button' class='btn btn-sm btn-success margin' href='index.php?module=pengetahuan&act=editpengetahuan&id=" . htmlspecialchars($r['kode_pengetahuan']) . "'><i class='fa fa-pencil-square-o'></i> Ubah</a>
                                     <a type='button' class='btn btn-sm btn-danger margin' href='" . $aksi . "?module=pengetahuan&act=hapus&id=" . htmlspecialchars($r['kode_pengetahuan']) . "' onclick=\"return confirm('Anda yakin akan menghapus aturan ini? (" . htmlspecialchars($r['nama_penyakit'], ENT_QUOTES) . " - " . htmlspecialchars($r['nama_gejala'], ENT_QUOTES) . ")');\"><i class='fa fa-trash-o'></i> Hapus</a>
                                   </td>
                                 </tr>";
                            $no++;
                        }
                    } else {
                        error_log("Pengetahuan - Error executing data query: " . $stmt_data->error);
                        echo "<tr><td colspan='6' class='alert alert-danger'>Terjadi kesalahan saat mengambil data pengetahuan.</td></tr>";
                    }
                    $stmt_data->close();
                } else {
                     error_log("Pengetahuan - Error preparing data query: " . $conn->error);
                     echo "<tr><td colspan='6' class='alert alert-danger'>Terjadi kesalahan sistem.</td></tr>";
                }
                echo "</tbody></table>";

                // Paging
                echo "<div class='paging text-center'>";
                $keyword_param_for_paging_link = !empty($keyword_for_paging) ? "&keyword_search=" . $keyword_for_paging : "";

                if ($offset != 0) {
                    $prevoffset = $offset - $limit;
                    if ($prevoffset < 0) $prevoffset = 0;
                    echo "<span class='prevnext'> <a href='index.php?module=pengetahuan&offset=$prevoffset" . $keyword_param_for_paging_link . "'>Back</a></span>";
                } else {
                    echo "<span class='disabled'>Back</span>";
                }

                $halaman = ceil($baris / $limit);
                for ($i = 1; $i <= $halaman; $i++) {
                    $newoffset = $limit * ($i - 1);
                    if ($offset != $newoffset) {
                        echo "<a href='index.php?module=pengetahuan&offset=$newoffset" . $keyword_param_for_paging_link . "'>$i</a>";
                    } else {
                        echo "<span class='current'>$i</span>";
                    }
                }

                if (($offset + $limit) < $baris) {
                    $newoffset = $offset + $limit;
                    echo "<span class='prevnext'><a href='index.php?module=pengetahuan&offset=$newoffset" . $keyword_param_for_paging_link . "'>Next</a></span>";
                } else {
                    echo "<span class='disabled'>Next</span>";
                }
                echo "</div>";

            } else {
                 if (!(isset($_POST['Go']) && !empty(trim($_POST['keyword'])))) {
                    echo "<br><b>Data Basis Pengetahuan Kosong !</b>";
                 }
            }
            break;
        
        case "tambahpengetahuan":
            echo "<div class='alert alert-info alert-dismissible'>
                    <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
                    <h4><i class='icon fa fa-info-circle'></i> Petunjuk Pengisian Pakar !</h4>
                    Silahkan pilih penyakit dan gejala yang sesuai, lalu berikan <b>nilai MB (Measure of Increased Belief)</b> dan <b>MD (Measure of Increased Disbelief)</b>.<br>
                    Rentang nilai adalah 0.0 hingga 1.0 (gunakan titik sebagai pemisah desimal).<br><br>
                    <b>Contoh:</b> MB = 0.8, MD = 0.2. Maka CF Pakar = MB - MD = 0.6.
                  </div>
                  <form name='text_form' method='POST' action='$aksi?module=pengetahuan&act=input' onsubmit='return Blank_TextField_Validator()'>
                  <br><br><table class='table table-bordered'>
                  <tr><td width='120'>Penyakit</td><td><select class='form-control' name='kode_penyakit' id='kode_penyakit' required><option value=''>- Pilih Penyakit -</option>";
            $sql_penyakit = "SELECT kode_penyakit, nama_penyakit FROM penyakit ORDER BY nama_penyakit";
            $hasil_penyakit = $conn->query($sql_penyakit);
            if ($hasil_penyakit && $hasil_penyakit->num_rows > 0) {
                while($r_penyakit = $hasil_penyakit->fetch_assoc()){
                    echo "<option value='" . htmlspecialchars($r_penyakit['kode_penyakit']) . "'>" . htmlspecialchars($r_penyakit['nama_penyakit']) . "</option>";
                }
            }
            echo    "</select></td></tr>
                    <tr><td>Gejala</td><td><select class='form-control' name='kode_gejala' id='kode_gejala' required><option value=''>- Pilih Gejala -</option>";
            $sql_gejala = "SELECT kode_gejala, nama_gejala FROM gejala ORDER BY nama_gejala";
            $hasil_gejala = $conn->query($sql_gejala);
            if ($hasil_gejala && $hasil_gejala->num_rows > 0) {
                while($r_gejala = $hasil_gejala->fetch_assoc()){
                    echo "<option value='" . htmlspecialchars($r_gejala['kode_gejala']) . "'>" . htmlspecialchars($r_gejala['nama_gejala']) . "</option>";
                }
            }
            echo    "</select></td></tr>
                    <tr><td>MB</td><td><input autocomplete='off' placeholder='Masukkan MB (Contoh: 0.8)' type='text' class='form-control' name='mb' size='15' required pattern='[0-1](\.[0-9]+)?' title='Masukkan angka antara 0.0 dan 1.0'></td></tr>
                    <tr><td>MD</td><td><input autocomplete='off' placeholder='Masukkan MD (Contoh: 0.2)' type='text' class='form-control' name='md' size='15' required pattern='[0-1](\.[0-9]+)?' title='Masukkan angka antara 0.0 dan 1.0'></td></tr>
                    <tr><td></td><td><input class='btn btn-success' type='submit' name='submit' value='Simpan'>
                    <input class='btn btn-danger' type='button' name='batal' value='Batal' onclick=\"window.location.href='index.php?module=pengetahuan';\"></td></tr>
                  </table></form>";
            break;
            
        case "editpengetahuan":
            if (!isset($_GET['id'])) {
                if(function_exists('set_alert')) set_alert('danger', 'Kode pengetahuan tidak ditemukan untuk diedit.');
                echo "<script>window.location.href='index.php?module=pengetahuan';</script>";
                exit();
            }
            $kode_pengetahuan_edit = $_GET['id'];
            $stmt_edit = $conn->prepare("SELECT * FROM basis_pengetahuan WHERE kode_pengetahuan = ?");
            $r_edit_bp = null;
             if ($stmt_edit) {
                $stmt_edit->bind_param("s", $kode_pengetahuan_edit);
                 if ($stmt_edit->execute()) {
                    $result_edit = $stmt_edit->get_result();
                    $r_edit_bp = $result_edit->fetch_assoc();
                } else {
                    error_log("Pengetahuan - Error executing edit query: " . $stmt_edit->error);
                }
                $stmt_edit->close();
            } else {
                error_log("Pengetahuan - Error preparing edit query: " . $conn->error);
            }

            if ($r_edit_bp) {
                echo "<br><br>
                      <form name='text_form' method='POST' action='$aksi?module=pengetahuan&act=update' onsubmit='return Blank_TextField_Validator()'>
                      <input type='hidden' name='id' value='" . htmlspecialchars($r_edit_bp['kode_pengetahuan']) . "'>
                      <table class='table table-bordered'>
                      <tr><td width='120'>Penyakit</td><td><select class='form-control' name='kode_penyakit' id='kode_penyakit' required>";
                $sql_penyakit_edit = "SELECT kode_penyakit, nama_penyakit FROM penyakit ORDER BY nama_penyakit";
                $hasil_penyakit_edit = $conn->query($sql_penyakit_edit);
                if ($hasil_penyakit_edit && $hasil_penyakit_edit->num_rows > 0) {
                    while($r_penyakit_opt = $hasil_penyakit_edit->fetch_assoc()){
                        $selected = ($r_edit_bp['kode_penyakit'] == $r_penyakit_opt['kode_penyakit']) ? "selected" : "";
                        echo "<option value='" . htmlspecialchars($r_penyakit_opt['kode_penyakit']) . "' $selected>" . htmlspecialchars($r_penyakit_opt['nama_penyakit']) . "</option>";
                    }
                }
                echo    "</select></td></tr>
                        <tr><td>Gejala</td><td><select class='form-control' name='kode_gejala' id='kode_gejala' required>";
                $sql_gejala_edit = "SELECT kode_gejala, nama_gejala FROM gejala ORDER BY nama_gejala";
                $hasil_gejala_edit = $conn->query($sql_gejala_edit);
                 if ($hasil_gejala_edit && $hasil_gejala_edit->num_rows > 0) {
                    while($r_gejala_opt = $hasil_gejala_edit->fetch_assoc()){
                        $selected = ($r_edit_bp['kode_gejala'] == $r_gejala_opt['kode_gejala']) ? "selected" : "";
                        echo "<option value='" . htmlspecialchars($r_gejala_opt['kode_gejala']) . "' $selected>" . htmlspecialchars($r_gejala_opt['nama_gejala']) . "</option>";
                    }
                }
                echo    "</select></td></tr>
                        <tr><td>MB</td><td><input autocomplete='off' placeholder='Masukkan MB' type='text' class='form-control' name='mb' size='15' value='" . htmlspecialchars($r_edit_bp['mb']) . "' required pattern='[0-1](\.[0-9]+)?' title='Masukkan angka antara 0.0 dan 1.0'></td></tr>
                        <tr><td>MD</td><td><input autocomplete='off' placeholder='Masukkan MD' type='text' class='form-control' name='md' size='15' value='" . htmlspecialchars($r_edit_bp['md']) . "' required pattern='[0-1](\.[0-9]+)?' title='Masukkan angka antara 0.0 dan 1.0'></td></tr>
                        <tr><td></td><td><input class='btn btn-success' type='submit' name='submit' value='Simpan Perubahan'>
                        <input class='btn btn-danger' type='button' name='batal' value='Batal' onclick=\"window.location.href='index.php?module=pengetahuan';\"></td></tr>
                      </table></form>";
            } else {
                if(function_exists('set_alert')) set_alert('danger', 'Data basis pengetahuan tidak ditemukan.');
                if(function_exists('display_alert')) display_alert();
                echo "<div class='alert alert-danger' style='margin:15px;'>Data basis pengetahuan tidak ditemukan atau terjadi kesalahan. <a href='index.php?module=pengetahuan'>Kembali</a></div>";
            }
            break;  
    } // end switch
} // end else ($conn)
?>