<title>Keterangan - Chirexs 1.0</title>
<h2 class='text text-primary'>Keterangan</h2>
<hr>
<div class="row">

    <?php
    // Make sure config/koneksi.php has been included/required before this point
    // For example, in content.php or index.php, you'd typically have:
    // require_once 'config/koneksi.php';

    // Check if the connection variable $conn is available from koneksi.php
    if (!isset($conn) || !$conn) {
        die("Database connection not established. Make sure koneksi.php is included.");
    }

    // Use mysqli_query() instead of mysql_query()
    // Pass the connection object ($conn) as the first argument
    $hasil = mysqli_query($conn, "SELECT * FROM post ORDER BY kode_post");

    // Use mysqli_fetch_array() instead of mysql_fetch_array()
    while ($r = mysqli_fetch_array($hasil)) {
        ?>

        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12" data-aos="fade-right">
            <div class="card text-center">
                <img class="card-img-top img-bordered-sm" src="<?php echo 'gambar/' . $r['gambar']; ?>" alt="" width="100%" height="200">
                <div class="card-block">
                    <h4 class="card-title"><h3 class="bg-keterangan"><?php echo $r['nama_post']; ?></h3>
                        <a class="btn bg-maroon btn-flat margin" href="#" data-toggle="modal" data-target="#modal<?php echo $r['kode_post']; ?>"><i class="fa fa-puzzle-piece" aria-hidden="true"></i> Detail</a>
                        <a class="btn bg-olive btn-flat margin" href="#" data-toggle="modal" data-target="#modaltindakan<?php echo $r['kode_post']; ?>"><i class="fa fa-quote-right" aria-hidden="true"></i> Saran</a>
                    </div>
                </div>
                <hr>
            </div>

            <div class="modal fade" id="modal<?php echo $r['kode_post'];?>" role="dialog">
                <div class="modal-dialog">

                    <div class="modal-content">
                        <div class="modal-header detail-ket">
                            <button type="button" class="close" data-dismiss="modal" style="opacity: .99;color: #fff;">&times;</button>
                            <h4 class="modal-title text text-ket"><i class="fa fa-puzzle-piece" aria-hidden="true"></i> Detail Untuk <?php echo $r['nama_post']; ?></h4>
                        </div>
                        <div class="modal-body" style="text-align: justify;text-justify: inter-word;">
                            <p><?php echo $r['det_post']; ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>

                </div>
            </div>
            
            <div class="modal fade" id="modaltindakan<?php echo $r['kode_post'];?>" role="dialog">
                <div class="modal-dialog">

                    <div class="modal-content">
                        <div class="modal-header saran-ket">
                            <button type="button" class="close" data-dismiss="modal" style="opacity: .99;color: #fff;">&times;</button>
                            <h4 class="modal-title text text-ket"><i class="fa fa-quote-right" aria-hidden="true"></i> Saran Untuk <?php echo $r['nama_post']; ?></h4>
                        </div>
                        <div class="modal-body" style="text-align: justify;text-justify: inter-word;">
                            <p><?php echo $r['srn_post']; ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>

                </div>
            </div>

        <?php } ?>

</div>