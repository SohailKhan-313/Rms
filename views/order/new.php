<?php
$pageTitle = "POS Terminal";
include_once __DIR__ . '/../../config/database.php';
include_once __DIR__ . '/../helpers/function.php';

// Fetch categories from DB if available
$categories = [];
if ($conn) {
    $resCat = @$conn->query("SELECT * FROM category ORDER BY category_name ASC");
    if ($resCat) {
        while ($c = $resCat->fetch_assoc()) {
            $categories[] = $c['category_name'];
        }
    }
}

// Fetch menu items from DB if available
$menuItems = [];
if ($conn) {
    $resMenu = @$conn->query("SELECT * FROM menue ORDER BY sn DESC");
    if ($resMenu && $resMenu->num_rows > 0) {
        while ($m = $resMenu->fetch_assoc()) {
            $imgSrc = rms_menu_image($m['image'] ?? '');

            $menuItems[] = [
                'id' => $m['sn'],
                'code' => $m['code'] ?? '',
                'name' => $m['item_name'] ?? 'Unnamed Item',
                'category' => $m['catagory'] ?? 'General',
                'price' => floatval($m['price'] ?? 0),
                'gst' => floatval($m['gst'] ?? 0),
                'image' => $imgSrc
            ];
            if (!in_array($m['catagory'], $categories) && !empty($m['catagory'])) {
                $categories[] = $m['catagory'];
            }
        }
    }
}

// Fetch registered customers from DB
$dbCustomers = [];
if ($conn) {
    $resCust = @$conn->query("SELECT * FROM `customers` ORDER BY `name` ASC");
    if ($resCust) {
        while ($c = $resCust->fetch_assoc()) {
            $dbCustomers[] = $c;
        }
    }
}

// Fetch restaurant floors from DB
$dbFloors = [];
if ($conn) {
    $resF = @$conn->query("SELECT * FROM `restaurant_floors` WHERE `status` = 'Active' ORDER BY id ASC");
    if ($resF) {
        while ($f = $resF->fetch_assoc()) {
            $dbFloors[] = $f;
        }
    }
}
if (empty($dbFloors)) {
    $dbFloors = [
        ['id' => 1, 'floor_name' => 'Ground Floor', 'floor_code' => 'GF'],
        ['id' => 2, 'floor_name' => 'First Floor (Family)', 'floor_code' => '1F'],
        ['id' => 3, 'floor_name' => 'Rooftop Terrace', 'floor_code' => 'RT'],
        ['id' => 4, 'floor_name' => 'Outdoor Lawn', 'floor_code' => 'OD']
    ];
}

// Fetch restaurant tables from DB
$dbTables = [];
if ($conn) {
    $resT = @$conn->query("SELECT * FROM `restaurant_tables` ORDER BY floor_id ASC, table_number ASC");
    if ($resT) {
        while ($t = $resT->fetch_assoc()) {
            $dbTables[] = $t;
        }
    }
}
if (empty($dbTables)) {
    $dbTables = [
        ['id' => 1, 'table_number' => 'Table 01', 'floor_id' => 1, 'floor_name' => 'Ground Floor', 'capacity' => 2, 'status' => 'Available'],
        ['id' => 2, 'table_number' => 'Table 02', 'floor_id' => 1, 'floor_name' => 'Ground Floor', 'capacity' => 4, 'status' => 'Available'],
        ['id' => 3, 'table_number' => 'Table 03', 'floor_id' => 1, 'floor_name' => 'Ground Floor', 'capacity' => 4, 'status' => 'Occupied'],
        ['id' => 4, 'table_number' => 'Table 04', 'floor_id' => 1, 'floor_name' => 'Ground Floor', 'capacity' => 6, 'status' => 'Available']
    ];
}

$selectedFloorParam = $_GET['floor'] ?? '';
$selectedTableParam = $_GET['table'] ?? '';

