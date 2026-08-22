-- App-User: nur SELECT/INSERT/UPDATE/DELETE auf die App-Datenbank
CREATE USER IF NOT EXISTS 'app_user'@'%' IDENTIFIED BY 'changeme_app';
GRANT SELECT, INSERT, UPDATE, DELETE ON artbook.* TO 'app_user'@'%';

-- Readonly-User: z.B. für Reporting oder externe Tools
CREATE USER IF NOT EXISTS 'readonly_user'@'%' IDENTIFIED BY 'changeme_readonly';
GRANT SELECT ON artbook.* TO 'readonly_user'@'%';

FLUSH PRIVILEGES;
