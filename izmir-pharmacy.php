<?php
header('Content-Type: application/json; charset=utf-8');

$url = "https://www.izmireczaciodasi.org.tr/nobetci-eczaneler";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
]);

$html = curl_exec($ch);
curl_close($ch);

// Hata kontrolü
if (!$html) {
    echo json_encode(['status' => false, 'error' => 'Sayfa alınamadı.']);
    exit;
}

// DEBUG: İstersen çıktıyı kaydet
file_put_contents("izmir-debug.html", $html);

// DOM ile parse et
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
libxml_clear_errors();

$xpath = new DOMXPath($dom);
$blocks = $xpath->query("//div[contains(@class, 'col_12_of_12') and .//h4[contains(., 'ECZANESİ')]]");

$data = [];

foreach ($blocks as $block) {
    $nameNode = $xpath->query(".//h4", $block)->item(0);
    $name = $nameNode ? trim(strip_tags($nameNode->nodeValue)) : '';

    $addressLines = $xpath->query(".//p", $block);
    $address = '';
    $description = '';
    $phone = '';
    $map = '';

    if ($addressLines->length > 0) {
        $html = $dom->saveHTML($addressLines->item(0));

        preg_match('/\d{3,4}\s?\d{3}\s?\d{2}\s?\d{2}/', $html, $tel);
        $phone = $tel[0] ?? '';

        preg_match('/<i class="fa fa-home main-color"><\/i>\s*(.*?)<br>/', $html, $adres1);
        $address = $adres1[1] ?? '';

        preg_match('/<i class="fa fa-arrow-right main-color"><\/i>\s*(.*?)<br>/', $html, $desc1);
        $description = $desc1[1] ?? '';

        preg_match('/https:\/\/www\.google\.com\/maps\?q=([\d.,-]+)/', $html, $coords);
        $map = $coords[1] ?? '';
    }

    $data[] = [
        'name' => $name,
        'address' => trim($address),
        'description' => trim($description),
        'phone' => $phone,
        'location' => $map
    ];
}

echo json_encode(['status' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
