<?php
require_once __DIR__ . '/vendor/autoload.php'; // mPDF autoload

// --- ১. ডাটাবেজ কানেকশন কনফিগারেশন ---
$host = 'localhost';
$db   = 'shop_invoice'; // আপনার ডাটাবেজের নাম দিন
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ডাটাবেজ কানেকশন ব্যর্থ হয়েছে: " . $e->getMessage());
}

// sanitize helper
function clean($v) {
  return trim(htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
}

// ইংরেজি থেকে বাংলা সংখ্যা রূপান্তর
function bn_number($number) {
    $eng = ['0','1','2','3','4','5','6','7','8','9'];
    $ban = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
    return str_replace($eng, $ban, $number);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = isset($_POST['customer_name']) ? clean($_POST['customer_name']) : 'নাম নেই';
    $customer_add = isset($_POST['customer_add']) ? clean($_POST['customer_add']) : 'নাম নেই';
    $product_names = $_POST['product_name'] ?? [];
    $product_qtys  = $_POST['product_qty'] ?? [];
    $product_prices = $_POST['product_price'] ?? [];

    $rows = [];
    $grand_total = 0; // এখানে টোটাল জিরো সেট করা হয়েছে

    $len = max(count($product_names), count($product_qtys), count($product_prices));
    for ($i = 0; $i < $len; $i++) {
        $name  = isset($product_names[$i]) ? clean($product_names[$i]) : '';
        // কমা (,) থাকলে তা সরিয়ে ফ্লোটে কনভার্ট করা
        $qty   = isset($product_qtys[$i]) ? floatval(str_replace(',', '', $product_qtys[$i])) : 0;
        $price = isset($product_prices[$i]) ? floatval(str_replace(',', '', $product_prices[$i])) : 0;

        if ($name === '' && $qty == 0 && $price == 0) continue;

        $line_total = $qty * $price;
        $grand_total += $line_total; // এখানে গ্র্যান্ড টোটাল যোগ হচ্ছে

        $rows[] = [
            'name'  => $name,
            'qty'   => $qty,
            'price' => $price,
            'total' => $line_total
        ];
    }

    // ডাটাবেজে ইনসার্ট
    try {
        $pdo->beginTransaction();

        // ১. গ্র্যান্ড টোটাল ($grand_total) সহ ইনভয়েস সেভ
        $stmt = $pdo->prepare("INSERT INTO invoices (customer_name, customer_add, total_amount) VALUES (?, ?, ?)");
        $stmt->execute([$customer_name, $customer_add, $grand_total]);
        $invoice_id_db = $pdo->lastInsertId();

        // ২. প্রতিটি আইটেম সেভ
        $itemStmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, product_name, quantity, price, line_total) VALUES (?, ?, ?, ?, ?)");
        foreach ($rows as $r) {
            $itemStmt->execute([
                $invoice_id_db,
                $r['name'],
                $r['qty'],
                $r['price'],
                $r['total']
            ]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("ভুল হয়েছে: " . $e->getMessage());
    }
}


// calculate grand total
$grand = 0;
foreach ($rows as $r) $grand += $r['total'];

// mPDF config with Bengali font
$defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];
$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

// assume fonts/ contains SolaimanLipi.ttf
$mpdf = new \Mpdf\Mpdf([
  'mode' => 'utf-8',
  'format' => 'A4',
  'default_font' => 'solaiman',
  'fontDir' => array_merge($fontDirs, [__DIR__ . '/fonts']),
  'fontdata' => $fontData + [
    'solaiman' => [
      'R' => 'SolaimanLipi_22-02-2012.ttf'
    ]
  ],
  'mode' => 'utf-8',
  'format' => 'A4',
  'autoScriptToLang' => true,
  'autoLangToFont' => true
]);

// build HTML for invoice
$issueDate = bn_number(date('d/m/Y'));
$invoiceNo = 'মেমো-' . bn_number($invoice_id_db);

// simple styling for PDF
$css = "
body { font-family: solaiman; font-size: 12pt; }
.header { display:flex; justify-content:space-between; margin-bottom:12px; }
.table { width:100%; border-collapse: collapse; }
.table th, .table td { border: 1px solid #ddd; padding:6px; }
.right { text-align:right; }
.big { font-weight:800; font-size:14pt; }
.footer-table {
    margin-top: 100px;
    padding-top: 10px;
    width: 100%;
    border-collapse: collapse; /* Ensure no border spacing */
}
.footer-table td {
    border: none;
    padding: 0;
}

.footer-note { 
    font-size:11pt; 
    text-align: left;
    width: 50%;
}

/* Updated style to push the text all the way to the right */
.proprietor-signature { 
    font-size: 11pt; 
    font-weight: bold;
    text-align: right; 
    width: 50%;
    /* Added more padding to push content away from the right edge */
    padding-right: 0px; 
}
";

// rows HTML
$rowsHtml = '';
$idx = 1;
foreach ($rows as $r) {
  $rowsHtml .= '<tr>';
  $rowsHtml .= '<td style="width:6%">' . bn_number($idx++) . '</td>';
  $rowsHtml .= '<td>' . ($r['name'] ?: '-') . '</td>';
  $rowsHtml .= '<td class="right">' . bn_number(number_format($r['qty'], 2, '.', ',')) . '</td>';
  $rowsHtml .= '<td class="right">' . bn_number(number_format($r['price'], 2, '.', ',')) . '</td>';
  $rowsHtml .= '<td class="right">' . bn_number(number_format($r['total'], 2, '.', ',')) . '</td>';
  $rowsHtml .= '</tr>';
}

$html = "
<html>
<head><style>{$css}</style></head>
<body>
  <div class='header'>
    <div style='text-align: center;'>
      <div class='big' style='font-size: 36px;'><strong>ঈশান ই-সার্ভিস সেন্টার</strong></div>
      <div style='font-size: 20px;'>আয়নাপুর, ঝিনাইগাতী, শেরপুর।</div>
      <div>মোবাইল নং- ০১৯৯০-৮২২০৫০</div>
      <div>এখানে সকল প্রকার অনলাইনের কাজ, জন্ম নিবন্ধন, আইডি কার্ড, পাসপোর্ট, ফটোকপি, টাইপিং ও ছবির কাজ করা হয়।</div>
    </div>
    <hr>
    
    <table style='width:100%; margin-bottom:8px; border:0;'>
      <tr>
        <td style='text-align:left; border:0;'>
          ইনভয়স নং: " . bn_number($invoiceNo) . "
        </td>
        <td style='text-align:right; border:0;'>
          তারিখ: " . bn_number($issueDate) . "
        </td>
      </tr>
    </table>
  </div>

  <div style='margin-bottom:10px'>
    <strong>ক্রেতার নাম:</strong> {$customer_name},
    <strong>ক্রেতার ঠিকানা:</strong> {$customer_add}
  </div>

  <table class='table'>
    <thead>
      <tr>
        <th style='width:6%; text-align:left;'>ক্রঃ</th>
        <th>পণ্যের বিবরণ</th>
        <th style='width:12%; text-align:right;'>পরিমাণ</th>
        <th style='width:18%; text-align:right;'>একক দাম</th>
        <th style='width:18%; text-align:right;'>মোট</th>
      </tr>
    </thead>
    <tbody>
      {$rowsHtml}
    </tbody>
    <tfoot>
      <tr>
        <td colspan='4' class='right'><strong>সর্বমোট</strong></td>
        <td class='right'><strong>" . bn_number(number_format($grand, 2, '.', ',')) . "</strong></td>
      </tr>
    </tfoot>
  </table>

  <table class='footer-table'>
    <tr>
      <td class='footer-note'>
          ধন্যবাদ! আবার আসবেন।
      </td>
      <td class='proprietor-signature'>
            প্রো: মোঃ বোরহান উদ্দিন (লেবু)  <br>
          জহুরুল মার্কেট, ঝিনাইগাতী, শেরপুর।
      </td>
    </tr>
  </table>
</body>
</html>
";


// output PDF inline for preview in new tab
$mpdf->WriteHTML($html);
$mpdf->Output("{$invoiceNo}.pdf", \Mpdf\Output\Destination::INLINE);
exit;
