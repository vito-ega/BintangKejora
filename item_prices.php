<?php
require_login();

$item_id = $_GET['id'] ?? null;
if (!$item_id) {
  die("Item ID tidak ditemukan.");
}

// Ambil data item
$stmt = pdo()->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();
if (!$item) {
  die("Customer tidak ditemukan.");
}

// Ambil daftar customer + harga khusus
$stmt = pdo()->prepare("
  SELECT 
    c.id AS customer_id,
    c.name AS customer_name,
    cip.price AS special_price
  FROM customers c
  LEFT JOIN customer_item_price cip 
    ON cip.customer_id = c.id
    AND cip.item_id = ?
  ORDER BY c.name
");
$stmt->execute([$item_id]);
$prices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<a href="items" class="btn btn-secondary mb-2">
    <i class="fas fa-arrow-left"></i> Back
</a>

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5>Harga Khusus untuk barang <?= htmlspecialchars($item['name']) ?></h5>
        <h5>Harga Normal : Rp. <?= $item['price'] !== null ? number_format($item['price'], 0, ',', '.') : '-' ?></h5>
    </div>

    <!-- 🔍 Search bar untuk filter -->
    <div class="mb-3 col-md-4">
      <div class="input-group">
        <span class="input-group-text">
          <i class="fas fa-search"></i> 
        </span>
        <input type="text" id="searchInput" class="form-control" placeholder="Cari nama customer...">
      </div>
  </div>

    <table class="table table-bordered table-striped align-middle" id="priceTable">
      <thead>
        <tr>
          <th>Customer</th>
          <th>Harga Khusus</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($prices as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['customer_name']) ?></td>
            <td>
            <input type="text"
                    class="form-control form-control-sm special-price-input"
                    data-customer-id="<?= $p['customer_id'] ?>"
                    value="<?= htmlspecialchars($p['special_price'] ?? '') ?>"
                    placeholder="-"
                    autocomplete="off">
            </td>
            <td>
              <button class="btn btn-sm btn-success save-price-btn" data-customer-id="<?= $p['customer_id'] ?>">
                  <i class="fa fa-save"></i>
            </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>



<script>

function formatNumber(value) {
    value = value.replace(/\D/g, '');
    return value === '' ? '' : new Intl.NumberFormat('id-ID').format(value);
}

// ✅ Format saat page pertama kali dibuka
document.querySelectorAll('.special-price-input').forEach(input => {
    input.value = formatNumber(input.value);
});

// ✅ Format saat user mengetik
document.addEventListener('input', function (e) {
    if (!e.target.classList.contains('special-price-input')) return;
    e.target.value = formatNumber(e.target.value);
});

// ✅ Helper ambil angka asli
function getRawNumber(input) {
    return input.value.replace(/\D/g, '');
}

/* === Filtering barang === */
document.getElementById('searchInput').addEventListener('keyup', function () {
  const filter = this.value.toLowerCase();
  const rows = document.querySelectorAll('#priceTable tbody tr');
  rows.forEach(row => {
    const name = row.cells[0].textContent.toLowerCase();
    row.style.display = name.includes(filter) ? '' : 'none';
  });
});

/* === Simpan harga khusus === */
document.querySelectorAll('.save-price-btn').forEach(btn => {
  btn.addEventListener('click', async () => {
    const customerId = btn.dataset.customerId;
    const input = document.querySelector(`.special-price-input[data-customer-id="${customerId}"]`);
    const specialPrice = getRawNumber(input);
    const itemId = <?= json_encode($item_id) ?>;
    console.log('Saving price for customer', customerId, 'item', itemId, 'price', specialPrice);

    const formData = new FormData();
    formData.append('customer_id', customerId);
    formData.append('item_id', itemId);
    formData.append('special_price', specialPrice);

    const res = await fetch('ajax_save_customer_price.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.success) {
      btn.classList.remove('btn-primary');
      btn.classList.add('btn-success');
      btn.textContent = 'Tersimpan';
      setTimeout(() => {
        btn.classList.remove('btn-success');
        btn.classList.add('btn-success');
        btn.innerHTML = '<i class="fa fa-save"></i>';
      }, 1500);
    } else {
      alert('Gagal menyimpan harga!');
    }
  });
});
</script>


<?php
include 'footer.php';
?>