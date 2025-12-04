<?php
session_start();
require_once '../config/database.php';

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';

// Dosya yükleme işlemi
if (isset($_POST['upload']) && isset($_FILES['file'])) {
    $uploadResult = handleFileUpload($_FILES['file'], $_SESSION['user_id']);
    $message = $uploadResult['message'];
    $messageType = $uploadResult['success'] ? 'success' : 'error';
}

// Basit dosya yükleme fonksiyonu
function handleFileUpload($file, $userId) {
    // Dosya hatası kontrolü
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Dosya yükleme hatası occurred.'];
    }
    
    // Boş dosya kontrolü
    if ($file['size'] <= 0) {
        return ['success' => false, 'message' => 'Dosya boş olamaz.'];
    }
    
    // Dosya boyutu kontrolü (10MB limit)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Dosya boyutu 10MB\'dan büyük olamaz.'];
    }
    
    // Upload klasörü hazırla
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Güvenli dosya adı oluştur
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip'];
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        return ['success' => false, 'message' => 'Bu dosya türü desteklenmemektedir.'];
    }
    
    $newFileName = uniqid() . '.' . $fileExtension;
    $targetPath = $uploadDir . $newFileName;
    
    // Dosyayı taşı
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Veritabanına kaydet
        if (saveFileToDatabase($file['name'], $newFileName, $file['size'], $userId)) {
            return ['success' => true, 'message' => 'Dosya başarıyla yüklendi!'];
        } else {
            unlink($targetPath); // Hata olursa dosyayı sil
            return ['success' => false, 'message' => 'Veritabanı kayıt hatası.'];
        }
    } else {
        return ['success' => false, 'message' => 'Dosya kaydedilemedi.'];
    }
}

// Veritabanına kaydetme fonksiyonu
function saveFileToDatabase($originalName, $storedName, $fileSize, $userId) {
    try {
        $pdo = getDB();
        $sql = "INSERT INTO files (original_name, stored_name, file_size, uploaded_by, is_public, upload_date) 
                VALUES (?, ?, ?, ?, 1, NOW())";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$originalName, $storedName, $fileSize, $userId]);
    } catch (Exception $e) {
        return false;
    }
}

// Kullanıcının dosyalarını getir
function getUserFiles($userId) {
    try {
        $pdo = getDB();
        $sql = "SELECT * FROM files WHERE uploaded_by = ? ORDER BY upload_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

$userFiles = getUserFiles($_SESSION['user_id']);

// Dosya boyutu formatlama
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dosya Yükle - FileSync</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .upload-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .upload-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .file-input-container {
            margin: 15px 0;
        }
        .file-input {
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 5px;
            text-align: center;
            background: #fafafa;
        }
        .file-input input[type="file"] {
            width: 100%;
            padding: 10px;
        }
        .upload-btn {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .upload-btn:hover {
            background: #0056b3;
        }
        .message {
            padding: 12px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .files-list {
            margin-top: 30px;
        }
        .file-item {
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .file-info h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        .file-info p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        .file-actions a {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 3px;
            margin-left: 5px;
        }
        .file-actions a:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="upload-container">
        <h1>Dosya Yükle</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="upload-form">
            <h3>Yeni Dosya Yükle</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="file-input-container">
                    <div class="file-input">
                        <input type="file" name="file" required>
                    </div>
                    <p style="margin-top: 10px; color: #666; font-size: 14px;">
                        ✅ İzin verilen dosyalar: JPG, PNG, GIF, PDF, DOC, DOCX, TXT, ZIP<br>
                        📏 Maksimum boyut: 10MB
                    </p>
                </div>
                
                <button type="submit" name="upload" class="upload-btn">
                    📤 Dosyayı Yükle
                </button>
            </form>
        </div>
        
        <div class="files-list">
            <h3>Yüklediğim Dosyalar (<?php echo count($userFiles); ?>)</h3>
            
            <?php if (empty($userFiles)): ?>
                <p style="color: #666; font-style: italic;">Henüz dosya yüklememişsiniz.</p>
            <?php else: ?>
                <?php foreach ($userFiles as $file): ?>
                    <div class="file-item">
                        <div class="file-info">
                            <h4><?php echo htmlspecialchars($file['original_name']); ?></h4>
                            <p>
                                📅 <?php echo date('d.m.Y H:i', strtotime($file['upload_date'])); ?> | 
                                📏 <?php echo formatFileSize($file['file_size']); ?>
                            </p>
                        </div>
                        <div class="file-actions">
                            <a href="download.php?id=<?php echo $file['id']; ?>">📥 İndir</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>