<?php
// scripts.php — put your custom script logic here.
// Edit this file to change bot behavior.


function runScripts(&$from, &$text, array &$state) {
// Example simple keyword-based flow. Replace with your scripts.
$lc = strtolower($text);


// Restart command
if (in_array($lc, ['restart', 'start over', 'reset'])) {
$state = [];
return "Conversation restarted. Hi! How can I help you today?";
}


if (strpos($lc, 'hi') === 0 || strpos($lc, 'hello') === 0) {
$state['last_intent'] = 'greeting';
return "Hello! I\'m your bot. Type 'menu' to see options.";
}


if ($lc === 'menu') {
$state['last_intent'] = 'menu';
return "Menu:\n1. Price\n2. Hours\n3. Contact\nReply with the word for the option.";
}

if ($lc === 'price' || $lc === '1') {
$state['last_intent'] = 'price';
return "Our price starts at $50. For custom quotes reply 'quote'.";
}


if ($lc === 'hours' || $lc === '2') {
$state['last_intent'] = 'hours';
return "We are open Mon-Fri 9:00-17:00.";
}


if ($lc === 'contact' || $lc === '3') {
$state['last_intent'] = 'contact';
return "You can contact us at +251XXXXXXXXX or email@example.com";
}
// Fallback
return "Sorry, I didn't understand that. Type 'menu' to see options or 'help' for assistance.";
}