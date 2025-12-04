<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Group sınıfı - Grup işlemlerini yönetir
 */
class Group {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * Yeni grup oluştur
     */
    public function createGroup($groupName, $description, $ownerId) {
        try {
            // Grup adı kontrolü
            if (empty(trim($groupName))) {
                return ['success' => false, 'message' => 'Grup adı boş olamaz.'];
            }
            
            // Aynı kullanıcının aynı isimde grup oluşturmasını engelle
            $checkSql = "SELECT id FROM groups WHERE group_name = ? AND owner_id = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$groupName, $ownerId]);
            
            if ($checkStmt->fetch()) {
                return ['success' => false, 'message' => 'Bu isimde bir grubunuz zaten var.'];
            }
            
            // Grubu oluştur
            $sql = "INSERT INTO groups (group_name, description, owner_id) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            
            if ($stmt->execute([$groupName, $description, $ownerId])) {
                $groupId = $this->pdo->lastInsertId();
                
                // Grup sahibini üye olarak ekle
                $this->addMember($groupId, $ownerId);
                
                return [
                    'success' => true, 
                    'message' => 'Grup başarıyla oluşturuldu!',
                    'group_id' => $groupId
                ];
            } else {
                return ['success' => false, 'message' => 'Grup oluşturulamadı.'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }
    
    /**
     * Gruba üye ekle
     */
    public function addMember($groupId, $userId) {
        try {
            // Zaten üye mi kontrol et
            $checkSql = "SELECT id FROM group_members WHERE group_id = ? AND user_id = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$groupId, $userId]);
            
            if ($checkStmt->fetch()) {
                return ['success' => false, 'message' => 'Kullanıcı zaten grubun üyesi.'];
            }
            
            // Üye ekle
            $sql = "INSERT INTO group_members (group_id, user_id) VALUES (?, ?)";
            $stmt = $this->pdo->prepare($sql);
            
            if ($stmt->execute([$groupId, $userId])) {
                return ['success' => true, 'message' => 'Üye gruba eklendi.'];
            } else {
                return ['success' => false, 'message' => 'Üye eklenemedi.'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }
    
    /**
     * Gruptan üye çıkar
     */
    public function removeMember($groupId, $userId, $requestingUserId) {
        try {
            // Grup sahibi kontrolü
            $group = $this->getGroupInfo($groupId);
            if (!$group || $group['owner_id'] != $requestingUserId) {
                return ['success' => false, 'message' => 'Bu işlem için yetkiniz yok.'];
            }
            
            // Grup sahibini çıkaramaz
            if ($userId == $group['owner_id']) {
                return ['success' => false, 'message' => 'Grup sahibi gruptan çıkarılamaz.'];
            }
            
            $sql = "DELETE FROM group_members WHERE group_id = ? AND user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            
            if ($stmt->execute([$groupId, $userId])) {
                return ['success' => true, 'message' => 'Üye gruptan çıkarıldı.'];
            } else {
                return ['success' => false, 'message' => 'Üye çıkarılamadı.'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }
    
    /**
     * Kullanıcının gruplarını getir
     */
    public function getUserGroups($userId) {
        try {
            $sql = "SELECT g.*, 
                           (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) as member_count,
                           (SELECT COUNT(*) FROM group_files gf WHERE gf.group_id = g.id) as file_count
                    FROM groups g 
                    JOIN group_members gm ON g.id = gm.group_id 
                    WHERE gm.user_id = ? 
                    ORDER BY g.created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Grup bilgilerini getir
     */
    public function getGroupInfo($groupId) {
        try {
            $sql = "SELECT g.*, u.username as owner_username, u.full_name as owner_name
                    FROM groups g 
                    JOIN users u ON g.owner_id = u.id 
                    WHERE g.id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$groupId]);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Grup üyelerini getir
     */
    public function getGroupMembers($groupId) {
        try {
            $sql = "SELECT u.id, u.username, u.full_name, gm.joined_at,
                           (u.id = g.owner_id) as is_owner
                    FROM group_members gm 
                    JOIN users u ON gm.user_id = u.id 
                    JOIN groups g ON gm.group_id = g.id
                    WHERE gm.group_id = ? 
                    ORDER BY is_owner DESC, gm.joined_at ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$groupId]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Kullanıcının grup üyeliğini kontrol et
     */
    public function isMember($groupId, $userId) {
        try {
            $sql = "SELECT id FROM group_members WHERE group_id = ? AND user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$groupId, $userId]);
            
            return $stmt->fetch() ? true : false;
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Grup sahibi kontrolü
     */
    public function isOwner($groupId, $userId) {
        try {
            $sql = "SELECT id FROM groups WHERE id = ? AND owner_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$groupId, $userId]);
            
            return $stmt->fetch() ? true : false;
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Grup sil
     */
    public function deleteGroup($groupId, $userId) {
        try {
            // Grup sahibi kontrolü
            if (!$this->isOwner($groupId, $userId)) {
                return ['success' => false, 'message' => 'Bu işlem için yetkiniz yok.'];
            }
            
            // Grup dosyalarını sil
            $this->deleteGroupFiles($groupId);
            
            // Grubu sil (cascade ile üyelikler de silinir)
            $sql = "DELETE FROM groups WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            
            if ($stmt->execute([$groupId])) {
                return ['success' => true, 'message' => 'Grup silindi.'];
            } else {
                return ['success' => false, 'message' => 'Grup silinemedi.'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }
    
    /**
     * Grup dosyalarını sil (yardımcı fonksiyon)
     */
    private function deleteGroupFiles($groupId) {
        try {
            $sql = "DELETE FROM group_files WHERE group_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$groupId]);
        } catch (PDOException $e) {
            // Hata durumunda sessizce devam et
        }
    }
    
    /**
     * Gruba dosya paylaş
     */
    public function shareFileToGroup($groupId, $fileId, $userId, $message = '') {
        try {
            // Grup üyeliği kontrolü
            if (!$this->isMember($groupId, $userId)) {
                return ['success' => false, 'message' => 'Bu grubun üyesi değilsiniz.'];
            }
            
            // Dosya zaten paylaşılmış mı kontrol et
            $checkSql = "SELECT id FROM group_files WHERE group_id = ? AND file_id = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$groupId, $fileId]);
            
            if ($checkStmt->fetch()) {
                return ['success' => false, 'message' => 'Bu dosya zaten grupta paylaşılmış.'];
            }
            
            // Dosyayı gruba paylaş
            $sql = "INSERT INTO group_files (group_id, file_id, shared_by, message) VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            
            if ($stmt->execute([$groupId, $fileId, $userId, $message])) {
                return ['success' => true, 'message' => 'Dosya grupta paylaşıldı!'];
            } else {
                return ['success' => false, 'message' => 'Dosya paylaşılamadı.'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }
    
    /**
     * Gruba dosya yükle (File sınıfı ile entegrasyon)
     */
    public function uploadFileToGroup($file, $groupId, $userId, $message = '') {
        try {
            // Grup üyeliği kontrolü
            if (!$this->isMember($groupId, $userId)) {
                return ['success' => false, 'message' => 'Bu grubun üyesi değilsiniz.'];
            }
            
            // File sınıfını kullanarak dosyayı yükle
            require_once __DIR__ . '/File.php';
            $fileHandler = new File();
            
            $result = $fileHandler->uploadGroupFile($file, $groupId, $userId, $message);
            
            return $result;
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()];
        }
    }
    
    /**
     * Kullanıcının grup dosyasına erişim yetkisi kontrolü
     */
    public function canUserAccessGroupFile($fileId, $userId) {
        try {
            // Dosyanın hangi gruplarda paylaşıldığını bul
            $sql = "SELECT DISTINCT gf.group_id FROM group_files gf WHERE gf.file_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$fileId]);
            $groups = $stmt->fetchAll();
            
            // Her grup için üyelik kontrolü yap
            foreach ($groups as $group) {
                if ($this->isMember($group['group_id'], $userId)) {
                    return true;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Grup dosyalarını getir (güncellenmiş versiyon)
     */
    public function getGroupFiles($groupId) {
        try {
            $sql = "SELECT gf.*, f.original_name, f.file_size, f.mime_type, f.download_count,
                           u.username as shared_by_username, u.full_name as shared_by_name,
                           f.uploaded_by, f.upload_date,
                           CASE
                               WHEN f.uploaded_by = gf.shared_by THEN 'uploaded'
                               ELSE 'shared'
                           END as source_type
                    FROM group_files gf
                    JOIN files f ON gf.file_id = f.id
                    JOIN users u ON gf.shared_by = u.id
                    WHERE gf.group_id = ?
                    ORDER BY gf.shared_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$groupId]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Grup dosyasını sil (File sınıfı ile entegrasyon)
     */
    public function deleteGroupFile($fileId, $groupId, $userId) {
        try {
            // Grup üyeliği kontrolü
            if (!$this->isMember($groupId, $userId)) {
                return ['success' => false, 'message' => 'Bu grubun üyesi değilsiniz.'];
            }
            
            // File sınıfını kullanarak dosyayı sil
            require_once __DIR__ . '/File.php';
            $fileHandler = new File();
            
            // Silme yetkisi kontrolü
            if (!$fileHandler->canUserDeleteGroupFile($fileId, $userId, $groupId)) {
                return ['success' => false, 'message' => 'Bu dosyayı silme yetkiniz yok. Sadece dosya sahibi veya grup sahibi silebilir.'];
            }
            
            $result = $fileHandler->deleteGroupFile($fileId, $userId, $groupId);
            
            return $result;
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()];
        }
    }
    
    /**
     * Kullanıcının grup dosyasını silme yetkisi kontrolü
     */
    public function canUserDeleteGroupFile($fileId, $groupId, $userId) {
        try {
            // Grup üyeliği kontrolü
            if (!$this->isMember($groupId, $userId)) {
                return false;
            }
            
            // File sınıfından yetki kontrolü
            require_once __DIR__ . '/File.php';
            $fileHandler = new File();
            
            return $fileHandler->canUserDeleteGroupFile($fileId, $userId, $groupId);
            
        } catch (Exception $e) {
            return false;
        }
    }
}
?>