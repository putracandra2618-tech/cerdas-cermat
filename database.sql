-- =====================================================
-- Database Schema for Cerdas Cermat Buzzer System
-- =====================================================

DROP DATABASE IF EXISTS cerdas_cermat;
CREATE DATABASE cerdas_cermat;
USE cerdas_cermat;

-- =====================================================
-- Table: teams
-- =====================================================
CREATE TABLE teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    total_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table: questions
-- =====================================================
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_text TEXT NOT NULL,
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT NOT NULL,
    option_e TEXT NOT NULL,
    correct_option CHAR(1) NOT NULL,
    category VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_correct_option CHECK (correct_option IN ('A','B','C','D','E'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table: matches
-- =====================================================
CREATE TABLE matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    round VARCHAR(50) DEFAULT 'Penyisihan',
    status ENUM('waiting', 'running', 'finished') DEFAULT 'waiting',
    winner_team_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (winner_team_id) REFERENCES teams(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table: match_teams (Many-to-Many)
-- =====================================================
CREATE TABLE match_teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    team_id INT NOT NULL,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    UNIQUE KEY unique_match_team (match_id, team_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table: rooms
-- =====================================================
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    status ENUM('waiting', 'running', 'finished') DEFAULT 'waiting',
    current_question_id INT DEFAULT NULL,
    current_phase ENUM('countdown', 'buzzer', 'answer', 'result', 'idle') DEFAULT 'idle',
    countdown_value INT DEFAULT 3,
    question_start_time DECIMAL(16,4) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (current_question_id) REFERENCES questions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table: room_participants
-- =====================================================
CREATE TABLE room_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    team_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_team (room_id, team_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table: buzzers
-- =====================================================
CREATE TABLE buzzers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    question_id INT NOT NULL,
    team_id INT NOT NULL,
    buzz_time DECIMAL(10,4) NOT NULL COMMENT 'Time in seconds from question start',
    buzzer_order INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    UNIQUE KEY unique_buzzer (room_id, question_id, team_id),
    INDEX idx_room_question (room_id, question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table: answers
-- =====================================================
CREATE TABLE answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    question_id INT NOT NULL,
    team_id INT NOT NULL,
    selected_answer CHAR(1) NOT NULL,
    is_correct TINYINT(1) DEFAULT 0,
    points_earned INT DEFAULT 0,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    UNIQUE KEY unique_answer (room_id, question_id, team_id),
    CONSTRAINT chk_selected_answer CHECK (selected_answer IN ('A','B','C','D','E'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table: scores
-- =====================================================
CREATE TABLE scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    team_id INT NOT NULL,
    total_points INT DEFAULT 0,
    correct_answers INT DEFAULT 0,
    wrong_answers INT DEFAULT 0,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_team_score (room_id, team_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Sample Data: Teams
-- =====================================================
INSERT INTO teams (name) VALUES 
('Tim A - Merah'),
('Tim B - Biru'),
('Tim C - Hijau'),
('Tim D - Kuning'),
('Tim E - Ungu'),
('Tim F - Orange'),
('Tim G - Pink'),
('Tim H - Coklat'),
('Tim I - Abu-abu');

-- =====================================================
-- Sample Data: Questions
-- =====================================================
INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, option_e, correct_option, category) VALUES
('Apa ibu kota Indonesia?', 'Jakarta', 'Bandung', 'Surabaya', 'Medan', 'Yogyakarta', 'A', 'Geografi'),
('Berapa jumlah provinsi di Indonesia saat ini?', '32', '33', '34', '35', '38', 'E', 'Geografi'),
('Siapa penemu lampu pijar?', 'Thomas Edison', 'Alexander Graham Bell', 'Albert Einstein', 'Isaac Newton', 'Nikola Tesla', 'A', 'Sains'),
('Apa warna daun yang sehat?', 'Merah', 'Kuning', 'Coklat', 'Hijau', 'Putih', 'D', 'Alam'),
('Berapa hasil dari 8 x 7?', '54', '56', '58', '60', '64', 'B', 'Matematika'),
('Planet terbesar di tata surya adalah?', 'Mars', 'Jupiter', 'Saturnus', 'Uranus', 'Neptunus', 'B', 'Astronomi'),
('Siapa presiden pertama Indonesia?', 'Soeharto', 'Soekarno', 'B.J. Habibie', 'Megawati', 'Jokowi', 'B', 'Sejarah'),
('Berapa jumlah pemain sepak bola dalam satu tim?', '9', '10', '11', '12', '13', 'C', 'Olahraga'),
('Apa nama negara berbentuk kepulauan terbesar di dunia?', 'Filipina', 'Jepang', 'Indonesia', 'Malaysia', 'Inggris', 'C', 'Geografi'),
('Hewan apa yang disebut raja hutan?', 'Harimau', 'Singa', 'Beruang', 'Serigala', 'Gajah', 'B', 'Alam'),
('Berapa hari dalam satu tahun kabisat?', '365', '366', '364', '367', '360', 'B', 'Umum'),
('Alat musik petik tradisional Indonesia adalah?', 'Angklung', 'Gamelan', 'Sasando', 'Kolintang', 'Seruling', 'C', 'Budaya'),
('Negara yang disebut negeri Sakura adalah?', 'China', 'Korea', 'Jepang', 'Thailand', 'Vietnam', 'C', 'Geografi'),
('Berapa sisi pada segi delapan?', '6', '7', '8', '9', '10', 'C', 'Matematika'),
('Gas apa yang dihirup manusia untuk bernapas?', 'Karbon Dioksida', 'Nitrogen', 'Oksigen', 'Hidrogen', 'Helium', 'C', 'Sains');

-- =====================================================
-- Sample Data: Demo Match
-- =====================================================
INSERT INTO matches (name, round, status) VALUES 
('Demo Match - Penyisihan 1', 'Penyisihan', 'waiting');

-- Assign 3 teams to demo match
INSERT INTO match_teams (match_id, team_id) VALUES 
(1, 1), -- Tim A
(1, 2), -- Tim B
(1, 3); -- Tim C

-- =====================================================
-- End of Schema
-- =====================================================