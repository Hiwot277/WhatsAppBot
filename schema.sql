-- Database Schema for WhatsApp Bot
-- Extracted from config.php

SET NAMES utf8mb4;

-- Table: users_responses (Tax Refund Flow)
CREATE TABLE IF NOT EXISTS users_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL,
    full_name VARCHAR(100),
    employment_status VARCHAR(50),
    salary_range VARCHAR(50),
    tax_criteria VARCHAR(10),
    eligibility_check_1 VARCHAR(10),
    eligibility_check_2 VARCHAR(10),
    savings_potential VARCHAR(10),
    welcome_response VARCHAR(255),
    selected_area VARCHAR(50),
    phone_num_2 VARCHAR(50),
    id_number VARCHAR(50),
    savings_potential_response VARCHAR(50),
    confirmation_response VARCHAR(50),
    no_savings_response VARCHAR(50),
    conversation_start TIMESTAMP NULL,
    conversation_end TIMESTAMP NULL,
    conversation_complete BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone_number (phone_number),
    INDEX idx_conversation_complete (conversation_complete)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: loans_responses (Fast Loans Flow)
CREATE TABLE IF NOT EXISTS loans_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL,
    loans_credit_card VARCHAR(50),
    loans_employment_status VARCHAR(50),
    loans_amount VARCHAR(50),
    loans_pension_fund VARCHAR(10),
    loans_turnover VARCHAR(50),
    loans_business_age VARCHAR(50),
    loans_real_estate VARCHAR(10),
    loans_full_name VARCHAR(100),
    loans_id_number VARCHAR(50),
    loans_savings_potential VARCHAR(50),
    conversation_start TIMESTAMP NULL,
    conversation_end TIMESTAMP NULL,
    conversation_complete BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone_number (phone_number),
    INDEX idx_conversation_complete (conversation_complete)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure full_name exists in users_responses (Migration)
-- Note: In a raw SQL file, checking if a column exists is complex. 
-- The following line attempts to add it, but will fail if it exists. 
-- For safety, you can run this and ignore "Duplicate column" errors if it already exists.
-- ALTER TABLE users_responses ADD COLUMN full_name VARCHAR(100) AFTER phone_number;
