<?php
require_once __DIR__ . '/../src/auth/require_admin.php';

use App\auth\AuthService;

$admin = AuthService::admin();
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


$allowedTables = ['movies', 'auditoriums', 'showings', 'tickets', 'report_hall_stats', 'report_film_tickets', 'report_film_stats'];
if (!in_array($table, $allowedTables, true)) {
    header('Location: ' . $_SERVER['PHP_SELF'] . '?table=movies');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['show_tickets'])) {
    $activeShowingId = $_POST['showing_id'];
    $ticketsForShowing = $db->GetTicketsForShowing($activeShowingId);
}

class Validator {
    public static function recordExists(PDO $pdo, string $table, int $id): bool {
        $stmt = $pdo->prepare("SELECT 1 FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetch();
    }

    public static function sanitizeString($input): string {
        $input = trim($input);
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $input = str_ireplace(
            ['--', ';', '\'', '"', '/*', '*/', '@@', '@', 'char', 'nchar', 'varchar', 'nvarchar', 'alter', 'begin', 'cast', 'create', 'cursor', 'declare', 'delete', 'drop', 'end', 'exec', 'execute', 'fetch', 'insert', 'kill', 'open', 'select', 'sys', 'sysobjects', 'syscolumns', 'table', 'update'],
            '',
            $input
        );
        return $input;
    }

    public static function isValidDuration($duration): bool {
        return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $duration) === 1;
    }

    public static function isValidDateTime(string $datetime): bool {
        $formats = [
            'Y-m-d\TH:i',
            'Y-m-d\TH:i:s',
            'Y-m-d H:i',
            'Y-m-d H:i:s',
        ];

        foreach ($formats as $format) {
            $dt = DateTime::createFromFormat($format, $datetime);

            if ($dt instanceof DateTime) {
                $errors = DateTime::getLastErrors();
                if (empty($errors['warning_count']) && empty($errors['error_count'])) {
                    return (int)$dt->format('Y') >= (int)date('Y');
                }
            }
        }

        return false;
    }

    public static function isValidNumber($value): bool {
        return is_numeric($value) && $value >= 0;
    }

    public static function isValidId($id): bool {
        return ctype_digit((string) $id);
    }

    public static function isValidCheckbox($value): bool {
        return in_array($value, ['0', '1', 0, 1, true, false], true);
    }

