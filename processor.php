<?php
// processor.php — calls your script logic and manages simple state
require_once __DIR__ . '/scripts.php';
require_once __DIR__ . '/db_utils.php';

// Track processed messages in memory for the current request
$internalProcessedMessages = [];

function processMessage($from, $text, $messageId = null) {
    global $internalProcessedMessages;
    
    // Normalize input
    $text = trim($text);
    if ($text === '') return null;
    
    // Create a unique key for this message
    $messageKey = $messageId ?: md5($from . $text . time());
    
    // Check if we've already processed this message in the current request
    if (in_array($messageKey, $internalProcessedMessages)) {
        error_log("Duplicate message detected in current request: $messageKey");
        return null;
    }
    $internalProcessedMessages[] = $messageKey;
    
    // Ensure state directory exists
    $stateDir = __DIR__ . '/state';
    
    // Check if we can write to the local directory
    if (!file_exists($stateDir)) {
        if (!@mkdir($stateDir, 0755, true)) {
            // Fallback to system temp directory if we can't create 'state'
            $stateDir = sys_get_temp_dir() . '/whatsapp_bot_state';
            if (!file_exists($stateDir)) {
                mkdir($stateDir, 0755, true);
            }
        }
    } else {
        // If it exists but is not writable, switch to temp
        if (!is_writable($stateDir)) {
            $stateDir = sys_get_temp_dir() . '/whatsapp_bot_state';
            if (!file_exists($stateDir)) {
                mkdir($stateDir, 0755, true);
            }
        }
    }

    // Load state from file
    $stateFile = $stateDir . '/state_' . md5($from) . '.json';
    $state = [];
    if (file_exists($stateFile)) {
        $state = json_decode(file_get_contents($stateFile), true) ?? [];
    }
    
    // Check if this is a new conversation or a restart trigger
    $isRestartTrigger = in_array(strtolower($text), ['hey', 'hi', 'start', 'restart', 'hello']);
    $isNewConversation = empty($state) || $isRestartTrigger;
    
    // If it's a new conversation or a restart, start fresh
    if ($isNewConversation) {
        // Only reset if it's a true restart or first message.
        // If state is empty, it's a new conversation.
        // If state is not empty and it's a restart trigger, reset.
        if (empty($state) || $isRestartTrigger) {
            $state = [
                'step' => 'welcome',
                'phone_number' => $from,
                'start_time' => date('Y-m-d H:i:s')
            ];
            // Save conversation start to database
            saveUserResponse($from, 'conversation_start', '1');
        }
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
    
    // Save the updated state if not ending conversation
    // Save the updated state if not ending conversation
    if (!isset($reply['end_conversation'])) {
        $saveResult = @file_put_contents($stateFile, json_encode($state));
        if ($saveResult === false) {
            $error = error_get_last();
            error_log("CRITICAL: Failed to save state to file: $stateFile. Error: " . ($error['message'] ?? 'Unknown'));
        }
    } else {
        // Clear the state file if conversation ended
        if (file_exists($stateFile)) {
            unlink($stateFile);
        }
    }
    
    return $reply;
}