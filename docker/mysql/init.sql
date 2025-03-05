DROP USER IF EXISTS 'test_user'@'localhost';
CREATE USER 'test_user'@'localhost' IDENTIFIED BY 'test_password';
GRANT ALL PRIVILEGES ON testing_db.* TO 'test_user'@'localhost';
FLUSH PRIVILEGES;
