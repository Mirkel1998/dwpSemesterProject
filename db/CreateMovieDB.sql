SET default_storage_engine=InnoDB;
DROP DATABASE IF EXISTS movieDB;
CREATE DATABASE movieDB;
USE movieDB;

CREATE TABLE `movies` (
   movieID INT not null auto_increment primary key,
   title VARCHAR(255),
   release_date DATE,
   rating DECIMAL(3,1) NULL,
   CONSTRAINT chk_movie_rating CHECK (rating IS NULL OR (rating >= 0 AND rating <= 10))
);

CREATE TABLE `cast` (
   castID INT not null auto_increment primary key,
   firstName VARCHAR(100),
   lastName VARCHAR(100)
);
CREATE TABLE `castMovieRelation` (
   relationID INT not null auto_increment primary key,
   castID INT not null,
   movieID INT not null,
   relationToMovie VARCHAR(100),
   FOREIGN KEY (castID) REFERENCES `cast`(castID),
   FOREIGN KEY (movieID) REFERENCES `movies`(movieID)
);
    
CREATE TABLE `genre` (
   genreID INT not null auto_increment primary key,
   genreName VARCHAR(100)
);


CREATE TABLE `movieGenreRelation` (
   relationID INT not null auto_increment primary key,
   movieID INT not null,
   genreID INT not null,
   FOREIGN KEY (movieID) REFERENCES `movies`(movieID),
   FOREIGN KEY (genreID) REFERENCES `genre`(genreID)
);

INSERT INTO `genre` (genreID, genreName) VALUES (NULL, 'Action'), (NULL, 'Comedy'), (NULL, 'Drama'), (NULL, 'Horror'), (NULL, 'Romance');
INSERT INTO `movies` (movieID, title, release_date, rating) VALUES
   (NULL, 'Inception', '2010-07-16', 8.8),
   (NULL, 'The Dark Knight', '2008-07-18', 9.0),
   (NULL, 'Interstellar', '2014-11-07', 8.7),
   (NULL, 'Pulp Fiction', '1994-10-14', 8.9),
   (NULL, 'Fight Club', '1999-10-15', 8.8),
   (NULL, 'The Matrix', '1999-03-31', 8.7),
   (NULL, 'Forrest Gump', '1994-07-06', 8.8),
   (NULL, 'The Godfather', '1972-03-24', 9.2),
   (NULL, 'The Shawshank Redemption', '1994-09-23', 9.3),
   (NULL, 'Titanic', '1997-12-19', 7.9);

-- Cast data based on IMDb credits for the above movies
INSERT INTO `cast` (castID, firstName, lastName) VALUES
   (NULL, 'Leonardo', 'DiCaprio'),
   (NULL, 'Christian', 'Bale'),
   (NULL, 'Matthew', 'McConaughey'),
   (NULL, 'Joseph', 'Gordon-Levitt'),
   (NULL, 'Heath', 'Ledger'),
   (NULL, 'Anne', 'Hathaway'),
   (NULL, 'John', 'Travolta'),
   (NULL, 'Samuel L.', 'Jackson'),
   (NULL, 'Uma', 'Thurman'),
   (NULL, 'Brad', 'Pitt'),
   (NULL, 'Edward', 'Norton'),
   (NULL, 'Helena', 'Bonham Carter'),
   (NULL, 'Keanu', 'Reeves'),
   (NULL, 'Laurence', 'Fishburne'),
   (NULL, 'Carrie-Anne', 'Moss'),
   (NULL, 'Tom', 'Hanks'),
   (NULL, 'Robin', 'Wright'),
   (NULL, 'Marlon', 'Brando'),
   (NULL, 'Al', 'Pacino'),
   (NULL, 'Morgan', 'Freeman'),
   (NULL, 'Kate', 'Winslet'),
   (NULL, 'Christopher', 'Nolan'),
   (NULL, 'Quentin', 'Tarantino'),
   (NULL, 'David', 'Fincher'),
   (NULL, 'Lana', 'Wachowski'),
   (NULL, 'Lilly', 'Wachowski'),
   (NULL, 'Robert', 'Zemeckis'),
   (NULL, 'Francis Ford', 'Coppola'),
   (NULL, 'Frank', 'Darabont'),
   (NULL, 'James', 'Cameron');

INSERT INTO `castMovieRelation` (relationID, castID, movieID, relationToMovie) VALUES
   (NULL, 1, 1, 'Actor'),
   (NULL, 4, 1, 'Actor'),
   (NULL, 22, 1, 'Director'),
   (NULL, 2, 2, 'Actor'),
   (NULL, 5, 2, 'Actor'),
   (NULL, 6, 2, 'Actor'),
   (NULL, 22, 2, 'Director'),
   (NULL, 3, 3, 'Actor'),
   (NULL, 22, 3, 'Director'),
   (NULL, 7, 4, 'Actor'),
   (NULL, 8, 4, 'Actor'),
   (NULL, 9, 4, 'Actor'),
   (NULL, 23, 4, 'Director'),
   (NULL, 10, 5, 'Actor'),
   (NULL, 11, 5, 'Actor'),
   (NULL, 12, 5, 'Actor'),
   (NULL, 24, 5, 'Director'),
   (NULL, 13, 6, 'Actor'),
   (NULL, 14, 6, 'Actor'),
   (NULL, 15, 6, 'Actor'),
   (NULL, 25, 6, 'Director'),
   (NULL, 26, 6, 'Director'),
   (NULL, 16, 7, 'Actor'),
   (NULL, 17, 7, 'Actor'),
   (NULL, 27, 7, 'Director'),
   (NULL, 18, 8, 'Actor'),
   (NULL, 19, 8, 'Actor'),
   (NULL, 28, 8, 'Director'),
   (NULL, 16, 9, 'Actor'),
   (NULL, 20, 9, 'Actor'),
   (NULL, 29, 9, 'Director'),
   (NULL, 1, 10, 'Actor'),
   (NULL, 21, 10, 'Actor'),
   (NULL, 30, 10, 'Director');

