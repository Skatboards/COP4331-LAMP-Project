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

    if ($action === 'createAdmin') {
        $db = getDB();
        $admin = requireAdmin($db);
        require __DIR__ . '/handlers/create_admin.php';
    }

    if ($action !== 'createContact') {
        respond(400, ['error' => 'POST action must be login, register, or createContact']);
    }
}

// Authentication establishes the caller; each contact handler applies
// resource ownership checks so authorization policy can be extended separately.
$db = getDB();
$userId = requireAuth($db);

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'users' || $action === 'allContacts') {
        $admin = requireAdmin($db);
        require __DIR__ . ($action === 'users'
            ? '/handlers/list_users.php'
            : '/handlers/list_all_contacts.php');
    }

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
    $action = $_GET['action'] ?? '';
    if ($action === 'disableUser') {
        $admin = requireAdmin($db);
        require __DIR__ . '/handlers/disable_user.php';
    }
    if ($action === 'changePassword') {
        $admin = requireAdmin($db);
        require __DIR__ . '/handlers/update_user_password.php';
    }
    require __DIR__ . '/handlers/update_contact.php';
}

if ($method === 'DELETE') {
    require __DIR__ . '/handlers/delete_contact.php';
}

respond(405, ['error' => 'Method not allowed']);
