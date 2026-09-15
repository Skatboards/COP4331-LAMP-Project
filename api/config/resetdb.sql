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
    INDEX `idx_users_username` (`Username`)
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
('Rick', 'Leinecker', 'RickL', 'COP4331'),
('Sam', 'Hill', 'SamH', 'Test'),
('Rick', 'Leinecker', 'RickL_MD5', '5832a71366768098cceb7095efb774f2'),
('Sam', 'Hill', 'SamH_MD5', '0cbc6611f5540bd0809a388dc95a615b'),
('Alex', 'Morgan', 'alex.morgan', 'alex123'),
('Jamie', 'Nguyen', 'jamie.nguyen', 'jamie123'),
('Riley', 'Johnson', 'riley.johnson', 'riley123'),
('Casey', 'Park', 'casey.park', 'casey123');

-- Seed sample contacts. User IDs 1–4 are the original test accounts; 5–8
-- are the additional accounts above. Test passwords are listed with Users.
INSERT INTO `Contacts` (`First_Name`, `Last_Name`, `Email`, `Phone_Number`, `User_ID`) VALUES
('Rick', 'Leinecker', 'rick.leinecker@example.test', '4075550101', 2),
('Sam', 'Hill', 'sam.hill@example.test', '4075550102', 1),
('Avery', 'Stone', 'avery.stone@example.test', '4075550103', 5),
('Taylor', 'Brooks', 'taylor.brooks@example.test', '4075550104', 5),
('Casey', 'Quinn', 'casey.quinn@example.test', '4075550105', 5),
('Jordan', 'Lee', 'jordan.lee@example.test', '4075550106', 6),
('Morgan', 'Chen', 'morgan.chen@example.test', '4075550107', 6),
('Skyler', 'Patel', 'skyler.patel@example.test', '4075550108', 6),
('Alex', 'Rivera', 'alex.rivera@example.test', '4075550109', 7),
('Quinn', 'Davis', 'quinn.davis@example.test', '4075550110', 7),
('Samira', 'Khan', 'samira.khan@example.test', '4075550111', 7),
('Drew', 'Wilson', 'drew.wilson@example.test', '4075550112', 8),
('Avery', 'Chen', 'avery.chen@example.test', '4075550113', 8),
('Robin', 'Taylor', 'robin.taylor@example.test', '4075550114', 8);

-- Create Application Database User & Privileges
CREATE USER IF NOT EXISTS 'ContactAppUser'@'localhost' IDENTIFIED BY 'WeLoveCOP4331!';
GRANT ALL PRIVILEGES ON `ContactAppDB`.* TO 'ContactAppUser'@'localhost';

CREATE USER IF NOT EXISTS 'ContactAppUser'@'%' IDENTIFIED BY 'WeLoveCOP4331!';
GRANT ALL PRIVILEGES ON `ContactAppDB`.* TO 'ContactAppUser'@'%';

FLUSH PRIVILEGES;
