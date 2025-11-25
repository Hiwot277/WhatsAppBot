<?php
// send.php — helper to send text messages via WhatsApp Cloud API


function sendWhatsAppText($to, $text) {
$token = env('WH_TOKEN');
$phoneId = env('WH_PHONE_ID');
if (!$token || !$phoneId) {
return ['error' => 'Missing WH_TOKEN or WH_PHONE_ID in .env'];
}


$url = "https://graph.facebook.com/v16.0/{$phoneId}/messages";
$payload = [
'messaging_product' => 'whatsapp',
'to' => $to,
'type' => 'text',
'text' => ['body' => $text]
];


$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
"Authorization: Bearer {$token}",
'Content-Type: application/json'
]);curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if (curl_errno($ch)) {
$err = curl_error($ch);
curl_close($ch);
return ['error' => $err];
}
curl_close($ch);


$json = json_decode($res, true);
return ['http' => $httpCode, 'response' => $json];
}