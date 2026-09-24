<?php
$sourceType = $body['sourceType'] ?? 'contact';
$sourceId = filter_var($body['sourceId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$sourceId) {
    respond(400, ['error' => 'sourceId must be a positive integer']);
}
if (!in_array($sourceType, ['contact', 'user'], true)) {
    respond(400, ['error' => 'sourceType must be contact or user']);
}

$source = $db->prepare($sourceType === 'user'
    ? "SELECT FirstName AS firstName, LastName AS lastName,
              '' AS email, '' AS phoneNumber
    FROM Users WHERE ID = :id LIMIT 1"
    : 'SELECT First_Name AS firstName, Last_Name AS lastName,
              Email AS email, Phone_Number AS phoneNumber
       FROM Contacts WHERE ID = :id LIMIT 1');
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
