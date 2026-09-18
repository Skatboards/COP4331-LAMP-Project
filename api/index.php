<?php
// API entry point. Each operation is implemented in its own handler file.
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

$method = $_SERVER['REQUEST_METHOD'];

// Public health check; it must remain available if the database is down.
if ($method === 'GET' && (isset($_GET['ping']) || ($_GET['action'] ?? '') === 'ping')) {
    require __DIR__ . '/handlers/ping.php';
}

// POST operations are selected explicitly in the JSON body.
if ($method === 'POST') {
    $body = getRequestBody();
    $action = $body['action'] ?? '';

    if ($action === 'login') {
        $db = getDB();
        require __DIR__ . '/handlers/login.php';
    }

    if ($action === 'register') {
        $db = getDB();
        require __DIR__ . '/handlers/create_user.php';
    }

    if ($action !== 'createContact') {
        respond(400, ['error' => 'POST action must be login, register, or createContact']);
    }
}

// Authentication establishes the caller; each contact handler applies
// resource ownership checks so authorization policy can be extended separately.
$userId = requireAuth();
$db = getDB();

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        require __DIR__ . '/handlers/get_contact.php';
    }

    $search = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['search']) ? trim($_GET['search']) : '');
    if ($search !== '') {
        require __DIR__ . '/handlers/search_contacts.php';
    }

    require __DIR__ . '/handlers/list_contacts.php';
}

if ($method === 'POST') {
    require __DIR__ . '/handlers/create_contact.php';
}

if ($method === 'PUT') {
    require __DIR__ . '/handlers/update_contact.php';
}

if ($method === 'DELETE') {
    require __DIR__ . '/handlers/delete_contact.php';
}

respond(405, ['error' => 'Method not allowed']);