    public static function canDeleteRecord(PDO $pdo, string $table, int $id): bool {
        switch ($table) {
            case 'movies':
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM showings WHERE movie_id = :id");
                break;
            case 'auditoriums':
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM showings WHERE auditorium_id = :id");
                break;
            case 'showings':
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE showing_id = :id");
                break;
            case 'tickets':
                return true; // У билетов нет зависимостей
            default:
                return true;
        }

        $stmt->execute(['id' => $id]);
        return $stmt->fetchColumn() == 0;
    }

}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commit_changes'])) {
    $errors = [];

    if ($table === 'movies' && isset($_POST['rows'])) {
        foreach ($_POST['rows'] as $key => $row) {
            $isNew = str_starts_with($key, 'new_');
            $shouldDelete = isset($row['delete']) && $row['delete'] == '1';
            $id = $row['id'] ?? null;

            if (!$isNew && !Validator::recordExists($pdo, 'movies', (int)$id)) {
                $errors[] = "Фильм: запись не найдена.";
                continue;
            }

            if ($shouldDelete && !$isNew && Validator::isValidId($id)) {
                if (!Validator::canDeleteRecord($pdo, 'movies', (int)$id)) {
                    $errors[] = "Невозможно удалить фильм: есть связанные показы.";
                    continue;
                }
                $stmt = $pdo->prepare("DELETE FROM movies WHERE id = :id");
                $stmt->execute(['id' => $id]);
                continue;
            }

            $title = Validator::sanitizeString($row['title'] ?? '');
            $duration = $row['duration'] ?? '';
            $price = $row['price'] ?? 0;
            $has3d = isset($row['has_3d']) ? $row['has_3d'] : 0;

            if ($isNew && empty($title) && empty($duration) && empty($price) && !$has3d) continue;

            if (!Validator::isValidDuration($duration)) {
                $errors[] = "Фильм: неверный формат длительности ($duration)";
                continue;
            }

            if (!Validator::isValidNumber($price)) {
                $errors[] = "Фильм: цена должна быть неотрицательным числом ($price)";
                continue;
            }

            if (!Validator::isValidCheckbox($has3d)) {
                $errors[] = "Фильм: некорректное значение поля 3D";
                continue;
            }

            $movie = new Movie(null, $title, $duration, (bool)$has3d, (float)$price);
            if (!$isNew) $movie->setId($id);
            $movie->save($pdo);
        }
    } elseif ($table === 'auditoriums' && isset($_POST['rows'])) {
        foreach ($_POST['rows'] as $key => $row) {
            $isNew = str_starts_with($key, 'new_');
            $shouldDelete = isset($row['delete']) && $row['delete'] == '1';
            $id = $row['id'] ?? null;

            if (!$isNew && !Validator::recordExists($pdo, 'auditoriums', (int)$id)) {
                $errors[] = "Аудитория: запись не найдена.";
                continue;
            }

            if ($shouldDelete && !$isNew && Validator::isValidId($id)) {
                if (!Validator::canDeleteRecord($pdo, 'auditoriums', (int)$id)) {
                    $errors[] = "Невозможно удалить аудиторию: есть связанные показы.";
                    continue;
                }
                $stmt = $pdo->prepare("DELETE FROM auditoriums WHERE id = :id");
                $stmt->execute(['id' => $id]);
                continue;
            }

            $name = Validator::sanitizeString($row['name'] ?? '');
            $capacity = $row['capacity'] ?? 0;
            $has3d = $row['has_3d'] ?? 0;

            if ($isNew && empty($name) && empty($capacity) && !$has3d) continue;

            if (!Validator::isValidNumber($capacity)) {
                $errors[] = "Аудитория: вместимость должна быть неотрицательным числом ($capacity)";
                continue;
            }

            if (!Validator::isValidCheckbox($has3d)) {
                $errors[] = "Аудитория: некорректное значение поля 3D";
                continue;
            }

            $auditorium = new Auditorium(null, $name, (int)$capacity, (bool)$has3d);
            if (!$isNew) $auditorium->setId($id);
            $auditorium->save($pdo);
        }
    } elseif ($table === 'showings' && isset($_POST['rows'])) {
        foreach ($_POST['rows'] as $key => $row) {
            $isNew = str_starts_with($key, 'new_');
            $shouldDelete = isset($row['delete']) && $row['delete'] == '1';
            $id = $row['id'] ?? null;

            if (!$isNew && !Validator::recordExists($pdo, 'showings', (int)$id)) {
                $errors[] = "Показ: запись не найдена.";
                continue;
            }

            if ($shouldDelete && !$isNew && Validator::isValidId($id)) {
                if (!Validator::canDeleteRecord($pdo, 'showings', (int)$id)) {
                    $errors[] = "Невозможно удалить показ: есть связанные билеты.";
                    continue;
                }
                $stmt = $pdo->prepare("DELETE FROM showings WHERE id = :id");
                $stmt->execute(['id' => $id]);
                continue;
            }

            $movieId = $row['movie_id'] ?? '';
            $auditoriumId = $row['auditorium_id'] ?? '';
            $startTime = $row['start_time'] ?? '';

            if ($isNew && (empty($movieId) || empty($auditoriumId) || empty($startTime))) continue;

            if (!Validator::isValidId($movieId)) {
                $errors[] = "Показ: неверный ID фильма ($movieId)";
                continue;
            }

            if (!Validator::isValidId($auditoriumId)) {
                $errors[] = "Показ: неверный ID аудитории ($auditoriumId)";
                continue;
            }

            if (!Validator::isValidDateTime($startTime)) {
                $errors[] = "Показ: неверная дата и время начала";
                continue;
            }

            $showing = new Showing(null, $movieId, $auditoriumId, $startTime);
            if (!$isNew) $showing->setId($id);
            $showing->save($pdo);
        }
    }

    if (isset($_POST['tickets'])) {
        foreach ($_POST['tickets'] as $showingId => $ticketRows) {
            if (!Validator::isValidId($showingId)) {
                $errors[] = "Билет: неверный показ";
                continue;
            }

            foreach ($ticketRows as $key => $row) {
                $isNew = str_starts_with($key, 'new_');
                $shouldDelete = isset($row['delete']) && $row['delete'] == '1';
                $id = $row['id'] ?? null;
                $seatNumber = $row['seat_number'] ?? '';

                if (!$isNew && !Validator::recordExists($pdo, 'tickets', (int)$id)) {
                    $errors[] = "Билет: запись не найдена.";
                    continue;
                }

                if ($shouldDelete && !$isNew && Validator::isValidId($id)) {
                    $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = :id");
                    $stmt->execute(['id' => $id]);
                    continue;
                }

                if ($isNew && empty($seatNumber)) continue;

                if (!Validator::isValidNumber($seatNumber)) {
                    $errors[] = "Билет: номер места должен быть неотрицательным числом ($seatNumber)";
                    continue;
                }

                $ticket = new Ticket(null, $showingId, (int)$seatNumber);
                if (!$isNew) $ticket->setId($id);
                $ticket->save($pdo);
            }
        }
    }

    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => empty($errors),
            'message' => empty($errors) ? 'Изменения успешно сохранены!' : 'Обнаружены ошибки при сохранении',
            'errors' => $errors
        ]);
        exit;
    } else {
        if (!headers_sent()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?table=" . urlencode($table));
            exit;
        }
    }
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
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }

        .toast-message {
            min-width: 250px;
            max-width: 400px;
            background-color: #323232;
            color: #fff;
            padding: 15px 20px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            opacity: 0;
            animation: fadeIn 0.4s ease forwards;
        }

        .toast-message.success { background-color: #4CAF50; }
        .toast-message.error   { background-color: #f44336; }

        .toast-message strong {
            margin-right: 8px;
        }

        @keyframes fadeIn {
            to { opacity: 1; }
        }
        @keyframes fadeOut {
            to { opacity: 0; transform: translateY(20px); }
        }
    </style>
</head>
<body>
<div class="container mt-3 mb-0 d-flex justify-content-between align-items-center">
    <h4 class="mb-0">Добро пожаловать!</h4>
    <a href="logout.php" class="btn btn-outline-danger btn-sm">Выйти</a>
</div>
<hr>
<div class="container">
    <h1 class="text-center mb-4">Кинотеатр</h1>
    <form method="POST" class="mb-4">
        <div class="form-group row justify-content-center">
            <div class="col-md-4">
                <select name="table" id="table" class="custom-select" onchange="this.form.submit()">
                    <option value="movies" <?= $table === 'movies' ? 'selected' : '' ?>>Фильмы</option>
                    <option value="auditoriums" <?= $table === 'auditoriums' ? 'selected' : '' ?>>Аудитории</option>
                    <option value="showings" <?= $table === 'showings' ? 'selected' : '' ?>>Показы</option>
                    <option disabled>────── Отчёты ──────</option>
                    <option value="report_film_tickets" <?= $table === 'report_film_tickets' ? 'selected' : '' ?>>Список билетов на фильм</option>
                    <option value="report_film_stats" <?= $table === 'report_film_stats' ? 'selected' : '' ?>>Статистика по фильмам</option>
                    <option value="report_hall_stats" <?= $table === 'report_hall_stats' ? 'selected' : '' ?>>Статистика по кинозалам</option>
                </select>
            </div>
        </div>
    </form>
    <?php if ($table === 'movies'): ?>
        <h2 class="text-center mb-4">Редактирование фильмов</h2>
        <form method="POST" id="crud-form">
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
                <button type="button" id="submit-btn" class="btn btn-primary px-4">Перенести изменения в БД</button>
            </div>
        </form>
    <?php elseif ($table === 'auditoriums'): ?>
        <h2 class="text-center mb-4">Редактирование аудиторий</h2>
        <form method="POST" id="crud-form">
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
                <button type="button" id="submit-btn" class="btn btn-primary px-4">Перенести изменения в БД</button>
            </div>
        </form>
    <?php elseif ($table === 'showings'): ?>
        <h2 class="text-center mb-4">Редактирование показов</h2>
        <form method="POST" id="crud-form">
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
                <button type="button" id="submit-btn" class="btn btn-primary px-4">Перенести изменения в БД</button>
            </div>
        </form>
    <?php elseif ($table === 'tickets'): ?>
        <h2 class="text-center mb-4">Редактирование билетов</h2>
        <form method="POST" id="crud-form">
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
                <button type="button" id="submit-btn" class="btn btn-primary px-4">Перенести изменения в БД</button>
            </div>
        </form>
    <?php elseif ($table === 'report_film_tickets'): ?>
        <?php $moviesList = $db->GetMovies(); ?>
        <h2 class="text-center mb-4">Список билетов на фильм</h2>
        <form method="POST" class="form-inline mb-4">
            <input type="hidden" name="table" value="report_film_tickets">
            <div class="form-group mr-2">
                <label for="film_id">Выберите фильм:</label>
                <select name="film_id" id="film_id" class="custom-select ml-2">
                    <?php foreach ($moviesList as $m): ?>
                        <option value="<?= $m->id ?>" <?= isset($_POST['film_id']) && $_POST['film_id'] == $m->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m->title) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary ml-2">Показать</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['film_id'])) {
            $filmId = (int)$_POST['film_id'];
            $report = $db->ReportTicketsForFilm($filmId);

            if (!empty($report)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                        <tr>
                            <?php foreach (array_keys($report[0]) as $col): ?>
                                <th><?= htmlspecialchars($col) ?></th>
                            <?php endforeach; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($report as $row): ?>
                            <tr>
                                <?php foreach ($row as $value): ?>
                                    <td><?= htmlspecialchars($value) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mb-4">
                    <form method="post" action="export.php" target="_blank" class="d-inline">
                        <input type="hidden" name="type" value="film_tickets">
                        <input type="hidden" name="film_id" value="<?= $filmId ?>">
                        <button name="format" value="excel" class="btn btn-success">📥 Скачать Excel</button>
                        <button name="format" value="word" class="btn btn-secondary">📄 Скачать Word</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">Нет данных по выбранному фильму.</div>
            <?php endif;
        }
        ?>
    <?php elseif ($table === 'report_film_stats'): ?>
        <h2 class="text-center mb-4">Статистика по всем фильмам</h2>
        <?php
        $report = $db->ReportStatsForAllFilms();
        if (!empty($report)): ?>
            <table class="table table-bordered">
                <thead class="thead-light">
                <tr>
                    <?php foreach (array_keys($report[0]) as $col): ?>
                        <th><?= htmlspecialchars($col) ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($report as $row): ?>
                    <tr>
                        <?php foreach ($row as $val): ?>
                            <td><?= htmlspecialchars($val) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="text-center mb-4">
                <form method="post" action="export.php" target="_blank" class="d-inline">
                    <input type="hidden" name="type" value="film_stats_all">
                    <button name="format" value="excel" class="btn btn-success">📥 Excel</button>
                    <button name="format" value="word" class="btn btn-secondary">📄 Word</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">Нет данных.</div>
        <?php endif; ?>
    <?php elseif ($table === 'report_hall_stats'): ?>
        <h2 class="text-center mb-4">Статистика по всем залам</h2>
        <?php
        $report = $db->ReportStatsForAllHalls();
        if (!empty($report)): ?>
            <table class="table table-bordered">
                <thead class="thead-light">
                <tr>
                    <?php foreach (array_keys($report[0]) as $col): ?>
                        <th><?= htmlspecialchars($col) ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($report as $row): ?>
                    <tr>
                        <?php foreach ($row as $val): ?>
                            <td><?= htmlspecialchars($val) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="text-center mb-4">
                <form method="post" action="export.php" target="_blank" class="d-inline">
                    <input type="hidden" name="type" value="hall_stats_all">
                    <button name="format" value="excel" class="btn btn-success">📥 Excel</button>
                    <button name="format" value="word" class="btn btn-secondary">📄 Word</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">Нет данных.</div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>

    function showToast(type = 'success', message = '') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast-message ${type}`;
        toast.innerHTML = `<strong>${type === 'success' ? '✅' : '❌'}</strong> ${message}`;
        container.appendChild(toast);

        // Удаление после показа
        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.4s ease forwards';
            toast.addEventListener('animationend', () => toast.remove());
        }, 5000);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('crud-form');
        const submitBtn = document.getElementById('submit-btn');

        if (!form || !submitBtn) return;

        submitBtn.addEventListener('click', async () => {
            const formData = new FormData(form);
            formData.append('commit_changes', '1');

            submitBtn.disabled = true;
            submitBtn.textContent = 'Сохранение...';

            try {
                const response = await fetch(location.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const text = await response.text();

                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    throw new Error('Сервер вернул не JSON:\n' + text);
                }

                document.querySelectorAll('.ajax-alert').forEach(el => el.remove());

                if (result.success) {
                    showToast('success', result.message || '✅ Изменения успешно сохранены!');
                } else {
                    (result.errors || []).forEach(msg => showToast('error', msg));
                }
            } catch (err) {
                alert('Ошибка при отправке данных');
                console.error('Ошибка при обработке ответа сервера:', err);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Перенести изменения в БД';
            }
        });
    });
</script>
<div class="toast-container" id="toast-container"></div>
</body>
</html>