<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Group.php';
require_once 'classes/File.php';

// Test için sabit değerler
$groupId = 1; // Test grup ID'si
$fileId = 1;  // Test dosya ID'si
$userId = 1;  // Test kullanıcı ID'si

echo "<h1>Debug: Dosya Silme Testi</h1>";

try {
    $groupHandler = new Group();
    $fileHandler = new File();
    
    echo "<h2>1. Dosya Bilgileri:</h2>";
    $file = $fileHandler->getFileById($fileId);
    if ($file) {
        echo "<pre>";
        print_r($file);
        echo "</pre>";
    } else {
        echo "Dosya bulunamadı!<br>";
    }
    
    echo "<h2>2. Grup Bilgileri:</h2>";
    $group = $groupHandler->getGroupInfo($groupId);
    if ($group) {
        echo "<pre>";
        print_r($group);
        echo "</pre>";
    } else {
        echo "Grup bulunamadı!<br>";
    }
    
    echo "<h2>3. Üyelik Kontrolü:</h2>";
    $isMember = $groupHandler->isMember($groupId, $userId);
    echo "Kullanıcı grup üyesi mi? " . ($isMember ? "EVET" : "HAYIR") . "<br>";
    
    echo "<h2>4. Silme Yetkisi Kontrolü:</h2>";
    $canDelete = $fileHandler->canUserDeleteGroupFile($fileId, $userId, $groupId);
    echo "Kullanıcı dosyayı silebilir mi? " . ($canDelete ? "EVET" : "HAYIR") . "<br>";
    
    echo "<h2>5. Grup Dosyaları:</h2>";
    $groupFiles = $groupHandler->getGroupFiles($groupId);
    echo "<pre>";
    print_r($groupFiles);
    echo "</pre>";
    
    echo "<h2>6. Silme İşlemi Testi:</h2>";
    if (isset($_GET['delete']) && $_GET['delete'] == '1') {
        $result = $groupHandler->deleteGroupFile($fileId, $groupId, $userId);
        echo "<strong>Silme Sonucu:</strong><br>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    } else {
        echo '<a href="?delete=1" style="background: red; color: white; padding: 10px; text-decoration: none;">DOSYAYI SİL (TEST)</a>';
    }
    
} catch (Exception $e) {
    echo "<h2>HATA:</h2>";
    echo $e->getMessage();
}
?>