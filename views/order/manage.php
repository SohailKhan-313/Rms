<?php
$pageTitle = "Manage Orders";
include_once __DIR__ . '/../../config/database.php';
include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';

$orders = [];
if ($conn) {
    // Ensure table exists
    $conn->query("CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_number` VARCHAR(50) UNIQUE NOT NULL,
        `order_type` VARCHAR(30) DEFAULT 'Dine-In',
        `table_no` VARCHAR(20) DEFAULT NULL,
        `customer_name` VARCHAR(100) DEFAULT 'Walk-in Customer',
        `customer_phone` VARCHAR(30) DEFAULT NULL,
        `subtotal` DECIMAL(10,2) DEFAULT 0.00,
        `tax` DECIMAL(10,2) DEFAULT 0.00,
        `discount` DECIMAL(10,2) DEFAULT 0.00,
        `grand_total` DECIMAL(10,2) NOT NULL,
        `paid_amount` DECIMAL(10,2) DEFAULT 0.00,
        `change_amount` DECIMAL(10,2) DEFAULT 0.00,
        `payment_method` VARCHAR(30) DEFAULT 'Cash',
        `order_status` VARCHAR(30) DEFAULT 'Completed',
        `notes` TEXT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $res = $conn->query("SELECT o.*, COUNT(i.id) AS item_count 
                         FROM `orders` o 
                         LEFT JOIN `order_items` i ON o.id = i.order_id 
                         GROUP BY o.id 
                         ORDER BY o.id DESC LIMIT 100");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $orders[] = $row;
        }
    }
}
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h3 class="mb-0 fw-bold">Order Management</h3>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="/RMS/public/index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Orders</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      
      <!-- Top Action Toolbar -->
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div class="d-flex gap-2">
          <a href="/RMS/views/order/new.php" class="btn btn-primary fw-semibold">
            <i class="bi bi-plus-circle me-1"></i> New POS Order
          </a>
          <a href="/RMS/views/order/print_pdf.php?format=orders_list" target="_blank" class="btn btn-danger fw-semibold">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i> Export Orders
          </a>
        </div>
        <div class="text-muted small">
          Showing recent <strong><?= count($orders) ?></strong> orders
        </div>
      </div>

      <!-- Orders List Card -->
      <div class="card shadow-sm border-0 rounded-3">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Order #</th>
                <th>Date & Time</th>
                <th>Type / Table</th>
                <th>Customer</th>
                <th>Items</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($orders) > 0): ?>
                <?php foreach ($orders as $ord): ?>
                  <tr>
                    <td class="fw-bold text-primary">
                      <?= htmlspecialchars($ord['order_number']) ?>
                    </td>
                    <td>
                      <small class="text-muted"><?= date('M d, Y h:i A', strtotime($ord['created_at'])) ?></small>
                    </td>
                    <td>
                      <span class="rms-badge rms-badge-primary">
                        <?= htmlspecialchars($ord['order_type']) ?>
                      </span>
                      <?php if (!empty($ord['floor_name']) || !empty($ord['table_no'])): ?>
                        <span class="badge bg-secondary ms-1">
                          <?= htmlspecialchars(($ord['floor_name'] ? $ord['floor_name'] . ' • ' : '') . ($ord['table_no'] ?? '')) ?>
                        </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="fw-semibold"><?= htmlspecialchars($ord['customer_name']) ?></div>
                      <?php if (!empty($ord['customer_phone'])): ?>
                        <small class="text-muted"><?= htmlspecialchars($ord['customer_phone']) ?></small>
                      <?php endif; ?>
                    </td>
                    <td><?= intval($ord['item_count']) ?> items</td>
                    <td class="fw-bold text-success">
                      Rs. <?= number_format($ord['grand_total'], 2) ?>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark border">
                        <i class="bi bi-wallet2 me-1"></i> <?= htmlspecialchars($ord['payment_method']) ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <?= htmlspecialchars($ord['order_status']) ?>
                      </span>
                    </td>
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary view-order-details-btn" data-id="<?= $ord['id'] ?>" title="View Order Details">
                          <i class="bi bi-eye-fill"></i> View
                        </button>
                        <a href="/RMS/views/order/print_slip.php?order_id=<?= $ord['id'] ?>&format=thermal&autoprint=1" class="btn btn-outline-danger btn-direct-print" title="Print Receipt">
                          <i class="bi bi-printer-fill"></i> Receipt
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="9" class="text-center py-5 text-muted">
                    <i class="bi bi-cart-x fs-1 text-secondary"></i>
                    <h5 class="mt-2">No orders recorded yet</h5>
                    <p class="text-muted">Place your first order using the POS Terminal.</p>
                    <a href="/RMS/views/order/new.php" class="btn btn-primary btn-sm mt-1">Open POS Screen</a>
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

