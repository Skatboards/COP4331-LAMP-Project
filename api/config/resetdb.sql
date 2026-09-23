-- ============================================================
-- SQL Full Reset Script: resetdb.sql
-- Project: COP4331 LAMP Stack Demo (Contact Manager)
-- Description: Drops existing tables if present, recreates schema,
--              seeds users and contacts, and sets up user permissions.
-- ============================================================

-- Create and select database
CREATE DATABASE IF NOT EXISTS `ContactAppDB`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `ContactAppDB`;

-- Drop existing tables to ensure a clean state
DROP TABLE IF EXISTS `Contacts`;
DROP TABLE IF EXISTS `User_Sessions`;
DROP TABLE IF EXISTS `Users`;

-- Create Users Table
CREATE TABLE `Users` (
    `ID` INT NOT NULL AUTO_INCREMENT,
    `FirstName` VARCHAR(50) NOT NULL DEFAULT '',
    `LastName` VARCHAR(50) NOT NULL DEFAULT '',
    `Username` VARCHAR(50) NOT NULL DEFAULT '',
    `Password` VARCHAR(255) NOT NULL DEFAULT '',
    `Role` VARCHAR(20) NOT NULL DEFAULT 'User',
    `Is_Disabled` TINYINT(1) NOT NULL DEFAULT 0,
    `Date_Created` DATETIME NOT NULL DEFAULT 19700101,
    `Date_Updated` DATETIME NOT NULL DEFAULT 19700101,
    PRIMARY KEY (`ID`),
    INDEX `idx_users_username` (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `User_Sessions` (
    `ID` INT NOT NULL AUTO_INCREMENT,
    `User_ID` INT NOT NULL,
    `Token_Hash` CHAR(64) NOT NULL,
    `Expires_At` DATETIME NOT NULL,
    PRIMARY KEY (`ID`),
    UNIQUE KEY `uq_user_sessions_token_hash` (`Token_Hash`),
    INDEX `idx_user_sessions_userid` (`User_ID`),
    FOREIGN KEY (`User_ID`) REFERENCES Users(`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Contacts Table
CREATE TABLE `Contacts` (
    `ID` INT NOT NULL AUTO_INCREMENT,
    `First_Name` VARCHAR(50) NOT NULL DEFAULT '',
    `Last_Name` VARCHAR(50) NOT NULL DEFAULT '',
    `Email` VARCHAR(50) NOT NULL DEFAULT '',
    `Phone_Number` VARCHAR(20) NOT NULL DEFAULT '',
    `Date_Created` DATETIME NOT NULL DEFAULT 19700101,
    `Date_Updated` DATETIME NOT NULL DEFAULT 19700101,
    `User_ID` INT NOT NULL,
    PRIMARY KEY (`ID`),
    FOREIGN KEY (`User_ID`) REFERENCES Users(`ID`),
    INDEX `idx_contacts_userid` (`User_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Sample Users
INSERT INTO `Users` (`FirstName`, `LastName`, `Username`, `Password`) VALUES
('Application', 'Administrator', 'root', '$2y$12$zbQYPTKFqRkOtfKZujRi4.DyPeHaofs3MMNuUYUS1NL5eDTo1mLwS', 'Admin', 0),
('Rick', 'Leinecker', 'RickL', '$2y$12$wlZ0l9FaR3xO03pG4fUALe2h1oaBQpyxu.WALt7MNfZOYojk11i0u', 'User', 0),
('Sam', 'Hill', 'SamH', '$2y$12$tgVfjk1AZTWE7PoZ6fizlOCMLUNd5ah8BEiRPX64d6uHuoDug1sL2', 'User', 0),
('Alex', 'Morgan', 'alex.morgan', '$2y$12$Ff5qQCjNvtyovRR3EfYOVObGvDnBhVISjv1l8oJnhzTHFfHluhgwW', 'User', 0),
('Jamie', 'Nguyen', 'jamie.nguyen', '$2y$12$CCxSsALSVuHsSwdupJy8EeKPPDIVBzRxlKpo5V5mn5mR.ETZwALjS', 'User', 0),
('Riley', 'Johnson', 'riley.johnson', '$2y$12$a69exHc6OFZrBsqAB7jV6.BA1bAWiSxip5vjR9uA5V0B6XRj.XERq', 'User', 0),
('Casey', 'Park', 'casey.park', '$2y$12$DE8WZ0p4iREJ2Wn1X7H1LetFg9eHI02WMytbQnnUKdFYlnUBbO2IO', 'User', 0);

-- Seed sample contacts. User IDs 1–4 are the original test accounts; 5–8
-- are the additional accounts above. Test passwords are listed with Users.
INSERT INTO `Contacts` (`First_Name`, `Last_Name`, `Email`, `Phone_Number`, `User_ID`) VALUES
('Rick', 'Leinecker', 'rick.leinecker@example.test', '4075550101', 2),
('Sam', 'Hill', 'sam.hill@example.test', '4075550102', 3),
('Avery', 'Stone', 'avery.stone@example.test', '4075550103', 4),
('Taylor', 'Brooks', 'taylor.brooks@example.test', '4075550104', 4),
('Casey', 'Quinn', 'casey.quinn@example.test', '4075550105', 4),
('Jordan', 'Lee', 'jordan.lee@example.test', '4075550106', 5),
('Morgan', 'Chen', 'morgan.chen@example.test', '4075550107', 5),
('Skyler', 'Patel', 'skyler.patel@example.test', '4075550108', 5),
('Alex', 'Rivera', 'alex.rivera@example.test', '4075550109', 6),
('Quinn', 'Davis', 'quinn.davis@example.test', '4075550110', 6),
('Samira', 'Khan', 'samira.khan@example.test', '4075550111', 6),
('Drew', 'Wilson', 'drew.wilson@example.test', '4075550112', 7),
('Avery', 'Chen', 'avery.chen@example.test', '4075550113', 7),
('Robin', 'Taylor', 'robin.taylor@example.test', '4075550114', 7);

-- Create Application Database User & Privileges
CREATE USER IF NOT EXISTS 'ContactAppUser'@'localhost' IDENTIFIED BY 'WeLoveCOP4331!';
GRANT ALL PRIVILEGES ON `ContactAppDB`.* TO 'ContactAppUser'@'localhost';

CREATE USER IF NOT EXISTS 'ContactAppUser'@'%' IDENTIFIED BY 'WeLoveCOP4331!';
GRANT ALL PRIVILEGES ON `ContactAppDB`.* TO 'ContactAppUser'@'%';

FLUSH PRIVILEGES;
