<?php
session_start();
require_once '../classes/User.php';

$user = new User();
$user->logout();

header('Location: ../index.php');
exit;
?>