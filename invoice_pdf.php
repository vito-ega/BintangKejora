<?php
require_login();
$id = intval($_GET['id'] ?? 0);
$pdo = pdo();
$stmt = $pdo->prepare("SELECT i.*, c.name as customer_name, c.address , c.phone FROM invoices i JOIN customers c ON i.customer_id=c.id WHERE i.id = ?");
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


// --- Data Dummy ---
$company_name = "Bintang Kejora 88";
$company_tagline = "Pabrik plastik terbaik di Indonesia";
$invoice_number = $inv['invoice_no'];
//date in format YYYY-MM-DD
$invoice_date = date('d M Y', strtotime($inv['created_at']));

// Data Payment Info
$payment_info = [
    'account_number' => '07234567890',
    'account_name' => 'PT Bintang Kejora 88',
    'bank_detail' => 'Bank Central Asia (BCA)'
];

// Data Customer
$customer_info = [
    'name' => $inv['customer_name'],
    'address' => $inv['address'],
    'phone' => $inv['phone']
];


// Data Total
$subtotal_amount = $inv['total'];
$tax_percent = '0.00%';
$tax_amount = '0.00';
$amount_due = $inv['total']; 


// --- Kelas FPDF Custom (Hanya Polygon yang diperlukan untuk header) ---
class PDF extends FPDF
{
    // FUNGSI POLYGON 
    function Polygon($p, $style = 'D')
    {
        $op = ($style == 'F') ? 'f' : (($style == 'FD' || $style == 'DF') ? 'B' : 'S');
        $this->_out(sprintf('%.2F %.2F m', $p[0] * $this->k, ($this->h - $p[1]) * $this->k));
        for ($i = 2; $i < count($p); $i += 2)
            $this->_out(sprintf('%.2F %.2F l', $p[$i] * $this->k, ($this->h - $p[$i+1]) * $this->k));
        $this->_out('h '.$op);
    }
    
    // CustomBackground hanya untuk area kanan atas
    function CustomBackground() {
        // Area Gelap di Kanan Atas (Garis diagonal)
        $this->SetFillColor(25, 25, 30); 
        $this->Polygon([140, 0, 210, 0, 210, 60, 180, 20], 'F');
    }
    
    function Header()
    {
        global $company_name, $company_tagline, $invoice_number, $invoice_date , $customer_info;
        
        $this->CustomBackground(); 
        
        // --- Company Logo/Title ---
        $this->SetY(10);
        $this->SetX(10);
        $this->SetTextColor(0, 0, 0); 
        
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 7, $company_name, 0, 1, 'L');
        
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 4, $company_tagline, 0, 1, 'L');
        
        $this->Ln(10); 
        
        // --- Judul INVOICE ---
        $this->SetFont('Arial', 'B', 30);
        $this->Cell(0, 15, 'INVOICE', 0, 1, 'L');
        
        // --- Detail Invoice (Nomor & Tanggal) ---

        $this->SetFont('Arial', '', 10);
        
        // No. Invoice
        $this->SetFont('Arial', 'I', 11);
        $this->Cell(40, 5, $invoice_number, 0, 0, 'L');
        

        
        // Tanggal Invoice
        $this->SetX(100); 
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, "Date : " . $invoice_date, 0, 1, 'L');

        // Customer Info Box
        $this->Ln(2); 
        $this->Cell(0, 5, "To Mr./Mrs. " . $customer_info['name'], 0, 1, 'L');
        $this->Cell(0, 5, $customer_info['address'], 0, 1, 'L');
        $this->Cell(0, 5, "Phone : " . $customer_info['phone'], 0, 1, 'L');
        
        $this->Ln(10); 
    }

    // Footer: show page number
    function Footer()
    {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(100);
        $this->Cell(0,10,'Page '.$this->PageNo().' of {nb}',0,0,'C');
    }

    // Public accessor for page height (h is protected in FPDF)
    public function GetPageHeight()
    {
        return $this->h;
    }

    // FUNGSI UNTUK BARIS ITEM (1 baris)
    function ItemRow($name, $qty, $price, $subtotal , $fill = false) {
        $w = [100, 20, 35, 35]; // 60mm adalah lebar kolom PRICE
        $h = 7;
        if ($fill) {
            // Warna A: Untuk baris genap (fill = true)
            $this->SetFillColor(240, 240, 240); // Abu-abu Muda
        } else {
            // Warna B: Untuk baris ganjil (fill = false)
            $this->SetFillColor(250, 250, 250); // Putih Gading (misalnya)
        }
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 10);
        
        $this->Cell($w[0], $h, $name, 0, 0, 'L', true); 
        $this->Cell($w[1], $h, $qty, 0, 0, 'C', true); 
        //Price dengan format Rp. dan group digits
        $this->Cell($w[2], $h, 'Rp. ' . number_format($price, 0, ',', '.'), 0, 0, 'R', true);
        $this->Cell($w[3], $h, 'Rp. ' . number_format($subtotal, 0, ',', '.'), 0, 1, 'R', true);
      }
}

