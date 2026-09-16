<?php
$contact = readContactInput($body);
$stmt = $db->prepare(
    'INSERT INTO Contacts (First_Name, Last_Name, Email, Phone_Number, User_ID)
     VALUES (:first_name, :last_name, :email, :phone, :user_id)'
);
$stmt->execute([
    ':first_name' => $contact['firstName'],
    ':last_name'  => $contact['lastName'],
    ':email'      => $contact['email'],
    ':phone'      => $contact['phoneNumber'],
    ':user_id'    => $userId,
]);

respond(201, ['message' => 'Contact created', 'id' => (int) $db->lastInsertId()]);
