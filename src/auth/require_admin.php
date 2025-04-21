<?php
use App\auth\AuthService;
use App\models\DatabaseHandler;

require_once __DIR__ . '/../../vendor/autoload.php';
session_start();
$db = new DatabaseHandler();
$pdo = $db->getPdo();

if (!AuthService::check()) {
    header('Location: /public/login.php');
    exit;
}