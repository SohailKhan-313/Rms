<?php
/**
 * RMS Dynamic Executive Dashboard
 * Fetches 100% live operational data from MariaDB/MySQL
 */
include_once __DIR__ . '/../../config/database.php';

// Default zero metrics
$totalOrders = 0;
$todayOrders = 0;
$totalRevenue = 0.0;
$todayRevenue = 0.0;
$totalMenuItems = 0;
$totalCategories = 0;
$totalCustomers = 0;
$totalSuppliers = 0;
$todayExpenses = 0.0;
$totalExpenses = 0.0;
$recentOrders = [];
$categoryDistribution = [];

if ($conn) {
    // 1. Orders counts & revenues
    $ordRes = $conn->query("SELECT 
        COUNT(*) as total_count,
        SUM(COALESCE(grand_total, total, 0)) as total_rev,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_count,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN COALESCE(grand_total, total, 0) ELSE 0 END) as today_rev
        FROM `orders`");
    if ($ordRes && $r = $ordRes->fetch_assoc()) {
        $totalOrders = intval($r['total_count'] ?? 0);
        $totalRevenue = floatval($r['total_rev'] ?? 0);
        $todayOrders = intval($r['today_count'] ?? 0);
        $todayRevenue = floatval($r['today_rev'] ?? 0);
    }

    // 2. Menu and Categories count
    $menuRes = $conn->query("SELECT COUNT(*) FROM `menue`");
    if ($menuRes) $totalMenuItems = intval($menuRes->fetch_row()[0]);

    $catRes = $conn->query("SELECT COUNT(*) FROM `category`");
    if ($catRes) $totalCategories = intval($catRes->fetch_row()[0]);

    // 3. Customers and Suppliers count
    $custRes = $conn->query("SELECT COUNT(*) FROM `customers`");
    if ($custRes) $totalCustomers = intval($custRes->fetch_row()[0]);

    $supRes = $conn->query("SELECT COUNT(*) FROM `suppliers`");
    if ($supRes) {
        $totalSuppliers = intval($supRes->fetch_row()[0]);
    } else {
        $supLegacy = $conn->query("SELECT COUNT(*) FROM `supliers`");
        if ($supLegacy) $totalSuppliers = intval($supLegacy->fetch_row()[0]);
    }

    // 4. Expenses
    $expRes = $conn->query("SELECT 
        SUM(amount) as total_exp,
        SUM(CASE WHEN `date` = CURDATE() THEN amount ELSE 0 END) as today_exp
        FROM `expenses`");
    if ($expRes && $er = $expRes->fetch_assoc()) {
        $totalExpenses = floatval($er['total_exp'] ?? 0);
        $todayExpenses = floatval($er['today_exp'] ?? 0);
    }

    // 5. Recent Orders (Latest 6)
    $recRes = $conn->query("SELECT * FROM `orders` ORDER BY `id` DESC LIMIT 6");
    if ($recRes) {
        while ($row = $recRes->fetch_assoc()) {
            $recentOrders[] = $row;
        }
    }

    // 6. Category breakdown from menu
    $catDistRes = $conn->query("SELECT catagory, COUNT(*) as cnt FROM `menue` GROUP BY catagory ORDER BY cnt DESC LIMIT 5");
    if ($catDistRes) {
        while ($crow = $catDistRes->fetch_assoc()) {
            $categoryDistribution[$crow['catagory']] = intval($crow['cnt']);
        }
    }
}

$todayNetProfit = $todayRevenue - $todayExpenses;
?>

<!--begin::App Main-->
<main class="app-main">
  <!--begin::App Content Header-->
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h3 class="mb-0 fw-bold">Executive Operations Dashboard</h3>
          <small class="text-muted">Live metrics connected to RMS Database</small>
        </div>
        <div class="col-sm-6">
          <div class="d-flex align-items-center justify-content-sm-end gap-2 mt-2 mt-sm-0">
            <a href="/RMS/views/order/new.php" class="btn btn-warning fw-bold shadow-sm">
              <i class="bi bi-calculator-fill me-1"></i> Launch POS Terminal
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!--end::App Content Header-->

  <!--begin::App Content-->
  <div class="app-content">
    <div class="container-fluid">

      <!-- Row 1: Primary Live KPI Small Boxes -->
      <div class="row g-3 mb-4">
        <!-- Widget 1: Today's Sales Revenue -->
        <div class="col-lg-3 col-sm-6">
          <div class="small-box text-bg-primary shadow-sm rounded-3">
            <div class="inner">
              <h3 class="fw-bold">Rs. <?= number_format($todayRevenue, 2) ?></h3>
              <p class="mb-1 fw-semibold">Today's Sales Revenue</p>
              <small class="text-white-50"><?= $todayOrders ?> orders completed today</small>
            </div>
            <div class="small-box-icon">
              <i class="bi bi-currency-dollar"></i>
            </div>
            <a href="/RMS/views/report/daily.php" class="small-box-footer link-light link-underline-opacity-0">
              Sales & Profit Audit <i class="bi bi-arrow-right-circle ms-1"></i>
            </a>
          </div>
        </div>

        <!-- Widget 2: Total Orders -->
        <div class="col-lg-3 col-sm-6">
          <div class="small-box text-bg-success shadow-sm rounded-3">
            <div class="inner">
              <h3 class="fw-bold"><?= number_format($totalOrders) ?></h3>
              <p class="mb-1 fw-semibold">Total Lifetime Orders</p>
              <small class="text-white-50">Gross Revenue: Rs. <?= number_format($totalRevenue, 2) ?></small>
            </div>
            <div class="small-box-icon">
              <i class="bi bi-cart-check-fill"></i>
            </div>
            <a href="/RMS/views/order/manage.php" class="small-box-footer link-light link-underline-opacity-0">
              Manage Orders <i class="bi bi-arrow-right-circle ms-1"></i>
            </a>
          </div>
        </div>

        <!-- Widget 3: Registered Customers -->
        <div class="col-lg-3 col-sm-6">
          <div class="small-box text-bg-warning text-dark shadow-sm rounded-3">
            <div class="inner">
              <h3 class="fw-bold"><?= number_format($totalCustomers) ?></h3>
              <p class="mb-1 fw-semibold">Active Customers</p>
              <small class="text-dark-50"><?= $totalSuppliers ?> Active Suppliers</small>
            </div>
            <div class="small-box-icon">
              <i class="bi bi-people-fill"></i>
            </div>
            <a href="/RMS/views/user/customers/costomers_list.php" class="small-box-footer link-dark link-underline-opacity-0">
              Customer Directory <i class="bi bi-arrow-right-circle ms-1"></i>
            </a>
          </div>
        </div>

        <!-- Widget 4: Menu Items & Categories -->
        <div class="col-lg-3 col-sm-6">
          <div class="small-box text-bg-danger shadow-sm rounded-3">
            <div class="inner">
              <h3 class="fw-bold"><?= number_format($totalMenuItems) ?></h3>
              <p class="mb-1 fw-semibold">Food Menu Items</p>
              <small class="text-white-50">Across <?= $totalCategories ?> categories</small>
            </div>
            <div class="small-box-icon">
              <i class="bi bi-journal-richtext"></i>
            </div>
            <a href="/RMS/views/menu/All_menus.php" class="small-box-footer link-light link-underline-opacity-0">
              View Menu Catalog <i class="bi bi-arrow-right-circle ms-1"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Row 2: Financial Snapshot Bar (Sales vs Expenses vs Net Profit) -->
      <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body p-3">
          <div class="row align-items-center text-center text-md-start g-3">
            <div class="col-md-3 border-end-md">
              <span class="text-muted small text-uppercase fw-semibold">Today's POS Sales</span>
              <h4 class="fw-bold text-success mb-0">Rs. <?= number_format($todayRevenue, 2) ?></h4>
            </div>
            <div class="col-md-3 border-end-md">
              <span class="text-muted small text-uppercase fw-semibold">Today's Expenses</span>
              <h4 class="fw-bold text-danger mb-0">Rs. <?= number_format($todayExpenses, 2) ?></h4>
            </div>
            <div class="col-md-3 border-end-md">
              <span class="text-muted small text-uppercase fw-semibold">Today's Net Profit</span>
              <h4 class="fw-bold text-<?= $todayNetProfit >= 0 ? 'primary' : 'danger' ?> mb-0">
                <?= $todayNetProfit < 0 ? '-' : '' ?>Rs. <?= number_format(abs($todayNetProfit), 2) ?>
              </h4>
            </div>
            <div class="col-md-3 text-md-end">
              <div class="d-flex flex-column gap-1">
                <a href="/RMS/views/report/daily.php" class="btn btn-primary btn-sm fw-semibold">
                  <i class="bi bi-graph-up-arrow me-1"></i> Sales & Profit Filter
                </a>
                <a href="/RMS/views/report/daily_expenses.php" class="btn btn-outline-danger btn-sm fw-semibold">
                  <i class="bi bi-wallet2 me-1"></i> Expenses Filter
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Row 3: Live Recent Orders & Quick Shortcuts -->
      <div class="row g-4 mb-4">
        <!-- Col Left: Recent Orders Table -->
        <div class="col-lg-8">
          <div class="card shadow-sm border-0 rounded-3 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0 fw-bold">
                <i class="bi bi-clock-history text-primary me-2"></i>Recent Orders
              </h5>
              <a href="/RMS/views/order/manage.php" class="btn btn-sm btn-outline-primary fw-semibold">View All Orders</a>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Date & Time</th>
                    <th>Payment</th>
                    <th class="text-end">Total</th>
                    <th class="text-center">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($recentOrders) > 0): ?>
                    <?php foreach ($recentOrders as $ro): 
                      $orderNum = !empty($ro['order_number']) ? $ro['order_number'] : ('ORD-' . $ro['id']);
                      $orderTotal = floatval($ro['grand_total'] ?? $ro['total'] ?? 0);
                    ?>
                      <tr>
                        <td class="fw-bold text-primary">
                          <a href="/RMS/views/order/details.php?id=<?= $ro['id'] ?>" class="text-decoration-none">
                            <?= htmlspecialchars($orderNum) ?>
                          </a>
                        </td>
                        <td><?= htmlspecialchars($ro['customer_name'] ?? 'Walk-in') ?></td>
                        <td>
                          <span class="rms-badge rms-badge-primary">
                            <?= htmlspecialchars($ro['order_type'] ?? 'Dine-In') ?>
                          </span>
                        </td>
                        <td><small class="text-muted"><?= date('M d, h:i A', strtotime($ro['created_at'])) ?></small></td>
                        <td>
                          <span class="badge bg-light text-dark border">
                            <?= htmlspecialchars($ro['payment_method'] ?? 'Cash') ?>
                          </span>
                        </td>
                        <td class="text-end fw-bold text-success">Rs. <?= number_format($orderTotal, 2) ?></td>
                        <td class="text-center">
                          <div class="btn-group btn-group-sm">
                            <a href="/RMS/views/order/details.php?id=<?= $ro['id'] ?>" class="btn btn-outline-secondary" title="View Order">
                              <i class="bi bi-eye"></i>
                            </a>
                            <a href="/RMS/views/order/print_pdf.php?order_id=<?= $ro['id'] ?>&format=thermal" target="_blank" class="btn btn-outline-dark" title="Print Receipt">
                              <i class="bi bi-printer"></i>
                            </a>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="7" class="text-center py-4 text-muted">
                        No orders recorded yet. Open POS to make the first sale!
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Col Right: Quick Actions & Menu Category Distribution -->
        <div class="col-lg-4">
          <!-- Quick Action Links -->
          <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
              <h5 class="card-title mb-0 fw-bold">
                <i class="bi bi-lightning-charge-fill text-warning me-2"></i>Quick Actions
              </h5>
            </div>
            <div class="card-body p-3">
              <div class="d-grid gap-2">
                <a href="/RMS/views/order/new.php" class="btn btn-primary fw-semibold text-start py-2">
                  <i class="bi bi-calculator me-2"></i> New POS Order
                </a>
                <a href="/RMS/views/menu/add_menus.php" class="btn btn-outline-primary fw-semibold text-start py-2">
                  <i class="bi bi-plus-circle me-2"></i> Add Food Menu Dish
                </a>
                <a href="/RMS/views/user/customers/costomers_list.php" class="btn btn-outline-secondary fw-semibold text-start py-2">
                  <i class="bi bi-person-plus me-2"></i> Add New Customer
                </a>
                <a href="/RMS/views/report/daily_expenses.php" class="btn btn-outline-danger fw-semibold text-start py-2">
                  <i class="bi bi-plus-slash-minus me-2"></i> Record Daily Expense
                </a>
                <a href="/RMS/views/user/customers/suppliers.php" class="btn btn-outline-dark fw-semibold text-start py-2">
                  <i class="bi bi-truck me-2"></i> Manage Suppliers
                </a>
              </div>
            </div>
          </div>

          <!-- Menu Category Distribution Card -->
          <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
              <h5 class="card-title mb-0 fw-bold">
                <i class="bi bi-pie-chart text-info me-2"></i>Top Menu Categories
              </h5>
            </div>
            <div class="card-body p-3">
              <?php if (!empty($categoryDistribution)): ?>
                <ul class="list-group list-group-flush">
                  <?php foreach ($categoryDistribution as $cName => $cCount): 
                    $percent = ($totalMenuItems > 0) ? round(($cCount / $totalMenuItems) * 100) : 0;
                  ?>
                    <li class="list-group-item px-0 py-2 border-0">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold small"><?= htmlspecialchars(ucfirst($cName)) ?></span>
                        <span class="badge bg-light text-dark border"><?= $cCount ?> items (<?= $percent ?>%)</span>
                      </div>
                      <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $percent ?>%"></div>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <p class="text-muted small mb-0">No food items added to categories yet.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
  <!--end::App Content-->
</main>
<!--end::App Main-->