# Проект: Система управления кинотеатром
Данный проект представляет собой веб-приложение для управления кинотеатром, которое включает функционал для работы с фильмами, залами, сеансами и билетами. Ниже представлена документация проекта, включающая описание структуры базы данных, основные классы на PHP и реализованные CRUD-операции.
---
## 1. Структура базы данных
Проект использует СУБД PostgreSQL. Ниже приведено описание структуры базы данных:
### Таблица: movies (Фильмы)

![Схема таблицы movies](readme_images/img_7.png)

**Поля:**
- `id` (PRIMARY KEY)
- `title` (VARCHAR) — название фильма
- `duration` (TIME) — продолжительность
- `has_3d` (BOOLEAN) — поддержка 3D
- `price` (DECIMAL) — стоимость билета

### Таблица: auditoriums (Залы кинотеатра)

![Схема таблицы auditoriums](readme_images/img_6.png)

**Поля:**
- `id` (PRIMARY KEY)
- `name` (VARCHAR) — название зала
- `capacity` (INTEGER) — вместимость
- `has_3d` (BOOLEAN) — поддержка 3D

### Таблица: showings (Сеансы)

![Схема таблицы showings](readme_images/img_8.png)

**Поля:**
- `id` (PRIMARY KEY)
- `movie_id` (FOREIGN KEY) — связь с таблицей movies
- `auditorium_id` (FOREIGN KEY) — связь с таблицей auditoriums
- `start_time` (TIMESTAMP) — время начала сеанса

### Таблица: tickets (Билеты)

![Схема таблицы tickets](readme_images/img_9.png)

**Поля:**
- `id` (PRIMARY KEY)
- `showing_id` (FOREIGN KEY) — связь с таблицей showings
- `seat_number` (INTEGER) — номер места

### Таблица: administrators (Администраторы)

![Схема таблицы administrators](readme_images/img_5.png)

**Поля:**
- `id` (PRIMARY KEY)
- `login` (VARCHAR) — логин администратора
- `admin_password` (VARCHAR) — пароль администратора
- `name` (VARCHAR) — имя
- `surname` (VARCHAR) — фамилия

### Скриншот структуры базы данных
Ниже приведён пример скриншота, демонстрирующего общую схему базы данных:

![Схема БД](readme_images/img_4.png)
---
## 2. Основные классы на PHP
Проект построен на объектно-ориентированном подходе. Ниже описаны основные классы с их назначением и основными методами.
### DatabaseHandler
Главный класс для работы с базой данных. Он обеспечивает подключение к PostgreSQL через PDO и реализует CRUD-операции для различных сущностей.
```php
class DatabaseHandler {
    private $pdo;

    public function __construct($dsn, $user, $password) {
        try {
            $this->pdo = new PDO($dsn, $user, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Ошибка подключения к БД: " . $e->getMessage());
        }
    }

    // Методы для работы с фильмами
    public function getMovies() { /* ... */ }
    public function getMovieByID($id) { /* ... */ }
    public function deleteMovie($id) { /* ... */ }

    // Методы для работы с залами
    public function getAuditoriums() { /* ... */ }
    public function getAuditoriumByID($id) { /* ... */ }
    public function deleteAuditorium($id) { /* ... */ }

    // Методы для работы с сеансами
    public function getShowings() { /* ... */ }
    public function getShowingsByMovie($movie_id) { /* ... */ }
    public function getShowingsByAuditorium($auditorium_id) { /* ... */ }
    public function getShowingByID($id) { /* ... */ }
    public function deleteShowing($id) { /* ... */ }

    // Методы для работы с билетами
    public function getTickets() { /* ... */ }
    public function getTicketsByShowing($showing_id) { /* ... */ }
    public function deleteTicket($id) { /* ... */ }
}
```
### Movie
Класс, отвечающий за данные о фильмах. Содержит свойства, соответствующие полям таблицы movies, и методы для работы с сеансами и сохранения в БД.

```php
class Movie {
    public $id;
    public $title;
    public $duration;
    public $has_3d;
    public $price;

    public function __construct($id = null, $title, $duration, $has_3d, $price) {
        $this->id = $id;
        $this->title = $title;
        $this->duration = $duration;
        $this->has_3d = $has_3d;
        $this->price = $price;
    }

    public function getShowings($pdo) {
        // Реализует получение всех сеансов, связанных с этим фильмом
    }

    public function save($pdo) {
        // Реализует сохранение или обновление фильма в базе данных
    }
}
```
### Auditorium
Класс для работы с залами кинотеатра.

```php
class Auditorium {
    public $id;
    public $name;
    public $capacity;
    public $has_3d;
    public function __construct($id = null, $name, $capacity, $has_3d) {
        $this->id = $id;
        $this->name = $name;
        $this->capacity = $capacity;
        $this->has_3d = $has_3d;
    }
    public function getShowings($pdo) {
        // Реализация получения сеансов для данного зала
    }
    public function save($pdo) {
        // Реализация сохранения или обновления информации о зале
    }
}
```

