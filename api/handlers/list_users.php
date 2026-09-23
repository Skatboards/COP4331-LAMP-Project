<?php
$search = trim($_GET['q'] ?? '');
$like = '%' . $search . '%';

$stmt = $db->prepare(
    'SELECT ID AS id, FirstName AS firstName, LastName AS lastName,
            Username AS username, Role AS role, Is_Disabled AS isDisabled
     FROM Users
    WHERE (:search = :empty_search OR FirstName LIKE :first_name
            OR LastName LIKE :last_name
            OR Username LIKE :username)
     ORDER BY LastName, FirstName, ID'
);
$stmt->execute([
    ':search' => $search,
    ':empty_search' => '',
    ':first_name' => $like,
    ':last_name' => $like,
    ':username' => $like,
]);

respond(200, ['users' => $stmt->fetchAll()]);
