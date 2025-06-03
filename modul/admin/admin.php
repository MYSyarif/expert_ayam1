<?php
// Fix 1: Check if a session is already active before starting
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
// Adjusted path for redirect if admin.php is in modul/admin/
if (!(isset($_SESSION['username']) && isset($_SESSION['password']))) {
    header('location:../../formlogin.php'); // Redirect to your actual login form
    exit();
}

// Include your database connection file
// Make sure koneksi.php establishes a $conn variable for MySQLi connection
// Corrected path using __DIR__ for robustness
include __DIR__ . "/../../config/koneksi.php"; // Adjust path if admin.php is in a submodule

// Include your custom alert functions (assuming it's in config/)
// If fungsi_alert.php contains alert() or confirm(), you might need to modify it as well.
// For now, the custom message box below will handle form validation alerts.
// For deletion confirmation, I've implemented a custom modal.
// include "../../config/fungsi_alert.php"; // Commented out, as its functionality is replaced/handled here

$aksi = "modul/admin/aksi_admin.php"; // Path to your action script

?>
<title>Admin - Chirexs 1.0</title>

<style>
    /* Custom Message Box Styles */
    .custom-message-box, .custom-confirm-box {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1000; /* Sit on top */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        justify-content: center;
        align-items: center;
    }
    .custom-message-box-content, .custom-confirm-box-content {
        background-color: #fefefe;
        margin: auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%; /* Could be responsive */
        max-width: 400px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        text-align: center;
    }
    .custom-message-box-content h3, .custom-confirm-box-content h3 {
        color: #dc3545; /* Red for error/warning */
        margin-bottom: 15px;
    }
    .custom-message-box-content p, .custom-confirm-box-content p {
        margin-bottom: 20px;
    }
    .custom-message-box-content button, .custom-confirm-box-content button {
        background-color: #007bff;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        margin: 5px;
    }
    .custom-confirm-box-content button.cancel {
        background-color: #6c757d;
    }
    .custom-message-box-content button:hover, .custom-confirm-box-content button:hover {
        opacity: 0.9;
    }
</style>

<script Language="JavaScript">
// Function to show the custom message box
function showMessageBox(title, message, focusElementId = null) {
    document.getElementById('messageBoxTitle').innerText = title;
    document.getElementById('messageBoxText').innerText = message;
    document.getElementById('customMessageBox').style.display = 'flex';
    document.getElementById('messageBoxOkButton').onclick = function() {
        hideMessageBox();
        if (focusElementId) {
            const element = document.getElementById(focusElementId);
            if (element) element.focus();
        }
    };
}

// Function to hide the custom message box
function hideMessageBox() {
    document.getElementById('customMessageBox').style.display = 'none';
}

// Function to show custom confirmation dialog
function showConfirmBox(message, onConfirmCallback) {
    document.getElementById('confirmBoxText').innerText = message;
    document.getElementById('customConfirmBox').style.display = 'flex';
    document.getElementById('confirmBoxOkButton').onclick = function() {
        hideConfirmBox();
        if (typeof onConfirmCallback === 'function') {
            onConfirmCallback();
        }
    };
    document.getElementById('confirmBoxCancelButton').onclick = function() {
        hideConfirmBox();
    };
}

// Function to hide custom confirmation dialog
function hideConfirmBox() {
    document.getElementById('customConfirmBox').style.display = 'none';
}


function Blank_TextField_Validator() {
    if (text_form.username.value == "") {
        showMessageBox("Peringatan!", "Username tidak boleh kosong !", "username");
        return (false);
    }
    if (text_form.password.value == "") {
        showMessageBox("Peringatan!", "Password tidak boleh kosong !", "password");
        return (false);
    }
    return (true);
}

function Blank_TextField_Validator_Cari() {
    if (text_form.keyword.value == "") {
        showMessageBox("Peringatan!", "Isi dulu keyword pencarian !", "keyword");
        return (false);
    }
    return (true);
}
</script>

<?php
// Fix 2: Use $_GET['act'] with quotes
$act = isset($_GET['act']) ? $_GET['act'] : '';

