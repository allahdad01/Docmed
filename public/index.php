<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PharmacySaaS\Core\TenantManager;
use PharmacySaaS\Auth\AuthManager;
use PharmacySaaS\Models\Product;
use PharmacySaaS\Models\Sale;
use PharmacySaaS\Models\DashboardStats;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Set error reporting
if ($_ENV['APP_DEBUG'] === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

// Initialize tenant manager
$tenantManager = new TenantManager();
$tenant = $tenantManager->detectTenant();

if (!$tenant) {
    // Show tenant registration or error page
    http_response_code(404);
    echo json_encode(['error' => 'Tenant not found']);
    exit;
}

// Initialize auth manager
$authManager = new AuthManager();

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Parse request
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestUri = trim($requestUri, '/');

// Remove tenant subdomain from path if present
if ($tenant && strpos($requestUri, $tenant['subdomain']) === 0) {
    $requestUri = substr($requestUri, strlen($tenant['subdomain']) + 1);
}

// API routes
$routes = [
    'POST' => [
        'api/auth/login' => 'handleLogin',
        'api/auth/register' => 'handleRegister',
        'api/products' => 'handleCreateProduct',
        'api/sales' => 'handleCreateSale',
        'api/tenants' => 'handleCreateTenant'
    ],
    'GET' => [
        'api/products' => 'handleGetProducts',
        'api/products/{id}' => 'handleGetProduct',
        'api/sales' => 'handleGetSales',
        'api/sales/{id}' => 'handleGetSale',
        'api/dashboard/stats' => 'handleGetDashboardStats',
        'api/tenants/{id}' => 'handleGetTenant'
    ],
    'PUT' => [
        'api/products/{id}' => 'handleUpdateProduct',
        'api/sales/{id}' => 'handleUpdateSale'
    ],
    'DELETE' => [
        'api/products/{id}' => 'handleDeleteProduct',
        'api/sales/{id}' => 'handleDeleteSale'
    ]
];

// Route the request
$routeFound = false;
foreach ($routes[$requestMethod] ?? [] as $route => $handler) {
    if (matchRoute($route, $requestUri, $params)) {
        $routeFound = true;
        try {
            call_user_func($handler, $params);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
    }
}

if (!$routeFound) {
    http_response_code(404);
    echo json_encode(['error' => 'Route not found']);
}

function matchRoute($route, $uri, &$params) {
    $params = [];
    $routeParts = explode('/', $route);
    $uriParts = explode('/', $uri);
    
    if (count($routeParts) !== count($uriParts)) {
        return false;
    }
    
    for ($i = 0; $i < count($routeParts); $i++) {
        if (strpos($routeParts[$i], '{') === 0) {
            $paramName = trim($routeParts[$i], '{}');
            $params[$paramName] = $uriParts[$i];
        } elseif ($routeParts[$i] !== $uriParts[$i]) {
            return false;
        }
    }
    
    return true;
}

// API Handlers
function handleLogin($params) {
    global $authManager;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['username']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password are required']);
        return;
    }
    
    try {
        $token = $authManager->login($input['username'], $input['password']);
        echo json_encode(['token' => $token, 'message' => 'Login successful']);
    } catch (\Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleRegister($params) {
    global $authManager;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        $userId = $authManager->register($input);
        echo json_encode(['id' => $userId, 'message' => 'User registered successfully']);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleCreateProduct($params) {
    global $authManager, $tenantManager;
    
    // Check authentication
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $authManager->requirePermission('products_manage');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $input['tenant_id'] = $tenantManager->getTenantId();
        
        $product = new Product();
        $productId = $product->create($input);
        
        echo json_encode(['id' => $productId, 'message' => 'Product created successfully']);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetProducts($params) {
    global $authManager, $tenantManager;
    
    // Check authentication
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $filters = $_GET;
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        
        $product = new Product();
        $products = $product->getAll($filters, $page, $limit);
        $total = $product->getCount($filters);
        
        echo json_encode([
            'data' => $products,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetProduct($params) {
    global $authManager, $tenantManager;
    
    // Check authentication
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $product = new Product();
        $productData = $product->getById($params['id']);
        
        if (!$productData) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            return;
        }
        
        echo json_encode($productData);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleCreateSale($params) {
    global $authManager, $tenantManager;
    
    // Check authentication
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $authManager->requirePermission('sales_manage');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $input['created_by'] = $user['id'];
        
        $sale = new Sale();
        $saleId = $sale->create($input);
        
        echo json_encode(['id' => $saleId, 'message' => 'Sale created successfully']);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetSales($params) {
    global $authManager, $tenantManager;
    
    // Check authentication
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $filters = $_GET;
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        
        $sale = new Sale();
        $sales = $sale->getAll($filters, $page, $limit);
        $total = $sale->getCount($filters);
        
        echo json_encode([
            'data' => $sales,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetDashboardStats($params) {
    global $authManager, $tenantManager;
    
    // Check authentication
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $tenantId = $tenantManager->getTenantId();
        $stats = $tenantManager->getTenantStats($tenantId);
        
        // Get today's sales
        $sale = new Sale();
        $todaySales = $sale->getDailySales();
        
        $stats['today_sales'] = $todaySales;
        
        echo json_encode($stats);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleCreateTenant($params) {
    // This endpoint is for super admin only
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        $tenantManager = new TenantManager();
        $tenantId = $tenantManager->createTenant($input);
        
        echo json_encode(['id' => $tenantId, 'message' => 'Tenant created successfully']);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleUpdateProduct($params) {
    global $authManager, $tenantManager;
    
    // Check authentication
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $authManager->requirePermission('products_manage');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $product = new Product();
        $success = $product->update($params['id'], $input);
        
        if ($success) {
            echo json_encode(['message' => 'Product updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleDeleteProduct($params) {
    global $authManager, $tenantManager;
    
    // Check authentication
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $authManager->requirePermission('products_manage');
        
        $product = new Product();
        $success = $product->delete($params['id']);
        
        if ($success) {
            echo json_encode(['message' => 'Product deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleUpdateSale($params) {
    global $authManager, $tenantManager;
    
    // Check authentication
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $authManager->requirePermission('sales_manage');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $sale = new Sale();
        $success = $sale->update($params['id'], $input);
        
        if ($success) {
            echo json_encode(['message' => 'Sale updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Sale not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleDeleteSale($params) {
    global $authManager, $tenantManager;
    
    // Check authentication
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $authManager->requirePermission('sales_manage');
        
        $sale = new Sale();
        $success = $sale->delete($params['id']);
        
        if ($success) {
            echo json_encode(['message' => 'Sale deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Sale not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetSale($params) {
    global $authManager, $tenantManager;
    
    // Check authentication
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $sale = new Sale();
        $saleData = $sale->getById($params['id']);
        
        if (!$saleData) {
            http_response_code(404);
            echo json_encode(['error' => 'Sale not found']);
            return;
        }
        
        // Get sale items
        $saleItems = $sale->getSaleItems($params['id']);
        $saleData['items'] = $saleItems;
        
        echo json_encode($saleData);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetTenant($params) {
    global $authManager, $tenantManager;
    
    // Check authentication
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $authManager->requireRole(['super_admin']);
        
        $tenantManager = new TenantManager();
        $tenant = $tenantManager->getTenantById($params['id']);
        
        if (!$tenant) {
            http_response_code(404);
            echo json_encode(['error' => 'Tenant not found']);
            return;
        }
        
        echo json_encode($tenant);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getAuthToken() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (strpos($authHeader, 'Bearer ') === 0) {
        return substr($authHeader, 7);
    }
    
    return null;
}