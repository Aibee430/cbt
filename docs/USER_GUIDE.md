# Codex CBT - User Guide

## Overview
Codex CBT is a web-based Computer-Based Test system built with PHP, MySQL, and Bootstrap. It supports MCQ, Fill-in-the-Blank, and Essay question types with flexible scheduling and result release control.

## Setup
1. Create MySQL database: `codexcbt`.
2. Import schema: `database/schema.sql`.
3. If upgrading an existing install, run SQL in `database/migrations`.
4. Update credentials in `connections/config.php`.
5. Open `http://localhost/cbt`.

## Default Logins
- Admin: `admin@codexcbt.local` / `admin123`
- Student: `student@codexcbt.local` / `student123`

## Roles and Permissions
- Super Admin: full access (includes admin user management).
- Exam Manager: classes, students, subjects, questions, exams, assignments.
- Result Manager: view results, grade essays.
- Viewer: dashboard only.

Assign roles in Admin -> Admins.

## Admin Workflow (Recommended Order)
1. Create Classes (Admin -> Classes).
2. Add Students (Admin -> Students) or use Bulk Upload.
3. Create Subjects (Admin -> Subjects).
4. Add Questions (Admin -> Questions) or use Bulk Upload.
5. Create Exams (Admin -> Exams).
6. Assign Exams to Classes (Admin -> Assignments).
7. For fixed exams, select questions in Admin -> Exams -> Questions.

## Bulk Upload Questions (CSV)
1. Go to Admin -> Questions.
2. Download the CSV template and fill it.
3. Upload the CSV in the Bulk Upload section.
4. Use `question_type` values: `mcq`, `fill`, or `essay`.
5. For MCQ, provide options A-D and `correct_option` (A-D or 1-4).

## Bulk Upload Students (CSV)
1. Go to Admin -> Students.
2. Download the student CSV template and fill it.
3. Upload the CSV in the Student Actions panel.
4. Required columns: `full_name`, `reg_no`, `email`, and `class_name` or `class_id`.

## Student Workflow
1. Log in with Reg No or Email.
2. Start assigned exams when open.
3. Submit before timer ends.
4. View results in Student -> Results (depending on release policy).

## Results and Grading
- MCQ and Fill questions are auto-graded.
- Essay answers require manual grading in Admin -> Results -> Grade/View.
- Results can be released immediately or after a release date.

## Exporting Results
- Go to Admin -> Results.
- Filter by exam if needed.
- Use Export CSV or Export PDF.

## Troubleshooting
- If login fails, confirm the DB import completed and credentials are correct.
- If results show "Pending grading", grade essays and save.
- If exams do not appear, confirm they are assigned to the student class and within the time window.
