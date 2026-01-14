<?php include 'lang.php'; ?>
<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>নতুন ইনভয়েস তৈরি করুন</title>
<style>
:root{
  --bg:#f6f9fc; --card:#fff; --primary:#0b6f4b; --accent:#1565c0; --muted:#6b7280; --radius:10px;
}
*{box-sizing:border-box}
body{
  margin:0;font-family:SolaimanLipi, sans-serif;background:var(--bg);color:#111;padding:20px;
}
.container{max-width:980px;margin:0 auto}
.header{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px}
.header h2{margin:0;font-size:20px}
.form-card{background:var(--card);padding:18px;border-radius:var(--radius);box-shadow:0 8px 20px rgba(16,24,40,.06)}
.row{display:flex;gap:10px;align-items:end;margin-bottom:8px}
.col{flex:1}
.col.small{flex:0 0 120px}
label{display:block;font-size:13px;color:var(--muted);margin-bottom:6px}
input[type="text"], input[type="number"]{
  width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:8px;font-size:15px;background:#fff;
}
.controls{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap}
.btn{border:0;padding:10px 14px;border-radius:10px;color:#fff;cursor:pointer;font-weight:700}
.btn.add{background:var(--accent)}
.btn.create{background:var(--primary)}
.btn.simple{background:#6b7280}
.remove-btn{background:#ef4444;border-radius:50%;width:34px;height:34px;border:0;color:#fff;cursor:pointer}
.total-line{display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:12px;border-top:1px dashed #eef2f6}
.total-line .label{color:var(--muted)}
.total-line .amount{font-size:20px;font-weight:800;color:var(--primary)}
.product-list{margin-top:12px}
.row .per-row-total{min-width:120px;text-align:right;font-weight:700;color:#0f172a}
@media (max-width:720px){
  .row{flex-direction:column;align-items:stretch}
  .col.small{flex:1}
  .remove-btn{position:relative;right:auto;top:auto;margin-left:auto}
}
</style>
</head>
<body>
<div class="container">
  <div style="text-align: right; margin-bottom: 20px;">
    <form method="GET" action="">
        <select name="change_lang" onchange="this.form.submit()" style="padding: 5px; border-radius: 5px;">
            <option value="bn" <?php echo $lang == 'bn' ? 'selected' : ''; ?>>বাংলা (Bangla)</option>
            <option value="en" <?php echo $lang == 'en' ? 'selected' : ''; ?>>English</option>
        </select>
    </form>
  </div>

  <div class="header">
    <h2>➕ <?php echo $t['title']; ?></h2>    
    <div style="color:var(--muted);font-weight: bold;"><a href="history.php"><?php echo $t['show_history']; ?></a> </div>
  </div>

  <form id="invoiceForm" class="form-card" action="preview.php" method="post" target="_blank" autocomplete="" novalidate>
    <div class="row">
      <div class="col" style="margin-bottom:8px">
        <label><?php echo $t['customer_name']; ?></label>
        <input name="customer_name" type="text" autocomplete="" placeholder="<?php echo $t['customer_name']; ?>" required />      
      </div>    
      <div class="col" style="margin-bottom:8px">
        <label><?php echo $t['customer_add']; ?></label>
        <input name="customer_add" type="text" autocomplete="" placeholder="<?php echo $t['customer_add']; ?>" required />
      </div>
    </div>
    <div class="product-list" id="productList" aria-live="polite">
      <!-- initial row rendered from template by JS on load -->
    </div>

    <div class="controls">
      <button type="button" class="btn add" id="addRowBtn">➕ <?php echo $t['add_more_product']; ?></button>
      <button type="button" class="btn simple" onclick="document.getElementById('invoiceForm').reset(); resetList()"><?php echo $t['form_reset']; ?></button>
      <button type="submit" class="btn create"><?php echo $t['preview']; ?></button>
    </div>

    <div class="total-line" aria-hidden="false">
      <div class="label"><?php echo $t['grand_total']; ?></div>
      <div class="amount" id="grandTotal"><?php echo $t['taka']; ?></div>
    </div>
  </form>
</div>

<!-- Row template -->
<template id="productRowTemplate">
  <div class="row product-row">
    <div class="col">
      <label><?php echo $t['product_name']; ?></label>
      <input type="text" name="product_name[]" autocomplete="" placeholder="পণ্যের নাম" required />
    </div>
    <div class="col small">
      <label><?php echo $t['qty']; ?></label>
      <input type="number" name="product_qty[]" step="any" min="0" value="" required />
    </div>
    <div class="col small">
      <label><?php echo $t['price']; ?></label>
      <input type="number" name="product_price[]" step="any" min="0" value="" required />
    </div>
    <div class="col small" style="display:flex;flex-direction:column;align-items:flex-end;justify-content:center">
      <label><?php echo $t['total']; ?></label>
      <div class="per-row-total">0.00</div>
    </div>
    <div style="display:flex;align-items:flex-end;padding-left:6px">
      <button type="button" class="remove-btn" title="<?php echo $t['remove']; ?>">✕</button>
    </div>
  </div>
</template>

<script>
/* Core behaviour:
   - use template to create rows with empty values
   - event delegation for inputs and remove buttons
   - per-row total and grand total calculation
   - before submit: remove any fully-empty rows and recalc; rely on browser to send arrays in order
*/

const productList = document.getElementById('productList');
const rowTpl = document.getElementById('productRowTemplate');
const addRowBtn = document.getElementById('addRowBtn');
const grandTotalEl = document.getElementById('grandTotal');
const form = document.getElementById('invoiceForm');

function createRow() {
  const node = rowTpl.content.cloneNode(true);
  // ensure inputs empty and attributes set
  const row = node.querySelector('.product-row');
  const inputs = row.querySelectorAll('input');
  inputs.forEach(inp => {
    inp.value = '';
    inp.autocomplete = '';
  });
  return node;
}

function addRow() {
  productList.appendChild(createRow());
  // focus first input of the new row
  const lastRow = productList.querySelector('.product-row:last-child');
  if (lastRow) lastRow.querySelector('input[name="product_name[]"]').focus();
  calculateTotals();
}

function resetList(){
  productList.innerHTML = '';
  addRow();
  calculateTotals();
}

function parseNumber(val) {
  const n = String(val).trim();
  if (n === '') return 0;
  const parsed = Number(n);
  return Number.isFinite(parsed) ? parsed : 0;
}

function calculateTotals() {
  const rows = productList.querySelectorAll('.product-row');
  let grand = 0;
  rows.forEach(row => {
    const qtyEl = row.querySelector('input[name="product_qty[]"]');
    const priceEl = row.querySelector('input[name="product_price[]"]');
    const totalEl = row.querySelector('.per-row-total');
    const qty = parseNumber(qtyEl.value);
    const price = parseNumber(priceEl.value);
    const rowTotal = qty * price;
    totalEl.textContent = rowTotal.toFixed(2);
    grand += rowTotal;
  });
  grandTotalEl.textContent = grand.toFixed(2) + ' টাকা';
  return grand;
}

// Event delegation: listen for input changes and remove clicks
productList.addEventListener('input', function(e){
  const target = e.target;
  if (target.matches('input[name="product_qty[]"], input[name="product_price[]"]')) {
    calculateTotals();
  }
});

productList.addEventListener('click', function(e){
  const btn = e.target.closest('.remove-btn');
  if (!btn) return;
  const row = btn.closest('.product-row');
  if (row) {
    row.remove();
    calculateTotals();
  }
});

// Ensure at least one row on load
if (productList.children.length === 0) addRow();

// Add new row on button click
addRowBtn.addEventListener('click', addRow);

// Before submit: remove fully empty rows (name empty and qty/price zero), recalc, and ensure required attr not preventing
form.addEventListener('submit', function(e){
  // remove empty rows
  const rows = Array.from(productList.querySelectorAll('.product-row'));
  rows.forEach(row => {
    const name = row.querySelector('input[name="product_name[]"]').value.trim();
    const qty = parseNumber(row.querySelector('input[name="product_qty[]"]').value);
    const price = parseNumber(row.querySelector('input[name="product_price[]"]').value);
    if (name === '' && qty === 0 && price === 0) {
      row.remove();
    }
  });
  // recalc and if no rows remain, prevent submit
  const remaining = productList.querySelectorAll('.product-row');
  if (remaining.length === 0) {
    e.preventDefault();
    alert('অনুগ্রহ করে অন্তত একটি পণ্যের তথ্য যোগ করুন।');
    addRow();
    return false;
  }
  calculateTotals();
  // browser will post arrays in DOM order, no extra serialization needed
});
</script>
</body>
</html>
