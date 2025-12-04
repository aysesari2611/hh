<?php
require_once __DIR__ . '/../config/database.php';

/**
 * User sınıfı - Kullanıcı işlemlerini yönetir
 */
class User {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * Yeni kullanıcı kaydı oluşturur
     */
    public function register($username, $email, $password, $full_name) {
        try {
            // Kullanıcı adı ve email kontrolü
            if ($this->userExists($username, $email)) {
                return ['success' => false, 'message' => 'Bu kullanıcı adı veya email zaten kullanılıyor.'];
            }
            
            // Şifreyi hash'le
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            
            if ($stmt->execute([$username, $email, $hashed_password, $full_name])) {
                return ['success' => true, 'message' => 'Kayıt başarılı!'];
            } else {
                return ['success' => false, 'message' => 'Kayıt sırasında hata oluştu.'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }
    
    /**
     * Kullanıcı giriş kontrolü
     */
    public function login($username, $password) {
        try {
            $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$username, $username]);
            
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                
                return ['success' => true, 'message' => 'Giriş başarılı!', 'user' => $user];
            } else {
                return ['success' => false, 'message' => 'Kullanıcı adı/email veya şifre hatalı.'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }
    
    /**
     * Kullanıcı çıkışı
     */
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Çıkış yapıldı.'];
    }
    
    /**
     * Kullanıcı ID'sine göre kullanıcı bilgilerini getirir
     */
    public function getUserById($user_id) {
        try {
            $sql = "SELECT id, username, email, full_name, created_at FROM users WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$user_id]);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Kullanıcı adı veya email kontrolü
     */
    private function userExists($username, $email) {
        try {
            $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$username, $email]);
            
            return $stmt->fetch() ? true : false;
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Kullanıcı arama
     */
    public function searchUsers($search_term) {
        try {
            $sql = "SELECT id, username, full_name FROM users WHERE username LIKE ? OR full_name LIKE ?";
            $stmt = $this->pdo->prepare($sql);
            $search_param = '%' . $search_term . '%';
            $stmt->execute([$search_param, $search_param]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>