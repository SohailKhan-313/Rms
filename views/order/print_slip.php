<?php
/**
 * RMS 80mm Thermal Receipt & Kitchen Order Ticket (KOT) Direct Print Slip
 * Designed for standard 80mm thermal receipt printers (72mm-80mm printable width).
 * Automatically triggers the browser's default system print preview when autoprint=1.
 */
include_once __DIR__ . '/../../config/database.php';

$orderId = intval($_GET['order_id'] ?? 0);
$format = $_GET['format'] ?? 'thermal'; // 'thermal' or 'kot'
$autoprint = isset($_GET['autoprint']) ? (intval($_GET['autoprint']) === 1) : false;

$order = null;
$items = [];

// 1. Fetch from Database if order_id is provided
if ($conn && $orderId > 0) {
    $res = $conn->query("SELECT * FROM `orders` WHERE `id` = $orderId LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $order = $row;
    }
    $resItems = $conn->query("SELECT * FROM `order_items` WHERE `order_id` = $orderId");
    if ($resItems) {
        while ($it = $resItems->fetch_assoc()) {
            $items[] = [
                'name' => $it['item_name'] ?? 'Dish Item',
                'qty' => intval($it['quantity'] ?? $it['qty'] ?? 1),
                'price' => floatval($it['price'] ?? 0),
                'total' => floatval($it['total'] ?? 0),
                'notes' => $it['notes'] ?? ''
            ];
        }
    }
}

// 2. Fallback to latest order if empty
if (!$order && $conn) {
    $res = $conn->query("SELECT * FROM `orders` ORDER BY `id` DESC LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $order = $row;
        $resItems = $conn->query("SELECT * FROM `order_items` WHERE `order_id` = '{$order['id']}'");
        if ($resItems) {
            while ($it = $resItems->fetch_assoc()) {
                $items[] = [
                    'name' => $it['item_name'] ?? 'Dish Item',
                    'qty' => intval($it['quantity'] ?? $it['qty'] ?? 1),
                    'price' => floatval($it['price'] ?? 0),
                    'total' => floatval($it['total'] ?? 0),
                    'notes' => $it['notes'] ?? ''
                ];
            }
        }
    }
}

// 3. Parse items from query if passed directly
if (empty($items) && !empty($_GET['items'])) {
    $decoded = json_decode($_GET['items'], true);
    if (is_array($decoded)) {
        foreach ($decoded as $it) {
            $items[] = [
                'name' => $it['item_name'] ?? $it['name'] ?? 'Dish Item',
                'qty' => intval($it['quantity'] ?? $it['qty'] ?? 1),
                'price' => floatval($it['price'] ?? 0),
                'total' => floatval($it['total'] ?? ($it['price'] * $it['qty'])),
                'notes' => $it['notes'] ?? ''
            ];
        }
    }
}

// Default fallback values
$orderNumber = $order['order_number'] ?? ($_GET['order_number'] ?? ('ORD-' . time()));
$orderType = $order['order_type'] ?? ($_GET['order_type'] ?? 'Dine-In');
$floorName = $order['floor_name'] ?? ($_GET['floor'] ?? '');
$rawTable = $order['table_no'] ?? ($_GET['table'] ?? '');
$customerName = $order['customer_name'] ?? ($_GET['customer_name'] ?? 'Walk-in Customer');
$paymentMethod = $order['payment_method'] ?? ($_GET['payment_method'] ?? 'Cash');
$createdAt = $order['created_at'] ?? date('Y-m-d h:i A');

$subtotal = floatval($order['subtotal'] ?? ($_GET['subtotal'] ?? 0));
$tax = floatval($order['tax'] ?? ($_GET['tax'] ?? 0));
$discount = floatval($order['discount'] ?? ($_GET['discount'] ?? 0));
$grandTotal = floatval($order['grand_total'] ?? ($_GET['grand_total'] ?? ($subtotal + $tax - $discount)));
$paidAmount = floatval($order['paid_amount'] ?? ($_GET['paid_amount'] ?? $grandTotal));
$changeAmount = floatval($order['change_amount'] ?? ($_GET['change_amount'] ?? max(0, $paidAmount - $grandTotal)));

