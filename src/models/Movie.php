<?php
namespace App\models;
use App\models\DatabaseHandler;
class Movie {
    public $id;
    public $title;
    public $duration;
    public $has_3d;
    public $price;
    public function __construct($id, $title, $duration, $has_3d, $price) {
        $this->id = $id;
        $this->title = $title;
        $this->duration = $duration;
        $this->has_3d = $has_3d;
        $this->price = $price;
    }
    public function __toString() {
        return (string)$this->title;
    }
    public function GetShowings() {
        $dbh = new DatabaseHandler();
        return $dbh->GetShowingsByMovie($this->id);
    }
    public function save($pdo) {
        $has3d = (!empty($this->has_3d) && $this->has_3d === true) ? 't' : 'f';

        if ($this->id) {
            $stmt = $pdo->prepare(
                'UPDATE "movies" SET title = :title, duration = :duration, has_3d = :has_3d, price = :price WHERE id = :id'
            );
            $stmt->execute([
                ':title'    => $this->title,
                ':duration' => $this->duration,
                ':has_3d'   => $has3d,
                ':price'    => $this->price,
                ':id'       => $this->id
            ]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO "movies" (title, duration, has_3d, price) VALUES (:title, :duration, :has_3d, :price)'
            );
            $stmt->execute([
                ':title'    => $this->title,
                ':duration' => $this->duration,
                ':has_3d'   => $has3d,
                ':price'    => $this->price
            ]);
            $this->id = $pdo->lastInsertId();
        }
    }
    public function setId($id) {
        $this->id = $id;
    }
}
?>