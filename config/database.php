<?php
/**
 * Veritabanı Bağlantı Konfigürasyonu
 * MySQL PDO bağlantısı için gerekli ayarlar
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'file_sharing_site';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    
    public $pdo;
    
    public function getConnection() {
        $this->pdo = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            echo "Veritabanı bağlantı hatası: " . $exception->getMessage();
        }
        
        return $this->pdo;
    }
}

// Global veritabanı bağlantısı
function getDB() {
    static $database = null;
    if ($database === null) {
        $database = new Database();
    }
    return $database->getConnection();
}
?>