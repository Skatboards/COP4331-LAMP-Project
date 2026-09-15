-- ============================================================
-- SQL Seed Script: seed_data.sql
-- Project: COP4331 LAMP Stack Demo (Colors Manager)
-- Description: Populates ColorsAppDB with initial Users & Colors.
-- ============================================================

USE `ColorsAppDB`;

-- 1. Seed Sample Users
-- User 1: Rick Leinecker (Plaintext password for demonstration / testing)
INSERT INTO `Users` (`FirstName`, `LastName`, `Login`, `Password`) 
VALUES ('Rick', 'Leinecker', 'RickL', 'COP4331');

-- User 2: Sam Hill
INSERT INTO `Users` (`FirstName`, `LastName`, `Login`, `Password`) 
VALUES ('Sam', 'Hill', 'SamH', 'Test');

-- User 3: Rick Leinecker (MD5 hashed password example)
INSERT INTO `Users` (`FirstName`, `LastName`, `Login`, `Password`) 
VALUES ('Rick', 'Leinecker', 'RickL_MD5', '5832a71366768098cceb7095efb774f2');

-- User 4: Sam Hill (MD5 hashed password example)
INSERT INTO `Users` (`FirstName`, `LastName`, `Login`, `Password`) 
VALUES ('Sam', 'Hill', 'SamH_MD5', '0cbc6611f5540bd0809a388dc95a615b');


-- 2. Seed Initial Palette Colors for User ID 1 (RickL)
INSERT INTO `Colors` (`Name`, `UserID`) VALUES 
('Blue', 1),
('White', 1),
('Black', 1),
('Magenta', 1),
('Yellow', 1),
('Cyan', 1),
('Salmon', 1),
('Chartreuse', 1),
('Lime', 1),
('Light Blue', 1),
('Light Gray', 1),
('Light Red', 1),
('Light Green', 1),
('Chiffon', 1),
('Fuscia', 1),
('Brown', 1),
('Beige', 1);

-- 3. Seed Initial Palette Colors for User ID 3 (RickL_MD5)
INSERT INTO `Colors` (`Name`, `UserID`) VALUES 
('Blue', 3),
('White', 3),
('Black', 3),
('Gray', 3),
('Magenta', 3),
('Yellow', 3),
('Cyan', 3),
('Salmon', 3),
('Chartreuse', 3),
('Lime', 3),
('Light Blue', 3),
('Light Gray', 3),
('Light Red', 3),
('Light Green', 3),
('Chiffon', 3),
('Fuscia', 3),
('Brown', 3),
('Beige', 3);
