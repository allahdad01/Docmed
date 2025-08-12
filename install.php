<?php

/**
 * Pharmacy SaaS Platform Installation Script
 * Run this script to set up your database and initial configuration
 */

// Check PHP version
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    die('PHP 8.1 or higher is required. Current version: ' . PHP_VERSION);
}

// Check required extensions
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    die('Missing required PHP extensions: ' . implode(', ', $missing_extensions));
}

echo "=== Pharmacy SaaS Platform Installation ===\n\n";

// Load environment variables if .env exists
if (file_exists('.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    echo "✓ Environment variables loaded from .env file\n";
} else {
    echo "⚠ .env file not found. Using default values.\n";
}

// Database configuration
$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_name = $_ENV['DB_NAME'] ?? 'pharmacy_saas';
$db_user = $_ENV['DB_USER'] ?? 'root';
$db_pass = $_ENV['DB_PASS'] ?? '';

echo "\nDatabase Configuration:\n";
echo "Host: $db_host\n";
echo "Database: $db_name\n";
echo "User: $db_user\n";

// Test database connection
try {
    $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connection successful\n";
} catch (PDOException $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

// Create database if it doesn't exist
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '$db_name' created/verified\n";
} catch (PDOException $e) {
    die("✗ Failed to create database: " . $e->getMessage() . "\n");
}

// Connect to the specific database
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("✗ Failed to connect to database '$db_name': " . $e->getMessage() . "\n");
}

// Read and execute SQL file
$sql_file = 'database.sql';
if (!file_exists($sql_file)) {
    die("✗ SQL file '$sql_file' not found\n");
}

echo "\nInstalling database schema...\n";

try {
    $sql = file_get_contents($sql_file);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "✓ Database schema installed successfully\n";
} catch (PDOException $e) {
    die("✗ Failed to install database schema: " . $e->getMessage() . "\n");
}

// Create storage directories
$storage_dirs = [
    'storage',
    'storage/uploads',
    'storage/logs',
    'storage/backups',
    'storage/temp'
];

echo "\nCreating storage directories...\n";

foreach ($storage_dirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✓ Created directory: $dir\n";
        } else {
            echo "⚠ Failed to create directory: $dir\n";
        }
    } else {
        echo "✓ Directory exists: $dir\n";
    }
}

// Create .htaccess for public directory
$htaccess_content = "RewriteEngine On\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^(.*)$ index.php [QSA,L]\n\n# Security headers\nHeader always set X-Content-Type-Options nosniff\nHeader always set X-Frame-Options DENY\nHeader always set X-XSS-Protection \"1; mode=block\"\n\n# Disable directory browsing\nOptions -Indexes";

if (file_put_contents('public/.htaccess', $htaccess_content)) {
    echo "✓ Created .htaccess file\n";
} else {
    echo "⚠ Failed to create .htaccess file\n";
}

// Create initial super admin user
echo "\nCreating initial super admin user...\n";

try {
    // Check if super admin already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'super_admin'");
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        // Create super admin user
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute(['admin', 'admin@pharmacysaas.com', $admin_password, 'super_admin', 'active']);
        
        echo "✓ Super admin user created:\n";
        echo "  Username: admin\n";
        echo "  Password: admin123\n";
        echo "  Email: admin@pharmacysaas.com\n";
        echo "  ⚠ Please change the password after first login!\n";
    } else {
        echo "✓ Super admin user already exists\n";
    }
} catch (PDOException $e) {
    echo "⚠ Failed to create super admin user: " . $e->getMessage() . "\n";
}

// Create sample tenant
echo "\nCreating sample tenant...\n";

try {
    // Check if sample tenant already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tenants WHERE subdomain = 'demo'");
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO tenants (name, subdomain, domain, subscription_plan, subscription_status, trial_ends_at, max_users, max_products, max_branches, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            'Demo Pharmacy',
            'demo',
            'demo.yourdomain.com',
            'premium',
            'active',
            date('Y-m-d', strtotime('+30 days')),
            20,
            10000,
            5,
            'active'
        ]);
        
        echo "✓ Sample tenant created:\n";
        echo "  Name: Demo Pharmacy\n";
        echo "  Subdomain: demo\n";
        echo "  Access URL: demo.yourdomain.com\n";
    } else {
        echo "✓ Sample tenant already exists\n";
    }
} catch (PDOException $e) {
    echo "⚠ Failed to create sample tenant: " . $e->getMessage() . "\n";
}

// Create sample branch for demo tenant
echo "\nCreating sample branch...\n";

try {
    $stmt = $pdo->prepare("SELECT id FROM tenants WHERE subdomain = 'demo'");
    $stmt->execute();
    $tenant_id = $stmt->fetchColumn();
    
    if ($tenant_id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM branches WHERE tenant_id = ? AND name = 'Main Branch'");
        $stmt->execute([$tenant_id]);
        
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO branches (tenant_id, name, address, phone, email, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $tenant_id,
                'Main Branch',
                '123 Main Street, Kabul, Afghanistan',
                '+93 123 456 789',
                'main@demopharmacy.com',
                'active'
            ]);
            
            echo "✓ Sample branch created: Main Branch\n";
        } else {
            echo "✓ Sample branch already exists\n";
        }
    }
} catch (PDOException $e) {
    echo "⚠ Failed to create sample branch: " . $e->getMessage() . "\n";
}

// Create sample categories
echo "\nCreating sample categories...\n";

try {
    $categories = [
        ['name' => 'Prescription Drugs', 'name_ar' => 'أدوية بوصفة طبية', 'name_ps' => 'دواګانې د نسخې سره', 'name_dr' => 'داروهای تجویزی'],
        ['name' => 'Over the Counter', 'name_ar' => 'أدوية بدون وصفة', 'name_ps' => 'د نسخې پرته دواګانې', 'name_dr' => 'داروهای بدون نسخه'],
        ['name' => 'Vitamins & Supplements', 'name_ar' => 'فيتامينات ومكملات', 'name_ps' => 'ویتامینونه او اضافي', 'name_dr' => 'ویتامین‌ها و مکمل‌ها'],
        ['name' => 'Personal Care', 'name_ar' => 'العناية الشخصية', 'name_ps' => 'شخصي پاملرنه', 'name_dr' => 'مراقبت شخصی'],
        ['name' => 'Medical Devices', 'name_ar' => 'الأجهزة الطبية', 'name_ps' => 'طبي وسایل', 'name_dr' => 'دستگاه‌های پزشکی']
    ];
    
    foreach ($categories as $category) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
        $stmt->execute([$category['name']]);
        
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO categories (name, name_ar, name_ps, name_dr, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$category['name'], $category['name_ar'], $category['name_ps'], $category['name_dr']]);
            echo "✓ Created category: " . $category['name'] . "\n";
        }
    }
} catch (PDOException $e) {
    echo "⚠ Failed to create sample categories: " . $e->getMessage() . "\n";
}

echo "\n=== Installation Complete! ===\n\n";
echo "Next steps:\n";
echo "1. Configure your web server to point to the 'public' directory\n";
echo "2. Update your .env file with proper database credentials\n";
echo "3. Access the application at: http://yourdomain.com\n";
echo "4. Login with super admin credentials:\n";
echo "   Username: admin\n";
echo "   Password: admin123\n";
echo "5. Create your first pharmacy tenant\n";
echo "6. Access your pharmacy at: http://yourtenant.yourdomain.com\n\n";
echo "For support, please refer to the documentation.\n";