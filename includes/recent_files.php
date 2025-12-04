<?php
// Son yüklenen dosyaları göster
try {
    $pdo = getDB();
    
    // Giriş yapan kullanıcı varsa hem kendi dosyalarını hem de herkese açık dosyaları göster
    if (isset($_SESSION['user_id'])) {
        $sql = "SELECT f.*, u.username, u.full_name,
                CASE
                    WHEN f.uploaded_by = ? THEN 'own'
                    ELSE 'public'
                END as file_type
                FROM files f
                JOIN users u ON f.uploaded_by = u.id
                WHERE f.is_public = 1 OR f.uploaded_by = ?
                ORDER BY f.upload_date DESC
                LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    } else {
        // Giriş yapmayan kullanıcılar sadece herkese açık dosyaları görsün
        $sql = "SELECT f.*, u.username, u.full_name, 'public' as file_type
                FROM files f
                JOIN users u ON f.uploaded_by = u.id
                WHERE f.is_public = 1
                ORDER BY f.upload_date DESC
                LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    
    $recent_files = $stmt->fetchAll();
    
    if ($recent_files): ?>
        <div class="files-list">
            <?php foreach ($recent_files as $file): ?>
                <div class="file-item">
                    <div class="file-info">
                        <h4><?php echo htmlspecialchars($file['original_name']); ?>
                        <?php if ($file['file_type'] === 'own' && $file['is_public'] == 0): ?>
                            <span style="background-color: #6c757d; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8em;">Gizli</span>
                        <?php elseif ($file['file_type'] === 'own'): ?>
                            <span style="background-color: #28a745; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8em;">Benim</span>
                        <?php endif; ?>
                        </h4>
                        <p>Yükleyen: <?php echo htmlspecialchars($file['full_name']); ?></p>
                        <p>Tarih: <?php echo date('d.m.Y H:i', strtotime($file['upload_date'])); ?></p>
                        <p>Boyut: <?php echo formatFileSize($file['file_size']); ?></p>
                        <p>İndirme: <?php echo $file['download_count']; ?> kez</p>
                        <p>Durum: <?php echo $file['is_public'] ? 'Herkese açık' : 'Gizli'; ?></p>
                    </div>
                    <div class="file-actions">
                        <a href="pages/download.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-primary">İndir</a>
                        <?php if ($file['file_type'] === 'own' && $file['is_public']): ?>
                            <button class="btn btn-sm btn-secondary" onclick="copyToClipboard('<?php echo getCurrentDomain(); ?>/pages/download.php?id=<?php echo $file['id']; ?>')">
                                Link Kopyala
                            </button>
                        <?php endif; ?>
                        <?php if ($file['file_type'] === 'own'): ?>
                            <button type="button" class="btn btn-sm btn-danger delete-file-btn"
                                    data-file-id="<?php echo $file['id']; ?>"
                                    data-file-name="<?php echo htmlspecialchars($file['original_name']); ?>"
                                    onclick="confirmDeleteMainFile(this)">
                                🗑️ Sil
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Henüz dosya yüklenmemiş.</p>
    <?php endif;
    
} catch (PDOException $e) {
    echo '<p>Dosyalar yüklenirken hata oluştu.</p>';
}

// Dosya boyutunu formatlamak için yardımcı fonksiyon
function formatFileSize($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Domain yardımcı fonksiyonu
function getCurrentDomain() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'];
}
?>