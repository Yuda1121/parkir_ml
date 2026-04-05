<h2>Prediksi Parkir</h2>

<form method="post" action="<?= base_url('parkir/prediksi') ?>">

    <label>Hari:</label>
    <select name="hari">
        <option value="senin">Senin</option>
        <option value="selasa">Selasa</option>
        <option value="rabu">Rabu</option>
        <option value="kamis">Kamis</option>
        <option value="jumat">Jumat</option>
        <option value="sabtu">Sabtu</option>
        <option value="minggu">Minggu</option>
    </select>

    <br><br>

    <label>Jam:</label>
    <input type="number" name="jam" min="0" max="23" required>

    <br><br>

    <button type="submit">Prediksi</button>

</form>