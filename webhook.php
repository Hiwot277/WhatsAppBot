<?php
// Simple webhook entry point. Place webhook.php in your project root.
require_once __DIR__ . '/processor.php';
require_once __DIR__ . '/send.php';


function env($k, $d = null) {
if (getenv($k) !== false) return getenv($k);
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) return $d;
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
if (strpos(trim($line), '#') === 0) continue;
[$key, $val] = array_map('trim', explode('=', $line, 2) + [1 => '']);
if ($key === $k) return $val;
}
return $d;
}
$VERIFY_TOKEN = env('WH_VERIFY_TOKEN', 'my_verify_token_123');


function logit($msg) {
$logfile = __DIR__ . '/webhook.log';
$time = date('Y-m-d H:i:s');
file_put_contents($logfile, "[$time] $msg\n", FILE_APPEND);
}


// GET — verification
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
$mode = $_GET['hub_mode'] ?? null;
$token = $_GET['hub_verify_token'] ?? null;
$challenge = $_GET['hub_challenge'] ?? null;


if ($mode === 'subscribe' && $token === $VERIFY_TOKEN) {
echo $challenge;
logit("GET verified: $token");
exit;
} else {
http_response_code(403);
echo 'Forbidden';
logit("GET verification failed. Got token: " . ($token ?? ''));
exit;
}
}


// POST — events
$body = file_get_contents('php://input');
if (!$body) {
http_response_code(400);
echo 'No body';
logit("POST with empty body");
exit;
}


$data = json_decode($body, true);
logit("POST raw: " . substr($body,0,1000));
if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
$msg = $data['entry'][0]['changes'][0]['value']['messages'][0];
$from = $msg['from'] ?? 'unknown';
$msgType = $msg['type'] ?? 'unknown';
$text = $msgType === 'text' ? ($msg['text']['body'] ?? '') : '';
logit("Message from $from type $msgType text: " . substr($text,0,500));


// Process message with your scripts
$reply = processMessage($from, $text);


if ($reply) {
$sendResp = sendWhatsAppText($from, $reply);
logit("Send response: " . json_encode($sendResp));
} else {
logit("No reply generated for message from $from");
}
} else {
logit("No message object in payload");
}


http_response_code(200);
echo 'EVENT_RECEIVED';