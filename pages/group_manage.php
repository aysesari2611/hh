<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Group.php';

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: groups.php');
    exit;
}

$groupId = (int)$_GET['id'];
$groupHandler = new Group();
$userHandler = new User();

// Grup bilgilerini getir
$group = $groupHandler->getGroupInfo($groupId);
if (!$group) {
    header('Location: groups.php');
    exit;
}

// Grup sahibi kontrolü
if ($group['owner_id'] != $_SESSION['user_id']) {
    echo '<h1>Erişim Engellendi</h1>';
    echo '<p>Bu grubu yönetme yetkiniz yok.</p>';
    echo '<a href="groups.php">Gruplarıma dön</a>';
    exit;
}

$error = '';
$success = '';

// Üye ekleme işlemi
if ($_POST && isset($_POST['add_member'])) {
    $userId = (int)$_POST['user_id'];
    
    $result = $groupHandler->addMember($groupId, $userId);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Üye çıkarma işlemi
if (isset($_GET['remove_member']) && is_numeric($_GET['remove_member'])) {
    $userId = (int)$_GET['remove_member'];
    
    $result = $groupHandler->removeMember($groupId, $userId, $_SESSION['user_id']);
    
    if ($result['success']) {
        header('Location: group_manage.php?id=' . $groupId . '&msg=member_removed');
    } else {
        header('Location: group_manage.php?id=' . $groupId . '&error=' . urlencode($result['message']));
    }
    exit;
}

// Grup silme işlemi
if (isset($_POST['delete_group'])) {
    $confirmDelete = trim($_POST['confirm_delete'] ?? '');
    
    if ($confirmDelete === $group['group_name']) {
        $result = $groupHandler->deleteGroup($groupId, $_SESSION['user_id']);
        
        if ($result['success']) {
            header('Location: groups.php?msg=group_deleted');
            exit;
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Grup adını doğru yazmalısınız.';
    }
}

// Grup üyelerini getir
$members = $groupHandler->getGroupMembers($groupId);

// URL parametrelerinden mesajları al
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'member_removed':
            $success = 'Üye gruptan çıkarıldı.';
            break;
    }
}

