<?php
require_once 'config.php';
require_once 'helper.php';
require_login();

// pastikan autoload PhpSpreadsheet sudah benar
require_once __DIR__ . '/vendor/autoload.php'; // atau sesuaikan jika kamu taruh di libs/

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// Ambil data item
$stmt = pdo()->query("SELECT name, price FROM items ORDER BY name");
$items = $stmt->fetchAll();

// Buat spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Header
$sheet->setCellValue('A1', 'Nama Item');
$sheet->setCellValue('B1', 'Harga');

// Styling header
$sheet->getStyle('A1:B1')->getFont()->setBold(true);
$sheet->getColumnDimension('A')->setWidth(40);
$sheet->getColumnDimension('B')->setWidth(20);

// Isi data
$row = 2;
foreach ($items as $item) {
    $sheet->setCellValue('A' . $row, $item['name']);
    $sheet->setCellValueExplicit('B'.$row, $item['price'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('B'.$row)
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

    $row++;
}

// Output file Excel
$filename = 'daftar_item_' . date('Y-m-d_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
