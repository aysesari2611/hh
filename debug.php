<?php
// Debug sayfası
session_start();
require_once 'config/database.php';

echo "<h1>Sistem Durumu Debug</h1>";

echo "<h2>PHP Ayarları</h2>";
echo "<p><strong>upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . "</p>";
echo "<p><strong>post_max_size:</strong> " . ini_get('post_max_size') . "</p>";
echo "<p><strong>max_file_uploads:</strong> " . ini_get('max_file_uploads') . "</p>";
echo "<p><strong>memory_limit:</strong> " . ini_get('memory_limit') . "</p>";
echo "<p><strong>max_execution_time:</strong> " . ini_get('max_execution_time') . " saniye</p>";

echo "<h2>Klasör Durumu</h2>";
$uploadDir = realpath(__DIR__ . '/uploads') . DIRECTORY_SEPARATOR;
echo "<p><strong>Upload klasörü:</strong> " . $uploadDir . "</p>";
echo "<p><strong>Klasör var mı?:</strong> " . (file_exists($uploadDir) ? 'Evet' : 'Hayır') . "</p>";
echo "<p><strong>Yazılabilir mi?:</strong> " . (is_writable($uploadDir) ? 'Evet' : 'Hayır') . "</p>";

echo "<h2>Veritabanı Bağlantısı</h2>";
try {
    $pdo = getDB();
    echo "<p><strong>Veritabanı:</strong> Bağlantı başarılı</p>";
    
    // Files tablosu varlığını kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'files'");
    if ($stmt->rowCount() > 0) {
        echo "<p><strong>Files tablosu:</strong> Var</p>";
    } else {
        echo "<p><strong>Files tablosu:</strong> YOK!</p>";
    }
} catch (Exception $e) {
    echo "<p><strong>Veritabanı hatası:</strong> " . $e->getMessage() . "</p>";
}

echo "<h2>Session Bilgileri</h2>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>User ID:</strong> " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Giriş yapılmamış') . "</p>";

// Test dosya yükleme
if ($_POST && isset($_FILES['test_file'])) {
    echo "<h2>Test Yükleme Sonucu</h2>";
    echo "<pre>";
    print_r($_FILES['test_file']);
    echo "</pre>";
    
    if ($_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
        $tempName = $_FILES['test_file']['tmp_name'];
        $uploadPath = $uploadDir . 'test_' . time() . '_' . $_FILES['test_file']['name'];
        
        if (move_uploaded_file($tempName, $uploadPath)) {
            echo "<p style='color: green;'>✓ Test dosyası başarıyla yüklendi: " . basename($uploadPath) . "</p>";
            // Test dosyasını sil
            unlink($uploadPath);
            echo "<p>Test dosyası temizlendi.</p>";
        } else {
            echo "<p style='color: red;'>✗ Dosya taşıma hatası</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Yükleme hatası: " . $_FILES['test_file']['error'] . "</p>";
    }
}
?>

<h2>Test Dosya Yükleme</h2>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="test_file" required>
    <button type="submit">Test Yükle</button>
</form>

<hr>
<a href="index.php">Ana Sayfaya Dön</a>