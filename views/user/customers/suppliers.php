<?php
ob_start();
$pageTitle = "Supplier Management";
include_once __DIR__ . "/../../../config/database.php";

$msg = "";
$error = "";

// Handle Supplier Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_supplier') {
    $supplierName = trim($_POST['supplier_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $itemOfSupply = trim($_POST['item_of_supply'] ?? '');
    $date = !empty($_POST['date']) ? $_POST['date'] : date('Y-m-d');
    $dues = floatval($_POST['dues'] ?? 0);

    if (empty($supplierName)) {
        $error = "Supplier name is required.";
    } elseif ($conn) {
        // Insert into suppliers table
        $stmt = $conn->prepare("INSERT INTO `suppliers` (`supplier_name`, `phone`, `item_of_supply`, `date`, `dues`) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssd", $supplierName, $phone, $itemOfSupply, $date, $dues);
            if ($stmt->execute()) {
                $msg = "Supplier <strong>" . htmlspecialchars($supplierName) . "</strong> added successfully!";
                
                // Also mirror into supliers if it exists to preserve backward compatibility
                $supSafeName = mysqli_real_escape_string($conn, $supplierName);
                $supSafePhone = mysqli_real_escape_string($conn, $phone);
                $supSafeItem = mysqli_real_escape_string($conn, $itemOfSupply);
                $conn->query("INSERT INTO `supliers` (`name`, `phone`, `item`, `date`, `dues`) VALUES ('$supSafeName', '$supSafePhone', '$supSafeItem', '$date', $dues)");
            } else {
                $error = "Error adding supplier: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    } else {
        $error = "Database disconnected.";
    }
}

// Handle Supplier Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_supplier') {
    $id = intval($_POST['supplier_id'] ?? 0);
    $supplierName = trim($_POST['supplier_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $itemOfSupply = trim($_POST['item_of_supply'] ?? '');
    $date = !empty($_POST['date']) ? $_POST['date'] : date('Y-m-d');
    $dues = floatval($_POST['dues'] ?? 0);

    if ($id > 0 && !empty($supplierName) && $conn) {
        $stmt = $conn->prepare("UPDATE `suppliers` SET `supplier_name` = ?, `phone` = ?, `item_of_supply` = ?, `date` = ?, `dues` = ? WHERE `id` = ?");
        if ($stmt) {
            $stmt->bind_param("ssssdi", $supplierName, $phone, $itemOfSupply, $date, $dues, $id);
            if ($stmt->execute()) {
                $msg = "Supplier updated successfully!";
            } else {
                $error = "Error updating supplier: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Handle Supplier Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_supplier') {
    $id = intval($_POST['delete_id'] ?? 0);
    if ($id > 0 && $conn) {
        $stmt = $conn->prepare("DELETE FROM `suppliers` WHERE `id` = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $msg = "Supplier deleted successfully.";
            } else {
                $error = "Error deleting supplier: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch all suppliers
$suppliers = [];
$search = trim($_GET['search'] ?? '');
$totalDues = 0;
if ($conn) {
    if (!empty($search)) {
        $searchSafe = mysqli_real_escape_string($conn, $search);
        $res = $conn->query("SELECT * FROM `suppliers` WHERE `supplier_name` LIKE '%$searchSafe%' OR `phone` LIKE '%$searchSafe%' OR `item_of_supply` LIKE '%$searchSafe%' ORDER BY `id` DESC");
    } else {
        $res = $conn->query("SELECT * FROM `suppliers` ORDER BY `id` DESC");
    }
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $suppliers[] = $row;
            $totalDues += floatval($row['dues']);
        }
    }
    
    // If suppliers table is empty, check legacy supliers table
    if (empty($suppliers) && empty($search)) {
        $legacyRes = $conn->query("SELECT * FROM `supliers` ORDER BY `id` DESC");
        if ($legacyRes && $legacyRes->num_rows > 0) {
            while ($lr = $legacyRes->fetch_assoc()) {
                $suppliers[] = [
                    'id' => $lr['id'],
                    'supplier_name' => $lr['name'],
                    'phone' => $lr['phone'],
                    'item_of_supply' => $lr['item'],
                    'date' => $lr['date'],
                    'dues' => $lr['dues'],
                    'created_at' => $lr['date']
                ];
                $totalDues += floatval($lr['dues']);
            }
        }
    }
}

include_once __DIR__ . "/../../layouts/header.php";
include_once __DIR__ . "/../../layouts/sidebar.php";
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h3 class="mb-0 fw-bold">Supplier Management</h3>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="/RMS/public/index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Suppliers</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">

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

      <!-- Metrics -->
      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-4">
          <div class="card shadow-sm border-0 border-start border-primary border-4 rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small text-uppercase fw-semibold">Total Suppliers</span>
                <h3 class="fw-bold mb-0 mt-1"><?= count($suppliers) ?></h3>
              </div>
              <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle fs-3">
                <i class="bi bi-truck"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-sm-6 col-md-4">
          <div class="card shadow-sm border-0 border-start border-danger border-4 rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small text-uppercase fw-semibold">Total Outstanding Dues</span>
                <h3 class="fw-bold mb-0 mt-1 text-danger">Rs. <?= number_format($totalDues, 2) ?></h3>
              </div>
              <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle fs-3">
                <i class="bi bi-cash-coin"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-sm-6 col-md-4">
          <div class="card shadow-sm border-0 border-start border-success border-4 rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small text-uppercase fw-semibold">Supply Status</span>
                <h3 class="fw-bold mb-0 mt-1 text-success">Active</h3>
              </div>
              <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle fs-3">
                <i class="bi bi-shield-check"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Toolbar -->
      <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body p-3">
          <div class="row g-2 align-items-center justify-content-between">
            <div class="col-md-6 col-lg-5">
              <form method="GET" class="d-flex gap-2">
                <div class="input-group">
                  <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                  <input type="text" name="search" class="form-control border-start-0" placeholder="Search supplier or item..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <button type="submit" class="btn btn-outline-secondary">Search</button>
                <?php if (!empty($search)): ?>
                  <a href="suppliers.php" class="btn btn-outline-danger">Clear</a>
                <?php endif; ?>
              </form>
            </div>
            <div class="col-md-auto d-flex gap-2">
              <a href="/RMS/views/order/print_pdf.php?format=suppliers" target="_blank" class="btn btn-danger fw-semibold">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Export PDF
              </a>
              <button type="button" class="btn btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                <i class="bi bi-plus-lg me-1"></i> Add Supplier
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Suppliers Table -->
      <div class="card shadow-sm border-0 rounded-3">
        <div class="table-responsive">
          <table class="table table-hover table-striped align-middle mb-0">
            <thead class="table-dark">
              <tr>
                <th style="width: 60px;">#</th>
                <th>Supplier Name</th>
                <th>Phone</th>
                <th>Item of Supply</th>
                <th>Last Supply Date</th>
                <th>Outstanding Dues</th>
                <th class="text-center" style="width: 140px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($suppliers) > 0): ?>
                <?php $sn = 1; foreach ($suppliers as $sup): ?>
                  <tr>
                    <td class="fw-bold"><?= $sn++ ?></td>
                    <td class="fw-semibold text-primary">
                      <i class="bi bi-building me-1"></i>
                      <?= htmlspecialchars($sup['supplier_name']) ?>
                    </td>
                    <td><?= htmlspecialchars($sup['phone']) ?></td>
                    <td>
                      <span class="badge bg-light text-dark border">
                        <i class="bi bi-box-seam me-1 text-muted"></i>
                        <?= htmlspecialchars($sup['item_of_supply']) ?>
                      </span>
                    </td>
                    <td><?= htmlspecialchars($sup['date']) ?></td>
                    <td>
                      <?php if (floatval($sup['dues']) > 0): ?>
                        <span class="text-danger fw-bold">Rs. <?= number_format($sup['dues'], 2) ?></span>
                      <?php else: ?>
                        <span class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Paid ($0.00)</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <!-- Edit Button -->
                        <button type="button" class="btn btn-outline-warning" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editSupplierModal<?= $sup['id'] ?>"
                                title="Edit Supplier">
                          <i class="bi bi-pencil-square"></i>
                        </button>
                        <!-- Delete Button -->
                        <button type="button" class="btn btn-outline-danger" 
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteSupplierModal<?= $sup['id'] ?>"
                                title="Delete Supplier">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>

                      <!-- Edit Supplier Modal -->
                      <div class="modal fade" id="editSupplierModal<?= $sup['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content text-start border-0 shadow">
                            <div class="modal-header bg-warning text-dark py-3">
                              <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Supplier</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" action="suppliers.php">
                              <input type="hidden" name="action" value="edit_supplier">
                              <input type="hidden" name="supplier_id" value="<?= $sup['id'] ?>">
                              <div class="modal-body p-4">
                                <div class="mb-3">
                                  <label class="form-label fw-semibold">Supplier Name <span class="text-danger">*</span></label>
                                  <input type="text" name="supplier_name" class="form-control" value="<?= htmlspecialchars($sup['supplier_name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                                  <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($sup['phone']) ?>" required>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label fw-semibold">Item of Supply <span class="text-danger">*</span></label>
                                  <input type="text" name="item_of_supply" class="form-control" value="<?= htmlspecialchars($sup['item_of_supply']) ?>" required>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label fw-semibold">Date</label>
                                  <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($sup['date']) ?>" required>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label fw-semibold">Outstanding Dues ($)</label>
                                  <input type="number" step="0.01" min="0" name="dues" class="form-control" value="<?= htmlspecialchars($sup['dues']) ?>" required>
                                </div>
                              </div>
                              <div class="modal-footer border-top p-3">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-warning fw-semibold px-4">Update Supplier</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>

                      <!-- Delete Supplier Modal -->
                      <div class="modal fade" id="deleteSupplierModal<?= $sup['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content text-start border-0 shadow">
                            <form method="POST" action="suppliers.php">
                              <input type="hidden" name="action" value="delete_supplier">
                              <input type="hidden" name="delete_id" value="<?= $sup['id'] ?>">
                              <div class="modal-header bg-danger text-white py-3">
                                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-octagon me-2"></i>Confirm Delete</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body p-4">
                                <p class="mb-0">Are you sure you want to delete supplier <strong>"<?= htmlspecialchars($sup['supplier_name']) ?>"</strong>? This action cannot be undone.</p>
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
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-truck display-4 d-block mb-2 text-secondary"></i>
                    No suppliers found in database. Click "Add Supplier" above to record supply details.
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

<!-- Modal: Add Supplier -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header bg-primary text-white py-3">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-truck me-2"></i>Add New Supplier
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form method="POST" action="suppliers.php">
        <input type="hidden" name="action" value="add_supplier">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-semibold">Supplier Name <span class="text-danger">*</span></label>
            <input type="text" name="supplier_name" class="form-control" placeholder="e.g. Metro Food Supplies" required autofocus>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
            <input type="text" name="phone" class="form-control" placeholder="e.g. +1 555-0188 or 03001234567" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Item of Supply <span class="text-danger">*</span></label>
            <input type="text" name="item_of_supply" class="form-control" placeholder="e.g. Fresh Chicken, Vegetables, Dairy" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Date of Supply</label>
            <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Outstanding Dues ($)</label>
            <input type="number" step="0.01" name="dues" class="form-control" min="0" value="0.00" required>
          </div>
        </div>

        <div class="modal-footer border-top p-3">
          <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold px-4">
            <i class="bi bi-check-circle me-1"></i> Save Supplier
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php 
include_once __DIR__ . "/../../layouts/footer.php";
ob_end_flush();
?>
