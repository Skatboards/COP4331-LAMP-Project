-- ============================================================
-- SQL Full Reset Script: resetdb.sql
-- Project: COP4331 LAMP Stack Demo (Colors Manager)
-- Description: Drops existing tables if present, recreates schema,
--              seeds users and colors, and sets up user permissions.
-- ============================================================

-- Create and select database
CREATE DATABASE IF NOT EXISTS `ContactAppDB`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `ContactAppDB`;

-- Drop existing tables to ensure a clean state
DROP TABLE IF EXISTS `Contacts`;
DROP TABLE IF EXISTS `Users`;

-- Create Users Table
CREATE TABLE `Users` (
    `ID` INT NOT NULL AUTO_INCREMENT,
    `FirstName` VARCHAR(50) NOT NULL DEFAULT '',
    `LastName` VARCHAR(50) NOT NULL DEFAULT '',
    `Username` VARCHAR(50) NOT NULL DEFAULT '',
    `Password` VARCHAR(50) NOT NULL DEFAULT '',
    `Date_Created` DATETIME NOT NULL DEFAULT 19700101,
    `Date_Updated` DATETIME NOT NULL DEFAULT 19700101,
    PRIMARY KEY (`ID`),
    INDEX `idx_users_login` (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Colors Table
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
    INDEX `idx_colors_userid` (`User_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Sample Users
INSERT INTO `Users` (`FirstName`, `LastName`, `Username`, `Password`) VALUES
('Rick', 'Leinecker', 'RickL', 'COP4331'),
('Sam', 'Hill', 'SamH', 'Test'),
('Rick', 'Leinecker', 'RickL_MD5', '5832a71366768098cceb7095efb774f2'),
('Sam', 'Hill', 'SamH_MD5', '0cbc6611f5540bd0809a388dc95a615b');

-- Seed Sample Colors for User 1 (RickL)
INSERT INTO `Contacts` (`First_Name`, `Last_Name`, `User_ID`) VALUES
('Rick', 'Leinecker', 2),
('Sam', 'Hill', 1);

-- Create Application Database User & Privileges
CREATE USER IF NOT EXISTS 'ContactAppUser'@'localhost' IDENTIFIED BY 'WeLoveCOP4331!';
GRANT ALL PRIVILEGES ON `ContactAppDB`.* TO 'ContactAppUser'@'localhost';

CREATE USER IF NOT EXISTS 'ContactAppUser'@'%' IDENTIFIED BY 'WeLoveCOP4331!';
GRANT ALL PRIVILEGES ON `ContactAppDB`.* TO 'ContactAppUser'@'%';

FLUSH PRIVILEGES;
