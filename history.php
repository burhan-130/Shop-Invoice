<?php
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

// ইংরেজি থেকে বাংলা সংখ্যা রূপান্তর ফাংশন
function bn_number($number) {
    $eng = ['0','1','2','3','4','5','6','7','8','9'];
    $ban = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
    return str_replace($eng, $ban, $number);
}

// সার্চ লজিক
$search_id = isset($_GET['search_id']) ? trim($_GET['search_id']) : '';

if ($search_id !== '') {
    // আইডি দিয়ে সার্চ করলে
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? ORDER BY created_at DESC");
    $stmt->execute([$search_id]);
} else {
    // সাধারণ অবস্থায় সব লিস্ট
    $stmt = $pdo->query("SELECT * FROM invoices ORDER BY created_at DESC");
}
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ইনভয়েস হিস্টরি</title>
    <style>
        body { font-family: 'SolaimanLipi', sans-serif; background-color: #f4f7f6; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { color: #0b6f4b; border-bottom: 2px solid #0b6f4b; padding-bottom: 10px; }

        /* সার্চ বক্স স্টাইল */
        .search-box { margin-bottom: 20px; background: #eef2f6; padding: 15px; border-radius: 8px; display: flex; gap: 10px; }
        .search-box input { padding: 8px; border: 1px solid #ccc; border-radius: 5px; flex: 1; }
        .search-box button { background: #0b6f4b; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; }
        .btn-reset { background: #6b7280; color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; font-size: 14px; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #0b6f4b; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .btn-view { background: #1565c0; color: white; padding: 5px 12px; text-decoration: none; border-radius: 5px; font-size: 14px; }
        .amount { font-weight: bold; color: #d32f2f; }
        .back-btn { display: inline-block; margin-bottom: 15px; color: #0b6f4b; text-decoration: none; font-weight: bold; }

        /* ডিলিট বাটনের জন্য সিএসএস */
        .btn-delete {
            background: #ef4444;
            color: white;
            padding: 5px 12px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            margin-left: 5px;
        }
        .btn-delete:hover {
            background: #dc2626;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" class="back-btn">← নতুন ইনভয়েস তৈরি করুন</a>
    <h2>📜 ইনভয়েস বিক্রয় হিস্টরি</h2>

    <form method="GET" action="history.php" class="search-box">
        <input type="number" name="search_id" placeholder="আইডি নম্বর দিয়ে খুঁজুন (উদা: 5)" value="<?php echo htmlspecialchars($search_id); ?>">
        <button type="submit">🔍 খুঁজুন</button>
        <?php if($search_id): ?>
            <a href="history.php" class="btn-reset">রিসেট</a>
        <?php endif; ?>
    </form>

    <table>
        <thead>
            <tr>
                <th>আইডি</th>
                <th>তারিখ ও সময়</th>
                <th>ক্রেতার নাম</th>
                <th>মোট টাকা</th>
                <th>অ্যাকশন</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($invoices) > 0): ?>
                <?php foreach ($invoices as $invoice): ?>
                <tr>
                    <td><?php echo bn_number($invoice['id']); ?></td>
                    <td><?php echo bn_number(date('d/m/Y h:i A', strtotime($invoice['created_at']))); ?></td>
                    <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                    <td class="amount"><?php echo bn_number(number_format($invoice['total_amount'], 2)); ?> ৳</td>
                    <td>
                        <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" target="_blank" class="btn-view">📄 দেখুন</a>
                        <a href="delete_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn-delete" onclick="return confirm('আপনি কি নিশ্চিত যে এই ইনভয়েসটি ডিলিট করতে চান?')">🗑️ ডিলিট
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align:center;">কোনো ইনভয়েস পাওয়া যায়নি।</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>