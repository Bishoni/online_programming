<?php
namespace App\models;
use App\models\DatabaseHandler;
class Showing {
    public $id;
    public $movie_id;
    public $auditorium_id;
    public $start_time;
    public function __construct($id, $movie_id, $auditorium_id, $start_time) {
        $this->id = $id;
        $this->movie_id = $movie_id;
        $this->auditorium_id = $auditorium_id;
        $this->start_time = $start_time;
    }
    public function __toString() {
        return (string)$this->start_time;
    }
    public function GetMovie() {
        $dbh = new DatabaseHandler();
        return $dbh->GetMovieByID($this->movie_id);
    }
    public function GetAuditorium() {
        $dbh = new DatabaseHandler();
        return $dbh->GetAuditoriumByID($this->auditorium_id);
    }
    public function GetTickets() {
        $dbh = new DatabaseHandler();
        return $dbh->GetTicketsByShowing($this->id);
    }
    public function save($pdo) {
        if ($this->id) {
            $stmt = $pdo->prepare("UPDATE \"showings\" SET movie_id = :movie_id, auditorium_id = :auditorium_id, start_time = :start_time WHERE id = :id");
            $stmt->execute([
                ':movie_id'       => $this->movie_id,
                ':auditorium_id'  => $this->auditorium_id,
                ':start_time'     => $this->start_time,
                ':id'             => $this->id
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO \"showings\" (movie_id, auditorium_id, start_time) VALUES (:movie_id, :auditorium_id, :start_time)");
            $stmt->execute([
                ':movie_id'       => $this->movie_id,
                ':auditorium_id'  => $this->auditorium_id,
                ':start_time'     => $this->start_time
            ]);
            $this->id = $pdo->lastInsertId();
        }
    }
    public function setId($id) {
        $this->id = $id;
    }
}
?>