// --- Inisialisasi PDF ---
$pdf = new PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(false, 0); 

// --- BAGIAN BODY: TABEL BARANG ---
$w = [100, 20, 35, 35]; // 60mm adalah lebar kolom PRICE

// Header Tabel (Latar belakang hitam)
$pdf->SetFillColor(0, 0, 0); 
$pdf->SetTextColor(255, 255, 255); 
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($w[0], 7, 'Nama Barang', 0, 0, 'L', true);
$pdf->Cell($w[1], 7, 'Qty', 0, 0, 'C', true);
$pdf->Cell($w[2], 7, 'Harga', 0, 0, 'R', true);
$pdf->Cell($w[3], 7, 'Jumlah', 0, 1, 'R', true);

// Isi Tabel
$fill_row = false;
// Determine page break threshold (leave room for footer area)
$maxY = $pdf->GetPageHeight() - 60; // 60mm reserved for totals/footer

foreach ($items as $item) {
    // if next row would touch footer area, add a new page and re-print table header
    $currentY = $pdf->GetY();
    $rowHeight = 7; // as defined in ItemRow
    if ($currentY + $rowHeight + 10 > $maxY) {
        $pdf->AddPage();
        // reprint table header on new page
        $pdf->SetFillColor(0, 0, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell($w[0], 7, 'Nama Barang', 0, 0, 'L', true);
        $pdf->Cell($w[1], 7, 'Qty', 0, 0, 'C', true);
        $pdf->Cell($w[2], 7, 'Harga', 0, 0, 'R', true);
        $pdf->Cell($w[3], 7, 'Jumlah', 0, 1, 'R', true);
        $pdf->SetFillColor(250,250,250);
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial', '', 10);
        // small gap after header
        //$pdf->Ln(2);
    }

    $pdf->ItemRow($item['item_name'], $item['qty'], $item['price'], $item['subtotal'],  $fill_row);
    $fill_row = !$fill_row;
}
$pdf->Ln(5);

// Before printing totals, ensure there's enough room; if not, start a new page
$currentY = $pdf->GetY();
$spaceNeededForTotals = 60; // approximate space needed for totals and amount due
if ($currentY + $spaceNeededForTotals > $maxY) {
    $pdf->AddPage();
}


// ------------------------------------------------------------------
// BAGIAN TOTAL, TAX, AMOUNT DUE (DIRATAKAN DENGAN KOLOM PRICE)
// ------------------------------------------------------------------
$line_height_total = 6;
// Total lebar invoice (190mm), dikurangi lebar kolom nilai (60mm)
$total_w_value = 60; // Lebar sama dengan kolom PRICE
$total_w_label = 190 - $total_w_value; // 130mm

$pdf->SetX(10);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);

