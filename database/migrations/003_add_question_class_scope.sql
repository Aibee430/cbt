ALTER TABLE questions
    ADD COLUMN class_id INT NULL AFTER subject_id,
    ADD CONSTRAINT fk_questions_class FOREIGN KEY (class_id) REFERENCES classes(id);

CREATE INDEX idx_questions_subject_class ON questions (subject_id, class_id);
