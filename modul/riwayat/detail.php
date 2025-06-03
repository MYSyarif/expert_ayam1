<title>Detail Riwayat - Chirexs 1.0</title>
<?php

// Make sure your koneksi.php is included/required at the top of your file
// or in a file that includes this one (e.g., content.php or index.php).
// For demonstration, I'll assume $conn is already available.
// If not, you might need to add:
// require_once '../../config/koneksi.php'; // Adjust the path as needed

if (isset($_GET['id'])) {
    $arcolor = array('#ffffff', '#cc66ff', '#019AFF', '#00CBFD', '#00FEFE', '#A4F804', '#FFFC00', '#FDCD01', '#FD9A01', '#FB6700');
    date_default_timezone_set("Asia/Jakarta");
    $inptanggal = date('Y-m-d H:i:s');

    $arbobot = array('0', '1', '0.8', '0.6', '0.4', '-0.2', '-0.4', '-0.6', '-0.8', '-1');

    // --- Start of fix: Remove the problematic $_POST['kondisi'] section ---
    // The previous code was trying to process $_POST['kondisi'] here,
    // but for 'detail.php' which uses $_GET['id'], the gejala data
    // should be loaded from the database 'hasil' table later.
    // So, this loop is not needed here.
    //
    // for ($i = 0; $i < count($_POST['kondisi']); $i++) {
    //     $arkondisi = explode("_", $_POST['kondisi'][$i]);
    //     if (strlen($_POST['kondisi'][$i]) > 1) {
    //         $argejala += array($arkondisi[0] => $arkondisi[1]);
    //     }
    // }
    // --- End of fix ---

    // Initialize argejala to an empty array. It will be populated from the database.
    $argejala = array();

    $sqlkondisi = mysqli_query($conn, "SELECT * FROM kondisi order by id+0");
    while ($rkondisi = mysqli_fetch_array($sqlkondisi)) {
        $arkondisitext[$rkondisi['id']] = $rkondisi['kondisi'];
    }

    $sqlpkt = mysqli_query($conn, "SELECT * FROM penyakit order by kode_penyakit+0");
    while ($rpkt = mysqli_fetch_array($sqlpkt)) {
        $arpkt[$rpkt['kode_penyakit']] = $rpkt['nama_penyakit'];
        $ardpkt[$rpkt['kode_penyakit']] = $rpkt['det_penyakit'];
        $arspkt[$rpkt['kode_penyakit']] = $rpkt['srn_penyakit'];
        $argpkt[$rpkt['kode_penyakit']] = $rpkt['gambar'];
    }

    // Load diagnosis results from the 'hasil' table using the $_GET['id']
    $id_hasil = intval($_GET['id']); // Sanitize input
    $sqlhasil = mysqli_query($conn, "SELECT * FROM hasil WHERE id_hasil = $id_hasil");

    if (mysqli_num_rows($sqlhasil) > 0) {
        $rhasil = mysqli_fetch_array($sqlhasil);
        $arpenyakit = unserialize($rhasil['penyakit']);
        $argejala = unserialize($rhasil['gejala']);
    } else {
        // Handle case where id_hasil is not found
        echo "<div class='content'><h2 class='text text-danger'>Data diagnosis tidak ditemukan.</h2></div>";
        exit(); // Stop execution if data isn't found
    }

    $np1 = 0;
    // Ensure $arpenyakit is an array before iterating
    if (is_array($arpenyakit)) {
        foreach ($arpenyakit as $key1 => $value1) {
            $np1++;
            $idpkt1[$np1] = $key1;
            $vlpkt1[$np1] = $value1;
        }
    } else {
        echo "<div class='content'><h2 class='text text-danger'>Data penyakit tidak valid.</h2></div>";
        exit();
    }


    echo "<div class='content'>
        <h2 class='text text-primary'>Hasil Diagnosis &nbsp;&nbsp;<button id='print' onClick='window.print();' data-toggle='tooltip' data-placement='right' title='Klik tombol ini untuk mencetak hasil diagnosa'><i class='fa fa-print'></i> Cetak</button> </h2>
        <hr><table class='table table-bordered table-striped diagnosa'>
        <th width=8%>No</th>
        <th width=10%>Kode</th>
        <th>Gejala yang dialami (keluhan)</th>
        <th width=20%>Pilihan</th>
        </tr>";

    $ig = 0;
    // Ensure $argejala is an array before iterating
    if (is_array($argejala)) {
        foreach ($argejala as $key => $value) {
            $kondisi = $value;
            $ig++;
            $gejala = $key;
            $sql4 = mysqli_query($conn, "SELECT * FROM gejala WHERE kode_gejala = '$key'");
            $r4 = mysqli_fetch_array($sql4);
            echo '<tr><td>' . $ig . '</td>';
            // Use property access for mysqli_fetch_array results or array access
            echo '<td>G' . str_pad($r4['kode_gejala'], 3, '0', STR_PAD_LEFT) . '</td>';
            echo '<td><span class="hasil text text-primary">' . $r4['nama_gejala'] . "</span></td>";
            echo '<td><span class="kondisipilih" style="color:' . (isset($arcolor[$kondisi]) ? $arcolor[$kondisi] : '#000000') . '">' . (isset($arkondisitext[$kondisi]) ? $arkondisitext[$kondisi] : 'Tidak Diketahui') . "</span></td></tr>";
        }
    } else {
        echo "<tr><td colspan='4'>Tidak ada gejala yang tercatat untuk diagnosis ini.</td></tr>";
    }

    echo "</table>"; // Close the table here

    $np = 0;
    // Ensure $arpenyakit is an array before iterating again
    if (is_array($arpenyakit)) {
        foreach ($arpenyakit as $key => $value) {
            $np++;
            $idpkt[$np] = $key;
            $nmpkt[$np] = $arpkt[$key];
            $vlpkt[$np] = $value;
        }
    }

    if (isset($idpkt[1]) && $argpkt[$idpkt[1]]) {
        $gambar = 'gambar/penyakit/' . $argpkt[$idpkt[1]];
    } else {
        $gambar = 'gambar/noimage.png';
    }

    echo "<div class='well well-small'><img class='card-img-top img-bordered-sm' style='float:right; margin-left:15px;' src='" . $gambar . "' height=200><h3>Hasil Diagnosa</h3>";
    echo "<div class='callout callout-default'>Jenis penyakit yang diderita adalah <b><h3 class='text text-success'>" . (isset($nmpkt[1]) ? $nmpkt[1] : 'Tidak Diketahui') . "</b> / " . (isset($vlpkt[1]) ? round($vlpkt[1], 2) : 'N/A') . " % (" . (isset($vlpkt[1]) ? $vlpkt[1] : 'N/A') . ")<br></h3>";
    echo "</div></div><div class='box box-info box-solid'><div class='box-header with-border'><h3 class='box-title'>Detail</h3></div><div class='box-body'><h4>";
    echo (isset($idpkt[1]) && isset($ardpkt[$idpkt[1]])) ? $ardpkt[$idpkt[1]] : 'Detail penyakit tidak tersedia.';
    echo "</h4></div></div>
        <div class='box box-warning box-solid'><div class='box-header with-border'><h3 class='box-title'>Saran</h3></div><div class='box-body'><h4>";
    echo (isset($idpkt[1]) && isset($arspkt[$idpkt[1]])) ? $arspkt[$idpkt[1]] : 'Saran tidak tersedia.';
    echo "</h4></div></div>
        <div class='box box-danger box-solid'><div class='box-header with-border'><h3 class='box-title'>Kemungkinan lain:</h3></div><div class='box-body'><h4>";

    if (isset($idpkt) && is_array($idpkt)) {
        for ($ipl = 2; $ipl < count($idpkt); $ipl++) {
            if (isset($nmpkt[$ipl]) && isset($vlpkt[$ipl])) {
                echo " <h4><i class='fa fa-caret-square-o-right'></i> " . $nmpkt[$ipl] . "</b> / " . round($vlpkt[$ipl], 2) . " % (" . $vlpkt[$ipl] . ")<br></h4>";
            }
        }
    } else {
        echo "Tidak ada kemungkinan penyakit lain yang ditemukan.";
    }

    echo "</div></div>
        </div>";

} else {
    // Handle the case where no ID is provided in the URL
    echo "<div class='content'><h2 class='text text-danger'>ID Riwayat tidak ditemukan.</h2></div>";
}
?>