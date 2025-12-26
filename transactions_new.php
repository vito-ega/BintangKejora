<?php
require_login();
$customers = pdo()->query("SELECT * FROM customers ORDER BY name")->fetchAll();
$items = pdo()->query("SELECT * FROM items ORDER BY name")->fetchAll();
?>

<a href="transactions" class="btn btn-secondary mb-2">
  <i class="fas fa-arrow-left"></i> Back
</a>

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between">
      <h4>Create Transaction</h4>
    </div>

    <hr style="margin-top:3px; margin-bottom:2rem;">

    <form id="transactionForm" method="post" action="<?= $base_url ?>transactions_save">

      <div class="d-flex justify-content-left mb-3 align-items-end">
        <div class="col-md-6 mb-3">
          <label class="fw-bold">Customer</label>
          <select id="customerSelect" name="customer_id" class="form-select" required>
            <option value="">-- Choose Customer --</option>
            <?php foreach ($customers as $c): ?>
              <option value="<?= $c['id'] ?>">
                <?= htmlspecialchars($c['name']) ?> - <?= htmlspecialchars($c['phone']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <button id="addCustomerBtn" class="btn btn-primary mb-3 ms-2">
          Register New Customer
        </button>
      </div>

      <hr>

      <div class="d-flex justify-content-between mb-1">
        <h5>Items</h5>
        <button type="button"
                class="btn btn-primary mb-3 ms-2"
                data-bs-toggle="modal"
                data-bs-target="#addItemModal">
          + Tambah Item
        </button>
      </div>

      <div class="table-responsive">
        <table class="table align-middle" id="transactionItemsTable">
          <colgroup>
            <col style="width:55%">
            <col style="width:10%">
            <col style="width:15%">
            <col style="width:15%">
            <col style="width:5%">
          </colgroup>
          <thead class="table-light">
            <tr>
              <th>Item</th>
              <th>Qty</th>
              <th>Harga</th>
              <th>Subtotal</th>
              <th></th>
            </tr>
          </thead>
          <tbody></tbody>
          <tfoot>
            <tr class="fw-bold">
              <td colspan="3" class="text-end" style="border-top:2px solid #0000001e;">
                Total
              </td>
              <td style="border-top:2px solid #0000001e;">
                Rp.<span id="totalText">0</span>
              </td>
              <td style="border-top:2px solid #0000001e;"></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <button class="btn btn-success">Simpan Transaction</button>
    </form>
  </div>
</div>

</main>
</div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>

<link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>

</body>
</html>

<!-- =======================
     MODAL TAMBAH ITEM
======================= -->
<div class="modal fade" id="addItemModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Pilih Item</label>
          <select id="itemSelect" class="form-select">
            <option value="">-- Pilih Item --</option>
            <?php foreach ($items as $it): ?>
              <option value="<?= $it['id'] ?>"
                      data-price="<?= $it['price'] ?>"
                      data-name="<?= htmlspecialchars($it['name']) ?>">
                <?= htmlspecialchars($it['name']) ?> -
                <?= number_format($it['price'], 0, ',', '.') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label">Qty</label>
            <input id="qty" type="number" value="1" min="1"
                   class="form-control form-control-sm">
            <input id="itemName" type="hidden">
          </div>
          <div class="col-md-8">
            <label class="form-label">Harga (IDR)</label>
            <input id="priceInput" type="number"
                   class="form-control form-control-sm">
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary"
                data-bs-dismiss="modal">Batal</button>
        <button id="addItemBtn" type="button"
                class="btn btn-primary">Tambah Item</button>
      </div>
    </div>
  </div>
</div>

<!-- =======================
     MODAL TAMBAH CUSTOMER
======================= -->
<div class="modal fade" id="customerModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-body p-0" id="customerModalContent"></div>
    </div>
  </div>
</div>

<style>
#transactionItemsTable input.form-control-sm {
  height: 36px;
  padding: 6px 8px;
}
#transactionItemsTable .subtotalCell {
  font-weight: 600;
}
.removeBtn {
  min-width: 34px;
}
</style>

<script>
const customerPricesCache = {};

document.addEventListener('DOMContentLoaded', function () {
  const customerTom = new TomSelect('#customerSelect', {
    create: false,
    sortField: { field: 'text', direction: 'asc' }
  });

  document.getElementById('customerSelect').addEventListener('change', async function () {
    const custId = this.value;
    if (!custId) return;
    const res = await fetch('ajax_get_customer_prices.php?customer_id=' + custId);
    customerPricesCache[custId] = await res.json();
  });

  new TomSelect('#itemSelect', { create:false });

  document.getElementById('itemSelect').addEventListener('change', function () {
    const opt = this.selectedOptions[0];
    if (!opt) return;
    const cust = document.getElementById('customerSelect').value;
    document.getElementById('itemName').value = opt.dataset.name;
    document.getElementById('priceInput').value =
      customerPricesCache[cust]?.[opt.value] ?? opt.dataset.price;
  });
});