// Subtotal
$pdf->Cell($total_w_label, $line_height_total, 'Subtotal', 0, 0, 'R');
$pdf->Cell($total_w_value, $line_height_total, 'Rp. ' . number_format($subtotal_amount,0, ',', '.'), 0, 1, 'R');

// Tax
$pdf->SetX(10);
$pdf->Cell($total_w_label, $line_height_total, 'PPN (' . $tax_percent . ')', 0, 0, 'R');
$pdf->Cell($total_w_value, $line_height_total, 'Rp. ' . number_format($tax_amount,0, ',', '.'), 0, 1, 'R');
$pdf->Ln(3);


// Amount Due (Latar belakang hitam/bold)
$pdf->SetX(10);
$pdf->SetFillColor(0, 0, 0);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell($total_w_label, 8, 'Total', 0, 0, 'R', true);
$pdf->Cell($total_w_value, 8, 'Rp. ' . number_format($amount_due,0, ',', '.'), 0, 1, 'R', true);
$pdf->Ln(10); // Jarak sebelum footer bawah


// ------------------------------------------------------------------
// FOOTER (Payment Info, T&C, Tanda Tangan) - fixed to bottom using page height
// ------------------------------------------------------------------
// Compute footer positions relative to page height so it works on A4
$Y_START_FOOTER = $pdf->GetPageHeight() - 60; 
$Y_BOTTOM = $pdf->GetPageHeight() - 17; 

$col_width = 95; 
$left_margin = 10;
$right_col_x = $left_margin + $col_width + 10; 
$line_height = 6;


// --- 1. Payment Info (Kolom Kiri - Mulai Y=230) ---
$pdf->SetY($Y_START_FOOTER); 

$pdf->SetX($left_margin);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell($col_width, 7, 'Payment Info:', 0, 1, 'L');
$pdf->Ln(1);

$pdf->SetX($left_margin);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(35, $line_height, 'Account Number', 0, 0, 'L');
$pdf->Cell(0, $line_height, $payment_info['account_number'], 0, 1, 'L');

$pdf->SetX($left_margin);
$pdf->Cell(35, $line_height, 'Account Name', 0, 0, 'L');
$pdf->Cell(0, $line_height, $payment_info['account_name'], 0, 1, 'L');

$pdf->SetX($left_margin);
$pdf->Cell(35, $line_height, 'Bank Detail', 0, 0, 'L');
$pdf->Cell(0, $line_height, $payment_info['bank_detail'], 0, 1, 'L');


// --- 2. Terms & Condition (Kolom Kiri, di bawah Payment Info) ---
$y_current_left = $pdf->GetY() + 5; 
$pdf->SetY($y_current_left);

$pdf->SetX($left_margin);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell($col_width, 7, 'Terms & Condition', 0, 1, 'L');
$pdf->Ln(1);

$pdf->SetX($left_margin);
$pdf->SetFont('Arial', '', 9);
$pdf->MultiCell($col_width, 4.5, 'Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan dengan alasan apapun.', 0, 'L');


// --- 3. Authorised Sign (Kolom Kanan - Mentok Paling Bawah) ---

// Posisi Y paling bawah (280mm)
$pdf->SetY($Y_BOTTOM); 

// Teks Authorised Sign
$pdf->SetX($right_col_x);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell($col_width, 6, 'Brimob Ebay', 0, 1, 'C'); 

// Garis Tanda Tangan (10mm di atas teks Authorised Sign)
$pdf->SetDrawColor(0, 0, 0);
$pdf->Line($right_col_x + ($col_width * 0.1), $Y_BOTTOM - 3, $right_col_x + ($col_width * 0.9), $Y_BOTTOM - 3);


// --- Output PDF ---
ob_end_clean(); // bersihkan output buffer sebelumnya

$pdf->Output('I', 'Invoice-'.$inv['invoice_no'].'.pdf');
exit;
