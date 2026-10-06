<?php
ob_start();
include_once __DIR__ . '/../../config/database.php';
include_once __DIR__ . '/../helpers/function.php';

$errorMsg = "";
$successMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['Code'] ?? '');
    $itemname = trim($_POST['itemname'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $gst = floatval($_POST['gst'] ?? 0);
    $total = floatval($_POST['total'] ?? 0);

    if ($total <= 0 && $price > 0) {
        $total = round($price * (1 + ($gst / 100)), 2);
    }

    if (empty($itemname)) {
        $errorMsg = "Item name is required.";
    } elseif ($price <= 0) {
        $errorMsg = "Please enter a valid price greater than 0.";
    } elseif ($conn) {
        // Image upload handling
        $imageName = "";
        if (!empty($_FILES['product-image']['name']) && $_FILES['product-image']['error'] === UPLOAD_ERR_OK) {
            $fileInfo = pathinfo($_FILES['product-image']['name']);
            $ext = strtolower($fileInfo['extension'] ?? '');
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (in_array($ext, $allowed)) {
                $imageName = 'item_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $uploadDir = __DIR__ . '/../../public/assets/uploads';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0777, true);
                }
                $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $imageName;
                if (!move_uploaded_file($_FILES['product-image']['tmp_name'], $targetPath)) {
                    $errorMsg = "Failed to move uploaded image to storage.";
                }
            } else {
                $errorMsg = "Invalid image file format. Only JPG, PNG, WEBP, and GIF are allowed.";
            }
        } else {
            // Assign high-resolution smart default based on category
            $catLower = strtolower($category);
            if (strpos($catLower, 'burger') !== false) $imageName = 'pos_cheeseburger.jpg';
            elseif (strpos($catLower, 'pizza') !== false) $imageName = 'pos_pizza.jpg';
            elseif (strpos($catLower, 'beverage') !== false || strpos($catLower, 'drink') !== false || strpos($catLower, 'tea') !== false || strpos($catLower, 'coffee') !== false) $imageName = 'pos_iced_tea.jpg';
            elseif (strpos($catLower, 'dessert') !== false || strpos($catLower, 'cake') !== false || strpos($catLower, 'bakery') !== false) $imageName = 'pos_lava_cake.jpg';
            elseif (strpos($catLower, 'side') !== false || strpos($catLower, 'fries') !== false || strpos($catLower, 'wing') !== false) $imageName = 'pos_fries.jpg';
            else $imageName = 'pos_cheeseburger.jpg';
        }

        if (empty($errorMsg)) {
            $stmt = $conn->prepare("INSERT INTO `menue` (`code`, `item_name`, `catagory`, `price`, `gst`, `total`, `image`) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssddds", $code, $itemname, $category, $price, $gst, $total, $imageName);
                if ($stmt->execute()) {
                    header("Location: All_menus.php?msg=added");
                    exit();
                } else {
                    $errorMsg = "Database error adding menu item: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $errorMsg = "Database prepare error: " . $conn->error;
            }
        }
    } else {
        $errorMsg = "Database disconnected.";
    }
}

$pageTitle = "Add Menu";
include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0 fw-bold">Add Menu Item</h3></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="/RMS/public/index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="All_menus.php">Menus</a></li>
            <li class="breadcrumb-item active" aria-current="page">Add Menu</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
              <h5 class="card-title mb-0 fw-bold text-primary">
                <i class="bi bi-plus-circle me-2"></i>New Dish Information
              </h5>
            </div>
            <div class="card-body p-4">
              <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($errorMsg) ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="code" class="form-label fw-semibold">Item Code <span class="text-danger">*</span></label>
                    <input type="text" id="code" name="Code" class="form-control" placeholder="e.g. FD-001 or 101" required>
                  </div>

                  <div class="col-md-6">
                    <label for="itemName" class="form-label fw-semibold">Item Name <span class="text-danger">*</span></label>
                    <input type="text" id="itemName" name="itemname" class="form-control" placeholder="e.g. Classic Cheese Burger" required>
                  </div>

                  <div class="col-md-6">
                    <label for="categorySelect" class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                    <select id="categorySelect" name="category" class="form-select" required>
                      <option value="">Select Category</option>
                      <?php
                      if ($conn) {
                          $sqlCat = "SELECT * FROM category ORDER BY category_name ASC";
                          $resCat = mysqli_query($conn, $sqlCat);
                          if ($resCat && mysqli_num_rows($resCat) > 0) {
                              while ($rowCat = mysqli_fetch_assoc($resCat)) {
                                  echo '<option value="' . htmlspecialchars($rowCat['category_name']) . '">' . htmlspecialchars($rowCat['category_name']) . '</option>';
                              }
                          }
                      }
                      ?>
                    </select>
                  </div>

                  <div class="col-md-6">
                    <label for="price" class="form-label fw-semibold">Price (Rs.) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" id="price" name="price" class="form-control" placeholder="0.00" required>
                  </div>

                  <div class="col-md-6">
                    <label for="gst" class="form-label fw-semibold">GST / Tax (%)</label>
                    <input type="number" step="0.01" min="0" id="gst" name="gst" class="form-control" placeholder="e.g. 5" value="5">
                  </div>

                  <div class="col-md-6">
                    <label for="total" class="form-label fw-semibold">Total Price with Tax (Rs.)</label>
                    <input type="number" step="0.01" id="total" name="total" class="form-control bg-light fw-bold text-success" placeholder="0.00" readonly>
                  </div>

                  <div class="col-12">
                    <label for="image" class="form-label fw-semibold">Dish Photo / Image</label>
                    <input type="file" id="image" name="product-image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                    <div class="form-text">Upload a JPG, PNG, or WEBP photo. If left empty, an optimal food photo will be assigned automatically.</div>
                  </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex gap-2">
                  <button type="submit" class="btn btn-primary px-4 fw-semibold">
                    <i class="bi bi-check2-circle me-1"></i> Save Dish & Sync with POS
                  </button>
                  <a href="All_menus.php" class="btn btn-outline-secondary px-3">Cancel</a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- External Menu Calculator Script -->
<script src="/RMS/public/assets/js/menu-calculator.js"></script>

<?php
include_once __DIR__ . '/../layouts/footer.php';
ob_end_flush();
?>