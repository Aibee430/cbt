-- Codex CBT Schema

CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin','exam_manager','result_manager','viewer') NOT NULL DEFAULT 'super_admin',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    reg_no VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_students_class FOREIGN KEY (class_id) REFERENCES classes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    code VARCHAR(40) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    class_id INT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('mcq','fill','essay') NOT NULL,
    correct_answer TEXT NULL,
    marks INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_questions_subject FOREIGN KEY (subject_id) REFERENCES subjects(id),
    CONSTRAINT fk_questions_class FOREIGN KEY (class_id) REFERENCES classes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE question_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    option_text TEXT NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_options_question FOREIGN KEY (question_id) REFERENCES questions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    subject_id INT NOT NULL,
    instructions TEXT NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    duration_minutes INT NOT NULL,
    question_count INT NOT NULL,
    randomize TINYINT(1) NOT NULL DEFAULT 1,
    allow_multiple_attempts TINYINT(1) NOT NULL DEFAULT 0,
    max_attempts INT NOT NULL DEFAULT 1,
    show_result ENUM('immediate','after_release') NOT NULL DEFAULT 'after_release',
    result_release_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_exams_subject FOREIGN KEY (subject_id) REFERENCES subjects(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_id INT NOT NULL,
    UNIQUE KEY uq_exam_question (exam_id, question_id),
    CONSTRAINT fk_exam_questions_exam FOREIGN KEY (exam_id) REFERENCES exams(id),
    CONSTRAINT fk_exam_questions_question FOREIGN KEY (question_id) REFERENCES questions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    class_id INT NOT NULL,
    UNIQUE KEY uq_exam_class (exam_id, class_id),
    CONSTRAINT fk_exam_assign_exam FOREIGN KEY (exam_id) REFERENCES exams(id),
    CONSTRAINT fk_exam_assign_class FOREIGN KEY (class_id) REFERENCES classes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    attempt_number INT NOT NULL DEFAULT 1,
    started_at DATETIME NOT NULL,
    submitted_at DATETIME NULL,
    status ENUM('in_progress','submitted','graded') NOT NULL DEFAULT 'in_progress',
    score DECIMAL(8,2) NOT NULL DEFAULT 0,
    total_marks DECIMAL(8,2) NOT NULL DEFAULT 0,
    CONSTRAINT fk_attempts_exam FOREIGN KEY (exam_id) REFERENCES exams(id),
    CONSTRAINT fk_attempts_student FOREIGN KEY (student_id) REFERENCES students(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_attempt_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    UNIQUE KEY uq_attempt_question (attempt_id, question_id),
    CONSTRAINT fk_attempt_questions_attempt FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id),
    CONSTRAINT fk_attempt_questions_question FOREIGN KEY (question_id) REFERENCES questions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_option_id INT NULL,
    answer_text TEXT NULL,
    is_correct TINYINT(1) NULL,
    marks_awarded DECIMAL(8,2) NULL,
    graded_by INT NULL,
    graded_at DATETIME NULL,
    CONSTRAINT fk_answers_attempt FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id),
    CONSTRAINT fk_answers_question FOREIGN KEY (question_id) REFERENCES questions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed data
INSERT INTO admin_users (name, email, password_hash, role)
VALUES ('Admin User', 'admin@codexcbt.local', '$2y$10$nfF6/ATPVeBVWseoh/WC1eCh5g.XjU9XamU0vNK1nNheJhw6XEG6m', 'super_admin');

INSERT INTO classes (name) VALUES ('Class A');

INSERT INTO students (class_id, full_name, reg_no, email, password_hash)
VALUES (1, 'Student One', 'CBT001', 'student@codexcbt.local', '$2y$10$tJar65QqAMYZSwCnKRB65em.2fw2GSSIoFBNd5nb9X.P.WcYgFkny');

INSERT INTO subjects (name, code) VALUES ('General Knowledge', 'GK');

INSERT INTO questions (id, subject_id, class_id, question_text, question_type, correct_answer, marks)
VALUES
(1, 1, 1, 'Which color is the sky on a clear day?', 'mcq', NULL, 1),
(2, 1, 1, 'The capital of Nigeria is _____.', 'fill', 'Abuja', 2),
(3, 1, 1, 'Explain why time management matters during exams.', 'essay', NULL, 5);

INSERT INTO question_options (question_id, option_text, is_correct)
VALUES
(1, 'Blue', 1),
(1, 'Green', 0),
(1, 'Red', 0),
(1, 'Yellow', 0);

INSERT INTO exams (id, title, subject_id, instructions, start_at, end_at, duration_minutes, question_count, randomize, allow_multiple_attempts, max_attempts, show_result)
VALUES
(1, 'Sample CBT Test', 1, 'Answer all questions. Essay will be graded later.', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 20, 3, 1, 0, 1, 'immediate');

INSERT INTO exam_assignments (exam_id, class_id) VALUES (1, 1);