function recalcTotal() {
  let total = 0;
  document.querySelectorAll('#transactionItemsTable tbody tr')
    .forEach(r => total += parseInt(r.dataset.subtotal || 0));
  document.getElementById('totalText').innerText =
    new Intl.NumberFormat('id-ID').format(total);
}

document.getElementById('addItemBtn').addEventListener('click', function () {
  const itemSelect = document.getElementById('itemSelect');
  const qtyInput = document.getElementById('qty');
  const priceInput = document.getElementById('priceInput');
  const itemName = document.getElementById('itemName');

  if (!itemSelect.value) {
    alert('Pilih item');
    return;
  }

  const tbody = document.querySelector('#transactionItemsTable tbody');
  const idx = tbody.children.length;

  const tr = document.createElement('tr');

  tr.innerHTML = `
    <td>
      ${itemName.value}
      <input type="hidden" name="items[${idx}][item_id]" value="${itemSelect.value}">
      <input type="hidden" name="items[${idx}][item_name]" value="${itemName.value}">
    </td>
    <td>
      <input type="number" name="items[${idx}][qty]" value="${qtyInput.value}"
             min="1" class="form-control form-control-sm qtyInput">
    </td>
    <td>
      <input type="number" name="items[${idx}][price]" value="${priceInput.value}"
             class="form-control form-control-sm priceInput">
    </td>
    <td class="subtotalCell">0</td>
    <td class="text-center">
      <button type="button" class="btn btn-sm btn-outline-danger removeBtn">
        <i class="fas fa-trash"></i>
      </button>
    </td>
  `;

  tbody.appendChild(tr);

  // --- fungsi hitung ulang subtotal baris ---
  function updateRowSubtotal() {
    const q = parseInt(tr.querySelector('.qtyInput').value) || 0;
    const p = parseInt(tr.querySelector('.priceInput').value) || 0;
    const s = q * p;

    tr.dataset.subtotal = s;
    tr.querySelector('.subtotalCell').innerText =
      new Intl.NumberFormat('id-ID').format(s);

    recalcTotal();
  }

  // initial calculate
  updateRowSubtotal();

  // listen perubahan qty & price
  tr.querySelector('.qtyInput').addEventListener('input', updateRowSubtotal);
  tr.querySelector('.priceInput').addEventListener('input', updateRowSubtotal);

  // remove row
  tr.querySelector('.removeBtn').addEventListener('click', function () {
    tr.remove();
    recalcTotal();
    
  });

  // reset modal & close
  qtyInput.value = 1;
  priceInput.value = '';
const modalEl = document.getElementById('addItemModal');
const modalInstance = bootstrap.Modal.getInstance(modalEl);

// tutup modal dulu
modalInstance.hide();

// reset SETELAH modal benar-benar tertutup
modalEl.addEventListener('hidden.bs.modal', function handler() {
  resetAddItemModal();
  modalEl.removeEventListener('hidden.bs.modal', handler);
});

});

document.getElementById('addItemModal')
  .addEventListener('hidden.bs.modal', function () {
    resetAddItemModal();
  });


function resetAddItemModal() {
  const itemSelect = document.getElementById('itemSelect');
  const qtyInput = document.getElementById('qty');
  const priceInput = document.getElementById('priceInput');
  const itemName = document.getElementById('itemName');

  // Reset Tom Select
  if (itemSelect && itemSelect.tomselect) {
    itemSelect.tomselect.clear();        // clear value
    itemSelect.tomselect.clearTextbox(); // clear search text
  } else if (itemSelect) {
    itemSelect.value = '';
  }

  if (qtyInput) qtyInput.value = 1;
  if (priceInput) priceInput.value = '';
  if (itemName) itemName.value = '';
}


</script>

<script>
document.getElementById('addCustomerBtn').addEventListener('click', function (e) {
  e.preventDefault();

  fetch('customer_form.php')
    .then(res => res.text())
    .then(html => {
      document.getElementById('customerModalContent').innerHTML = html;

      const modalEl = document.getElementById('customerModal');
      const modal = new bootstrap.Modal(modalEl);
      modal.show();

      const form = document.getElementById('customerForm');
      if (!form) return;

      form.addEventListener('submit', async function (ev) {
        ev.preventDefault();

        const formData = new FormData(form);
        const res = await fetch('customer_form.php?from_modal=1', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();

        if (data.success) {
          const select = document.getElementById('customerSelect');

          const label = data.name + (data.phone ? ' - ' + data.phone : '');

          const opt = document.createElement('option');
          opt.value = data.id;
          opt.textContent = label;
          opt.dataset.phone = data.phone || '';
          select.appendChild(opt);

          if (select.tomselect) {
            select.tomselect.addOption({
              value: data.id,
              text: label
            });
            select.tomselect.setValue(data.id);
          } else {
            select.value = data.id;
          }


          modal.hide();
        } else {
          alert('Gagal menambah customer');
        }
      });
    });
});
</script>


<?php include 'footer.php'; ?>
