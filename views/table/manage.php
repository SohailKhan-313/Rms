<?php
$pageTitle = "Tables & Floors Management";
include_once __DIR__ . '/../../config/database.php';
include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';

// Ensure tables exist
if ($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS `restaurant_floors` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `floor_name` VARCHAR(100) NOT NULL UNIQUE,
        `floor_code` VARCHAR(50) NULL,
        `description` VARCHAR(255) NULL,
        `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $conn->query("CREATE TABLE IF NOT EXISTS `restaurant_tables` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `table_number` VARCHAR(50) NOT NULL UNIQUE,
        `floor_id` INT NOT NULL,
        `floor_name` VARCHAR(100) NOT NULL,
        `capacity` INT NOT NULL DEFAULT 4,
        `status` VARCHAR(30) DEFAULT 'Available',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Fetch floors
    $floors = [];
    $resF = $conn->query("SELECT f.*, COUNT(t.id) as table_count 
                          FROM `restaurant_floors` f 
                          LEFT JOIN `restaurant_tables` t ON f.id = t.floor_id 
                          GROUP BY f.id 
                          ORDER BY f.id ASC");
    if ($resF) {
        while ($row = $resF->fetch_assoc()) {
            $floors[] = $row;
        }
    }

    // Fetch tables
    $tables = [];
    $resT = $conn->query("SELECT t.*, f.floor_code 
                          FROM `restaurant_tables` t 
                          LEFT JOIN `restaurant_floors` f ON t.floor_id = f.id 
                          ORDER BY t.floor_id ASC, t.table_number ASC");
    if ($resT) {
        while ($row = $resT->fetch_assoc()) {
            $tables[] = $row;
        }
    }
}

// Compute statistics
$totalTables = count($tables);
$availableTables = 0;
$occupiedTables = 0;
$reservedTables = 0;
foreach ($tables as $t) {
    if ($t['status'] === 'Available') $availableTables++;
    elseif ($t['status'] === 'Occupied') $occupiedTables++;
    elseif ($t['status'] === 'Reserved') $reservedTables++;
}
?>

<main class="app-main">
  <!-- Content Header -->
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h3 class="mb-0 fw-bold">
            <i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i> Restaurant Tables & Floors
          </h3>
          <p class="text-muted small mb-0">Organize dining floors, seating capacities, and real-time table occupancy</p>
        </div>
        <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
          <div class="btn-group gap-2">
            <button type="button" class="btn btn-outline-primary fw-semibold" onclick="openAddFloorModal()">
              <i class="bi bi-building-add me-1"></i> Add Floor
            </button>
            <button type="button" class="btn btn-primary fw-semibold" onclick="openAddTableModal()">
              <i class="bi bi-plus-circle-fill me-1"></i> Add Table
            </button>
            <a href="/RMS/views/order/print_pdf.php?format=tables&autoprint=1" class="btn btn-outline-danger fw-semibold btn-direct-print">
              <i class="bi bi-printer me-1"></i> Print Floor Plan
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="app-content">
    <div class="container-fluid">
      <!-- KPI Stats Cards -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-primary border-4">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small fw-semibold text-uppercase">Total Floors</span>
                <h3 class="fw-bold mb-0 text-dark"><?= count($floors) ?></h3>
              </div>
              <div class="bg-primary-subtle text-primary p-3 rounded-circle fs-4">
                <i class="bi bi-layers-fill"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-success border-4">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small fw-semibold text-uppercase">Available Tables</span>
                <h3 class="fw-bold mb-0 text-success"><?= $availableTables ?> <small class="fs-6 text-muted fw-normal">/ <?= $totalTables ?></small></h3>
              </div>
              <div class="bg-success-subtle text-success p-3 rounded-circle fs-4">
                <i class="bi bi-check-circle-fill"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-danger border-4">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small fw-semibold text-uppercase">Occupied / Dining</span>
                <h3 class="fw-bold mb-0 text-danger"><?= $occupiedTables ?></h3>
              </div>
              <div class="bg-danger-subtle text-danger p-3 rounded-circle fs-4">
                <i class="bi bi-people-fill"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-warning border-4">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small fw-semibold text-uppercase">Reserved</span>
                <h3 class="fw-bold mb-0 text-warning"><?= $reservedTables ?></h3>
              </div>
              <div class="bg-warning-subtle text-warning p-3 rounded-circle fs-4">
                <i class="bi bi-bookmark-star-fill"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Floor Filter Tabs & Search -->
      <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <ul class="nav nav-pills flex-nowrap overflow-auto py-1" id="floorPills">
              <li class="nav-item">
                <button class="nav-link active fw-semibold text-nowrap rounded-pill px-3 py-2" onclick="filterFloor(0, this)">
                  <i class="bi bi-grid-fill me-1"></i> All Floors (<?= $totalTables ?>)
                </button>
              </li>
              <?php foreach ($floors as $f): ?>
                <li class="nav-item">
                  <button class="nav-link fw-semibold text-nowrap rounded-pill px-3 py-2" onclick="filterFloor(<?= $f['id'] ?>, this)">
                    <?= htmlspecialchars($f['floor_name']) ?> 
                    <span class="badge bg-secondary-subtle text-secondary ms-1"><?= $f['table_count'] ?></span>
                  </button>
                </li>
              <?php endforeach; ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
              <div class="input-group input-group-sm" style="max-width: 220px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" id="tableSearchInput" class="form-control bg-light border-start-0" placeholder="Search table..." oninput="searchTables(this.value)">
              </div>
              <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary active" id="viewModeGridBtn" onclick="toggleViewMode('grid')">
                  <i class="bi bi-grid-3x3-gap"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" id="viewModeListBtn" onclick="toggleViewMode('list')">
                  <i class="bi bi-list-ul"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- VISUAL GRID VIEW -->
      <div id="tableGridView">
        <div class="row g-3" id="tablesCardContainer">
          <?php if (empty($tables)): ?>
            <div class="col-12 text-center py-5 text-muted">
              <i class="bi bi-inbox fs-1 d-block mb-2"></i>
              <h5>No tables configured yet</h5>
              <p>Click "Add Table" above to create your restaurant dining layout.</p>
            </div>
          <?php else: ?>
            <?php foreach ($tables as $t): 
              $statusClass = 'success';
              $statusBg = 'bg-success-subtle text-success border-success';
              if ($t['status'] === 'Occupied') {
                  $statusClass = 'danger';
                  $statusBg = 'bg-danger-subtle text-danger border-danger';
              } elseif ($t['status'] === 'Reserved') {
                  $statusClass = 'warning';
                  $statusBg = 'bg-warning-subtle text-warning border-warning';
              } elseif ($t['status'] === 'Maintenance') {
                  $statusClass = 'secondary';
                  $statusBg = 'bg-secondary-subtle text-secondary border-secondary';
              }
            ?>
              <div class="col-12 col-sm-6 col-md-4 col-xl-3 table-card-col" 
                   data-floor-id="<?= $t['floor_id'] ?>" 
                   data-table-number="<?= strtolower(htmlspecialchars($t['table_number'])) ?>"
                   data-status="<?= $t['status'] ?>">
                <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover border-top border-4 border-<?= $statusClass ?>">
                  <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                      <div>
                        <span class="badge bg-light text-secondary border mb-1 small">
                          <i class="bi bi-layers me-1"></i><?= htmlspecialchars($t['floor_name']) ?>
                        </span>
                        <h4 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($t['table_number']) ?></h4>
                      </div>
                      <span class="badge border <?= $statusBg ?> px-2 py-1 rounded-pill fw-semibold">
                        <?= htmlspecialchars($t['status']) ?>
                      </span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between text-muted small my-3 py-2 border-top border-bottom">
                      <span><i class="bi bi-person-fill me-1"></i> Capacity:</span>
                      <strong class="text-dark"><?= $t['capacity'] ?> Guests</strong>
                    </div>

                    <div class="d-flex align-items-center justify-content-between gap-1 pt-1">
                      <!-- Status Toggle Dropdown -->
                      <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                          Status
                        </button>
                        <ul class="dropdown-menu shadow-sm border-0">
                          <li><a class="dropdown-item text-success" href="javascript:void(0)" onclick="quickUpdateStatus(<?= $t['id'] ?>, 'Available')"><i class="bi bi-check2-circle me-1"></i> Available</a></li>
                          <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="quickUpdateStatus(<?= $t['id'] ?>, 'Occupied')"><i class="bi bi-people me-1"></i> Occupied</a></li>
                          <li><a class="dropdown-item text-warning" href="javascript:void(0)" onclick="quickUpdateStatus(<?= $t['id'] ?>, 'Reserved')"><i class="bi bi-bookmark-star me-1"></i> Reserved</a></li>
                          <li><a class="dropdown-item text-secondary" href="javascript:void(0)" onclick="quickUpdateStatus(<?= $t['id'] ?>, 'Maintenance')"><i class="bi bi-wrench me-1"></i> Maintenance</a></li>
                        </ul>
                      </div>

                      <div class="d-flex gap-1">
                        <a href="/RMS/views/order/new.php?floor=<?= urlencode($t['floor_name']) ?>&table=<?= urlencode($t['table_number']) ?>" class="btn btn-sm btn-primary" title="Open POS for this Table">
                          <i class="bi bi-cart-plus-fill me-1"></i> Order
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-dark" onclick='openEditTableModal(<?= json_encode($t) ?>)' title="Edit Table">
                          <i class="bi bi-pencil-fill"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteTable(<?= $t['id'] ?>, '<?= addslashes($t['table_number']) ?>')" title="Delete Table">
                          <i class="bi bi-trash-fill"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- TABULAR LIST VIEW (Hidden by default) -->
      <div id="tableListView" style="display: none;">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="ps-4">Table Number</th>
                  <th>Floor</th>
                  <th>Capacity</th>
                  <th>Current Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($tables as $t): 
                  $statusBg = 'bg-success-subtle text-success';
                  if ($t['status'] === 'Occupied') $statusBg = 'bg-danger-subtle text-danger';
                  elseif ($t['status'] === 'Reserved') $statusBg = 'bg-warning-subtle text-warning';
                  elseif ($t['status'] === 'Maintenance') $statusBg = 'bg-secondary-subtle text-secondary';
                ?>
                  <tr class="table-row-item" data-floor-id="<?= $t['floor_id'] ?>" data-table-number="<?= strtolower(htmlspecialchars($t['table_number'])) ?>">
                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($t['table_number']) ?></td>
                    <td>
                      <span class="badge bg-light text-secondary border">
                        <i class="bi bi-layers me-1"></i><?= htmlspecialchars($t['floor_name']) ?>
                      </span>
                    </td>
                    <td><i class="bi bi-person-fill text-muted me-1"></i><?= $t['capacity'] ?> Persons</td>
                    <td>
                      <span class="badge <?= $statusBg ?> px-2 py-1 rounded-pill">
                        <?= htmlspecialchars($t['status']) ?>
                      </span>
                    </td>
                    <td>
                      <div class="d-flex gap-1">
                        <a href="/RMS/views/order/new.php?floor=<?= urlencode($t['floor_name']) ?>&table=<?= urlencode($t['table_number']) ?>" class="btn btn-sm btn-outline-primary">
                          <i class="bi bi-cart-plus-fill me-1"></i> POS Order
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick='openEditTableModal(<?= json_encode($t) ?>)'>
                          <i class="bi bi-pencil-fill"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteTable(<?= $t['id'] ?>, '<?= addslashes($t['table_number']) ?>')">
                          <i class="bi bi-trash-fill"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Manage Floors Table Section -->
      <div class="card border-0 shadow-sm rounded-4 mt-5">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
          <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-building me-2 text-primary"></i> Configured Restaurant Floors
          </h5>
          <button type="button" class="btn btn-sm btn-primary fw-semibold" onclick="openAddFloorModal()">
            <i class="bi bi-plus-lg me-1"></i> New Floor
          </button>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="ps-4">Floor Name</th>
                  <th>Floor Code</th>
                  <th>Description</th>
                  <th>Total Tables</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($floors)): ?>
                  <tr><td colspan="6" class="text-center py-4 text-muted">No floors configured.</td></tr>
                <?php else: ?>
                  <?php foreach ($floors as $f): ?>
                    <tr>
                      <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($f['floor_name']) ?></td>
                      <td><span class="badge bg-secondary"><?= htmlspecialchars($f['floor_code'] ?? '-') ?></span></td>
                      <td class="text-muted"><?= htmlspecialchars($f['description'] ?? 'No notes') ?></td>
                      <td><span class="badge bg-primary rounded-pill"><?= $f['table_count'] ?> Tables</span></td>
                      <td>
                        <span class="badge <?= $f['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>">
                          <?= htmlspecialchars($f['status']) ?>
                        </span>
                      </td>
                      <td>
                        <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick='openEditFloorModal(<?= json_encode($f) ?>)'>
                          <i class="bi bi-pencil-fill"></i> Edit
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteFloor(<?= $f['id'] ?>, '<?= addslashes($f['floor_name']) ?>')">
                          <i class="bi bi-trash-fill"></i> Delete
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- ADD / EDIT TABLE MODAL -->
<div class="modal fade" id="tableModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow rounded-4">
      <div class="modal-header bg-primary text-white py-3">
        <h5 class="modal-title fw-bold" id="tableModalTitle">
          <i class="bi bi-plus-circle me-2"></i> Add Restaurant Table
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="tableForm" onsubmit="saveTable(event)">
        <input type="hidden" id="modalTableId" value="0">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label for="modalTableNumber" class="form-label fw-semibold">Table Number / Label <span class="text-danger">*</span></label>
            <input type="text" id="modalTableNumber" class="form-control" placeholder="e.g. Table 01, VIP-01, Lawn-03" required>
          </div>
          <div class="mb-3">
            <label for="modalTableFloor" class="form-label fw-semibold">Dining Floor <span class="text-danger">*</span></label>
            <select id="modalTableFloor" class="form-select" required>
              <option value="">Select Floor</option>
              <?php foreach ($floors as $f): ?>
                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['floor_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label for="modalTableCapacity" class="form-label fw-semibold">Guest Capacity</label>
              <input type="number" id="modalTableCapacity" class="form-control" value="4" min="1" max="50" required>
            </div>
            <div class="col-6">
              <label for="modalTableStatus" class="form-label fw-semibold">Status</label>
              <select id="modalTableStatus" class="form-select">
                <option value="Available">Available</option>
                <option value="Occupied">Occupied</option>
                <option value="Reserved">Reserved</option>
                <option value="Maintenance">Maintenance</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top p-3">
          <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 fw-bold" id="saveTableBtn">
            <i class="bi bi-check2-circle me-1"></i> Save Table
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ADD / EDIT FLOOR MODAL -->
<div class="modal fade" id="floorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow rounded-4">
      <div class="modal-header bg-dark text-white py-3">
        <h5 class="modal-title fw-bold" id="floorModalTitle">
          <i class="bi bi-building-add me-2"></i> Add Restaurant Floor
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="floorForm" onsubmit="saveFloor(event)">
        <input type="hidden" id="modalFloorId" value="0">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label for="modalFloorName" class="form-label fw-semibold">Floor Name <span class="text-danger">*</span></label>
            <input type="text" id="modalFloorName" class="form-control" placeholder="e.g. Ground Floor, Rooftop Terrace" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label for="modalFloorCode" class="form-label fw-semibold">Short Code</label>
              <input type="text" id="modalFloorCode" class="form-control" placeholder="e.g. GF, RT">
            </div>
            <div class="col-6">
              <label for="modalFloorStatus" class="form-label fw-semibold">Status</label>
              <select id="modalFloorStatus" class="form-select">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label for="modalFloorDesc" class="form-label fw-semibold">Description / Notes</label>
            <textarea id="modalFloorDesc" class="form-control" rows="2" placeholder="e.g. Main dining hall with open grill"></textarea>
          </div>
        </div>
        <div class="modal-footer border-top p-3">
          <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 fw-bold" id="saveFloorBtn">
            <i class="bi bi-check2-circle me-1"></i> Save Floor
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let currentFloorFilter = 0;

function filterFloor(floorId, btn) {
  currentFloorFilter = floorId;
  const pills = document.querySelectorAll('#floorPills .nav-link');
  pills.forEach(p => p.classList.remove('active'));
  if (btn) btn.classList.add('active');

  const cardCols = document.querySelectorAll('.table-card-col');
  cardCols.forEach(col => {
    const fId = parseInt(col.getAttribute('data-floor-id'), 10);
    if (floorId === 0 || fId === floorId) {
      col.style.display = '';
    } else {
      col.style.display = 'none';
    }
  });

  const listRows = document.querySelectorAll('.table-row-item');
  listRows.forEach(row => {
    const fId = parseInt(row.getAttribute('data-floor-id'), 10);
    if (floorId === 0 || fId === floorId) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

function searchTables(query) {
  const q = query.toLowerCase().trim();
  const cardCols = document.querySelectorAll('.table-card-col');
  cardCols.forEach(col => {
    const tNum = col.getAttribute('data-table-number') || '';
    const fId = parseInt(col.getAttribute('data-floor-id'), 10);
    const matchesFloor = (currentFloorFilter === 0 || fId === currentFloorFilter);
    const matchesQuery = tNum.includes(q);
    col.style.display = (matchesFloor && matchesQuery) ? '' : 'none';
  });

  const listRows = document.querySelectorAll('.table-row-item');
  listRows.forEach(row => {
    const tNum = row.getAttribute('data-table-number') || '';
    const fId = parseInt(row.getAttribute('data-floor-id'), 10);
    const matchesFloor = (currentFloorFilter === 0 || fId === currentFloorFilter);
    const matchesQuery = tNum.includes(q);
    row.style.display = (matchesFloor && matchesQuery) ? '' : 'none';
  });
}

function toggleViewMode(mode) {
  const gridView = document.getElementById('tableGridView');
  const listView = document.getElementById('tableListView');
  const gridBtn = document.getElementById('viewModeGridBtn');
  const listBtn = document.getElementById('viewModeListBtn');

  if (mode === 'grid') {
    gridView.style.display = 'block';
    listView.style.display = 'none';
    gridBtn.classList.add('active');
    listBtn.classList.remove('active');
  } else {
    gridView.style.display = 'none';
    listView.style.display = 'block';
    listBtn.classList.add('active');
    gridBtn.classList.remove('active');
  }
}

// Quick status toggle
function quickUpdateStatus(tableId, status) {
  const fd = new FormData();
  fd.append('action', 'update_table_status');
  fd.append('id', tableId);
  fd.append('status', status);

  fetch('/RMS/app/controller/table.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        window.showCrudAlert(res.message || ('Table status updated to ' + status + '!'), 'info', 'reload');
      } else {
        window.rmsToast(res.message || 'Error updating status', 'danger');
      }
    })
    .catch(err => window.rmsToast('Network error: ' + err, 'danger'));
}

// Table Modal
function openAddTableModal() {
  document.getElementById('tableModalTitle').innerHTML = '<i class="bi bi-plus-circle me-2"></i> Add Restaurant Table';
  document.getElementById('modalTableId').value = '0';
  document.getElementById('modalTableNumber').value = '';
  document.getElementById('modalTableFloor').value = '';
  document.getElementById('modalTableCapacity').value = '4';
  document.getElementById('modalTableStatus').value = 'Available';
  new bootstrap.Modal(document.getElementById('tableModal')).show();
}

function openEditTableModal(t) {
  document.getElementById('tableModalTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Restaurant Table';
  document.getElementById('modalTableId').value = t.id;
  document.getElementById('modalTableNumber').value = t.table_number;
  document.getElementById('modalTableFloor').value = t.floor_id;
  document.getElementById('modalTableCapacity').value = t.capacity;
  document.getElementById('modalTableStatus').value = t.status;
  new bootstrap.Modal(document.getElementById('tableModal')).show();
}

function saveTable(e) {
  e.preventDefault();
  const btn = document.getElementById('saveTableBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

  const fd = new FormData();
  fd.append('action', 'save_table');
  fd.append('id', document.getElementById('modalTableId').value);
  fd.append('table_number', document.getElementById('modalTableNumber').value.trim());
  fd.append('floor_id', document.getElementById('modalTableFloor').value);
  fd.append('capacity', document.getElementById('modalTableCapacity').value);
  fd.append('status', document.getElementById('modalTableStatus').value);

  fetch('/RMS/app/controller/table.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Save Table';
      if (res.success) {
        const modalEl = document.getElementById('tableModal');
        const modalInst = bootstrap.Modal.getInstance(modalEl);
        if (modalInst) modalInst.hide();
        window.showCrudAlert(res.message || 'Table saved successfully!', 'success', 'reload');
      } else {
        window.rmsToast(res.message || 'Error saving table', 'danger');
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Save Table';
      window.rmsToast('Network error: ' + err, 'danger');
    });
}

function deleteTable(id, name) {
  if (!confirm(`Are you sure you want to delete "${name}"?`)) return;
  const fd = new FormData();
  fd.append('action', 'delete_table');
  fd.append('id', id);

  fetch('/RMS/app/controller/table.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        window.showCrudAlert(res.message || `Table "${name}" deleted successfully!`, 'success', 'reload');
      } else {
        window.rmsToast(res.message || 'Error deleting table', 'danger');
      }
    })
    .catch(err => window.rmsToast('Network error: ' + err, 'danger'));
}

// Floor Modal
function openAddFloorModal() {
  document.getElementById('floorModalTitle').innerHTML = '<i class="bi bi-building-add me-2"></i> Add Restaurant Floor';
  document.getElementById('modalFloorId').value = '0';
  document.getElementById('modalFloorName').value = '';
  document.getElementById('modalFloorCode').value = '';
  document.getElementById('modalFloorDesc').value = '';
  document.getElementById('modalFloorStatus').value = 'Active';
  new bootstrap.Modal(document.getElementById('floorModal')).show();
}

function openEditFloorModal(f) {
  document.getElementById('floorModalTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Restaurant Floor';
  document.getElementById('modalFloorId').value = f.id;
  document.getElementById('modalFloorName').value = f.floor_name;
  document.getElementById('modalFloorCode').value = f.floor_code || '';
  document.getElementById('modalFloorDesc').value = f.description || '';
  document.getElementById('modalFloorStatus').value = f.status || 'Active';
  new bootstrap.Modal(document.getElementById('floorModal')).show();
}

function saveFloor(e) {
  e.preventDefault();
  const btn = document.getElementById('saveFloorBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

  const fd = new FormData();
  fd.append('action', 'save_floor');
  fd.append('id', document.getElementById('modalFloorId').value);
  fd.append('floor_name', document.getElementById('modalFloorName').value.trim());
  fd.append('floor_code', document.getElementById('modalFloorCode').value.trim());
  fd.append('description', document.getElementById('modalFloorDesc').value.trim());
  fd.append('status', document.getElementById('modalFloorStatus').value);

  fetch('/RMS/app/controller/table.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Save Floor';
      if (res.success) {
        const modalEl = document.getElementById('floorModal');
        const modalInst = bootstrap.Modal.getInstance(modalEl);
        if (modalInst) modalInst.hide();
        window.showCrudAlert(res.message || 'Floor saved successfully!', 'success', 'reload');
      } else {
        window.rmsToast(res.message || 'Error saving floor', 'danger');
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Save Floor';
      window.rmsToast('Network error: ' + err, 'danger');
    });
}

function deleteFloor(id, name) {
  if (!confirm(`Warning: Deleting floor "${name}" will also delete all associated tables!\n\nAre you sure you want to proceed?`)) return;
  const fd = new FormData();
  fd.append('action', 'delete_floor');
  fd.append('id', id);

  fetch('/RMS/app/controller/table.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        window.showCrudAlert(res.message || `Floor "${name}" deleted successfully!`, 'success', 'reload');
      } else {
        window.rmsToast(res.message || 'Error deleting floor', 'danger');
      }
    })
    .catch(err => window.rmsToast('Network error: ' + err, 'danger'));
}
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
