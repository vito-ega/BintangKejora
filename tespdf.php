<?php
$fpdf_path = __DIR__ . '/libs/fpdf/fpdf.php';
require_once $fpdf_path;

// --- Data Dummy ---
$company_name = "Company";
$company_tagline = "REAL ESTATE AGENCY";
$invoice_number = "0001 A";
$invoice_date = "21 / 07 / 2024";

// Data Payment Info
$payment_info = [
    'account_number' => '123 456 789',
    'account_name' => 'Charles James Peter',
    'bank_detail' => 'Add your detail'
];

$items = [
    ['item' => 'Add item description for service or equipment', 'qty' => '00', 'price' => '00000'],
    ['item' => 'Add item description for service or equipment', 'qty' => '00', 'price' => '00000'],
    ['item' => 'Add item description for service or equipment', 'qty' => '00', 'price' => '00000'],
    ['item' => 'Add item description for service or equipment', 'qty' => '00', 'price' => '00000']
];

// Data Total
$subtotal_amount = '180.00';
$tax_percent = '0.00%';
$tax_amount = '0.00';
$amount_due = '180.00'; 


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
        global $company_name, $company_tagline, $invoice_number, $invoice_date;
        
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
        $this->Ln(2); 
        $this->SetFont('Arial', '', 10);
        
        // No. Invoice
        $this->Cell(20, 5, 'INVOICE', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(40, 5, 'N' . chr(176) . ' ' . $invoice_number, 0, 0, 'L');
        
        // Shape dekoratif (Black Chevron)
        $x_chevron = $this->GetX() - 40;
        $y_chevron = $this->GetY() + 0;
        $this->SetFillColor(30, 30, 30);
        $this->Polygon([$x_chevron + 40, $y_chevron, $x_chevron + 40, $y_chevron + 6, $x_chevron + 46, $y_chevron + 3], 'F');
        
        // Tanggal Invoice
        $this->SetX(100); 
        $this->SetFont('Arial', '', 10);
        $this->Cell(20, 5, 'DATE', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 5, $invoice_date, 0, 1, 'L');
        
        $this->Ln(10); 
    }

    // FUNGSI UNTUK BARIS ITEM (1 baris)
    function ItemRow($item, $qty, $price, $fill = false) {
        $w = [110, 20, 60]; // 60mm adalah lebar kolom PRICE
        $h = 7;
        $this->SetFillColor(240, 240, 240); 
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 10);
        
        $this->Cell($w[0], $h, $item['item'], 0, 0, 'L', $fill); 
        $this->Cell($w[1], $h, $qty, 0, 0, 'C', $fill); 
        $this->Cell($w[2], $h, '$' . $price, 0, 1, 'R', $fill); 
    }
}

// --- Inisialisasi PDF ---
$pdf = new PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(false, 0); 

// --- BAGIAN BODY: TABEL BARANG ---
$w = [110, 20, 60]; // 60mm adalah lebar kolom PRICE

// Header Tabel (Latar belakang hitam)
$pdf->SetFillColor(0, 0, 0); 
$pdf->SetTextColor(255, 255, 255); 
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($w[0], 7, 'ITEM DESCRIPTION', 0, 0, 'L', true);
$pdf->Cell($w[1], 7, 'QTY.', 0, 0, 'C', true);
$pdf->Cell($w[2], 7, 'PRICE', 0, 1, 'R', true);

// Isi Tabel
$fill_row = false;
foreach ($items as $item) {
    $pdf->ItemRow($item, $item['qty'], $item['price'], $fill_row);
    $fill_row = !$fill_row; 
}
$pdf->Ln(5); 


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
$pdf->Cell($total_w_label, $line_height_total, 'SUBTOTAL', 0, 0, 'R');
$pdf->Cell($total_w_value, $line_height_total, '$' . $subtotal_amount, 0, 1, 'R');

// Tax
$pdf->SetX(10);
$pdf->Cell($total_w_label, $line_height_total, 'TAX (' . $tax_percent . ')', 0, 0, 'R');
$pdf->Cell($total_w_value, $line_height_total, '$' . $tax_amount, 0, 1, 'R');
$pdf->Ln(3);


// Amount Due (Latar belakang hitam/bold)
$pdf->SetX(10);
$pdf->SetFillColor(0, 0, 0);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell($total_w_label, 8, 'AMOUNT DUE', 0, 0, 'R', true);
$pdf->Cell($total_w_value, 8, '$' . $amount_due, 0, 1, 'R', true);
$pdf->Ln(10); // Jarak sebelum footer bawah


// ------------------------------------------------------------------
// FOOTER (Payment Info, T&C, Tanda Tangan) - MENTOK BAWAH
// ------------------------------------------------------------------
$Y_START_FOOTER = 230; 
$Y_BOTTOM = 280; 

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
$pdf->MultiCell($col_width, 4.5, 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci.', 0, 'L');


// --- 3. Authorised Sign (Kolom Kanan - Mentok Paling Bawah) ---

// Posisi Y paling bawah (280mm)
$pdf->SetY($Y_BOTTOM); 

// Teks Authorised Sign
$pdf->SetX($right_col_x);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell($col_width, 6, 'Authorised Sign', 0, 1, 'C'); 

// Garis Tanda Tangan (10mm di atas teks Authorised Sign)
$pdf->SetDrawColor(0, 0, 0);
$pdf->Line($right_col_x + ($col_width * 0.1), $Y_BOTTOM - 10, $right_col_x + ($col_width * 0.9), $Y_BOTTOM - 10);


// --- Output PDF ---
$pdf->Output('I', 'Invoice_Final_Layout_V3.pdf'); 

?>