<?php
$stmt = $db->prepare(
    'SELECT ID AS id, First_Name AS firstName, Last_Name AS lastName,
            Email AS email, Phone_Number AS phoneNumber
     FROM Contacts
     WHERE User_ID = :user_id
     ORDER BY Last_Name, First_Name, ID'
);
$stmt->execute([':user_id' => $userId]);
respond(200, ['contacts' => $stmt->fetchAll()]);
