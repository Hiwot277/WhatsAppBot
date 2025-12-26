<?php
$statusFile = __DIR__ . '/bot_status.txt';

// Initialize status if not exists
if (!file_exists($statusFile)) {
    file_put_contents($statusFile, 'ON');
}

// Handle toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentStatus = trim(file_get_contents($statusFile));
    $newStatus = ($currentStatus === 'ON') ? 'OFF' : 'ON';
    file_put_contents($statusFile, $newStatus);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$status = trim(file_get_contents($statusFile));
$isOnline = ($status === 'ON');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bot Status Control</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f2f5;
            color: #333;
        }
        .container {
            text-align: center;
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 300px;
        }
        h1 {
            margin-bottom: 2rem;
            font-size: 1.5rem;
            color: #1a1a1a;
        }       
        .status-indicator {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            font-weight: bold;
        }
        .status-on { color: #22c55e; }
        .status-off { color: #ef4444; }
        
        button {
            background: none;
            border: none;
            cursor: pointer;
            transition: transform 0.1s;
        }
        button:active {
            transform: scale(0.95);
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 80px;
            height: 40px;
            background-color: #ccc;
            border-radius: 40px;
            transition: background-color 0.3s;
        }
        .toggle-switch::after {
            content: '';
            position: absolute;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: white;
            top: 2px;
            left: 2px;
            transition: transform 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .active .toggle-switch {
            background-color: #22c55e;
        }
        .active .toggle-switch::after {
            transform: translateX(40px);
        }
        .inactive .toggle-switch {
            background-color: #ef4444;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>WhatsApp Bot Control</h1>
        
        <div class="status-indicator">
            Status: 
            <span class="<?php echo $isOnline ? 'status-on' : 'status-off'; ?>">
                <?php echo $isOnline ? 'ONLINE' : 'OFFLINE'; ?>
            </span>
        </div>

        <form method="POST" class="<?php echo $isOnline ? 'active' : 'inactive'; ?>">
            <button type="submit">
                <div class="toggle-switch"></div>
            </button>
        </form>
        
        <p style="margin-top: 2rem; font-size: 0.9rem; color: #666;">
            <?php echo $isOnline ? 'Bot is replying to messages.' : 'Bot is ignoring all messages.'; ?>
        </p>
    </div>
</body>
</html>
