<?php
require_login();
$customers = pdo()->query("SELECT * FROM customers ORDER BY name")->fetchAll();
$items = pdo()->query("SELECT * FROM items ORDER BY name")->fetchAll();
?>

<a href="invoices" class="btn btn-secondary mb-2">
    <i class="fas fa-arrow-left"></i> Back
</a>
<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between ">
      <h4>Create Invoices</h4>
    </div>
    <hr style="margin-top:3px; margin-bottom:2rem;">
    <form id="invoiceForm" method="post" action="<?php echo $base_url; ?>invoices_save">
      <div class="d-flex justify-content-left mb-3 align-items-end">
      <div class="col-md-6 mb-3">
        <label class="fw-bold">Customer</label>
        <!-- searchable select powered by Tom Select (prevents free-text entry) -->
        <select id="customerSelect" name="customer_id" class="form-select" required>
          <option value="">-- Choose Customer --</option>
          <?php foreach($customers as $c): ?>
            <option value="<?=$c['id']?>"><?=htmlspecialchars($c['name'])?> - <?=htmlspecialchars($c['phone'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
        <button id="addCustomerBtn" class="btn btn-primary mb-3 ms-2">Register New Customer</button>
      </div>

      <hr>
    <div class="d-flex justify-content-between mb-1">
      <h5>Items</h5>
      <button type="button" class="btn btn-primary mb-3 ms-2" data-bs-toggle="modal" data-bs-target="#addItemModal">
        + Tambah Item
      </button>
    </div>



<div class="table-responsive">
  <table class="table table-bordered align-middle" id="invoiceItemsTable">
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
        <th class="">Qty</th>
        <th class="">Harga</th>
        <th class="">Subtotal</th>
        <th></th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>


      <div class="text-end mb-3">
        <strong>Total: <span id="totalText">0</span></strong>
      </div>

      <button class="btn btn-success">Simpan Invoice</button>
    </form>
  </div>
</div>

    </main> <!-- end main -->
  </div> <!-- page content -->
</div> <!-- wrapper -->

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<!-- Tom Select (CDN) for searchable select -->
<link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
</body>
</html>


<!-- Modal Tambah Item -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addItemModalLabel">Tambah Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Pilih Item</label>
          <select id="itemSelect" class="form-select">
            <option value="">-- Pilih Item --</option>
            <?php foreach($items as $it): ?>
              <option value="<?=$it['id']?>" data-price="<?=$it['price']?>" data-name="<?=htmlspecialchars($it['name'])?>">
                <?=htmlspecialchars($it['name'])?> - <?=number_format($it['price'],0,',','.')?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label">Qty</label>
            <input id="qty" type="number" value="1" min="1" class="form-control form-control-sm">
            <input id="itemName" type="hidden">
          </div>
          <div class="col-md-8">
            <label class="form-label">Harga (IDR)</label>
            <input id="priceInput" type="number" class="form-control form-control-sm" placeholder="Harga manual (opsional)">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button id="addItemBtn" type="button" class="btn btn-primary">Tambah Item</button>
      </div>
    </div>
  </div>
</div>



<!-- Modal Tambah Customer -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-body p-0" id="customerModalContent">
        <!-- form akan dimuat di sini -->
      </div>
    </div>
  </div>
</div>

<style>
  /* small UI tweaks for invoice table */
  #invoiceItemsTable input.form-control-sm {
    height: 36px;
    padding: 6px 8px;
  }
  #invoiceItemsTable .subtotalCell { font-weight:600; }
  .removeBtn { min-width:34px; }
</style>




<script>
const itemsData = <?php echo json_encode($items); ?>;
const customerPricesCache = {};

// Initialize Tom Select on the customer select, disallow creation of new items
let customerTom = null;
document.addEventListener('DOMContentLoaded', function(){
  customerTom = new TomSelect('#customerSelect', {
    create: false,
    sortField: { field: 'text', direction: 'asc' }
  });

  // When customer changes, fetch their prices
  document.getElementById('customerSelect').addEventListener('change', async function(){
    const custId = this.value;
    if(!custId) return;
    try {
      const res = await fetch('ajax_get_customer_prices.php?customer_id=' + custId);
      const data = await res.json();
      customerPricesCache[custId] = data;
    } catch (e) {
      console.error('Failed to load customer prices', e);
    }
  });
});

document.addEventListener('DOMContentLoaded', function () {
  const modalEl = document.getElementById('addItemModal');
  const itemSelect = document.getElementById('itemSelect');
  const qtyInput = document.getElementById('qty');
  const priceInput = document.getElementById('priceInput');
  const itemNameHidden = document.getElementById('itemName');

  // Reset function
  function resetAddItemModal() {
    // If TomSelect instance exists, use its API to clear
    if (itemSelect && itemSelect.tomselect) {
      itemSelect.tomselect.clear();   // clears selection and typed text
      itemSelect.tomselect.clearTextbox(); // optional: clear search box (TomSelect v2)
    } else if (itemSelect) {
      itemSelect.value = ''; // native select fallback
    }

    if (qtyInput) qtyInput.value = 1;
    if (priceInput) priceInput.value = '';
    if (itemNameHidden) itemNameHidden.value = '';
  }

  // Clear when modal is hidden (user closed or cancelled)
  if (modalEl) {
    modalEl.addEventListener('hidden.bs.modal', function () {
      resetAddItemModal();
    });

    // Optional: also reset when modal is shown (guarantee clean state)
    modalEl.addEventListener('show.bs.modal', function () {
      resetAddItemModal();
    });
  }
});

// itemSelect will be initialized with Tom Select as well; wire change handler after DOM loaded
function onItemSelectChange(tsInstance) {
  // support both Tom Select instance and native select
  const sel = document.getElementById('itemSelect');
  const opt = sel.selectedOptions ? sel.selectedOptions[0] : null;
  if(!opt) return;
  const basePrice = opt.dataset.price || 0;
  const cust = document.getElementById('customerSelect').value;
  if(cust && customerPricesCache[cust] && customerPricesCache[cust][opt.value]) {
    document.getElementById('priceInput').value = customerPricesCache[cust][opt.value];
  } else {
    document.getElementById('priceInput').value = basePrice;
  }
  document.getElementById('itemName').value = opt.dataset.name || '';
}

// Initialize item Tom Select in DOMContentLoaded to ensure it's available
document.addEventListener('DOMContentLoaded', function(){
  // re-use existing DOMContentLoaded registration for customerTom; ensure itemSelect exists
  if(document.getElementById('itemSelect')) {
    new TomSelect('#itemSelect', { create: false, sortField: { field: 'text', direction: 'asc' } });
    document.getElementById('itemSelect').addEventListener('change', function(){ onItemSelectChange(); });
  }
});

// --- Fungsi Hitung Total ---
function recalcTotal() {
  let total = 0;
  document.querySelectorAll('#invoiceItemsTable tbody tr').forEach(r=>{
    total += parseInt(r.dataset.subtotal || 0);
  });
  document.getElementById('totalText').innerText = new Intl.NumberFormat('id-ID').format(total);
}

// --- Tambah Item ---
document.getElementById('addItemBtn').addEventListener('click', function(){
  const itemSelect = document.getElementById('itemSelect');
  const qtyInput = document.getElementById('qty');
  const priceInput = document.getElementById('priceInput');
  const tbody = document.querySelector('#invoiceItemsTable tbody');
  const itemName = document.getElementById('itemName');

  console.log('itemName:', itemName.value);

  const itemId = itemSelect.value;
  if(!itemId){ alert('Pilih item'); return; }

  const itemText = itemSelect.selectedOptions[0].text;
  const qty = parseInt(qtyInput.value) || 1;
  const price = parseInt(priceInput.value) || 0;
  const subtotal = qty * price;

  const rowCount = tbody.querySelectorAll('tr').length;
  const tr = document.createElement('tr');
  tr.dataset.subtotal = subtotal;
  tr.innerHTML = `
    <td>
      <input type="text" name="items[${rowCount}][item_name]" value="${itemName.value}"min="1" class="form-control form-control-sm" >
      <input type="hidden" name="items[${rowCount}][item_id]" value="${itemId}">
    </td>
    <td><input type="number" name="items[${rowCount}][qty]" value="${qty}" min="1" class="form-control form-control-sm" style="width:80px"></td>
    <td><input type="number" name="items[${rowCount}][price]" value="${price}" class="form-control form-control-sm" style="width:120px"></td>
    <td class="subtotalCell">${new Intl.NumberFormat('id-ID').format(subtotal)}</td>
    <td class="text-center">
      <button type="button" class="btn btn-sm btn-outline-danger removeBtn">
        <i class="fas fa-trash"></i>
      </button>
    </td>
  `;
  tbody.appendChild(tr);

  // --- Event perubahan qty/price ---
  tr.querySelectorAll('input').forEach(inp => {
    inp.addEventListener('input', function(){
      const q = parseInt(tr.querySelector(`input[name="items[${rowCount}][qty]"]`).value) || 0;
      const p = parseInt(tr.querySelector(`input[name="items[${rowCount}][price]"]`).value) || 0;
      const s = q * p;
      tr.dataset.subtotal = s;
      tr.querySelector('.subtotalCell').innerText = new Intl.NumberFormat('id-ID').format(s);
      recalcTotal();
    });
  });

  // --- Tombol hapus ---
  tr.querySelector('.removeBtn').addEventListener('click', function(){
    tr.remove();
    recalcTotal();
  });

  recalcTotal();

  // Reset & tutup modal
  if (itemSelect.tomselect) {
    itemSelect.tomselect.clear(); // ✅ reset Tom Select selection
  }
  qtyInput.value = 1;
  priceInput.value = '';
  bootstrap.Modal.getInstance(document.getElementById('addItemModal')).hide();

});

document.getElementById('addCustomerBtn').addEventListener('click', function (e) {
  e.preventDefault();
  fetch('customer_form.php')
    .then(res => res.text())
    .then(html => {
      document.getElementById('customerModalContent').innerHTML = html;
      const modal = new bootstrap.Modal(document.getElementById('customerModal'));
      modal.show();

      // Tangani submit form dalam modal
      const form = document.getElementById('customerForm');
      form.addEventListener('submit', async function (ev) {
        ev.preventDefault();
        const formData = new FormData(form);
        const res = await fetch('customer_form.php?from_modal=1', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();

        if (data.success) {
          // Tambahkan customer baru to Tom Select and select it
              const select = document.getElementById('customerSelect');
              const opt = document.createElement('option');
              opt.value = data.id;
              opt.textContent = data.name;
              select.appendChild(opt);
              // refresh tom select choices and set value
              if(customerTom) {
                customerTom.addOption({value: data.id, text: data.name - data.phone});
                customerTom.setValue(data.id);
              } else {
                select.value = data.id;
              }
              modal.hide();
        } else {
          alert('Gagal menambah customer.');
        }
      });
    });
});



</script>

<?php
include 'footer.php';
?>