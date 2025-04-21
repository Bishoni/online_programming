<?php
namespace App\auth;

use App\models\DatabaseHandler;
use App\models\Administrator;
use PDO;
use App\auth\PasswordEncrypter;

class AuthService {
    public static function login(string $login, string $password): bool {
        $db = new DatabaseHandler();
        $pdo = $db->getPdo();
        $stmt = $pdo->prepare('SELECT * FROM administrators WHERE login = ?');
        $stmt->execute([$login]);
        $row = $stmt->fetch();

        if (!$row) return false;

        if (PasswordEncrypter::verify($password, $row['admin_password'])) {
            $_SESSION['admin'] = [
                'id' => $row['id'],
                'login' => $row['login'],
                'name' => $row['name'],
                'surname' => $row['surname'],
            ];
            return true;
        }

        return false;
    }

    public static function logout(): void {
        unset($_SESSION['admin']);
        session_destroy();
    }

    public static function check(): bool {
        return isset($_SESSION['admin']);
    }

    public static function admin(): ?Administrator {
        if (!self::check()) return null;

        $a = $_SESSION['admin'];
        return new Administrator($a['id'], $a['login'], '', $a['name'], $a['surname']);
    }
}