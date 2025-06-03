<?php
// ========== PENGATURAN MODE DEBUG ==========
// Set $debug_mode ke true untuk menampilkan pesan-pesan proses.
// Set ke false untuk penggunaan normal (sembunyikan pesan debug).
$debug_mode = false; // <--- TETAP true UNTUK SEKARANG

// Jika mode debug aktif, tampilkan semua error PHP
if ($debug_mode) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    echo "<div style='background-color:#FFFFE0; padding:15px; margin:10px; border:1px solid #FFD700; font-family: monospace; font-size: 12px; z-index: 9999; position: relative;'>";
    echo "<h3><strong style='color:red;'>MODE DEBUG AKTIF (diagnosa.php)</strong></h3>";
}

// Pastikan variabel $conn sudah didefinisikan dan tersedia dari config/koneksi.php
global $conn; 

if (!$conn || !($conn instanceof mysqli)) {
    if ($debug_mode) echo "<strong>ERROR:</strong> Variabel koneksi database (\$conn) tidak valid atau belum diinisialisasi.<br>";
    error_log("Kesalahan Kritis di diagnosa.php: Variabel koneksi database (\$conn) tidak valid atau belum diinisialisasi.");
    if ($debug_mode) echo "</div>";
    die("Terjadi kesalahan pada sistem diagnosis. Silakan coba lagi nanti atau hubungi administrator. (Error Code: DB_CONN_INVALID)");
}
if ($debug_mode) echo "Koneksi database (\$conn) berhasil terdeteksi.<br>";

$act = $_GET['act'] ?? ''; 
if ($debug_mode) echo "Nilai untuk \$act = '" . htmlspecialchars($act) . "'<br>";

// DEBUGGING AWAL UNTUK $_POST DAN REQUEST METHOD
if ($debug_mode) {
    echo "<pre style='background-color:#f0f0f0; border:1px dashed #999; padding:10px; margin-bottom:10px;'>";
    echo "<strong>DEBUG AWAL REQUEST:</strong><br>";
    echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "<br>";
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (empty($_POST)) {
            echo "Array \$_POST KOSONG (meskipun method POST).<br>";
        } else {
            echo "Isi \$_POST:<br>";
            print_r($_POST);
        }
    } else {
        echo "Array \$_POST tidak akan diisi karena method bukan POST.<br>";
    }
    echo "</pre>";
}

