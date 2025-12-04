-- Database schema for Cerdas Cermat System

CREATE DATABASE IF NOT EXISTS cerdas_cermat;
USE cerdas_cermat;

-- Table for teams
CREATE TABLE teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for questions
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_answer ENUM('A', 'B', 'C', 'D') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for matches/games
CREATE TABLE matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    total_questions INT NOT NULL,
    current_question INT DEFAULT 0,
    status ENUM('waiting', 'active', 'completed') DEFAULT 'waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table for match teams (many-to-many relationship)
CREATE TABLE match_teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    team_id INT NOT NULL,
    score INT DEFAULT 0,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    UNIQUE KEY unique_match_team (match_id, team_id)
);

-- Table for buzzer responses
CREATE TABLE buzzer_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    question_id INT NOT NULL,
    team_id INT NOT NULL,
    response_time DECIMAL(10,3) NOT NULL, -- Time in seconds with millisecond precision
    buzzer_order INT NOT NULL, -- Order in which teams pressed buzzer
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    INDEX idx_match_question (match_id, question_id)
);

-- Table for team answers
CREATE TABLE team_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    question_id INT NOT NULL,
    team_id INT NOT NULL,
    selected_answer ENUM('A', 'B', 'C', 'D') NOT NULL,
    is_correct BOOLEAN NOT NULL,
    points_earned INT DEFAULT 0,
    answer_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    UNIQUE KEY unique_team_answer (match_id, question_id, team_id)
);

-- Insert sample teams
INSERT INTO teams (name) VALUES 
('Tim A'), ('Tim B'), ('Tim C'), ('Tim D'), ('Tim E'), 
('Tim F'), ('Tim G'), ('Tim H'), ('Tim I');

-- Insert sample questions
INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES
('Apa ibu kota Indonesia?', 'Jakarta', 'Bandung', 'Surabaya', 'Medan', 'A'),
('Berapa jumlah provinsi di Indonesia?', '32', '33', '34', '35', 'C'),
('Siapa penemu lampu pijar?', 'Thomas Edison', 'Alexander Graham Bell', 'Albert Einstein', 'Isaac Newton', 'A'),
('Apa warna daun yang sehat?', 'Merah', 'Kuning', 'Coklat', 'Hijau', 'D'),
('Berapa hasil dari 8 x 7?', '54', '56', '58', '60', 'B');