<?php
ob_start();
$pageTitle = "Sales, Expenses & Profit Report";
include_once __DIR__ . '/../../config/database.php';

$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');

// Backward compatibility with ?date=
if (empty($fromDate) && empty($toDate)) {
    if (isset($_GET['date'])) {
        if ($_GET['date'] === 'all') {
            $fromDate = '';
            $toDate = '';
        } else {
            $fromDate = $_GET['date'];
            $toDate = $_GET['date'];
        }
    } else {
        // Default to current month: 1st of month to today
        $fromDate = date('Y-m-01');
        $toDate = date('Y-m-d');
    }
}

$orders = [];
$totalRevenue = 0.0;
$totalTax = 0.0;
$totalDiscount = 0.0;
$totalOrders = 0;
$paymentBreakdown = ['Cash' => 0.0, 'Card' => 0.0, 'Online' => 0.0];

$expenses = [];
$totalExpenses = 0.0;
$dailyLedger = [];

if ($conn) {
    // 1. Build WHERE clauses
    $orderWhere = "1=1";
    $expWhere = "1=1";

    if (!empty($fromDate) && !empty($toDate)) {
        $fromSafe = mysqli_real_escape_string($conn, $fromDate);
        $toSafe = mysqli_real_escape_string($conn, $toDate);
        $orderWhere = "DATE(created_at) >= '$fromSafe' AND DATE(created_at) <= '$toSafe'";
        $expWhere = "`date` >= '$fromSafe' AND `date` <= '$toSafe'";
    } elseif (!empty($fromDate)) {
        $fromSafe = mysqli_real_escape_string($conn, $fromDate);
        $orderWhere = "DATE(created_at) >= '$fromSafe'";
        $expWhere = "`date` >= '$fromSafe'";
    } elseif (!empty($toDate)) {
        $toSafe = mysqli_real_escape_string($conn, $toDate);
        $orderWhere = "DATE(created_at) <= '$toSafe'";
        $expWhere = "`date` <= '$toSafe'";
    }

    // 2. Fetch Orders (Sales)
    $ordQuery = "SELECT * FROM `orders` WHERE $orderWhere ORDER BY `created_at` DESC";
    $ordRes = $conn->query($ordQuery);
    if ($ordRes) {
        while ($ord = $ordRes->fetch_assoc()) {
            $orders[] = $ord;
            $amt = floatval($ord['grand_total'] ?? $ord['total'] ?? 0);
            $totalRevenue += $amt;
            $totalTax += floatval($ord['tax'] ?? 0);
            $totalDiscount += floatval($ord['discount'] ?? 0);
            $totalOrders++;

            $method = $ord['payment_method'] ?? 'Cash';
            if (!isset($paymentBreakdown[$method])) $paymentBreakdown[$method] = 0.0;
            $paymentBreakdown[$method] += $amt;

            // Day aggregate
            $dayKey = date('Y-m-d', strtotime($ord['created_at']));
            if (!isset($dailyLedger[$dayKey])) {
                $dailyLedger[$dayKey] = ['sales' => 0.0, 'expenses' => 0.0, 'orders' => 0];
            }
            $dailyLedger[$dayKey]['sales'] += $amt;
            $dailyLedger[$dayKey]['orders']++;
        }
    }

    // 3. Fetch Expenses
    $expQuery = "SELECT * FROM `expenses` WHERE $expWhere ORDER BY `date` DESC, `id` DESC";
    $expRes = $conn->query($expQuery);
    if ($expRes) {
        while ($exp = $expRes->fetch_assoc()) {
            $expenses[] = $exp;
            $eAmt = floatval($exp['amount'] ?? 0);
            $totalExpenses += $eAmt;

            // Day aggregate
            $dayKey = $exp['date'];
            if (!isset($dailyLedger[$dayKey])) {
                $dailyLedger[$dayKey] = ['sales' => 0.0, 'expenses' => 0.0, 'orders' => 0];
            }
            $dailyLedger[$dayKey]['expenses'] += $eAmt;
        }
    }
}

