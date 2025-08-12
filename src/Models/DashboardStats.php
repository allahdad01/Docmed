<?php

namespace PharmacySaaS\Models;

use PharmacySaaS\Core\Database;

class DashboardStats
{
    private $db;
    private $tenantId;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->tenantId = $this->db->getTenantId();
    }

    /**
     * Get overall dashboard statistics
     */
    public function getOverallStats()
    {
        $stats = [];

        // Get basic counts
        $stats['total_products'] = $this->getProductCount();
        $stats['total_customers'] = $this->getCustomerCount();
        $stats['total_sales'] = $this->getSalesCount();
        $stats['total_branches'] = $this->getBranchCount();

        // Get financial stats
        $stats['today_sales'] = $this->getTodaySales();
        $stats['monthly_sales'] = $this->getMonthlySales();
        $stats['yearly_sales'] = $this->getYearlySales();

        // Get inventory stats
        $stats['low_stock_count'] = $this->getLowStockCount();
        $stats['out_of_stock_count'] = $this->getOutOfStockCount();
        $stats['expiring_soon_count'] = $this->getExpiringSoonCount();

        // Get user stats
        $stats['active_users'] = $this->getActiveUserCount();
        $stats['total_users'] = $this->getTotalUserCount();

        return $stats;
    }

    /**
     * Get product count
     */
    private function getProductCount()
    {
        $sql = "SELECT COUNT(*) as count FROM products WHERE tenant_id = ?";
        $result = $this->db->fetch($sql, [$this->tenantId]);
        return $result['count'] ?? 0;
    }

    /**
     * Get customer count
     */
    private function getCustomerCount()
    {
        $sql = "SELECT COUNT(*) as count FROM customers WHERE tenant_id = ?";
        $result = $this->db->fetch($sql, [$this->tenantId]);
        return $result['count'] ?? 0;
    }

    /**
     * Get sales count
     */
    private function getSalesCount()
    {
        $sql = "SELECT COUNT(*) as count FROM sales WHERE tenant_id = ?";
        $result = $this->db->fetch($sql, [$this->tenantId]);
        return $result['count'] ?? 0;
    }

    /**
     * Get branch count
     */
    private function getBranchCount()
    {
        $sql = "SELECT COUNT(*) as count FROM branches WHERE tenant_id = ?";
        $result = $this->db->fetch($sql, [$this->tenantId]);
        return $result['count'] ?? 0;
    }

    /**
     * Get today's sales
     */
    private function getTodaySales()
    {
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM sales 
                WHERE tenant_id = ? AND DATE(sale_date) = CURDATE()";
        $result = $this->db->fetch($sql, [$this->tenantId]);
        return $result['total'] ?? 0;
    }

    /**
     * Get monthly sales
     */
    private function getMonthlySales()
    {
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM sales 
                WHERE tenant_id = ? AND MONTH(sale_date) = MONTH(CURRENT_DATE()) 
                AND YEAR(sale_date) = YEAR(CURRENT_DATE())";
        $result = $this->db->fetch($sql, [$this->tenantId]);
        return $result['total'] ?? 0;
    }

    /**
     * Get yearly sales
     */
    private function getYearlySales()
    {
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM sales 
                WHERE tenant_id = ? AND YEAR(sale_date) = YEAR(CURRENT_DATE())";
        $result = $this->db->fetch($sql, [$this->tenantId]);
        return $result['total'] ?? 0;
    }

    /**
     * Get low stock count
     */
    private function getLowStockCount()
    {
        $sql = "SELECT COUNT(*) as count FROM product_inventory 
                WHERE tenant_id = ? AND quantity <= 10 AND quantity > 0";
        $result = $this->db->fetch($sql, [$this->tenantId]);
        return $result['count'] ?? 0;
    }

    /**
     * Get out of stock count
     */
    private function getOutOfStockCount()
    {
        $sql = "SELECT COUNT(*) as count FROM product_inventory 
                WHERE tenant_id = ? AND quantity = 0";
        $result = $this->db->fetch($sql, [$this->tenantId]);
        return $result['count'] ?? 0;
    }

    /**
     * Get expiring soon count
     */
    private function getExpiringSoonCount()
    {
        $sql = "SELECT COUNT(DISTINCT product_id) as count FROM inventory 
                WHERE tenant_id = ? AND expiry_date IS NOT NULL 
                AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                AND quantity > 0";
        $result = $this->db->fetch($sql, [$this->tenantId]);
        return $result['count'] ?? 0;
    }

    /**
     * Get active user count
     */
    private function getActiveUserCount()
    {
        $sql = "SELECT COUNT(*) as count FROM users WHERE tenant_id = ? AND is_active = 1";
        $result = $this->db->fetch($sql, [$this->tenantId]);
        return $result['count'] ?? 0;
    }

    /**
     * Get total user count
     */
    private function getTotalUserCount()
    {
        $sql = "SELECT COUNT(*) as count FROM users WHERE tenant_id = ?";
        $result = $this->db->fetch($sql, [$this->tenantId]);
        return $result['count'] ?? 0;
    }

    /**
     * Get sales trend for the last 7 days
     */
    public function getSalesTrend($days = 7)
    {
        $sql = "SELECT DATE(sale_date) as date, SUM(total_amount) as total
                FROM sales 
                WHERE tenant_id = ? AND sale_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(sale_date)
                ORDER BY date ASC";
        
        return $this->db->fetchAll($sql, [$this->tenantId, $days]);
    }

    /**
     * Get top selling products
     */
    public function getTopSellingProducts($limit = 10)
    {
        $sql = "SELECT p.name, p.name_ar, p.name_ps, p.name_dr, p.barcode,
                       SUM(si.quantity) as total_quantity,
                       SUM(si.quantity * si.unit_price) as total_revenue
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
                JOIN sales s ON si.sale_id = s.id
                WHERE s.tenant_id = ? AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY si.product_id
                ORDER BY total_quantity DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$this->tenantId, $limit]);
    }

    /**
     * Get top customers
     */
    public function getTopCustomers($limit = 10)
    {
        $sql = "SELECT c.name, c.name_ar, c.name_ps, c.name_dr, c.phone, c.email,
                       COUNT(s.id) as total_purchases,
                       SUM(s.total_amount) as total_spent
                FROM customers c
                LEFT JOIN sales s ON c.id = s.customer_id AND s.tenant_id = c.tenant_id
                WHERE c.tenant_id = ? AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY c.id
                ORDER BY total_spent DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$this->tenantId, $limit]);
    }

    /**
     * Get branch performance
     */
    public function getBranchPerformance($period = 'month')
    {
        $sql = "SELECT b.name, b.name_ar, b.name_ps, b.name_dr,
                       COUNT(s.id) as total_sales,
                       COALESCE(SUM(s.total_amount), 0) as total_revenue
                FROM branches b
                LEFT JOIN sales s ON b.id = s.branch_id AND s.tenant_id = b.tenant_id";
        
        if ($period === 'month') {
            $sql .= " AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        } elseif ($period === 'week') {
            $sql .= " AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        }
        
        $sql .= " WHERE b.tenant_id = ?
                GROUP BY b.id
                ORDER BY total_revenue DESC";
        
        return $this->db->fetchAll($sql, [$this->tenantId]);
    }

    /**
     * Get inventory valuation
     */
    public function getInventoryValuation()
    {
        $sql = "SELECT 
                    COUNT(DISTINCT pi.product_id) as total_products,
                    SUM(pi.quantity) as total_quantity,
                    SUM(pi.quantity * p.unit_cost) as total_cost,
                    SUM(pi.quantity * p.selling_price) as total_value
                FROM product_inventory pi
                JOIN products p ON pi.product_id = p.id
                WHERE pi.tenant_id = ? AND pi.quantity > 0";
        
        $result = $this->db->fetch($sql, [$this->tenantId]);
        
        if ($result) {
            $result['potential_profit'] = $result['total_value'] - $result['total_cost'];
        }
        
        return $result;
    }
}