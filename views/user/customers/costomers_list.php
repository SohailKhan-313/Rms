<?php
ob_start();
$pageTitle = "Customer Management";
include_once __DIR__ . "/../../../config/database.php";

$msg = "";
$error = "";

// Handle Customer Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_customer') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : '2000-01-01';
    $discount = trim($_POST['discount'] ?? '0');
    $discount = rtrim($discount, '%');
    $addedBy = trim($_POST['added_by'] ?? 'Admin');

    if (empty($name)) {
        $error = "Customer name is required.";
    } elseif ($conn) {
        $stmt = $conn->prepare("INSERT INTO `customers` (`name`, `phone`, `email`, `address`, `dob`, `discount`, `added_by`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssssss", $name, $phone, $email, $address, $dob, $discount, $addedBy);
            if ($stmt->execute()) {
                $msg = "Customer <strong>" . htmlspecialchars($name) . "</strong> added successfully!";
            } else {
                $error = "Error adding customer: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Database prepare error: " . $conn->error;
        }
    } else {
        $error = "Database connection not available.";
    }
}

// Handle Customer Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_customer') {
    $id = intval($_POST['customer_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
    $discount = trim($_POST['discount'] ?? '0');
    $discount = rtrim($discount, '%');

    if ($id > 0 && !empty($name) && $conn) {
        $stmt = $conn->prepare("UPDATE `customers` SET `name` = ?, `phone` = ?, `email` = ?, `address` = ?, `dob` = ?, `discount` = ? WHERE `id` = ?");
        if ($stmt) {
            $stmt->bind_param("ssssssi", $name, $phone, $email, $address, $dob, $discount, $id);
            if ($stmt->execute()) {
                $msg = "Customer updated successfully!";
            } else {
                $error = "Error updating customer: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Handle Customer Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_customer') {
    $id = intval($_POST['delete_id'] ?? 0);
    if ($id > 0 && $conn) {
        $stmt = $conn->prepare("DELETE FROM `customers` WHERE `id` = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $msg = "Customer deleted successfully.";
            } else {
                $error = "Error deleting customer: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch all customers from DB
$customers = [];
$search = trim($_GET['search'] ?? '');
if ($conn) {
    if (!empty($search)) {
        $searchSafe = mysqli_real_escape_string($conn, $search);
        $res = $conn->query("SELECT * FROM `customers` WHERE `name` LIKE '%$searchSafe%' OR `phone` LIKE '%$searchSafe%' OR `email` LIKE '%$searchSafe%' ORDER BY `id` DESC");
    } else {
        $res = $conn->query("SELECT * FROM `customers` ORDER BY `id` DESC");
    }
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $customers[] = $row;
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
          <h3 class="mb-0 fw-bold">Customer Management</h3>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="/RMS/public/index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Customers</li>
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

      <!-- Quick Metrics -->
      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-4">
          <div class="card shadow-sm border-0 border-start border-primary border-4 rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small text-uppercase fw-semibold">Total Registered Customers</span>
                <h3 class="fw-bold mb-0 mt-1"><?= count($customers) ?></h3>
              </div>
              <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle fs-3">
                <i class="bi bi-people-fill"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-sm-6 col-md-4">
          <div class="card shadow-sm border-0 border-start border-success border-4 rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small text-uppercase fw-semibold">Active VIP / Discounts</span>
                <h3 class="fw-bold mb-0 mt-1">
                  <?= count(array_filter($customers, function($c){ return floatval($c['discount']) > 0; })) ?>
                </h3>
              </div>
              <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle fs-3">
                <i class="bi bi-percent"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-sm-6 col-md-4">
          <div class="card shadow-sm border-0 border-start border-warning border-4 rounded-3 p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small text-uppercase fw-semibold">POS Integration</span>
                <h3 class="fw-bold mb-0 mt-1 text-warning">Synced</h3>
              </div>
              <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle fs-3">
                <i class="bi bi-check-all"></i>
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
                  <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, phone or email..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <button type="submit" class="btn btn-outline-secondary">Search</button>
                <?php if (!empty($search)): ?>
                  <a href="costomers_list.php" class="btn btn-outline-danger">Clear</a>
                <?php endif; ?>
              </form>
            </div>
            <div class="col-md-auto d-flex gap-2">
              <a href="/RMS/views/order/print_pdf.php?format=customers" target="_blank" class="btn btn-danger fw-semibold">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Export PDF
              </a>
              <button type="button" class="btn btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                <i class="bi bi-person-plus-fill me-1"></i> Add New Customer
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Customers Table -->
      <div class="card shadow-sm border-0 rounded-3">
        <div class="table-responsive">
          <table class="table table-hover table-striped align-middle mb-0">
            <thead class="table-dark">
              <tr>
                <th style="width: 60px;">#</th>
                <th>Customer Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Address</th>
                <th>Date of Birth</th>
                <th>Discount</th>
                <th>Added By</th>
                <th class="text-center" style="width: 140px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($customers) > 0): ?>
                <?php $sn = 1; foreach ($customers as $cust): ?>
                  <tr>
                    <td class="fw-bold"><?= $sn++ ?></td>
                    <td class="fw-semibold text-primary">
                      <i class="bi bi-person-circle me-1"></i>
                      <?= htmlspecialchars($cust['name']) ?>
                    </td>
                    <td><?= htmlspecialchars($cust['phone'] ?: 'N/A') ?></td>
                    <td><?= htmlspecialchars($cust['email'] ?: 'N/A') ?></td>
                    <td><small class="text-muted"><?= htmlspecialchars($cust['address'] ?: 'N/A') ?></small></td>
                    <td><?= (!empty($cust['dob']) && $cust['dob'] !== '0000-00-00') ? htmlspecialchars($cust['dob']) : 'N/A' ?></td>
                    <td>
                      <?php if (floatval($cust['discount']) > 0): ?>
                        <span class="badge bg-success-subtle text-success border border-success fw-bold">
                          <?= htmlspecialchars($cust['discount']) ?>%
                        </span>
                      <?php else: ?>
                        <span class="text-muted small">0%</span>
                      <?php endif; ?>
                    </td>
                    <td><small class="badge bg-secondary"><?= htmlspecialchars($cust['added_by'] ?? 'Admin') ?></small></td>
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <!-- Edit Button -->
                        <button type="button" class="btn btn-outline-warning" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editCustomerModal<?= $cust['id'] ?>"
                                title="Edit Customer">
                          <i class="bi bi-pencil-square"></i>
                        </button>
                        <!-- Delete Button -->
                        <button type="button" class="btn btn-outline-danger" 
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteCustomerModal<?= $cust['id'] ?>"
                                title="Delete Customer">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>

                      <!-- Edit Customer Modal -->
                      <div class="modal fade" id="editCustomerModal<?= $cust['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                          <div class="modal-content text-start border-0 shadow">
                            <div class="modal-header bg-warning text-dark py-3">
                              <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Customer Details</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" action="costomers_list.php">
                              <input type="hidden" name="action" value="edit_customer">
                              <input type="hidden" name="customer_id" value="<?= $cust['id'] ?>">
                              <div class="modal-body p-4">
                                <div class="row g-3">
                                  <div class="col-md-6">
                                    <label class="form-label fw-semibold">Customer Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($cust['name']) ?>" required>
                                  </div>
                                  <div class="col-md-6">
                                    <label class="form-label fw-semibold">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($cust['phone'] ?? '') ?>">
                                  </div>
                                  <div class="col-md-6">
                                    <label class="form-label fw-semibold">Email Address</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($cust['email'] ?? '') ?>">
                                  </div>
                                  <div class="col-md-6">
                                    <label class="form-label fw-semibold">Date of Birth</label>
                                    <input type="date" name="dob" class="form-control" value="<?= (!empty($cust['dob']) && $cust['dob'] !== '0000-00-00') ? htmlspecialchars($cust['dob']) : '' ?>">
                                  </div>
                                  <div class="col-md-8">
                                    <label class="form-label fw-semibold">Address</label>
                                    <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($cust['address'] ?? '') ?>">
                                  </div>
                                  <div class="col-md-4">
                                    <label class="form-label fw-semibold">Discount (%)</label>
                                    <input type="number" step="0.5" min="0" max="100" name="discount" class="form-control" value="<?= htmlspecialchars($cust['discount'] ?? '0') ?>">
                                  </div>
                                </div>
                              </div>
                              <div class="modal-footer border-top p-3">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-warning fw-semibold px-4">Update Customer</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>

                      <!-- Delete Customer Modal -->
                      <div class="modal fade" id="deleteCustomerModal<?= $cust['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content text-start border-0 shadow">
                            <form method="POST" action="costomers_list.php">
                              <input type="hidden" name="action" value="delete_customer">
                              <input type="hidden" name="delete_id" value="<?= $cust['id'] ?>">
                              <div class="modal-header bg-danger text-white py-3">
                                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-octagon me-2"></i>Confirm Delete</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body p-4">
                                <p class="mb-0">Are you sure you want to delete customer <strong>"<?= htmlspecialchars($cust['name']) ?>"</strong>? This action cannot be undone.</p>
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
                  <td colspan="9" class="text-center py-5 text-muted">
                    <i class="bi bi-people display-4 d-block mb-2 text-secondary"></i>
                    No customers found in database. Click "Add New Customer" above to create one.
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

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header bg-primary text-white py-3">
        <h5 class="modal-title fw-bold" id="addCustomerModalLabel">
          <i class="bi bi-person-plus-fill me-2"></i>Add New Customer
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="costomers_list.php">
        <input type="hidden" name="action" value="add_customer">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="cName" class="form-label fw-semibold">Customer Full Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="cName" name="name" placeholder="e.g. Michael Smith" required autofocus>
            </div>
            <div class="col-md-6">
              <label for="cPhone" class="form-label fw-semibold">Phone Number</label>
              <input type="tel" class="form-control" id="cPhone" name="phone" placeholder="e.g. +1 555-0199 or 03001234567">
            </div>
            <div class="col-md-6">
              <label for="cEmail" class="form-label fw-semibold">Email Address</label>
              <input type="email" class="form-control" id="cEmail" name="email" placeholder="e.g. michael@example.com">
            </div>
            <div class="col-md-6">
              <label for="cDob" class="form-label fw-semibold">Date of Birth</label>
              <input type="date" class="form-control" id="cDob" name="dob">
            </div>
            <div class="col-md-8">
              <label for="cAddress" class="form-label fw-semibold">Street Address / City</label>
              <input type="text" class="form-control" id="cAddress" name="address" placeholder="e.g. 123 Main Street, Suite 4B">
            </div>
            <div class="col-md-4">
              <label for="cDiscount" class="form-label fw-semibold">VIP Discount (%)</label>
              <input type="number" step="0.5" min="0" max="100" class="form-control" id="cDiscount" name="discount" value="0" placeholder="0">
            </div>
            <div class="col-md-6">
              <label for="cAddedBy" class="form-label fw-semibold">Added By Staff</label>
              <input type="text" class="form-control" id="cAddedBy" name="added_by" value="Admin" required>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top p-3">
          <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold px-4">
            <i class="bi bi-check-circle me-1"></i> Save Customer
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