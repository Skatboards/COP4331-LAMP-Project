<?php
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    respond(400, ['error' => 'Contact ID is required — use ?id=']);
}

$check = $db->prepare(
    'SELECT First_Name AS firstName, Last_Name AS lastName,
            Email AS email, Phone_Number AS phoneNumber
     FROM Contacts
     WHERE ID = :id AND User_ID = :user_id
     LIMIT 1'
);
$check->execute([':id' => $id, ':user_id' => $userId]);
$existing = $check->fetch();
if (!$existing) {
    respond(404, ['error' => 'Contact not found']);
}

// Omitted fields keep their stored values; explicit empty strings can clear optional fields.
$contact = readContactInput(array_merge($existing, getRequestBody()));
$stmt = $db->prepare(
    'UPDATE Contacts
     SET First_Name = :first_name, Last_Name = :last_name,
         Email = :email, Phone_Number = :phone
     WHERE ID = :id AND User_ID = :user_id'
);
$stmt->execute([
    ':first_name' => $contact['firstName'],
    ':last_name'  => $contact['lastName'],
    ':email'      => $contact['email'],
    ':phone'      => $contact['phoneNumber'],
    ':id'         => $id,
    ':user_id'    => $userId,
]);

respond(200, ['message' => 'Contact updated']);