<!-- ORDER DETAILS MODAL -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content border-0 shadow rounded-4">
      <div class="modal-header bg-primary text-white py-3">
        <h5 class="modal-title fw-bold" id="orderDetailsModalTitle">
          <i class="bi bi-receipt me-2"></i> Order Details
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" id="orderDetailsModalBody">
        <div class="text-center py-4">
          <div class="spinner-border text-primary" role="status"></div>
        </div>
      </div>
      <div class="modal-footer border-top p-3 d-flex justify-content-between flex-wrap gap-2">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <div class="d-flex gap-2">
          <a href="#" id="modalOrderPdfKotLink" class="btn btn-outline-dark btn-sm fw-bold btn-direct-print">
            <i class="bi bi-fire me-1 text-danger"></i> Kitchen Ticket
          </a>
          <a href="#" id="modalOrderPdfReceiptLink" class="btn btn-outline-danger btn-sm fw-bold btn-direct-print">
            <i class="bi bi-receipt me-1"></i> Print Receipt
          </a>
          <a href="#" id="modalOrderPdfInvoiceLink" class="btn btn-danger btn-sm fw-bold btn-direct-print">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i> Print Invoice
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const detailBtns = document.querySelectorAll('.view-order-details-btn');
  const modalEl = document.getElementById('orderDetailsModal');
  const modalBody = document.getElementById('orderDetailsModalBody');
  const modalTitle = document.getElementById('orderDetailsModalTitle');
  const kotPdfLink = document.getElementById('modalOrderPdfKotLink');
  const receiptPdfLink = document.getElementById('modalOrderPdfReceiptLink');
  const invoicePdfLink = document.getElementById('modalOrderPdfInvoiceLink');

  detailBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      const orderId = this.dataset.id;
      if (kotPdfLink) kotPdfLink.href = '/RMS/views/order/print_slip.php?order_id=' + orderId + '&format=kot&autoprint=1';
      if (receiptPdfLink) receiptPdfLink.href = '/RMS/views/order/print_slip.php?order_id=' + orderId + '&format=thermal&autoprint=1';
      if (invoicePdfLink) invoicePdfLink.href = '/RMS/views/order/print_slip.php?order_id=' + orderId + '&format=invoice&autoprint=1';

      modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
      const modal = new bootstrap.Modal(modalEl);
      modal.show();

      fetch('/RMS/app/controller/order.php?action=get_order_details&order_id=' + orderId)
        .then(res => res.json())
        .then(data => {
          if (data && data.success && data.order) {
            const o = data.order;
            modalTitle.innerHTML = `<i class="bi bi-receipt me-2"></i> Order #${o.order_number}`;
            modalBody.innerHTML = `
              <div class="row mb-3 pb-3 border-bottom">
                <div class="col-sm-6">
                  <div class="text-muted small">Customer</div>
                  <h6 class="fw-bold mb-0">${o.customer_name} ${o.customer_phone ? '(' + o.customer_phone + ')' : ''}</h6>
                  <div class="text-muted small mt-1">Type: <strong>${o.order_type}</strong> ${o.table_no ? '• Table: <strong>' + o.table_no + '</strong>' : ''}</div>
                </div>
                <div class="col-sm-6 text-sm-end">
                  <div class="text-muted small">Date & Time</div>
                  <h6 class="fw-bold mb-0">${o.created_at}</h6>
                  <div class="text-muted small mt-1">Payment: <strong>${o.payment_method}</strong> • Status: <span class="badge bg-success">${o.order_status}</span></div>
                </div>
              </div>

              <div class="table-responsive mb-3">
                <table class="table table-bordered table-sm align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Item</th>
                      <th class="text-center" style="width: 80px;">Qty</th>
                      <th class="text-end" style="width: 100px;">Price</th>
                      <th class="text-end" style="width: 110px;">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${(o.items || []).map(i => `
                      <tr>
                        <td>
                          <strong>${i.item_name}</strong>
                          ${i.notes ? `<br><small class="text-muted">(${i.notes})</small>` : ''}
                        </td>
                        <td class="text-center">${i.quantity}</td>
                        <td class="text-end">Rs. ${parseFloat(i.price).toFixed(2)}</td>
                        <td class="text-end fw-bold">Rs. ${parseFloat(i.total).toFixed(2)}</td>
                      </tr>
                    `).join('')}
                  </tbody>
                </table>
              </div>

              <div class="row justify-content-end">
                <div class="col-sm-6">
                  <div class="d-flex justify-content-between mb-1 small text-muted">
                    <span>Subtotal:</span>
                    <span>Rs. ${parseFloat(o.subtotal).toFixed(2)}</span>
                  </div>
                  <div class="d-flex justify-content-between mb-1 small text-muted">
                    <span>Tax / GST:</span>
                    <span>Rs. ${parseFloat(o.tax).toFixed(2)}</span>
                  </div>
                  ${parseFloat(o.discount) > 0 ? `
                    <div class="d-flex justify-content-between mb-1 small text-danger">
                      <span>Discount:</span>
                      <span>-Rs. ${parseFloat(o.discount).toFixed(2)}</span>
                    </div>
                  ` : ''}
                  <div class="d-flex justify-content-between fs-5 fw-bold text-primary border-top pt-2">
                    <span>Grand Total:</span>
                    <span>Rs. ${parseFloat(o.grand_total).toFixed(2)}</span>
                  </div>
                </div>
              </div>
            `;
          } else {
            modalBody.innerHTML = '<div class="alert alert-danger">Failed to load order details.</div>';
          }
        })
        .catch(err => {
          modalBody.innerHTML = '<div class="alert alert-danger">Error fetching order details.</div>';
        });
    });
  });
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
