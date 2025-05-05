-- PostgreSQL

DROP TABLE IF EXISTS Tickets CASCADE;
DROP TABLE IF EXISTS Showings CASCADE;
DROP TABLE IF EXISTS Movies CASCADE;
DROP TABLE IF EXISTS Auditoriums CASCADE;
DROP TABLE IF EXISTS Administrators CASCADE;

CREATE TABLE Movies (
                        id SERIAL PRIMARY KEY,
                        title VARCHAR(64) NOT NULL,
                        duration TIME NOT NULL,
                        has_3d BOOLEAN NOT NULL,
                        price DECIMAL(5, 2) NOT NULL
);

CREATE TABLE Auditoriums (
                             id SERIAL PRIMARY KEY,
                             name VARCHAR(64) NOT NULL,
                             capacity SMALLINT NOT NULL,
                             has_3d BOOLEAN NOT NULL
);

CREATE TABLE Showings (
                          id SERIAL PRIMARY KEY,
                          movie_id INT REFERENCES Movies(id),
                          auditorium_id SMALLINT REFERENCES Auditoriums(id),
                          start_time TIMESTAMP NOT NULL
);

CREATE TABLE Tickets (
                         id BIGSERIAL PRIMARY KEY,
                         showing_id INT REFERENCES Showings(id),
                         seat_number SMALLINT NOT NULL
);

CREATE TABLE Administrators (
                                id SERIAL PRIMARY KEY,
                                login VARCHAR(64) NOT NULL,
                                admin_password VARCHAR(64) NOT NULL,
                                name VARCHAR(64) NOT NULL,
                                surname VARCHAR(64) NOT NULL
);

INSERT INTO Movies (title, duration, has_3d, price) VALUES
                                                        ('Movie 1', '01:30:00', TRUE, 10.00),
                                                        ('Movie 2', '02:00:00', FALSE, 8.50),
                                                        ('Movie 3', '01:45:00', TRUE, 12.00),
                                                        ('Movie 4', '02:15:00', FALSE, 9.00),
                                                        ('Movie 5', '01:50:00', TRUE, 11.00),
                                                        ('Movie 6', '01:20:00', FALSE, 7.50),
                                                        ('Movie 7', '02:05:00', TRUE, 13.00),
                                                        ('Movie 8', '01:40:00', FALSE, 8.00),
                                                        ('Movie 9', '02:10:00', TRUE, 14.00),
                                                        ('Movie 10', '01:35:00', FALSE, 9.50),
                                                        ('Movie 11', '01:55:00', TRUE, 10.50),
                                                        ('Movie 12', '02:20:00', FALSE, 7.00),
                                                        ('Movie 13', '01:25:00', TRUE, 13.50),
                                                        ('Movie 14', '02:30:00', FALSE, 6.50),
                                                        ('Movie 15', '01:45:00', TRUE, 12.50);

INSERT INTO Auditoriums (name, capacity, has_3d) VALUES
                                                     ('Auditorium A', 100, TRUE),
                                                     ('Auditorium B', 120, FALSE),
                                                     ('Auditorium C', 80, TRUE),
                                                     ('Auditorium D', 150, FALSE),
                                                     ('Auditorium E', 90, TRUE),
                                                     ('Auditorium F', 110, FALSE),
                                                     ('Auditorium G', 95, TRUE),
                                                     ('Auditorium H', 130, FALSE),
                                                     ('Auditorium I', 85, TRUE),
                                                     ('Auditorium J', 140, FALSE),
                                                     ('Auditorium K', 75, TRUE),
                                                     ('Auditorium L', 125, FALSE),
                                                     ('Auditorium M', 105, TRUE),
                                                     ('Auditorium N', 115, FALSE),
                                                     ('Auditorium O', 100, TRUE);

INSERT INTO Showings (movie_id, auditorium_id, start_time) VALUES
                                                               (1, 1, '2026-10-10 10:00:00'),
                                                               (2, 2, '2026-10-10 12:00:00'),
                                                               (3, 3, '2026-10-10 14:00:00'),
                                                               (4, 4, '2026-10-10 16:00:00'),
                                                               (5, 5, '2026-10-10 18:00:00'),
                                                               (6, 6, '2026-10-10 20:00:00'),
                                                               (7, 7, '2026-10-11 10:00:00'),
                                                               (8, 8, '2026-10-11 12:00:00'),
                                                               (9, 9, '2026-10-11 14:00:00'),
                                                               (10, 10, '2026-10-11 16:00:00'),
                                                               (11, 11, '2026-10-11 18:00:00'),
                                                               (12, 12, '2026-10-11 20:00:00'),
                                                               (13, 13, '2026-10-12 10:00:00'),
                                                               (14, 14, '2026-10-12 12:00:00'),
                                                               (15, 15, '2026-10-12 14:00:00');

INSERT INTO Tickets (showing_id, seat_number) VALUES
                                                  (1, 1), (1, 2), (1, 3), (1, 4), (1, 5),
                                                  (2, 1), (2, 2), (2, 3), (2, 4), (2, 5),
                                                  (3, 1), (3, 2), (3, 3), (3, 4), (3, 5),
                                                  (4, 1), (4, 2), (4, 3), (4, 4), (4, 5),
                                                  (5, 1), (5, 2), (5, 3), (5, 4), (5, 5),
                                                  (6, 1), (6, 2), (6, 3), (6, 4), (6, 5),
                                                  (7, 1), (7, 2), (7, 3), (7, 4), (7, 5),
                                                  (8, 1), (8, 2), (8, 3), (8, 4), (8, 5),
                                                  (9, 1), (9, 2), (9, 3), (9, 4), (9, 5),
                                                  (10, 1), (10, 2), (10, 3), (10, 4), (10, 5),
                                                  (11, 1), (11, 2), (11, 3), (11, 4), (11, 5),
                                                  (12, 1), (12, 2), (12, 3), (12, 4), (12, 5),
                                                  (13, 1), (13, 2), (13, 3), (13, 4), (13, 5),
                                                  (14, 1), (14, 2), (14, 3), (14, 4), (14, 5),
                                                  (15, 1), (15, 2), (15, 3), (15, 4), (15, 5);

INSERT INTO Administrators (login, admin_password, name, surname) VALUES
                                                                      ('admin1', '$2y$12$krv00SjHnxgwJu.Tf6i1EuSb1nImXaXS.qiOGJw63WL7ZTsZ8u59a', 'Name1', 'Surname1'),
                                                                      ('admin2', '$2y$12$krv00SjHnxgwJu.Tf6i1EuSb1nImXaXS.qiOGJw63WL7ZTsZ8u59a', 'Name2', 'Surname2');
