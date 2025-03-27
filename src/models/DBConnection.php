<?php

namespace App\models;

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '5432';
$dbname = $_ENV['DB_DATABASE'] ?? 'database';
$user = $_ENV['DB_USER'] ?? 'username';
$pass = $_ENV['DB_PASS'] ?? 'password';
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";

class DBConnection
{
    public static $dsn;
    public static $usr;
    public static $pwd;
}

DBConnection::$dsn = $dsn;
DBConnection::$usr = $user;
DBConnection::$pwd = $pass;