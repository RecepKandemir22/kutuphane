<?php
/**
 * Migration: CreateUsersTable
 */
return new class {
    
    public function up($db) {
        // Create users table
        $db->query("
            CREATE TABLE IF NOT EXISTS `users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `email` VARCHAR(100) UNIQUE NOT NULL,
                `password` VARCHAR(255) NOT NULL,
                `role` VARCHAR(20) DEFAULT 'student',
                `remember_token` VARCHAR(100) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Hash password '123456' dynamically
        $hashed = password_hash('123456', PASSWORD_BCRYPT);

        // Seed default records
        $db->query("
            INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES 
            ('Admin User', 'admin@codeforge.com', '{$hashed}', 'admin'),
            ('John Doe', 'john@codeforge.com', '{$hashed}', 'student'),
            ('Jane Smith', 'jane@codeforge.com', '{$hashed}', 'student')
        ");
    }
    
    public function down($db) {
        $db->query("DROP TABLE IF EXISTS `users`");
    }
};
PHP;
