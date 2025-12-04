<?php
// Sayfa yolu belirleme fonksiyonu
function getBasePath() {
    $currentDir = dirname($_SERVER['PHP_SELF']);
    return (strpos($currentDir, '/pages') !== false) ? '../' : '';
}

$basePath = getBasePath();
?>

<header class="main-header">
    <nav class="navbar">
        <div class="nav-brand">
            <a href="<?php echo $basePath; ?>index.php">
                <span class="logo-icon">📁</span>
                <span class="logo-text">FileSync</span>
            </a>
        </div>
        
        <ul class="nav-menu">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Giriş yapmış kullanıcı menüsü -->
                <li><a href="<?php echo $basePath; ?>index.php">Ana Sayfa</a></li>
                <li><a href="<?php echo $basePath; ?>pages/upload.php">Yükle</a></li>
                <li><a href="<?php echo $basePath; ?>pages/groups.php">Takımlar</a></li>
                <li class="user-menu">
                    <span class="user-welcome"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="<?php echo $basePath; ?>pages/logout.php" class="btn btn-sm">Çıkış</a>
                </li>
            <?php else: ?>
                <!-- Giriş yapmamış kullanıcı menüsü -->
                <li><a href="<?php echo $basePath; ?>index.php">Ana Sayfa</a></li>
                <li><a href="<?php echo $basePath; ?>pages/login.php">Giriş Yap</a></li>
                <li><a href="<?php echo $basePath; ?>pages/register.php">Kayıt Ol</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>