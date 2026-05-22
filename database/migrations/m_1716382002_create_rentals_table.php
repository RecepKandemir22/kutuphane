<?php
/**
 * Migration: CreateRentalsTable
 */
return new class {
    
    public function up($db) {
        // Create rentals table with foreign key constraints
        $db->query("
            CREATE TABLE IF NOT EXISTS `rentals` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `book_id` INT NOT NULL,
                `rent_date` DATE NOT NULL,
                `return_date` DATE NULL,
                `status` VARCHAR(20) DEFAULT 'rented', -- rented, returned
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
    
    public function down($db) {
        $db->query("DROP TABLE IF EXISTS `rentals`");
    }
};
PHP;
