<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\auth\AuthService;

session_start();
AuthService::logout();

header('Location: login.php');