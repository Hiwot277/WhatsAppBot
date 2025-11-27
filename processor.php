<?php
// processor.php — calls your script logic and manages simple state
require_once __DIR__ . '/scripts.php';

// Track processed messages in memory for the current request
$processedMessages = [];

function processMessage($from, $text, $messageId = null) {
    global $processedMessages;
    
    // Normalize input
    $text = trim($text);
    if ($text === '') return null;
    
    // Create a unique key for this message
    $messageKey = $messageId ?: md5($from . $text . time());
    
    // Check if we've already processed this message in the current request
    if (in_array($messageKey, $processedMessages)) {
        error_log("Duplicate message detected in current request: $messageKey");
        return null;
    }
    $processedMessages[] = $messageKey;
    
    // Simple state example (file-based). For production use DB or Redis.
    $stateFile = __DIR__ . '/state_' . md5($from) . '.json';
    $state = [];
    if (file_exists($stateFile)) {
        $state = json_decode(file_get_contents($stateFile), true) ?? [];
    }
    
    // Check if this is a duplicate of the last message we processed
    $lastMessageFile = $stateDir . '/last_message_' . md5($from) . '.json';
    if (file_exists($lastMessageFile)) {
        $lastMessage = json_decode(file_get_contents($lastMessageFile), true);
        if ($lastMessage && $lastMessage['text'] === $text && (time() - $lastMessage['timestamp']) < 5) {
            error_log("Duplicate message detected (same as last message within 5 seconds): $text");
            return null;
        }
    }
    
    // Save this message as the last processed message
    file_put_contents($lastMessageFile, json_encode([
        'text' => $text,
        'timestamp' => time()
    ]));
    
    // Process the message
    $reply = runScripts($from, $text, $state);
    
    // Save the updated state
    if (!@file_put_contents($stateFile, json_encode($state))) {
        error_log("Failed to save state to file: $stateFile");
    }
    
    return $reply;
}