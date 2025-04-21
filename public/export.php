<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/auth/require_admin.php';

use App\models\DatabaseHandler;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

$db = new DatabaseHandler();

$type = $_POST['type'] ?? '';
$format = $_POST['format'] ?? '';
$report = [];
$title = 'Отчет';

switch ($type) {
    case 'film_tickets':
        $id = (int)$_POST['film_id'];
        $report = $db->ReportTicketsForFilm($id);
        $title = "Билеты на фильм";
        break;
    case 'film_stats_all':
        $report = $db->ReportStatsForAllFilms();
        $title = "Статистика по всем фильмам";
        break;
    case 'hall_stats_all':
        $report = $db->ReportStatsForAllHalls();
        $title = "Статистика по всем залам";
        break;
    default:
        exit("Неверный тип отчета.");
}

if (empty($report)) exit("Нет данных.");

if ($format === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray(array_keys($report[0]), null, 'A1');
    $sheet->fromArray($report, null, 'A2');

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"report.xlsx\"");
    header('Cache-Control: max-age=0');
    (new Xlsx($spreadsheet))->save('php://output');
    exit;
} elseif ($format === 'word') {
    Settings::setZipClass(Settings::PCLZIP);
    $word = new PhpWord();
    $section = $word->addSection();
    $section->addText($title, ['bold' => true, 'size' => 16]);

    $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
    $table->addRow();
    foreach (array_keys($report[0]) as $col) {
        $table->addCell(2000)->addText($col, ['bold' => true]);
    }

    foreach ($report as $row) {
        $table->addRow();
        foreach ($row as $val) {
            $table->addCell(2000)->addText($val);
        }
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header("Content-Disposition: attachment;filename=\"report.docx\"");
    IOFactory::createWriter($word, 'Word2007')->save('php://output');
    exit;
} else {
    exit("Неверный формат.");
}