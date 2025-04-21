<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\auth\AuthService;

session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';

    if (AuthService::login($login, $password)) {
        header('Location: /public/index.php');
        exit;
    }

    $error = 'Неверный логин или пароль';
}
?>

<form method="POST">
    <input name="login" placeholder="Логин" required>
    <input name="password" type="password" placeholder="Пароль" required>
    <button>Войти</button>
    <div><?= $error ?></div>
</form>