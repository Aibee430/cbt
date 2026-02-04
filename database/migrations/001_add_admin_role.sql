ALTER TABLE admin_users
    ADD COLUMN role ENUM('super_admin','exam_manager','result_manager','viewer') NOT NULL DEFAULT 'super_admin';

UPDATE admin_users SET role='super_admin' WHERE role IS NULL;