switch($act){
    // Tampil admin
    default:
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        //jumlah data yang ditampilkan perpage
        $limit = 10;
        
        // Use mysqli_query
        $tampil = mysqli_query($conn, "SELECT * FROM admin ORDER BY username");
        
        echo "<br><form method=POST action='?module=admin' name=text_form onsubmit='return Blank_TextField_Validator_Cari()'>
              <br><table class='table table-bordered'>
              <tr><td><input class='btn bg-olive margin' type=button name=tambah value='Tambah Admin' onclick=\"window.location.href='?module=admin&act=tambahadmin';\"><input type=text name='keyword' style='margin-left: 10px;' placeholder='Ketik dan tekan cari...' class='form-control' value='" . (isset($_POST['keyword']) ? htmlspecialchars($_POST['keyword']) : '') . "' /> <input class='btn bg-olive margin' type=submit value='    Cari    ' name=Go></td> </tr>
              </table></form>";
        
        // Use mysqli_num_rows
        $baris = mysqli_num_rows($tampil);

        if (isset($_POST['Go'])){
            $keyword = mysqli_real_escape_string($conn, $_POST['keyword']);
            $numrows_query = mysqli_query($conn, "SELECT * FROM admin WHERE username LIKE '%$keyword%'");
            $numrows = mysqli_num_rows($numrows_query);

            if ($numrows > 0){
                echo "<div class='alert alert-success alert-dismissible'>
                        <h4><i class='icon fa fa-check'></i> Sukses!</h4>
                        Admin yang anda cari di temukan.
                      </div>";
                $i = 1;
                echo" <table class='table table-bordered' style='overflow-x:auto' cellpadding='0' cellspacing='0'>
                      <thead>
                        <tr>
                          <th>No</th>
                          <th>Username</th>
                          <th>Nama Lengkap</th>
                          <th width='21%'>Aksi</th>
                        </tr>
                      </thead>
                      <tbody>";
                
                $hasil = mysqli_query($conn, "SELECT * FROM admin WHERE username LIKE '%$keyword%'");
                $no = 1;
                $counter = 1;
                while ($r = mysqli_fetch_array($hasil)){
                    $warna = ($counter % 2 == 0) ? "light" : "dark"; // Corrected logic for light/dark
                    echo "<tr class='".$warna."'>
                             <td align=center>$no</td>
                             <td>" . htmlspecialchars($r['username']) . "</td>
                             <td>" . htmlspecialchars($r['nama_lengkap']) . "</td>
                             <td align=center>
                                 <a type='button' class='btn btn-success margin' href='?module=admin&act=editadmin&id=" . htmlspecialchars($r['username']) . "'><i class='fa fa-pencil-square-o' aria-hidden='true'></i> Ubah </a> &nbsp;
                                 <a type='button' class='btn btn-danger margin' href='#' onclick=\"showConfirmBox('Anda yakin akan menghapusnya ?', function(){ window.location.href='" . $aksi . "?module=admin&act=hapus&id=" . htmlspecialchars($r['username']) . "'; }); return false;\"><i class='fa fa-trash-o' aria-hidden='true'></i> Hapus</a>
                             </td>
                          </tr>";
                    $no++;
                    $counter++;
                }
                echo "</tbody></table>";
            } else {
                echo "<div class='alert alert-danger alert-dismissible'>
                        <h4><i class='icon fa fa-ban'></i> Gagal!</h4>
                        Maaf, Admin yang anda cari tidak ditemukan , silahkan inputkan dengan benar dan cari kembali.
                      </div>";
            }
        } else {
            if($baris > 0){
                echo" <table class='table table-bordered' style='overflow-x:auto' cellpadding='0' cellspacing='0'>
                      <thead>
                        <tr>
                          <th>No</th>
                          <th>Username</th>
                          <th>Nama Lengkap</th>
                          <th width='21%'>Aksi</th>
                        </tr>
                      </thead>
                      <tbody>";
                
                $hasil = mysqli_query($conn, "SELECT * FROM admin ORDER BY username LIMIT $offset,$limit");
                $no = 1 + $offset;
                $counter = 1;
                while ($r = mysqli_fetch_array($hasil)){
                    $warna = ($counter % 2 == 0) ? "dark" : "light"; // Corrected logic for light/dark
                    echo "<tr class='".$warna."'>
                             <td align=center>$no</td>
                             <td>" . htmlspecialchars($r['username']) . "</td>
                             <td>" . htmlspecialchars($r['nama_lengkap']) . "</td>
                             <td align=center>
                                 <a type='button' class='btn btn-success margin' href='?module=admin&act=editadmin&id=" . htmlspecialchars($r['username']) . "'><i class='fa fa-pencil-square-o' aria-hidden='true'></i> Ubah </a> &nbsp;
                                 <a type='button' class='btn btn-danger margin' href='#' onclick=\"showConfirmBox('Anda yakin akan menghapusnya ?', function(){ window.location.href='" . $aksi . "?module=admin&act=hapus&id=" . htmlspecialchars($r['username']) . "'; }); return false;\"><i class='fa fa-trash-o' aria-hidden='true'></i> Hapus</a>
                             </td>
                          </tr>";
                    $no++;
                    $counter++;
                }
                echo "</tbody></table>";
                echo "<div class=paging>";

                if ($offset != 0) {
                    $prevoffset = $offset - 10;
                    echo "<span class=prevnext> <a href=index.php?module=admin&offset=$prevoffset>Back</a></span>";
                } else {
                    echo "<span class=disabled>Back</span>";
                }
                
                $halaman = intval($baris / $limit);
                if ($baris % $limit) {
                    $halaman++;
                }
                for($i=1;$i<=$halaman;$i++){
                    $newoffset = $limit * ($i-1);
                    if($offset != $newoffset){
                        echo "<a href=index.php?module=admin&offset=$newoffset>$i</a>";
                    } else {
                        echo "<span class=current>".$i."</span>";
                    }
                }

                if(!(($offset/$limit)+1 == $halaman) && $halaman != 1){
                    $newoffset = $offset + $limit;
                    echo "<span class=prevnext><a href=index.php?module=admin&offset=$newoffset>Next</a>";
                } else {
                    echo "<span class=disabled>Next</span>";
                }
                echo "</div>";
            } else {
                echo "<br><b>Data Kosong !</b>";
            }
        }
        break;
    
    case "tambahadmin":
        echo "<form name=text_form method=POST action='" . $aksi . "?module=admin&act=input' onsubmit='return Blank_TextField_Validator()'>
              <br><br><table class='table table-bordered'>
              <tr><td>Nama Lengkap</td>    <td>  <input autocomplete='off' placeholder='Masukkan nama lengkap...' type=text class='form-control' name='nama_lengkap' size=30></td></tr>
              <tr><td>Username</td>    <td>  <input autocomplete='off' placeholder='Masukkan username...' type=text class='form-control' name='username' size=30></td></tr>
              <tr><td>Password</td>    <td> <input autocomplete='off' placeholder='Masukkan password admin...' type=password class='form-control' name='password' size=30></td></tr>
              <tr><td></td><td>
              <input class='btn btn-success' type=submit name=submit value='Simpan' >
              <input class='btn btn-danger' type=button name=batal value='Batal' onclick=\"window.location.href='?module=admin';\">
              </td></tr>
              </table></form>";
        break;
        
    case "editadmin":
        $id = mysqli_real_escape_string($conn, $_GET['id']);
        $edit = mysqli_query($conn, "SELECT * FROM admin WHERE username='$id'");
        $r = mysqli_fetch_array($edit);
        
        echo "<form name=text_form method=POST action='" . $aksi . "?module=admin&act=update' onsubmit='return Blank_TextField_Validator()'>
              <input type=hidden name=id value='" . htmlspecialchars($r['username']) . "'>
              <br><br><table class='table table-bordered'>
              <tr><td>Username</td> <td>  <input autocomplete='off' type=text class='form-control' name='username' value=\"" . htmlspecialchars($r['username']) . "\" size=30></td></tr>
              <tr><td>Nama Lengkap</td> <td>  <input autocomplete='off' type=text class='form-control' name='nama_lengkap' value=\"" . htmlspecialchars($r['nama_lengkap']) . "\" size=30></td></tr>
              <tr><td></td><td>
              <input class='btn btn-success' type=submit name=submit value='Simpan' >
              <input class='btn btn-danger' type=button name=batal value='Batal' onclick=\"window.location.href='?module=admin';\"></td></tr>
              </table></form>";
        break;    
}
?>

<div id="customMessageBox" class="custom-message-box">
    <div class="custom-message-box-content">
        <h3 id="messageBoxTitle"></h3>
        <p id="messageBoxText"></p>
        <button id="messageBoxOkButton">OK</button>
    </div>
</div>

<div id="customConfirmBox" class="custom-confirm-box">
    <div class="custom-confirm-box-content">
        <h3>Konfirmasi</h3>
        <p id="confirmBoxText"></p>
        <button id="confirmBoxOkButton">Ya</button>
        <button id="confirmBoxCancelButton" class="cancel">Tidak</button>
    </div>
</div>
