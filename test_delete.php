<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Group.php';
require_once 'classes/File.php';

// Basit test sayfası
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test için
}

$groupHandler = new Group();
$fileHandler = new File();

echo "<h1>Dosya Silme Test Sayfası</h1>";

// Tüm grup dosyalarını listele
echo "<h2>Mevcut Grup Dosyaları:</h2>";
try {
    $pdo = getDB();
    $sql = "SELECT gf.*, f.original_name, f.stored_name, f.uploaded_by, f.is_public, g.group_name, g.owner_id
            FROM group_files gf 
            JOIN files f ON gf.file_id = f.id 
            JOIN groups g ON gf.group_id = g.id 
            ORDER BY gf.shared_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $groupFiles = $stmt->fetchAll();
    
    if (empty($groupFiles)) {
        echo "<p>Hiç grup dosyası bulunamadı.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Dosya Adı</th><th>Grup</th><th>Yükleyen</th><th>Public</th><th>İşlem</th></tr>";
        
        foreach ($groupFiles as $file) {
            echo "<tr>";
            echo "<td>" . $file['file_id'] . "</td>";
            echo "<td>" . htmlspecialchars($file['original_name']) . "</td>";
            echo "<td>" . htmlspecialchars($file['group_name']) . "</td>";
            echo "<td>" . $file['uploaded_by'] . "</td>";
            echo "<td>" . ($file['is_public'] ? 'Evet' : 'Hayır') . "</td>";
            echo "<td>";
            echo "<a href='?delete_file=" . $file['file_id'] . "&group_id=" . $file['group_id'] . "' ";
            echo "onclick=\"return confirm('Dosyayı silmek istediğinizden emin misiniz?')\" ";
            echo "style='background: red; color: white; padding: 5px; text-decoration: none;'>SİL</a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}

// Silme işlemi
if (isset($_GET['delete_file']) && isset($_GET['group_id'])) {
    $fileId = (int)$_GET['delete_file'];
    $groupId = (int)$_GET['group_id'];
    $userId = $_SESSION['user_id'];
    
    echo "<h2>Silme İşlemi Sonucu:</h2>";
    
    $result = $groupHandler->deleteGroupFile($fileId, $groupId, $userId);
    
    echo "<div style='padding: 10px; border: 1px solid; margin: 10px 0;'>";
    if ($result['success']) {
        echo "<strong style='color: green;'>BAŞARILI:</strong> " . $result['message'];
    } else {
        echo "<strong style='color: red;'>HATA:</strong> " . $result['message'];
    }
    echo "</div>";
    
    echo "<a href='test_delete.php'>Sayfayı Yenile</a>";
}

echo "<br><br><a href='debug_delete.php'>Debug Sayfasına Git</a>";
?>