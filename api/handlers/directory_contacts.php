<?php
$search = trim($_GET['q'] ?? '');
$like = '%' . $search . '%';

$stmt = $db->prepare(
    "SELECT 'contact' AS directoryType, ID AS sourceId,
            First_Name AS firstName, Last_Name AS lastName,
            Email AS email, Phone_Number AS phoneNumber,
            NULL AS username
     FROM Contacts
     WHERE (:search = :empty_search OR First_Name LIKE :first_name
            OR Last_Name LIKE :last_name
            OR CONCAT(First_Name, CHAR(32), Last_Name) LIKE :full_name
            OR Email LIKE :email
            OR Phone_Number LIKE :phone)
         UNION ALL
         SELECT 'user' AS directoryType, ID AS sourceId,
             FirstName AS firstName, LastName AS lastName,
             '' AS email, '' AS phoneNumber, Username AS username
         FROM Users
         WHERE (:user_search = :user_empty_search OR FirstName LIKE :user_first_name
             OR LastName LIKE :user_last_name
             OR Username LIKE :user_username)
         ORDER BY lastName, firstName, sourceId"
);
$stmt->execute([
    ':search' => $search,
    ':empty_search' => '',
    ':first_name' => $like,
    ':last_name' => $like,
    ':full_name' => $like,
    ':email' => $like,
    ':phone' => $like,
    ':user_search' => $search,
    ':user_empty_search' => '',
    ':user_first_name' => $like,
    ':user_last_name' => $like,
    ':user_username' => $like,
]);

respond(200, ['contacts' => $stmt->fetchAll()]);
