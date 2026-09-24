-- ============================================================
-- SQL Seed Script: seed_data.sql
-- Project: COP4331 LAMP Contact Manager
-- Description: Adds development users and contacts to ContactAppDB.
-- Run after create_tables.sql on an empty database.
-- ============================================================

USE `ContactAppDB`;

INSERT INTO `Users` (`FirstName`, `LastName`, `Username`, `Password`, `Role`, `Is_Disabled`) VALUES
('Application', 'Administrator', 'root', '$2y$12$zbQYPTKFqRkOtfKZujRi4.DyPeHaofs3MMNuUYUS1NL5eDTo1mLwS', 'Admin', 0),
('Rick', 'Leinecker', 'RickL', '$2y$12$wlZ0l9FaR3xO03pG4fUALe2h1oaBQpyxu.WALt7MNfZOYojk11i0u', 'User', 0),
('Sam', 'Hill', 'SamH', '$2y$12$tgVfjk1AZTWE7PoZ6fizlOCMLUNd5ah8BEiRPX64d6uHuoDug1sL2', 'User', 0),
('Alex', 'Morgan', 'alex.morgan', '$2y$12$Ff5qQCjNvtyovRR3EfYOVObGvDnBhVISjv1l8oJnhzTHFfHluhgwW', 'User', 0),
('Jamie', 'Nguyen', 'jamie.nguyen', '$2y$12$CCxSsALSVuHsSwdupJy8EeKPPDIVBzRxlKpo5V5mn5mR.ETZwALjS', 'User', 0),
('Riley', 'Johnson', 'riley.johnson', '$2y$12$a69exHc6OFZrBsqAB7jV6.BA1bAWiSxip5vjR9uA5V0B6XRj.XERq', 'User', 0),
('Casey', 'Park', 'casey.park', '$2y$12$DE8WZ0p4iREJ2Wn1X7H1LetFg9eHI02WMytbQnnUKdFYlnUBbO2IO', 'User', 0);

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
