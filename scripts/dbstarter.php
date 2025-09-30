<?php
// Database Setup and Table Creation
class DatabaseStarter {
    private $host = 'localhost';
    private $dbname = 'stacknro_blog';
    private $username = 'stacknro_blog';
    private $password = 'admin-2025';
    private $pdo;
    
    public function __construct() {
        try {
            // First connect without database to create it
            $this->pdo = new PDO("mysql:host={$this->host}", $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if not exists
            $this->pdo->exec("CREATE DATABASE IF NOT EXISTS {$this->dbname} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Connect to the database
            $this->pdo = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "Database connection successful!\n";
            $this->createTables();
            
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    private function createTables() {
        $tables = [
            // Users table
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                avatar VARCHAR(255) DEFAULT 'default-avatar.png',
                bio TEXT,
                is_admin TINYINT(1) DEFAULT 0,
                is_verified TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            
            // Email verifications table
            "CREATE TABLE IF NOT EXISTS email_verifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(100) NOT NULL,
                verification_code VARCHAR(10) NOT NULL,
                code_type ENUM('registration', 'password_reset') DEFAULT 'registration',
                expires_at TIMESTAMP NOT NULL,
                is_used TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Posts table
            "CREATE TABLE IF NOT EXISTS posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                content LONGTEXT NOT NULL,
                image VARCHAR(255) NOT NULL,
                hashtags TEXT,
                author_id INT NOT NULL,
                status ENUM('draft', 'published') DEFAULT 'published',
                views INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
                FULLTEXT(title, hashtags)
            )",
            
            // Likes table
            "CREATE TABLE IF NOT EXISTS likes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                post_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                UNIQUE KEY unique_like (user_id, post_id)
            )",
            
            // Comments table
            "CREATE TABLE IF NOT EXISTS comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            
            // Chat messages table
            "CREATE TABLE IF NOT EXISTS chat_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sender_id INT NOT NULL,
                receiver_id INT NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            
            // Contact messages table
            "CREATE TABLE IF NOT EXISTS contact_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Sessions table for security
            "CREATE TABLE IF NOT EXISTS user_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_token VARCHAR(255) UNIQUE NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )"
        ];
        
        foreach ($tables as $table) {
            try {
                $this->pdo->exec($table);
                echo "Table created successfully\n";
            } catch(PDOException $e) {
                echo "Error creating table: " . $e->getMessage() . "\n";
            }
        }
        
        // Insert default admin user
        $this->createDefaultAdmin();
        echo "All tables created successfully!\n";
    }
    
    private function createDefaultAdmin() {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE is_admin = 1");
            $stmt->execute();
            $adminCount = $stmt->fetchColumn();
            
            if ($adminCount == 0) {
                $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, is_admin, is_verified) VALUES (?, ?, ?, 1, 1)");
                $stmt->execute(['admin', 'admin@blog.com', $hashedPassword]);
                echo "Default admin user created (admin/admin123)\n";
            }
        } catch(PDOException $e) {
            echo "Error creating admin: " . $e->getMessage() . "\n";
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

// Initialize database
$dbStarter = new DatabaseStarter();
echo "Database setup completed!\n";
?>
