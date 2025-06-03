<?php
// session_start(); // Diasumsikan sudah dipanggil oleh index.php (atau file utama Anda)
if (!(isset($_SESSION['username']) && isset($_SESSION['password']))) {
    // index.php akan menghalangi akses jika sesi tidak ada
    // Jika file ini bisa diakses langsung, uncomment baris di bawah
    // header('location:index.php?module=formlogin'); 
    // exit();
}
?>
<title>Riwayat Konsultasi - Chirexs 1.0</title>
<h2 class='text text-primary'>Riwayat Konsultasi Anda</h2>
<hr>
<?php
// include "config/fungsi_alert.php"; // Diasumsikan sudah di-include oleh index.php
// $aksi = "modul/riwayat/aksi_hasil.php"; // Tidak ada aksi hapus/edit dari halaman riwayat utama

// Pastikan koneksi $conn sudah ada dari config/koneksi.php yang di-include oleh index.php
if (!$conn || !($conn instanceof mysqli)) {
    echo "<div class='alert alert-danger' style='margin:15px;'>Koneksi database gagal. Riwayat tidak dapat dimuat.</div>";
} else { // Lanjutkan hanya jika koneksi berhasil

    // Menampilkan alert dari session jika ada (pastikan fungsi ini ada dan session_start() sudah dipanggil)
    if (function_exists('display_alert')) {
        display_alert();
    }

    // default action adalah menampilkan daftar riwayat
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $limit = 15; // Jumlah data per halaman (sudah integer)

    // Preload data penyakit untuk mapping nama
    $arpkt = array();
    $sqlpkt = mysqli_query($conn, "SELECT kode_penyakit, nama_penyakit FROM penyakit");
    if ($sqlpkt) {
        while ($rpkt = mysqli_fetch_assoc($sqlpkt)) {
            $arpkt[$rpkt['kode_penyakit']] = $rpkt['nama_penyakit'];
        }
        mysqli_free_result($sqlpkt);
    } else {
        error_log("Riwayat - Gagal mengambil data penyakit untuk mapping: " . mysqli_error($conn));
    }


    // Query untuk total data (untuk paging)
    $sql_total = "SELECT COUNT(*) as total FROM hasil"; // Filter berdasarkan user jika perlu
    $result_total = mysqli_query($conn, $sql_total);
    $baris = 0; // Inisialisasi $baris sebagai integer
    if ($result_total) {
        $row_total = mysqli_fetch_assoc($result_total);
        $baris = isset($row_total['total']) ? (int)$row_total['total'] : 0; // Pastikan $baris adalah integer
        mysqli_free_result($result_total);
    } else {
        error_log("Riwayat - Error executing total count query: " . mysqli_error($conn));
    }
    

    if ($baris > 0) {
        echo "<div class='row'><div class='col-md-8'><table class='table table-bordered table-striped riwayat' style='overflow-x:auto;'>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Tanggal Konsultasi</th>
                  <th>Penyakit Terdiagnosis Utama</th>
                  <th nowrap>Nilai Keyakinan (CF)</th>
                  <th width='15%' class='text-center'>Aksi</th>
                </tr>
              </thead>
              <tbody>";

        // Menggunakan prepared statement untuk mengambil data riwayat
        $stmt_riwayat = $conn->prepare("SELECT id_hasil, tanggal, hasil_id, hasil_nilai FROM hasil ORDER BY tanggal DESC, id_hasil DESC LIMIT ?, ?");
        if ($stmt_riwayat) {
            // Pastikan $offset dan $limit adalah integer saat binding
            $offset_param = (int)$offset;
            $limit_param = (int)$limit; // $limit sudah integer, tapi casting ulang tidak masalah
            $stmt_riwayat->bind_param("ii", $offset_param, $limit_param);
            
            if ($stmt_riwayat->execute()) {
                $hasil_riwayat = $stmt_riwayat->get_result();
                $no = 1 + $offset_param; 
                while ($r = $hasil_riwayat->fetch_assoc()) {
                    $nama_penyakit_riwayat = isset($arpkt[$r['hasil_id']]) ? htmlspecialchars($arpkt[$r['hasil_id']]) : 'N/A (Kode: ' . htmlspecialchars($r['hasil_id']) . ')';
                    $warna = ($no % 2 == 0) ? "even-row" : "odd-row"; // Untuk styling jika diperlukan

                    echo "<tr class='" . $warna . "'>
                             <td align='center'>$no</td>
                             <td>" . htmlspecialchars(date('d M Y, H:i', strtotime($r['tanggal']))) . "</td>
                             <td>" . $nama_penyakit_riwayat . "</td>
                             <td align='center'><span class='label label-default'>" . htmlspecialchars(round((float)$r['hasil_nilai'] * 100, 2)) . "%</span></td>
                             <td align='center'>
                               <a type='button' class='btn btn-info btn-xs' href='index.php?module=riwayat-detail&id=" . htmlspecialchars($r['id_hasil']) . "'><i class='fa fa-eye'></i> Detail</a>
                               </td>
                           </tr>";
                    $no++;
                }
            } else {
                error_log("Riwayat - Gagal eksekusi query data riwayat: " . $stmt_riwayat->error);
                echo "<tr><td colspan='5' class='alert alert-warning text-center'>Gagal memuat data riwayat.</td></tr>";
            }
            $stmt_riwayat->close();
            echo "</tbody></table></div>"; // Penutup tabel dan col-md-8
            
            // Bagian Grafik 
            $arr_grafik = []; 
            $sql_grafik = "SELECT hasil_id, COUNT(hasil_id) as jlh_id FROM hasil WHERE hasil_id IS NOT NULL AND hasil_id != '' GROUP BY hasil_id ORDER BY jlh_id DESC";
            $hasil_grafik = mysqli_query($conn, $sql_grafik);
            if($hasil_grafik){
                while ($rg = mysqli_fetch_assoc($hasil_grafik)) {
                    if (!empty($rg['hasil_id']) && isset($arpkt[$rg['hasil_id']])) { 
                        $arr_grafik[] = array(
                            'label' => htmlspecialchars($arpkt[$rg['hasil_id']]), 
                            'data' => (int)$rg['jlh_id'] 
                        );
                    }
                }
                mysqli_free_result($hasil_grafik);
            } else {
                error_log("Gagal mengambil data untuk grafik riwayat: " . mysqli_error($conn));
            }
            ?>

            <div class="col-md-4">
              <div class="box box-success box-solid">
                <div class="box-header with-border">
                  <i class="fa fa-pie-chart"></i>
                  <h3 class="box-title">Grafik Riwayat Penyakit</h3>
                  <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                    <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                  </div>
                </div>
                <div class="box-body">
                  <div id="donut-chart-riwayat" class="chart" style="width:100%;height:250px;"></div>
                  <div id="legend-container-riwayat" style="margin-top:10px; max-height: 200px; overflow-y: auto;"></div>
                </div>
              </div>
            </div>
            <?php

            // Paging
            echo "</div><div class='row' style='margin-top:15px;'><div class='col-md-12'><div class='paging text-center'>";
            if ((int)$offset != 0) { // Casting $offset untuk perbandingan
                $prevoffset = (int)$offset - (int)$limit; // Casting semua yang terlibat
                if ($prevoffset < 0) $prevoffset = 0;
                echo "<span class='prevnext'> <a href='index.php?module=riwayat&offset=$prevoffset'>Back</a></span>";
            } else {
                echo "<span class='disabled'>Back</span>";
            }

            // Pastikan $limit adalah integer dan tidak nol untuk menghindari division by zero
            $limit_int = (int)$limit;
            if ($limit_int == 0) $limit_int = 1; // Fallback jika limit entah bagaimana menjadi 0
            
            $halaman = ($baris > 0 && $limit_int > 0) ? ceil((int)$baris / $limit_int) : 0; 

            for ($i = 1; $i <= $halaman; $i++) {
                // PERBAIKAN UTAMA UNTUK TypeError:
                // Pastikan semua operan adalah integer sebelum perkalian
                $newoffset = $limit_int * ($i - 1); 
                if ((int)$offset != $newoffset) { 
                    echo "<a href='index.php?module=riwayat&offset=$newoffset'>$i</a>";
                } else {
                    echo "<span class='current'>$i</span>";
                }
            }

            if (((int)$offset + $limit_int) < (int)$baris && $halaman > 1) { // Casting semua yang terlibat
                $newoffset = (int)$offset + $limit_int;
                echo "<span class='prevnext'><a href='index.php?module=riwayat&offset=$newoffset'>Next</a></span>";
            } else {
                echo "<span class='disabled'>Next</span>";
            }
            echo "</div></div></div>"; 

        } else { 
             // Kondisi ini seharusnya sudah ditangani oleh if ($baris > 0) di atas.
             // Jika $stmt_riwayat gagal, pesan error sudah dicatat.
        }
    } else { // jika $baris <= 0
        echo "<div class='col-xs-12'><div class='alert alert-info' style='margin:15px;'>Belum ada data riwayat konsultasi.</div></div>";
    }
} // end else ($conn)
?>