### Showing
Класс, отвечающий за сеансы.
```php
class Showing {
    public $id;
    public $movie_id;
    public $auditorium_id;
    public $start_time;
    public function __construct($id = null, $movie_id, $auditorium_id, $start_time) {
        $this->id = $id;
        $this->movie_id = $movie_id;
        $this->auditorium_id = $auditorium_id;
        $this->start_time = $start_time;
    }
    public function getMovie($pdo) {
        // Получение данных фильма для данного сеанса
    }
    public function getAuditorium($pdo) {
        // Получение данных зала для данного сеанса
    }
    public function getTickets($pdo) {
        // Получение билетов для данного сеанса
    }
    public function save($pdo) {
        // Сохранение или обновление информации о сеансе
    }
}
```

### Ticket

Класс для работы с билетами.

```php
class Ticket {
    public $id;
    public $showing_id;
    public $seat_number;

    public function __construct($id = null, $showing_id, $seat_number) {
        $this->id = $id;
        $this->showing_id = $showing_id;
        $this->seat_number = $seat_number;
    }

    public function save($pdo) {
        // Сохранение билета в базу данных
    }
}
```
---
## 3. Реализованные CRUD-операции

### Фильмы 
![Интерфейс](readme_images/img.png)  
- **Read (Чтение)**: Выводятся фильмы по строкам с наименованием, длительностью, наличием 3D, ценой за билет
- **Delete (Удаление)**: Справа от соответствующей строки есть чекбокс **Удалить**, нажав на который соответствующий фильм будет помечен для удаления
- **Create (Создание)**: Для создания нового фильма снизу на странице есть дополнительная строка, в которой можно задать соответствующие поля для нового фильма
- **Update (Обновление)**: Для обновления необходимо выбрать строку, изменить в ней необходимые данные.

Примечание: При внесении всех изменений они сначала сохраняются локально, а переносятся в БД только по нажатии на кнопку **Перенести изменения в БД**

### Аудитории (Кинозалы) 
![Интерфейс](readme_images/img_1.png)  
- **Read (Чтение)**: Выводятся кинозалы по строкам с наименованием, вместимостью, наличием 3D.
- **Delete (Удаление)**: Справа от соответствующей строки есть чекбокс **Удалить**, нажав на который соответствующий кинозал будет помечен для удаления.
- **Create (Создание)**: Для создания нового кинозала снизу на странице есть дополнительная строка, в которой можно задать соответствующие поля для нового кинозала.
- **Update (Обновление)**: Для обновления необходимо выбрать строку, изменить в ней необходимые данные.

Примечание: При внесении всех изменений они сначала сохраняются локально, а переносятся в БД только по нажатии на кнопку **Перенести изменения в БД**

### Показы
![Интерфейс](readme_images/img_2.png)  
- **Read (Чтение)**: Выводятся показы по строкам с наименованием фильма, кинозалом, временем начала.
- **Delete (Удаление)**: Справа от соответствующей строки есть чекбокс **Удалить**, нажав на который соответствующий показ будет помечен для удаления
- **Create (Создание)**: Для создания нового показа снизу на странице есть дополнительная строка, в которой можно задать соответствующие поля для нового фильма
- **Update (Обновление): Для обновления необходимо выбрать строку, изменить в ней необходимые данные. Внешние ключи реализованы через вывод соответствующих значений из таблиц с использованием комбобоксов

Примечание: При внесении всех изменений они сначала сохраняются локально, а переносятся в БД только по нажатии на кнопку **Перенести изменения в БД**

### Билеты 
![Интерфейс](readme_images/img_3.png)  
- **Read (Чтение)**: Выводятся билеты по строкам с показами, для этого рядом с показом создана отдельный столбец с возможностью вывода билетов по соответствующему показу.
- **Delete (Удаление)**: Справа от соответствующей строки есть чекбокс **Удалить**, нажав на который соответствующий билет будет помечен для удаления
- **Create (Создание)**: Для создания нового билета снизу соответствующией строки есть дополнительная строка, в которой можно задать номер билета для выбранного показа
- **Update (Обновление)**: Для обновления необходимо выбрать строку, изменить в ней необходимые данные.

Примечание: При внесении всех изменений они сначала сохраняются локально, а переносятся в БД только по нажатии на кнопку **Перенести изменения в БД**

---
## 4. Технологии
- **Язык программирования:** PHP (включая ООП)
- **СУБД:** PostgreSQL
- **Библиотека доступа к БД:** PDO
- **Фронтенд:** HTML, CSS, Bootstrap
---
