<?php
session_start();

// Test için session kontrolünü devre dışı bırak
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test user ID
}

$error = '';
$success = '';

// Dosya yükleme işlemi
if ($_POST && isset($_FILES['file'])) {
    error_log("=== SIMPLE UPLOAD DEBUG START ===");
    error_log("POST Data: " . print_r($_POST, true));
    error_log("FILES Data: " . print_r($_FILES, true));
    
    $file = $_FILES['file'];
    
    // Temel kontroller
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Upload hatası: " . $file['error'];
        error_log("Upload error: " . $file['error']);
    } 
    else if ($file['size'] == 0) {
        $error = "Dosya boş";
        error_log("Empty file");
    }
    else if ($file['size'] > 50 * 1024 * 1024) { // 50MB
        $error = "Dosya çok büyük (max 50MB)";
        error_log("File too large: " . $file['size']);
    }
    else {
        // Upload klasörü
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Dosya adı
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newName = uniqid() . '.' . $extension;
        $fullPath = $uploadDir . $newName;
        
        error_log("Upload dir: " . $uploadDir);
        error_log("Is writable: " . (is_writable($uploadDir) ? 'YES' : 'NO'));
        error_log("Original name: " . $file['name']);
        error_log("New name: " . $newName);
        error_log("Full path: " . $fullPath);
        error_log("Temp file: " . $file['tmp_name']);
        error_log("Temp exists: " . (file_exists($file['tmp_name']) ? 'YES' : 'NO'));
        
        // Dosyayı taşı
        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            $success = "Dosya başarıyla yüklendi: " . $file['name'];
            error_log("Upload SUCCESS!");
            
            // Veritabanına kaydet (basit)
            try {
                require_once '../config/database.php';
                $pdo = getDB();
                
                $sql = "INSERT INTO files (original_name, stored_name, file_path, file_size, mime_type, uploaded_by, is_public, upload_date) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                
                $result = $stmt->execute([
                    $file['name'],
                    $newName, 
                    $fullPath,
                    $file['size'],
                    $file['type'],
                    $_SESSION['user_id'],
                    1 // public
                ]);
                
                if ($result) {
                    error_log("Database insert SUCCESS!");
                } else {
                    error_log("Database insert FAILED: " . print_r($stmt->errorInfo(), true));
                }
                
            } catch (Exception $e) {
                error_log("Database error: " . $e->getMessage());
            }
            
        } else {
            $error = "Dosya taşıma başarısız";
            error_log("move_uploaded_file FAILED");
            error_log("Last error: " . print_r(error_get_last(), true));
        }
    }
    
    error_log("=== SIMPLE UPLOAD DEBUG END ===");
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Basit Dosya Yükle</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .alert { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .upload-form { background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .file-input { margin: 10px 0; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Basit Dosya Yükleme Testi</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <strong>HATA:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>BAŞARILI:</strong> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="upload-form">
            <h3>Dosya Seç ve Yükle</h3>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="file-input">
                    <label for="file">Dosya Seçin:</label><br>
                    <input type="file" id="file" name="file" required>
                </div>
                
                <div style="margin: 15px 0;">
                    <button type="submit" class="btn">YÜKLE</button>
                </div>
            </form>
        </div>
        
        <div>
            <h3>Debug Bilgileri</h3>
            <p><strong>PHP Upload Max:</strong> <?php echo ini_get('upload_max_filesize'); ?></p>
            <p><strong>PHP Post Max:</strong> <?php echo ini_get('post_max_size'); ?></p>
            <p><strong>Upload Dir:</strong> <?php echo __DIR__ . '/../uploads/'; ?></p>
            <p><strong>Upload Dir Exists:</strong> <?php echo is_dir(__DIR__ . '/../uploads/') ? 'YES' : 'NO'; ?></p>
            <p><strong>Upload Dir Writable:</strong> <?php echo is_writable(__DIR__ . '/../uploads/') ? 'YES' : 'NO'; ?></p>
        </div>
        
        <p><a href="upload.php">← Ana Upload Sayfasına Dön</a></p>
    </div>

    <script>
        console.log('Simple upload page loaded');
        
        document.querySelector('form').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('file');
            const file = fileInput.files[0];
            
            console.log('Form submitting...');
            
            if (!file) {
                alert('Dosya seçin!');
                e.preventDefault();
                return false;
            }
            
            console.log('Selected file:', {
                name: file.name,
                size: file.size,
                type: file.type
            });
        });
    </script>
</body>
</html>