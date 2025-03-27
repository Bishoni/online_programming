<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\models\DatabaseHandler;
use App\models\Movie;
use App\models\Auditorium;
use App\models\Showing;
use App\models\Ticket;
$db = new DatabaseHandler();
$pdo = $db->getPdo();
$table = $_POST['table'] ?? ($_GET['table'] ?? 'movies');
$activeShowingId = null;
$ticketsForShowing = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['show_tickets'])) {
    $activeShowingId = $_POST['showing_id'];
    $ticketsForShowing = $db->GetTicketsForShowing($activeShowingId);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commit_changes'])) {
    if ($table === 'movies' && isset($_POST['rows'])) {
        foreach ($_POST['rows'] as $key => $row) {
            if (isset($row['delete']) && $row['delete'] == 1 && is_numeric($key)) {
                $db->deleteMovie($row['id']);
            } else {
                if (strpos($key, 'new_') === 0) {
                    if (empty($row['title']) && empty($row['duration']) && empty($row['price']) && !isset($row['has_3d'])) {
                        continue;
                    }
                }
                $title    = isset($row['title']) ? $row['title'] : '';
                $duration = isset($row['duration']) ? $row['duration'] : '00:00';
                $price    = isset($row['price']) ? $row['price'] : 0;
                $has3d    = isset($row['has_3d']) && $row['has_3d'] == 1 ? true : false;
                $movie = new Movie(null, $title, $duration, $has3d, $price);
                if (is_numeric($key)) {
                    $movie->setId($row['id']);
                }
                $movie->save($pdo);
            }
        }
    } elseif ($table === 'auditoriums' && isset($_POST['rows'])) {
        foreach ($_POST['rows'] as $key => $row) {
            if (isset($row['delete']) && $row['delete'] == 1 && is_numeric($key)) {
                $db->deleteAuditorium($row['id']);
            } else {
                if (strpos($key, 'new_') === 0) {
                    if (empty($row['name']) && empty($row['capacity']) && !isset($row['has_3d'])) {
                        continue;
                    }
                }
                $has3d = isset($row['has_3d']) && $row['has_3d'] == 1 ? true : false;
                $auditorium = new Auditorium(null, $row['name'], $row['capacity'], $has3d);
                if (is_numeric($key)) {
                    $auditorium->setId($row['id']);
                }
                $auditorium->save($pdo);
            }
        }
    } elseif ($table === 'showings' && isset($_POST['rows'])) {
        foreach ($_POST['rows'] as $key => $row) {
            if (isset($row['delete']) && $row['delete'] == 1 && is_numeric($key)) {
                $db->deleteShowing($row['id']);
            } else {
                if (strpos($key, 'new_') === 0) {
                    if (empty($row['movie_id']) || empty($row['auditorium_id']) || empty($row['start_time'])) {
                        continue;
                    }
                }
                $showing = new Showing(null, $row['movie_id'], $row['auditorium_id'], $row['start_time']);
                if (is_numeric($key)) {
                    $showing->setId($row['id']);
                }
                $showing->save($pdo);
            }
        }
    }
    if (isset($_POST['tickets'])) {
        foreach ($_POST['tickets'] as $showingId => $ticketRows) {
            foreach ($ticketRows as $key => $row) {
                if (isset($row['delete']) && $row['delete'] == 1 && is_numeric($key)) {
                    $db->deleteTicket($row['id']);
                } else {
                    if (strpos($key, 'new_') === 0) {
                        if (empty($row['seat_number'])) continue;
                    }
                    $ticket = new Ticket(null, $showingId, $row['seat_number']);
                    if (is_numeric($key)) {
                        $ticket->setId($row['id']);
                    }
                    $ticket->save($pdo);
                }
            }
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?table=" . urlencode($table));
    exit();
}
if ($table === 'movies') {
    $movies = $db->GetMovies();
    $moviesList = $db->GetMovies();
}
if ($table === 'auditoriums') {
    $auditoriums = $db->GetAuditoriums();
    $auditoriumsList = $db->GetAuditoriums();
}
if ($table === 'showings') {
    $showings = $db->GetShowings();
    $moviesList = $db->GetMovies();
    $auditoriumsList = $db->GetAuditoriums();
}
if ($table === 'tickets') {
    $tickets = $db->GetTickets();
    $showingsList = $db->GetShowings();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Кинотеатр</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #eceff1; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        h1, h2 { color: #263238; }
        .container { background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 8px 16px rgba(0,0,0,0.1); margin-top: 30px; margin-bottom: 30px; }
        table { font-size: 0.95rem; }
        .thead-light { background-color: #37474f; color: #eceff1; }
        .form-control, .custom-select { border-radius: 4px; }
        button.btn { border-radius: 4px; }
        .table-responsive { margin-bottom: 20px; }
        .inline-form { display: inline; }
        option { color: #263238; }
    </style>
</head>
<body>
<div class="container">
    <h1 class="text-center mb-4">Кинотеатр</h1>
    <form method="POST" class="mb-4">
        <div class="form-group row justify-content-center">
            <div class="col-md-4">
                <select name="table" id="table" class="custom-select" onchange="this.form.submit()">
                    <option value="movies" <?= $table === 'movies' ? 'selected' : '' ?>>Фильмы</option>
                    <option value="auditoriums" <?= $table === 'auditoriums' ? 'selected' : '' ?>>Аудитории</option>
                    <option value="showings" <?= $table === 'showings' ? 'selected' : '' ?>>Показы</option>
                </select>
            </div>
        </div>
    </form>
    <?php if ($table === 'movies'): ?>
        <h2 class="text-center mb-4">Редактирование фильмов</h2>
        <form method="POST">
            <input type="hidden" name="table" value="movies">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                    <tr>
                        <th>Название</th>
                        <th>Длительность</th>
                        <th>3D</th>
                        <th>Цена</th>
                        <th>Удалить</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($movies as $movie): ?>
                        <tr>
                            <td>
                                <input type="text" name="rows[<?= $movie->id ?>][title]" value="<?= htmlspecialchars($movie->title) ?>" class="form-control" required>
                                <input type="hidden" name="rows[<?= $movie->id ?>][id]" value="<?= $movie->id ?>">
                            </td>
                            <td>
                                <input type="time" name="rows[<?= $movie->id ?>][duration]" value="<?= htmlspecialchars($movie->duration) ?>" class="form-control" required>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" name="rows[<?= $movie->id ?>][has_3d]" value="1" <?= $movie->has_3d ? 'checked' : '' ?>>
                            </td>
                            <td>
                                <input type="number" step="0.01" name="rows[<?= $movie->id ?>][price]" value="<?= htmlspecialchars($movie->price) ?>" class="form-control" required>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" name="rows[<?= $movie->id ?>][delete]" value="1">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td><input type="text" name="rows[new_1][title]" placeholder="Название нового фильма" class="form-control"></td>
                        <td><input type="time" name="rows[new_1][duration]" class="form-control"></td>
                        <td class="text-center"><input type="checkbox" name="rows[new_1][has_3d]" value="1"></td>
                        <td><input type="number" step="0.01" name="rows[new_1][price]" placeholder="Цена" class="form-control"></td>
                        <td></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="text-center">
                <button type="submit" name="commit_changes" class="btn btn-primary px-4">Перенести изменения в БД</button>
            </div>
        </form>
    <?php elseif ($table === 'auditoriums'): ?>
        <h2 class="text-center mb-4">Редактирование аудиторий</h2>
        <form method="POST">
            <input type="hidden" name="table" value="auditoriums">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                    <tr>
                        <th>Название</th>
                        <th>Вместимость</th>
                        <th>3D</th>
                        <th>Удалить</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($auditoriums as $auditorium): ?>
                        <tr>
                            <td>
                                <input type="text" name="rows[<?= $auditorium->id ?>][name]" value="<?= htmlspecialchars($auditorium->name) ?>" class="form-control" required>
                                <input type="hidden" name="rows[<?= $auditorium->id ?>][id]" value="<?= $auditorium->id ?>">
                            </td>
                            <td>
                                <input type="number" name="rows[<?= $auditorium->id ?>][capacity]" value="<?= htmlspecialchars($auditorium->capacity) ?>" class="form-control" required>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" name="rows[<?= $auditorium->id ?>][has_3d]" value="1" <?= $auditorium->has_3d ? 'checked' : '' ?>>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" name="rows[<?= $auditorium->id ?>][delete]" value="1">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td><input type="text" name="rows[new_1][name]" placeholder="Название аудитории" class="form-control"></td>
                        <td><input type="number" name="rows[new_1][capacity]" placeholder="Вместимость" class="form-control"></td>
                        <td class="text-center"><input type="checkbox" name="rows[new_1][has_3d]" value="1"></td>
                        <td></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="text-center">
                <button type="submit" name="commit_changes" class="btn btn-primary px-4">Перенести изменения в БД</button>
            </div>
        </form>
    <?php elseif ($table === 'showings'): ?>
        <h2 class="text-center mb-4">Редактирование показов</h2>
        <form method="POST">
            <input type="hidden" name="table" value="showings">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                    <tr>
                        <th>Фильм</th>
                        <th>Аудитория</th>
                        <th>Время начала</th>
                        <th>Удалить</th>
                        <th>Действия</th>
                        <th>Номера мест</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($showings as $showing): ?>
                        <tr>
                            <td>
                                <select name="rows[<?= $showing->id ?>][movie_id]" class="custom-select" required>
                                    <option value="">Выберите фильм</option>
                                    <?php foreach ($moviesList as $movie): ?>
                                        <option value="<?= $movie->id ?>" <?= $movie->id == $showing->movie_id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($movie->title) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="rows[<?= $showing->id ?>][id]" value="<?= $showing->id ?>">
                            </td>
                            <td>
                                <select name="rows[<?= $showing->id ?>][auditorium_id]" class="custom-select" required>
                                    <option value="">Выберите аудиторию</option>
                                    <?php foreach ($auditoriumsList as $auditorium): ?>
                                        <option value="<?= $auditorium->id ?>" <?= $auditorium->id == $showing->auditorium_id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($auditorium->name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="datetime-local" name="rows[<?= $showing->id ?>][start_time]" value="<?= date('Y-m-d\TH:i', strtotime($showing->start_time)) ?>" class="form-control" required>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" name="rows[<?= $showing->id ?>][delete]" value="1">
                            </td>
                            <td class="text-center">
                                <?php if (!isset($activeShowingId) || $activeShowingId != $showing->id): ?>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="table" value="showings">
                                        <input type="hidden" name="showing_id" value="<?= $showing->id ?>">
                                        <button type="submit" name="show_tickets" class="btn btn-sm btn-info">Показать билеты</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-success font-weight-bold">Билеты отображены</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($activeShowingId) && $activeShowingId == $showing->id): ?>
                                    <div class="table-responsive mt-2">
                                        <table class="table table-bordered ticket-table">
                                            <thead class="thead-light">
                                            <tr>
                                                <th>Номер места</th>
                                                <th>Удалить</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php if (!empty($ticketsForShowing)): ?>
                                                <?php foreach ($ticketsForShowing as $ticket): ?>
                                                    <tr>
                                                        <td>
                                                            <input type="number" name="tickets[<?= $showing->id ?>][<?= $ticket->id ?>][seat_number]" value="<?= htmlspecialchars($ticket->seat_number) ?>" class="form-control" required>
                                                            <input type="hidden" name="tickets[<?= $showing->id ?>][<?= $ticket->id ?>][id]" value="<?= $ticket->id ?>">
                                                        </td>
                                                        <td class="text-center">
                                                            <input type="checkbox" name="tickets[<?= $showing->id ?>][<?= $ticket->id ?>][delete]" value="1">
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <tr>
                                                <td>
                                                    <input type="number" name="tickets[<?= $showing->id ?>][new_1][seat_number]" placeholder="Номер места" class="form-control">
                                                </td>
                                                <td></td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td>
                            <select name="rows[new_1][movie_id]" class="custom-select">
                                <option value="">Выберите фильм</option>
                                <?php foreach ($moviesList as $movie): ?>
                                    <option value="<?= $movie->id ?>"><?= htmlspecialchars($movie->title) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="rows[new_1][auditorium_id]" class="custom-select">
                                <option value="">Выберите аудиторию</option>
                                <?php foreach ($auditoriumsList as $auditorium): ?>
                                    <option value="<?= $auditorium->id ?>"><?= htmlspecialchars($auditorium->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="datetime-local" name="rows[new_1][start_time]" class="form-control">
                        </td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="text-center">
                <button type="submit" name="commit_changes" class="btn btn-primary px-4">Перенести изменения в БД</button>
            </div>
        </form>
    <?php elseif ($table === 'tickets'): ?>
        <h2 class="text-center mb-4">Редактирование билетов</h2>
        <form method="POST">
            <input type="hidden" name="table" value="tickets">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                    <tr>
                        <th>Показ</th>
                        <th>Номер места</th>
                        <th>Удалить</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td>
                                <select name="rows[<?= $ticket->id ?>][showing_id]" class="custom-select" required>
                                    <option value="">Выберите показ</option>
                                    <?php foreach ($showingsList as $showing): ?>
                                        <option value="<?= $showing->id ?>" <?= $showing->id == $ticket->showing_id ? 'selected' : '' ?>>
                                            <?= "Показ #".htmlspecialchars($showing->id) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="rows[<?= $ticket->id ?>][id]" value="<?= $ticket->id ?>">
                            </td>
                            <td>
                                <input type="number" name="rows[<?= $ticket->id ?>][seat_number]" value="<?= htmlspecialchars($ticket->seat_number) ?>" class="form-control" required>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" name="rows[<?= $ticket->id ?>][delete]" value="1">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td>
                            <select name="rows[new_1][showing_id]" class="custom-select" required>
                                <option value="">Выберите показ</option>
                                <?php foreach ($showingsList as $showing): ?>
                                    <option value="<?= $showing->id ?>"><?= "Показ #".htmlspecialchars($showing->id) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" name="rows[new_1][seat_number]" placeholder="Номер места" class="form-control">
                        </td>
                        <td></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="text-center">
                <button type="submit" name="commit_changes" class="btn btn-primary px-4">Перенести изменения в БД</button>
            </div>
        </form>
    <?php endif; ?>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>