<?php
require_login();
$id = intval($_GET['id'] ?? 0);
$pdo = pdo();
$stmt = $pdo->prepare("SELECT i.*, c.name as customer_name, c.address FROM invoices i JOIN customers c ON i.customer_id=c.id WHERE i.id = ?");
$stmt->execute([$id]);
$inv = $stmt->fetch();
if (!$inv) { echo "Invoice tidak ditemukan"; exit; }
$items = $pdo->prepare("SELECT ii.*, it.name FROM invoice_items ii JOIN items it ON ii.item_id = it.id WHERE ii.invoice_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();

// FPDF must be present at libs/fpdf/fpdf.php
$fpdf_path = __DIR__ . '/libs/fpdf/fpdf.php';
if (!file_exists($fpdf_path)) {
    echo 'FPDF library tidak ditemukan. Silakan download dari http://www.fpdf.org/ dan taruh di libs/fpdf/fpdf.php';
    exit;
}
require_once $fpdf_path;

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,8,'Bintang Kejora 88',0,1,'C');
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6,'Jalan desa dadap raya no.88 , Banten , Jawa Barat',0,1,'C');
$pdf->Ln(4);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(100,6,'Invoice No: ' . $inv['invoice_no'],0,0);
$pdf->Cell(0,6,'Tanggal: ' . $inv['created_at'],0,1);
$pdf->Ln(4);

$pdf->SetFont('Arial','',11);
$pdf->Cell(0,6,'Customer: ' . $inv['customer_name'],0,1);
$pdf->MultiCell(0,6,'Alamat: ' . $inv['address'],0,1);
$pdf->Ln(4);

$pdf->SetFont('Arial','B',11);
$pdf->Cell(80,7,'Item',1);
$pdf->Cell(20,7,'Qty',1,0,'R');
$pdf->Cell(40,7,'Harga',1,0,'R');
$pdf->Cell(40,7,'Subtotal',1,1,'R');
$pdf->SetFont('Arial','',11);

foreach($items as $it){
  $pdf->Cell(80,7,substr($it['name'],0,40),1);
  $pdf->Cell(20,7,$it['qty'],1,0,'R');
  $pdf->Cell(40,7,number_format($it['price'],0,',','.'),1,0,'R');
  $pdf->Cell(40,7,number_format($it['qty']*$it['price'],0,',','.'),1,1,'R');
}

$pdf->SetFont('Arial','B',12);
$pdf->Cell(140,10,'Total',1);
$pdf->Cell(40,10,number_format($inv['total'],0,',','.'),1,1,'R');



ob_end_clean(); // bersihkan output buffer sebelumnya

$pdf->Output('I', 'Invoice-'.$inv['invoice_no'].'.pdf');
exit;
