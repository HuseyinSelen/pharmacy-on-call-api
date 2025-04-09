<?php

/**
 * Ankara Nöbetçi Eczane Bilgileri
 *
 * Bu PHP script'i, Ankara Eczacı Odası'nın sağladığı API'den belirli bir tarihteki
 * tüm nöbetçi eczanelerin listesini çeker ve basit JSON formatında geri döner.
 *
 * Kullanımı:
 * GET isteği ile çağrılmalıdır:
 * http://localhost/ankara-pharmacies.php?date=2025-04-09
 */

header('Content-Type: application/json; charset=utf-8');

// Kullanıcıdan tarih al (varsayılan olarak bugünü alır)
$date = $_GET['date'] ?? date('Y-m-d');

// API URL
$apiUrl = "https://mvc.aeo.org.tr/home/NobetciEczaneGetirTarih?nobetTarihi=" . $date;

// cURL kullanarak verileri çek
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'User-Agent: PHP'
    ]
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["status" => false, "error" => curl_error($ch)]);
    exit;
}

curl_close($ch);

$data = json_decode($response, true);

// API yanıt kontrolü
if (!$data || !$data['isSuccess']) {
    echo json_encode(["status" => false, "error" => "API verisi alınamadı."]);
    exit;
}

// Eczane bilgilerini düzenle
$pharmacies = [];

foreach ($data['NobetciEczaneBilgisiListesi'] as $eczane) {
    $pharmacies[] = [
        "name"      => $eczane["EczaneAdi"],
        "address"   => $eczane["EczaneAdresi"],
        "phone"     => $eczane["Telefon"],
        "district"  => $eczane["IlceAdi"],
        "description" => $eczane["AdresAciklamasi"],
        "location"  => [
            "lat" => $eczane["KoordinatLat"],
            "lng" => $eczane["KoordinatLng"]
        ]
    ];
}

// JSON formatında çıktı ver
echo json_encode(["status" => true, "data" => $pharmacies], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);