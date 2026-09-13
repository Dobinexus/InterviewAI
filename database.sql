CREATE DATABASE IF NOT EXISTS interview_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE interview_ai;

DROP TABLE IF EXISTS interview_responses;
DROP TABLE IF EXISTS interview_questions;
DROP TABLE IF EXISTS interviews;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('INTERVIEWER','ADMIN') NOT NULL DEFAULT 'INTERVIEWER',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE interviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  system_type VARCHAR(180) NOT NULL,
  interviewee_role VARCHAR(100) NOT NULL,
  title VARCHAR(200) NULL,
  focus TEXT NULL,
  status ENUM('DRAFT','PREPARED','IN_PROGRESS','COMPLETED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE interview_questions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  interview_id INT UNSIGNED NOT NULL,
  category VARCHAR(80) NOT NULL,
  question TEXT NOT NULL,
  priority ENUM('CRITICAL','IMPORTANT','RECOMMENDED','OPTIONAL') NOT NULL DEFAULT 'RECOMMENDED',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_questions_interview FOREIGN KEY (interview_id) REFERENCES interviews(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE interview_responses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  interview_id INT UNSIGNED NOT NULL,
  speaker ENUM('INTERVIEWER','INTERVIEWEE') NOT NULL,
  response_text TEXT NOT NULL,
  captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_responses_interview FOREIGN KEY (interview_id) REFERENCES interviews(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Demo account:
-- Email: demo@interviewai.local
-- Password: password
-- The hash below is a PHP password_hash() value for the demo password.
INSERT INTO users(name,email,password_hash,role)
VALUES ('Demo Interviewer','demo@interviewai.local',
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaX3t7a3Xh5P0X7M5y8s6pW9G4K',
'INTERVIEWER');
