<?php
session_start();
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'bn'; // ডিফল্ট ভাষা বাংলা
}

// ভাষা পরিবর্তনের লজিক
if (isset($_GET['change_lang'])) {
    $_SESSION['lang'] = $_GET['change_lang'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$texts = [
    'bn' => [
        'title' => 'নতুন ইনভয়েস তৈরি করুন',
        'show_history' => 'হিসটরি দেখুন',
        'customer_name' => 'ক্রেতার নাম',
        'customer_add' => 'ক্রেতার ঠিকানা',
        'product_name' => 'পণ্যের বিবরণ',
        'add_more_product' => 'আরও পণ্য যোগ করুন',
        'qty' => 'পরিমাণ',
        'price' => 'একক দাম',
        'total' => 'মোট',
        'grand_total' => 'সর্বমোট',
        'preview' => '🔍 প্রিভিউ দেখুন',
        'history' => '📜 ইনভয়েস হিস্টরি',
        'form_reset' => 'ফর্ম রিসেট',
        'taka' => '০.০০ টাকা',
        'remove' => 'মুছে ফেলুন',
    ],
    'en' => [
        'title' => 'Create New Invoice',
        'show_history' => 'Show History',
        'customer_name' => 'Customer Name',
        'customer_add' => 'Customer Address',
        'product_name' => 'Product Name',
        'add_more_product' => 'Add More Product',
        'qty' => 'Quantity',
        'price' => 'Unit Price',
        'total' => 'Total',
        'grand_total' => 'Grand Total',
        'preview' => '🔍 Preview Invoice',
        'history' => '📜 Invoice History',
        'form_reset' => 'Form Reset',
        'taka' => '0.00 Taka',
        'remove' => 'Remove',
    ]
];

$lang = $_SESSION['lang'];
$t = $texts[$lang]; // বর্তমান ভাষার টেক্সটগুলো এই ভেরিয়েবলে থাকবে
?>