// If no items in database, provide rich fallback menu items so POS is immediately playable
if (empty($menuItems)) {
    $menuItems = [
        ['id' => 1, 'code' => 'BG-01', 'name' => 'Classic Cheeseburger', 'category' => 'Burgers', 'price' => 8.99, 'gst' => 5, 'image' => '/RMS/public/assets/images/food/pos_cheeseburger.jpg'],
        ['id' => 2, 'code' => 'BG-02', 'name' => 'Double Bacon BBQ Burger', 'category' => 'Burgers', 'price' => 11.50, 'gst' => 5, 'image' => '/RMS/public/assets/images/food/pos_bbq_burger.jpg'],
        ['id' => 3, 'code' => 'PZ-01', 'name' => 'Margherita Pizza 12"', 'category' => 'Pizza', 'price' => 13.99, 'gst' => 5, 'image' => '/RMS/public/assets/images/food/pos_pizza.jpg'],
        ['id' => 4, 'code' => 'PZ-02', 'name' => 'Pepperoni Passion Pizza', 'category' => 'Pizza', 'price' => 15.50, 'gst' => 5, 'image' => '/RMS/public/assets/images/food/pos_pepperoni.jpg'],
        ['id' => 5, 'code' => 'DR-01', 'name' => 'Fresh Iced Lemon Tea', 'category' => 'Beverages', 'price' => 3.50, 'gst' => 0, 'image' => '/RMS/public/assets/images/food/pos_iced_tea.jpg'],
        ['id' => 6, 'code' => 'DR-02', 'name' => 'Cappuccino / Latte', 'category' => 'Beverages', 'price' => 4.25, 'gst' => 0, 'image' => '/RMS/public/assets/images/food/pos_cappuccino.jpg'],
        ['id' => 7, 'code' => 'SN-01', 'name' => 'Crispy French Fries (L)', 'category' => 'Sides', 'price' => 4.50, 'gst' => 5, 'image' => '/RMS/public/assets/images/food/pos_fries.jpg'],
        ['id' => 8, 'code' => 'SN-02', 'name' => 'Buffalo Chicken Wings (6pc)', 'category' => 'Sides', 'price' => 9.25, 'gst' => 5, 'image' => '/RMS/public/assets/images/food/pos_buffalo_wings.jpg'],
        ['id' => 9, 'code' => 'DS-01', 'name' => 'Chocolate Lava Cake', 'category' => 'Desserts', 'price' => 6.50, 'gst' => 5, 'image' => '/RMS/public/assets/images/food/pos_lava_cake.jpg'],
        ['id' => 10, 'code' => 'DS-02', 'name' => 'Vanilla Bean Sundae', 'category' => 'Desserts', 'price' => 5.00, 'gst' => 5, 'image' => '/RMS/public/assets/images/food/pos_icecream_sundae.jpg'],
    ];
    $categories = ['Burgers', 'Pizza', 'Beverages', 'Sides', 'Desserts'];
}

$isKioskMode = true;
include_once __DIR__ . '/../layouts/header.php';
if (isset($_GET['admin_sidebar']) && $_GET['admin_sidebar'] == '1' && !empty($_SESSION['email'])) {
    include_once __DIR__ . '/../layouts/sidebar.php';
}
?>

