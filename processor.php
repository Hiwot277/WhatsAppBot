<?php
// processor.php — calls your script logic and manages simple state
require_once __DIR__ . '/scripts.php';


function processMessage($from, $text) {
// Normalize
$text = trim($text);
if ($text === '') return null;


// Simple state example (file-based). For production use DB or Redis.
$stateFile = __DIR__ . '/state_' . md5($from) . '.json';
$state = [];
if (file_exists($stateFile)) {
$state = json_decode(file_get_contents($stateFile), true) ?? [];
}


// Call your scripts (you edit scripts.php to change behavior)
$reply = runScripts($from, $text, $state);


// Save state if changed (script may modify $state by reference)
file_put_contents($stateFile, json_encode($state));


return $reply;
}