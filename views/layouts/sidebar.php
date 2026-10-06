<!--begin::Sidebar-->
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
  <!--begin::Sidebar Brand-->
  <div class="sidebar-brand">
    <a href="/RMS/public/index.php" class="brand-link text-decoration-none">
      <img
        src="/RMS/public/assets/images/credit/pngwing.com.png"
        alt="RMS Logo"
        class="brand-image opacity-75 shadow rounded"
        style="width: 40px; height: 40px; object-fit: contain;" />
      <span class="brand-text fw-bold text-light ms-2">RMS <small class="text-secondary fw-normal">Restaurant</small></span>
    </a>
  </div>
  <!--end::Sidebar Brand-->

  <!--begin::Sidebar Wrapper-->
  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <ul
        class="nav sidebar-menu flex-column"
        data-lte-toggle="treeview"
        role="navigation"
        aria-label="Main navigation"
        data-accordion="false"
        id="navigation">

        <!-- 1. POS Terminal (Direct Quick Action) -->
        <li class="nav-item my-1">
          <a href="/RMS/views/order/new.php" class="nav-link pos-active-badge">
            <i class="nav-icon bi bi-calculator-fill"></i>
            <p>
              POS Terminal
              <span class="badge text-bg-warning float-end text-dark fw-bold">ACTIVE</span>
            </p>
          </a>
        </li>

        <!-- 2. Dashboard -->
        <li class="nav-item">
          <a href="/RMS/public/index.php" class="nav-link">
            <i class="nav-icon bi bi-speedometer2"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <!-- 3. Orders Module -->
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon bi bi-cart-check-fill"></i>
            <p>
              Orders
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="/RMS/views/order/new.php" class="nav-link">
                <i class="nav-icon bi bi-plus-circle-dotted"></i>
                <p>New Order (POS)</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="/RMS/views/order/manage.php" class="nav-link">
                <i class="nav-icon bi bi-list-check"></i>
                <p>Manage Orders</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- Tables & Floors Module -->
        <li class="nav-item">
          <a href="/RMS/views/table/manage.php" class="nav-link">
            <i class="nav-icon bi bi-grid-3x3-gap-fill text-warning"></i>
            <p>Tables & Floors</p>
          </a>
        </li>

        <!-- 4. Menu Management -->
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon bi bi-journal-richtext"></i>
            <p>
              Menus
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="/RMS/views/menu/All_menus.php" class="nav-link">
                <i class="nav-icon bi bi-card-list"></i>
                <p>All Menus</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="/RMS/views/menu/add_menus.php" class="nav-link">
                <i class="nav-icon bi bi-plus-lg"></i>
                <p>Add Menu</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="/RMS/views/menu/Menue_catagories.php" class="nav-link">
                <i class="nav-icon bi bi-tags-fill"></i>
                <p>All Categories</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="/RMS/views/menu/Add_menue_catagories.php" class="nav-link">
                <i class="nav-icon bi bi-tag"></i>
                <p>Add Category</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- 5. Customers & Suppliers -->
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon bi bi-people-fill"></i>
            <p>
              Customers
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="/RMS/views/user/customers/costomers_list.php" class="nav-link">
                <i class="nav-icon bi bi-person-lines-fill"></i>
                <p>Customer List</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="/RMS/views/user/customers/suppliers.php" class="nav-link">
                <i class="nav-icon bi bi-truck"></i>
                <p>Suppliers</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- 6. Reports -->
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon bi bi-bar-chart-line-fill"></i>
            <p>
              Reports
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="/RMS/views/report/daily.php" class="nav-link">
                <i class="nav-icon bi bi-graph-up-arrow"></i>
                <p>Sales & Profit</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="/RMS/views/report/daily_expenses.php" class="nav-link">
                <i class="nav-icon bi bi-wallet2"></i>
                <p>Expenses & Audit</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- 7. Authentication / Sign out -->
        <li class="nav-header text-uppercase text-secondary small mt-3">Account</li>
        <li class="nav-item">
          <a href="/RMS/views/user/logout.php" class="nav-link text-danger">
            <i class="nav-icon bi bi-box-arrow-right text-danger"></i>
            <p>Logout</p>
          </a>
        </li>

      </ul>
    </nav>
  </div>
  <!--end::Sidebar Wrapper-->
</aside>
<!--end::Sidebar-->