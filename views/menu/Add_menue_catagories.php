<?php
ob_start();
include_once(__DIR__ . '/../../config/database.php');

$error = "";
$success = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category = trim($_POST['category_name'] ?? '');
  
    if (!empty($category)) {
        if ($conn) {
            $catSafe = mysqli_real_escape_string($conn, $category);
            // Check for duplicate
            $dupCheck = mysqli_query($conn, "SELECT id FROM category WHERE LOWER(category_name) = LOWER('$catSafe')");
            if ($dupCheck && mysqli_num_rows($dupCheck) > 0) {
                $error = "Category '$category' already exists.";
            } else {
                $sql = "INSERT INTO category (category_name) VALUES ('$catSafe')";
                if (mysqli_query($conn, $sql)) {
                    header("Location: Menue_catagories.php?msg=added");
                    exit();
                } else {
                    $error = "Database Error: " . mysqli_error($conn);
                }
            }
        } else {
            $error = "Database disconnected. Please check MySQL server.";
        }
    } else {
        $error = "Please enter a valid category name.";
    }
}

$pageTitle = "Add Category";
include_once(__DIR__ . '/../layouts/header.php');
include_once(__DIR__ . '/../layouts/sidebar.php');
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Add Category</h3></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="/RMS/public/index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="Menue_catagories.php">Categories</a></li>
            <li class="breadcrumb-item active" aria-current="page">Add Category</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row justify-content-center">
        <div class="col-md-6">
          <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
              <h5 class="card-title mb-0 fw-bold text-primary">New Category Details</h5>
            </div>
            <div class="card-body p-4">
              <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
              <?php endif; ?>

              <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <div class="mb-4">
                  <label for="category_name" class="form-label fw-semibold">Category Name</label>
                  <input type="text" id="category_name" name="category_name" class="form-control form-control-lg" placeholder="e.g. Burgers, Beverages, Desserts" required autofocus>
                  <div class="form-text">Enter a unique category name for food & beverage items.</div>
                </div>

                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-primary px-4 fw-semibold">
                    <i class="bi bi-check2-circle me-1"></i> Save Category
                  </button>
                  <a href="Menue_catagories.php" class="btn btn-outline-secondary px-3">Cancel</a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php
include_once(__DIR__ . '/../layouts/footer.php');
ob_end_flush();
?>
