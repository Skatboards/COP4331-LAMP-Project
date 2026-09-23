<?php
$sourceId = filter_var($body['contactId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$sourceId) {
    respond(400, ['error' => 'contactId must be a positive integer']);
}

$source = $db->prepare(
    'SELECT First_Name AS firstName, Last_Name AS lastName,
            Email AS email, Phone_Number AS phoneNumber
     FROM Contacts
     WHERE ID = :id
     LIMIT 1'
);
$source->execute([':id' => $sourceId]);
$contact = $source->fetch();
if (!$contact) {
    respond(404, ['error' => 'Directory contact not found']);
}

$insert = $db->prepare(
    'INSERT INTO Contacts (First_Name, Last_Name, Email, Phone_Number, User_ID)
     VALUES (:first_name, :last_name, :email, :phone, :user_id)'
);
$insert->execute([
    ':first_name' => $contact['firstName'],
    ':last_name' => $contact['lastName'],
    ':email' => $contact['email'],
    ':phone' => $contact['phoneNumber'],
    ':user_id' => $userId,
]);

respond(201, ['message' => 'Contact added to your list', 'id' => (int)$db->lastInsertId()]);
