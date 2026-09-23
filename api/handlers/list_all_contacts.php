<?php
$search = trim($_GET['q'] ?? '');
$like = '%' . $search . '%';

$stmt = $db->prepare(
    'SELECT c.ID AS id, c.First_Name AS firstName, c.Last_Name AS lastName,
            c.Email AS email, c.Phone_Number AS phoneNumber,
            u.ID AS userId, u.Username AS username
     FROM Contacts c
     INNER JOIN Users u ON u.ID = c.User_ID
    WHERE (:search = :empty_search OR c.First_Name LIKE :first_name
            OR c.Last_Name LIKE :last_name
            OR CONCAT(c.First_Name, CHAR(32), c.Last_Name) LIKE :full_name
            OR c.Email LIKE :email
            OR c.Phone_Number LIKE :phone
            OR u.Username LIKE :username)
     ORDER BY c.Last_Name, c.First_Name, c.ID'
);
$stmt->execute([
    ':search' => $search,
    ':empty_search' => '',
    ':first_name' => $like,
    ':last_name' => $like,
    ':full_name' => $like,
    ':email' => $like,
    ':phone' => $like,
    ':username' => $like,
]);

respond(200, ['contacts' => $stmt->fetchAll()]);
