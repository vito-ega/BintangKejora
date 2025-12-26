<?php
require_once 'config.php';
require_login();

// --- Tentukan tanggal default bulan ini ---
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-t');

// --- Total nilai dan jumlah invoice ---
$stmt = pdo()->prepare("
    SELECT 
        COALESCE(SUM(total),0) AS total_value,
        COUNT(*) AS invoice_count
    FROM transactions
    WHERE created_at BETWEEN ? AND ?
");
$stmt->execute([$start, $end]);
$summary = $stmt->fetch();

// --- Top 3 customer berdasarkan total belanja ---
$stmt2 = pdo()->prepare("
    SELECT 
        c.name AS customer_name,
        SUM(i.total) AS total_belanja
    FROM transactions i
    JOIN customers c ON c.id = i.customer_id
    WHERE i.created_at BETWEEN ? AND ?
    GROUP BY c.id, c.name
    ORDER BY total_belanja DESC
    LIMIT 3
");
$stmt2->execute([$start, $end]);
$top_customers = $stmt2->fetchAll();

// --- Top 3 produk terlaris (berdasarkan qty) ---
$stmt3 = pdo()->prepare("
    SELECT 
        it.name AS item_name,
        SUM(ii.qty) AS total_qty
    FROM transaction_items ii
    JOIN items it ON it.id = ii.item_id
    JOIN transactions i ON i.id = ii.transaction_id
    WHERE i.created_at BETWEEN ? AND ?
    GROUP BY it.id, it.name
    ORDER BY total_qty DESC
    LIMIT 3
");
$stmt3->execute([$start, $end]);
$top_products = $stmt3->fetchAll();
?>



<div class="">
    <div class="d-flex justify-content-between mb-3 align-items-center">
      <div>
        <h4>Dashboard</h4>
        <p class="fst-italic text-muted small">Ringkasan seluruh transaksi berdasarkan filter tanggal</p>
      </div>
      <div>
          <form method="get" class="row g-2 align-items-end mb-4">
            <div class="col-auto">
            <label class="form-label fw-bold">Start Date</label>
            <input type="date" id="startDate" value="<?=$start?>" class="form-control">
            </div>
            <div class="col-auto">
            <label class="form-label fw-bold">End Date</label>
            <input type="date" id="endDate" value="<?=$end?>" class="form-control">
            </div>
        </form>   
      </div>
    </div>


  <div class="row text-center mb-4">
    <div class="col-md-4 mb-3">
      <div class="card shadow-sm" style="border: none; border-left: 5px solid #A7C7E7; border-radius: .5rem;"> 
        <div class="card-body">
          <h6>Total Nilai Transaksi</h6>
          <h3 class="fw-bold" style="color: #6B8EBF;">Rp. <?=number_format($summary['total_value'],0,',','.')?></h3> 
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="card shadow-sm" style="border: none; border-left: 5px solid #B0E0E6; border-radius: .5rem;"> 
        <div class="card-body">
          <h6>Jumlah Transaksi</h6>
          <h3 class="fw-bold" style="color: #77BBDD;"><?=$summary['invoice_count']?></h3> 
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="card shadow-sm" style="border: none; border-left: 5px solid #FFDAB9; border-radius: .5rem;"> 
        <div class="card-body">
          <h6>Periode</h6>
          <h5 style="color: #b68771ff;"><?=date('d M Y', strtotime($start))?> - <?=date('d M Y', strtotime($end))?></h5> 
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6 mb-4">
      <div class="card shadow-sm" style="border: 1px solid #ddd; border-radius: .5rem;"> 
        <div class="card-header fw-bold" style="background-color: #E6F0F6; color: #555; border-bottom: 1px solid #ddd;">👑 Top 3 Customer</div> 
        <div class="card-body">
          <?php if ($top_customers): ?>
            <table class="table" style="border: none;"> 
              <thead><tr><th>Rank</th><th>Customer</th><th>Total Belanja</th></tr></thead>
              <tbody>
                <?php foreach ($top_customers as $i => $c): ?>
                  <tr style="border-bottom: 1px solid #f5f5f5;"> <td class="fw-bold" style="color: #888; border-top: none; padding-left: 0.5rem;"><?=$i+1?></td> 
                    <td style="border-top: none;"><?=htmlspecialchars($c['customer_name'])?></td>
                    <td style="border-top: none;"><?=number_format($c['total_belanja'],0,',','.')?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <p class="text-muted">Belum ada data.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-md-6 mb-4">
      <div class="card shadow-sm" style="border: 1px solid #ddd; border-radius: .5rem;"> 
        <div class="card-header fw-bold" style="background-color: #E6F6EF; color: #555; border-bottom: 1px solid #ddd;">📦 Top 3 Produk Terlaris</div> 
        <div class="card-body">
          <?php if ($top_products): ?>
            <table class="table" style="border: none;"> 
              <thead><tr><th>Rank</th><th>Produk</th><th>Qty Terjual</th></tr></thead>
              <tbody>
                <?php foreach ($top_products as $i => $p): ?>
                  <tr style="border-bottom: 1px solid #f5f5f5;"> <td class="fw-bold" style="color: #888; border-top: none; padding-left: 0.5rem;"><?=$i+1?></td> 
                    <td style="border-top: none;"><?=htmlspecialchars($p['item_name'])?></td>
                    <td style="border-top: none;"><?=$p['total_qty']?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <p class="text-muted">Belum ada data.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>


<script>
  const startInput = document.getElementById('startDate');
  const endInput = document.getElementById('endDate');

  function reloadWithDate() {
    const start = startInput.value;
    const end = endInput.value;
    if (!start || !end) return; // biar gak error kalau belum lengkap
    const url = new URL(window.location.href);
    url.searchParams.set('start', start);
    url.searchParams.set('end', end);
    window.location.href = url.toString();
  }

  startInput.addEventListener('change', reloadWithDate);
  endInput.addEventListener('change', reloadWithDate);
</script>


<?php
include 'footer.php';
?>