// 4. Calculate Net Profit & Margin
$netProfit = $totalRevenue - $totalExpenses;
$profitMargin = ($totalRevenue > 0) ? ($netProfit / $totalRevenue) * 100 : 0;
$avgOrderValue = ($totalOrders > 0) ? ($totalRevenue / $totalOrders) : 0;

// Sort daily ledger descending by date
krsort($dailyLedger);

// Period description label
if (!empty($fromDate) && !empty($toDate)) {
    if ($fromDate === $toDate) {
        $periodLabel = date('M d, Y (l)', strtotime($fromDate));
    } else {
        $periodLabel = date('M d, Y', strtotime($fromDate)) . ' &mdash; ' . date('M d, Y', strtotime($toDate));
    }
} elseif (!empty($fromDate)) {
    $periodLabel = "From " . date('M d, Y', strtotime($fromDate));
} elseif (!empty($toDate)) {
    $periodLabel = "Up to " . date('M d, Y', strtotime($toDate));
} else {
    $periodLabel = "All Recorded Lifetime History";
}

include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';
?>

<style>
@media print {
  .no-print, .main-header, .main-sidebar, .app-header, .app-sidebar, footer {
    display: none !important;
  }
  .print-only {
    display: block !important;
  }
  body {
    background: #fff !important;
    font-size: 12pt;
    color: #000 !important;
  }
  .card {
    border: 1px solid #ddd !important;
    box-shadow: none !important;
  }
}
.print-only {
  display: none;
}
.kpi-card {
  transition: transform 0.15s ease, box-shadow 0.15s ease;
  border-radius: 14px;
}
.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
}
</style>

