<?php
$like = '%' . $search . '%';
$stmt = $db->prepare(
    'SELECT ID AS id, First_Name AS firstName, Last_Name AS lastName,
            Email AS email, Phone_Number AS phoneNumber
     FROM Contacts
     WHERE User_ID = :user_id
       AND (First_Name LIKE :first_name
            OR Last_Name LIKE :last_name
            OR CONCAT(First_Name, CHAR(32), Last_Name) LIKE :full_name
            OR Email LIKE :email
            OR Phone_Number LIKE :phone)
     ORDER BY Last_Name, First_Name, ID'
);
$stmt->execute([
    ':user_id'    => $userId,
    ':first_name' => $like,
    ':last_name'  => $like,
    ':full_name'  => $like,
    ':email'      => $like,
    ':phone'      => $like,
]);
respond(200, ['contacts' => $stmt->fetchAll()]);
