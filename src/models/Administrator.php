<?php
namespace App\models;
class Administrator {
    public $id;
    public $login;
    public $admin_password;
    public $name;
    public $surname;
    function __construct($id, $login, $admin_password, $name, $surname) {
        $this->id = $id;
        $this->login = $login;
        $this->admin_password = $admin_password;
        $this->name = $name;
        $this->surname = $surname;
    }
    public function __toString() {
        return (string) ($this->name . ' ' . $this->surname);
    }
}
?>