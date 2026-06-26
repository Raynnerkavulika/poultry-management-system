<?php
// dashboard.php - Main Dashboard (Pure CSS, no Bootstrap)
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_name = $_SESSION['username'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'staff';

// ---------- FETCH STATISTICS ----------
$stmt = $pdo->query("SELECT SUM(quantity) AS total FROM birds");
$totalBirds = $stmt->fetchColumn() ?: 0;

$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT SUM(egg_count) AS eggs_today FROM egg_production WHERE production_date = ?");
$stmt->execute([$today]);
$eggsToday = $stmt->fetchColumn() ?: 0;

$monthStart = date('Y-m-01');
$stmt = $pdo->prepare("SELECT SUM(total_amount) AS month_sales FROM sales WHERE sale_date >= ?");
$stmt->execute([$monthStart]);
$monthSales = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(amount) AS month_expenses FROM expenses WHERE expense_date >= ?");
$stmt->execute([$monthStart]);
$monthExpenses = $stmt->fetchColumn() ?: 0;

$profit = $monthSales - $monthExpenses;

// Recent sales, health, feed
$stmt = $pdo->query("SELECT * FROM sales ORDER BY sale_date DESC LIMIT 5");
$recentSales = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT h.*, b.breed FROM health_records h LEFT JOIN birds b ON h.bird_id = b.id ORDER BY h.record_date DESC LIMIT 5");
$recentHealth = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM feed_consumption ORDER BY consumption_date DESC LIMIT 5");
$recentFeed = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Chart data: Egg production last 7 days
$stmt = $pdo->query("SELECT production_date, SUM(egg_count) AS total FROM egg_production WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY production_date ORDER BY production_date");
$eggData = $stmt->fetchAll(PDO::FETCH_ASSOC);
$eggLabels = [];
$eggCounts = [];
foreach ($eggData as $row) {
    $eggLabels[] = date('M d', strtotime($row['production_date']));
    $eggCounts[] = $row['total'];
}
if (empty($eggLabels)) { $eggLabels = ['No Data']; $eggCounts = [0]; }

// Sales vs Expenses last 6 months
$months = [];
$salesData = [];
$expensesData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime("-$i months"));
    $stmt = $pdo->prepare("SELECT SUM(total_amount) AS total FROM sales WHERE DATE_FORMAT(sale_date, '%Y-%m') = ?");
    $stmt->execute([$month]);
    $salesData[] = $stmt->fetchColumn() ?: 0;
    $stmt = $pdo->prepare("SELECT SUM(amount) AS total FROM expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?");
    $stmt->execute([$month]);
    $expensesData[] = $stmt->fetchColumn() ?: 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Poultry Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ---------- RESET & BASE ---------- */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f4f8;
            display: flex;
            min-height: 100vh;
        }
        a { text-decoration: none; }

        /* ---------- SIDEBAR ---------- */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1e2a3a, #0f1a2b);
            color: #fff;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }
        .sidebar .brand {
            padding: 1.5rem 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar .brand i {
            font-size: 2.5rem;
            color: #00b894;
        }
        .sidebar .brand h4 {
            font-weight: 700;
            margin-top: 0.5rem;
        }
        .sidebar .brand small {
            color: rgba(255,255,255,0.5);
        }
        .sidebar .nav {
            list-style: none;
            padding: 1rem 0;
            flex: 1;
        }
        .sidebar .nav li a {
            display: block;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.7);
            border-left: 3px solid transparent;
            transition: all 0.3s;
            font-weight: 500;
        }
        .sidebar .nav li a i {
            width: 24px;
            margin-right: 10px;
            text-align: center;
        }
        .sidebar .nav li a:hover,
        .sidebar .nav li a.active {
            color: #fff;
            background: rgba(0, 184, 148, 0.15);
            border-left-color: #00b894;
        }
        .sidebar .user-info {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 0.9rem;
            color: rgba(255,255,255,0.8);
        }
        .sidebar .user-info .badge {
            background: #00b894;
            color: #fff;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            margin-left: 0.5rem;
        }
        .sidebar .user-info .logout-link {
            display: block;
            margin-top: 0.5rem;
            color: rgba(255,255,255,0.5);
        }
        .sidebar .user-info .logout-link:hover {
            color: #fff;
        }
        /* Sidebar scrollbar */
        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-thumb { background: #00b894; border-radius: 8px; }

        /* ---------- MAIN CONTENT ---------- */
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 2rem;
        }

        /* ---------- TOP NAVBAR ---------- */
        .top-nav {
            background: #fff;
            padding: 1rem 2rem;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .top-nav .toggle-sidebar {
            background: none;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 0.4rem 0.8rem;
            cursor: pointer;
            font-size: 1.2rem;
            display: none;
        }
        .top-nav h5 {
            font-weight: 600;
            color: #1e2a3a;
            display: inline-block;
            margin-left: 0.5rem;
        }
        .top-nav .user-dropdown {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .top-nav .user-dropdown .date {
            color: #6c7a8a;
        }
        .top-nav .user-dropdown .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00b894, #00a67e);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* ---------- STAT CARDS ---------- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #fff;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.1);
        }
        .stat-card .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #fff;
            margin-bottom: 1rem;
        }
        .stat-card .stat-label {
            color: #6c7a8a;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e2a3a;
        }
        .stat-card .stat-change {
            font-size: 0.8rem;
            font-weight: 600;
        }
        .stat-change.positive { color: #00b894; }
        .stat-change.negative { color: #e74c3c; }

        /* Icon gradient helpers */
        .bg-gradient-green { background: linear-gradient(135deg, #00b894, #00a67e); }
        .bg-gradient-orange { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .bg-gradient-blue { background: linear-gradient(135deg, #3498db, #2980b9); }
        .bg-gradient-purple { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
        .bg-gradient-red { background: linear-gradient(135deg, #e74c3c, #c0392b); }

        /* ---------- CHARTS ---------- */
        .charts-row {
            display: grid;
            grid-template-columns: 7fr 5fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .chart-container {
            background: #fff;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
        }
        .chart-container h6 {
            font-weight: 600;
            color: #1e2a3a;
            margin-bottom: 1rem;
        }
        .chart-container h6 i {
            margin-right: 0.5rem;
        }

        /* ---------- RECENT ACTIVITY ---------- */
        .activity-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f4f8;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-item .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .activity-item .activity-content {
            flex: 1;
        }
        .activity-item .activity-content .text-muted {
            color: #6c7a8a;
            font-size: 0.8rem;
        }
        .activity-item .activity-time {
            font-size: 0.75rem;
            color: #b0bec5;
            white-space: nowrap;
        }
        .no-data {
            color: #b0bec5;
            text-align: center;
            padding: 1rem 0;
        }

        /* ---------- FOOTER ---------- */
        .footer {
            text-align: center;
            color: #b0bec5;
            font-size: 0.85rem;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 1200px) {
            .activity-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            .top-nav .toggle-sidebar {
                display: inline-block;
            }
            .charts-row {
                grid-template-columns: 1fr;
            }
            .activity-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .top-nav {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .top-nav .user-dropdown .date {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <i class="fas fa-egg"></i>
            <h4>Poultry Pro</h4>
            <small>Management System</small>
        </div>
        <ul class="nav">
            <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="modules/birds/index.php"><i class="fas fa-dove"></i> Birds</a></li>
            <li><a href="modules/feed/index.php"><i class="fas fa-seedling"></i> Feed</a></li>
            <li><a href="modules/health/index.php"><i class="fas fa-heartbeat"></i> Health</a></li>
            <li><a href="modules/eggs/index.php"><i class="fas fa-egg"></i> Eggs</a></li>
            <li><a href="modules/sales/index.php"><i class="fas fa-hand-holding-usd"></i> Sales</a></li>
            <li><a href="modules/expenses/index.php"><i class="fas fa-receipt"></i> Expenses</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-pie"></i> Reports</a></li>
        </ul>
        <div class="user-info">
            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($user_name) ?>
            <span class="badge"><?= ucfirst($user_role) ?></span>
            <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- Top Nav -->
        <div class="top-nav">
            <div>
                <button class="toggle-sidebar" id="toggleSidebar"><i class="fas fa-bars"></i></button>
                <h5>Dashboard</h5>
            </div>
            <div class="user-dropdown">
                <span class="date"><?= date('l, M d Y') ?></span>
                <div class="avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-gradient-green"><i class="fas fa-dove"></i></div>
                <div class="stat-label">Total Birds</div>
                <div class="stat-value"><?= number_format($totalBirds) ?></div>
                <span class="stat-change positive"><i class="fas fa-arrow-up"></i> +5% from last month</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-gradient-orange"><i class="fas fa-egg"></i></div>
                <div class="stat-label">Eggs Today</div>
                <div class="stat-value"><?= number_format($eggsToday) ?></div>
                <span class="stat-change positive"><i class="fas fa-arrow-up"></i> +12% from yesterday</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-gradient-blue"><i class="fas fa-hand-holding-usd"></i></div>
                <div class="stat-label">Monthly Sales</div>
                <div class="stat-value">$<?= number_format($monthSales, 2) ?></div>
                <span class="stat-change positive"><i class="fas fa-arrow-up"></i> +8% from last month</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon <?= $profit >= 0 ? 'bg-gradient-green' : 'bg-gradient-red' ?>">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-label">Net Profit</div>
                <div class="stat-value">$<?= number_format($profit, 2) ?></div>
                <span class="stat-change <?= $profit >= 0 ? 'positive' : 'negative' ?>">
                    <i class="fas fa-<?= $profit >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i> 
                    <?= $profit >= 0 ? 'Profitable' : 'Loss' ?>
                </span>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-row">
            <div class="chart-container">
                <h6><i class="fas fa-chart-line" style="color:#00b894;"></i> Egg Production (Last 7 Days)</h6>
                <canvas id="eggChart" height="200"></canvas>
            </div>
            <div class="chart-container">
                <h6><i class="fas fa-chart-bar" style="color:#3498db;"></i> Sales vs Expenses</h6>
                <canvas id="salesExpensesChart" height="200"></canvas>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="activity-grid">
            <!-- Sales -->
            <div class="chart-container">
                <h6><i class="fas fa-shopping-cart" style="color:#f39c12;"></i> Recent Sales</h6>
                <?php if (count($recentSales) > 0): ?>
                    <?php foreach ($recentSales as $sale): ?>
                        <div class="activity-item">
                            <div class="activity-icon bg-gradient-orange"><i class="fas fa-tag"></i></div>
                            <div class="activity-content">
                                <div><strong><?= htmlspecialchars($sale['item_type']) ?></strong> x <?= $sale['quantity'] ?></div>
                                <div class="text-muted">Customer: <?= htmlspecialchars($sale['customer_name'] ?? 'N/A') ?></div>
                            </div>
                            <div class="activity-time">$<?= number_format($sale['total_amount'], 2) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-data">No sales recorded yet.</div>
                <?php endif; ?>
            </div>

            <!-- Health -->
            <div class="chart-container">
                <h6><i class="fas fa-heartbeat" style="color:#e74c3c;"></i> Recent Health</h6>
                <?php if (count($recentHealth) > 0): ?>
                    <?php foreach ($recentHealth as $health): ?>
                        <div class="activity-item">
                            <div class="activity-icon bg-gradient-red"><i class="fas fa-notes-medical"></i></div>
                            <div class="activity-content">
                                <div><strong><?= htmlspecialchars($health['event_type']) ?></strong></div>
                                <div class="text-muted"><?= htmlspecialchars($health['description']) ?></div>
                            </div>
                            <div class="activity-time"><?= date('M d', strtotime($health['record_date'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-data">No health records.</div>
                <?php endif; ?>
            </div>

            <!-- Feed -->
            <div class="chart-container">
                <h6><i class="fas fa-seedling" style="color:#2ecc71;"></i> Feed Consumption</h6>
                <?php if (count($recentFeed) > 0): ?>
                    <?php foreach ($recentFeed as $feed): ?>
                        <div class="activity-item">
                            <div class="activity-icon bg-gradient-green"><i class="fas fa-weight"></i></div>
                            <div class="activity-content">
                                <div><strong><?= htmlspecialchars($feed['feed_type']) ?></strong></div>
                                <div class="text-muted"><?= $feed['quantity_kg'] ?> kg consumed</div>
                            </div>
                            <div class="activity-time"><?= date('M d', strtotime($feed['consumption_date'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-data">No feed consumption logged.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer">
            &copy; <?= date('Y') ?> Poultry Management System. All rights reserved.
        </div>
    </main>

    <!-- SCRIPTS -->
    <script>
        // Sidebar toggle
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
        });

        // Close sidebar on outside click (mobile)
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('toggleSidebar');
            if (window.innerWidth <= 992) {
                if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });

        // ---------- CHART.JS ----------
        // Egg chart
        const eggCtx = document.getElementById('eggChart').getContext('2d');
        new Chart(eggCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($eggLabels) ?>,
                datasets: [{
                    label: 'Eggs',
                    data: <?= json_encode($eggCounts) ?>,
                    backgroundColor: 'rgba(0, 184, 148, 0.2)',
                    borderColor: '#00b894',
                    borderWidth: 3,
                    pointBackgroundColor: '#00a67e',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // Sales vs Expenses chart
        const salesExpCtx = document.getElementById('salesExpensesChart').getContext('2d');
        new Chart(salesExpCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: [
                    {
                        label: 'Sales',
                        data: <?= json_encode($salesData) ?>,
                        backgroundColor: 'rgba(52, 152, 219, 0.7)',
                        borderColor: '#3498db',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: 'Expenses',
                        data: <?= json_encode($expensesData) ?>,
                        backgroundColor: 'rgba(231, 76, 60, 0.7)',
                        borderColor: '#e74c3c',
                        borderWidth: 1,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, boxWidth: 10 }
                    }
                },
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>
</body>
</html>