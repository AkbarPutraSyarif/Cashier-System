<?php
// Include Midtrans
require_once dirname(__FILE__) . '/midtrans-php-master/Midtrans.php';

// Konfigurasi Midtrans
\Midtrans\Config::$serverKey = 'SB-Mid-server-34W_Tg1pRmK6iuLwM4Q8s3Fs';
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// Koneksi ke database
require 'db.php';

// Ambil data dari client (fetch POST)
$data = json_decode(file_get_contents("php://input"), true);

// Validasi input
if (!isset($data['cart']) || !is_array($data['cart'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Cart data missing or invalid']);
    exit;
}

$items = [];
$totalAmount = 0;

// Buat detail item dan total
foreach ($data['cart'] as $item) {
    $items[] = [
        'id' => uniqid(),
        'price' => $item['price'],
        'quantity' => $item['quantity'],
        'name' => $item['title']
    ];
    $totalAmount += $item['price'] * $item['quantity'];
}

// Buat order_id unik
$order_id = 'ORDER-' . time() . '-' . rand(1000, 9999);

// Simpan cart ke database untuk referensi callback
$cart_json = json_encode($data['cart']);
$stmt = $conn->prepare("INSERT INTO transactions (order_id, cart_data) VALUES (?, ?)");
$stmt->bind_param("ss", $order_id, $cart_json);
$stmt->execute();

// Parameter Snap
$transaction = [
    'transaction_details' => [
        'order_id' => $order_id,
        'gross_amount' => $totalAmount,
    ],
    'item_details' => $items,
    'customer_details' => [
        'first_name' => 'Customer',
        'email' => 'customer@example.com'
    ]
];

// Dapatkan Snap Token
$snapToken = \Midtrans\Snap::getSnapToken($transaction);

// Kirim Snap token ke client
echo json_encode(['token' => $snapToken]);
