<?php
namespace App\models;
class Ticket {
    public $id;
    public $showing_id;
    public $seat_number;
    public function __construct($id, $showing_id, $seat_number) {
        $this->id = $id;
        $this->showing_id = $showing_id;
        $this->seat_number = $seat_number;
    }
    public function __toString() {
        return (string)$this->seat_number;
    }
    public function save($pdo) {
        if ($this->id) {
            $stmt = $pdo->prepare("UPDATE \"tickets\" SET showing_id = :showing_id, seat_number = :seat_number WHERE id = :id");
            $stmt->execute([
                ':showing_id' => $this->showing_id,
                ':seat_number' => $this->seat_number,
                ':id' => $this->id,
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO \"tickets\" (showing_id, seat_number) VALUES (:showing_id, :seat_number)");
            $stmt->execute([
                ':showing_id' => $this->showing_id,
                ':seat_number' => $this->seat_number,
            ]);
            $this->id = $pdo->lastInsertId();
        }
    }
    public function setId($id) {
        $this->id = $id;
    }
}
?>