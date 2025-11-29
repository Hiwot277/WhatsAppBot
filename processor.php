<?php
// processor.php — calls your script logic and manages simple state
require_once __DIR__ . '/scripts.php';
require_once __DIR__ . '/db_utils.php';

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
    
    // Ensure state directory exists
    $stateDir = __DIR__ . '/state';
    if (!file_exists($stateDir)) {
        mkdir($stateDir, 0755, true);
    }

    // Load state from file
    $stateFile = $stateDir . '/state_' . md5($from) . '.json';
    $state = [];
    if (file_exists($stateFile)) {
        $state = json_decode(file_get_contents($stateFile), true) ?? [];
    }
    
    // Check if this is a new conversation
    $isNewConversation = empty($state) || in_array(strtolower($text), ['hi', 'hello', 'start']);
    
    // If it's a new conversation, start fresh
    if ($isNewConversation) {
        $state = [
            'step' => 'welcome',
            'phone_number' => $from,
            'start_time' => date('Y-m-d H:i:s')
        ];
        // Save conversation start to database
        saveUserResponse($from, 'conversation_start', '1');
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
    ], JSON_PRETTY_PRINT));
    
    // Call the main script logic
    $reply = runScripts($from, $text, $state);
    
    // Handle end of conversation
    if (isset($reply['end_conversation'])) {
        // Save the end of conversation to database
        saveUserResponse($from, 'conversation_complete', '1');
        // Clear the state
        $state = [];
    } 
    // Save the response to database if we have a valid field to save
    elseif (isset($state['step'])) {
        $field = $state['step'];
        if (isset($state[$field])) {
            saveUserResponse($from, $field, $state[$field]);
        }
    }
    
    // Save the updated state if not ending conversation
    if (!isset($reply['end_conversation'])) {
        if (!@file_put_contents($stateFile, json_encode($state))) {
            error_log("Failed to save state to file: $stateFile");
        }
    } else {
        // Clear the state file if conversation ended
        if (file_exists($stateFile)) {
            unlink($stateFile);
        }
    }
    
    return $reply;
}