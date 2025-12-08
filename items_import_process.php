<?php
require_once 'config.php';
require_once 'helper.php';

require_once __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_FILES['file']['tmp_name'])) {
    $filePath = $_FILES['file']['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $isFirst = true;
        $imported = 0;
        $updated = 0;

        foreach ($rows as $row) {
            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            $name  = trim($row[0] ?? '');

            if ($name === '' || $price === '') continue;

            $price = trim($row[1] ?? '');
            if ($price === '') continue;

            // Hapus simbol dan teks yang tidak relevan (Rp, spasi, dll)
            $price = preg_replace('/[^\d.,-]/', '', $price);

            // Coba deteksi format desimal berdasarkan tanda koma/titik terakhir
            if (substr_count($price, ',') > 0 && strrpos($price, ',') > strrpos($price, '.')) {
                // Format Indonesia/Eropa (1.234,56)
                $price = str_replace('.', '', $price);
                $price = str_replace(',', '.', $price);
            } else {
                // Format Inggris (1,234.56)
                $price = str_replace(',', '', $price);
            }

            $price = floatval($price);

            $stmt = pdo()->prepare("SELECT id FROM items WHERE name = ?");
            $stmt->execute([$name]);
            $existing = $stmt->fetch();

            if ($existing) {
                $updateStmt = pdo()->prepare("UPDATE items SET price = ? WHERE id = ?");
                $updateStmt->execute([$price, $existing['id']]);
                $updated++;
            } else {
                $insertStmt = pdo()->prepare("INSERT INTO items (name, price) VALUES (?, ?)");
                $insertStmt->execute([$name, $price]);
                $imported++;
            }
        }

        flash_set('success', "$imported item baru dimasukkan, $updated item diupdate.");
        header('Location: ' . $base_url . 'items');

    } catch (Exception $e) {
        echo "Terjadi kesalahan: " . $e->getMessage();
    }

} else {
    echo "Tidak ada file yang diupload.";
}
