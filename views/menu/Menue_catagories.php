<?php
$pageTitle = "Menu Categories";
include_once(__DIR__ . '/../../config/database.php');
include_once(__DIR__ . '/../layouts/header.php');
include_once(__DIR__ . '/../layouts/sidebar.php');

$msg = "";
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') {
        $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm'><i class='bi bi-check-circle-fill me-2'></i>Category added successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } elseif ($_GET['msg'] === 'deleted') {
        $msg = "<div class='alert alert-info alert-dismissible fade show shadow-sm'><i class='bi bi-trash-fill me-2'></i>Category deleted successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } elseif ($_GET['msg'] === 'updated') {
        $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm'><i class='bi bi-check-circle-fill me-2'></i>Category updated successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['category_name'])) {
    $category = mysqli_real_escape_string($conn, trim($_POST['category_name']));
    if (!empty($category)) {
        $sql = "INSERT INTO category (category_name) VALUES ('$category')";
        if ($conn && $conn->query($sql) === true) {
            $msg = "<div class='alert alert-success alert-dismissible fade show'>Category added successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
            $msg = "<div class='alert alert-danger'>Error adding category: " . ($conn ? $conn->error : "DB error") . "</div>";
        }
    }
}

$result = null;
if ($conn) {
    $record = "SELECT * FROM category ORDER BY id DESC";
    $result = mysqli_query($conn, $record);
}
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Menu Categories</h3></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="/RMS/public/index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="All_menus.php">Menus</a></li>
            <li class="breadcrumb-item active" aria-current="page">Categories</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <?= $msg ?>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 text-muted">All Available Food Categories</h5>
        <a href="Add_menue_catagories.php" class="btn btn-primary btn-sm">
          <i class="bi bi-plus-lg me-1"></i> Add Category
        </a>
      </div>

      <div class="card shadow-sm border-0 rounded-3">
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 80px;">#</th>
                <th>Category Name</th>
                <th style="width: 160px;" class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              if ($result && mysqli_num_rows($result) > 0) {
                  $sn = 1;
                  while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                      <td class="fw-bold"><?= $sn ?></td>
                      <td>
                        <span class="rms-badge rms-badge-primary">
                          <i class="bi bi-tag-fill me-1"></i> <?= htmlspecialchars($row['category_name']) ?>
                        </span>
                      </td>
                      <td class="text-center">
                        <div class="btn-group btn-group-sm">
                          <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-outline-warning">
                            <i class="bi bi-pencil-square"></i> Edit
                          </a>
                          <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>">
                            <i class="bi bi-trash"></i> Delete
                          </button>
                        </div>

                        <!-- Delete Modal -->
                        <div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1">
                          <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content text-start">
                              <form action="delete_2.php" method="POST">
                                <div class="modal-header bg-danger text-white">
                                  <h5 class="modal-title">Confirm Delete</h5>
                                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                  <p>Are you sure you want to delete category <strong>"<?= htmlspecialchars($row['category_name']) ?>"</strong>?</p>
                                  <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                </div>
                                <div class="modal-footer">
                                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                  <button type="submit" name="delete_btn" class="btn btn-danger">Yes, Delete</button>
                                </div>
                              </form>
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                    <?php
                    $sn++;
                  }
              } else {
                  echo "<tr><td colspan='3' class='text-center py-4 text-muted'>No categories found. Click 'Add Category' above to create one.</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include_once(__DIR__ . '/../layouts/footer.php'); ?>