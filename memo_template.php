<?php
function generateMemoHTML($shopInfo, $customerInfo, $products) {
    $total = 0;
    foreach ($products as &$item) {
        $item['qty'] = floatval($item['qty']);
        $item['unit_price'] = floatval($item['unit_price']);
        $item['total'] = $item['qty'] * $item['unit_price'];       
        $total += $item['total'];
    }

    $html = '
    <style>
        body { font-family: solaimanlipi; font-size: 18px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { border: 1px solid #000; padding: 8px; text-align: center; }
        .total { text-align: right; font-size: 16px; font-weight: bold; margin-top: 15px; }
    </style>

    <table class="table">
        <thead>
            <tr>
                <th>ক্রমিক</th>
                <th>পণ্যের নাম</th>
                <th>পরিমাণ</th>
                <th>একক দাম</th>
                <th>মোট</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($products as $index => $item) {
        $html .= '
            <tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($item['name']) . '</td>
                <td>' . number_format($item['qty'], 2) . '</td>
                <td>' . number_format($item['unit_price'], 2) . '</td>
                <td>' . number_format($item['total'], 2) . '</td>
            </tr>';
    }

    $html .= '
        </tbody>
    </table>

    <div class="total">সর্বমোট: ' . number_format($total, 2) . ' টাকা</div>';

    return $html;
}

?>
