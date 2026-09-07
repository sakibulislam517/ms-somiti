CREATE TABLE IF NOT EXISTS members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    address VARCHAR(255) NULL,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS investments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) NOT NULL,
    issue_date DATE NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    item_description VARCHAR(255) NULL,
    quantity DECIMAL(12,2) NOT NULL DEFAULT 1,
    unit VARCHAR(30) NULL,
    selling_price DECIMAL(14,2) NOT NULL DEFAULT 0,
    cost_price DECIMAL(14,2) NOT NULL DEFAULT 0,
    gross_profit DECIMAL(14,2) NOT NULL DEFAULT 0,
    investment_amount DECIMAL(14,2) NOT NULL,
    amount_paid DECIMAL(14,2) NOT NULL DEFAULT 0,
    amount_due DECIMAL(14,2) NOT NULL DEFAULT 0,
    profit_rate DECIMAL(8,2) NOT NULL DEFAULT 0,
    profit_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    total_payable DECIMAL(14,2) NOT NULL DEFAULT 0,
    term_months SMALLINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    status ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_investments_member FOREIGN KEY (member_id) REFERENCES members(id),
    INDEX idx_investments_member (member_id),
    INDEX idx_investments_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS investment_schedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    investment_id INT UNSIGNED NOT NULL,
    installment_no SMALLINT UNSIGNED NOT NULL,
    due_date DATE NOT NULL,
    principal_amount DECIMAL(14,2) NOT NULL,
    profit_amount DECIMAL(14,2) NOT NULL,
    installment_amount DECIMAL(14,2) NOT NULL,
    paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    status ENUM('upcoming', 'partial', 'paid') NOT NULL DEFAULT 'upcoming',
    CONSTRAINT fk_schedule_investment FOREIGN KEY (investment_id) REFERENCES investments(id) ON DELETE CASCADE,
    UNIQUE KEY uq_investment_installment (investment_id, installment_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS member_collections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NOT NULL,
    purpose VARCHAR(150) NOT NULL DEFAULT 'Monthly fee',
    method VARCHAR(30) NOT NULL DEFAULT 'Cash',
    reference_no VARCHAR(100) NULL,
    amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    collected_at DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_collections_member FOREIGN KEY (member_id) REFERENCES members(id),
    INDEX idx_collections_member (member_id),
    INDEX idx_collections_date (collected_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS investor_ledger (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NOT NULL,
    entry_type ENUM('collection', 'investment', 'invest_withdraw', 'payment', 'expense') NOT NULL,
    debit DECIMAL(14,2) NOT NULL DEFAULT 0,
    credit DECIMAL(14,2) NOT NULL DEFAULT 0,
    purpose VARCHAR(200) NOT NULL DEFAULT '',
    method VARCHAR(30) NOT NULL DEFAULT 'Cash',
    reference_no VARCHAR(100) NULL,
    entry_date DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ledger_member FOREIGN KEY (member_id) REFERENCES members(id),
    INDEX idx_ledger_member (member_id),
    INDEX idx_ledger_type (entry_type),
    INDEX idx_ledger_date (entry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO members (name) VALUES
    ('Mohammad Jonaid'),
    ('Abdullah Ibne Kabir Fahim'),
    ('Abdur Rahim'),
    ('MD Ariful Islam Riad')
ON DUPLICATE KEY UPDATE name = VALUES(name);