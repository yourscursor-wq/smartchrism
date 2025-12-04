<?php
require_once("config.php");
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$current = basename($_SERVER['PHP_SELF']);

// Get statistics with error handling
// Products
$products = 0;
$productsResult = $conn->query("SELECT COUNT(*) AS total FROM products");
if ($productsResult) {
    $products = (int)$productsResult->fetch_assoc()['total'];
}

// Orders - Total
$orders = 0;
$ordersResult = $conn->query("SELECT COUNT(*) AS total FROM orders");
if ($ordersResult) {
    $orders = (int)$ordersResult->fetch_assoc()['total'];
}

// Orders by status
$pendingOrders = 0;
$paidOrders = 0;
$shippedOrders = 0;
$cancelledOrders = 0;

$statusResult = $conn->query("SELECT status, COUNT(*) AS total FROM orders GROUP BY status");
if ($statusResult) {
    while ($row = $statusResult->fetch_assoc()) {
        $status = strtolower($row['status'] ?? '');
        $count = (int)$row['total'];
        switch ($status) {
            case 'pending':
                $pendingOrders = $count;
                break;
            case 'paid':
                $paidOrders = $count;
                break;
            case 'shipped':
                $shippedOrders = $count;
                break;
            case 'cancelled':
                $cancelledOrders = $count;
                break;
        }
    }
}

// Total revenue from all paid orders
$income = 0.0;
$incomeResult = $conn->query("SELECT SUM(total_amount) AS total FROM orders WHERE status IN ('paid', 'shipped')");
if ($incomeResult) {
    $incomeRow = $incomeResult->fetch_assoc();
    $income = (float)($incomeRow['total'] ?? 0);
}

// Admin accounts
$admins = 0;
$adminsResult = $conn->query("SELECT COUNT(*) AS total FROM admins");
if ($adminsResult) {
    $admins = (int)$adminsResult->fetch_assoc()['total'];
}

// Registered customers (users table)
$users = 0;
$usersResult = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($usersResult) {
    $users = (int)$usersResult->fetch_assoc()['total'];
}

// Get recent orders count (last 7 days)
$recentOrders = 0;
$recentOrdersResult = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
if ($recentOrdersResult) {
    $recentOrders = (int)$recentOrdersResult->fetch_assoc()['total'];
}

// Get contact messages count (check if table exists first)
$newMessages = 0;
$totalMessages = 0;
$tableCheck = $conn->query("SHOW TABLES LIKE 'contact_messages'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $newMessagesResult = $conn->query("SELECT COUNT(*) AS total FROM contact_messages WHERE status = 'new'");
    $newMessages = $newMessagesResult ? (int)$newMessagesResult->fetch_assoc()['total'] : 0;

    $allMessagesResult = $conn->query("SELECT COUNT(*) AS total FROM contact_messages");
    $totalMessages = $allMessagesResult ? (int)$allMessagesResult->fetch_assoc()['total'] : 0;
}

// Get recent sales data for chart (last 7 days)
$salesData = [];
$salesLabels = [];
$salesQuery = $conn->query("SELECT DATE(created_at) as day, SUM(total_amount) as total FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY day ASC");
if ($salesQuery) {
    while ($row = $salesQuery->fetch_assoc()) {
        $salesLabels[] = htmlspecialchars(date('M j', strtotime($row['day'])), ENT_QUOTES, 'UTF-8');
        $salesData[] = floatval($row['total'] ?? 0);
    }
}

