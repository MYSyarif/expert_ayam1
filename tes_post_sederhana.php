<?php
// Aktifkan pelaporan error dasar
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Tes Form POST Sederhana</h1>";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "<br><br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "Data POST yang diterima:<br>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
}
?>

<hr>
<form method="POST" action="tes_post_sederhana.php">
    <label for="nama">Nama:</label>
    <input type="text" id="nama" name="nama_pengguna" value="Pengguna Tes">
    <br><br>
    <button type="submit" name="submit_tes">Kirim Tes POST</button>
</form>