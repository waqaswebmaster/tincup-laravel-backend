<?php

// Load environment variables from .env file
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}

// Set headers for CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Autoload classes
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../app/Controllers/' . $class . '.php',
        __DIR__ . '/../app/Models/' . $class . '.php',
        __DIR__ . '/../database/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Get request body
$requestBody = file_get_contents('php://input');
$request = json_decode($requestBody, true) ?: [];

// Merge GET parameters
if (!empty($_GET)) {
    $request = array_merge($request, $_GET);
}

// Helper function to extract bearer token
function getBearerToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    return null;
}

// Middleware to authenticate requests
function authenticateRequest() {
    $token = getBearerToken();
    
    if (!$token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No token provided']);
        exit;
    }

    try {
        $authController = new AuthController();
        $decoded = $authController->verifyToken($token);
        return $decoded->userId;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
        exit;
    }
}

// Router
try {
    // Health check
    if ($uri === '/' || $uri === '/index.php') {
        echo json_encode([
            'success' => true,
            'message' => 'TinCup API is running',
            'version' => '1.0.0',
            'timestamp' => date('c')
        ]);
        exit;
    }

    if ($uri === '/api/auth/health') {
        echo json_encode([
            'success' => true,
            'message' => 'Auth service is healthy',
            'timestamp' => date('c')
        ]);
        exit;
    }

    $authController = new AuthController();

    // Public routes
    if ($uri === '/api/auth/register' && $method === 'POST') {
        $authController->register($request);
    } elseif ($uri === '/api/auth/login' && $method === 'POST') {
        $authController->login($request);
    } elseif ($uri === '/api/auth/refresh-token' && $method === 'POST') {
        $authController->refreshToken($request);
    }

    // Protected routes
    elseif ($uri === '/api/auth/profile' && $method === 'GET') {
        $userId = authenticateRequest();
        $authController->getProfile($userId);
    } elseif ($uri === '/api/auth/profile' && $method === 'PUT') {
        $userId = authenticateRequest();
        $authController->updateProfile($userId, $request);
    } elseif ($uri === '/api/auth/logout' && $method === 'POST') {
        $userId = authenticateRequest();
        $authController->logout($userId);
    }

    // 404 Not Found
    else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => "Route not found: $method $uri"
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => getenv('APP_DEBUG') === 'true' ? $e->getMessage() : null
    ]);
}