INSERT INTO `movieGenreRelation` (relationID, movieID, genreID) VALUES
   (NULL, 1, 1),
   (NULL, 2, 1),
   (NULL, 3, 1),
   (NULL, 4, 3),
   (NULL, 5, 3),
   (NULL, 6, 1),
   (NULL, 7, 3),
   (NULL, 8, 3),
   (NULL, 9, 3),
   (NULL, 10, 5);

-- ===== Cinema + gamification =====
CREATE TABLE `users` (
   userID INT not null auto_increment primary key,
   username VARCHAR(20) not null UNIQUE,
   email VARCHAR(255) not null UNIQUE,
   password_hash VARCHAR(255) not null,
   role ENUM('user','admin') not null default 'user',
   xp INT not null default 0,
   level INT not null default 1,
   login_streak INT not null default 0,
   last_login_date DATE NULL,
   created_at TIMESTAMP not null default CURRENT_TIMESTAMP
);

CREATE TABLE `theaters` (
   theaterID INT not null auto_increment primary key,
   name VARCHAR(100) not null,
   city VARCHAR(100) not null,
   seat_rows INT not null,
   seats_per_row INT not null
);

CREATE TABLE `showtimes` (
   showtimeID INT not null auto_increment primary key,
   movieID INT not null,
   theaterID INT not null,
   starts_at DATETIME not null,
   price INT not null,
   FOREIGN KEY (movieID) REFERENCES `movies`(movieID),
   FOREIGN KEY (theaterID) REFERENCES `theaters`(theaterID)
);

CREATE TABLE `bookings` (
   bookingID INT not null auto_increment primary key,
   userID INT not null,
   showtimeID INT not null,
   seat_row INT not null,
   seat_number INT not null,
   price_paid INT not null,
   created_at TIMESTAMP not null default CURRENT_TIMESTAMP,
   UNIQUE KEY one_seat_per_showing (showtimeID, seat_row, seat_number),
   FOREIGN KEY (userID) REFERENCES `users`(userID),
   FOREIGN KEY (showtimeID) REFERENCES `showtimes`(showtimeID)
);

CREATE TABLE `xp_log` (
   logID INT not null auto_increment primary key,
   userID INT not null,
   amount INT not null,
   reason VARCHAR(100) not null,
   created_at TIMESTAMP not null default CURRENT_TIMESTAMP,
   FOREIGN KEY (userID) REFERENCES `users`(userID)
);

CREATE TABLE `achievements` (
   achievementID INT not null auto_increment primary key,
   code VARCHAR(30) not null UNIQUE,
   name VARCHAR(60) not null,
   description VARCHAR(255) not null,
   xp_reward INT not null
);

CREATE TABLE `user_achievements` (
   userID INT not null,
   achievementID INT not null,
   unlocked_at TIMESTAMP not null default CURRENT_TIMESTAMP,
   PRIMARY KEY (userID, achievementID),
   FOREIGN KEY (userID) REFERENCES `users`(userID),
   FOREIGN KEY (achievementID) REFERENCES `achievements`(achievementID)
);

INSERT INTO `theaters` (name, city, seat_rows, seats_per_row) VALUES
   ('Pixel Palace', 'Copenhagen', 6, 10),
   ('Byte Biograf', 'Aarhus', 5, 8),
   ('Arcade Cinema', 'Odense', 4, 8);

-- Each movie plays in two theaters, 3 days ahead, 2 slots per day
INSERT INTO `showtimes` (movieID, theaterID, starts_at, price)
SELECT m.movieID, t.theaterID, TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL d.n DAY), s.t), 90
FROM `movies` m
JOIN `theaters` t ON t.theaterID = (m.movieID % 3) + 1 OR t.theaterID = ((m.movieID + 1) % 3) + 1
CROSS JOIN (SELECT 1 AS n UNION SELECT 2 UNION SELECT 3) d
CROSS JOIN (SELECT '18:00:00' AS t UNION SELECT '21:00:00') s;

INSERT INTO `achievements` (code, name, description, xp_reward) VALUES
   ('WELCOME', 'Player One', 'Create an account.', 0),
   ('FIRST_TICKET', 'First Ticket', 'Book your first seat.', 50),
   ('REGULAR', 'Box Office Regular', 'Make 5 bookings.', 150),
   ('SQUAD', 'Squad Goals', 'Book 4 or more seats at once.', 100),
   ('NIGHT_OWL', 'Night Owl', 'Book a 21:00 showing.', 50),
   ('STREAK_3', 'On a Roll', 'Log in 3 days in a row.', 75),
   ('LEVEL_5', 'Level 5 Reached', 'Reach level 5.', 200);

   