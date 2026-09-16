<?php
$id = (int) $_GET['id'];
if ($id <= 0) {
    respond(400, ['error' => 'Contact ID must be a positive integer']);
}

$stmt = $db->prepare(
    'SELECT ID AS id, First_Name AS firstName, Last_Name AS lastName,
            Email AS email, Phone_Number AS phoneNumber
     FROM Contacts
     WHERE ID = :id AND User_ID = :user_id
     LIMIT 1'
);
$stmt->execute([':id' => $id, ':user_id' => $userId]);
$contact = $stmt->fetch();
if (!$contact) {
    respond(404, ['error' => 'Contact not found']);
}
respond(200, $contact);
