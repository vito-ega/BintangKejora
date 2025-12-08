<?php
require_once 'config.php';
require_once 'helper.php';
require_login();
?>

<h2>Import Items</h2>

<form action="items_import_process.php" method="post" enctype="multipart/form-data">
    <label for="file">Pilih file Excel (.xlsx / .xls)</label><br>
    <input type="file" name="file" accept=".xls,.xlsx" required><br><br>
    <button type="submit">Import</button>
</form>