switch ($act) {
    default:
        // Kondisi ini sekarang dicakup oleh blok DEBUG AWAL REQUEST di atas
        // if ($debug_mode) {
		// 	if ($debug_mode) {
        //         // ... blok debug awal untuk $_POST sebelumnya ...
        //     }
        //     if (isset($_POST['submit'])) {
        //         echo "<strong>DEBUG:</strong> Tombol 'submit' TERDETEKSI dalam \$_POST.<br>";
        //         echo "<pre><strong>DEBUG \$_POST:</strong> "; print_r($_POST); echo "</pre>";
        //     } else {
        //         echo "<strong>DEBUG:</strong> Tombol 'submit' TIDAK terdeteksi dalam \$_POST. Menampilkan form.<br>";
        //     }
        // }

        if (isset($_POST['submit'])) {
            if ($debug_mode) {
                echo "<strong>DEBUG:</strong> Tombol 'submit' TERDETEKSI dalam \$_POST.<br>";
                echo "<pre><strong>DEBUG (dalam if isset POST submit) \$_POST:</strong> "; print_r($_POST); echo "</pre>";
                echo "<strong>DEBUG:</strong> Memulai bagian PROSES DIAGNOSA.<br>";
            }

            $arcolor = ['#ffffff', '#cc66ff', '#019AFF', '#00CBFD', '#00FEFE', '#A4F804', '#FFFC00', '#FDCD01', '#FD9A01', '#FB6700'];
            date_default_timezone_set("Asia/Jakarta");
            $inptanggal = date('Y-m-d H:i:s');

            $arbobot = ['0', '1', '0.8', '0.6', '0.4', '-0.2', '-0.4', '-0.6', '-0.8', '-1'];
            $argejala = []; 

            if (isset($_POST['kondisi']) && is_array($_POST['kondisi'])) {
                if ($debug_mode) echo "<strong>DEBUG:</strong> Memproses \$_POST['kondisi']...<br>";
                foreach ($_POST['kondisi'] as $kondisi_input) {
                    if ($kondisi_input !== "0" && strpos($kondisi_input, '_') !== false) {
                        $arkondisi = explode("_", $kondisi_input);
                        if (count($arkondisi) == 2) {
                            $kode_gejala = $arkondisi[0];
                            $id_kondisi = $arkondisi[1];
                            $argejala[$kode_gejala] = $id_kondisi;
                            if ($debug_mode) echo "<strong>DEBUG:</strong> Gejala '$kode_gejala' dengan kondisi '$id_kondisi' ditambahkan ke \$argejala.<br>";
                        }
                    }
                }
            }
            if ($debug_mode) {
                echo "<pre><strong>DEBUG \$argejala (gejala dipilih pengguna):</strong> "; print_r($argejala); echo "</pre>";
            }

            if (empty($argejala)) {
                if ($debug_mode) echo "<strong>DEBUG:</strong> \$argejala kosong. Menampilkan pesan error 'belum memilih gejala'.<br>";
                 // Div pembungkus konten hasil agar tidak tercampur pesan debug di atasnya jika ada
                echo "<div class='hasil-diagnosa-content-error'>";
                echo "<div class='alert alert-warning'><h4>Perhatian!</h4>Anda belum memilih satupun gejala. Silakan pilih minimal satu gejala.</div>";
                echo "<a href='?module=diagnosa' class='btn btn-primary'>Kembali ke Form Diagnosa</a>";
                echo "</div>"; // penutup div.hasil-diagnosa-content-error
            } else { 
                if ($debug_mode) echo "<strong>DEBUG:</strong> \$argejala TIDAK kosong. Melanjutkan proses.<br>";

                $arkondisitext = [];
                $sqlkondisi = mysqli_query($conn, "SELECT id, kondisi FROM kondisi ORDER BY id ASC");
                if (!$sqlkondisi) {
                    if ($debug_mode) echo "<strong>ERROR:</strong> Query Kondisi Gagal: " . mysqli_error($conn) . "<br>";
                    error_log("Query Kondisi Gagal: " . mysqli_error($conn));
                    if ($debug_mode) echo "</div>"; die("Terjadi kesalahan saat mengambil data kondisi. (Error Code: DB_QUERY_KONDISI)");
                }
                while ($rkondisi = mysqli_fetch_assoc($sqlkondisi)) {
                    $arkondisitext[$rkondisi['id']] = $rkondisi['kondisi'];
                }
                mysqli_free_result($sqlkondisi);
                if ($debug_mode) { echo "<pre><strong>DEBUG \$arkondisitext:</strong> "; print_r($arkondisitext); echo "</pre>"; }

                $arpkt = []; $ardpkt = []; $arspkt = []; $argpkt = [];
                $sqlpkt_all = mysqli_query($conn, "SELECT kode_penyakit, nama_penyakit, det_penyakit, srn_penyakit, gambar FROM penyakit ORDER BY kode_penyakit ASC");
                if (!$sqlpkt_all) {
                     if ($debug_mode) echo "<strong>ERROR:</strong> Query Penyakit Gagal: " . mysqli_error($conn) . "<br>";
                    error_log("Query Penyakit Gagal: " . mysqli_error($conn));
                     if ($debug_mode) echo "</div>"; die("Terjadi kesalahan saat mengambil data penyakit. (Error Code: DB_QUERY_PENYAKIT)");
                }
                while ($rpkt = mysqli_fetch_assoc($sqlpkt_all)) {
                    $arpkt[$rpkt['kode_penyakit']] = $rpkt['nama_penyakit'];
                    $ardpkt[$rpkt['kode_penyakit']] = $rpkt['det_penyakit'];
                    $arspkt[$rpkt['kode_penyakit']] = $rpkt['srn_penyakit'];
                    $argpkt[$rpkt['kode_penyakit']] = $rpkt['gambar'];
                }
                mysqli_free_result($sqlpkt_all);
                if ($debug_mode) { echo "<pre><strong>DEBUG \$arpkt (Nama Penyakit):</strong> "; print_r($arpkt); echo "</pre>"; }
                
                if ($debug_mode) echo "<strong>DEBUG:</strong> Memulai PERHITUNGAN CERTAINTY FACTOR (CF).<br>";
                $arpenyakit_cf = []; 

                $stmt_gejala_bp = mysqli_prepare($conn, "SELECT kode_gejala, mb, md FROM basis_pengetahuan WHERE kode_penyakit = ?");
                if (!$stmt_gejala_bp) {
                    if ($debug_mode) echo "<strong>ERROR:</strong> Prepare Statement Basis Pengetahuan Gagal: " . mysqli_error($conn) . "<br>";
                    error_log("Prepare Statement Basis Pengetahuan Gagal: " . mysqli_error($conn));
                    if ($debug_mode) echo "</div>"; die("Terjadi kesalahan pada sistem diagnosis. (Error Code: DB_PREPARE_BP)");
                }

                foreach (array_keys($arpkt) as $kode_penyakit) {
                    if ($debug_mode) echo "<hr style='border-top: 1px dashed #ccc;'><strong>DEBUG CF:</strong> Memproses penyakit '$kode_penyakit' - ".(isset($arpkt[$kode_penyakit]) ? $arpkt[$kode_penyakit] : "N/A").".<br>";
                    $cflama = 0; 

                    mysqli_stmt_bind_param($stmt_gejala_bp, "s", $kode_penyakit);
                    if (!mysqli_stmt_execute($stmt_gejala_bp)) {
                        if ($debug_mode) echo "<strong>ERROR CF:</strong> Execute Statement Basis Pengetahuan Gagal untuk penyakit '$kode_penyakit': " . mysqli_stmt_error($stmt_gejala_bp) . "<br>";
                        error_log("Execute Statement Basis Pengetahuan Gagal: " . mysqli_stmt_error($stmt_gejala_bp) . " untuk kode_penyakit: " . $kode_penyakit);
                        continue; 
                    }
                    $result_gejala_bp = mysqli_stmt_get_result($stmt_gejala_bp);
                    if (!$result_gejala_bp) {
                         if ($debug_mode) echo "<strong>ERROR CF:</strong> Get Result Statement Basis Pengetahuan Gagal untuk penyakit '$kode_penyakit': " . mysqli_stmt_error($stmt_gejala_bp) . "<br>";
                         error_log("Get Result Statement Basis Pengetahuan Gagal: " . mysqli_stmt_error($stmt_gejala_bp));
                         continue;
                    }

                    if ($debug_mode) echo "<strong>DEBUG CF [$kode_penyakit]:</strong> Loop melalui aturan basis pengetahuan...<br>";
                    while ($rgejala_bp = mysqli_fetch_assoc($result_gejala_bp)) {
                        if ($debug_mode) echo "&nbsp;&nbsp;Aturan untuk gejala '{$rgejala_bp['kode_gejala']}' (MB:{$rgejala_bp['mb']}, MD:{$rgejala_bp['md']}).<br>";
                        if (isset($argejala[$rgejala_bp['kode_gejala']])) {
                            $id_kondisi_user = $argejala[$rgejala_bp['kode_gejala']];
                            if ($debug_mode) echo "&nbsp;&nbsp;&nbsp;&nbsp;<span style='color:blue;'>Gejala '{$rgejala_bp['kode_gejala']}' DIPILIH pengguna dengan kondisi '$id_kondisi_user'.</span><br>";

                            if (isset($arbobot[$id_kondisi_user])) {
                                $cf_pakar = (float)$rgejala_bp['mb'] - (float)$rgejala_bp['md']; 
                                $cf_user = (float)$arbobot[$id_kondisi_user]; 
                                $cf_evidence = $cf_pakar * $cf_user; 
                                if ($debug_mode) {
                                    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;CF Pakar='{$cf_pakar}', CF User='{$cf_user}', CF Evidence='{$cf_evidence}'. CF Lama (sebelum)='{$cflama}'.<br>";
                                }

                                if ($cflama == 0 && $cf_evidence != 0) {
                                    $cflama = $cf_evidence;
                                } elseif ($cflama != 0 && $cf_evidence != 0) {
                                    if ($cflama > 0 && $cf_evidence > 0) {
                                        $cflama = $cflama + $cf_evidence * (1 - $cflama);
                                    } elseif ($cflama < 0 && $cf_evidence < 0) {
                                        $cflama = $cflama + $cf_evidence * (1 + $cflama);
                                    } else { 
                                        $cflama = ($cflama + $cf_evidence) / (1 - min(abs($cflama), abs($cf_evidence)));
                                    }
                                }
                                if ($debug_mode) echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;CF Lama (sesudah)='{$cflama}'.<br>";
                            } else {
                                if ($debug_mode) echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style='color:orange;'>Bobot untuk kondisi '$id_kondisi_user' TIDAK DITEMUKAN di \$arbobot.</span><br>";
                            }
                        }
                    }
                    mysqli_free_result($result_gejala_bp);

                    if ($cflama != 0) {
                        $arpenyakit_cf[$kode_penyakit] = round($cflama, 4);
                        if ($debug_mode) echo "<strong>DEBUG CF [$kode_penyakit]:</strong> Penyakit '$kode_penyakit' ditambahkan ke hasil CF dengan nilai '$cflama'.<br>";
                    } else {
                        if ($debug_mode) echo "<strong>DEBUG CF [$kode_penyakit]:</strong> Penyakit '$kode_penyakit' TIDAK ditambahkan (CF Lama masih 0).<br>";
                    }
                }
                mysqli_stmt_close($stmt_gejala_bp);
                if ($debug_mode) { echo "<pre><strong>DEBUG \$arpenyakit_cf (HASIL AKHIR CF sebelum sort):</strong> "; print_r($arpenyakit_cf); echo "</pre>"; }

                arsort($arpenyakit_cf); 
                if ($debug_mode) { echo "<pre><strong>DEBUG \$arpenyakit_cf (HASIL AKHIR CF SETELAH sort):</strong> "; print_r($arpenyakit_cf); echo "</pre>"; }


                $inpgejala = serialize($argejala);
                $inppenyakit_cf = serialize($arpenyakit_cf);

                $idpkt1 = null; $vlpkt1 = null;
                if (!empty($arpenyakit_cf)) {
                    reset($arpenyakit_cf);
                    $idpkt1 = key($arpenyakit_cf); 
                    $vlpkt1 = current($arpenyakit_cf); 
                    if ($debug_mode) echo "<strong>DEBUG:</strong> Penyakit teratas: Kode='$idpkt1', Nilai CF='$vlpkt1'.<br>";
                } else {
                    if ($debug_mode) echo "<strong>DEBUG:</strong> \$arpenyakit_cf kosong, tidak ada penyakit teratas.<br>";
                }

                $stmt_insert_hasil = mysqli_prepare($conn, "INSERT INTO hasil (tanggal, gejala, penyakit, hasil_id, hasil_nilai) VALUES (?, ?, ?, ?, ?)");
                if (!$stmt_insert_hasil) {
                    if ($debug_mode) echo "<strong>ERROR:</strong> Prepare Statement Insert Hasil Gagal: " . mysqli_error($conn) . "<br>";
                    error_log("Prepare Statement Insert Hasil Gagal: " . mysqli_error($conn));
                     if ($debug_mode) echo "</div>"; die("Terjadi kesalahan saat menyimpan hasil diagnosis. (Error Code: DB_PREPARE_HASIL)");
                }
                $vlpkt1_db = !is_null($vlpkt1) ? (string)$vlpkt1 : null;
                mysqli_stmt_bind_param($stmt_insert_hasil, "sssss", $inptanggal, $inpgejala, $inppenyakit_cf, $idpkt1, $vlpkt1_db);
                if (!mysqli_stmt_execute($stmt_insert_hasil)) {
                    if ($debug_mode) echo "<strong>ERROR:</strong> Insert Hasil Gagal: " . mysqli_stmt_error($stmt_insert_hasil) . "<br>";
                    error_log("Insert Hasil Gagal: " . mysqli_stmt_error($stmt_insert_hasil));
                    if ($debug_mode) echo "</div>"; die("Gagal menyimpan hasil diagnosis. (Error Code: DB_EXEC_HASIL)");
                } else {
                    if ($debug_mode) echo "<strong>DEBUG:</strong> Hasil diagnosa BERHASIL disimpan ke database.<br>";
                }
                mysqli_stmt_close($stmt_insert_hasil);

                // --- BAGIAN MENAMPILKAN HASIL DIAGNOSA ---
                if ($debug_mode) echo "<hr><strong>DEBUG:</strong> Mulai menampilkan hasil diagnosa ke pengguna.<br>";
                
                echo "<div class='hasil-diagnosa-content'>"; 
                echo "<div class='content'>
                        <h2 class='text text-primary'>Hasil Diagnosis &nbsp;&nbsp;<button id='print' onClick='window.print();' data-toggle='tooltip' data-placement='right' title='Klik tombol ini untuk mencetak hasil diagnosa'><i class='fa fa-print'></i> Cetak</button> </h2>
                        <hr><table class='table table-bordered table-striped diagnosa'>
                        <tr><th width='8%'>No</th>
                        <th width='10%'>Kode</th>
                        <th>Gejala yang dialami (keluhan)</th>
                        <th width='20%'>Pilihan</th>
                        </tr>";
                $ig = 0;

                $stmt_nama_gejala = mysqli_prepare($conn, "SELECT nama_gejala FROM gejala WHERE kode_gejala = ?");
                if (!$stmt_nama_gejala && $debug_mode) {
                    echo "<strong>WARNING DEBUG:</strong> Prepare Statement Nama Gejala Gagal: " . mysqli_error($conn) . ". Nama gejala mungkin tidak tampil.<br>";
                }

                foreach ($argejala as $kode_gejala_user => $id_kondisi_user) {
                    $ig++;
                    $nama_gejala_display = "Gejala tidak ditemukan"; 
                    if ($stmt_nama_gejala) {
                        mysqli_stmt_bind_param($stmt_nama_gejala, "s", $kode_gejala_user);
                        mysqli_stmt_execute($stmt_nama_gejala);
                        $result_nama_gejala = mysqli_stmt_get_result($stmt_nama_gejala);
                        if ($result_nama_gejala && $r_nama_gejala = mysqli_fetch_assoc($result_nama_gejala)) {
                            $nama_gejala_display = $r_nama_gejala['nama_gejala'];
                        }
                        if($result_nama_gejala) mysqli_free_result($result_nama_gejala);
                    }

                    echo '<tr><td>' . $ig . '</td>';
                    echo '<td>G' . str_pad(htmlspecialchars($kode_gejala_user), 3, '0', STR_PAD_LEFT) . '</td>';
                    echo '<td><span class="hasil text text-primary">' . htmlspecialchars($nama_gejala_display) . "</span></td>";
                    $kondisi_text_display = isset($arkondisitext[$id_kondisi_user]) ? $arkondisitext[$id_kondisi_user] : "Kondisi tidak valid";
                    $kondisi_color = isset($arcolor[$id_kondisi_user]) ? $arcolor[$id_kondisi_user] : '#ffffff'; 
                    echo '<td><span class="kondisipilih" style="color:' . $kondisi_color . '">' . htmlspecialchars($kondisi_text_display) . "</span></td></tr>";
                }
                if ($stmt_nama_gejala) mysqli_stmt_close($stmt_nama_gejala);
                echo "</table>";

                if (!empty($arpenyakit_cf)) {
                    if ($debug_mode) echo "<strong>DEBUG:</strong> Menampilkan detail penyakit terdiagnosis (\$arpenyakit_cf TIDAK kosong).<br>";
                    $nama_penyakit_tertinggi = isset($arpkt[$idpkt1]) ? $arpkt[$idpkt1] : "Nama Penyakit Tidak Diketahui";
                    $detail_penyakit_tertinggi = isset($ardpkt[$idpkt1]) ? $ardpkt[$idpkt1] : 'Detail tidak ditemukan.';
                    $saran_penyakit_tertinggi = isset($arspkt[$idpkt1]) ? $arspkt[$idpkt1] : 'Saran tidak ditemukan.';
                    $gambar_penyakit_tertinggi_file = isset($argpkt[$idpkt1]) ? $argpkt[$idpkt1] : '';

                    $gambar_path = 'gambar/noimage.png'; 
                    if (!empty($gambar_penyakit_tertinggi_file)) {
                        $potential_path = 'gambar/penyakit/' . $gambar_penyakit_tertinggi_file;
                        if (file_exists($potential_path)) {
                            $gambar_path = $potential_path;
                        } else {
                            if($debug_mode) echo "<strong>DEBUG IMAGE:</strong> File gambar '$potential_path' tidak ditemukan.<br>";
                        }
                    } else {
                         if($debug_mode) echo "<strong>DEBUG IMAGE:</strong> Nama file gambar kosong untuk penyakit '$idpkt1'.<br>";
                    }

                    echo "<div class='well well-small'><img class='card-img-top img-bordered-sm' style='float:right; margin-left:15px;' src='" . htmlspecialchars($gambar_path) . "' height='200' alt='Gambar Penyakit'><h3>Hasil Diagnosa</h3>";
                    $persen_vlpkt1 = round($vlpkt1 * 100, 2);
                    echo "<div class='callout callout-default'>Jenis penyakit yang diderita adalah <b><h3 class='text text-success'>" . htmlspecialchars($nama_penyakit_tertinggi) . "</b> / " . $persen_vlpkt1 . " % (CF: " . $vlpkt1 . ")<br></h3>";
                    echo "</div></div><div class='box box-info box-solid'><div class='box-header with-border'><h3 class='box-title'>Detail</h3></div><div class='box-body'><h4>";
                    echo nl2br(htmlspecialchars($detail_penyakit_tertinggi));
                    echo "</h4></div></div>
                            <div class='box box-warning box-solid'><div class='box-header with-border'><h3 class='box-title'>Saran</h3></div><div class='box-body'><h4>";
                    echo nl2br(htmlspecialchars($saran_penyakit_tertinggi));
                    echo "</h4></div></div>";

                    if (count($arpenyakit_cf) > 1) {
                        if ($debug_mode) echo "<strong>DEBUG:</strong> Menampilkan kemungkinan penyakit lain.<br>";
                        echo "<div class='box box-danger box-solid'><div class='box-header with-border'><h3 class='box-title'>Kemungkinan lain:</h3></div><div class='box-body'>";
                        $first = true;
                        foreach ($arpenyakit_cf as $kode_pkt_lain => $cf_pkt_lain) {
                            if ($first) { $first = false; continue; } 
                            $nama_pkt_lain = isset($arpkt[$kode_pkt_lain]) ? $arpkt[$kode_pkt_lain] : "Nama Penyakit Tidak Diketahui";
                            $persen_pkt_lain = round($cf_pkt_lain * 100, 2);
                            echo " <h4><i class='fa fa-caret-square-o-right'></i> " . htmlspecialchars($nama_pkt_lain) . "</b> / " . $persen_pkt_lain . " % (CF: " . $cf_pkt_lain . ")<br></h4>";
                        }
                        echo "</div></div>";
                    }
                } else {
                    if ($debug_mode) echo "<strong>DEBUG:</strong> \$arpenyakit_cf KOSONG. Menampilkan pesan 'Tidak ada penyakit yang dapat didiagnosis'.<br>";
                    echo "<div class='callout callout-warning'><h4>Tidak ada penyakit yang dapat didiagnosis berdasarkan gejala yang dipilih atau kombinasi gejala tidak cukup kuat.</h4></div>";
                }
                echo "</div>"; 
                echo "</div>"; 
            } 

        } else { 
            if ($debug_mode) echo "<strong>DEBUG:</strong> Menampilkan FORM DIAGNOSA (karena \$_POST['submit'] tidak terdeteksi).<br>";
            
            echo "<div class='form-diagnosa-content'>";
            echo "
            <h2 class='text text-primary'>Diagnosa Penyakit</h2> <hr>
            <div class='alert alert-success alert-dismissible'>
                <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
                <h4><i class='icon fa fa-exclamation-triangle'></i>Perhatian !</h4>
                Silahkan memilih gejala sesuai dengan kondisi ayam anda, anda dapat memilih kepastian kondisi ayam dari pasti tidak sampai pasti ya, jika sudah tekan tombol proses (<i class='fa fa-search-plus'></i>)  di bawah untuk melihat hasil.
            </div>
            <form name='text_form' method='POST' action='?module=diagnosa'>
                <table class='table table-bordered table-striped konsultasi'><tbody class='pilihkondisi'>
                <tr><th>No</th><th>Kode</th><th>Gejala</th><th width='20%'>Pilih Kondisi</th></tr>";

            $sql_gejala_all = mysqli_query($conn, "SELECT kode_gejala, nama_gejala FROM gejala ORDER BY kode_gejala ASC");
            if (!$sql_gejala_all) {
                if ($debug_mode) echo "<strong>ERROR FORM:</strong> Query Gejala Formulir Gagal: " . mysqli_error($conn) . "<br>";
                error_log("Query Gejala Formulir Gagal: " . mysqli_error($conn));
                if ($debug_mode) echo "</div>"; die("Terjadi kesalahan saat memuat form diagnosa. (Error Code: DB_QUERY_GEJALA_FORM)");
            }

            $arkondisi_opsi = [];
            $sql_kondisi_all = mysqli_query($conn, "SELECT id, kondisi FROM kondisi ORDER BY id ASC");
            if (!$sql_kondisi_all) {
                 if ($debug_mode) echo "<strong>ERROR FORM:</strong> Query Kondisi Opsi Gagal: " . mysqli_error($conn) . "<br>";
                error_log("Query Kondisi Opsi Gagal: " . mysqli_error($conn));
                if ($debug_mode) echo "</div>"; die("Terjadi kesalahan saat memuat form diagnosa. (Error Code: DB_QUERY_KONDISI_FORM)");
            }
            while ($row_kondisi = mysqli_fetch_assoc($sql_kondisi_all)) {
                $arkondisi_opsi[] = $row_kondisi;
            }
            mysqli_free_result($sql_kondisi_all);

            $i = 0;
            while ($r_gejala = mysqli_fetch_assoc($sql_gejala_all)) {
                $i++;
                echo "<tr><td class='opsi'>$i</td>";
                echo "<td class='opsi'>G" . str_pad(htmlspecialchars($r_gejala['kode_gejala']), 3, '0', STR_PAD_LEFT) . "</td>";
                echo "<td class='gejala'>" . htmlspecialchars($r_gejala['nama_gejala']) . "</td>";
                echo '<td class="opsi"><select name="kondisi[]" id="sl' . $i . '" class="form-control opsikondisi"><option data-id="0" value="0">Pilih jika sesuai</option>';

                foreach ($arkondisi_opsi as $rw_kondisi_opt) {
                    ?>
                    <option data-id="<?php echo htmlspecialchars($rw_kondisi_opt['id']); ?>" value="<?php echo htmlspecialchars($r_gejala['kode_gejala'] . '_' . $rw_kondisi_opt['id']); ?>"><?php echo htmlspecialchars($rw_kondisi_opt['kondisi']); ?></option>
                    <?php
                }
                echo '</select></td>';
                ?>
                </tr>
                <?php 
            }
            mysqli_free_result($sql_gejala_all);
            echo "
                </tbody></table>
                
                <div style='clear:both; padding-top:10px;'> 
                "; // Div untuk clearfix jika tombol menggunakan float
            
            // Hapus class 'float' jika tidak benar-benar diperlukan, atau pastikan CSS menanganinya dengan baik
            // Jika Anda tidak menggunakan class 'float' untuk memposisikan tombol ke kanan, Anda bisa hapus 'float'
            echo "
                <button class='btn btn-primary' type='submit' name='submit' data-toggle='tooltip' data-placement='top' title='Klik disini untuk melihat hasil diagnosa'>
                    <i class='fa fa-search-plus'></i> Proses Diagnosa
                </button>
                </div> 
                </form>";
            echo "</div>"; // penutup form-diagnosa-content

            
            // JAVASCRIPT INLINE SUDAH DIKOMENTARI DI VERSI INI
            // Jika Anda ingin mengaktifkannya kembali, pastikan jQuery dan Bootstrap JS juga aktif di index.php
            ?>
            <script type="text/javascript">
                $(document).ready(function () {
                    var arcolor = ['#ffffff', '#cc66ff', '#019AFF', '#00CBFD', '#00FEFE', '#A4F804', '#FFFC00', '#FDCD01', '#FD9A01', '#FB6700'];
                    function applyColorToSelect(selector) {
                        var selectedItem = $(selector + ' :selected');
                        var colorId = selectedItem.data("id"); 
                        $(selector).css({'background-color': arcolor[0], 'color': 'black'});
                        if (colorId !== undefined && arcolor[colorId]) {
                            $(selector).css('background-color', arcolor[colorId]);
                            if (colorId == 0 || colorId == 4 || colorId == 5 || colorId == 6 || colorId == 7){ 
                                $(selector).css('color', 'black');
                            } else if (colorId !=0) { 
                                $(selector).css('color', 'white');
                            }
                        }
                    }
                    $('select.opsikondisi').each(function() {
                        applyColorToSelect('#' + $(this).attr('id'));
                    });
                    $('.pilihkondisi').on('change', 'select.opsikondisi', function () {
                        applyColorToSelect('#' + $(this).attr('id'));
                    });
                    // $('[data-toggle="tooltip"]').tooltip(); // Butuh Bootstrap JS
                });
            </script>
            <?php
            
        }
        break;
}

if ($debug_mode) {
    echo "<br><strong>DEBUG:</strong> Akhir dari skrip diagnosa.php.<br>";
    echo "</div>"; // Penutup div debug
}
?>