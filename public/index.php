<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PharmacySaaS\Core\TenantManager;
use PharmacySaaS\Auth\AuthManager;
use PharmacySaaS\Models\Product;
use PharmacySaaS\Models\Sale;
use PharmacySaaS\Models\Customer;
use PharmacySaaS\Models\Inventory;
use PharmacySaaS\Models\DashboardStats;
use PharmacySaaS\Models\Category;
use PharmacySaaS\Models\Branch;
use PharmacySaaS\Models\Supplier;

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
        'api/customers' => 'handleCreateCustomer',
        'api/inventory' => 'handleCreateInventory',
        'api/categories' => 'handleCreateCategory',
        'api/branches' => 'handleCreateBranch',
        'api/suppliers' => 'handleCreateSupplier',
        'api/tenants' => 'handleCreateTenant'
    ],
    'GET' => [
        'api/products' => 'handleGetProducts',
        'api/products/{id}' => 'handleGetProduct',
        'api/sales' => 'handleGetSales',
        'api/sales/{id}' => 'handleGetSale',
        'api/customers' => 'handleGetCustomers',
        'api/customers/{id}' => 'handleGetCustomer',
        'api/customers/{id}/history' => 'handleGetCustomerHistory',
        'api/inventory' => 'handleGetInventory',
        'api/inventory/{id}' => 'handleGetInventoryItem',
        'api/categories' => 'handleGetCategories',
        'api/categories/{id}' => 'handleGetCategory',
        'api/categories/active' => 'handleGetActiveCategories',
        'api/branches' => 'handleGetBranches',
        'api/branches/{id}' => 'handleGetBranch',
        'api/branches/active' => 'handleGetActiveBranches',
        'api/suppliers' => 'handleGetSuppliers',
        'api/suppliers/{id}' => 'handleGetSupplier',
        'api/suppliers/active' => 'handleGetActiveSuppliers',
        'api/dashboard/stats' => 'handleGetDashboardStats',
        'api/tenants/{id}' => 'handleGetTenant'
    ],
    'PUT' => [
        'api/products/{id}' => 'handleUpdateProduct',
        'api/sales/{id}' => 'handleUpdateSale',
        'api/customers/{id}' => 'handleUpdateCustomer',
        'api/inventory/{id}' => 'handleUpdateInventory',
        'api/categories/{id}' => 'handleUpdateCategory',
        'api/branches/{id}' => 'handleUpdateBranch',
        'api/suppliers/{id}' => 'handleUpdateSupplier'
    ],
    'DELETE' => [
        'api/products/{id}' => 'handleDeleteProduct',
        'api/sales/{id}' => 'handleDeleteSale',
        'api/customers/{id}' => 'handleDeleteCustomer',
        'api/inventory/{id}' => 'handleDeleteInventory',
        'api/categories/{id}' => 'handleDeleteCategory',
        'api/branches/{id}' => 'handleDeleteBranch',
        'api/suppliers/{id}' => 'handleDeleteSupplier'
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

function handleGetCustomers($params) {
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
        
        $customer = new Customer();
        $customers = $customer->getAll($filters, $page, $limit);
        $total = $customer->getCount($filters);
        
        echo json_encode([
            'data' => $customers,
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

function handleGetCustomer($params) {
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
        
        $customer = new Customer();
        $customerData = $customer->getById($params['id']);
        
        if (!$customerData) {
            http_response_code(404);
            echo json_encode(['error' => 'Customer not found']);
            return;
        }
        
        echo json_encode($customerData);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleCreateCustomer($params) {
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
        $authManager->requirePermission('customers_manage');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $input['created_by'] = $user['id'];
        
        $customer = new Customer();
        $customerId = $customer->create($input);
        
        echo json_encode(['id' => $customerId, 'message' => 'Customer created successfully']);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleUpdateCustomer($params) {
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
        $authManager->requirePermission('customers_manage');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $customer = new Customer();
        $success = $customer->update($params['id'], $input);
        
        if ($success) {
            echo json_encode(['message' => 'Customer updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Customer not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleDeleteCustomer($params) {
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
        $authManager->requirePermission('customers_manage');
        
        $customer = new Customer();
        $success = $customer->delete($params['id']);
        
        if ($success) {
            echo json_encode(['message' => 'Customer deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Customer not found']);
        }
            } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}

function handleGetCustomerHistory($params) {
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
        
        // For now, return mock data - this would be enhanced with real database queries
        $history = [
            'purchases' => [
                [
                    'date' => '2024-01-15',
                    'amount' => '125.50',
                    'items' => '3'
                ],
                [
                    'date' => '2024-01-10',
                    'amount' => '89.99',
                    'items' => '2'
                ],
                [
                    'date' => '2024-01-05',
                    'amount' => '45.75',
                    'items' => '1'
                ]
            ],
            'prescriptions' => [
                [
                    'date' => '2024-01-12',
                    'doctor' => 'Dr. Smith',
                    'status' => 'active'
                ],
                [
                    'date' => '2024-01-08',
                    'doctor' => 'Dr. Johnson',
                    'status' => 'completed'
                ]
            ]
        ];
        
        echo json_encode($history);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetInventory($params) {
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
        
        $inventory = new Inventory();
        $inventoryData = $inventory->getAll($filters, $page, $limit);
        $total = $inventory->getCount($filters);
        
        echo json_encode([
            'data' => $inventoryData,
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

function getAuthToken() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (strpos($authHeader, 'Bearer ') === 0) {
        return substr($authHeader, 7);
    }
    
    return null;
}

// Additional API handlers for advanced features
function handleGetInventoryItem($params) {
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
        
        $inventory = new Inventory();
        $item = $inventory->getById($params['id']);
        
        if ($item) {
            echo json_encode($item);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Inventory item not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleCreateInventory($params) {
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
        $authManager->requirePermission('inventory_manage');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $inventory = new Inventory();
        $inventoryId = $inventory->create($input);
        
        if ($inventoryId) {
            echo json_encode([
                'message' => 'Inventory item created successfully',
                'id' => $inventoryId
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to create inventory item']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleUpdateInventory($params) {
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
        $authManager->requirePermission('inventory_manage');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $inventory = new Inventory();
        $success = $inventory->update($params['id'], $input);
        
        if ($success) {
            echo json_encode(['message' => 'Inventory item updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Inventory item not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleDeleteInventory($params) {
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
        $authManager->requirePermission('inventory_manage');
        
        $inventory = new Inventory();
        $success = $inventory->delete($params['id']);
        
        if ($success) {
            echo json_encode(['message' => 'Inventory item deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Inventory item not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetCategories($params) {
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
        
        // Initialize Category model
        $categoryModel = new Category();
        
        // Get query parameters for pagination and filtering
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        // Get categories with pagination and filtering
        $categories = $categoryModel->getAll($page, $limit, $search, $status);
        $total = $categoryModel->getCount($search, $status);
        
        echo json_encode([
            'data' => $categories,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetBranches($params) {
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
        
        // Initialize Branch model
        $branchModel = new Branch();
        
        // Get query parameters for pagination and filtering
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        // Get branches with pagination and filtering
        $branches = $branchModel->getAll($page, $limit, $search, $status);
        $total = $branchModel->getCount($search, $status);
        
        echo json_encode([
            'data' => $branches,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetSuppliers($params) {
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
        
        // Initialize Supplier model
        $supplierModel = new Supplier();
        
        // Get query parameters for pagination and filtering
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        // Get suppliers with pagination and filtering
        $suppliers = $supplierModel->getAll($page, $limit, $search, $status);
        $total = $supplierModel->getCount($search, $status);
        
        echo json_encode([
            'data' => $suppliers,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Category CRUD Handlers
function handleCreateCategory($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Category name is required']);
            return;
        }
        
        $categoryModel = new Category();
        $categoryId = $categoryModel->create($input);
        
        echo json_encode([
            'message' => 'Category created successfully',
            'id' => $categoryId
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetCategory($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $categoryId = $params['id'];
        
        $categoryModel = new Category();
        $category = $categoryModel->getById($categoryId);
        
        if (!$category) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
            return;
        }
        
        echo json_encode($category);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleUpdateCategory($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $categoryId = $params['id'];
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Category name is required']);
            return;
        }
        
        $categoryModel = new Category();
        $success = $categoryModel->update($categoryId, $input);
        
        if ($success) {
            echo json_encode(['message' => 'Category updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found or no changes made']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleDeleteCategory($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $categoryId = $params['id'];
        
        $categoryModel = new Category();
        $success = $categoryModel->delete($categoryId);
        
        if ($success) {
            echo json_encode(['message' => 'Category deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found or cannot be deleted']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Branch CRUD Handlers
function handleCreateBranch($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['name']) || empty($input['address'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Branch name and address are required']);
            return;
        }
        
        $branchModel = new Branch();
        $branchId = $branchModel->create($input);
        
        echo json_encode([
            'message' => 'Branch created successfully',
            'id' => $branchId
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetBranch($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $branchId = $params['id'];
        
        $branchModel = new Branch();
        $branch = $branchModel->getById($branchId);
        
        if (!$branch) {
            http_response_code(404);
            echo json_encode(['error' => 'Branch not found']);
            return;
        }
        
        echo json_encode($branch);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleUpdateBranch($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $branchId = $params['id'];
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['name']) || empty($input['address'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Branch name and address are required']);
            return;
        }
        
        $branchModel = new Branch();
        $success = $branchModel->update($branchId, $input);
        
        if ($success) {
            echo json_encode(['message' => 'Branch updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Branch not found or no changes made']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleDeleteBranch($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $branchId = $params['id'];
        
        $branchModel = new Branch();
        $success = $branchModel->delete($branchId);
        
        if ($success) {
            echo json_encode(['message' => 'Branch deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Branch not found or cannot be deleted']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Supplier CRUD Handlers
function handleCreateSupplier($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['name']) || empty($input['contact_person'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Supplier name and contact person are required']);
            return;
        }
        
        $supplierModel = new Supplier();
        $supplierId = $supplierModel->create($input);
        
        echo json_encode([
            'message' => 'Supplier created successfully',
            'id' => $supplierId
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetSupplier($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $supplierId = $params['id'];
        
        $supplierModel = new Supplier();
        $supplier = $supplierModel->getById($supplierId);
        
        if (!$supplier) {
            http_response_code(404);
            echo json_encode(['error' => 'Supplier not found']);
            return;
        }
        
        echo json_encode($supplier);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleUpdateSupplier($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $supplierId = $params['id'];
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['name']) || empty($input['contact_person'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Supplier name and contact person are required']);
            return;
        }
        
        $supplierModel = new Supplier();
        $success = $supplierModel->update($supplierId, $input);
        
        if ($success) {
            echo json_encode(['message' => 'Supplier updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Supplier not found or no changes made']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleDeleteSupplier($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $supplierId = $params['id'];
        
        $supplierModel = new Supplier();
        $success = $supplierModel->delete($supplierId);
        
        if ($success) {
            echo json_encode(['message' => 'Supplier deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Supplier not found or cannot be deleted']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Active entities handlers for dropdowns
function handleGetActiveCategories($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $categoryModel = new Category();
        $categories = $categoryModel->getActive();
        
        echo json_encode([
            'data' => $categories,
            'total' => count($categories)
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetActiveBranches($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $branchModel = new Branch();
        $branches = $branchModel->getActive();
        
        echo json_encode([
            'data' => $branches,
            'total' => count($branches)
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetActiveSuppliers($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $supplierModel = new Supplier();
        $suppliers = $supplierModel->getActive();
        
        echo json_encode([
            'data' => $suppliers,
            'total' => count($suppliers)
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}