<script type="text/javascript">
$(function () {
    var donutDataRiwayat = <?php echo isset($arr_grafik) && !empty($arr_grafik) ? json_encode($arr_grafik) : '[]'; ?>;

    if (typeof jQuery !== 'undefined' && typeof jQuery.plot !== 'undefined' && donutDataRiwayat.length > 0) {
        function legendFormatterRiwayat(label, series) {
            return '<div style="font-size:11px; text-align:left; padding:2px; color:grey;">' +
                   '<span style="display:inline-block; width:10px; height:10px; margin-right:3px; background-color:' + series.color + '"></span>' +
                   label + ' (' + Math.round(series.percent) + '%)</div>';
        };

        try {
            $.plot('#donut-chart-riwayat', donutDataRiwayat, {
                series: {
                    pie: {
                        show: true,
                        radius: 1,
                        innerRadius: 0.3, 
                        label: {
                            show: true,
                            radius: 2/3, 
                            formatter: function (label, series) {
                                return '<div class="badge bg-navy color-pallete" style="font-size:11px;">' + Math.round(series.percent) + '%</div>';
                            },
                            threshold: 0.03 
                        }
                    }
                },
                legend: {
                    show: true, 
                    container: $("#legend-container-riwayat"), 
                    labelFormatter: legendFormatterRiwayat, 
                    noColumns: 1, 
                    labelBoxBorderColor: "none" 
                },
                grid: {
                    hoverable: true 
                }
            });
        } catch (e) {
            console.error("Error plotting riwayat chart: ", e);
            $('#donut-chart-riwayat').html("<p class='text-danger'>Gagal memuat grafik riwayat.</p>");
        }
    } else if (typeof jQuery !== 'undefined' && typeof jQuery.plot !== 'undefined') {
        $('#donut-chart-riwayat').html("<p class='text-info'>Tidak ada data yang cukup untuk ditampilkan pada grafik riwayat.</p>");
        $('#legend-container-riwayat').empty();
    } else {
         console.error("jQuery atau Flot tidak dimuat. Grafik riwayat tidak dapat ditampilkan.");
         $('#donut-chart-riwayat').html("<p class='text-warning'>Komponen grafik tidak dapat dimuat.</p>");
    }
});
</script>