if (isset($_GET['error'])) {
    $error = $_GET['error'];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grup Yönet: <?php echo htmlspecialchars($group['group_name']); ?> - Dosya Paylaşım Sitesi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="group-manage-header">
            <h1><?php echo htmlspecialchars($group['group_name']); ?> - Yönetim</h1>
            <div class="manage-actions">
                <a href="group_detail.php?id=<?php echo $groupId; ?>" class="btn btn-secondary">Gruba Dön</a>
                <a href="groups.php" class="btn btn-secondary">Gruplarıma Dön</a>
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
        
        <div class="manage-sections">
            <!-- Grup Bilgileri -->
            <div class="section">
                <h3>Grup Bilgileri</h3>
                <div class="group-info-card">
                    <p><strong>Grup Adı:</strong> <?php echo htmlspecialchars($group['group_name']); ?></p>
                    <p><strong>Açıklama:</strong> <?php echo htmlspecialchars($group['description'] ?: 'Açıklama yok'); ?></p>
                    <p><strong>Oluşturulma:</strong> <?php echo date('d.m.Y H:i', strtotime($group['created_at'])); ?></p>
                    <p><strong>Üye Sayısı:</strong> <?php echo count($members); ?></p>
                </div>
            </div>
            
            <!-- Üye Yönetimi -->
            <div class="section">
                <h3>Üye Yönetimi</h3>
                
                <!-- Üye Ekleme -->
                <div class="add-member-section">
                    <h4>Yeni Üye Ekle</h4>
                    <div class="member-search">
                        <input type="text" id="member-search" placeholder="Kullanıcı adı veya ad soyad ile ara..." />
                        <div id="search-results"></div>
                    </div>
                </div>
                
                <!-- Mevcut Üyeler -->
                <div class="current-members">
                    <h4>Mevcut Üyeler</h4>
                    <div class="members-table">
                        <?php foreach ($members as $member): ?>
                            <div class="member-row">
                                <div class="member-info">
                                    <strong><?php echo htmlspecialchars($member['full_name']); ?></strong>
                                    <span class="username">(@<?php echo htmlspecialchars($member['username']); ?>)</span>
                                    <?php if ($member['is_owner']): ?>
                                        <span class="owner-badge">Sahip</span>
                                    <?php endif; ?>
                                </div>
                                <div class="member-actions">
                                    <span class="join-date">Katılma: <?php echo date('d.m.Y', strtotime($member['joined_at'])); ?></span>
                                    <?php if (!$member['is_owner']): ?>
                                        <a href="?id=<?php echo $groupId; ?>&remove_member=<?php echo $member['id']; ?>" 
                                           class="btn btn-sm remove-btn"
                                           onclick="return confirm('<?php echo htmlspecialchars($member['full_name']); ?> adlı kullanıcıyı gruptan çıkarmak istediğinizden emin misiniz?')">
                                            Çıkar
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Tehlikeli İşlemler -->
            <div class="section danger-section">
                <h3>⚠️ Tehlikeli Alan</h3>
                <div class="danger-content">
                    <h4>Grubu Sil</h4>
                    <p>Bu işlem geri alınamaz. Grup silindiğinde tüm üyelikler ve grup dosyaları da silinir.</p>
                    
                    <button class="btn danger-btn" onclick="toggleDeleteForm()">Grubu Sil</button>
                    
                    <div id="delete-form" class="delete-form" style="display: none;">
                        <form method="POST">
                            <div class="form-group">
                                <label>Silmek için grup adını yazın: <strong><?php echo htmlspecialchars($group['group_name']); ?></strong></label>
                                <input type="text" name="confirm_delete" required placeholder="Grup adını buraya yazın">
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="delete_group" class="btn danger-btn">Grubu Sil</button>
                                <button type="button" class="btn btn-secondary" onclick="toggleDeleteForm()">İptal</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/main.js"></script>
    <script>
        function toggleDeleteForm() {
            const form = document.getElementById('delete-form');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
                form.querySelector('input[name="confirm_delete"]').value = '';
            }
        }
        
        // Kullanıcı arama için gerçek AJAX implementasyonu
        let searchTimeout;
        document.getElementById('member-search').addEventListener('input', function() {
            const searchTerm = this.value.trim();
            const resultsDiv = document.getElementById('search-results');
            
            // Önceki timeout'u temizle
            clearTimeout(searchTimeout);
            
            if (searchTerm.length >= 2) {
                // Arama yaparken loading göster
                resultsDiv.innerHTML = '<div class="search-loading">🔍 Aranıyor...</div>';
                
                // 300ms gecikme ile arama yap (çok fazla istek önlemek için)
                searchTimeout = setTimeout(() => {
                    fetch(`../api/search_users.php?q=${encodeURIComponent(searchTerm)}`)
                        .then(response => response.json())
                        .then(users => {
                            if (users.length > 0) {
                                let html = '<div class="search-results-container">';
                                html += '<p class="search-info">Bulunan kullanıcılar:</p>';
                                
                                users.forEach(user => {
                                    html += `
                                        <div class="search-result-item">
                                            <div class="user-info">
                                                <strong>${escapeHtml(user.full_name)}</strong>
                                                <span class="username">(@${escapeHtml(user.username)})</span>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-primary add-user-btn"
                                                    data-user-id="${user.id}"
                                                    data-user-name="${escapeHtml(user.full_name)}">
                                                Ekle
                                            </button>
                                        </div>
                                    `;
                                });
                                
                                html += '</div>';
                                resultsDiv.innerHTML = html;
                                
                                // Ekle butonlarına event listener ekle
                                document.querySelectorAll('.add-user-btn').forEach(btn => {
                                    btn.addEventListener('click', function() {
                                        const userId = this.getAttribute('data-user-id');
                                        const userName = this.getAttribute('data-user-name');
                                        
                                        if (confirm(`${userName} adlı kullanıcıyı gruba eklemek istediğinizden emin misiniz?`)) {
                                            addUserToGroup(userId, userName, this);
                                        }
                                    });
                                });
                                
                            } else {
                                resultsDiv.innerHTML = '<div class="no-results">🚫 Hiç kullanıcı bulunamadı.</div>';
                            }
                        })
                        .catch(error => {
                            console.error('Arama hatası:', error);
                            resultsDiv.innerHTML = '<div class="search-error">❌ Arama sırasında hata oluştu.</div>';
                        });
                }, 300);
                
            } else if (searchTerm.length === 0) {
                resultsDiv.innerHTML = '';
            } else {
                resultsDiv.innerHTML = '<div class="search-info">En az 2 karakter yazın...</div>';
            }
        });
        
        // Kullanıcıyı gruba ekle
        function addUserToGroup(userId, userName, button) {
            // Butonu deaktif et ve loading göster
            button.disabled = true;
            button.innerHTML = '⏳ Ekleniyor...';
            
            // Form oluştur ve submit et
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const userIdInput = document.createElement('input');
            userIdInput.type = 'hidden';
            userIdInput.name = 'user_id';
            userIdInput.value = userId;
            
            const submitInput = document.createElement('input');
            submitInput.type = 'hidden';
            submitInput.name = 'add_member';
            submitInput.value = '1';
            
            form.appendChild(userIdInput);
            form.appendChild(submitInput);
            document.body.appendChild(form);
            form.submit();
        }
        
        // HTML escape fonksiyonu
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    </script>
</body>
</html>