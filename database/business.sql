CREATE DATABASE IF NOT EXISTS business;
USE business;

CREATE TABLE IF NOT EXISTS ClientTransactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL,
    transaction_date DATE NOT NULL,
    status VARCHAR(50) NOT NULL,
    system_name VARCHAR(255) NOT NULL,
    payment VARCHAR(100) DEFAULT NULL,
    date_completed DATE DEFAULT NULL,
    transaction_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    deleted_at DATETIME DEFAULT NULL
);
