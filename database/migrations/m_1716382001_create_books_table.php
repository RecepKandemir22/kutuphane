<?php
/**
 * Migration: CreateBooksTable
 */
return new class {
    
    public function up($db) {
        // Create books table
        $db->query("
            CREATE TABLE IF NOT EXISTS `books` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(150) NOT NULL,
                `author` VARCHAR(100) NOT NULL,
                `isbn` VARCHAR(30) UNIQUE NOT NULL,
                `category` VARCHAR(50) NOT NULL,
                `total_copies` INT NOT NULL DEFAULT 1,
                `available_copies` INT NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Seed default books catalog
        $db->query("
            INSERT INTO `books` (`title`, `author`, `isbn`, `category`, `total_copies`, `available_copies`) VALUES 
            ('Design Patterns', 'Erich Gamma', '9780201633610', 'Programming', 5, 5),
            ('Clean Code', 'Robert C. Martin', '9780132350884', 'Software Engineering', 3, 3),
            ('Refactoring', 'Martin Fowler', '9780201485677', 'Refactoring', 4, 4),
            ('The Pragmatic Programmer', 'Andrew Hunt', '9780201616224', 'Development', 6, 6)
        ");
    }
    
    public function down($db) {
        $db->query("DROP TABLE IF EXISTS `books`");
    }
};
PHP;