// Clean table number - strictly remove any extra symbols, bullets, or duplicate words
$cleanTable = '';
if (!empty($rawTable)) {
    $cleanTable = preg_replace('/\s*\([^)]*\).*/', '', $rawTable);
    $cleanTable = preg_replace('/\s*-\s*(Available|Occupied|Reserved).*/i', '', $cleanTable);
    $cleanTable = str_replace(['•', 'Â', 'â€¢', '#', ':'], '', $cleanTable);
    $cleanTable = trim($cleanTable);
    if (!preg_match('/^table\s*/i', $cleanTable) && !empty($cleanTable)) {
        $cleanTable = 'Table ' . $cleanTable;
    }
}

$isKot = ($format === 'kot');
$isInvoice = ($format === 'invoice' || $format === 'a4');
$pageTitleText = $isKot ? 'KOT #' . htmlspecialchars($orderNumber) : ($isInvoice ? 'Invoice #' . htmlspecialchars($orderNumber) : 'Receipt #' . htmlspecialchars($orderNumber));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $pageTitleText ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Consolas', 'Courier New', Courier, monospace;
    }
    body {
      background: #f1f5f9;
      color: #000;
      padding: 20px 10px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .print-slip-container {
      width: 80mm;
      max-width: 100%;
      background: #fff;
      padding: 16px 12px;
      border: 1px solid #cbd5e1;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      font-size: 15px;
      line-height: 1.4;
      color: #000;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-left { text-align: left; }
    .fw-bold { font-weight: bold; }
    .fs-sm { font-size: 13px; }
    .fs-base { font-size: 15px; }
    .fs-lg { font-size: 18px; }
    .fs-xl { font-size: 21px; }
    .fs-xxl { font-size: 23px; }
    .divider {
      border-top: 1.5px dashed #000;
      margin: 8px 0;
    }
    .divider-double {
      border-top: 2.5px dashed #000;
      margin: 9px 0;
    }
    .slip-table {
      width: 100%;
      border-collapse: collapse;
      margin: 8px 0;
    }
    .slip-table th, .slip-table td {
      padding: 4px 0;
      vertical-align: top;
      font-size: 15px;
    }
    .slip-table th {
      border-bottom: 1.5px dashed #000;
      font-size: 14px;
      font-weight: bold;
    }
    .action-bar {
      margin-top: 15px;
      display: flex;
      gap: 10px;
    }
    .btn {
      padding: 8px 18px;
      font-size: 14px;
      font-weight: bold;
      border-radius: 6px;
      cursor: pointer;
      border: none;
      font-family: sans-serif;
    }
    .btn-print {
      background: #10b981;
      color: #fff;
    }
    .btn-close {
      background: #64748b;
      color: #fff;
    }
    @media print {
      body {
        background: transparent !important;
        padding: 0 !important;
      }
      .action-bar {
        display: none !important;
      }
      .print-slip-container {
        border: none !important;
        box-shadow: none !important;
        width: 100% !important;
        padding: 2px !important;
        font-size: 15px !important;
        line-height: 1.4 !important;
      }
      @page {
        size: 80mm auto;
        margin: 2mm;
      }
    }
  </style>
</head>
<body>

  <div class="print-slip-container" id="printableArea">
    <?php if ($isKot): ?>
      <!-- KITCHEN ORDER TICKET (KOT) -->
      <div class="text-center">
        <div class="fs-xl fw-bold">*** KITCHEN ORDER TICKET ***</div>
        <div class="fw-bold fs-xl" style="margin-top: 4px; letter-spacing: 0.5px;">
          <?= strtoupper(htmlspecialchars($orderType)) ?>
          <?php if ($orderType === 'Dine-In'): ?>
            <?= !empty($floorName) ? ' | ' . htmlspecialchars($floorName) : '' ?>
            <?= !empty($cleanTable) ? ' | ' . htmlspecialchars($cleanTable) : '' ?>
          <?php endif; ?>
        </div>
        <div class="fs-sm fw-bold" style="margin-top: 4px;">
          Order #: <?= htmlspecialchars($orderNumber) ?> | <?= date('h:i:s A', strtotime($createdAt)) ?>
        </div>
      </div>

      <div class="divider"></div>

      <table class="slip-table">
        <thead>
          <tr>
            <th class="text-left" style="width: 22%;">QTY</th>
            <th class="text-left">ITEM & INSTRUCTIONS</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($items)): ?>
            <tr><td colspan="2" class="text-center fs-base">No items listed</td></tr>
          <?php else: ?>
            <?php foreach ($items as $it): ?>
              <tr>
                <td class="fw-bold fs-xl" style="vertical-align: top;"><?= $it['qty'] ?>x</td>
                <td>
                  <div class="fw-bold fs-lg"><?= htmlspecialchars($it['name']) ?></div>
                  <?php if (!empty($it['notes'])): ?>
                    <div class="fs-sm fw-bold" style="margin-top: 3px; color: #000;">
                      >> NOTE: <?= htmlspecialchars($it['notes']) ?>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <div class="divider"></div>
      <div class="text-center fs-sm fw-bold">RMS Kitchen Order Management</div>

    <?php elseif ($isInvoice): ?>
      <!-- OFFICIAL RESTAURANT TAX INVOICE -->
      <div class="text-center">
        <div class="fs-xl fw-bold">RMS RESTAURANT</div>
        <div class="fs-sm">123 Gourmet Boulevard, Food District</div>
        <div class="fs-sm">Phone: +92 347 02320579</div>
        <div class="divider"></div>
        <div class="fs-lg fw-bold" style="letter-spacing: 1px;">TAX INVOICE</div>
      </div>

      <div class="divider"></div>

      <div style="font-size: 14px; line-height: 1.45;">
        <div><strong>Invoice #:</strong> <?= htmlspecialchars($orderNumber) ?></div>
        <div><strong>Date:</strong> <?= htmlspecialchars($createdAt) ?></div>
        <div><strong>Customer:</strong> <?= htmlspecialchars($customerName) ?></div>
        <div><strong>Order Type:</strong> <?= htmlspecialchars($orderType) ?><?= ($orderType === 'Dine-In' && !empty($cleanTable)) ? ' | ' . htmlspecialchars($cleanTable) : '' ?></div>
        <div><strong>Payment:</strong> <?= htmlspecialchars($paymentMethod) ?></div>
      </div>

      <div class="divider"></div>

      <table class="slip-table">
        <thead>
          <tr>
            <th class="text-left">ITEM</th>
            <th class="text-center" style="width: 16%;">QTY</th>
            <th class="text-right" style="width: 26%;">PRICE</th>
            <th class="text-right" style="width: 28%;">TOTAL</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($items)): ?>
            <tr><td colspan="4" class="text-center fs-base">No items</td></tr>
          <?php else: ?>
            <?php foreach ($items as $it): ?>
              <tr>
                <td>
                  <div class="fw-bold fs-base"><?= htmlspecialchars($it['name']) ?></div>
                  <?php if (!empty($it['notes'])): ?>
                    <div class="fs-sm" style="color: #475569;">Note: <?= htmlspecialchars($it['notes']) ?></div>
                  <?php endif; ?>
                </td>
                <td class="text-center fw-bold fs-base"><?= $it['qty'] ?></td>
                <td class="text-right fs-base">Rs. <?= number_format($it['price'], 2) ?></td>
                <td class="text-right fw-bold fs-base">Rs. <?= number_format($it['total'], 2) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <div class="divider"></div>

      <table style="width: 100%; font-size: 14px; line-height: 1.5;">
        <tr>
          <td>Subtotal:</td>
          <td class="text-right fw-bold">Rs. <?= number_format($subtotal, 2) ?></td>
        </tr>
        <tr>
          <td>Tax / GST:</td>
          <td class="text-right fw-bold">Rs. <?= number_format($tax, 2) ?></td>
        </tr>
        <?php if ($discount > 0): ?>
          <tr>
            <td>Discount:</td>
            <td class="text-right fw-bold">-Rs. <?= number_format($discount, 2) ?></td>
          </tr>
        <?php endif; ?>
        <tr class="fw-bold fs-xl" style="border-top: 1.5px dashed #000;">
          <td style="padding-top: 5px;">GRAND TOTAL:</td>
          <td class="text-right" style="padding-top: 5px;">Rs. <?= number_format($grandTotal, 2) ?></td>
        </tr>
        <tr>
          <td style="padding-top: 4px;">Paid (<?= htmlspecialchars($paymentMethod) ?>):</td>
          <td class="text-right fw-bold" style="padding-top: 4px;">Rs. <?= number_format($paidAmount, 2) ?></td>
        </tr>
        <tr>
          <td>Change:</td>
          <td class="text-right fw-bold">Rs. <?= number_format($changeAmount, 2) ?></td>
        </tr>
      </table>

      <div class="divider-double"></div>

      <div class="text-center fs-sm fw-bold">
        <div>Official Restaurant Tax Invoice</div>
        <div>Thank you for your visit!</div>
      </div>

    <?php else: ?>
      <!-- 80mm THERMAL CUSTOMER RECEIPT -->
      <div class="text-center">
        <div class="fs-xl fw-bold">RESTAURANT POINT OF SALE</div>
        <div class="fs-sm">123 Gourmet Boulevard, Food District</div>
        <div class="fs-sm">Phone: +92 347 02320579</div>
      </div>

      <div class="divider"></div>

      <div style="font-size: 14px; line-height: 1.45;">
        <div><strong>Order #:</strong> <?= htmlspecialchars($orderNumber) ?></div>
        <div><strong>Date:</strong> <?= htmlspecialchars($createdAt) ?></div>
        <div><strong>Type:</strong> <?= htmlspecialchars($orderType) ?><?= ($orderType === 'Dine-In' && !empty($cleanTable)) ? ' | ' . htmlspecialchars($cleanTable) : '' ?></div>
        <div><strong>Customer:</strong> <?= htmlspecialchars($customerName) ?></div>
        <div><strong>Cashier:</strong> Staff Cashier</div>
      </div>

      <div class="divider"></div>

      <table class="slip-table">
        <thead>
          <tr>
            <th class="text-left">ITEM</th>
            <th class="text-center" style="width: 18%;">QTY</th>
            <th class="text-right" style="width: 34%;">AMOUNT</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($items)): ?>
            <tr><td colspan="3" class="text-center fs-base">No items</td></tr>
          <?php else: ?>
            <?php foreach ($items as $it): ?>
              <tr>
                <td class="fw-bold fs-base"><?= htmlspecialchars($it['name']) ?></td>
                <td class="text-center fw-bold fs-base"><?= $it['qty'] ?></td>
                <td class="text-right fw-bold fs-base">Rs. <?= number_format($it['total'], 2) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <div class="divider"></div>

      <table style="width: 100%; font-size: 14px; line-height: 1.5;">
        <tr>
          <td>Subtotal:</td>
          <td class="text-right fw-bold">Rs. <?= number_format($subtotal, 2) ?></td>
        </tr>
        <tr>
          <td>Tax / GST:</td>
          <td class="text-right fw-bold">Rs. <?= number_format($tax, 2) ?></td>
        </tr>
        <?php if ($discount > 0): ?>
          <tr>
            <td>Discount:</td>
            <td class="text-right fw-bold">-Rs. <?= number_format($discount, 2) ?></td>
          </tr>
        <?php endif; ?>
        <tr class="fw-bold fs-xl" style="border-top: 1.5px dashed #000;">
          <td style="padding-top: 5px;">TOTAL DUE:</td>
          <td class="text-right" style="padding-top: 5px;">Rs. <?= number_format($grandTotal, 2) ?></td>
        </tr>
        <tr>
          <td style="padding-top: 4px;">Paid (<?= htmlspecialchars($paymentMethod) ?>):</td>
          <td class="text-right fw-bold" style="padding-top: 4px;">Rs. <?= number_format($paidAmount, 2) ?></td>
        </tr>
        <tr>
          <td>Change:</td>
          <td class="text-right fw-bold">Rs. <?= number_format($changeAmount, 2) ?></td>
        </tr>
      </table>

      <div class="divider-double"></div>

      <div class="text-center fs-sm fw-bold">
        <div>Thank you for dining with us!</div>
        <div>Please visit again</div>
      </div>
    <?php endif; ?>
  </div>

  <div class="action-bar">
    <button class="btn btn-print" onclick="window.print();">
      Print
    </button>
    <button class="btn btn-close" onclick="window.close();">
      Close
    </button>
  </div>

  <?php if ($autoprint): ?>
  <script>
    // Direct Print Trigger with duplicate prevention guard
    window.addEventListener('load', function () {
      setTimeout(function () {
        if (!window._rmsPrintExecuted) {
          window._rmsPrintExecuted = true;
          window.focus();
          window.print();
        }
        if (window.opener) {
          window.onafterprint = function () {
            window.close();
          };
        }
      }, 100);
    });
  </script>
  <?php endif; ?>

</body>
</html>
