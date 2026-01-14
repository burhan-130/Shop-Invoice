<?php
require_once __DIR__ . '/vendor/autoload.php';

// ডাটাবেজ কানেকশন
$host = 'localhost';
$db   = 'shop_invoice';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// আইডি চেক করা
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$id) {
    die("ভুল আইডি প্রদান করা হয়েছে।");
}

// ইংরেজি থেকে বাংলা সংখ্যা রূপান্তর
function bn_number($number) {
    $eng = ['0','1','2','3','4','5','6','7','8','9'];
    $ban = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
    return str_replace($eng, $ban, $number);
}

// --- ২. ডাটাবেজ থেকে ইনভয়েস তথ্য সংগ্রহ ---

// মেইন ইনভয়েস
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
$stmt->execute([$id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    die("এই ইনভয়েসটি ডাটাবেজে পাওয়া যায়নি।");
}

// ইনভয়েসের আইটেমসমূহ
$itemStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$itemStmt->execute([$id]);
$rows = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

// --- ৩. mPDF প্রিভিউ জেনারেট করা ---

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'solaiman',
    'fontDir' => array_merge((new Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'], [__DIR__ . '/fonts']),
    'fontdata' => (new Mpdf\Config\FontVariables())->getDefaults()['fontdata'] + [
        'solaiman' => [ 'R' => 'SolaimanLipi_22-02-2012.ttf' ]
    ],
    'autoScriptToLang' => true,
    'autoLangToFont' => true
]);

$issueDate = bn_number(date('d/m/Y', strtotime($invoice['created_at'])));
$invoiceNo = 'মেমো-' . bn_number($invoice['id']);
$customer_name = htmlspecialchars($invoice['customer_name']);
$customer_add = htmlspecialchars($invoice['customer_add']);
$grand = $invoice['total_amount'];

// CSS স্টাইল
$css = "
body { font-family: solaiman; font-size: 12pt; }
.header { text-align: center; margin-bottom:12px; }
.table { width:100%; border-collapse: collapse; }
.table th, .table td { border: 1px solid #ddd; padding:6px; }
.right { text-align:right; }
.big { font-weight:800; font-size:14pt; }
.footer-table { margin-top: 100px; width: 100%; border-collapse: collapse; }
.footer-table td { border: none; padding: 0; }
.proprietor-signature { text-align: right; font-weight: bold; }
";

// আইটেম টেবিলের রো তৈরি
$rowsHtml = '';
$idx = 1;
foreach ($rows as $r) {
    $rowsHtml .= '<tr>';
    $rowsHtml .= '<td style="width:6%">' . bn_number($idx++) . '</td>';
    $rowsHtml .= '<td>' . htmlspecialchars($r['product_name']) . '</td>';
    $rowsHtml .= '<td class="right">' . bn_number(number_format($r['quantity'], 2, '.', ',')) . '</td>';
    $rowsHtml .= '<td class="right">' . bn_number(number_format($r['price'], 2, '.', ',')) . '</td>';
    $rowsHtml .= '<td class="right">' . bn_number(number_format($r['line_total'], 2, '.', ',')) . '</td>';
    $rowsHtml .= '</tr>';
}

// মূল HTML
$html = "
<html>
<head><style>{$css}</style></head>
<body>
  <div class='header'>
      <div class='big' style='font-size: 36px;'><strong>ঈশান ই-সার্ভিস সেন্টার</strong></div>
      <div style='font-size: 20px;'>আয়নাপুর, ঝিনাইগাতী, শেরপুর।</div>
      <div>মোবাইল নং- ০১৯৯০-৮২২০৫০</div>
      <div>এখানে সকল প্রকার অনলাইনের কাজ করা হয়।</div>
      <hr>
      <table style='width:100%; margin-bottom:8px; border:0;'>
        <tr>
          <td style='text-align:left; border:0;'>ইনভয়স নং: {$invoiceNo}</td>
          <td style='text-align:right; border:0;'>তারিখ: {$issueDate}</td>
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
      <td style='text-align:left;'>ধন্যবাদ! আবার আসবেন।</td>
      <td class='proprietor-signature'>
          প্রো: মোঃ বোরহান উদ্দিন (লেবু) <br>
          জহুরুল মার্কেট, ঝিনাইগাতী, শেরপুর।
      </td>
    </tr>
  </table>
</body>
</html>
";

$mpdf->WriteHTML($html);
$mpdf->Output("Invoice_{$invoice['id']}.pdf", \Mpdf\Output\Destination::INLINE);
exit;