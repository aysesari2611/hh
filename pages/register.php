<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';

// Zaten giriş yapmışsa ana sayfaya yönlendir
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

if ($_POST) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    
    // Form validasyonu
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'Lütfen tüm alanları doldurun.';
    } elseif (strlen($username) < 3) {
        $error = 'Kullanıcı adı en az 3 karakter olmalıdır.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir email adresi girin.';
    } elseif (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır.';
    } elseif ($password !== $password_confirm) {
        $error = 'Şifreler eşleşmiyor.';
    } else {
        $user = new User();
        $result = $user->register($username, $email, $password, $full_name);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Üye Ol - Dosya Paylaşım Sitesi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="auth-form">
            <h2>Üye Ol</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <p><a href="login.php">Giriş yapmak için tıklayın</a></p>
                </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Ad Soyad:</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="username">Kullanıcı Adı:</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Şifre:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Şifre Tekrar:</label>
                    <input type="password" id="password_confirm" name="password_confirm" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Üye Ol</button>
            </form>
            
            <p class="auth-switch">
                Zaten hesabınız var mı? <a href="login.php">Giriş yapın</a>
            </p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>