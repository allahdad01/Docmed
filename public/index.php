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
use PharmacySaaS\Models\Doctor;
use PharmacySaaS\Models\Patient;
use PharmacySaaS\Models\Prescription;
use PharmacySaaS\Models\Commission;
use PharmacySaaS\Models\Medicine;
use PharmacySaaS\Models\LaboratoryTest;
use PharmacySaaS\Models\Expense;
use PharmacySaaS\Models\Payment;

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
        'api/tenants' => 'handleCreateTenant',
        'api/doctors' => 'handleCreateDoctor',
        'api/patients' => 'handleCreatePatient',
        'api/prescriptions' => 'handleCreatePrescription',
        'api/commissions/pharmacy' => 'handleCreatePharmacyCommission',
        'api/commissions/lab' => 'handleCreateLabCommission',
        'api/medicines' => 'handleCreateMedicine',
        'api/laboratory-tests' => 'handleCreateLaboratoryTest',
        'api/expenses' => 'handleCreateExpense',
        'api/payments' => 'handleCreatePayment'
    ],
    'GET' => [
        'api/auth/validate' => 'handleValidateToken',
        'api/tenants/{id}' => 'handleGetTenant',
        'api/doctors' => 'handleGetDoctors',
        'api/doctors/{id}' => 'handleGetDoctor',
        'api/patients' => 'handleGetPatients',
        'api/patients/{id}' => 'handleGetPatient',
        'api/prescriptions' => 'handleGetPrescriptions',
        'api/prescriptions/{id}' => 'handleGetPrescription',
        'api/commissions/pharmacy' => 'handleGetPharmacyCommissions',
        'api/commissions/lab' => 'handleGetLabCommissions',
        'api/commissions/summary' => 'handleGetCommissionSummary',
        'api/pharmacies' => 'handleGetPharmacies',
        'api/labs' => 'handleGetLabs',
        'api/medicines' => 'handleGetMedicines',
        'api/medicines/{id}' => 'handleGetMedicine',
        'api/medicines/search' => 'handleSearchMedicines',
        'api/laboratory-tests' => 'handleGetLaboratoryTests',
        'api/laboratory-tests/{id}' => 'handleGetLaboratoryTest',
        'api/laboratory-tests/search' => 'handleSearchLaboratoryTests',
        'api/expenses' => 'handleGetExpenses',
        'api/expenses/{id}' => 'handleGetExpense',
        'api/expenses/summary' => 'handleGetExpenseSummary',
        'api/payments' => 'handleGetPayments',
        'api/payments/{id}' => 'handleGetPayment',
        'api/payments/summary' => 'handleGetPaymentSummary',
        'api/dashboard/stats' => 'handleGetDashboardStats'
    ],
    'PUT' => [
        'api/products/{id}' => 'handleUpdateProduct',
        'api/sales/{id}' => 'handleUpdateSale',
        'api/customers/{id}' => 'handleUpdateCustomer',
        'api/inventory/{id}' => 'handleUpdateInventory',
        'api/categories/{id}' => 'handleUpdateCategory',
        'api/branches/{id}' => 'handleUpdateBranch',
        'api/suppliers/{id}' => 'handleUpdateSupplier',
        'api/doctors/{id}' => 'handleUpdateDoctor',
        'api/patients/{id}' => 'handleUpdatePatient',
        'api/prescriptions/{id}' => 'handleUpdatePrescription',
        'api/commissions/pharmacy/{id}' => 'handleUpdatePharmacyCommission',
        'api/commissions/lab/{id}' => 'handleUpdateLabCommission',
        'api/medicines/{id}' => 'handleUpdateMedicine',
        'api/laboratory-tests/{id}' => 'handleUpdateLaboratoryTest',
        'api/expenses/{id}' => 'handleUpdateExpense',
        'api/payments/{id}' => 'handleUpdatePayment'
    ],
    'DELETE' => [
        'api/products/{id}' => 'handleDeleteProduct',
        'api/sales/{id}' => 'handleDeleteSale',
        'api/customers/{id}' => 'handleDeleteCustomer',
        'api/inventory/{id}' => 'handleDeleteInventory',
        'api/categories/{id}' => 'handleDeleteCategory',
        'api/branches/{id}' => 'handleDeleteBranch',
        'api/suppliers/{id}' => 'handleDeleteSupplier',
        'api/doctors/{id}' => 'handleDeleteDoctor',
        'api/patients/{id}' => 'handleDeletePatient',
        'api/prescriptions/{id}' => 'handleDeletePrescription',
        'api/commissions/pharmacy/{id}' => 'handleDeletePharmacyCommission',
        'api/commissions/lab/{id}' => 'handleDeleteLabCommission',
        'api/medicines/{id}' => 'handleDeleteMedicine',
        'api/laboratory-tests/{id}' => 'handleDeleteLaboratoryTest',
        'api/expenses/{id}' => 'handleDeleteExpense',
        'api/payments/{id}' => 'handleDeletePayment'
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

// Doctor Management Handlers
function handleCreateDoctor($params) {
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
        
        // Validate required fields
        if (empty($input['name']) || empty($input['phone']) || empty($input['specialty'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Name, phone, and specialty are required']);
            return;
        }
        
        $doctorModel = new Doctor();
        $doctorId = $doctorModel->create($input);
        
        if ($doctorId) {
            echo json_encode([
                'message' => 'Doctor created successfully',
                'id' => $doctorId
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to create doctor']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetDoctors($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
        $doctorModel = new Doctor();
        $doctors = $doctorModel->getAll($page, $limit, $search);
        $total = $doctorModel->getCount($search);
        
        echo json_encode([
            'data' => $doctors,
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

function handleGetDoctor($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $doctorId = $params['id'];
        
        $doctorModel = new Doctor();
        $doctor = $doctorModel->getById($doctorId);
        
        if ($doctor) {
            echo json_encode(['data' => $doctor]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Doctor not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleUpdateDoctor($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $doctorId = $params['id'];
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['name']) || empty($input['phone']) || empty($input['specialty'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Name, phone, and specialty are required']);
            return;
        }
        
        $doctorModel = new Doctor();
        $success = $doctorModel->update($doctorId, $input);
        
        if ($success) {
            echo json_encode(['message' => 'Doctor updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Doctor not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleDeleteDoctor($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $doctorId = $params['id'];
        
        $doctorModel = new Doctor();
        $success = $doctorModel->delete($doctorId);
        
        if ($success) {
            echo json_encode(['message' => 'Doctor deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Doctor not found or cannot be deleted']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetDoctorStats($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $doctorId = $params['id'];
        
        $doctorModel = new Doctor();
        $stats = $doctorModel->getStats($doctorId);
        
        echo json_encode(['data' => $stats]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetDoctorMedicines($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $doctorId = $params['id'];
        
        $doctorModel = new Doctor();
        $medicines = $doctorModel->getPersonalMedicines($doctorId);
        
        echo json_encode(['data' => $medicines]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetDoctorPharmacies($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $doctorId = $params['id'];
        
        $doctorModel = new Doctor();
        $pharmacies = $doctorModel->getPartnerPharmacies($doctorId);
        
        echo json_encode(['data' => $pharmacies]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetDoctorLabs($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $doctorId = $params['id'];
        
        $doctorModel = new Doctor();
        $labs = $doctorModel->getPartnerLabs($doctorId);
        
        echo json_encode(['data' => $labs]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Patient Management Handlers
function handleCreatePatient($params) {
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
        
        // Validate required fields
        if (empty($input['name']) || empty($input['phone']) || empty($input['age'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Name, phone, and age are required']);
            return;
        }
        
        $patientModel = new Patient();
        $patientId = $patientModel->create($input);
        
        if ($patientId) {
            echo json_encode([
                'message' => 'Patient created successfully',
                'id' => $patientId
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to create patient']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetPatients($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;
        
        $patientModel = new Patient();
        
        if ($doctorId) {
            $patients = $patientModel->getByDoctor($doctorId, $page, $limit);
            $total = $patientModel->getCount($search);
        } else {
            $patients = $patientModel->getAll($page, $limit, $search);
            $total = $patientModel->getCount($search);
        }
        
        echo json_encode([
            'data' => $patients,
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

function handleGetPatient($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $patientId = $params['id'];
        
        $patientModel = new Patient();
        $patient = $patientModel->getById($patientId);
        
        if ($patient) {
            echo json_encode(['data' => $patient]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Patient not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleUpdatePatient($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $patientId = $params['id'];
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['name']) || empty($input['phone']) || empty($input['age'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Name, phone, and age are required']);
            return;
        }
        
        $patientModel = new Patient();
        $success = $patientModel->update($patientId, $input);
        
        if ($success) {
            echo json_encode(['message' => 'Patient updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Patient not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleDeletePatient($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $patientId = $params['id'];
        
        $patientModel = new Patient();
        $success = $patientModel->delete($patientId);
        
        if ($success) {
            echo json_encode(['message' => 'Patient deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Patient not found or cannot be deleted']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetPatientHistory($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $patientId = $params['id'];
        
        $patientModel = new Patient();
        $history = $patientModel->getMedicalHistory($patientId);
        
        echo json_encode(['data' => $history]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Prescription Management Handlers
function handleCreatePrescription($params) {
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
        
        // Validate required fields
        if (empty($input['doctor_id']) || empty($input['patient_id']) || empty($input['items'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Doctor ID, patient ID, and items are required']);
            return;
        }
        
        $prescriptionModel = new Prescription();
        $prescriptionId = $prescriptionModel->create($input);
        
        if ($prescriptionId) {
            echo json_encode([
                'message' => 'Prescription created successfully',
                'id' => $prescriptionId
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to create prescription']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetPrescriptions($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;
        $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;
        
        $prescriptionModel = new Prescription();
        
        if ($doctorId) {
            $prescriptions = $prescriptionModel->getByDoctor($doctorId, $page, $limit);
            $total = $prescriptionModel->getCount($search);
        } elseif ($patientId) {
            $prescriptions = $prescriptionModel->getByPatient($patientId, $page, $limit);
            $total = $prescriptionModel->getCount($search);
        } else {
            $prescriptions = $prescriptionModel->getAll($page, $limit, $search);
            $total = $prescriptionModel->getCount($search);
        }
        
        echo json_encode([
            'data' => $prescriptions,
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

function handleGetPrescription($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $prescriptionId = $params['id'];
        
        $prescriptionModel = new Prescription();
        $prescription = $prescriptionModel->getById($prescriptionId);
        
        if ($prescription) {
            echo json_encode(['data' => $prescription]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Prescription not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetPrescriptionItems($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $prescriptionId = $params['id'];
        
        $prescriptionModel = new Prescription();
        $items = $prescriptionModel->getItems($prescriptionId);
        
        echo json_encode(['data' => $items]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleUpdatePrescription($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $prescriptionId = $params['id'];
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['doctor_id']) || empty($input['patient_id']) || empty($input['items'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Doctor ID, patient ID, and items are required']);
            return;
        }
        
        $prescriptionModel = new Prescription();
        $success = $prescriptionModel->update($prescriptionId, $input);
        
        if ($success) {
            echo json_encode(['message' => 'Prescription updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Prescription not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleDeletePrescription($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $prescriptionId = $params['id'];
        
        $prescriptionModel = new Prescription();
        $success = $prescriptionModel->delete($prescriptionId);
        
        if ($success) {
            echo json_encode(['message' => 'Prescription deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Prescription not found or cannot be deleted']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Commission Management Handlers
function handleCreatePharmacyCommission($params) {
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
        
        // Validate required fields
        if (empty($input['doctor_id']) || empty($input['pharmacy_id']) || !isset($input['amount'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Doctor ID, pharmacy ID, and amount are required']);
            return;
        }
        
        $commissionModel = new Commission();
        $commissionId = $commissionModel->createPharmacyCommission($input);
        
        if ($commissionId) {
            echo json_encode([
                'message' => 'Pharmacy commission created successfully',
                'id' => $commissionId
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to create pharmacy commission']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleCreateLabCommission($params) {
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
        
        // Validate required fields
        if (empty($input['doctor_id']) || empty($input['lab_id']) || !isset($input['amount'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Doctor ID, lab ID, and amount are required']);
            return;
        }
        
        $commissionModel = new Commission();
        $commissionId = $commissionModel->createLabCommission($input);
        
        if ($commissionId) {
            echo json_encode([
                'message' => 'Lab commission created successfully',
                'id' => $commissionId
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to create lab commission']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetPharmacyCommissions($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;
        
        $commissionModel = new Commission();
        $commissions = $commissionModel->getPharmacyCommissions($page, $limit, $search, $doctorId);
        $total = $commissionModel->getPharmacyCommissionCount($search, $doctorId);
        
        echo json_encode([
            'data' => $commissions,
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

function handleGetLabCommissions($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;
        
        $commissionModel = new Commission();
        $commissions = $commissionModel->getLabCommissions($page, $limit, $search, $doctorId);
        $total = $commissionModel->getLabCommissionCount($search, $doctorId);
        
        echo json_encode([
            'data' => $commissions,
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

function handleGetCommissionSummary($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;
        
        $commissionModel = new Commission();
        $summary = $commissionModel->getCommissionStats($doctorId);
        
        echo json_encode(['data' => $summary]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleUpdatePharmacyCommission($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $commissionId = $params['id'];
        $input = json_decode(file_get_contents('php://input'), true);
        
        $commissionModel = new Commission();
        $success = $commissionModel->updatePharmacyCommissionStatus($commissionId, $input['status']);
        
        if ($success) {
            echo json_encode(['message' => 'Pharmacy commission updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Commission not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleUpdateLabCommission($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $commissionId = $params['id'];
        $input = json_decode(file_get_contents('php://input'), true);
        
        $commissionModel = new Commission();
        $success = $commissionModel->updateLabCommissionStatus($commissionId, $input['status']);
        
        if ($success) {
            echo json_encode(['message' => 'Lab commission updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Commission not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleDeletePharmacyCommission($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $commissionId = $params['id'];
        
        $commissionModel = new Commission();
        $success = $commissionModel->deletePharmacyCommission($commissionId);
        
        if ($success) {
            echo json_encode(['message' => 'Pharmacy commission deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Commission not found or cannot be deleted']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleDeleteLabCommission($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $commissionId = $params['id'];
        
        $commissionModel = new Commission();
        $success = $commissionModel->deleteLabCommission($commissionId);
        
        if ($success) {
            echo json_encode(['message' => 'Lab commission deleted successfully']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Commission not found or cannot be deleted']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetPharmacies($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $tenant = $tenantManager->getCurrentTenant();
        
        // Get search and pagination parameters
        $search = $_GET['search'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = ($page - 1) * $limit;
        
        // For now, we'll use branches as pharmacies since they're essentially the same
        // In a real implementation, you might have a separate pharmacies table
        $branchModel = new Branch();
        $pharmacies = $branchModel->getAll($tenant['id'], $search, $limit, $offset);
        $total = $branchModel->getTotalCount($tenant['id'], $search);
        
        echo json_encode([
            'data' => $pharmacies,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetLabs($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        $tenant = $tenantManager->getCurrentTenant();
        
        // Get search and pagination parameters
        $search = $_GET['search'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = ($page - 1) * $limit;
        
        // For now, we'll use branches as labs since they're essentially the same
        // In a real implementation, you might have a separate labs table
        $branchModel = new Branch();
        $labs = $branchModel->getAll($tenant['id'], $search, $limit, $offset);
        $total = $branchModel->getTotalCount($tenant['id'], $search);
        
        echo json_encode([
            'data' => $labs,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleCreateMedicine($params) {
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
            echo json_encode(['error' => 'Medicine name is required']);
            return;
        }
        
        $medicineModel = new Medicine();
        $medicineId = $medicineModel->create($input);
        
        echo json_encode([
            'message' => 'Medicine created successfully',
            'id' => $medicineId
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetMedicines($params) {
    global $authManager, $tenantManager;
    
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
        
        $medicineModel = new Medicine();
        $medicines = $medicineModel->getAll($filters, $page, $limit);
        $total = $medicineModel->getCount($filters);
        
        echo json_encode([
            'data' => $medicines,
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

function handleGetMedicine($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $medicineModel = new Medicine();
        $medicineData = $medicineModel->getById($params['id']);
        
        if (!$medicineData) {
            http_response_code(404);
            echo json_encode(['error' => 'Medicine not found']);
            return;
        }
        
        echo json_encode($medicineData);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleSearchMedicines($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $search = $_GET['search'] ?? '';
        
        $medicineModel = new Medicine();
        $medicines = $medicineModel->search($search);
        
        echo json_encode([
            'data' => $medicines,
            'total' => count($medicines)
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetLaboratoryTests($params) {
    global $authManager, $tenantManager;
    
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
        
        $laboratoryTestModel = new LaboratoryTest();
        $laboratoryTests = $laboratoryTestModel->getAll($filters, $page, $limit);
        $total = $laboratoryTestModel->getCount($filters);
        
        echo json_encode([
            'data' => $laboratoryTests,
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

function handleGetLaboratoryTest($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $laboratoryTestModel = new LaboratoryTest();
        $laboratoryTestData = $laboratoryTestModel->getById($params['id']);
        
        if (!$laboratoryTestData) {
            http_response_code(404);
            echo json_encode(['error' => 'Laboratory test not found']);
            return;
        }
        
        echo json_encode($laboratoryTestData);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleSearchLaboratoryTests($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $search = $_GET['search'] ?? '';
        
        $laboratoryTestModel = new LaboratoryTest();
        $laboratoryTests = $laboratoryTestModel->search($search);
        
        echo json_encode([
            'data' => $laboratoryTests,
            'total' => count($laboratoryTests)
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetExpenses($params) {
    global $authManager, $tenantManager;
    
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
        
        $expenseModel = new Expense();
        $expenses = $expenseModel->getAll($filters, $page, $limit);
        $total = $expenseModel->getCount($filters);
        
        echo json_encode([
            'data' => $expenses,
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

function handleGetExpense($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $expenseModel = new Expense();
        $expenseData = $expenseModel->getById($params['id']);
        
        if (!$expenseData) {
            http_response_code(404);
            echo json_encode(['error' => 'Expense not found']);
            return;
        }
        
        echo json_encode($expenseData);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetExpenseSummary($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $expenseModel = new Expense();
        $summary = $expenseModel->getExpenseSummary();
        
        echo json_encode(['data' => $summary]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetPayments($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;
        
        $paymentModel = new Payment();
        
        if ($doctorId) {
            $payments = $paymentModel->getByDoctor($doctorId, $page, $limit);
            $total = $paymentModel->getCount($search);
        } else {
            $payments = $paymentModel->getAll($page, $limit, $search);
            $total = $paymentModel->getCount($search);
        }
        
        echo json_encode([
            'data' => $payments,
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

function handleGetPayment($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $paymentModel = new Payment();
        $paymentData = $paymentModel->getById($params['id']);
        
        if (!$paymentData) {
            http_response_code(404);
            echo json_encode(['error' => 'Payment not found']);
            return;
        }
        
        echo json_encode($paymentData);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetPaymentSummary($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $paymentModel = new Payment();
        $summary = $paymentModel->getPaymentSummary();
        
        echo json_encode(['data' => $summary]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleCreateExpense($params) {
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
        
        if (empty($input['amount']) || empty($input['description'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Amount and description are required']);
            return;
        }
        
        $expenseModel = new Expense();
        $expenseId = $expenseModel->create($input);
        
        echo json_encode([
            'message' => 'Expense created successfully',
            'id' => $expenseId
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleCreateLaboratoryTest($params) {
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
            echo json_encode(['error' => 'Laboratory test name is required']);
            return;
        }
        
        $laboratoryTestModel = new LaboratoryTest();
        $laboratoryTestId = $laboratoryTestModel->create($input);
        
        echo json_encode([
            'message' => 'Laboratory test created successfully',
            'id' => $laboratoryTestId
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleCreatePayment($params) {
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
        
        if (empty($input['amount']) || empty($input['description'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Amount and description are required']);
            return;
        }
        
        $paymentModel = new Payment();
        $paymentId = $paymentModel->create($input);
        
        echo json_encode([
            'message' => 'Payment created successfully',
            'id' => $paymentId
        ]);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleValidateToken($params) {
    global $authManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        echo json_encode(['message' => 'Token is valid']);
    } catch (\Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Update handlers for new models
function handleUpdateMedicine($params) {
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
            echo json_encode(['error' => 'Medicine name is required']);
            return;
        }
        
        $medicineModel = new Medicine();
        $success = $medicineModel->update($params['id'], $input);
        
        if ($success) {
            echo json_encode(['message' => 'Medicine updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Medicine not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleUpdateLaboratoryTest($params) {
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
            echo json_encode(['error' => 'Laboratory test name is required']);
            return;
        }
        
        $laboratoryTestModel = new LaboratoryTest();
        $success = $laboratoryTestModel->update($params['id'], $input);
        
        if ($success) {
            echo json_encode(['message' => 'Laboratory test updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Laboratory test not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleUpdateExpense($params) {
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
        
        if (empty($input['amount']) || empty($input['description'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Amount and description are required']);
            return;
        }
        
        $expenseModel = new Expense();
        $success = $expenseModel->update($params['id'], $input);
        
        if ($success) {
            echo json_encode(['message' => 'Expense updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Expense not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleUpdatePayment($params) {
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
        
        if (empty($input['amount']) || empty($input['description'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Amount and description are required']);
            return;
        }
        
        $paymentModel = new Payment();
        $success = $paymentModel->update($params['id'], $input);
        
        if ($success) {
            echo json_encode(['message' => 'Payment updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Payment not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Delete handlers for new models
function handleDeleteMedicine($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $medicineModel = new Medicine();
        $success = $medicineModel->delete($params['id']);
        
        if ($success) {
            echo json_encode(['message' => 'Medicine deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Medicine not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleDeleteLaboratoryTest($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $laboratoryTestModel = new LaboratoryTest();
        $success = $laboratoryTestModel->delete($params['id']);
        
        if ($success) {
            echo json_encode(['message' => 'Laboratory test deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Laboratory test not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleDeleteExpense($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $expenseModel = new Expense();
        $success = $expenseModel->delete($params['id']);
        
        if ($success) {
            echo json_encode(['message' => 'Expense deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Expense not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleDeletePayment($params) {
    global $authManager, $tenantManager;
    
    $token = getAuthToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    try {
        $user = $authManager->validateToken($token);
        
        $paymentModel = new Payment();
        $success = $paymentModel->delete($params['id']);
        
        if ($success) {
            echo json_encode(['message' => 'Payment deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Payment not found']);
        }
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}