<?php
namespace App\models;
use App\models\DatabaseHandler;
class Auditorium {
    public $id;
    public $name;
    public $capacity;
    public $has_3d;
    public function __construct($id, $name, $capacity, $has_3d) {
        $this->id = $id;
        $this->name = $name;
        $this->capacity = $capacity;
        $this->has_3d = $has_3d;
    }
    public function __toString() {
        return (string)$this->name;
    }
    public function GetShowings() {
        $dbh = new DatabaseHandler();
        return $dbh->GetShowingsByAuditorium($this->id);
    }
    public function save($pdo) {
        $has3d = (!empty($this->has_3d) && $this->has_3d === true) ? 't' : 'f';
        if ($this->id) {
            $stmt = $pdo->prepare(
                'UPDATE "auditoriums" SET name = :name, capacity = :capacity, has_3d = :has_3d WHERE id = :id'
            );
            $stmt->execute([
                ':name'     => $this->name,
                ':capacity' => $this->capacity,
                ':has_3d'   => $has3d,
                ':id'       => $this->id
            ]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO "auditoriums" (name, capacity, has_3d) VALUES (:name, :capacity, :has_3d)'
            );
            $stmt->execute([
                ':name'     => $this->name,
                ':capacity' => $this->capacity,
                ':has_3d'   => $has3d
            ]);
            $this->id = $pdo->lastInsertId();
        }
    }
    public function setId($id) {
        $this->id = $id;
    }
}
?>