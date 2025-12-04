<?php
session_start();
require_once '../config/database.php';
require_once '../classes/File.php';
require_once '../classes/Group.php';

// File ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(404);
    die('Dosya bulunamadı.');
}

$fileId = (int)$_GET['id'];
$fileHandler = new File();

// Dosya bilgilerini getir
$file = $fileHandler->getFileById($fileId);

if (!$file) {
    http_response_code(404);
    die('Dosya bulunamadı.');
}

// Erişim kontrolü
if ($file['is_public'] == 0) {
    // Grup dosyası - giriş kontrolü ve yetki kontrolü gerekli
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        die('Bu dosyaya erişmek için giriş yapmalısınız.');
    }
    
    // Dosya sahibi mi kontrol et
    if ($file['uploaded_by'] != $_SESSION['user_id']) {
        // Grup üyeliği kontrolü
        if (!$fileHandler->canUserAccessFile($fileId, $_SESSION['user_id'])) {
            http_response_code(403);
            die('Bu dosyaya erişim yetkiniz yok.');
        }
    }
}

// Dosya yolunu hazırla
$filePath = __DIR__ . '/../uploads/' . $file['stored_name'];

if (!file_exists($filePath)) {
    http_response_code(404);
    die('Dosya sistemde bulunamadı.');
}

// İndirme sayısını artır
$fileHandler->incrementDownloadCount($fileId);

// Dosyayı indir
$filename = $file['original_name'];
$filesize = filesize($filePath);
$mime = mime_content_type($filePath);

// Header ayarları
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $filesize);
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

// Büyük dosyalar için chunk'lar halinde oku
$handle = fopen($filePath, 'rb');
if ($handle) {
    while (!feof($handle)) {
        echo fread($handle, 8192);
        flush();
    }
    fclose($handle);
}
exit;
?>