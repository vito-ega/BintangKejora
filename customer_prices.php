<?php
require_login();

$customer_id = $_GET['id'] ?? null;
if (!$customer_id) {
  die("Customer ID tidak ditemukan.");
}

// Ambil data customer
$stmt = pdo()->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();
if (!$customer) {
  die("Customer tidak ditemukan.");
}

// Ambil daftar item + harga khusus
$stmt = pdo()->prepare("
  SELECT 
    i.id AS item_id,
    i.name AS item_name,
    i.price AS normal_price,
    cip.price AS special_price
  FROM items i
  LEFT JOIN customer_item_price cip 
    ON cip.item_id = i.id 
    AND cip.customer_id = ?
  ORDER BY i.name
");
$stmt->execute([$customer_id]);
$prices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<a href="customers" class="btn btn-secondary mb-2">
    <i class="fas fa-arrow-left"></i> Back
</a>

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5>Harga Khusus untuk <?= htmlspecialchars($customer['name']) ?></h5>
    </div>

    <!-- 🔍 Search bar untuk filter -->
    <div class="mb-3 col-md-4">
      <div class="input-group">
        <span class="input-group-text">
          <i class="fas fa-search"></i> 
        </span>
        <input type="text" id="searchInput" class="form-control" placeholder="Cari nama barang...">
      </div>
  </div>

    <table class="table table-bordered table-striped align-middle" id="priceTable">
      <thead>
        <tr>
          <th>Barang</th>
          <th>Harga Normal</th>
          <th>Harga Khusus</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($prices as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['item_name']) ?></td>
            <td><?= number_format($p['normal_price'], 0, ',', '.') ?></td>
            <td>
              <input type="text" 
                     class="form-control form-control-sm special-price-input" 
                     data-item-id="<?= $p['item_id'] ?>"
                     value="<?= htmlspecialchars($p['special_price'] ?? '') ?>"
                     placeholder="-" 
                     autocomplete="off">
            </td>
            <td>
              <button class="btn btn-sm btn-primary save-price-btn" data-item-id="<?= $p['item_id'] ?>">
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
    const itemId = btn.dataset.itemId;
    const input = document.querySelector(`.special-price-input[data-item-id="${itemId}"]`);
    const specialPrice = getRawNumber(input);
    const customerId = <?= json_encode($customer_id) ?>;

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
        btn.classList.add('btn-primary');
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