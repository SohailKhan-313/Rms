<?php
$pageTitle = "Order Details";
include_once __DIR__ . '/../../config/database.php';

$orderId = intval($_GET['id'] ?? $_GET['order_id'] ?? 0);
$order = null;
$items = [];

if ($conn && $orderId > 0) {
    $res = $conn->query("SELECT * FROM `orders` WHERE `id` = '$orderId' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $order = $res->fetch_assoc();
        $resItems = $conn->query("SELECT * FROM `order_items` WHERE `order_id` = '$orderId'");
        if ($resItems) {
            while ($it = $resItems->fetch_assoc()) {
                $items[] = $it;
            }
        }
    }
}

include_once __DIR__ . '/../layouts/header.php';
include_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h3 class="mb-0">Order Invoice</h3>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="/RMS/public/index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="/RMS/views/order/manage.php">Orders</a></li>
            <li class="breadcrumb-item active" aria-current="page">Invoice</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <?php if ($order): ?>
        <div class="card shadow-sm border-0 rounded-4 mx-auto" style="max-width: 750px;">
          <div class="card-body p-4 p-md-5">
            
            <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
              <div>
                <h3 class="fw-bold text-primary mb-1">RMS Restaurant</h3>
                <p class="text-muted small mb-0">123 Gourmet Street • +1 555-890-1234</p>
              </div>
              <div class="text-end">
                <span class="badge bg-success-subtle text-success fs-6 px-3 py-2 border border-success-subtle">
                  <?= htmlspecialchars(!empty($order['order_status']) ? $order['order_status'] : (!empty($order['status']) ? $order['status'] : 'Completed')) ?>
                </span>
                <div class="text-muted small mt-2">Invoice #: <strong><?= htmlspecialchars($order['order_number']) ?></strong></div>
                <div class="text-muted small"><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></div>
              </div>
            </div>

            <div class="row mb-4">
              <div class="col-sm-6">
                <h6 class="text-muted small text-uppercase fw-bold">Billed To:</h6>
                <div class="fw-bold fs-5"><?= htmlspecialchars($order['customer_name']) ?></div>
                <?php if (!empty($order['customer_phone'])): ?>
                  <div class="text-muted"><?= htmlspecialchars($order['customer_phone']) ?></div>
                <?php endif; ?>
              </div>
              <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                <h6 class="text-muted small text-uppercase fw-bold">Order Details:</h6>
                <div>Type: <strong><?= htmlspecialchars($order['order_type']) ?></strong></div>
                <?php if (!empty($order['floor_name'])): ?>
                  <div>Floor: <strong><?= htmlspecialchars($order['floor_name']) ?></strong></div>
                <?php endif; ?>
                <?php if (!empty($order['table_no'])): ?>
                  <div>Table: <strong><?= htmlspecialchars($order['table_no']) ?></strong></div>
                <?php endif; ?>
                <div>Payment Method: <strong><?= htmlspecialchars($order['payment_method']) ?></strong></div>
              </div>
            </div>

            <div class="table-responsive mb-4">
              <table class="table table-bordered">
                <thead class="table-light">
                  <tr>
                    <th>Item Description</th>
                    <th class="text-center" style="width: 80px;">Qty</th>
                    <th class="text-end" style="width: 120px;">Unit Price</th>
                    <th class="text-end" style="width: 120px;">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($items as $item): ?>
                    <tr>
                      <td>
                        <strong><?= htmlspecialchars($item['item_name']) ?></strong>
                        <?php if (!empty($item['notes'])): ?>
                          <div class="text-muted small"><?= htmlspecialchars($item['notes']) ?></div>
                        <?php endif; ?>
                      </td>
                      <td class="text-center"><?= $item['quantity'] ?></td>
                      <td class="text-end">Rs. <?= number_format($item['price'], 2) ?></td>
                      <td class="text-end fw-bold">Rs. <?= number_format($item['total'], 2) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <div class="row justify-content-end mb-4">
              <div class="col-md-5">
                <div class="d-flex justify-content-between py-1 text-muted">
                  <span>Subtotal:</span>
                  <span>Rs. <?= number_format($order['subtotal'], 2) ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 text-muted">
                  <span>Tax / GST:</span>
                  <span>Rs. <?= number_format($order['tax'], 2) ?></span>
                </div>
                <?php if ($order['discount'] > 0): ?>
                  <div class="d-flex justify-content-between py-1 text-danger">
                    <span>Discount:</span>
                    <span>-Rs. <?= number_format($order['discount'], 2) ?></span>
                  </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between py-2 border-top border-bottom fs-5 fw-bold text-primary mt-2">
                  <span>Grand Total:</span>
                  <span>Rs. <?= number_format($order['grand_total'], 2) ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 text-muted small">
                  <span>Amount Paid:</span>
                  <span>Rs. <?= number_format($order['paid_amount'], 2) ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 text-muted small">
                  <span>Change:</span>
                  <span>Rs. <?= number_format($order['change_amount'], 2) ?></span>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-3 border-top">
              <a href="/RMS/views/order/manage.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Orders
              </a>
              <div class="d-flex gap-2 flex-wrap">
                <a href="/RMS/views/order/print_slip.php?order_id=<?= $orderId ?>&format=kot&autoprint=1" class="btn btn-outline-dark fw-bold btn-direct-print">
                  <i class="bi bi-fire me-1 text-danger"></i> Kitchen Ticket
                </a>
                <a href="/RMS/views/order/print_slip.php?order_id=<?= $orderId ?>&format=thermal&autoprint=1" class="btn btn-outline-danger fw-bold btn-direct-print">
                  <i class="bi bi-receipt me-1"></i> Print Receipt
                </a>
                <a href="/RMS/views/order/print_slip.php?order_id=<?= $orderId ?>&format=invoice&autoprint=1" class="btn btn-danger fw-bold btn-direct-print">
                  <i class="bi bi-file-earmark-pdf-fill me-1"></i> Print Invoice
                </a>
              </div>
            </div>

          </div>
        </div>
      <?php else: ?>
        <div class="alert alert-warning text-center py-5">
          <h5>Order Not Found</h5>
          <p class="text-muted">The requested order does not exist or has been removed.</p>
          <a href="/RMS/views/order/manage.php" class="btn btn-primary btn-sm">Return to Orders</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
