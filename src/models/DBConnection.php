<?php

namespace App\models;

$host = 'postgres';
$port = '5432';
$dbname = 'my_kino';
$user = 'user';
$pass = 'secret';
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