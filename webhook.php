<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check Bot Status
$statusFile = __DIR__ . '/bot_status.txt';
if (file_exists($statusFile)) {
    $status = trim(file_get_contents($statusFile));
    if ($status === 'OFF') {
        // Log only if it's a POST request to avoid spamming logs on GET checks if any
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            error_log("Bot is OFF. Ignoring request.");
        }
        http_response_code(200);
        echo 'EVENT_RECEIVED';
        exit;
    }
}

// Simple webhook entry point. Place webhook.php in your project root.
require_once __DIR__ . '/processor.php';
require_once __DIR__ . '/send.php';

// Set error log file
ini_set('error_log', __DIR__ . '/php_errors.log');

// Note: We do not filter or reject events based on 'messages'.
// If idempotency is needed, implement it in application logic or an external store.

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

$VERIFY_TOKEN = "my_verify_token_123";

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
    // Accept both dotted (hub.mode) and underscored (hub_mode) query param formats
    $mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? null;
    $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? null;
    $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? null;

    // Log what we received (do not log the token itself)
    logit('GET verification request', [
        'query' => $_SERVER['QUERY_STRING'] ?? '',
        'mode' => $mode,
        'token_present' => ($token !== null),
        'challenge_present' => ($challenge !== null),
    ]);

    // Use a timing-safe comparison for the verify token
    if ($mode === 'subscribe' && $token !== null && function_exists('hash_equals') && hash_equals($VERIFY_TOKEN, $token)) {
        logit("Webhook verified");
        http_response_code(200);
        // echo challenge if provided
        if ($challenge !== null) {
            echo $challenge;
        } else {
            echo 'VERIFIED';
        }
        exit;
    }

    // Invalid verification — return 403
    http_response_code(403);
    logit("Webhook verification failed: mode={$mode} token_provided=" . ($token !== null ? 'yes' : 'no'));
    echo 'Forbidden';
    exit;
}

// POST — events
$body = file_get_contents('php://input');
// Log the full raw payload for audit/inspection (per requirement)
file_put_contents(__DIR__ . '/webhook.log', "[" . date('c') . "] RAW: " . $body . PHP_EOL, FILE_APPEND);
logit("POST received");

$data = json_decode($body, true);
logit("Incoming webhook data (v2):", $data);

// Check if this is a message we've already processed
$processedMessagesFile = __DIR__ . '/processed_messages.json';
$messageId = $data['entry'][0]['changes'][0]['value']['messages'][0]['id'] ?? null;
if ($messageId && isset($processedMessages[$messageId])) {
    logit("Duplicate message detected, ignoring: $messageId");
    http_response_code(200);
    echo 'EVENT_RECEIVED';
    exit;
}

// Add to processed messages and save to file
if ($messageId) {
    $fp = fopen($processedMessagesFile, 'c+');
    if (flock($fp, LOCK_EX)) {
        // Reload to get latest changes
        $fileContent = stream_get_contents($fp);
        $processedMessages = $fileContent ? json_decode($fileContent, true) : [];
        
        if (isset($processedMessages[$messageId])) {
            // Already processed by another process while we were waiting for lock
            flock($fp, LOCK_UN);
            fclose($fp);
            logit("Duplicate message detected (race condition), ignoring: $messageId");
            http_response_code(200);
            echo 'EVENT_RECEIVED';
            exit;
        }
        
        $processedMessages[$messageId] = time();
        
        // Clean up old messages (older than 1 hour)
        $oneHourAgo = time() - 3600;
        foreach ($processedMessages as $id => $timestamp) {
            if ($timestamp < $oneHourAgo) {
                unset($processedMessages[$id]);
            }
        }
        
        // Keep only the last 1000 message IDs
        if (count($processedMessages) > 1000) {
            $processedMessages = array_slice($processedMessages, -1000, null, true);
        }
        
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($processedMessages));
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
    $msg = $data['entry'][0]['changes'][0]['value']['messages'][0];
    $from = $msg['from'] ?? 'unknown';
    $msgType = $msg['type'] ?? 'unknown';
    $timestamp = $msg['timestamp'] ?? time();

    // Do not filter messages by age; pass through all events and let application logic handle idempotency if needed.
    
    // Handle different message types
    if ($msgType === 'text') {
        $text = $msg['text']['body'] ?? '';
    } elseif ($msgType === 'interactive' && isset($msg['interactive']['button_reply']['id'])) {
        // Handle button clicks
        $text = $msg['interactive']['button_reply']['id'];
    } elseif ($msgType === 'interactive' && isset($msg['interactive']['list_reply']['id'])) {
        // Handle list selection
        $text = $msg['interactive']['list_reply']['id'];
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
    $phoneNumberId = $data['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? null;
    $reply = processMessage($from, $text, $messageId, $phoneNumberId);
    logit("Generated reply:", $reply);

    if ($reply) {
        $sendResp = sendWhatsAppText($from, $reply, $phoneNumberId);
        logit("Send response:", $sendResp);
    } else {
        logit("No reply generated for message from $from");
                
    }
} else {
    logit("No message object in payload");
}

http_response_code(200);
echo 'EVENT_RECEIVED';