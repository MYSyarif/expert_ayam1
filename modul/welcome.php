<title>Beranda - Chirexs 1.0</title>
<?php
// Your koneksi.php (config/koneksi.php) includes the database connection
// and assigns it to the variable $conn.
// Ensure that the $conn variable is available in this scope,
// which it should be if index.php or content.php includes config/koneksi.php before this file.

// Changed $koneksi to $conn for consistency with config/koneksi.php
$htgejala = mysqli_query($conn, "SELECT count(*) as total from gejala");
$dtgejala = mysqli_fetch_assoc($htgejala);
?>
<div class='row'>
    <div class='col-lg-3 col-xs-6'>
        <div class='small-box bg-aqua'>
            <div class='inner'>
                <h3> <?php echo $dtgejala["total"]; ?></h3>
                <p>Total Gejala</p>
            </div>
            <div class='icon'>
                <i class="icon ion-android-archive"></i>
            </div>
        </div>
    </div>
    <?php
    // Changed $koneksi to $conn for consistency
    $htpenyakit = mysqli_query($conn, "SELECT count(*) as total from penyakit");
    $dtpenyakit = mysqli_fetch_assoc($htpenyakit);
    ?>
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-green">
            <div class="inner">
                <h3> <?php echo $dtpenyakit["total"]; ?></h3>

                <p>Total Kerusakan</p>
            </div>
            <div class="icon">
                <i class="ion ion-settings"></i>
            </div>
        </div>
    </div>
    <?php
    // Changed $koneksi to $conn for consistency
    $htpengetahuan = mysqli_query($conn, "SELECT count(*) as total from basis_pengetahuan");
    $dtpengetahuan = mysqli_fetch_assoc($htpengetahuan);
    ?>
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-yellow">
            <div class="inner">
                <h3><?php echo $dtpengetahuan["total"]; ?></h3>

                <p>Total Pengetahuan</p>
            </div>
            <div class="icon">
                <i class="ion ion-document-text"></i>
            </div>
        </div>
    </div>
    <?php
    // Changed $koneksi to $conn for consistency
    $htadmin = mysqli_query($conn, "SELECT count(*) as total from admin");
    $dtadmin = mysqli_fetch_assoc($htadmin);
    ?>
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-red">
            <div class="inner">
                <h3> <?php echo $dtadmin["total"]; ?></h3>
                <p>Total Admin Pakar</p>
            </div>
            <div class="icon">
                <i class="ion ion-android-contacts"></i>
            </div>
        </div>
    </div>
</div>
<div id="carousel-example-generic" class="carousel slide" data-ride="carousel">
    <ol class="carousel-indicators">
        <li data-target="#carousel-example-generic" data-slide-to="0" class=""></li>
        <li data-target="#carousel-example-generic" data-slide-to="1" class=""></li>
        <li data-target="#carousel-example-generic" data-slide-to="2" class="active"></li>
    </ol>
    <div class="carousel-inner">
        <div class="item active">
            <img src="aset/banner/ayam.jpg" alt="First slide">
            <div class="carousel-caption">
            </div>
        </div>
        <div class="item">
            <img src="aset/banner/telur.jpg" alt="Second slide">
            <div class="carousel-caption">
            </div>
        </div>
    </div>
    <a class="left carousel-control" href="#carousel-example-generic" data-slide="prev">
        <span class="fa fa-angle-left"></span>
    </a>
    <a class="right carousel-control" href="#carousel-example-generic" data-slide="next">
        <span class="fa fa-angle-right"></span>
    </a>
</div>
<br>
<div class="row">
    <div class="col-sm-4 text-center padding wow fadeIn animated" data-wow-duration="1000ms" data-wow-delay="300ms" style="visibility: visible; animation-duration: 1000ms; animation-delay: 300ms; animation-name: fadeIn;">
        <div class="single-service">

            <img src="aset/banner/icon3.png" alt="">

            <h2>Aplikasi Responsif</h2>
            <p>Aplikasi sistem pakar ini dapat menyesuaikan ukuran perangkat anda, jadi walupun di akses melalui perangkat mobile tetap nyaman juga.</p>
        </div>
    </div>
    <div class="col-sm-4 text-center padding wow fadeIn animated" data-wow-duration="1000ms" data-wow-delay="600ms" style="visibility: visible; animation-duration: 1000ms; animation-delay: 600ms; animation-name: fadeIn;">
        <div class="single-service">

            <img src="aset/banner/icon2.png" alt="">

            <h2>Sahabat Peternak</h2>
            <p>Sistem Pakar ini terus di sesuaikan perhitungan diagnosanya, supaya akurasi terhadap penyakit yang diderita lebih sesuai dan  menjadi acuan peternak.</p>
        </div>
    </div>
    <div class="col-sm-4 text-center padding wow fadeIn animated" data-wow-duration="1000ms" data-wow-delay="900ms" style="visibility: visible; animation-duration: 1000ms; animation-delay: 900ms; animation-name: fadeIn;">
        <div class="single-service">
            <img src="aset/banner/icon1.png" alt="">
            <h2>Admin Pakar</h2>
            <p>Terdapat fitur admin pakar, untuk mengatur pengetahuan dan CF pakar, telah di sesuaikan tampilannya sehingga pakar bisa lebih mengeksplore aplikasi.</p>
        </div>
    </div>
</div>
<div></div>