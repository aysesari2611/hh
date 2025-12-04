<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';

// Sadece giriş yapmış kullanıcılar arama yapabilir
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['q']) || strlen(trim($_GET['q'])) < 2) {
    echo json_encode([]);
    exit;
}

$searchTerm = trim($_GET['q']);
$userHandler = new User();

$users = $userHandler->searchUsers($searchTerm);

// Kendini arama sonuçlarından çıkar
$users = array_filter($users, function($user) {
    return $user['id'] != $_SESSION['user_id'];
});

// JSON olarak döndür
header('Content-Type: application/json');
echo json_encode(array_values($users));
?>