📖 WhatsAppBot
Overview
WhatsAppBot is a PHP‑based automation tool designed to handle customer interactions over WhatsApp. It integrates with Google Sheets for structured data storage and supports dynamic question flows, loan eligibility checks, and bot toggling features.

✨ Features
Automated Question Flow: Collects user responses and saves them directly into Google Sheets.
Loan Script Module: Includes eligibility logic and fast loan processing scripts.
Bot Toggle: Easily switch the bot on/off using toggle_bot.php or bot_status.txt.
Error & Debug Logging: Tracks activity with logs (debug_log.txt, webhook.log, etc.) for troubleshooting.
Database Integration: Saves user responses and test data into MySQL using helper scripts.
Webhook Support: Handles incoming WhatsApp messages via webhook.php.

🛠 Tech Stack
Language: PHP (100%)
Data Storage: Google Sheets + MySQL
Deployment: Vercel + Cloudways (PHP Stack)
Version Control: GitHub

📂 Repository Structure
Code
├── config.php              # Configuration for Google Sheets + DB
├── processor.php           # Core message processing logic
├── webhook.php             # WhatsApp webhook handler
├── toggle_bot.php          # Enable/disable bot
├── verify_eligibility_logic.php # Loan eligibility checks
├── test_*.php              # Test scripts for flows & DB
├── logs/                   # Debug and error logs
└── README.md               # Project documentation

🚀 Getting Started
Clone the repo
bash
git clone https://github.com/Hiwot277/WhatsAppBot.git
cd WhatsAppBot
Configure environment

Update .env with Google Sheets API credentials and DB connection.

Set webhook URL in WhatsApp Business API.

Run locally

Deploy on PHP server (Cloudways/Vercel).

Test with test_bot_response.php or test_google_sheets.php.

📌 Usage
Deploy the bot and connect it to your WhatsApp Business account.

Use Google Sheets as a backend for storing structured responses.

Toggle bot availability with toggle_bot.php.

🤝 Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you’d like to change.
