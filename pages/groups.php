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

$error = '';
$success = '';

$groupHandler = new Group();

// Grup oluşturma işlemi
if ($_POST && isset($_POST['create_group'])) {
    $groupName = trim($_POST['group_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    $result = $groupHandler->createGroup($groupName, $description, $_SESSION['user_id']);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Kullanıcının gruplarını getir
$userGroups = $groupHandler->getUserGroups($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gruplarım - Dosya Paylaşım Sitesi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="groups-header">
            <h2>Gruplarım</h2>
            <button class="btn btn-primary" onclick="toggleCreateForm()">Yeni Grup Oluştur</button>
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
        
        <!-- Grup oluşturma formu -->
        <div id="create-group-form" class="auth-form" style="display: none; margin-bottom: 2rem;">
            <h3>Yeni Grup Oluştur</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="group_name">Grup Adı:</label>
                    <input type="text" id="group_name" name="group_name" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Açıklama:</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="create_group" class="btn btn-primary">Oluştur</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleCreateForm()">İptal</button>
                </div>
            </form>
        </div>
        
        <!-- Gruplar listesi -->
        <?php if (empty($userGroups)): ?>
            <div class="empty-state">
                <h3>Henüz hiçbir grubun üyesi değilsiniz</h3>
                <p>Yeni bir grup oluşturun veya arkadaşlarınızın gruplarına katılın.</p>
            </div>
        <?php else: ?>
            <div class="groups-grid">
                <?php foreach ($userGroups as $group): ?>
                    <div class="group-card">
                        <div class="group-header">
                            <h3><?php echo htmlspecialchars($group['group_name']); ?></h3>
                            <?php if ($group['owner_id'] == $_SESSION['user_id']): ?>
                                <span class="owner-badge">Sahip</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($group['description']): ?>
                            <p class="group-description">
                                <?php echo htmlspecialchars($group['description']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="group-stats">
                            <span>👥 <?php echo $group['member_count']; ?> üye</span>
                            <span>📁 <?php echo $group['file_count']; ?> dosya</span>
                        </div>
                        
                        <div class="group-actions">
                            <a href="group_detail.php?id=<?php echo $group['id']; ?>" class="btn btn-primary">Gruba Git</a>
                            <?php if ($group['owner_id'] == $_SESSION['user_id']): ?>
                                <a href="group_manage.php?id=<?php echo $group['id']; ?>" class="btn btn-secondary">Yönet</a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="group-meta">
                            <small>Oluşturulma: <?php echo date('d.m.Y', strtotime($group['created_at'])); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/main.js"></script>
    <script>
        function toggleCreateForm() {
            const form = document.getElementById('create-group-form');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
                document.getElementById('group_name').focus();
            } else {
                form.style.display = 'none';
                // Formu temizle
                document.getElementById('group_name').value = '';
                document.getElementById('description').value = '';
            }
        }
    </script>
</body>
</html>