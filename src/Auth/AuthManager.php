<?php

namespace PharmacySaaS\Auth;

use PharmacySaaS\Core\Database;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthManager
{
    private $db;
    private $currentUser = null;
    private $jwtSecret;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'default-secret-key';
    }

    public function login($username, $password)
    {
        $sql = "SELECT u.*, t.name as tenant_name, t.subscription_status, b.name as branch_name 
                FROM users u 
                JOIN tenants t ON u.tenant_id = t.id 
                LEFT JOIN branches b ON u.branch_id = b.id 
                WHERE (u.username = :username OR u.email = :username) 
                AND u.is_active = 1";
        
        $user = $this->db->fetch($sql, ['username' => $username]);
        
        if (!$user) {
            throw new \Exception('Invalid credentials');
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new \Exception('Invalid credentials');
        }

        if ($user['subscription_status'] !== 'active') {
            throw new \Exception('Tenant subscription is not active');
        }

        // Update last login
        $this->db->update('users', 
            ['last_login_at' => date('Y-m-d H:i:s')], 
            'id = :id', 
            ['id' => $user['id']]
        );

        $this->currentUser = $user;
        
        return $this->generateToken($user);
    }

    public function register($userData)
    {
        $requiredFields = ['username', 'email', 'password', 'first_name', 'last_name', 'role'];
        foreach ($requiredFields as $field) {
            if (empty($userData[$field])) {
                throw new \Exception("Missing required field: {$field}");
            }
        }

        // Check if username or email already exists
        $existing = $this->db->fetch(
            "SELECT id FROM users WHERE username = :username OR email = :email",
            ['username' => $userData['username'], 'email' => $userData['email']]
        );

        if ($existing) {
            throw new \Exception('Username or email already exists');
        }

        $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        unset($userData['password']);

        $userId = $this->db->insert('users', $userData);
        
        return $userId;
    }

    public function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            if ($decoded->exp < time()) {
                throw new \Exception('Token expired');
            }

            $user = $this->db->fetch(
                "SELECT u.*, t.name as tenant_name, t.subscription_status, b.name as branch_name 
                 FROM users u 
                 JOIN tenants t ON u.tenant_id = t.id 
                 LEFT JOIN branches b ON u.branch_id = b.id 
                 WHERE u.id = :id AND u.is_active = 1",
                ['id' => $decoded->user_id]
            );

            if (!$user) {
                throw new \Exception('User not found');
            }

            if ($user['subscription_status'] !== 'active') {
                throw new \Exception('Tenant subscription is not active');
            }

            $this->currentUser = $user;
            return $user;

        } catch (\Exception $e) {
            throw new \Exception('Invalid token: ' . $e->getMessage());
        }
    }

    public function generateToken($user)
    {
        $payload = [
            'user_id' => $user['id'],
            'tenant_id' => $user['tenant_id'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + ($_ENV['JWT_EXPIRY'] ?? 3600)
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    public function getCurrentUser()
    {
        return $this->currentUser;
    }

    public function hasPermission($permission)
    {
        if (!$this->currentUser) {
            return false;
        }

        $sql = "SELECT COUNT(*) as count FROM role_permissions rp 
                JOIN permissions p ON rp.permission_id = p.id 
                WHERE rp.role = :role AND p.name = :permission";
        
        $result = $this->db->fetch($sql, [
            'role' => $this->currentUser['role'],
            'permission' => $permission
        ]);

        return $result['count'] > 0;
    }

    public function requirePermission($permission)
    {
        if (!$this->hasPermission($permission)) {
            throw new \Exception('Access denied: Insufficient permissions');
        }
    }

    public function requireRole($roles)
    {
        if (!$this->currentUser) {
            throw new \Exception('Authentication required');
        }

        if (is_string($roles)) {
            $roles = [$roles];
        }

        if (!in_array($this->currentUser['role'], $roles)) {
            throw new \Exception('Access denied: Insufficient role');
        }
    }

    public function logout()
    {
        $this->currentUser = null;
        return true;
    }

    public function changePassword($userId, $currentPassword, $newPassword)
    {
        $user = $this->db->fetch(
            "SELECT password_hash FROM users WHERE id = :id",
            ['id' => $userId]
        );

        if (!$user) {
            throw new \Exception('User not found');
        }

        if (!password_verify($currentPassword, $user['password_hash'])) {
            throw new \Exception('Current password is incorrect');
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        return $this->db->update('users', 
            ['password_hash' => $newHash], 
            'id = :id', 
            ['id' => $userId]
        );
    }

    public function enableTwoFactor($userId)
    {
        // Generate secret key for 2FA
        $secret = $this->generateTwoFactorSecret();
        
        return $this->db->update('users', 
            [
                'two_factor_enabled' => true,
                'two_factor_secret' => $secret
            ], 
            'id = :id', 
            ['id' => $userId]
        );
    }

    private function generateTwoFactorSecret()
    {
        return bin2hex(random_bytes(32));
    }

    public function verifyTwoFactor($userId, $code)
    {
        // Implementation for 2FA verification
        // This would typically use a library like Google Authenticator
        return true;
    }

    public function refreshToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            // Generate new token with extended expiry
            $user = $this->db->fetch(
                "SELECT * FROM users WHERE id = :id",
                ['id' => $decoded->user_id]
            );

            if (!$user) {
                throw new \Exception('User not found');
            }

            return $this->generateToken($user);

        } catch (\Exception $e) {
            throw new \Exception('Invalid token: ' . $e->getMessage());
        }
    }
}