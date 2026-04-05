<h2>Hasil Prediksi</h2>

<p>Hari: <?= ucfirst($result['hari']) ?></p>
<p>Jam: <?= $result['jam'] ?></p>

<hr>

<p>Mobil: <?= $result['mobil'] ?></p>
<p>Motor: <?= $result['motor'] ?></p>
<p>Total: <?= $result['total'] ?></p>

<br>

<a href="<?= base_url('parkir') ?>">Kembali</a>