# Codex CBT System

## Setup
1. Create database `codexcbt` in MySQL.
2. Import the schema: `database/schema.sql`.
3. If upgrading an existing database, run migrations in `database/migrations`.
4. Update DB credentials in `connections/config.php` if needed.
5. Open `http://localhost/cbt` in your browser.

## Default Logins
- Admin: `admin@codexcbt.local` / `admin123`
- Student: `student@codexcbt.local` / `student123`

## Notes
- Exams can be assigned to classes from Admin -> Assignments.
- For fixed exams, select questions in Admin -> Exams -> Questions.
- Essay questions require grading in Admin -> Results.
- Admin roles: Super Admin (full), Exam Manager, Result Manager, Viewer.

## How to Refer to This Project in Future Sessions
- "Open `c:\xampp\htdocs\codexCbt` and continue the Codex CBT project."
- "Work on the Codex CBT system in `c:\xampp\htdocs\codexCbt`."
