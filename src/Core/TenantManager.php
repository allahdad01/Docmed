<?php

namespace PharmacySaaS\Core;

class TenantManager
{
    private $db;
    private $currentTenant = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function detectTenant()
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $subdomain = $this->extractSubdomain($host);
        
        if ($subdomain) {
            $tenant = $this->getTenantBySubdomain($subdomain);
            if ($tenant) {
                $this->setCurrentTenant($tenant);
                return $tenant;
            }
        }

        // Check for custom domain
        $tenant = $this->getTenantByDomain($host);
        if ($tenant) {
            $this->setCurrentTenant($tenant);
            return $tenant;
        }

        return null;
    }

    private function extractSubdomain($host)
    {
        $parts = explode('.', $host);
        if (count($parts) > 2) {
            return $parts[0];
        }
        return null;
    }

    public function getTenantBySubdomain($subdomain)
    {
        $sql = "SELECT * FROM tenants WHERE subdomain = :subdomain AND subscription_status = 'active'";
        return $this->db->fetch($sql, ['subdomain' => $subdomain]);
    }

    public function getTenantByDomain($domain)
    {
        $sql = "SELECT * FROM tenants WHERE domain = :domain AND subscription_status = 'active'";
        return $this->db->fetch($sql, ['domain' => $domain]);
    }

    public function getTenantById($tenantId)
    {
        $sql = "SELECT * FROM tenants WHERE id = :id";
        return $this->db->fetch($sql, ['id' => $tenantId]);
    }

    public function setCurrentTenant($tenant)
    {
        $this->currentTenant = $tenant;
        $this->db->setTenantId($tenant['id']);
        
        // Set timezone
        if ($tenant['timezone']) {
            date_default_timezone_set($tenant['timezone']);
        }
    }

    public function getCurrentTenant()
    {
        return $this->currentTenant;
    }

    public function getTenantId()
    {
        return $this->currentTenant ? $this->currentTenant['id'] : null;
    }

    public function isTenantActive()
    {
        return $this->currentTenant && $this->currentTenant['subscription_status'] === 'active';
    }

    public function checkSubscriptionLimits($feature, $currentCount = 0)
    {
        if (!$this->currentTenant) {
            return false;
        }

        $limits = [
            'users' => $this->currentTenant['max_users'],
            'products' => $this->currentTenant['max_products'],
            'branches' => $this->currentTenant['max_branches']
        ];

        if (isset($limits[$feature])) {
            return $currentCount < $limits[$feature];
        }

        return true;
    }

    public function createTenant($data)
    {
        $requiredFields = ['name', 'subdomain', 'email'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Missing required field: {$field}");
            }
        }

        // Check if subdomain already exists
        $existing = $this->getTenantBySubdomain($data['subdomain']);
        if ($existing) {
            throw new \Exception("Subdomain already exists");
        }

        $tenantData = [
            'name' => $data['name'],
            'subdomain' => $data['subdomain'],
            'domain' => $data['domain'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'currency' => $data['currency'] ?? 'USD',
            'language' => $data['language'] ?? 'en',
            'calendar_type' => $data['calendar_type'] ?? 'gregorian',
            'timezone' => $data['timezone'] ?? 'UTC',
            'subscription_plan' => $data['subscription_plan'] ?? 'free',
            'trial_ends_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
        ];

        $tenantId = $this->db->insert('tenants', $tenantData);

        // Create default branch
        $branchData = [
            'tenant_id' => $tenantId,
            'name' => 'Main Branch',
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email']
        ];

        $branchId = $this->db->insert('branches', $branchData);

        // Create pharmacy admin user
        $adminData = [
            'tenant_id' => $tenantId,
            'branch_id' => $branchId,
            'username' => 'admin',
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'] ?? 'admin123', PASSWORD_DEFAULT),
            'first_name' => 'Pharmacy',
            'last_name' => 'Admin',
            'role' => 'pharmacy_admin',
            'is_active' => true
        ];

        $this->db->insert('users', $adminData);

        return $tenantId;
    }

    public function updateTenant($tenantId, $data)
    {
        $allowedFields = [
            'name', 'logo_url', 'primary_color', 'secondary_color', 
            'address', 'phone', 'email', 'currency', 'language', 
            'calendar_type', 'timezone'
        ];

        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        if (empty($updateData)) {
            return false;
        }

        return $this->db->update('tenants', $updateData, 'id = :id', ['id' => $tenantId]);
    }

    public function suspendTenant($tenantId, $reason = '')
    {
        $data = [
            'subscription_status' => 'suspended',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->db->update('tenants', $data, 'id = :id', ['id' => $tenantId]);
    }

    public function activateTenant($tenantId)
    {
        $data = [
            'subscription_status' => 'active',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->db->update('tenants', $data, 'id = :id', ['id' => $tenantId]);
    }

    public function getTenantStats($tenantId)
    {
        $stats = [];

        // Count users
        $sql = "SELECT COUNT(*) as count FROM users WHERE tenant_id = :tenant_id";
        $result = $this->db->fetch($sql, ['tenant_id' => $tenantId]);
        $stats['users'] = $result['count'];

        // Count products
        $sql = "SELECT COUNT(*) as count FROM products WHERE tenant_id = :tenant_id";
        $result = $this->db->fetch($sql, ['tenant_id' => $tenantId]);
        $stats['products'] = $result['count'];

        // Count branches
        $sql = "SELECT COUNT(*) as count FROM branches WHERE tenant_id = :tenant_id";
        $result = $this->db->fetch($sql, ['tenant_id' => $tenantId]);
        $stats['branches'] = $result['count'];

        // Count customers
        $sql = "SELECT COUNT(*) as count FROM customers WHERE tenant_id = :tenant_id";
        $result = $this->db->fetch($sql, ['tenant_id' => $tenantId]);
        $stats['customers'] = $result['count'];

        // Monthly sales
        $sql = "SELECT SUM(total_amount) as total FROM sales 
                WHERE tenant_id = :tenant_id 
                AND MONTH(sale_date) = MONTH(CURRENT_DATE()) 
                AND YEAR(sale_date) = YEAR(CURRENT_DATE())";
        $result = $this->db->fetch($sql, ['tenant_id' => $tenantId]);
        $stats['monthly_sales'] = $result['total'] ?? 0;

        return $stats;
    }
}