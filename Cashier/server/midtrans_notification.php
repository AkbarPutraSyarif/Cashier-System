<?php
require_once dirname(__FILE__) . '/midtrans-php-master/Midtrans.php';
require_once 'db.php'; // koneksi database

\Midtrans\Config::$serverKey = 'SB-Mid-server-34W_Tg1pRmK6iuLwM4Q8s3Fs';
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

try {
    $notif = new \Midtrans\Notification();

    $transaction = $notif->transaction_status;
    $order_id = $notif->order_id;

    //update status pembayaran
    $updateStatus = $conn->prepare("UPDATE transactions SET status = ? WHERE order_id = ?");
    $updateStatus->bind_param("ss", $transaction, $order_id);
    $updateStatus->execute();
    $updateStatus->close();

    if ($transaction === 'capture' || $transaction === 'settlement') {
        // Ambil cart_data dari tabel transactions berdasarkan order_id
        $stmt = $conn->prepare("SELECT cart_data FROM transactions WHERE order_id = ?");
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        $stmt->bind_result($cart_data);
        $stmt->fetch();
        $stmt->close();

        if ($cart_data) {
            // Decode JSON cart_data
            $cart = json_decode($cart_data, true);

            foreach ($cart as $item) {
                $title = $item['title'];
                $qty = $item['quantity'];

                // Update stok produk
                $update = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE title = ?");
                $update->bind_param("is", $qty, $title);
                $update->execute();
                $update->close();
            }
        }
    }

    http_response_code(200);
    echo json_encode(["message" => "Notification processed successfully."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
