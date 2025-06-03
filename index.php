<?php
// error_reporting(0); // Nonaktifkan baris ini dengan komentar
error_reporting(E_ALL); // Aktifkan semua pelaporan error
ini_set('display_errors', 1); // Pastikan error ditampilkan (walaupun sudah di php.ini)
ob_start();
session_start();
include "config/koneksi.php";
include "config/fungsi_alert.php";

$module = ''; // Inisialisasi variabel $module dengan nilai default kosong
if (isset($_GET['module'])) { // Periksa apakah parameter 'module' ada di URL
    $module = $_GET['module']; // Jika ada, ambil nilainya
}
?>
<!DOCTYPE html>
<html><head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <base href="http://localhost/sistem_pakar_ayam/">
	<link rel="icon" href="gambar/admin/favicon.png">
    <link href="css/font-awesome-4.2.0/font-awesome-4.2.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="css/owl-carousel/owl.carousel.css" rel="stylesheet"  media="all">
    <link href="css/owl-carousel/owl.theme.css" rel="stylesheet"  media="all">
    <link href="css/magnific-popup.css" type="text/css" rel="stylesheet" media="all" />
    <link href="css/font.css" rel="stylesheet" type="text/css"  media="all">
    <link href="css/fontello.css" rel="stylesheet" type="text/css"  media="all">
    <link href="css/main.css" rel="stylesheet" type="text/css" media="all"/>
    <link rel=stylesheet href="css/paging.css" type="text/css" media=screen>
    <link rel="stylesheet" href="aset/bootstrap.css">
    <link rel="stylesheet" href="aset/AdminLTE.css">
	<link rel="stylesheet" href="aset/cinta.css">
    <link rel="stylesheet" href="aset/Ionicons/css/ionicons.min.css">
    <link rel="stylesheet" href="aset/skins/_all-skins.min.css">
    <link rel="stylesheet" href="aset/custom.css">
    <link rel="stylesheet" href="aset/icheck/green.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <script src="aset/jQuery-2.js"></script>
    <script src="aset/bootstrap.js"></script>
    <script src="aset/icheck/icheck.js"></script>
    <script src="aset/ckeditor/ckeditor.js"></script>
    <script src="aset/Flot/jquery.flot.js"></script>
    <script src="aset/Flot/jquery.flot.resize.js"></script>
    <script src="aset/Flot/jquery.flot.pie.js"></script>
    <script src="aset/Flot/jquery.flot.categories.js"></script> 
    <script src="aset/app.js"></script>
	
  </head>
  <body id="pakarayam" class="hold-transition skin-purple-light sidebar-mini">
    <div class="wrapper">
      <header class="main-header">
        <a href="./" class="logo">
          <span class="logo-mini"><b><i class="fa fa-motorcycle" aria-hidden="true"></i>ES</b></span>
          <span class="logo-lg"><b><i class="fa fa-motorcycle" aria-hidden="true"></i>Expert System Motorcycle</b></span>
        </a>
        <nav class="navbar navbar-static-top" role="navigation">
          <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
          </a>

          <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">
                <?php
                if (isset($_SESSION['username']) && isset($_SESSION['password'])) {
                    // Pastikan $user terdefinisi jika dibutuhkan di sini, misalnya dari session atau query
                    // $user = $_SESSION['username']; // Contoh, jika $user tidak didefinisikan di tempat lain
                    ?>
                  <li class="dropdown user user-menu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                      <img src="gambar/admin/admin.png" class="user-image" alt="User Image"> <?php echo ucfirst($_SESSION['username']); ?>
                      <span class="hidden-xs"><?php // echo isset($user) ? $user : ''; // Tampilkan $user jika ada ?></span>
                    </a>
                    <ul class="dropdown-menu">
                      <li class="user-header">
                        <img src="gambar/admin/admin.png" class="img-circle" alt="User Image">
                        <p>
                         Login sebagai <?php echo ucfirst($_SESSION['username']); ?>
                          <small>Pakar dari Chirexs 1.0</small>
                        </p>
                      </li>
                      <li class="user-body">
						<a <?php if ($module == "bantuan") echo 'class="active"'; ?> href="?module=bantuan"><i class="fa fa-question-circle"></i> <span>Bantuan</span></a>
                        </li>
                      <li class="user-footer"> 
                        <div class="pull-left">
                          <a class="btn btn-default btn-flat <?php if ($module == "tentang") echo 'active'; ?>" href="?module=tentang"><i class="fa fa-info-circle"></i> <span>Tentang</span></a>
                        </div>
                        <div class="pull-right">
                           <a class="btn btn-default btn-flat" href="logout.php" onclick="return confirm('Anda yakin akan logout dari aplikasi ?');"><i class="fa fa-sign-out"></i> <span>LogOut</span></a>
                        </div>
                      </li>
                    </ul>
                  </li>
              <?php } else { ?> 
                  <li><a <?php if ($module == "bantuan") echo 'class="active"'; ?> id="bantu" href="?module=bantuan" data-toggle="tooltip" data-placement="bottom" data-delay='{"show":"300", "hide":"500"}' title="Silahkan klik link berikut, jika anda masih kurang paham tentang penggunaan aplikasi ini !"><i class="fa fa-question-circle"></i> <span>Bantuan</span></a></li>
				  <li class="dropdown messages-menu">
                    <a <?php if ($module == "formlogin") echo 'class="active"'; ?> href="?module=formlogin"><i class="fa fa-sign-in"></i> <span>Login</span></a>
                  </li>
              <?php } ?>
            </ul>
          </div>
        </nav>
      </header>
      <aside class="main-sidebar">
        <section class="sidebar">
          <ul class="sidebar-menu">
            <li class="header">Menu</li>
            <?php include "menu.php"; // Pastikan link di menu.php menggunakan ?module=... ?>
        </section>
        </aside>
      <div class="content-wrapper" style="min-height: 310px;">
        <section class="content-header">
        </section>
        <section class="content">
          <div class="box">
            <div class="box-body">
                <?php include "content.php"; ?>		
            </div>
          </div>
        </section></div><footer class="main-footer">
        <button class="kontak ke-kanan" data-toggle="modal" data-target="#modalForm"> 
            <i class="fa fa-envelope-square"></i> Kontak Kami
        </button>
        <div class="modal fade" id="modalForm" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content" style="max-width: 450px;">
                    <div class="modal-header mdl-kontak">
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true">&times;</span>
                            <span class="sr-only">Tutup</span>
                        </button>
                        <h4 class="text-ket" id="labelModalKu"><i class="fa fa-envelope-square"></i> Kontak Kami</h4>
                    </div>
                    <div class="modal-body">
                        <p class="statusMsg"></p>
                        <form role="form">
                            <div class="form-group">
                                <label for="masukkanNama">Nama:  </label>
                                <input type="text" class="form-control" id="masukkanNama" placeholder="Masukkan nama Anda" style="margin-left: 11px;"/>
                            </div>
                            <div class="form-group">
                                <label for="masukkanEmail">Email:  </label>
                                <input type="email" class="form-control" id="masukkanEmail" placeholder="Masukkan email Anda" style="margin-left: 11px;" />
                            </div>
                            <div class="form-group">
                                <label for="masukkanPesan">Pesan: </label>
                                <textarea class="form-control" id="masukkanPesan" placeholder="Masukkan pesan Anda" style="min-width: 200px; max-height: 250px; min-height: 80px; max-width: 290px; margin-left: 10px; width: 270px; height: 133px;"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn bg-maroon btn-flat" data-dismiss="modal">Keluar</button>
                        <button type="button" class="btn bg-olive btn-flat" onClick="kirimContactForm()">Kirim</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
        function kirimContactForm(){
            var reg = /^[A-Z0-9._%+-]+@([A-Z0-9-]+\.)+[A-Z]{2,4}$/i;
            var nama = $('#masukkanNama').val();
            var email = $('#masukkanEmail').val();
            var pesan = $('#masukkanPesan').val();
            if(nama.trim() == '' ){
                alert('Masukkan nama Anda.');
                $('#masukkanNama').focus();
                return false;
            }else if(email.trim() == '' ){
                alert('Masukkan email Anda.');
                $('#masukkanEmail').focus();
                return false;
            }else if(email.trim() != '' && !reg.test(email)){
                alert('Masukkan email yang valid.');
                $('#masukkanEmail').focus();
                return false;
            }else if(pesan.trim() == '' ){
                alert('Masukkan pesan Anda.');
                $('#masukkanPesan').focus();
                return false;
            }else{
                $.ajax({
                    type:'POST',
                    url:'kirim_form.php',
                    data:'contactFrmSubmit=1&nama='+nama+'&email='+email+'&pesan='+pesan,
                    beforeSend: function () {
                        $('.submitBtn').attr("disabled","disabled"); // Mungkin '.btn.bg-olive.btn-flat' jika ingin lebih spesifik
                        $('.modal-body').css('opacity', '.5');
                    },
                    success:function(msg){
                        if(msg == 'ok'){
                            $('#masukkanNama').val('');
                            $('#masukkanEmail').val('');
                            $('#masukkanPesan').val('');
                            $('.statusMsg').html('<span style="color:green;">Terima kasih telah menghubungi kami.</p>');
                        }else{
                            $('.statusMsg').html('<span style="color:red;">Ada sedikit masalah, silakan coba lagi.</span>');
                        }
                        $('.submitBtn').removeAttr("disabled"); // Mungkin '.btn.bg-olive.btn-flat'
                        $('.modal-body').css('opacity', '');
                    }
                });
            }
        }
        </script>
        <strong><div class="cinta">Copyright © 2017 - Made with <i class="fa fa-heart pulse"></i> by <a href="http://januriawan.github.io" target="_blank">Januriawan</a></div></strong>
      </footer>
      <div class="control-sidebar-bg" style="position: fixed; height: auto;"></div>
    </div></body></html>
<?php ob_end_flush(); ?>