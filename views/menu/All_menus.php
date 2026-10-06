<?php
$pageTitle = "All Menus";
include_once __DIR__ . '/../../config/database.php';
include_once __DIR__ . '/../helpers/function.php';
include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;
$total_records = 0;
$total_pages = 1;
$result = null;

if ($conn) {
    $search_condition = "";
    if (!empty($search)) {
        $search_safe = mysqli_real_escape_string($conn, $search);
        $search_condition = "WHERE item_name LIKE '%$search_safe%' OR code LIKE '%$search_safe%' OR catagory LIKE '%$search_safe%'";
    }

    $count_res = @$conn->query("SELECT COUNT(*) AS total FROM menue $search_condition");
    if ($count_res) {
        $count_row = $count_res->fetch_assoc();
        $total_records = intval($count_row['total'] ?? 0);
        $total_pages = max(1, ceil($total_records / $limit));
    }

    $result = @$conn->query("SELECT * FROM menue $search_condition ORDER BY sn DESC LIMIT $start, $limit");
}
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6"><h3 class="mb-0 fw-bold">Menu Dish Catalog</h3></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="/RMS/public/index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">All Menus</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">

      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
          <i class="bi bi-check-circle-fill me-2"></i> New menu item added and synced with POS!
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
          <i class="bi bi-check-circle-fill me-2"></i> Menu item updated successfully!
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
          <i class="bi bi-trash-fill me-2"></i> Menu item deleted successfully!
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <!-- Add Menu + Search + PDF Export -->
      <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex gap-2 flex-wrap">
              <a href="add_menus.php" class="btn btn-primary fw-semibold btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Add Menu Dish
              </a>
              <a href="/RMS/views/order/print_pdf.php?format=menu" target="_blank" class="btn btn-danger fw-semibold btn-sm">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Export Menu PDF
              </a>
            </div>

            <form method="GET" class="d-flex gap-2">
              <input type="text" name="search" class="form-control form-control-sm" placeholder="Search dish or code…" value="<?= htmlspecialchars($search) ?>">
              <button class="btn btn-outline-primary btn-sm px-3" type="submit">Search</button>
              <?php if (!empty($search)): ?>
                <a href="All_menus.php" class="btn btn-outline-secondary btn-sm">Clear</a>
              <?php endif; ?>
            </form>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="card shadow-sm border-0 rounded-3">
        <div class="table-responsive">
          <table class="table table-hover table-bordered align-middle mb-0">
            <thead class="table-dark text-center">
              <tr>
                <th style="width: 50px;">SN</th>
                <th style="width: 90px;">Code</th>
                <th style="width: 80px;">Photo</th>
                <th>Item Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>GST%</th>
                <th>Total Price</th>
                <th style="width: 140px;">Action</th>
              </tr>
            </thead>
            <tbody class="text-center">
              <?php
              if ($result && mysqli_num_rows($result) > 0) {
                  $sn = $start + 1;
                  while ($row = mysqli_fetch_assoc($result)) { 
                    $imgUrl = rms_menu_image($row['image']);
                  ?>
                    <tr>
                      <td class="fw-bold"><?= $sn ?></td>
                      <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['code']) ?></span></td>
                      <td>
                        <img src="<?= htmlspecialchars($imgUrl); ?>" class="menu-thumb shadow-sm" alt="<?= htmlspecialchars($row['item_name']) ?>" onerror="this.src='/RMS/public/assets/images/food/pos_cheeseburger.jpg';">
                      </td>
                      <td class="fw-semibold text-start text-primary"><?= htmlspecialchars($row['item_name']) ?></td>
                      <td><span class="rms-badge rms-badge-primary"><?= htmlspecialchars($row['catagory']) ?></span></td>
                      <td>Rs. <?= number_format(floatval($row['price']), 2) ?></td>
                      <td><?= floatval($row['gst']) ?>%</td>
                      <td class="fw-bold text-success">Rs. <?= number_format(floatval($row['total']), 2) ?></td>
                      <td class="text-center">
                        <div class="btn-group btn-group-sm">
                          <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['sn'] ?>" title="Edit Dish">
                            <i class="bi bi-pencil-square"></i>
                          </button>
                          <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['sn'] ?>" title="Delete Dish">
                            <i class="bi bi-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>

                    <!-- Delete Modal -->
                    <div class="modal fade" id="deleteModal<?= $row['sn'] ?>" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content text-start">
                          <form action="delete.php" method="POST">
                            <div class="modal-header bg-danger text-white py-3">
                              <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-octagon me-2"></i>Confirm Delete</h5>
                              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                              <p class="mb-0">Are you sure you want to delete <strong>"<?= htmlspecialchars($row['item_name']) ?>"</strong>? This dish will also be removed from the POS screen.</p>
                              <input type="hidden" name="delete_id" value="<?= $row['sn'] ?>">
                            </div>
                            <div class="modal-footer border-top p-3">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" name="delete_btn" class="btn btn-danger fw-semibold px-4">Yes, Delete</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal<?= $row['sn'] ?>" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content text-start border-0 shadow">
                          <form action="edit.php" method="POST" enctype="multipart/form-data">
                            <div class="modal-header bg-warning text-dark py-3">
                              <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Menu Item</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                              <input type="hidden" name="sn" value="<?= $row['sn'] ?>">
                              <div class="row g-3">
                                <div class="col-md-6">
                                  <label class="form-label fw-semibold">Item Name <span class="text-danger">*</span></label>
                                  <input type="text" name="item_name" class="form-control" value="<?= htmlspecialchars($row['item_name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label fw-semibold">Item Code <span class="text-danger">*</span></label>
                                  <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($row['code']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                                  <select name="category" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <?php
                                    $sqlCat = "SELECT * FROM category ORDER BY category_name ASC";
                                    $resCat = mysqli_query($conn, $sqlCat);
                                    if ($resCat && mysqli_num_rows($resCat) > 0) {
                                        while ($cat = mysqli_fetch_assoc($resCat)) {
                                            $selected = ($cat['category_name'] == $row['catagory']) ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($cat['category_name']) . '" ' . $selected . '>' . htmlspecialchars($cat['category_name']) . '</option>';
                                        }
                                    }
                                    ?>
                                  </select>
                                </div>
                                <div class="col-md-3">
                                  <label class="form-label fw-semibold">Price (Rs.) <span class="text-danger">*</span></label>
                                  <input type="number" step="0.01" min="0.01" name="price" class="form-control" value="<?= htmlspecialchars($row['price']) ?>" required>
                                </div>
                                <div class="col-md-3">
                                  <label class="form-label fw-semibold">GST / Tax (%)</label>
                                  <input type="number" step="0.01" min="0" name="gst" class="form-control" value="<?= htmlspecialchars($row['gst']) ?>">
                                </div>
                                <div class="col-12">
                                  <label class="form-label fw-semibold">Change Dish Image</label>
                                  <div class="d-flex align-items-center gap-3">
                                    <img src="<?= htmlspecialchars($imgUrl); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #ccc;">
                                    <input type="file" name="product-image" class="form-control" accept="image/*">
                                  </div>
                                  <small class="text-muted">Leave empty to keep existing dish photo.</small>
                                </div>
                              </div>
                            </div>
                            <div class="modal-footer border-top p-3">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" name="update_btn" class="btn btn-warning fw-semibold px-4">Update Dish</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  <?php $sn++; } 
              } else { ?>
                <tr>
                  <td colspan="9" class="text-center py-5 text-muted">
                    <i class="bi bi-journal-x display-4 d-block mb-2 text-secondary"></i>
                    No menu items found. <a href="add_menus.php" class="btn btn-sm btn-primary ms-2">Add New Menu Dish</a>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
          <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <span class="text-muted small">Showing <?= $start + 1 ?> to <?= min($start + $limit, $total_records) ?> of <?= $total_records ?> items</span>
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a>
              </li>
              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
              </li>
            </ul>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</main>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>