<?php
// ============================================================
//  api/index.php — Contact Manager REST API
//
//  GET    /api/index.php?ping=1    — status ping
//  POST   /api/index.php (login)   — authenticate with username/password JSON
//  GET    /api/index.php           — list the caller's contacts
//  GET    /api/index.php?q=term    — search the caller's contacts
//  GET    /api/index.php?id=1      — get one contact
//  POST   /api/index.php (contact) — create a contact
//  PUT    /api/index.php?id=1      — replace a contact
//  DELETE /api/index.php?id=1      — delete a contact
// ============================================================

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';

/**
 * Validates and normalizes a full contact representation from JSON/form data.
 * First or last name is required; email and phone are optional.
 */
function readContactInput($body) {
    $fields = [
        'firstName'  => ['max' => 50],
        'lastName'   => ['max' => 50],
        'email'      => ['max' => 50],
        'phoneNumber'=> ['max' => 20],
    ];
    $contact = [];

    foreach ($fields as $field => $rules) {
        $value = $body[$field] ?? '';
        if (!is_string($value)) {
            respond(400, ['error' => $field . ' must be a string']);
        }

        $value = clean($value);
        if (strlen($value) > $rules['max']) {
            respond(400, ['error' => $field . ' must be ' . $rules['max'] . ' characters or fewer']);
        }
        $contact[$field] = $value;
    }

    if ($contact['firstName'] === '' && $contact['lastName'] === '') {
        respond(400, ['error' => 'At least one of firstName or lastName is required']);
    }

    return $contact;
}

setCORSHeaders();

$method = $_SERVER['REQUEST_METHOD'];

// Health check does not need a database connection.
if ($method === 'GET' && (isset($_GET['ping']) || (isset($_GET['action']) && $_GET['action'] === 'ping'))) {
    respond(200, ['status' => 'OK', 'timestamp' => time()]);
}

$db = getDB();

// Login is the only unauthenticated POST operation. Credentials are JSON
// fields named username and password and are matched against Users.Username.
if ($method === 'POST') {
    $body = getRequestBody();
    if (array_key_exists('username', $body) || array_key_exists('password', $body)) {
        $username = $body['username'] ?? '';
        $password = $body['password'] ?? '';

        if (!is_string($username) || !is_string($password)) {
            respond(400, ['error' => 'Username and password are required']);
        }
        $username = clean($username);
        if ($username === '' || $password === '') {
            respond(400, ['error' => 'Username and password are required']);
        }

        $stmt = $db->prepare(
            'SELECT ID AS id, FirstName AS firstName, LastName AS lastName
             FROM Users
             WHERE Username = :username AND Password = :password
             LIMIT 1'
        );
        $stmt->execute([':username' => $username, ':password' => $password]);
        $user = $stmt->fetch();

        if (!$user) {
            respond(401, ['error' => 'Invalid username or password']);
        }

        $userId = (int) $user['id'];
        respond(200, [
            'id'        => $userId,
            'firstName' => $user['firstName'],
            'lastName'  => $user['lastName'],
            'token'     => (string) $userId,
        ]);
    }
}

// Authentication identifies the caller. Contact ownership checks below are
// kept at the resource layer so a role-based policy can be added independently.
$userId = requireAuth();

switch ($method) {
    case 'GET':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $search = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['search']) ? trim($_GET['search']) : '');

        if (isset($_GET['id']) && $id <= 0) {
            respond(400, ['error' => 'Contact ID must be a positive integer']);
        }

        $select = 'SELECT ID AS id, First_Name AS firstName, Last_Name AS lastName,
                          Email AS email, Phone_Number AS phoneNumber
                   FROM Contacts';

        if ($id > 0) {
            $stmt = $db->prepare($select . ' WHERE ID = :id AND User_ID = :user_id LIMIT 1');
            $stmt->execute([':id' => $id, ':user_id' => $userId]);
            $contact = $stmt->fetch();
            if (!$contact) {
                respond(404, ['error' => 'Contact not found']);
            }
            respond(200, $contact);
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $stmt = $db->prepare(
                $select . ' WHERE User_ID = :user_id
                    AND (First_Name LIKE :first_name 
                         OR Last_Name LIKE :last_name
                         OR CONCAT(First_Name, CHAR(32), Last_Name) LIKE :full_name
                         OR Email LIKE :email 
                         OR Phone_Number LIKE :phone)
                    ORDER BY Last_Name, First_Name, ID'
            );
            $stmt->execute([
                ':user_id'   => $userId,
                ':first_name'=> $like,
                ':last_name' => $like,
                ':full_name' => $like,
                ':email'     => $like,
                ':phone'     => $like,
            ]);
        } else {
            $stmt = $db->prepare(
                $select . ' WHERE User_ID = :user_id ORDER BY Last_Name, First_Name, ID'
            );
            $stmt->execute([':user_id' => $userId]);
        }

        respond(200, ['contacts' => $stmt->fetchAll()]);

    case 'POST':
        $contact = readContactInput(getRequestBody());
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

    case 'PUT':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            respond(400, ['error' => 'Contact ID is required — use ?id=']);
        }

        $check = $db->prepare(
            'SELECT First_Name AS firstName, Last_Name AS lastName,
                    Email AS email, Phone_Number AS phoneNumber
             FROM Contacts WHERE ID = :id AND User_ID = :user_id LIMIT 1'
        );
        $check->execute([':id' => $id, ':user_id' => $userId]);
        $existing = $check->fetch();
        if (!$existing) {
            respond(404, ['error' => 'Contact not found']);
        }

        // Omitted fields keep their stored values; explicitly supplied empty
        // strings remain available to clear optional fields.
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

    case 'DELETE':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            respond(400, ['error' => 'Contact ID is required — use ?id=']);
        }

        $stmt = $db->prepare('DELETE FROM Contacts WHERE ID = :id AND User_ID = :user_id');
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        if ($stmt->rowCount() === 0) {
            respond(404, ['error' => 'Contact not found']);
        }

        respond(200, ['message' => 'Contact deleted']);

    default:
        respond(405, ['error' => 'Method not allowed']);
}
