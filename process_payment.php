<?php
// process_payment.php

header('Content-Type: application/json');

// Ini cuma buat testing, JANGAN PAKE API KEY LANGSUNG DI SINI DI PRODUCTION!
// Pake environment variable atau cara aman lainnya.
define('ATLANTIC_API_KEY', 'raKBVBoZhQzNYA1dvOVzdTc1ED5iXe2fiQcv42GcoJ3ciRD7Ll8NJbRpc2QjOFiQxF2kjSCVccEWcBQDtcNevtZMj9L5K1PS3STS'); [cite_start]// Ganti dengan API Key Anda [cite: 80, 81]
define('ATLANTIC_API_URL', 'https://atlantich2h.com'); [cite_start]// [cite: 5]

$productName = $_POST['product_name'] ?? 'Unknown Product';
$price = (int)($_POST['price'] ?? 0);
$link = $_POST['link'] ?? '';
$reffId = 'WEBSTORE-' . uniqid(); [cite_start]// ID unik dari sistem kita [cite: 80]

// Data yang akan dikirim ke API Atlantic Pedia untuk membuat deposit QRIS
$postData = http_build_query([
    [cite_start]'api_key' => ATLANTIC_API_KEY, [cite: 80]
    [cite_start]'reff_id' => $reffId, [cite: 80]
    [cite_start]'nominal' => $price, [cite: 80]
    [cite_start]'type' => 'ewallet', [cite: 81]
    [cite_start]'metode' => 'qris' [cite: 81]
]);

$ch = curl_init();
[cite_start]curl_setopt($ch, CURLOPT_URL, ATLANTIC_API_URL . '/deposit/create'); [cite: 78]
curl_setopt($ch, CURLOPT_POST, 1);
[cite_start]curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); [cite: 78, 79, 80, 81]
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
[cite_start]curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']); [cite: 78]

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['status' => 'error', 'message' => 'Curl error: ' . $error]);
    exit();
}

$apiResponse = json_decode($response, true);

[cite_start]if (isset($apiResponse['status']) && $apiResponse['status'] === true) { [cite: 83]
    $atlanticId = $apiResponse['data']['id']; [cite_start]// ID transaksi dari Atlantic [cite: 83, 84]
    $qrImage = $apiResponse['data']['qr_image']; [cite_start]// URL gambar QRIS [cite: 83, 86]
    
    // --- Bagian ini untuk menyimpan data ke file (HANYA UNTUK UJI COBA!) ---
    // Di produksi, ini harusnya ke database
    $transactionsFile = 'transactions.json';
    $transactions = [];
    if (file_exists($transactionsFile)) {
        $transactions = json_decode(file_get_contents($transactionsFile), true);
    }
    
    $transactions[$atlanticId] = [
        'reff_id' => $reffId,
        'product_name' => $productName,
        'price' => $price,
        'atlantic_id' => $atlanticId,
        'qr_image_url' => $qrImage,
        'expected_link' => $link,
        [cite_start]'payment_status' => 'pending', // Status awal [cite: 83, 87]
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    file_put_contents($transactionsFile, json_encode($transactions, JSON_PRETTY_PRINT));
    // --- Akhir bagian penyimpanan file ---

    echo json_encode([
        'status' => 'success',
        'message' => 'QRIS berhasil dibuat',
        'qr_image_url' => $qrImage,
        'atlantic_transaction_id' => $atlanticId,
        'reff_id' => $reffId // ini reff_id kita
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        [cite_start]'message' => $apiResponse['message'] ?? 'Gagal membuat transaksi QRIS', [cite: 83]
        'api_response' => $apiResponse
    ]);
}
?>
