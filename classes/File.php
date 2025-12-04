<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Basit File sınıfı - Sadece gerekli dosya işlemleri
 */
class File {
    private $pdo;
    private $uploadDir;
    
    public function __construct() {
        $this->pdo = getDB();
        $this->uploadDir = __DIR__ . '/../uploads/';
        
        // Upload klasörünü oluştur
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Dosya indirme için dosya bilgilerini getir
     */
    public function getFileById($fileId) {
        try {
            $sql = "SELECT * FROM files WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$fileId]);
            
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($file && file_exists($this->uploadDir . $file['stored_name'])) {
                return $file;
            }
            
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Dosya indirme sayısını artır
     */
    public function incrementDownloadCount($fileId) {
        try {
            $sql = "UPDATE files SET download_count = download_count + 1 WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$fileId]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Kullanıcının dosyalarını getir
     */
    public function getUserFiles($userId) {
        try {
            $sql = "SELECT * FROM files WHERE uploaded_by = ? ORDER BY upload_date DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Herkese açık dosyaları getir
     */
    public function getPublicFiles($limit = 20) {
        try {
            $sql = "SELECT f.*, u.username, u.full_name 
                    FROM files f 
                    JOIN users u ON f.uploaded_by = u.id 
                    WHERE f.is_public = 1 
                    ORDER BY f.upload_date DESC 
                    LIMIT ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Dosya sil
     */
    public function deleteFile($fileId, $userId) {
        try {
            // Kullanıcı yetkisi kontrolü
            $sql = "SELECT * FROM files WHERE id = ? AND uploaded_by = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$fileId, $userId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$file) {
                return ['success' => false, 'message' => 'Dosya bulunamadı veya silme yetkiniz yok.'];
            }
            
            // Önce grup dosya kayıtlarını sil
            $groupFileSql = "DELETE FROM group_files WHERE file_id = ?";
            $groupFileStmt = $this->pdo->prepare($groupFileSql);
            $groupFileStmt->execute([$fileId]);
            
            // Fiziksel dosyayı sil
            $filePath = $this->uploadDir . $file['stored_name'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Veritabanından sil
            $sql = "DELETE FROM files WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            
            if ($stmt->execute([$fileId])) {
                return ['success' => true, 'message' => 'Dosya başarıyla silindi.'];
            } else {
                return ['success' => false, 'message' => 'Dosya silinemedi.'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()];
        }
    }
    
    /**
     * Gruba dosya yükle
     */
    public function uploadGroupFile($file, $groupId, $userId, $message = '') {
        try {
            // Dosya hatası kontrolü
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'message' => 'Dosya yükleme hatası oluştu.'];
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
            
            // Güvenli dosya adı oluştur
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip'];
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                return ['success' => false, 'message' => 'Bu dosya türü desteklenmemektedir.'];
            }
            
            $newFileName = uniqid() . '.' . $fileExtension;
            $targetPath = $this->uploadDir . $newFileName;
            
            // Dosyayı taşı
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Veritabanına kaydet (grup dosyası olarak is_public = 0)
                $sql = "INSERT INTO files (original_name, stored_name, file_path, file_size, uploaded_by, is_public, upload_date, mime_type)
                        VALUES (?, ?, ?, ?, ?, 0, NOW(), ?)";
                $stmt = $this->pdo->prepare($sql);
                
                $mimeType = mime_content_type($targetPath);
                $filePath = 'uploads/' . $newFileName; // Relative path
                
                if ($stmt->execute([$file['name'], $newFileName, $filePath, $file['size'], $userId, $mimeType])) {
                    $fileId = $this->pdo->lastInsertId();
                    
                    // group_files tablosuna kaydet
                    $groupSql = "INSERT INTO group_files (group_id, file_id, shared_by, message, shared_at)
                                VALUES (?, ?, ?, ?, NOW())";
                    $groupStmt = $this->pdo->prepare($groupSql);
                    
                    if ($groupStmt->execute([$groupId, $fileId, $userId, $message])) {
                        return ['success' => true, 'message' => 'Dosya başarıyla gruba yüklendi!', 'file_id' => $fileId];
                    } else {
                        // Hata durumunda dosyayı sil
                        unlink($targetPath);
                        $this->pdo->prepare("DELETE FROM files WHERE id = ?")->execute([$fileId]);
                        return ['success' => false, 'message' => 'Grup dosya kaydı oluşturulamadı.'];
                    }
                } else {
                    unlink($targetPath); // Hata olursa dosyayı sil
                    return ['success' => false, 'message' => 'Veritabanı kayıt hatası.'];
                }
            } else {
                return ['success' => false, 'message' => 'Dosya kaydedilemedi.'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()];
        }
    }
    
    /**
     * Kullanıcının dosyaya erişim yetkisi kontrolü
     */
    public function canUserAccessFile($fileId, $userId) {
        try {
            // Önce dosya bilgilerini al
            $sql = "SELECT * FROM files WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$fileId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$file) {
                return false;
            }
            
            // Public dosya ise herkes erişebilir
            if ($file['is_public'] == 1) {
                return true;
            }
            
            // Dosya sahibi ise erişebilir
            if ($file['uploaded_by'] == $userId) {
                return true;
            }
            
            // Grup dosyası ise grup üyeliği kontrolü
            $groupSql = "SELECT gf.group_id FROM group_files gf
                        JOIN group_members gm ON gf.group_id = gm.group_id
                        WHERE gf.file_id = ? AND gm.user_id = ?";
            $groupStmt = $this->pdo->prepare($groupSql);
            $groupStmt->execute([$fileId, $userId]);
            
            return $groupStmt->fetch() ? true : false;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Grup dosyası bilgilerini getir (yetki kontrolü ile)
     */
    public function getGroupFileById($fileId, $userId) {
        try {
            if (!$this->canUserAccessFile($fileId, $userId)) {
                return null;
            }
            
            return $this->getFileById($fileId);
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Grup dosyasını sil (yetki kontrolü ile)
     */
    public function deleteGroupFile($fileId, $userId, $groupId) {
        try {
            error_log("deleteGroupFile başlatıldı - FileID: $fileId, UserID: $userId, GroupID: $groupId");
            
            // Dosya bilgilerini al
            $sql = "SELECT * FROM files WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$fileId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$file) {
                error_log("Dosya bulunamadı - FileID: $fileId");
                return ['success' => false, 'message' => 'Dosya bulunamadı.'];
            }
            
            error_log("Dosya bulundu: " . json_encode($file));
            
            // Yetki kontrolü: Dosya sahibi veya grup sahibi silebilir
            $canDelete = false;
            
            // 1. Dosya sahibi mi?
            if ($file['uploaded_by'] == $userId) {
                $canDelete = true;
                error_log("Kullanıcı dosya sahibi - silme yetkisi var");
            } else {
                // 2. Grup sahibi mi?
                require_once __DIR__ . '/Group.php';
                $groupHandler = new Group();
                if ($groupHandler->isOwner($groupId, $userId)) {
                    $canDelete = true;
                    error_log("Kullanıcı grup sahibi - silme yetkisi var");
                } else {
                    error_log("Kullanıcı ne dosya sahibi ne de grup sahibi");
                }
            }
            
            if (!$canDelete) {
                error_log("Silme yetkisi yok");
                return ['success' => false, 'message' => 'Bu dosyayı silme yetkiniz yok.'];
            }
            
            // Grup dosya kaydını sil
            $groupFileSql = "DELETE FROM group_files WHERE file_id = ? AND group_id = ?";
            $groupFileStmt = $this->pdo->prepare($groupFileSql);
            $deleteResult = $groupFileStmt->execute([$fileId, $groupId]);
            
            error_log("Group_files silme sonucu: " . ($deleteResult ? "başarılı" : "başarısız"));
            error_log("Silinen satır sayısı: " . $groupFileStmt->rowCount());
            
            // Dosyanın başka gruplarda paylaşılıp paylaşılmadığını kontrol et
            $checkSql = "SELECT COUNT(*) as count FROM group_files WHERE file_id = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$fileId]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Dosyanın başka gruplardaki paylaşım sayısı: " . $result['count']);
            error_log("Dosya public mi? " . ($file['is_public'] ? "evet" : "hayır"));
            
            // Eğer dosya başka hiçbir grupta paylaşılmıyorsa ve public değilse, dosyayı tamamen sil
            if ($result['count'] == 0 && $file['is_public'] == 0) {
                error_log("Dosya tamamen silinecek");
                
                // Fiziksel dosyayı sil
                $filePath = $this->uploadDir . $file['stored_name'];
                error_log("Fiziksel dosya yolu: $filePath");
                
                if (file_exists($filePath)) {
                    $unlinkResult = unlink($filePath);
                    error_log("Fiziksel dosya silme sonucu: " . ($unlinkResult ? "başarılı" : "başarısız"));
                } else {
                    error_log("Fiziksel dosya bulunamadı");
                }
                
                // Veritabanından sil
                $deleteSql = "DELETE FROM files WHERE id = ?";
                $deleteStmt = $this->pdo->prepare($deleteSql);
                $dbDeleteResult = $deleteStmt->execute([$fileId]);
                
                error_log("Veritabanından silme sonucu: " . ($dbDeleteResult ? "başarılı" : "başarısız"));
                error_log("Silinen dosya kayıt sayısı: " . $deleteStmt->rowCount());
                
                return ['success' => true, 'message' => 'Dosya tamamen silindi.'];
            } else {
                error_log("Dosya sadece gruptan kaldırıldı");
                return ['success' => true, 'message' => 'Dosya gruptan kaldırıldı.'];
            }
            
        } catch (Exception $e) {
            error_log("deleteGroupFile hatası: " . $e->getMessage());
            return ['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()];
        }
    }
    
    /**
     * Kullanıcının dosyayı silme yetkisi kontrolü
     */
    public function canUserDeleteGroupFile($fileId, $userId, $groupId) {
        try {
            // Dosya bilgilerini al
            $sql = "SELECT uploaded_by FROM files WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$fileId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$file) {
                return false;
            }
            
            // Dosya sahibi silebilir
            if ($file['uploaded_by'] == $userId) {
                return true;
            }
            
            // Grup sahibi silebilir
            require_once __DIR__ . '/Group.php';
            $groupHandler = new Group();
            if ($groupHandler->isOwner($groupId, $userId)) {
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            return false;
        }
    }
}
?>