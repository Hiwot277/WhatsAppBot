<?php
// send.php — helper to send messages via WhatsApp Cloud API

function sendWhatsAppText($to, $message) {
    $token = env('WH_TOKEN');
    $phoneId = env('WH_PHONE_ID');
    
    if (!$token || !$phoneId) {
        return ['error' => 'Missing WH_TOKEN or WH_PHONE_ID in .env'];
    }

    $url = "https://graph.facebook.com/v16.0/{$phoneId}/messages";
    
    // If $message is a string, convert it to the expected array format
    if (is_string($message)) {
        $message = ['text' => $message];
    }
    
    // Prepare the base payload
    $payload = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $to,
    ];
    
    // Handle text with buttons
    if (isset($message['buttons'])) {
        $payload['type'] = 'interactive';
        $payload['interactive'] = [
            'type' => 'button',
            'header' => isset($message['header']) ? ['type' => 'text', 'text' => $message['header']] : null,
            'body' => ['text' => $message['text']],
            'action' => [
                'buttons' => []
            ]
        ];
        
        // Add buttons (max 3)
        foreach ($message['buttons'] as $button) {
            $payload['interactive']['action']['buttons'][] = [
                'type' => 'reply',
                'reply' => [
                    'id' => $button['id'],
                    'title' => $button['text']
                ]
            ];
        }
        
        // Remove header if not set
        if (!isset($message['header'])) {
            unset($payload['interactive']['header']);
        }
    } else {
        // Handle plain text message
        $payload['type'] = 'text';
        $payload['text'] = ['body' => $message['text']];
    }

    // Initialize cURL and set options
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$token}",
        'Content-Type: application/json'
    ]);
    
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Check for errors
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        
        // Log the error
        error_log("cURL Error: " . $error);
        error_log("Payload: " . json_encode($payload, JSON_PRETTY_PRINT));
        
        return [
            'error' => $error,
            'http' => $httpCode,
            'payload' => $payload
        ];
    }
    
    curl_close($ch);
    
    // Parse the JSON response
    $jsonResponse = json_decode($response, true);
    
    // Log the response for debugging
    error_log("WhatsApp API Response: " . json_encode([
        'http_code' => $httpCode,
        'response' => $jsonResponse,
        'payload' => $payload
    ], JSON_PRETTY_PRINT));
    
    return [
        'http' => $httpCode,
        'response' => $jsonResponse,
        'payload' => $payload
    ];
}