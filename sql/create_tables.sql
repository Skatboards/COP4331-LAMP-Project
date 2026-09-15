-- ============================================================
-- SQL Schema Script: create_tables.sql
-- Project: COP4331 LAMP Stack Demo (Colors Manager)
-- Description: Creates the ColorsAppDB database, Users table,
--              Colors table, and grants user permissions.
-- ============================================================

-- 1. Create and select the database
CREATE DATABASE IF NOT EXISTS `ColorsAppDB`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `ColorsAppDB`;

-- 2. Create Users Table
CREATE TABLE IF NOT EXISTS `Users` (
    `ID` INT NOT NULL AUTO_INCREMENT,
    `FirstName` VARCHAR(50) NOT NULL DEFAULT '',
    `LastName` VARCHAR(50) NOT NULL DEFAULT '',
    `Login` VARCHAR(50) NOT NULL DEFAULT '',
    `Password` VARCHAR(50) NOT NULL DEFAULT '',
    PRIMARY KEY (`ID`),
    INDEX `idx_users_login` (`Login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create Colors Table
CREATE TABLE IF NOT EXISTS `Colors` (
    `ID` INT NOT NULL AUTO_INCREMENT,
    `Name` VARCHAR(50) NOT NULL DEFAULT '',
    `UserID` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`ID`),
    INDEX `idx_colors_userid` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create Application Database User & Grant Permissions
-- Note: Replace password if desired for custom deployments.
CREATE USER IF NOT EXISTS 'ColorsAppUser'@'localhost' IDENTIFIED BY 'WeLoveCOP4331!';
GRANT ALL PRIVILEGES ON `ColorsAppDB`.* TO 'ColorsAppUser'@'localhost';

-- Also allow connection from any host (useful for Docker containerization)
CREATE USER IF NOT EXISTS 'ColorsAppUser'@'%' IDENTIFIED BY 'WeLoveCOP4331!';
GRANT ALL PRIVILEGES ON `ColorsAppDB`.* TO 'ColorsAppUser'@'%';

FLUSH PRIVILEGES;
