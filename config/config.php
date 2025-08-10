<?php

return [
    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'Pharmacy SaaS Platform',
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => $_ENV['APP_DEBUG'] === 'true',
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',
        'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
        'version' => '1.0.0'
    ],
    
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'name' => $_ENV['DB_NAME'] ?? 'pharmacy_saas',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'pass' => $_ENV['DB_PASS'] ?? '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],
    
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? 'your-super-secret-jwt-key-here',
        'expiry' => (int)($_ENV['JWT_EXPIRY'] ?? 3600),
        'algorithm' => 'HS256'
    ],
    
    'mail' => [
        'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
        'port' => (int)($_ENV['MAIL_PORT'] ?? 587),
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@pharmacysaas.com',
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Pharmacy SaaS Platform'
    ],
    
    'sms' => [
        'gateway' => $_ENV['SMS_GATEWAY'] ?? 'twilio',
        'account_sid' => $_ENV['SMS_ACCOUNT_SID'] ?? '',
        'auth_token' => $_ENV['SMS_AUTH_TOKEN'] ?? ''
    ],
    
    'storage' => [
        'path' => $_ENV['STORAGE_PATH'] ?? 'storage/',
        'upload_max_size' => (int)($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760), // 10MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']
    ],
    
    'tenant' => [
        'domain_pattern' => $_ENV['TENANT_DOMAIN_PATTERN'] ?? '{tenant}.yourdomain.com',
        'default_language' => $_ENV['DEFAULT_LANGUAGE'] ?? 'en',
        'supported_languages' => explode(',', $_ENV['SUPPORTED_LANGUAGES'] ?? 'en,ar,ps,dr'),
        'max_file_size' => 5242880, // 5MB
        'backup_retention_days' => 30
    ],
    
    'features' => [
        'multi_branch' => true,
        'prescription_management' => true,
        'online_orders' => false,
        'doctor_panel' => false,
        'loyalty_program' => true,
        'credit_sales' => true,
        'barcode_generation' => true,
        'expiry_alerts' => true,
        'low_stock_alerts' => true,
        'activity_logging' => true,
        'two_factor_auth' => true
    ],
    
    'limits' => [
        'free_plan' => [
            'max_users' => 5,
            'max_products' => 1000,
            'max_branches' => 1,
            'max_customers' => 500,
            'storage_limit' => 1073741824 // 1GB
        ],
        'premium_plan' => [
            'max_users' => 20,
            'max_products' => 10000,
            'max_branches' => 5,
            'max_customers' => 5000,
            'storage_limit' => 10737418240 // 10GB
        ],
        'enterprise_plan' => [
            'max_users' => -1, // Unlimited
            'max_products' => -1,
            'max_branches' => -1,
            'max_customers' => -1,
            'storage_limit' => 107374182400 // 100GB
        ]
    ],
    
    'currencies' => [
        'AFN' => ['name' => 'Afghan Afghani', 'symbol' => '؋', 'rate' => 1],
        'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'rate' => 0.012],
        'EUR' => ['name' => 'Euro', 'symbol' => '€', 'rate' => 0.011],
        'GBP' => ['name' => 'British Pound', 'symbol' => '£', 'rate' => 0.0095]
    ],
    
    'tax_rates' => [
        'default' => 0,
        'prescription' => 0,
        'otc' => 5,
        'cosmetics' => 10
    ],
    
    'loyalty' => [
        'points_per_currency' => 1, // 1 point per 1 currency unit
        'currency_per_point' => 0.01, // 1 point = 0.01 currency
        'min_points_redemption' => 100,
        'points_expiry_days' => 365
    ]
];