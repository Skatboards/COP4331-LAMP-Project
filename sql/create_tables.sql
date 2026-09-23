-- ============================================================
-- SQL Schema Script: create_tables.sql
-- Project: COP4331 LAMP Contact Manager
-- Description: Creates the contact database and secure auth tables.
-- This initializer preserves existing tables and data.
-- ============================================================

-- 1. Create and select the database
CREATE DATABASE IF NOT EXISTS `ContactAppDB`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `ContactAppDB`;

-- 2. Create Users Table
CREATE TABLE IF NOT EXISTS `Users` (
    `ID` INT NOT NULL AUTO_INCREMENT,
    `FirstName` VARCHAR(50) NOT NULL DEFAULT '',
    `LastName` VARCHAR(50) NOT NULL DEFAULT '',
    `Username` VARCHAR(50) NOT NULL DEFAULT '',
    `Password` VARCHAR(255) NOT NULL DEFAULT '',
    `Role` VARCHAR(20) NOT NULL DEFAULT 'User',
    `Is_Disabled` TINYINT(1) NOT NULL DEFAULT 0,
    `Date_Created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `Date_Updated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    UNIQUE KEY `uq_users_username` (`Username`),
    INDEX `idx_users_role` (`Role`),
    INDEX `idx_users_disabled` (`Is_Disabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create opaque bearer-token storage
CREATE TABLE IF NOT EXISTS `User_Sessions` (
    `ID` INT NOT NULL AUTO_INCREMENT,
    `User_ID` INT NOT NULL,
    `Token_Hash` CHAR(64) NOT NULL,
    `Expires_At` DATETIME NOT NULL,
    PRIMARY KEY (`ID`),
    UNIQUE KEY `uq_user_sessions_token_hash` (`Token_Hash`),
    INDEX `idx_user_sessions_userid` (`User_ID`),
    FOREIGN KEY (`User_ID`) REFERENCES `Users`(`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create Contacts Table
CREATE TABLE IF NOT EXISTS `Contacts` (
    `ID` INT NOT NULL AUTO_INCREMENT,
    `First_Name` VARCHAR(50) NOT NULL DEFAULT '',
    `Last_Name` VARCHAR(50) NOT NULL DEFAULT '',
    `Email` VARCHAR(50) NOT NULL DEFAULT '',
    `Phone_Number` VARCHAR(20) NOT NULL DEFAULT '',
    `Date_Created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `Date_Updated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `User_ID` INT NOT NULL,
    PRIMARY KEY (`ID`),
    INDEX `idx_contacts_userid` (`User_ID`),
    FOREIGN KEY (`User_ID`) REFERENCES `Users`(`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create Application Database User & Grant Permissions
CREATE USER IF NOT EXISTS 'ContactAppUser'@'localhost' IDENTIFIED BY 'WeLoveCOP4331!';
GRANT ALL PRIVILEGES ON `ContactAppDB`.* TO 'ContactAppUser'@'localhost';

CREATE USER IF NOT EXISTS 'ContactAppUser'@'%' IDENTIFIED BY 'WeLoveCOP4331!';
GRANT ALL PRIVILEGES ON `ContactAppDB`.* TO 'ContactAppUser'@'%';

FLUSH PRIVILEGES;