// If no sales data, create empty arrays
if (empty($salesData)) {
    $salesLabels = ['No data'];
    $salesData = [0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Chrism Shop</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-body">
    <div class="sidebar">
        <h2>Smart Chrism</h2>
        <ul>
            <li><a href="dashboard.php" class="<?= $current == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
            <li><a href="add product.php" class="<?= $current == 'add product.php' ? 'active' : '' ?>">Add Product</a></li>
            <li><a href="products.php" class="<?= $current == 'products.php' ? 'active' : '' ?>">Manage Products</a></li>
            <li><a href="analytics.php" class="<?= $current == 'analytics.php' ? 'active' : '' ?>">Analytics</a></li>
            <li><a href="setup_mpesa.php" class="<?= $current == 'setup_mpesa.php' ? 'active' : '' ?>">M-Pesa Setup</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <div class="content">
        <h1>Dashboard</h1>
        
        <div class="cards">
            <div class="card">
                <h3>Total Products</h3>
                <p><?= number_format($products) ?></p>
            </div>
            <div class="card">
                <h3>Total Orders</h3>
                <p><?= number_format($orders) ?></p>
            </div>
            <div class="card">
                <h3>Total Revenue</h3>
                <p>KSh <?= number_format($income, 2) ?></p>
            </div>
            <div class="card">
                <h3>Recent Orders (7 days)</h3>
                <p><?= number_format($recentOrders) ?></p>
            </div>
            <?php if ($newMessages > 0): ?>
            <div class="card">
                <h3>New Messages</h3>
                <p><?= number_format($newMessages) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="chart-container">
            <h2>Sales (Last 7 Days)</h2>
            <canvas id="salesChart"></canvas>
        </div>

        <div class="chart-container">
            <h2>Business Summary</h2>
            <canvas id="summaryChart"></canvas>
        </div>

        <div class="chart-container">
            <h2>Records Summary 
                <button id="refreshSummary" class="btn ghost" style="font-size:12px;padding:4px 8px;margin-left:10px" title="Refresh data">
                    🔄 Refresh
                </button>
            </h2>
            <table class="records-summary" id="recordsSummaryTable">
                <thead>
                    <tr>
                        <th>Record Type</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Admins</strong></td>
                        <td id="stat-admins"><?= number_format($admins) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Customers (Users)</strong></td>
                        <td id="stat-users"><?= number_format($users) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Products</strong></td>
                        <td id="stat-products"><?= number_format($products) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Orders</strong></td>
                        <td id="stat-orders"><?= number_format($orders) ?></td>
                    </tr>
                    <tr>
                        <td>&nbsp;&nbsp;Pending Orders</td>
                        <td id="stat-pending"><?= number_format($pendingOrders) ?></td>
                    </tr>
                    <tr>
                        <td>&nbsp;&nbsp;Paid Orders</td>
                        <td id="stat-paid"><?= number_format($paidOrders) ?></td>
                    </tr>
                    <tr>
                        <td>&nbsp;&nbsp;Shipped Orders</td>
                        <td id="stat-shipped"><?= number_format($shippedOrders) ?></td>
                    </tr>
                    <tr>
                        <td>&nbsp;&nbsp;Cancelled Orders</td>
                        <td id="stat-cancelled"><?= number_format($cancelledOrders) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Contact Messages (New)</strong></td>
                        <td id="stat-new-messages"><?= number_format($newMessages) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Contact Messages (All)</strong></td>
                        <td id="stat-total-messages"><?= number_format($totalMessages) ?></td>
                    </tr>
                    <tr style="background-color:#f0f8ff;font-weight:bold">
                        <td><strong>Total Revenue (KSh)</strong></td>
                        <td id="stat-revenue"><?= number_format($income, 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart');
        if (salesCtx) {
            new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($salesLabels) ?>,
                    datasets: [{
                        label: 'Sales (KSh)',
                        data: <?= json_encode($salesData) ?>,
                        borderColor: '#007BFF',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Summary Chart
        const summaryCtx = document.getElementById('summaryChart');
        if (summaryCtx) {
            // Normalize data for chart (income might be much larger than products/orders)
            const productsCount = <?= intval($products) ?>;
            const ordersCount = <?= intval($orders) ?>;
            const incomeValue = <?= floatval($income) ?>;
            
            new Chart(summaryCtx, {
                type: 'bar',
                data: {
                    labels: ['Products', 'Orders', 'Revenue (KSh)'],
                    datasets: [{
                        label: 'Business Summary',
                        data: [productsCount, ordersCount, incomeValue],
                        backgroundColor: ['#007BFF', '#17A2B8', '#28A745']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Auto-refresh summary every 30 seconds
        let refreshInterval = null;
        
        async function refreshSummary() {
            try {
                const response = await fetch('dashboard_stats.php', {
                    credentials: 'include'
                });
                if (response.ok) {
                    const data = await response.json();
                    if (data.ok) {
                        // Update all statistics
                        document.getElementById('stat-admins').textContent = data.admins.toLocaleString();
                        document.getElementById('stat-users').textContent = data.users.toLocaleString();
                        document.getElementById('stat-products').textContent = data.products.toLocaleString();
                        document.getElementById('stat-orders').textContent = data.orders.toLocaleString();
                        document.getElementById('stat-pending').textContent = data.pendingOrders.toLocaleString();
                        document.getElementById('stat-paid').textContent = data.paidOrders.toLocaleString();
                        document.getElementById('stat-shipped').textContent = data.shippedOrders.toLocaleString();
                        document.getElementById('stat-cancelled').textContent = data.cancelledOrders.toLocaleString();
                        document.getElementById('stat-new-messages').textContent = data.newMessages.toLocaleString();
                        document.getElementById('stat-total-messages').textContent = data.totalMessages.toLocaleString();
                        document.getElementById('stat-revenue').textContent = parseFloat(data.revenue).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        
                        // Update cards
                        document.querySelector('.card:nth-child(1) p').textContent = data.products.toLocaleString();
                        document.querySelector('.card:nth-child(2) p').textContent = data.orders.toLocaleString();
                        document.querySelector('.card:nth-child(3) p').textContent = 'KSh ' + parseFloat(data.revenue).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        document.querySelector('.card:nth-child(4) p').textContent = data.recentOrders.toLocaleString();
                    }
                }
            } catch (err) {
                console.error('Failed to refresh summary:', err);
            }
        }

        // Manual refresh button
        document.getElementById('refreshSummary').addEventListener('click', () => {
            const btn = document.getElementById('refreshSummary');
            btn.textContent = '⏳ Refreshing...';
            btn.disabled = true;
            refreshSummary().then(() => {
                btn.textContent = '🔄 Refresh';
                btn.disabled = false;
            });
        });

        // Auto-refresh every 30 seconds
        refreshInterval = setInterval(refreshSummary, 30000);
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
    </script>
</body>
</html>
