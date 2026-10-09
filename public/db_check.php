<?php
/**
 * RMS Database Diagnostic Center
 * Directly inspects and diagnoses Railway, Docker, and Localhost database connectivity.
 */
include_once __DIR__ . '/../config/database.php';

$pageTitle = "Database Diagnostic Center";
$isDbConnected = ($conn && !$conn->connect_error);

$activeDb = '';
$tables = [];
if ($isDbConnected) {
    $dbRes = $conn->query("SELECT DATABASE()");
    if ($dbRes) $activeDb = $dbRes->fetch_row()[0] ?? '';
    $tblRes = $conn->query("SHOW TABLES");
    if ($tblRes) {
        while ($r = $tblRes->fetch_row()) {
            $tables[] = $r[0];
        }
    }
}

// Gather environment status (mask sensitive passwords)
$envVarsToCheck = [
    'MYSQLHOST', 'MYSQLPORT', 'MYSQLUSER', 'MYSQLDATABASE', 'MYSQL_URL',
    'DATABASE_URL', 'DB_HOST', 'DB_PORT', 'DB_USER', 'DB_NAME', 'PORT',
    'RAILWAY_ENVIRONMENT', 'RAILWAY_SERVICE_NAME'
];

$detectedEnv = [];
foreach ($envVarsToCheck as $var) {
    $val = get_rms_db_env($var);
    if ($val !== null && $val !== '') {
        if (strpos($var, 'PASSWORD') !== false || strpos($var, 'PASS') !== false) {
            $masked = substr($val, 0, 1) . str_repeat('•', max(4, strlen($val) - 2)) . substr($val, -1);
            $detectedEnv[$var] = $masked;
        } elseif (strpos($var, 'URL') !== false) {
            $masked = preg_replace('#://([^:]+):([^@]+)@#', '://$1:••••••••@', $val);
            $detectedEnv[$var] = $masked;
        } else {
            $detectedEnv[$var] = $val;
        }
    } else {
        $detectedEnv[$var] = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RMS Database Diagnostic Center</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body { background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    .card-diag { border: none; border-radius: 14px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
    .terminal-box { background: #1e293b; color: #38bdf8; border-radius: 10px; font-family: monospace; font-size: 0.85rem; padding: 1rem; max-height: 250px; overflow-y: auto; }
  </style>
</head>
<body class="py-4">
  <div class="container" style="max-width: 850px;">
    
    <div class="text-center mb-4">
      <h2 class="fw-bold text-dark"><i class="bi bi-database-fill-gear text-primary me-2"></i>RMS Database Diagnostic</h2>
      <p class="text-muted">Live inspection for Railway Cloud, Docker containers, and Localhost MariaDB</p>
    </div>

    <!-- Status Callout -->
    <div class="card card-diag mb-4 border-start border-4 <?= $isDbConnected ? 'border-success' : 'border-danger' ?>">
      <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="fs-1 <?= $isDbConnected ? 'text-success' : 'text-danger' ?>">
            <i class="bi <?= $isDbConnected ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>"></i>
          </div>
          <div>
            <h4 class="fw-bold mb-1 <?= $isDbConnected ? 'text-success' : 'text-danger' ?>">
              <?= $isDbConnected ? 'Database Connected Successfully!' : 'Database Connection Failed' ?>
            </h4>
            <p class="mb-0 text-muted small">
              <?= $isDbConnected 
                ? 'Connected to database <strong>' . htmlspecialchars($activeDb) . '</strong> (' . count($tables) . ' tables loaded)' 
                : htmlspecialchars(rms_db_last_error()) ?>
            </p>
          </div>
        </div>
        <div>
          <a href="javascript:location.reload();" class="btn btn-outline-primary fw-semibold">
            <i class="bi bi-arrow-clockwise me-1"></i> Retest
          </a>
          <a href="/RMS/public/index.php" class="btn btn-primary fw-semibold ms-1">
            <i class="bi bi-speedometer2 me-1"></i> Dashboard
          </a>
        </div>
      </div>
    </div>

    <?php if ($isDbConnected): ?>
      <!-- Database & Tables Info -->
      <div class="card card-diag mb-4 bg-white">
        <div class="card-header bg-white fw-bold py-3 border-bottom d-flex justify-content-between align-items-center">
          <span><i class="bi bi-table me-2 text-success"></i>Active Schema Status (Database: <code><?= htmlspecialchars($activeDb) ?></code>)</span>
          <a href="/RMS/config/init_db.php?force=1" class="btn btn-sm btn-outline-success">
            <i class="bi bi-arrow-repeat me-1"></i> Sync / Seed Schema
          </a>
        </div>
        <div class="card-body p-4">
          <p class="small text-muted mb-2">Total tables detected in database: <strong><?= count($tables) ?></strong></p>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($tables as $t): ?>
              <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-check2 text-success me-1"></i><?= htmlspecialchars($t) ?></span>
            <?php endforeach; ?>
          </div>
          <?php if (count($tables) === 0): ?>
            <div class="alert alert-warning mt-3 mb-0">
              <i class="bi bi-exclamation-triangle me-1"></i> Database is connected but no tables exist yet. Click the <strong>Sync / Seed Schema</strong> button above to load initial data.
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
      <!-- Railway Quick-Fix Guide -->
      <div class="card card-diag mb-4 bg-white">
        <div class="card-header bg-danger-subtle text-danger fw-bold border-0 py-3">
          <i class="bi bi-lightbulb-fill me-2"></i> How to Fix on Railway (Step-by-Step)
        </div>
        <div class="card-body p-4">
          <ol class="mb-3 lh-lg">
            <li>Open your <strong><a href="https://railway.app/dashboard" target="_blank" class="text-danger fw-bold">Railway.app Project Dashboard</a></strong>.</li>
            <li>Click on your <strong>RMS Web Service</strong> card.</li>
            <li>Go to the <strong>Variables</strong> tab at the top.</li>
            <li>Click <strong>+ New Variable</strong> &rarr; Click <strong>Add Reference</strong>.</li>
            <li>Select your <strong>MySQL</strong> database service. Railway will automatically link:
              <code>MYSQLHOST</code>, <code>MYSQLUSER</code>, <code>MYSQLPASSWORD</code>, <code>MYSQLDATABASE</code>, and <code>MYSQL_URL</code>.
            </li>
            <li>Railway will trigger an automatic redeployment. In ~30 seconds, refresh this page!</li>
          </ol>
          <div class="alert alert-info small mb-0">
            <i class="bi bi-info-circle me-1"></i> <strong>Tip:</strong> If you haven't added a MySQL database in Railway yet, click <em>+ Create</em> &rarr; <em>Database</em> &rarr; <em>Add MySQL</em> first!
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Connection Attempts Log -->
    <div class="card card-diag mb-4 bg-white">
      <div class="card-header bg-white fw-bold py-3 border-bottom">
        <i class="bi bi-terminal-split me-2 text-primary"></i>Connection Attempts Trace
      </div>
      <div class="card-body p-3">
        <div class="terminal-box">
          <?php if (!empty($GLOBALS['RMS_DB_ATTEMPTS'])): ?>
            <?php foreach ($GLOBALS['RMS_DB_ATTEMPTS'] as $att): ?>
              <div>&gt; <?= htmlspecialchars($att) ?></div>
            <?php endforeach; ?>
          <?php else: ?>
            <div>&gt; No connection attempts recorded.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Detected Environment Variables -->
    <div class="card card-diag mb-4 bg-white">
      <div class="card-header bg-white fw-bold py-3 border-bottom">
        <i class="bi bi-sliders me-2 text-primary"></i>Runtime Environment Variables
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-striped mb-0 align-middle small">
          <thead class="table-light">
            <tr>
              <th>Variable Name</th>
              <th>Status</th>
              <th>Detected Value (Masked)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($detectedEnv as $key => $val): ?>
              <tr>
                <td class="font-monospace fw-bold"><?= htmlspecialchars($key) ?></td>
                <td>
                  <?php if ($val !== null): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">DETECTED</span>
                  <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary border">NOT SET</span>
                  <?php endif; ?>
                </td>
                <td class="font-monospace text-muted"><?= $val !== null ? htmlspecialchars($val) : '<em class="text-secondary">empty</em>' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</body>
</html>