<main class="app-main p-0">
  <div class="pos-wrapper">

    <!-- Top Action & Meta Bar -->
    <header class="pos-header-bar">
      <!-- Section A: Brand & Direct Navigation (Prominent on all screens, top row on mobile) -->
      <div class="pos-header-nav-brand">
        <div class="pos-brand-tag">
          <i class="bi bi-shop-window text-primary"></i>
          <span class="brand-text">POS Terminal</span>
        </div>

        <!-- Primary Top Action: Always-reachable Dashboard & Auth Navigation -->
        <div class="pos-header-nav-actions">
          <a href="/RMS/public/index.php" class="btn btn-outline-primary btn-sm fw-semibold d-inline-flex align-items-center gap-1 pos-dashboard-btn" id="posDashboardNavBtn" title="Return to Admin Dashboard">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
          </a>

          <?php if (!empty($_SESSION['email'])): ?>
            <a href="/RMS/views/user/logout.php" class="btn btn-outline-danger btn-sm" title="Sign Out">
              <i class="bi bi-box-arrow-right"></i>
            </a>
          <?php else: ?>
            <a href="/RMS/views/user/login.php" class="btn btn-primary btn-sm fw-semibold shadow-sm px-2" title="Sign In to Administration">
              <i class="bi bi-box-arrow-in-right me-1"></i> Staff Login
            </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Section B: Order Modes, Table Selectors & Utilities -->
      <div class="pos-header-controls">
        <!-- Order Type Switcher -->
        <div class="pos-order-types">
          <button type="button" class="pos-type-btn active" data-type="Dine-In">
            <i class="bi bi-cup-hot-fill"></i>
            <span>Dine-In</span>
          </button>
          <button type="button" class="pos-type-btn" data-type="Takeaway">
            <i class="bi bi-bag-check-fill"></i>
            <span>Takeaway</span>
          </button>
          <button type="button" class="pos-type-btn" data-type="Delivery">
            <i class="bi bi-bicycle"></i>
            <span>Delivery</span>
          </button>
        </div>

        <!-- Floor & Table Selector Wrapper (Dine-In) -->
        <div class="d-flex align-items-center gap-1" id="posDineInLocationWrapper">
          <!-- Floor Selector -->
          <div class="pos-table-badge bg-white shadow-sm" id="posFloorSelectWrapper" title="Select Dining Floor">
            <i class="bi bi-layers-fill text-primary"></i>
            <select id="posFloorSelect" class="form-select form-select-sm border-0 bg-transparent fw-semibold py-0 ps-1 pe-3 shadow-none">
              <?php foreach ($dbFloors as $fl): ?>
                <option value="<?= htmlspecialchars($fl['floor_name']) ?>" 
                        data-floor-id="<?= $fl['id'] ?>"
                        <?= ($selectedFloorParam === $fl['floor_name']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($fl['floor_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Table Selector -->
          <div class="pos-table-badge bg-white shadow-sm" id="posTableSelectWrapper" title="Select Dining Table">
            <i class="bi bi-grid-3x3-gap-fill text-danger"></i>
            <select id="posTableSelect" class="form-select form-select-sm border-0 bg-transparent fw-semibold py-0 ps-1 pe-3 shadow-none">
              <?php foreach ($dbTables as $tb): ?>
                <option value="<?= htmlspecialchars($tb['table_number']) ?>" 
                        data-floor-name="<?= htmlspecialchars($tb['floor_name']) ?>" 
                        data-floor-id="<?= $tb['floor_id'] ?>"
                        data-status="<?= $tb['status'] ?>"
                        <?= ($selectedTableParam === $tb['table_number']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($tb['table_number']) ?> (<?= $tb['capacity'] ?>p) - <?= $tb['status'] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Utilities Group -->
        <div class="pos-header-utilities">
          <!-- Printer Status Dropdown -->
          <div class="dropdown" id="posPrinterStatusWrapper">
            <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle d-flex align-items-center gap-1 fw-semibold" id="posPrinterStatusBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Thermal Printer Connection Status">
              <span class="pos-printer-dot online" id="posPrinterDot"></span>
              <i class="bi bi-printer-fill text-success" id="posPrinterIcon"></i>
              <span class="d-none d-sm-inline" id="posPrinterStatusText">Printer: Connected</span>
              <span class="badge bg-success-subtle text-success border border-success-subtle ms-1 d-none d-md-inline" id="posPrinterModeBadge">Preview ON</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end shadow-lg p-3" style="min-width: 320px; z-index: 1050;">
              <h6 class="dropdown-header px-0 text-dark fw-bold d-flex align-items-center justify-content-between mb-2">
                <span><i class="bi bi-printer me-2 text-primary"></i>Receipt Printer Setup</span>
                <span class="badge bg-success" id="posPrinterStatePill">Attached</span>
              </h6>

              <div class="p-2 bg-light rounded border mb-2">
                <div class="form-check form-switch mb-1">
                  <input class="form-check-input" type="checkbox" role="switch" id="posPrinterAttachedToggle" checked>
                  <label class="form-check-label fw-semibold" for="posPrinterAttachedToggle" id="posPrinterToggleLabel">
                    Printer is Attached
                  </label>
                </div>
                <div class="text-muted small" id="posPrinterExplainer" style="font-size: 0.78rem;">
                  When attached: Shows on-screen receipt preview modal before printing. When not attached: Directly triggers print.
                </div>
              </div>

              <div class="mb-2">
                <label class="form-label small fw-semibold text-muted mb-1">Completion Print Action:</label>
                <div class="form-check small mb-1">
                  <input class="form-check-input" type="radio" name="printerRule" id="rulePreviewIfAttached" value="preview_if_attached" checked>
                  <label class="form-check-label" for="rulePreviewIfAttached">
                    <strong>Preview if Attached</strong> (Direct print otherwise)
                  </label>
                </div>
                <div class="form-check small">
                  <input class="form-check-input" type="radio" name="printerRule" id="ruleDirectIfAttached" value="direct_if_attached">
                  <label class="form-check-label" for="ruleDirectIfAttached">
                    <strong>Direct Print if Attached</strong> (Preview if not attached)
                  </label>
                </div>
              </div>

              <div class="d-grid gap-2 border-top pt-2 mt-2">
                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="posTestPrintSlipBtn">
                  <i class="bi bi-receipt me-1"></i> Print Test Receipt
                </button>
              </div>
            </div>
          </div>

          <!-- Recent Orders Trigger -->
          <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold" id="posRecentOrdersBtn" title="View Recent Completed Orders">
            <i class="bi bi-clock-history me-1"></i>
            <span class="d-none d-md-inline">Orders</span>
          </button>

          <!-- Held Orders Trigger -->
          <button type="button" class="btn btn-outline-warning btn-sm fw-semibold position-relative" id="posHeldOrdersListBtn" title="View Parked / Held Orders">
            <i class="bi bi-pause-circle me-1"></i>
            <span class="d-none d-md-inline">Held</span>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="posHeldBadge" style="display: none;">0</span>
          </button>

          <!-- Live Clock -->
          <div class="d-none d-xl-flex align-items-center gap-1 text-muted small fw-semibold bg-light px-2 py-1 rounded border">
            <i class="bi bi-clock"></i>
            <span id="posLiveClock">--:--:--</span>
          </div>

          <!-- Sound Toggle -->
          <button type="button" class="btn btn-light btn-sm border d-none d-md-inline-flex" id="posSoundToggle" title="Toggle audio feedback">
            <i class="bi bi-volume-up-fill text-primary"></i>
          </button>

          <!-- Fullscreen Toggle -->
          <button type="button" class="btn btn-light btn-sm border d-none d-md-inline-flex" id="posFullscreenToggle" title="Toggle Fullscreen">
            <i class="bi bi-arrows-fullscreen"></i>
          </button>
        </div>
      </div>
    </header>

    <!-- Main Dual Split Container -->
    <div class="pos-main-container">

      <!-- LEFT PANE: Product Catalog -->
      <section class="pos-catalog-pane">
        
        <!-- Category Filter Pills -->
        <div class="pos-category-scroll">
          <button type="button" class="pos-category-pill active" data-category="all">
            <i class="bi bi-grid-fill"></i>
            <span>All Items</span>
            <span class="badge-count"><?= count($menuItems) ?></span>
          </button>
          <?php foreach ($categories as $cat): 
            $catCount = count(array_filter($menuItems, function($i) use ($cat) { return strcasecmp($i['category'], $cat) === 0; }));
          ?>
            <button type="button" class="pos-category-pill" data-category="<?= htmlspecialchars($cat) ?>">
              <span><?= htmlspecialchars($cat) ?></span>
              <span class="badge-count"><?= $catCount ?></span>
            </button>
          <?php endforeach; ?>
        </div>

        <!-- Search Bar with barcode support -->
        <div class="pos-search-wrapper">
          <i class="bi bi-search pos-search-icon"></i>
          <input type="text" id="posSearchInput" class="pos-search-input" placeholder="Search dishes by name, code or scan barcode... (Press F2 to focus)" autocomplete="off">
          <button type="button" class="pos-clear-search" id="posClearSearch" title="Clear search">
            <i class="bi bi-x-circle-fill"></i>
          </button>
        </div>

        <!-- Product Grid -->
        <div class="pos-product-grid" id="posProductGrid">
          <?php foreach ($menuItems as $item): ?>
            <div class="pos-product-card" 
                 data-id="<?= htmlspecialchars($item['id']) ?>"
                 data-code="<?= htmlspecialchars($item['code']) ?>"
                 data-name="<?= htmlspecialchars($item['name']) ?>"
                 data-category="<?= htmlspecialchars($item['category']) ?>"
                 data-price="<?= $item['price'] ?>"
                 data-gst="<?= $item['gst'] ?>"
                 data-image="<?= htmlspecialchars($item['image']) ?>">
              <div class="pos-card-img-wrap">
                <img src="<?= htmlspecialchars($item['image']) ?>" class="pos-card-img" alt="<?= htmlspecialchars($item['name']) ?>" onerror="this.src='/RMS/public/assets/images/food/pos_cheeseburger.jpg';">
                <?php if (!empty($item['code'])): ?>
                  <span class="pos-card-code"><?= htmlspecialchars($item['code']) ?></span>
                <?php endif; ?>
              </div>
              <div class="pos-card-content">
                <div>
                  <div class="pos-card-category"><?= htmlspecialchars($item['category']) ?></div>
                  <h6 class="pos-card-title"><?= htmlspecialchars($item['name']) ?></h6>
                </div>
                <div class="pos-card-footer">
                  <span class="pos-card-price">Rs. <?= number_format($item['price'], 2) ?></span>
                  <span class="pos-card-add-btn">
                    <i class="bi bi-plus-lg"></i>
                  </span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

          <!-- Empty search result state -->
          <div class="pos-empty-catalog" id="posEmptyCatalog" style="display: none;">
            <i class="bi bi-search"></i>
            <h5>No dishes found</h5>
            <p class="text-muted">Try a different search term or category filter</p>
          </div>
        </div>
      </section>

      <!-- RIGHT PANE: Order Cart (Desktop & Tablets >= 768px) -->
      <aside class="pos-cart-pane d-none d-md-flex">

        <!-- Customer Selector Bar -->
        <div class="pos-cart-customer-bar">
          <div class="d-flex align-items-center gap-2 flex-grow-1">
            <i class="bi bi-person-circle text-primary fs-5"></i>
            <select id="posCustomerSelect" class="form-select form-select-sm pos-customer-select">
              <option value="walk-in" data-name="Walk-in Customer" data-phone="" data-discount="0" selected>Walk-in Customer</option>
              <?php foreach ($dbCustomers as $cust): ?>
                <option value="<?= $cust['id'] ?>" 
                        data-name="<?= htmlspecialchars($cust['name']) ?>" 
                        data-phone="<?= htmlspecialchars($cust['phone'] ?? '') ?>" 
                        data-discount="<?= floatval($cust['discount'] ?? 0) ?>">
                  <?= htmlspecialchars($cust['name']) ?><?= !empty($cust['phone']) ? ' (' . htmlspecialchars($cust['phone']) . ')' : '' ?><?= floatval($cust['discount'] ?? 0) > 0 ? ' [' . $cust['discount'] . '% VIP]' : '' ?>
                </option>
              <?php endforeach; ?>
              <option value="new">+ Add New Customer...</option>
            </select>
          </div>
          <button type="button" class="btn btn-outline-primary btn-sm pos-btn-add-cust" data-bs-toggle="modal" data-bs-target="#posAddCustomerModal" title="Quick Add Customer">
            <i class="bi bi-person-plus-fill"></i>
          </button>
        </div>

        <!-- Order Meta Info -->
        <div class="pos-cart-meta">
          <span>Active Order Ticket</span>
          <span class="text-primary fw-semibold"><i class="bi bi-person-badge me-1"></i>Cashier: Admin</span>
        </div>

        <!-- Scrollable Cart Items List -->
        <div class="pos-cart-items-list" id="posCartItemsList">
          <!-- Filled dynamically by pos.js -->
        </div>

        <!-- Empty Cart Notice -->
        <div class="pos-empty-cart" id="posEmptyCartMsg">
          <i class="bi bi-cart3"></i>
          <h6 class="fw-bold mb-1">Your order is empty</h6>
          <p class="small text-muted mb-0">Select dishes from the menu to build an order</p>
        </div>

        <!-- Calculation & Discounts -->
        <div class="pos-cart-calc-box">
          <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
            <span class="small fw-semibold text-muted">Discount:</span>
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-secondary btn-discount-preset active" data-percent="0">0%</button>
              <button type="button" class="btn btn-outline-secondary btn-discount-preset" data-percent="5">5%</button>
              <button type="button" class="btn btn-outline-secondary btn-discount-preset" data-percent="10">10%</button>
              <button type="button" class="btn btn-outline-secondary btn-discount-preset" data-percent="15">15%</button>
            </div>
          </div>

          <div class="pos-calc-row">
            <span>Subtotal</span>
            <strong class="pos-calc-subtotal">Rs. 0.00</strong>
          </div>
          <div class="pos-calc-row">
            <span>Tax / GST</span>
            <span class="pos-calc-tax">Rs. 0.00</span>
          </div>
          <div class="pos-calc-row text-danger">
            <span>Discount</span>
            <span class="pos-calc-discount">Rs. 0.00</span>
          </div>
          <div class="pos-calc-row grand-total">
            <span>Payable Total</span>
            <span class="pos-grand-price pos-calc-grand-total">Rs. 0.00</span>
          </div>
        </div>

        <!-- Cart Quick Actions & Checkout - Permanently pinned at bottom -->
        <div class="pos-cart-actions">
          <button type="button" class="btn-pos-hold" id="posHoldOrderBtn" title="Hold Order (F8)">
            <i class="bi bi-pause-circle me-1"></i> Hold (F8)
          </button>
          <button type="button" class="btn-pos-clear" id="posClearCartBtn" title="Clear Order">
            <i class="bi bi-trash3 me-1"></i> Void Order
          </button>
          <button type="button" class="btn btn-outline-dark btn-sm fw-semibold col-span-2 py-2" id="posKotBtn" title="Send to Kitchen Ticket (F9)">
            <i class="bi bi-fire me-1 text-danger"></i> Kitchen Order Ticket (KOT)
          </button>
          <button type="button" class="btn-pos-checkout" id="posDesktopCheckoutBtn" disabled>
            <i class="bi bi-credit-card-2-front-fill"></i>
            <span>Pay Now</span>
            <span class="checkout-btn-amount ms-2 fw-bold">Rs. 0.00</span>
          </button>
        </div>
      </aside>

    </div>

    <!-- Mobile Sticky Bottom Floating Cart Bar (< 768px) - Compact & Slim -->
    <div class="pos-mobile-cart-bar" id="posMobileCartBar">
      <div class="pos-mobile-cart-info">
        <span class="pos-mobile-cart-count" id="posMobileCartCount">0 items</span>
        <span class="pos-mobile-cart-total" id="posMobileCartTotal">Rs. 0.00</span>
      </div>
      <div class="d-flex align-items-center gap-1">
        <button type="button" class="pos-mobile-view-cart-btn" data-bs-toggle="modal" data-bs-target="#posViewOrderModal" title="View Order items">
          <i class="bi bi-cart-fill"></i>
          <span>View</span>
        </button>
        <button type="button" class="btn btn-success btn-sm fw-bold px-2 py-1 btn-pos-checkout d-flex align-items-center gap-1 shadow-sm" id="posMobileBarCheckoutBtn" disabled title="Proceed to Checkout">
          <i class="bi bi-credit-card-2-front-fill"></i>
          <span>Pay Now</span>
        </button>
      </div>
    </div>

  </div>
</main>

<!-- VIEW ORDER MODAL (Medium / Small Dialog) -->
<div class="modal fade" id="posViewOrderModal" tabindex="-1" aria-labelledby="posViewOrderModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header bg-light border-bottom py-2 px-3">
        <h6 class="modal-title fw-bold text-primary mb-0" id="posViewOrderModalLabel">
          <i class="bi bi-cart3 me-2"></i> Current Order
        </h6>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="overflow-auto p-3" id="posMobileCartItemsList" style="max-height: 48vh;">
          <!-- Injected dynamically by pos.js -->
        </div>
      </div>
      <div class="modal-footer border-top p-3 bg-light d-flex flex-column gap-2">
        <div class="w-100">
          <div class="d-flex justify-content-between small text-muted mb-1">
            <span>Subtotal:</span>
            <strong class="pos-calc-subtotal text-dark">Rs. 0.00</strong>
          </div>
          <div class="d-flex justify-content-between small text-muted mb-1">
            <span>Tax & GST:</span>
            <span class="pos-calc-tax text-dark">Rs. 0.00</span>
          </div>
          <div class="d-flex justify-content-between fw-bold text-primary border-top pt-2">
            <span>Total Payable:</span>
            <span class="pos-calc-grand-total fs-5">Rs. 0.00</span>
          </div>
        </div>
        <div class="d-grid gap-2 w-100">
          <button type="button" class="btn btn-success fw-bold py-2 btn-pos-checkout" id="posMobileCheckoutBtn">
            <i class="bi bi-credit-card-2-front me-1"></i> Pay Now (<span class="checkout-btn-amount">Rs. 0.00</span>)
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- PAYMENT MODAL -->
<div class="modal fade" id="posPaymentModal" tabindex="-1" aria-labelledby="posPaymentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header bg-primary text-white border-0 py-3">
        <h5 class="modal-title fw-bold" id="posPaymentModalLabel">
          <i class="bi bi-cash-stack me-2"></i> Checkout & Payment
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Amount Due Callout -->
        <div class="text-center p-3 mb-4 rounded-3 bg-light border">
          <span class="text-muted text-uppercase small fw-semibold">Amount to Pay</span>
          <h2 class="display-6 fw-bold text-primary mb-0" id="posPayModalDueAmount">Rs. 0.00</h2>
        </div>

        <!-- Payment Method Tabs -->
        <label class="form-label fw-semibold text-muted small">Select Payment Method</label>
        <div class="payment-method-selector">
          <div class="payment-method-btn active" data-method="Cash">
            <i class="bi bi-cash"></i>
            <span>Cash</span>
          </div>
          <div class="payment-method-btn" data-method="Card">
            <i class="bi bi-credit-card-2-front"></i>
            <span>Card</span>
          </div>
          <div class="payment-method-btn" data-method="Online">
            <i class="bi bi-qr-code-scan"></i>
            <span>QR / UPI</span>
          </div>
        </div>

        <!-- Cash Details & Quick Denominations -->
        <div id="cashPaymentDetails">
          <label class="form-label fw-semibold">Cash Received (Rs.)</label>
          <div class="input-group input-group-lg mb-2">
            <span class="input-group-text fw-bold">Rs.</span>
            <input type="number" step="1" class="form-control fw-bold" id="posCashTendered" placeholder="0.00">
          </div>

          <div class="quick-cash-grid">
            <button type="button" class="btn-quick-cash" data-amount="exact">Exact</button>
            <button type="button" class="btn-quick-cash" data-amount="100">Rs. 100</button>
            <button type="button" class="btn-quick-cash" data-amount="500">Rs. 500</button>
            <button type="button" class="btn-quick-cash" data-amount="1000">Rs. 1000</button>
            <button type="button" class="btn-quick-cash" data-amount="5000">Rs. 5000</button>
          </div>

          <!-- Change Due Box -->
          <div class="change-due-box mt-3" id="posChangeDueWrapper">
            <span>Change Due:</span>
            <span id="posChangeDueAmount">Rs. 0.00</span>
          </div>
        </div>
      </div>
      <div class="modal-footer border-top p-3">
        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success px-4 fw-bold" id="posConfirmPaymentBtn">
          <i class="bi bi-printer me-2"></i>Complete & Print Receipt
        </button>
      </div>
    </div>
  </div>
</div>

<!-- THERMAL RECEIPT MODAL & PRINT PREVIEW -->
<div class="modal fade" id="posReceiptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header bg-dark text-white py-3 border-0">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-receipt me-2"></i> Customer Receipt
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 bg-light overflow-auto" style="max-height: 70vh;">
        <div id="thermalReceiptPreviewContainer">
          <!-- Injected dynamically by pos.js -->
        </div>
      </div>
      <div class="modal-footer border-top p-3 d-flex justify-content-between flex-wrap gap-2">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="bi bi-plus-circle me-1"></i> New Order
        </button>
        <div class="d-flex gap-2">
          <a href="/RMS/views/order/print_slip.php?format=kot&autoprint=1" id="posReceiptKotBtn" class="btn btn-outline-danger btn-lg fw-bold px-3 btn-direct-print" title="Kitchen Order Ticket">
            <i class="bi bi-fire me-1"></i> Kitchen Ticket
          </a>
          <a href="/RMS/views/order/print_slip.php?format=thermal&autoprint=1" id="posDownloadPdfBtn" class="btn btn-danger btn-lg fw-bold px-4 btn-direct-print">
            <i class="bi bi-printer-fill me-2"></i> Print Receipt
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- HELD ORDERS MODAL -->
<div class="modal fade" id="posHeldOrdersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content border-0 shadow rounded-4">
      <div class="modal-header bg-warning py-3">
        <h5 class="modal-title fw-bold text-dark">
          <i class="bi bi-pause-circle me-2"></i> Parked / Held Orders
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" id="posHeldOrdersList" style="max-height: 60vh; overflow-y: auto;">
        <!-- Injected dynamically by pos.js -->
      </div>
    </div>
  </div>
</div>

<!-- KITCHEN ORDER TICKET (KOT) MODAL -->
<div class="modal fade" id="posKotModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow rounded-4">
      <div class="modal-header bg-danger text-white py-3">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-fire me-2"></i> Kitchen Order Ticket
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 bg-light" id="posKotContent">
        <!-- Injected dynamically by pos.js -->
      </div>
      <div class="modal-footer border-top p-3">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a href="/RMS/views/order/print_slip.php?format=kot&autoprint=1" id="posKotPdfBtn" class="btn btn-danger fw-bold btn-direct-print">
          <i class="bi bi-printer-fill me-1"></i> Print Ticket
        </a>
      </div>
    </div>
  </div>
</div>

<!-- QUICK ADD CUSTOMER MODAL -->
<div class="modal fade" id="posAddCustomerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow rounded-4">
      <div class="modal-header bg-primary text-white py-3">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-person-plus-fill me-2"></i> Quick Add Customer
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="posQuickCustomerForm" onsubmit="event.preventDefault(); 
        const n = document.getElementById('posNewCustName').value.trim(); 
        const p = document.getElementById('posNewCustPhone').value.trim(); 
        if(n){ 
          const fd = new FormData();
          fd.append('action', 'add_customer');
          fd.append('name', n);
          fd.append('phone', p);
          fd.append('added_by', 'POS Cashier');
          fetch('/RMS/views/user/customers/costomers_list.php', { method: 'POST', body: fd }).catch(console.error);

          posState.customerName = n; 
          posState.customerPhone = p; 
          const sel = document.getElementById('posCustomerSelect'); 
          const opt = new Option(n + (p ? ' ('+p+')' : ''), 'cust-new', true, true); 
          opt.dataset.name = n;
          opt.dataset.phone = p;
          opt.dataset.discount = '0';
          sel.add(opt); 
          bootstrap.Modal.getInstance(document.getElementById('posAddCustomerModal')).hide(); 
          if(window.rmsToast) window.rmsToast('Customer ' + n + ' saved to DB & selected!', 'success'); 
        }">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-semibold">Customer Full Name</label>
            <input type="text" id="posNewCustName" class="form-control" placeholder="e.g. Alex Johnson" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Phone Number</label>
            <input type="tel" id="posNewCustPhone" class="form-control" placeholder="e.g. +1 555-0199">
          </div>
        </div>
        <div class="modal-footer p-3 border-top">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-bold px-4">Save & Select</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- RECENT ORDERS MODAL -->
<div class="modal fade" id="posRecentOrdersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content border-0 shadow rounded-4">
      <div class="modal-header bg-dark text-white py-3">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-clock-history me-2"></i> Recent POS Orders
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3" id="posRecentOrdersList" style="max-height: 65vh; overflow-y: auto;">
        <!-- Injected dynamically by pos.js -->
      </div>
    </div>
  </div>
</div>

<!-- Hidden Direct Print Iframe for Silent/Instant Thermal Printing -->
<iframe id="posDirectPrintFrame" style="display:none;position:fixed;width:0;height:0;border:0;"></iframe>

<!-- Initial Database Data for Floor & Table Sync -->
<script>
window.posInitialData = {
  floors: <?= json_encode($dbFloors) ?>,
  tables: <?= json_encode($dbTables) ?>,
  initialFloor: <?= json_encode($selectedFloorParam) ?>,
  initialTable: <?= json_encode($selectedTableParam) ?>
};
</script>

<!-- Dedicated POS Application Script -->
<script src="/RMS/public/assets/js/pos.js"></script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
