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
              <th>Qty (PCS)</th>
              <th>Qty (Satuan)</th>
              <th>Harga / PCS</th>
              <th>Subtotal</th>
              <th></th>
            </tr>
          </thead>
          <tbody></tbody>
          <tfoot>
            <tr class="fw-bold">
              <td colspan="4" class="text-end" style="border-top:2px solid #0000001e;">
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
                      data-name="<?= htmlspecialchars($it['name']) ?>"
                      data-unit="<?= htmlspecialchars($it['unit_name'] ?? 'pcs') ?>"
                      data-multiplier="<?= $it['unit_multiplier'] ?? 1 ?>">
                <?= htmlspecialchars($it['name']) ?>
                (<?= $it['unit_name'] ?? 'pcs' ?> x<?= $it['unit_multiplier'] ?? 1 ?>)
                - <?= number_format($it['price'], 0, ',', '.') ?>
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
    .forEach(tr => total += parseInt(tr.dataset.subtotal || 0));
  document.getElementById('totalText').innerText = formatIDR(total);
}

function formatIDR(n){
  return new Intl.NumberFormat('id-ID').format(n);
}


document.getElementById('addItemBtn').addEventListener('click', function () {
  const sel = document.getElementById('itemSelect');
  const opt = sel.selectedOptions[0];
  if (!opt) { alert('Pilih item'); return; }

  const tbody = document.querySelector('#transactionItemsTable tbody');
  const idx = tbody.children.length;

  const pricePCS = parseInt(opt.dataset.price);
  const modalQtyPCS = parseInt(document.getElementById('qty').value) || 0;
  const multiplier = parseFloat(opt.dataset.multiplier) || 1;
  const qtyUnitInit = multiplier
  ? (modalQtyPCS / multiplier).toFixed(2)
  : 0;
  const unitName = opt.dataset.unit;

  const tr = document.createElement('tr');
  tr.dataset.multiplier = multiplier;
  tr.dataset.price = pricePCS;
  tr.dataset.subtotal = 0;

tr.innerHTML = `
  <td>
    <input type="text"
           class="form-control form-control-sm item-name-input"
           name="items[${idx}][item_name]"
           value="${opt.dataset.name}">
    <input type="hidden" name="items[${idx}][item_id]" value="${opt.value}">
    <input type="hidden" name="items[${idx}][qty]" class="qty-hidden" value="${modalQtyPCS}">
    <input type="hidden" name="items[${idx}][price]" value="${pricePCS}">
  </td>

  <td>
    <div class="d-flex align-items-center gap-2">
    <input type="number" min="0"
           class="form-control form-control-sm qty-pcs"
           value="${modalQtyPCS}">
    <small class="text-muted fst-italic text-nowrap">
      pcs
    </small>
    </div>
  </td>

<td>
  <div class="d-flex align-items-center gap-2">
    <input type="number" min="0" step="0.01"
           class="form-control form-control-sm qty-unit"
           value="${qtyUnitInit}"
           style="width:90px">

    <small class="text-muted fst-italic text-nowrap">
      ${unitName} (x${multiplier})
    </small>
  </div>
</td>


  <td>Rp ${formatIDR(pricePCS)}</td>

  <td class="subtotalCell">0</td>

  <td class="text-center">
    <button type="button" class="btn btn-sm btn-outline-danger removeBtn">
      <i class="fas fa-trash"></i>
    </button>
  </td>
`;



  tbody.appendChild(tr);
  // initial subtotal
const initialSubtotal = modalQtyPCS * pricePCS;
tr.dataset.subtotal = initialSubtotal;
tr.querySelector('.subtotalCell').innerText = formatIDR(initialSubtotal);
recalcTotal();


  const qtyPCS = tr.querySelector('.qty-pcs');
  const qtyUnit = tr.querySelector('.qty-unit');
  const qtyHidden = tr.querySelector('.qty-hidden');

  function recalcFromPCS(){
    const pcs = parseInt(qtyPCS.value) || 0;
    qtyUnit.value = (pcs / multiplier).toFixed(2);
    const subtotal = pcs * pricePCS;
    qtyHidden.value = pcs;
    tr.dataset.subtotal = subtotal;
    tr.querySelector('.subtotalCell').innerText = formatIDR(subtotal);
    recalcTotal();
  }

  function recalcFromUnit(){
    const unit = parseFloat(qtyUnit.value) || 0;
    const pcs = Math.round(unit * multiplier);
    qtyPCS.value = pcs;
    const subtotal = pcs * pricePCS;
    qtyHidden.value = pcs;
    tr.dataset.subtotal = subtotal;
    tr.querySelector('.subtotalCell').innerText = formatIDR(subtotal);
    recalcTotal();
  }

  qtyPCS.addEventListener('input', recalcFromPCS);
  qtyUnit.addEventListener('input', recalcFromUnit);

  tr.querySelector('.removeBtn').addEventListener('click', () => {
    tr.remove();
    recalcTotal();
  });

  // reset modal
  if (sel.tomselect) sel.tomselect.clear();
  bootstrap.Modal.getInstance(document.getElementById('addItemModal')).hide();
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
