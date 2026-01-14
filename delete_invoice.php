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
    die("কানেকশন ব্যর্থ: " . $e->getMessage());
}

// আইডি চেক করা
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($id) {
    try {
        // ইনভয়েস ডিলিট করা (Foreign Key থাকলে invoice_items অটো ডিলিট হবে যদি ON DELETE CASCADE দেওয়া থাকে)
        $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt->execute([$id]);

        // ডিলিট সফল হলে হিস্টরি পেজে ব্যাক করবে
        header("Location: history.php?msg=deleted");
        exit;
    } catch (Exception $e) {
        die("ডিলিট করতে সমস্যা হয়েছে: " . $e->getMessage());
    }
} else {
    header("Location: history.php");
    exit;
}
?>