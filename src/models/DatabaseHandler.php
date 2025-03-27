<?php
namespace App\models;
use App\models\DBConnection;
use App\models\Movie;
use App\models\Auditorium;
use App\models\Showing;
use App\models\Ticket;
use App\models\Administrator;
class DatabaseHandler {
    private $pdo;
    public function __construct() {
        try {
            $this->pdo = new \PDO(DBConnection::$dsn, DBConnection::$usr, DBConnection::$pwd);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            die("Ошибка подключения: " . $e->getMessage());
        }
    }
    public function getPdo() {
        return $this->pdo;
    }
    public function GetMovies() {
        $stmt = $this->pdo->query("SELECT * FROM \"movies\" ORDER BY id ASC");
        $movies = [];
        while ($v = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $movies[] = new Movie($v['id'], $v['title'], $v['duration'], $v['has_3d'], $v['price']);
        }
        return $movies;
    }
    public function GetMovieByID($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM \"movies\" WHERE id = :id");
        $stmt->execute([':id' => $id]);
        if ($v = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return new Movie($v['id'], $v['title'], $v['duration'], $v['has_3d'], $v['price']);
        }
        return false;
    }
    public function deleteMovie($id) {
        $stmt = $this->pdo->prepare("DELETE FROM \"movies\" WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    public function GetAuditoriums() {
        $stmt = $this->pdo->query("SELECT * FROM \"auditoriums\" ORDER BY id ASC");
        $auditoriums = [];
        while ($v = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $auditoriums[] = new Auditorium($v['id'], $v['name'], $v['capacity'], $v['has_3d']);
        }
        return $auditoriums;
    }
    public function GetAuditoriumByID($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM \"auditoriums\" WHERE id = :id");
        $stmt->execute([':id' => $id]);
        if ($v = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return new Auditorium($v['id'], $v['name'], $v['capacity'], $v['has_3d']);
        }
        return false;
    }
    public function deleteAuditorium($id) {
        $stmt = $this->pdo->prepare("DELETE FROM \"auditoriums\" WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    public function GetShowings() {
        $stmt = $this->pdo->query("SELECT * FROM \"showings\" ORDER BY id ASC");
        $showings = [];
        while ($v = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $showings[] = new Showing($v['id'], $v['movie_id'], $v['auditorium_id'], $v['start_time']);
        }
        return $showings;
    }
    public function GetShowingsByMovie($movie_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM \"showings\" WHERE movie_id = :movie_id ORDER BY id ASC");
        $stmt->execute([':movie_id' => $movie_id]);
        $showings = [];
        while ($v = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $showings[] = new Showing($v['id'], $v['movie_id'], $v['auditorium_id'], $v['start_time']);
        }
        return $showings;
    }
    public function GetShowingsByAuditorium($auditorium_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM \"showings\" WHERE auditorium_id = :auditorium_id ORDER BY id ASC");
        $stmt->execute([':auditorium_id' => $auditorium_id]);
        $showings = [];
        while ($v = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $showings[] = new Showing($v['id'], $v['movie_id'], $v['auditorium_id'], $v['start_time']);
        }
        return $showings;
    }
    public function GetShowingByID($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM \"showings\" WHERE id = :id");
        $stmt->execute([':id' => $id]);
        if ($v = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return new Showing($v['id'], $v['movie_id'], $v['auditorium_id'], $v['start_time']);
        }
        return false;
    }
    public function deleteShowing($id) {
        $stmt = $this->pdo->prepare("DELETE FROM \"showings\" WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    public function GetTicketsByShowing($showing_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM \"tickets\" WHERE showing_id = :showing_id ORDER BY id ASC");
        $stmt->execute([':showing_id' => $showing_id]);
        $tickets = [];
        while ($v = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $tickets[] = new Ticket($v['id'], $v['showing_id'], $v['seat_number']);
        }
        return $tickets;
    }
    public function GetTickets() {
        $stmt = $this->pdo->query("SELECT * FROM \"tickets\" ORDER BY id ASC");
        $tickets = [];
        while ($v = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $tickets[] = new Ticket($v['id'], $v['showing_id'], $v['seat_number']);
        }
        return $tickets;
    }
    public function deleteTicket($id) {
        $stmt = $this->pdo->prepare("DELETE FROM \"tickets\" WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    public function GetAdministratorByID($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM \"administrators\" WHERE id = :id");
        $stmt->execute([':id' => $id]);
        if ($v = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return new Administrator($v['id'], $v['login'], $v['admin_password'], $v['name'], $v['surname']);
        }
        return false;
    }
    public function GetTicketsForShowing($activeShowingId) {
        $stmt = $this->pdo->prepare("SELECT * FROM \"tickets\" WHERE showing_id = :showing_id ORDER BY id ASC");
        $stmt->execute([':showing_id' => $activeShowingId]);
        $tickets = [];
        while ($v = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $tickets[] = new Ticket($v['id'], $v['showing_id'], $v['seat_number']);
        }
        return $tickets;
    }
}
?>