<main class="app-main">
  <!-- Header -->
  <div class="app-content-header no-print">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-7">
          <h3 class="mb-0 fw-bold">
            <i class="bi bi-graph-up-arrow text-primary me-2"></i>Sales, Expenses & Profit Report
          </h3>
          <small class="text-muted">Financial audit with customized date-to-date filtering</small>
        </div>
        <div class="col-sm-5 text-sm-end mt-2 mt-sm-0">
          <div class="btn-group shadow-sm">
            <button onclick="window.print()" class="btn btn-outline-secondary fw-semibold">
              <i class="bi bi-printer me-1"></i> Print
            </button>
            <a href="/RMS/views/order/print_pdf.php?format=sales_report&from_date=<?= urlencode($fromDate) ?>&to_date=<?= urlencode($toDate) ?>" target="_blank" class="btn btn-danger fw-semibold">
              <i class="bi bi-file-earmark-pdf-fill me-1"></i> Export PDF
            </a>
            <a href="/RMS/views/report/daily_expenses.php?from_date=<?= urlencode($fromDate) ?>&to_date=<?= urlencode($toDate) ?>" class="btn btn-primary fw-semibold">
              <i class="bi bi-wallet2 me-1"></i> Expense Ledger
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">

      <!-- Print-only Official Header -->
      <div class="print-only mb-4 pb-2 border-bottom">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h2 class="fw-bold mb-0 text-primary">RMS RESTAURANT MANAGEMENT SYSTEM</h2>
            <p class="mb-0 text-muted">123 Gourmet Boulevard, Food District | Tel: +1 (555) 890-1234</p>
          </div>
          <div class="text-end">
            <h4 class="fw-bold mb-1">FINANCIAL AUDIT REPORT</h4>
            <div class="badge bg-secondary fs-6">Period: <?= strip_tags($periodLabel) ?></div>
          </div>
        </div>
        <div class="mt-2 text-muted small">Generated on <?= date('Y-m-d h:i A') ?> by Administrator</div>
      </div>

      <!-- Date-to-Date Filter Toolbar -->
      <div class="card shadow-sm border-0 mb-4 rounded-4 no-print">
        <div class="card-body p-4">
          <form method="GET" action="daily.php" class="row g-3 align-items-end">
            <div class="col-lg-3 col-md-4">
              <label class="form-label fw-bold text-secondary small text-uppercase">
                <i class="bi bi-calendar-event me-1"></i> From Date
              </label>
              <input type="date" name="from_date" class="form-control fw-semibold" value="<?= htmlspecialchars($fromDate) ?>">
            </div>

            <div class="col-lg-3 col-md-4">
              <label class="form-label fw-bold text-secondary small text-uppercase">
                <i class="bi bi-calendar-check me-1"></i> To Date
              </label>
              <input type="date" name="to_date" class="form-control fw-semibold" value="<?= htmlspecialchars($toDate) ?>">
            </div>

            <div class="col-lg-2 col-md-4">
              <button type="submit" class="btn btn-primary fw-bold w-100 py-2 shadow-sm">
                <i class="bi bi-funnel-fill me-1"></i> Filter Range
              </button>
            </div>

            <div class="col-lg-4 col-12">
              <label class="form-label fw-bold text-secondary small text-uppercase d-block">Quick Presets</label>
              <div class="btn-group btn-group-sm w-100 flex-wrap">
                <a href="daily.php?from_date=<?= date('Y-m-d') ?>&to_date=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary <?= ($fromDate === date('Y-m-d') && $toDate === date('Y-m-d')) ? 'active' : '' ?>">Today</a>
                <a href="daily.php?from_date=<?= date('Y-m-d', strtotime('-1 day')) ?>&to_date=<?= date('Y-m-d', strtotime('-1 day')) ?>" class="btn btn-outline-secondary">Yesterday</a>
                <a href="daily.php?from_date=<?= date('Y-m-d', strtotime('-6 days')) ?>&to_date=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary">Last 7 Days</a>
                <a href="daily.php?from_date=<?= date('Y-m-01') ?>&to_date=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary <?= ($fromDate === date('Y-m-01') && $toDate === date('Y-m-d')) ? 'active' : '' ?>">This Month</a>
                <a href="daily.php?from_date=&to_date=" class="btn btn-outline-secondary <?= (empty($fromDate) && empty($toDate)) ? 'active' : '' ?>">All Time</a>
              </div>
            </div>
          </form>

          <!-- Current Active Period Banner -->
          <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <span class="text-muted fw-semibold me-2">Selected Range:</span>
              <span class="badge bg-primary-subtle text-primary border border-primary fs-6 px-3 py-2">
                <i class="bi bi-calendar3 me-1"></i> <?= $periodLabel ?>
              </span>
            </div>
            <div class="small text-muted">
              Matching: <strong><?= number_format($totalOrders) ?></strong> Orders & <strong><?= number_format(count($expenses)) ?></strong> Expense Records
            </div>
          </div>
        </div>
      </div>

      <!-- Financial KPI Cards (Sales, Expenses, Net Profit, Orders) -->
      <div class="row g-3 mb-4">
        <!-- 1. Total Sales Revenue -->
        <div class="col-sm-6 col-xl-3">
          <div class="card kpi-card shadow-sm border-0 bg-primary text-white p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <span class="text-white-50 small text-uppercase fw-bold">Sales Revenue</span>
                <h3 class="fw-bold mb-1 mt-1">Rs. <?= number_format($totalRevenue, 2) ?></h3>
                <small class="text-white-75">
                  <i class="bi bi-bag-check me-1"></i><?= number_format($totalOrders) ?> completed orders
                </small>
              </div>
              <div class="p-3 bg-white bg-opacity-25 rounded-circle">
                <i class="bi bi-currency-dollar fs-4"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Total Expenses -->
        <div class="col-sm-6 col-xl-3">
          <div class="card kpi-card shadow-sm border-0 bg-danger text-white p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <span class="text-white-50 small text-uppercase fw-bold">Total Expenses</span>
                <h3 class="fw-bold mb-1 mt-1">Rs. <?= number_format($totalExpenses, 2) ?></h3>
                <small class="text-white-75">
                  <i class="bi bi-receipt me-1"></i><?= number_format(count($expenses)) ?> vouchers recorded
                </small>
              </div>
              <div class="p-3 bg-white bg-opacity-25 rounded-circle">
                <i class="bi bi-wallet2 fs-4"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Net Profit / Loss -->
        <div class="col-sm-6 col-xl-3">
          <div class="card kpi-card shadow-sm border-0 <?= $netProfit >= 0 ? 'bg-success' : 'bg-dark' ?> text-white p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <span class="text-white-50 small text-uppercase fw-bold">
                  <?= $netProfit >= 0 ? 'Net Profit' : 'Net Loss' ?>
                </span>
                <h3 class="fw-bold mb-1 mt-1">
                  <?= $netProfit < 0 ? '-' : '' ?>Rs. <?= number_format(abs($netProfit), 2) ?>
                </h3>
                <small class="badge bg-white <?= $netProfit >= 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                  <?= number_format($profitMargin, 1) ?>% Margin
                </small>
              </div>
              <div class="p-3 bg-white bg-opacity-25 rounded-circle">
                <i class="bi <?= $netProfit >= 0 ? 'bi-graph-up' : 'bi-graph-down' ?> fs-4"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. Tax & Discounts Summary -->
        <div class="col-sm-6 col-xl-3">
          <div class="card kpi-card shadow-sm border-0 bg-dark text-white p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <span class="text-white-50 small text-uppercase fw-bold">Tax & Discounts</span>
                <h4 class="fw-bold mb-1 mt-1">Rs. <?= number_format($totalTax, 2) ?></h4>
                <small class="text-warning">
                  <i class="bi bi-tag-fill me-1"></i>Discounts: Rs. <?= number_format($totalDiscount, 2) ?>
                </small>
              </div>
              <div class="p-3 bg-white bg-opacity-25 rounded-circle">
                <i class="bi bi-pie-chart-fill fs-4"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Day-by-Day Financial Ledger Table -->
      <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h5 class="card-title mb-0 fw-bold">
            <i class="bi bi-calendar3-range text-primary me-2"></i>Day-by-Day Financial Breakdown
          </h5>
          <span class="badge bg-secondary-subtle text-secondary border border-secondary px-3 py-1">
            <?= count($dailyLedger) ?> Active Days in Range
          </span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover table-striped align-middle mb-0">
            <thead class="table-dark">
              <tr>
                <th style="width: 140px;">Date</th>
                <th class="text-center" style="width: 100px;">Orders</th>
                <th class="text-end" style="width: 170px;">Sales Revenue</th>
                <th class="text-end" style="width: 170px;">Expenses</th>
                <th class="text-end" style="width: 180px;">Net Profit</th>
                <th class="text-center" style="width: 120px;">Margin %</th>
                <th class="text-center" style="width: 130px;">Financial Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($dailyLedger) > 0): ?>
                <?php foreach ($dailyLedger as $day => $data): 
                  $dSales = $data['sales'];
                  $dExp = $data['expenses'];
                  $dProfit = $dSales - $dExp;
                  $dMargin = ($dSales > 0) ? ($dProfit / $dSales) * 100 : 0;
                ?>
                  <tr>
                    <td class="fw-bold">
                      <i class="bi bi-calendar-day text-secondary me-1"></i>
                      <?= date('M d, Y', strtotime($day)) ?>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-primary-subtle text-primary rounded-pill px-3">
                        <?= $data['orders'] ?>
                      </span>
                    </td>
                    <td class="text-end fw-bold text-success">
                      Rs. <?= number_format($dSales, 2) ?>
                    </td>
                    <td class="text-end fw-bold text-danger">
                      Rs. <?= number_format($dExp, 2) ?>
                    </td>
                    <td class="text-end fw-bold <?= $dProfit >= 0 ? 'text-success' : 'text-danger' ?>">
                      <?= $dProfit < 0 ? '-' : '' ?>Rs. <?= number_format(abs($dProfit), 2) ?>
                    </td>
                    <td class="text-center fw-semibold">
                      <?= number_format($dMargin, 1) ?>%
                    </td>
                    <td class="text-center">
                      <?php if ($dProfit > 0): ?>
                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                          <i class="bi bi-arrow-up-circle me-1"></i> Profitable
                        </span>
                      <?php elseif ($dProfit < 0): ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">
                          <i class="bi bi-arrow-down-circle me-1"></i> Deficit
                        </span>
                      <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary px-2 py-1">
                          Break Even
                        </span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <!-- Summary Total Row -->
                <tr class="table-light fw-bold border-top border-2">
                  <td>TOTAL FOR PERIOD</td>
                  <td class="text-center"><?= $totalOrders ?></td>
                  <td class="text-end text-success">Rs. <?= number_format($totalRevenue, 2) ?></td>
                  <td class="text-end text-danger">Rs. <?= number_format($totalExpenses, 2) ?></td>
                  <td class="text-end <?= $netProfit >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $netProfit < 0 ? '-' : '' ?>Rs. <?= number_format(abs($netProfit), 2) ?>
                  </td>
                  <td class="text-center"><?= number_format($profitMargin, 1) ?>%</td>
                  <td class="text-center">
                    <span class="badge <?= $netProfit >= 0 ? 'bg-success' : 'bg-danger' ?> px-2 py-1">
                      <?= $netProfit >= 0 ? 'NET PROFIT' : 'NET LOSS' ?>
                    </span>
                  </td>
                </tr>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center py-4 text-muted">
                    <i class="bi bi-info-circle me-1"></i> No sales or expense transactions recorded for the selected period.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Orders Breakdown Table -->
      <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h5 class="card-title mb-0 fw-bold">
            <i class="bi bi-receipt text-primary me-2"></i>Orders in Selected Period (<?= count($orders) ?>)
          </h5>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success px-3 py-1">
              Revenue: Rs. <?= number_format($totalRevenue, 2) ?>
            </span>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 60px;">#</th>
                <th>Order Number</th>
                <th>Date & Time</th>
                <th>Order Type</th>
                <th>Customer</th>
                <th>Payment Method</th>
                <th class="text-end">Amount</th>
                <th class="text-center no-print" style="width: 100px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($orders) > 0): ?>
                <?php $sn = 1; foreach ($orders as $ord): ?>
                  <tr>
                    <td class="fw-bold text-muted"><?= $sn++ ?></td>
                    <td class="fw-bold text-primary">
                      <a href="/RMS/views/order/details.php?id=<?= $ord['id'] ?>" class="text-decoration-none">
                        <?= htmlspecialchars($ord['order_number'] ?: ('ORD-' . $ord['id'])) ?>
                      </a>
                    </td>
                    <td>
                      <small class="text-muted d-block"><?= date('Y-m-d', strtotime($ord['created_at'])) ?></small>
                      <span class="fw-semibold"><?= date('h:i A', strtotime($ord['created_at'])) ?></span>
                    </td>
                    <td>
                      <span class="badge bg-primary-subtle text-primary border border-primary">
                        <?= htmlspecialchars($ord['order_type'] ?: 'Dine-In') ?>
                      </span>
                    </td>
                    <td><?= htmlspecialchars($ord['customer_name'] ?: 'Walk-in Customer') ?></td>
                    <td>
                      <span class="badge bg-light text-dark border">
                        <?= htmlspecialchars($ord['payment_method'] ?: 'Cash') ?>
                      </span>
                    </td>
                    <td class="text-end fw-bold text-success fs-6">
                      Rs. <?= number_format(floatval($ord['grand_total'] ?? $ord['total'] ?? 0), 2) ?>
                    </td>
                    <td class="text-center no-print">
                      <a href="/RMS/views/order/details.php?id=<?= $ord['id'] ?>" class="btn btn-outline-primary btn-sm px-2" title="View Order">
                        <i class="bi bi-eye"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="text-center py-4 text-muted">
                    No orders found within this date range.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</main>

<?php 
include_once __DIR__ . '/../layouts/footer.php'; 
ob_end_flush();
?>
