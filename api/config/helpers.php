<?php
// ============================================================
//  api/config/helpers.php — Utility & Helper Functions for API
// ============================================================

/**
 * Loads environment variables from a .env file into putenv, $_ENV, and $_SERVER.
 *
 * @param string|null $path Path to the .env file
 */
function loadEnv($path = null) {
    static $loaded = false;
    if ($loaded) {
        return;
    }

    if ($path === null) {
        $possiblePaths = [
            __DIR__ . '/../../.env',
            __DIR__ . '/../.env',
            __DIR__ . '/.env',
            (defined('ROOT_PATH') ? ROOT_PATH . '/.env' : null),
        ];
        foreach ($possiblePaths as $p) {
            if ($p && file_exists($p)) {
                $path = $p;
                break;
            }
        }
    }

    if ($path && file_exists($path)) {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name  = trim($name);
                $value = trim($value);

                // Strip surrounding quotes
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }

                if (getenv($name) === false) {
                    putenv("{$name}={$value}");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
    $loaded = true;
}

// Automatically load environment variables
loadEnv();

/**
 * Sets standard CORS headers to allow cross-origin API requests.
 * Handles preflight OPTIONS requests by exiting with 200 OK.
 */
function setCORSHeaders() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-User-Id");

    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

/**
 * Sends a JSON response with the specified HTTP status code and terminates execution.
 *
 * @param int $statusCode HTTP status code (e.g. 200, 201, 400, 404, 405, 500)
 * @param mixed $data Data array or object to serialize as JSON
 */
function respond($statusCode, $data) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Gets and decodes the JSON request body or falls back to $_POST input.
 *
 * @return array
 */
function getRequestBody() {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return $_POST ?? [];
}

/**
 * Sanitizes input data by trimming whitespace and stripping HTML tags.
 *
 * @param mixed $data
 * @return mixed
 */
function clean($data) {
    if (is_string($data)) {
        return trim(strip_tags($data));
    }
    return $data;
}

/**
 * Validates and normalizes contact fields from JSON/form data.
 * First or last name is required; email and phone are optional.
 *
 * @param array $body
 * @return array
 */
function readContactInput($body) {
    $fields = [
        'firstName'   => ['max' => 50],
        'lastName'    => ['max' => 50],
        'email'       => ['max' => 50],
        'phoneNumber' => ['max' => 20],
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

function getBearerToken() {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? (function_exists('apache_request_headers') ? (apache_request_headers()['Authorization'] ?? null) : null);

    if (!$authHeader) {
        return null;
    }

    $token = trim(preg_replace('/^Bearer\s+/i', '', $authHeader));
    return $token !== '' ? $token : null;
}

/**
 * Requires a valid, unexpired bearer token and returns the authenticated User ID.
 * Caller-supplied IDs and X-User-Id headers are intentionally ignored.
 */
function requireAuth($db) {
    $token = getBearerToken();
    if (!$token) {
        respond(401, ['error' => 'Authentication token is required']);
    }

    $stmt = $db->prepare(
        'SELECT u.ID AS id, u.Is_Disabled AS isDisabled
         FROM User_Sessions s
         INNER JOIN Users u ON u.ID = s.User_ID
         WHERE s.Token_Hash = :token_hash
           AND s.Expires_At > UTC_TIMESTAMP()
         LIMIT 1'
    );
    $stmt->execute([':token_hash' => hash('sha256', $token)]);
    $user = $stmt->fetch();

    if (!$user || (int)$user['isDisabled'] === 1) {
        respond(401, ['error' => 'Authentication is invalid or expired']);
    }

    return (int)$user['id'];
}

function requireAdmin($db) {
    $userId = requireAuth($db);
    $stmt = $db->prepare(
        'SELECT ID AS id, FirstName AS firstName, LastName AS lastName,
                Username AS username, Role AS role, Is_Disabled AS isDisabled
         FROM Users
         WHERE ID = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if (!$user || $user['role'] !== 'Admin' || (int)$user['isDisabled'] === 1) {
        respond(403, ['error' => 'Admin access is required']);
    }

    return $user;
}
