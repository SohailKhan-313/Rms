<?php
ob_start();
$pageTitle = "Daily Expenses & Audit";
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
        // Default to current month: from 1st of month to today
        $fromDate = date('Y-m-01');
        $toDate = date('Y-m-d');
    }
}

$msg = "";
$error = "";

// 1. Handle Add Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_expense') {
    $date = !empty($_POST['date']) ? $_POST['date'] : date('Y-m-d');
    $rp = trim($_POST['rp'] ?? '');
    $catagory = trim($_POST['catagory'] ?? 'General');
    $amount = floatval($_POST['amount'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if ($amount <= 0) {
        $error = "Please enter a valid expense amount greater than 0.";
    } elseif (empty($rp)) {
        $error = "Responsible person / payee name is required.";
    } elseif ($conn) {
        $stmt = $conn->prepare("INSERT INTO `expenses` (`date`, `rp`, `amount`, `catagory`, `note`) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssdss", $date, $rp, $amount, $catagory, $note);
            if ($stmt->execute()) {
                $msg = "Expense of <strong>Rs. " . number_format($amount, 2) . "</strong> recorded successfully!";
            } else {
                $error = "Database Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// 2. Handle Edit Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_expense') {
    $id = intval($_POST['expense_id'] ?? 0);
    $date = !empty($_POST['date']) ? $_POST['date'] : date('Y-m-d');
    $rp = trim($_POST['rp'] ?? '');
    $catagory = trim($_POST['catagory'] ?? 'General');
    $amount = floatval($_POST['amount'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if ($id > 0 && $amount > 0 && $conn) {
        $stmt = $conn->prepare("UPDATE `expenses` SET `date` = ?, `rp` = ?, `amount` = ?, `catagory` = ?, `note` = ? WHERE `id` = ?");
        if ($stmt) {
            $stmt->bind_param("ssdssi", $date, $rp, $amount, $catagory, $note, $id);
            if ($stmt->execute()) {
                $msg = "Expense record updated successfully!";
            } else {
                $error = "Database Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// 3. Handle Delete Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_expense') {
    $id = intval($_POST['delete_id'] ?? 0);
    if ($id > 0 && $conn) {
        $stmt = $conn->prepare("DELETE FROM `expenses` WHERE `id` = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $msg = "Expense record deleted successfully.";
            } else {
                $error = "Error deleting expense: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// 4. Fetch expenses & sales for selected date range
$expenses = [];
$totalExpenses = 0.0;
$catBreakdown = [];
$periodSales = 0.0;
$periodOrdersCount = 0;
$periodTax = 0.0;
$periodDiscount = 0.0;
$dailyBreakdown = [];

if ($conn) {
    // Expense WHERE filter
    $whereExp = [];
    if (!empty($fromDate)) {
        $fromSafe = mysqli_real_escape_string($conn, $fromDate);
        $whereExp[] = "`date` >= '$fromSafe'";
    }
    if (!empty($toDate)) {
        $toSafe = mysqli_real_escape_string($conn, $toDate);
        $whereExp[] = "`date` <= '$toSafe'";
    }
    $whereExpSql = !empty($whereExp) ? "WHERE " . implode(" AND ", $whereExp) : "";

    $expRes = $conn->query("SELECT * FROM `expenses` $whereExpSql ORDER BY `date` DESC, `id` DESC");
    if ($expRes) {
        while ($row = $expRes->fetch_assoc()) {
            $expenses[] = $row;
            $amt = floatval($row['amount']);
            $totalExpenses += $amt;
            $cat = !empty($row['catagory']) ? $row['catagory'] : 'Uncategorized';
            if (!isset($catBreakdown[$cat])) $catBreakdown[$cat] = 0.0;
            $catBreakdown[$cat] += $amt;

            // Daily breakdown accumulation
            $d = $row['date'];
            if (!isset($dailyBreakdown[$d])) {
                $dailyBreakdown[$d] = ['sales' => 0.0, 'expenses' => 0.0, 'orders' => 0];
            }
            $dailyBreakdown[$d]['expenses'] += $amt;
        }
    }

    // Orders (Sales) WHERE filter
    $whereOrd = [];
    if (!empty($fromDate)) {
        $fromSafe = mysqli_real_escape_string($conn, $fromDate);
        $whereOrd[] = "DATE(created_at) >= '$fromSafe'";
    }
    if (!empty($toDate)) {
        $toSafe = mysqli_real_escape_string($conn, $toDate);
        $whereOrd[] = "DATE(created_at) <= '$toSafe'";
    }
    $whereOrdSql = !empty($whereOrd) ? "WHERE " . implode(" AND ", $whereOrd) : "";

    $salesRes = $conn->query("SELECT 
        COUNT(*) as total_orders,
        SUM(COALESCE(grand_total, total, 0)) as total_sales,
        SUM(COALESCE(tax, 0)) as total_tax,
        SUM(COALESCE(discount, 0)) as total_discount
        FROM `orders` $whereOrdSql");
    if ($salesRes && $sRow = $salesRes->fetch_assoc()) {
        $periodSales = floatval($sRow['total_sales'] ?? 0);
        $periodOrdersCount = intval($sRow['total_orders'] ?? 0);
        $periodTax = floatval($sRow['total_tax'] ?? 0);
        $periodDiscount = floatval($sRow['total_discount'] ?? 0);
    }

    // Daily breakdown for sales
    $dailySalesRes = $conn->query("SELECT 
        DATE(created_at) as order_date, 
        COUNT(*) as order_cnt,
        SUM(COALESCE(grand_total, total, 0)) as daily_rev 
        FROM `orders` $whereOrdSql 
        GROUP BY DATE(created_at)");
    if ($dailySalesRes) {
        while ($dsRow = $dailySalesRes->fetch_assoc()) {
            $d = $dsRow['order_date'];
            if (!isset($dailyBreakdown[$d])) {
                $dailyBreakdown[$d] = ['sales' => 0.0, 'expenses' => 0.0, 'orders' => 0];
            }
            $dailyBreakdown[$d]['sales'] += floatval($dsRow['daily_rev']);
            $dailyBreakdown[$d]['orders'] += intval($dsRow['order_cnt']);
        }
    }

    krsort($dailyBreakdown); // sort by date descending
}

$netProfit = $periodSales - $totalExpenses;
$profitMargin = ($periodSales > 0) ? ($netProfit / $periodSales) * 100 : 0.0;

// Human-friendly Period Label
$periodLabel = '';
if (!empty($fromDate) && !empty($toDate)) {
    if ($fromDate === $toDate) {
        $periodLabel = date('F d, Y (l)', strtotime($fromDate));
    } else {
        $periodLabel = date('M d, Y', strtotime($fromDate)) . ' — ' . date('M d, Y', strtotime($toDate));
    }
} elseif (!empty($fromDate)) {
    $periodLabel = 'From ' . date('M d, Y', strtotime($fromDate));
} elseif (!empty($toDate)) {
    $periodLabel = 'Up to ' . date('M d, Y', strtotime($toDate));
} else {
    $periodLabel = 'All Recorded Dates';
}

include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="app-main">
  <!-- Content Header -->
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h3 class="mb-0 fw-bold">Sales, Expenses & Profit Audit</h3>
          <small class="text-muted">Financial audit with custom date-to-date range filtering</small>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="/RMS/public/index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="/RMS/views/report/daily.php">Reports</a></li>
            <li class="breadcrumb-item active" aria-current="page">Expenses & Profit</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">

      <!-- Print-only Official Header (Shown strictly when printing or in PDF) -->
      <div class="print-only print-header">
        <h2>RMS RESTAURANT MANAGEMENT SYSTEM</h2>
        <p>123 Gourmet Boulevard, Food District | Tel: +1 (555) 890-1234</p>
        <h4 style="margin: 8px 0 2px 0; font-weight: bold; text-decoration: underline;">
          FINANCIAL PERFORMANCE AUDIT STATEMENT (SALES, EXPENSES & PROFIT)
        </h4>
        <p><strong>Report Period:</strong> <?= $periodLabel ?> | <strong>Printed On:</strong> <?= date('Y-m-d h:i A') ?> by Admin</p>
      </div>

      <!-- Alerts -->
      <?php if (!empty($msg)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
          <i class="bi bi-check-circle-fill me-2"></i> <?= $msg ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <!-- Filter & Action Toolbar: From Date to To Date -->
      <div class="card shadow-sm border-0 mb-4 rounded-3 no-print">
        <div class="card-body p-3">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
              <div class="d-flex align-items-center gap-1">
                <label class="fw-semibold text-muted small mb-0"><i class="bi bi-calendar-event me-1"></i>From:</label>
                <input type="date" name="from_date" class="form-control form-control-sm" style="width: 145px;" value="<?= htmlspecialchars($fromDate) ?>">
              </div>
              <div class="d-flex align-items-center gap-1">
                <label class="fw-semibold text-muted small mb-0"><i class="bi bi-calendar-check me-1"></i>To:</label>
                <input type="date" name="to_date" class="form-control form-control-sm" style="width: 145px;" value="<?= htmlspecialchars($toDate) ?>">
              </div>
              <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">
                <i class="bi bi-filter me-1"></i> Filter Range
              </button>
              <div class="btn-group btn-group-sm">
                <a href="daily_expenses.php?from_date=<?= date('Y-m-d') ?>&to_date=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary <?= ($fromDate === date('Y-m-d') && $toDate === date('Y-m-d')) ? 'active' : '' ?>">Today</a>
                <a href="daily_expenses.php?from_date=<?= date('Y-m-d', strtotime('-1 day')) ?>&to_date=<?= date('Y-m-d', strtotime('-1 day')) ?>" class="btn btn-outline-secondary">Yesterday</a>
                <a href="daily_expenses.php?from_date=<?= date('Y-m-d', strtotime('-6 days')) ?>&to_date=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary">Last 7 Days</a>
                <a href="daily_expenses.php?from_date=<?= date('Y-m-01') ?>&to_date=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary <?= ($fromDate === date('Y-m-01') && $toDate === date('Y-m-d')) ? 'active' : '' ?>">This Month</a>
                <a href="daily_expenses.php?from_date=&to_date=" class="btn btn-outline-secondary <?= (empty($fromDate) && empty($toDate)) ? 'active' : '' ?>">All Time</a>
              </div>
            </form>

            <div class="d-flex align-items-center gap-2">
              <!-- Direct PDF Export -->
              <a href="/RMS/views/order/print_pdf.php?format=expenses&from_date=<?= urlencode($fromDate) ?>&to_date=<?= urlencode($toDate) ?>" target="_blank" class="btn btn-danger btn-sm fw-semibold">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Export PDF Statement
              </a>

              <!-- Add Expense Trigger -->
              <button type="button" class="btn btn-success btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                <i class="bi bi-plus-circle me-1"></i> Record Expense
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Active Period Banner -->
      <div class="alert alert-light border shadow-sm py-2 px-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2 rounded-3 no-print">
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-primary px-2 py-1"><i class="bi bi-clock-history me-1"></i>Active Range</span>
          <strong class="text-dark"><?= $periodLabel ?></strong>
        </div>
        <div class="small text-muted">
          Showing data for <strong><?= count($dailyBreakdown) ?></strong> days with recorded transactions
        </div>
      </div>

      <!-- Financial KPI Summary Cards: Sales, Expenses, Profit & Margin -->
      <div class="row g-3 mb-4">
        <!-- 1. Total Sales Revenue -->
        <div class="col-sm-6 col-xl-3">
          <div class="card shadow-sm border-0 border-start border-success border-4 rounded-3 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small text-uppercase fw-semibold">Sales Revenue</span>
                <h3 class="fw-bold text-success mb-0 mt-1">Rs. <?= number_format($periodSales, 2) ?></h3>
                <small class="text-muted"><?= $periodOrdersCount ?> orders completed</small>
              </div>
              <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle fs-3">
                <i class="bi bi-graph-up-arrow"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Total Expenses -->
        <div class="col-sm-6 col-xl-3">
          <div class="card shadow-sm border-0 border-start border-danger border-4 rounded-3 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small text-uppercase fw-semibold">Total Expenses</span>
                <h3 class="fw-bold text-danger mb-0 mt-1">Rs. <?= number_format($totalExpenses, 2) ?></h3>
                <small class="text-muted"><?= count($expenses) ?> expenses logged</small>
              </div>
              <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle fs-3">
                <i class="bi bi-wallet2"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Net Balance / Profit -->
        <div class="col-sm-6 col-xl-3">
          <div class="card shadow-sm border-0 border-start border-<?= $netProfit >= 0 ? 'primary' : 'danger' ?> border-4 rounded-3 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small text-uppercase fw-semibold">Net Profit / Margin</span>
                <h3 class="fw-bold text-<?= $netProfit >= 0 ? 'primary' : 'danger' ?> mb-0 mt-1">
                  <?= $netProfit < 0 ? '-' : '' ?>Rs. <?= number_format(abs($netProfit), 2) ?>
                </h3>
                <small class="text-<?= $netProfit >= 0 ? 'success' : 'danger' ?> fw-semibold">
                  <?= $netProfit >= 0 ? '<i class="bi bi-check-circle me-1"></i>Profitable' : '<i class="bi bi-exclamation-circle me-1"></i>Net Deficit' ?>
                </small>
              </div>
              <div class="bg-<?= $netProfit >= 0 ? 'primary' : 'danger' ?> bg-opacity-10 text-<?= $netProfit >= 0 ? 'primary' : 'danger' ?> p-3 rounded-circle fs-3">
                <i class="bi bi-cash-stack"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. Profit Margin % & Categories -->
        <div class="col-sm-6 col-xl-3">
          <div class="card shadow-sm border-0 border-start border-info border-4 rounded-3 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small text-uppercase fw-semibold">Profit Margin %</span>
                <h3 class="fw-bold text-info mb-0 mt-1"><?= number_format($profitMargin, 1) ?>%</h3>
                <small class="text-muted"><?= count($catBreakdown) ?> expense categories</small>
              </div>
              <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle fs-3">
                <i class="bi bi-pie-chart-fill"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Day-by-Day Financial Comparison Table (Only shown when multi-day or single day in range) -->
      <?php if (!empty($dailyBreakdown)): ?>
        <div class="card shadow-sm border-0 rounded-3 mb-4">
          <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="card-title mb-0 fw-bold">
              <i class="bi bi-bar-chart-line-fill text-primary me-2"></i>
              Day-by-Day Financial Comparison (Sales vs Expenses vs Profit)
            </h5>
            <span class="badge bg-light text-dark border">
              <?= count($dailyBreakdown) ?> Active Recorded Day<?= count($dailyBreakdown) > 1 ? 's' : '' ?>
            </span>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Date</th>
                  <th class="text-end">POS Sales (Rs.)</th>
                  <th class="text-end">Expenses (Rs.)</th>
                  <th class="text-end">Net Profit (Rs.)</th>
                  <th class="text-end">Margin</th>
                  <th class="text-center">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($dailyBreakdown as $dDate => $dData): 
                  $dProfit = $dData['sales'] - $dData['expenses'];
                  $dMargin = ($dData['sales'] > 0) ? ($dProfit / $dData['sales']) * 100 : 0;
                ?>
                  <tr>
                    <td class="fw-bold">
                      <i class="bi bi-calendar3 me-1 text-primary"></i>
                      <?= date('M d, Y (D)', strtotime($dDate)) ?>
                    </td>
                    <td class="text-end fw-bold text-success">
                      Rs. <?= number_format($dData['sales'], 2) ?>
                      <small class="text-muted d-block" style="font-size: 0.72rem;"><?= $dData['orders'] ?> order<?= $dData['orders'] != 1 ? 's' : '' ?></small>
                    </td>
                    <td class="text-end fw-bold text-danger">
                      Rs. <?= number_format($dData['expenses'], 2) ?>
                    </td>
                    <td class="text-end fw-bold text-<?= $dProfit >= 0 ? 'primary' : 'danger' ?>">
                      <?= $dProfit < 0 ? '-' : '' ?>Rs. <?= number_format(abs($dProfit), 2) ?>
                    </td>
                    <td class="text-end fw-semibold">
                      <?= number_format($dMargin, 1) ?>%
                    </td>
                    <td class="text-center">
                      <?php if ($dProfit > 0): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Profit</span>
                      <?php elseif ($dProfit < 0): ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Deficit</span>
                      <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Break-even</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>

      <!-- Category Breakdown Pills (Screen view) -->
      <?php if (!empty($catBreakdown)): ?>
        <div class="mb-3 d-flex flex-wrap gap-2 no-print">
          <span class="fw-semibold small text-muted align-self-center me-1">Expense Categories:</span>
          <?php foreach ($catBreakdown as $cName => $cSum): ?>
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
              <strong class="text-primary"><?= htmlspecialchars(ucfirst($cName)) ?>:</strong> 
              Rs. <?= number_format($cSum, 2) ?>
            </span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Main Expense Table Card -->
      <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0 fw-bold">
            <i class="bi bi-card-checklist text-primary me-2"></i>
            Detailed Expense Records for <?= $periodLabel ?>
          </h5>
          <span class="badge bg-danger fs-6 no-print">Total: Rs. <?= number_format($totalExpenses, 2) ?></span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover table-striped align-middle mb-0">
            <thead class="table-dark">
              <tr>
                <th style="width: 50px;">#</th>
                <th style="width: 110px;">Date</th>
                <th>Responsible Person / Payee</th>
                <th>Category</th>
                <th>Description / Memo</th>
                <th class="text-end" style="width: 140px;">Amount</th>
                <th class="text-center no-print" style="width: 130px;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($expenses) > 0): ?>
                <?php $sn = 1; foreach ($expenses as $exp): ?>
                  <tr>
                    <td class="fw-bold"><?= $sn++ ?></td>
                    <td><?= htmlspecialchars($exp['date']) ?></td>
                    <td class="fw-semibold">
                      <i class="bi bi-person-fill text-secondary me-1"></i>
                      <?= htmlspecialchars($exp['rp']) ?>
                    </td>
                    <td>
                      <span class="badge bg-secondary-subtle text-secondary border border-secondary">
                        <?= htmlspecialchars(ucfirst($exp['catagory'])) ?>
                      </span>
                    </td>
                    <td>
                      <small class="text-muted"><?= htmlspecialchars($exp['note'] ?: 'No notes') ?></small>
                    </td>
                    <td class="text-end fw-bold text-danger fs-6">
                      Rs. <?= number_format(floatval($exp['amount']), 2) ?>
                    </td>
                    <td class="text-center no-print">
                      <div class="btn-group btn-group-sm">
                        <!-- Edit Button -->
                        <button type="button" class="btn btn-outline-warning" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editExpenseModal<?= $exp['id'] ?>"
                                title="Edit Expense">
                          <i class="bi bi-pencil-square"></i>
                        </button>
                        <!-- Delete Button -->
                        <button type="button" class="btn btn-outline-danger" 
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteExpenseModal<?= $exp['id'] ?>"
                                title="Delete Expense">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>

                      <!-- Edit Expense Modal -->
                      <div class="modal fade" id="editExpenseModal<?= $exp['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content text-start border-0 shadow">
                            <div class="modal-header bg-warning text-dark py-3">
                              <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Expense</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" action="daily_expenses.php?from_date=<?= urlencode($fromDate) ?>&to_date=<?= urlencode($toDate) ?>">
                              <input type="hidden" name="action" value="edit_expense">
                              <input type="hidden" name="expense_id" value="<?= $exp['id'] ?>">
                              <div class="modal-body p-4">
                                <div class="mb-3">
                                  <label class="form-label fw-semibold">Expense Date</label>
                                  <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($exp['date']) ?>" required>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label fw-semibold">Paid To / Responsible Person</label>
                                  <input type="text" name="rp" class="form-control" value="<?= htmlspecialchars($exp['rp']) ?>" required>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label fw-semibold">Category</label>
                                  <input type="text" name="catagory" class="form-control" value="<?= htmlspecialchars($exp['catagory']) ?>" required>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label fw-semibold">Amount (Rs.)</label>
                                  <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="<?= htmlspecialchars($exp['amount']) ?>" required>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label fw-semibold">Notes / Purpose</label>
                                  <textarea name="note" class="form-control" rows="2"><?= htmlspecialchars($exp['note']) ?></textarea>
                                </div>
                              </div>
                              <div class="modal-footer border-top p-3">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-warning fw-semibold px-4">Update Expense</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>

                      <!-- Delete Expense Modal -->
                      <div class="modal fade" id="deleteExpenseModal<?= $exp['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content text-start border-0 shadow">
                            <form method="POST" action="daily_expenses.php?from_date=<?= urlencode($fromDate) ?>&to_date=<?= urlencode($toDate) ?>">
                              <input type="hidden" name="action" value="delete_expense">
                              <input type="hidden" name="delete_id" value="<?= $exp['id'] ?>">
                              <div class="modal-header bg-danger text-white py-3">
                                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-octagon me-2"></i>Delete Expense</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body p-4">
                                <p class="mb-0">Are you sure you want to delete this expense of <strong>Rs. <?= number_format(floatval($exp['amount']), 2) ?></strong> (<?= htmlspecialchars($exp['catagory']) ?>)?</p>
                              </div>
                              <div class="modal-footer border-top p-3">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger fw-semibold px-4">Yes, Delete</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>

                    </td>
                  </tr>
                <?php endforeach; ?>
                <!-- Total Row -->
                <tr class="table-light fw-bold">
                  <td colspan="5" class="text-end text-uppercase">Total Period Expenses:</td>
                  <td class="text-end text-danger fs-6">Rs. <?= number_format($totalExpenses, 2) ?></td>
                  <td class="no-print"></td>
                </tr>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 text-secondary"></i>
                    <h5 class="mt-2">No expense records found for this period</h5>
                    <p class="text-muted">Use the button above to record expenses or choose a different date range.</p>
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

<!-- Modal: Add Expense -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header bg-primary text-white py-3">
        <h5 class="modal-title fw-bold" id="addExpenseModalLabel">
          <i class="bi bi-plus-circle me-2"></i>Record New Daily Expense
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form method="POST" action="daily_expenses.php?from_date=<?= urlencode($fromDate) ?>&to_date=<?= urlencode($toDate) ?>">
        <input type="hidden" name="action" value="add_expense">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-semibold">Expense Date <span class="text-danger">*</span></label>
            <input type="date" name="date" class="form-control" value="<?= (!empty($toDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) ? htmlspecialchars($toDate) : date('Y-m-d') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Responsible Person / Payee <span class="text-danger">*</span></label>
            <input type="text" name="rp" class="form-control" placeholder="e.g. Sohail (Manager), Chef John, Electric Co." required autofocus>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
            <select name="catagory" class="form-select" required>
              <option value="Electricity">Electricity / Utilities</option>
              <option value="Gas">Gas / Fuel</option>
              <option value="Kitchen Supplies">Kitchen Supplies</option>
              <option value="Vegetables & Meat">Vegetables & Meat</option>
              <option value="Bakery & Dairy">Bakery & Dairy</option>
              <option value="Staff Salary">Staff Daily Wage / Salary</option>
              <option value="Maintenance">Equipment Maintenance</option>
              <option value="Cleaning">Cleaning & Hygiene</option>
              <option value="Miscellaneous" selected>Miscellaneous</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Amount (Rs.) <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">Rs.</span>
              <input type="number" step="0.01" min="0.01" name="amount" class="form-control fw-bold" placeholder="0.00" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Notes / Invoice # / Memo</label>
            <textarea name="note" class="form-control" rows="2" placeholder="e.g. Monthly bill paid in cash, receipt #8901"></textarea>
          </div>
        </div>

        <div class="modal-footer border-top p-3">
          <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold px-4">
            <i class="bi bi-check-circle me-1"></i> Save Expense
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php 
include_once __DIR__ . '/../layouts/footer.php';
ob_end_flush();
?>
