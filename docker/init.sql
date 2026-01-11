SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS cinedb;
USE cinedb;

DROP TABLE IF EXISTS movies;

CREATE TABLE movies (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        title VARCHAR(255) NOT NULL,
                        director VARCHAR(255),
                        release_year INT,
                        summary TEXT,
                        user_mood VARCHAR(255),
                        letterboxd_url VARCHAR(500),
                        poster_url VARCHAR(500),
                        personal_rating INT DEFAULT 0,
                        is_seen BOOLEAN DEFAULT FALSE,
                        source_type ENUM('AI', 'MANUAL') DEFAULT 'AI',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Note les doubles apostrophes '' pour éviter les bugs SQL
INSERT INTO movies (title, director, release_year, summary, source_type, personal_rating, is_seen)
VALUES
    ('Inception', 'Christopher Nolan', 2010, 'Un voleur qui s''approprie des secrets.', 'MANUAL', 5, TRUE),
    ('The Matrix', 'Lana & Lilly Wachowski', 1999, 'Un pirate informatique apprend la vérité.', 'MANUAL', 4, TRUE);