<?php

function fetchBursaEczaneler(): array {
    $url = 'https://www.beo.org.tr/nobetci-eczaneler';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
    ]);
    $html = curl_exec($ch);

    if ($html === false) {
        die("cURL Hatası: " . curl_error($ch));
    }

    curl_close($ch);

    // DEBUG: HTML çıktıyı kontrol edebilmek için
    file_put_contents(__DIR__ . "/bursa-debug.html", $html);

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $eczaneler = [];
    foreach ($xpath->query('//div[contains(@class, "nobetci")]') as $eczaneDiv) {
        // Eczane adı ve ilçe
        $titleNode = $xpath->query('.//h4', $eczaneDiv)->item(0);
        $titleText = $titleNode ? trim($titleNode->textContent) : '';
        $name = null;
        $district = null;
    
        if (strpos($titleText, ' - ') !== false) {
            [$name, $district] = explode(' - ', $titleText);
        }
    
        // Adres ikonu sonrası gelen text node'u bul
        $address = '';
        $phone = '';
    
        // Adres kısmı <i class="fa fa-home ..."></i> öğesinden sonra gelen text olarak başlar
        $iTags = $xpath->query('.//i[contains(@class, "fa-home")]', $eczaneDiv);
        if ($iTags->length > 0) {
            $iNode = $iTags->item(0);
            $textNode = $iNode->nextSibling;
            if ($textNode && $textNode->nodeType === XML_TEXT_NODE) {
                $address = trim($textNode->textContent);
            }
        }
    
        // Telefon
        $phoneLink = $xpath->query('.//a[contains(@href, "tel:")]', $eczaneDiv)->item(0);
        if ($phoneLink) {
            $phone = trim($phoneLink->textContent);
        }
    
        $eczaneler[] = [
            'name' => trim($name),
            'district' => trim($district),
            'address' => $address,
            'phone' => $phone,
        ];
    }
    

    return $eczaneler;
}

// Kullanım
header('Content-Type: application/json; charset=utf-8');
echo json_encode(fetchBursaEczaneler(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
