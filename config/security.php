<?php
/**
 * Güvenlik Konfigürasyonu ve Yardımcı Fonksiyonlar
 */

// CSRF Token oluşturma
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF Token doğrulama
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// XSS koruması için güvenli çıktı
function safeOutput($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Dosya adı temizleme
function sanitizeFileName($filename) {
    // Tehlikeli karakterleri temizle
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    // Çoklu noktaları tek noktaya çevir
    $filename = preg_replace('/\.+/', '.', $filename);
    // Dosya adının uzunluğunu sınırla
    if (strlen($filename) > 255) {
        $filename = substr($filename, 0, 255);
    }
    return $filename;
}

// Güvenli dizin oluşturma
function createSecureDirectory($path) {
    if (!file_exists($path)) {
        mkdir($path, 0755, true);
        // .htaccess dosyası oluştur
        file_put_contents($path . '/.htaccess', "Options -Indexes\nDeny from all");
        // index.php dosyası oluştur
        file_put_contents($path . '/index.php', "<?php\nheader('HTTP/1.0 403 Forbidden');\nexit('Access denied');");
    }
}

// Session güvenliği
function secureSession() {
    // Session güvenlik ayarları
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_strict_mode', 1);
    
    // Session ID'yi düzenli olarak yenile
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// IP adresi kontrolü
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Rate limiting basit implementasyonu
function checkRateLimit($action, $limit = 10, $window = 3600) {
    $key = 'rate_limit_' . $action . '_' . getUserIP();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $window];
    }
    
    $data = $_SESSION[$key];
    
    // Zaman aşımı kontrolü
    if (time() > $data['reset_time']) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $window];
        $data = $_SESSION[$key];
    }
    
    // Limit kontrolü
    if ($data['count'] >= $limit) {
        return false;
    }
    
    // Sayacı artır
    $_SESSION[$key]['count']++;
    
    return true;
}

// Dosya MIME type doğrulaması
function validateMimeType($filePath, $allowedTypes) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    
    return in_array($mimeType, $allowedTypes);
}

// Güvenli yönlendirme
function safeRedirect($url) {
    // Sadece internal URL'lere yönlendirme yapmaya izin ver
    $parsedUrl = parse_url($url);
    if (isset($parsedUrl['host']) || isset($parsedUrl['scheme'])) {
        // External URL, güvenli değil
        header('Location: /index.php');
    } else {
        header('Location: ' . $url);
    }
    exit;
}

// Güvenli dosya silme
function secureFileDelete($filePath) {
    if (file_exists($filePath)) {
        // Dosyanın gerçek yolunu kontrol et
        $realPath = realpath($filePath);
        $uploadDir = realpath(__DIR__ . '/../uploads/');
        
        // Dosya upload dizini içinde mi?
        if (strpos($realPath, $uploadDir) === 0) {
            return unlink($filePath);
        }
    }
    return false;
}

// Error logging
function logSecurityEvent($event, $details = '') {
    $logEntry = date('Y-m-d H:i:s') . ' - ' . $event . ' - IP: ' . getUserIP();
    if ($details) {
        $logEntry .= ' - Details: ' . $details;
    }
    $logEntry .= PHP_EOL;
    
    file_put_contents(__DIR__ . '/../logs/security.log', $logEntry, FILE_APPEND | LOCK_EX);
}

// Güvenli session başlatma
function startSecureSession() {
    secureSession();
    
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    // Session hijacking koruması
    if (!isset($_SESSION['user_agent'])) {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    } elseif ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        // Session hijacking algılandı
        session_destroy();
        logSecurityEvent('Session hijacking attempt detected');
        header('Location: /pages/login.php');
        exit;
    }
}
?>