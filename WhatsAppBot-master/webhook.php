<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Simple webhook entry point. Place webhook.php in your project root.
require_once __DIR__ . '/processor.php';
require_once __DIR__ . '/send.php';

// Set error log file
ini_set('error_log', __DIR__ . '/php_errors.log');

// Track processed message IDs to prevent duplicates
$processedMessagesFile = __DIR__ . '/processed_messages.json';
$processedMessages = [];

// Load processed messages from file
if (file_exists($processedMessagesFile)) {
    $processedMessages = json_decode(file_get_contents($processedMessagesFile), true) ?: [];
}

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

function logit($msg, $data = null) {
    $logfile = __DIR__ . '/webhook.log';
    $time = date('Y-m-d H:i:s');
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = isset($backtrace[1]) ? 
        basename($backtrace[1]['file']) . ':' . $backtrace[1]['line'] : 'unknown';
    
    $logMessage = "[$time] [$caller] $msg";
    
    if ($data !== null) {
        $logMessage .= ' ' . (is_string($data) ? $data : json_encode($data, JSON_PRETTY_PRINT));
    }
    
    $logMessage .= "\n";
    
    file_put_contents($logfile, $logMessage, FILE_APPEND);
    error_log(trim($logMessage));
}


// GET — verification
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? null;
    $token = $_GET['hub_verify_token'] ?? null;
    $challenge = $_GET['hub_challenge'] ?? null;

    if ($mode === 'subscribe' && $token === $VERIFY_TOKEN) {
        logit("Webhook verified");
        http_response_code(200);
        echo $challenge;
        exit;
    }

    http_response_code(403);
    logit("Webhook verification failed: $mode $token");
    exit;
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
logit("Incoming webhook data:", $data);

// Check if this is a message we've already processed
$messageId = $data['entry'][0]['changes'][0]['value']['messages'][0]['id'] ?? null;
if ($messageId && isset($processedMessages[$messageId])) {
    logit("Duplicate message detected, ignoring: $messageId");
    http_response_code(200);
    echo 'EVENT_RECEIVED';
    exit;
}

// Add to processed messages and save to file
if ($messageId) {
    $processedMessages[$messageId] = time();
    
    // Clean up old messages (older than 1 hour)
    $oneHourAgo = time() - 3600;
    foreach ($processedMessages as $id => $timestamp) {
        if ($timestamp < $oneHourAgo) {
            unset($processedMessages[$id]);
        }
    }
    
    // Keep only the last 1000 message IDs to prevent file from growing too large
    if (count($processedMessages) > 1000) {
        $processedMessages = array_slice($processedMessages, -1000, null, true);
    }
    
    // Save to file
    file_put_contents($processedMessagesFile, json_encode($processedMessages));
}

if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
    $msg = $data['entry'][0]['changes'][0]['value']['messages'][0];
    $from = $msg['from'] ?? 'unknown';
    $msgType = $msg['type'] ?? 'unknown';
    
    // Handle different message types
    if ($msgType === 'text') {
        $text = $msg['text']['body'] ?? '';
    } elseif ($msgType === 'interactive' && isset($msg['interactive']['button_reply']['id'])) {
        // Handle button clicks
        $text = $msg['interactive']['button_reply']['id'];
    } else {
        $text = '';
    }
    
    logit("Processing message", [
        'from' => $from,
        'type' => $msgType,
        'text' => $text,
        'message_id' => $messageId
    ]);

    // Process message with your scripts
    $reply = processMessage($from, $text, $messageId);
    logit("Generated reply:", $reply);

    if ($reply) {
        $sendResp = sendWhatsAppText($from, $reply);
        logit("Send response:", $sendResp);
    } else {
        logit("No reply generated for message from $from");
        // Send default response
        $defaultReply = [
            'text' => "I'm not sure how to respond to that. Type 'restart' to begin again."
        ];
        $sendResp = sendWhatsAppText($from, $defaultReply);
        logit("Sent default response");
    }
} else {
    logit("No message object in payload");
}

http_response_code(200);
echo 'EVENT_RECEIVED';