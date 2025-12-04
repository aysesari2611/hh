<?php
session_start();
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/File.php';

// Kullanıcı giriş yapmış mı kontrol et
$user = null;
if (isset($_SESSION['user_id'])) {
    $userObj = new User();
    $user = $userObj->getUserById($_SESSION['user_id']);
}

$error = '';
$success = '';

// Ana sayfa dosya silme işlemi
if ($_POST && isset($_POST['delete_main_file']) && isset($_SESSION['user_id'])) {
    $fileId = (int)$_POST['file_id'];
    $fileHandler = new File();
    
    $result = $fileHandler->deleteFile($fileId, $_SESSION['user_id']);
    
    if ($result['success']) {
        $success = $result['message'];
        // Başarılı silme sonrası sayfayı yenile (POST-redirect-GET pattern)
        header("Location: index.php?deleted=1");
        exit;
    } else {
        $error = $result['message'];
    }
}

// URL'den silme başarı mesajını al
if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $success = 'Dosya başarıyla silindi.';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FileSync - Modern Bulut Depolama</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php if ($user): ?>
            <!-- Giriş yapmış kullanıcı için ana sayfa -->
            <div class="welcome-section">
                <h1>Merhaba, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
                <p>Dosyalarınızı kolayca yönetin ve paylaşın</p>
            </div>
            
            <div class="main-actions">
                <div class="action-card">
                    <div class="card-icon">📤</div>
                    <h3>Dosya Yükle</h3>
                    <p>Dosyalarınızı hızlı ve güvenli şekilde yükleyin</p>
                    <a href="pages/upload.php" class="btn btn-primary">Yükle</a>
                </div>
                
                <div class="action-card">
                    <div class="card-icon">👥</div>
                    <h3>Takımlar</h3>
                    <p>Ekibinizle kolaborasyon yapın</p>
                    <a href="pages/groups.php" class="btn btn-secondary">Takımlarım</a>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <div class="recent-files">
                <h2>Son Dosyalar</h2>
                <?php include 'includes/recent_files.php'; ?>
            </div>
            
        <?php else: ?>
            <!-- Giriş yapmamış kullanıcı için ana sayfa -->
            <div class="hero-section">
                <h1>FileSync</h1>
                <p class="hero-subtitle">Modern, güvenli ve kolay kullanımlı bulut depolama çözümü</p>
                <p>Dosyalarınızı her yerden erişilebilir şekilde saklayın, organize edin ve paylaşın.</p>
                <div class="hero-actions">
                    <a href="pages/login.php" class="btn btn-primary">Giriş Yap</a>
                    <a href="pages/register.php" class="btn btn-secondary">Ücretsiz Başla</a>
                </div>
            </div>
            
            <div class="features">
                <div class="feature">
                    <div class="feature-icon">🔒</div>
                    <h3>Güvenli Saklama</h3>
                    <p>Dosyalarınız 256-bit şifreleme ile korunur ve güvenli sunucularda saklanır</p>
                </div>
                
                <div class="feature">
                    <div class="feature-icon">🚀</div>
                    <h3>Hızlı Senkronizasyon</h3>
                    <p>Dosyalarınız tüm cihazlarınızda anında senkronize edilir</p>
                </div>
                
                <div class="feature">
                    <div class="feature-icon">🤝</div>
                    <h3>Kolay Paylaşım</h3>
                    <p>Takımınızla kolayca işbirliği yapın ve dosyalarınızı güvenle paylaşın</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Link panoya kopyalandı!');
            }).catch(function(err) {
                console.error('Kopyalama hatası: ', err);
            });
        }
        
        // Ana sayfa dosya silme onayı
        function confirmDeleteMainFile(button) {
            const fileId = button.getAttribute('data-file-id');
            const fileName = button.getAttribute('data-file-name');
            
            if (confirm(`"${fileName}" dosyasını silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz ve dosya tüm gruplardan da kaldırılacaktır.`)) {
                // Form oluştur ve submit et
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const fileIdInput = document.createElement('input');
                fileIdInput.type = 'hidden';
                fileIdInput.name = 'file_id';
                fileIdInput.value = fileId;
                
                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_main_file';
                deleteInput.value = '1';
                
                form.appendChild(fileIdInput);
                form.appendChild(deleteInput);
                document.body.appendChild(form);
                
                // Butonu deaktif et
                button.disabled = true;
                button.innerHTML = '⏳ Siliniyor...';
                
                form.submit();
            }
        }
    </script>
</body>
</html>