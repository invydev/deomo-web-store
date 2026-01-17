<?php
// check_payment.php

header('Content-Type: application/json');

// Ini cuma buat testing, JANGAN PAKE API KEY LANGSUNG DI SINI DI PRODUCTION!
define('ATLANTIC_API_KEY', 'raKBVBoZhQzNYA1dvOVzdTc1ED5iXe2fiQcv42GcoJ3ciRD7Ll8NJbRpc2QjOFiQxF2kjSCVccEWcBQDtcNevtZMj9L5K1PS3STS'); [cite_start]// Ganti dengan API Key Anda [cite: 111]
[cite_start]define('ATLANTIC_API_URL', 'https://atlantich2h.com'); [cite: 5]

$transactionId = $_POST['transaction_id'] ?? null;

if (!$transactionId) {
    echo json_encode(['status' => 'error', 'message' => 'ID transaksi tidak ditemukan.']);
    exit();
}

// --- Bagian ini untuk membaca data dari file (HANYA UNTUK UJI COBA!) ---
$transactionsFile = 'transactions.json';
$transactions = [];
if (file_exists($transactionsFile)) {
    $transactions = json_decode(file_get_contents($transactionsFile), true);
}

$localTransaction = $transactions[$transactionId] ?? null;

if (!$localTransaction) {
    echo json_encode(['status' => 'error', 'message' => 'Transaksi dengan ID tersebut tidak ditemukan di catatan lokal.']);
    exit();
}
// --- Akhir bagian membaca file ---


// Data yang akan dikirim ke API Atlantic Pedia untuk cek status deposit
$postData = http_build_query([
    [cite_start]'api_key' => ATLANTIC_API_KEY, [cite: 111]
    [cite_start]'id' => $transactionId // ID dari Atlantic [cite: 111]
]);

$ch = curl_init();
[cite_start]curl_setopt($ch, CURLOPT_URL, ATLANTIC_API_URL . '/deposit/status'); [cite: 109]
curl_setopt($ch, CURLOPT_POST, 1);
[cite_start]curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); [cite: 109, 110, 111]
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
[cite_start]curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']); [cite: 109]

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['status' => 'error', 'message' => 'Curl error: ' . $error]);
    exit();
}

$apiResponse = json_decode($response, true);

[cite_start]if (isset($apiResponse['status']) && $apiResponse['status'] === true) { [cite: 113]
    $paymentStatus = $apiResponse['data']['status']; [cite_start]// Status dari API Atlantic [cite: 113, 116]

    // Update status di catatan lokal (HANYA UNTUK UJI COBA!)
    $transactions[$transactionId]['payment_status'] = $paymentStatus;
    file_put_contents($transactionsFile, json_encode($transactions, JSON_PRETTY_PRINT));

    echo json_encode([
        'status' => 'success',
        [cite_start]'payment_status' => $paymentStatus, [cite: 113, 116]
        'access_link' => $localTransaction['expected_link'] // Kirim link dari data lokal kita
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        [cite_start]'message' => $apiResponse['message'] ?? 'Gagal memeriksa status pembayaran', [cite: 113]
        'api_response' => $apiResponse
    ]);
}
?>
