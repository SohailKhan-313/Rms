<?php
/**
 * RMS System-Wide PDF Document & Report Generator using FPDF
 * Generates official high-definition PDF documents across all RMS modules:
 * 
 * 1. 'thermal'      : 80mm POS Customer Thermal Receipt
 * 2. 'a4'           : Standard A4 Tax Invoice for Orders
 * 3. 'kot'          : 80mm Kitchen Order Ticket
 * 4. 'expenses'     : Daily Expenses Financial Audit Report
 * 5. 'sales_report' : Daily Sales & Revenue Audit Report
 * 6. 'customers'    : Registered Customer Directory & VIP Ledger
 * 7. 'suppliers'    : Supplier Directory & Outstanding Dues Ledger
 * 8. 'orders_list'  : Orders Management Ledger
 * 9. 'menu'         : Complete Restaurant Food & Beverage Menu
 */

require_once __DIR__ . '/../../vendor/autoload.php';
include_once __DIR__ . '/../../config/database.php';

$format = strtolower(trim($_GET['format'] ?? 'thermal'));
$download = isset($_GET['download']) && $_GET['download'] == '1';
$autoprint = isset($_GET['autoprint']) && $_GET['autoprint'] == '1';

// Extend FPDF to support embedded JavaScript for direct/automatic thermal printing
if (!class_exists('PDF_AutoPrint')) {
    class PDF_AutoPrint extends FPDF {
        protected $javascript = '';
        protected $n_js;

        function IncludeJS($script) {
            $this->javascript = $script;
        }

        protected function _putjavascript() {
            $this->_newobj();
            $this->n_js = $this->n;
            $this->_put('<<');
            $this->_put('/Names [(EmbeddedJS) ' . ($this->n + 1) . ' 0 R]');
            $this->_put('>>');
            $this->_put('endobj');
            $this->_newobj();
            $this->_put('<<');
            $this->_put('/S /JavaScript');
            $this->_put('/JS ' . $this->_textstring($this->javascript));
            $this->_put('>>');
            $this->_put('endobj');
        }

        protected function _putresources() {
            parent::_putresources();
            if (!empty($this->javascript)) {
                $this->_putjavascript();
            }
        }

        protected function _putcatalog() {
            parent::_putcatalog();
            if (!empty($this->javascript)) {
                $this->_put('/Names <</JavaScript ' . ($this->n_js) . ' 0 R>>');
            }
        }
    }
}

// -------------------------------------------------------------
// 1. FORMAT: DAILY EXPENSES AUDIT REPORT (A4)
// -------------------------------------------------------------
if ($format === 'expenses') {
    $fromDate = trim($_GET['from_date'] ?? '');
    $toDate = trim($_GET['to_date'] ?? '');

    // Backward compatibility with ?date=
    if (empty($fromDate) && empty($toDate)) {
        if (isset($_GET['date'])) {
            if ($_GET['date'] === 'all') {
                $fromDate = '';
                $toDate = '';
            } else {
                $fromDate = $_GET['date'];
                $toDate = $_GET['date'];
            }
        } else {
            $fromDate = date('Y-m-d');
            $toDate = date('Y-m-d');
        }
    }

    $expenses = [];
    $totalAmount = 0;
    $periodSales = 0;

    if ($conn) {
        $expWhere = "1=1";
        $ordWhere = "1=1";
        if (!empty($fromDate) && !empty($toDate)) {
            $fromSafe = mysqli_real_escape_string($conn, $fromDate);
            $toSafe = mysqli_real_escape_string($conn, $toDate);
            $expWhere = "`date` >= '$fromSafe' AND `date` <= '$toSafe'";
            $ordWhere = "DATE(created_at) >= '$fromSafe' AND DATE(created_at) <= '$toSafe'";
        } elseif (!empty($fromDate)) {
            $fromSafe = mysqli_real_escape_string($conn, $fromDate);
            $expWhere = "`date` >= '$fromSafe'";
            $ordWhere = "DATE(created_at) >= '$fromSafe'";
        } elseif (!empty($toDate)) {
            $toSafe = mysqli_real_escape_string($conn, $toDate);
            $expWhere = "`date` <= '$toSafe'";
            $ordWhere = "DATE(created_at) <= '$toSafe'";
        }

        // Fetch Expenses
        $res = $conn->query("SELECT * FROM `expenses` WHERE $expWhere ORDER BY `date` DESC, `id` DESC");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $expenses[] = $row;
                $totalAmount += floatval($row['amount']);
            }
        }

        // Fetch Sales in period
        $sRes = $conn->query("SELECT SUM(COALESCE(grand_total, total, 0)) AS sales FROM `orders` WHERE $ordWhere");
        if ($sRes && $sr = $sRes->fetch_assoc()) {
            $periodSales = floatval($sr['sales'] ?? 0);
        }
    }

    $netProfit = $periodSales - $totalAmount;

    // Period Text
    if (!empty($fromDate) && !empty($toDate)) {
        $periodText = ($fromDate === $toDate) ? date('M d, Y', strtotime($fromDate)) : date('M d, Y', strtotime($fromDate)) . ' to ' . date('M d, Y', strtotime($toDate));
    } elseif (!empty($fromDate)) {
        $periodText = 'From ' . date('M d, Y', strtotime($fromDate));
    } elseif (!empty($toDate)) {
        $periodText = 'Up to ' . date('M d, Y', strtotime($toDate));
    } else {
        $periodText = 'All Recorded History';
    }

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();

    // Brand Header
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor(220, 38, 38);
    $pdf->Cell(110, 8, 'RMS RESTAURANT', 0, 0, 'L');

    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(70, 8, 'EXPENSE STATEMENT', 0, 1, 'R');

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(110, 5, '123 Gourmet Boulevard, Food District | Phone: +1 (555) 890-1234', 0, 0, 'L');
    $pdf->Cell(70, 5, 'Audit Period: ' . $periodText, 0, 1, 'R');

    $pdf->Cell(110, 5, 'Generated by: Admin Staff (Authorized Accounting Audit)', 0, 0, 'L');
    $pdf->Cell(70, 5, 'Printed: ' . date('Y-m-d h:i A'), 0, 1, 'R');

    $pdf->Ln(4);
    $pdf->SetDrawColor(203, 213, 225);
    $pdf->SetLineWidth(0.4);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(6);

    // Summary Box with Sales, Expenses & Profit
    $pdf->SetFillColor(254, 242, 242);
    $pdf->Rect(15, $pdf->GetY(), 180, 14, 'F');
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(153, 27, 27);
    $pdf->Cell(60, 14, '  EXPENSES: Rs. ' . number_format($totalAmount, 2), 0, 0, 'L');
    $pdf->SetTextColor(22, 101, 52);
    $pdf->Cell(60, 14, 'SALES: Rs. ' . number_format($periodSales, 2), 0, 0, 'C');
    $pdf->SetTextColor($netProfit >= 0 ? 22 : 185, $netProfit >= 0 ? 101 : 28, $netProfit >= 0 ? 52 : 28);
    $pdf->Cell(60, 14, 'NET PROFIT: Rs. ' . number_format($netProfit, 2) . '  ', 0, 1, 'R');
    $pdf->Ln(4);

    // Table Header
    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(12, 8, '#', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Date', 1, 0, 'C', true);
    $pdf->Cell(45, 8, 'Payee / Person', 1, 0, 'L', true);
    $pdf->Cell(35, 8, 'Category', 1, 0, 'L', true);
    $pdf->Cell(38, 8, 'Note / Memo', 1, 0, 'L', true);
    $pdf->Cell(25, 8, 'Amount', 1, 1, 'R', true);

    // Table Body
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(51, 65, 85);
    $sn = 1;
    if (count($expenses) > 0) {
        foreach ($expenses as $e) {
            $pdf->Cell(12, 7, $sn++, 1, 0, 'C');
            $pdf->Cell(25, 7, $e['date'], 1, 0, 'C');
            $pdf->Cell(45, 7, substr($e['rp'], 0, 24), 1, 0, 'L');
            $pdf->Cell(35, 7, substr($e['catagory'], 0, 18), 1, 0, 'L');
            $pdf->Cell(38, 7, substr($e['note'] ?: '-', 0, 22), 1, 0, 'L');
            $pdf->Cell(25, 7, 'Rs. ' . number_format(floatval($e['amount']), 2), 1, 1, 'R');
        }

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(248, 250, 252);
        $pdf->Cell(155, 8, 'TOTAL EXPENSES:   ', 1, 0, 'R', true);
        $pdf->SetTextColor(220, 38, 38);
        $pdf->Cell(25, 8, 'Rs. ' . number_format($totalAmount, 2), 1, 1, 'R', true);
    } else {
        $pdf->Cell(180, 12, 'No expense records found for this date range.', 1, 1, 'C');
    }

    $pdf->Ln(15);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->Cell(60, 5, 'Prepared By: ___________________', 0, 0, 'L');
    $pdf->Cell(60, 5, 'Verified By: ___________________', 0, 0, 'C');
    $pdf->Cell(60, 5, 'Manager Signature: _____________', 0, 1, 'R');

    $pdf->SetY(270);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(148, 163, 184);
    $pdf->Cell(0, 4, 'RMS Restaurant Management System - Confidential Accounting Audit', 0, 1, 'C');

    $fileDateSuffix = (!empty($fromDate) ? $fromDate : 'all') . (empty($toDate) ? '' : '_to_' . $toDate);
    $filename = 'Expenses_Audit_' . $fileDateSuffix . '.pdf';
    $pdf->Output($download ? 'D' : 'I', $filename);
    exit();
}

// -------------------------------------------------------------
// 2. FORMAT: DAILY SALES AUDIT REPORT (A4)
// -------------------------------------------------------------
if ($format === 'sales_report') {
    $fromDate = trim($_GET['from_date'] ?? '');
    $toDate = trim($_GET['to_date'] ?? '');

    // Backward compatibility with ?date=
    if (empty($fromDate) && empty($toDate)) {
        if (isset($_GET['date'])) {
            if ($_GET['date'] === 'all') {
                $fromDate = '';
                $toDate = '';
            } else {
                $fromDate = $_GET['date'];
                $toDate = $_GET['date'];
            }
        } else {
            $fromDate = date('Y-m-d');
            $toDate = date('Y-m-d');
        }
    }

    $orders = [];
    $totalRev = 0;
    $totalTax = 0;
    $totalDisc = 0;
    $totalExpInPeriod = 0;

    if ($conn) {
        $ordWhere = "1=1";
        $expWhere = "1=1";
        if (!empty($fromDate) && !empty($toDate)) {
            $fromSafe = mysqli_real_escape_string($conn, $fromDate);
            $toSafe = mysqli_real_escape_string($conn, $toDate);
            $ordWhere = "DATE(created_at) >= '$fromSafe' AND DATE(created_at) <= '$toSafe'";
            $expWhere = "`date` >= '$fromSafe' AND `date` <= '$toSafe'";
        } elseif (!empty($fromDate)) {
            $fromSafe = mysqli_real_escape_string($conn, $fromDate);
            $ordWhere = "DATE(created_at) >= '$fromSafe'";
            $expWhere = "`date` >= '$fromSafe'";
        } elseif (!empty($toDate)) {
            $toSafe = mysqli_real_escape_string($conn, $toDate);
            $ordWhere = "DATE(created_at) <= '$toSafe'";
            $expWhere = "`date` <= '$toSafe'";
        }

        // Fetch Orders
        $res = $conn->query("SELECT * FROM `orders` WHERE $ordWhere ORDER BY `created_at` DESC");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $orders[] = $row;
                $totalRev += floatval($row['grand_total'] ?? $row['total'] ?? 0);
                $totalTax += floatval($row['tax'] ?? 0);
                $totalDisc += floatval($row['discount'] ?? 0);
            }
        }

        // Fetch Expenses in same period
        $eRes = $conn->query("SELECT SUM(amount) AS total_exp FROM `expenses` WHERE $expWhere");
        if ($eRes && $er = $eRes->fetch_assoc()) {
            $totalExpInPeriod = floatval($er['total_exp'] ?? 0);
        }
    }

    $netProfit = $totalRev - $totalExpInPeriod;

    // Period Text
    if (!empty($fromDate) && !empty($toDate)) {
        $periodText = ($fromDate === $toDate) ? date('M d, Y', strtotime($fromDate)) : date('M d, Y', strtotime($fromDate)) . ' to ' . date('M d, Y', strtotime($toDate));
    } elseif (!empty($fromDate)) {
        $periodText = 'From ' . date('M d, Y', strtotime($fromDate));
    } elseif (!empty($toDate)) {
        $periodText = 'Up to ' . date('M d, Y', strtotime($toDate));
    } else {
        $periodText = 'All Recorded History';
    }

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor(79, 70, 229);
    $pdf->Cell(110, 8, 'RMS RESTAURANT', 0, 0, 'L');

    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(70, 8, 'SALES & PROFIT AUDIT', 0, 1, 'R');

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(110, 5, '123 Gourmet Boulevard, Food District | Phone: +1 (555) 890-1234', 0, 0, 'L');
    $pdf->Cell(70, 5, 'Sales Period: ' . $periodText, 0, 1, 'R');

    $pdf->Cell(110, 5, 'Register: POS Terminal 01 | Shift: All Shifts', 0, 0, 'L');
    $pdf->Cell(70, 5, 'Generated: ' . date('Y-m-d h:i A'), 0, 1, 'R');

    $pdf->Ln(4);
    $pdf->SetDrawColor(203, 213, 225);
    $pdf->SetLineWidth(0.4);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(6);

    // Summary Box: Sales, Expenses & Net Profit
    $pdf->SetFillColor(238, 242, 255);
    $pdf->Rect(15, $pdf->GetY(), 180, 14, 'F');
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(67, 56, 202);
    $pdf->Cell(50, 14, '  SALES: Rs. ' . number_format($totalRev, 2), 0, 0, 'L');
    $pdf->SetTextColor(185, 28, 28);
    $pdf->Cell(45, 14, 'EXP: Rs. ' . number_format($totalExpInPeriod, 2), 0, 0, 'C');
    $pdf->SetTextColor($netProfit >= 0 ? 22 : 185, $netProfit >= 0 ? 101 : 28, $netProfit >= 0 ? 52 : 28);
    $pdf->Cell(50, 14, 'NET PROFIT: Rs. ' . number_format($netProfit, 2), 0, 0, 'C');
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(35, 14, 'ORDERS: ' . count($orders) . '  ', 0, 1, 'R');
    $pdf->Ln(4);

    // Table Header
    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(12, 8, '#', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Order #', 1, 0, 'L', true);
    $pdf->Cell(25, 8, 'Date/Time', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Type', 1, 0, 'L', true);
    $pdf->Cell(45, 8, 'Customer', 1, 0, 'L', true);
    $pdf->Cell(33, 8, 'Grand Total', 1, 1, 'R', true);

    // Table Body
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(51, 65, 85);
    $sn = 1;
    if (count($orders) > 0) {
        foreach ($orders as $o) {
            $pdf->Cell(12, 7, $sn++, 1, 0, 'C');
            $pdf->Cell(35, 7, $o['order_number'] ?: ('ORD-' . $o['id']), 1, 0, 'L');
            $pdf->Cell(25, 7, date('m/d h:i A', strtotime($o['created_at'])), 1, 0, 'C');
            $pdf->Cell(30, 7, $o['order_type'] ?: 'Dine-In', 1, 0, 'L');
            $pdf->Cell(45, 7, substr($o['customer_name'] ?: 'Walk-in', 0, 24), 1, 0, 'L');
            $pdf->Cell(33, 7, 'Rs. ' . number_format(floatval($o['grand_total'] ?? $o['total'] ?? 0), 2), 1, 1, 'R');
        }

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(248, 250, 252);
        $pdf->Cell(147, 8, 'NET SALES IN PERIOD:   ', 1, 0, 'R', true);
        $pdf->SetTextColor(16, 185, 129);
        $pdf->Cell(33, 8, 'Rs. ' . number_format($totalRev, 2), 1, 1, 'R', true);
    } else {
        $pdf->Cell(180, 12, 'No orders recorded for this date range.', 1, 1, 'C');
    }

    $pdf->SetY(270);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(148, 163, 184);
    $pdf->Cell(0, 4, 'RMS Restaurant Management System - Official Sales & Profit Audit Sheet', 0, 1, 'C');

    $fileDateSuffix = (!empty($fromDate) ? $fromDate : 'all') . (empty($toDate) ? '' : '_to_' . $toDate);
    $filename = 'Sales_Audit_' . $fileDateSuffix . '.pdf';
    $pdf->Output($download ? 'D' : 'I', $filename);
    exit();
}

// -------------------------------------------------------------
// 3. FORMAT: CUSTOMER DIRECTORY & VIP LEDGER (A4)
// -------------------------------------------------------------
if ($format === 'customers') {
    $customers = [];
    if ($conn) {
        $res = $conn->query("SELECT * FROM `customers` ORDER BY `name` ASC");
        if ($res) {
            while ($row = $res->fetch_assoc()) $customers[] = $row;
        }
    }

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor(79, 70, 229);
    $pdf->Cell(110, 8, 'RMS RESTAURANT', 0, 0, 'L');
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(70, 8, 'CUSTOMER DIRECTORY', 0, 1, 'R');

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(110, 5, 'Official Customer Database & VIP Program Ledger', 0, 0, 'L');
    $pdf->Cell(70, 5, 'Date: ' . date('F d, Y'), 0, 1, 'R');

    $pdf->Ln(4);
    $pdf->SetDrawColor(203, 213, 225);
    $pdf->SetLineWidth(0.4);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(6);

    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(12, 8, '#', 1, 0, 'C', true);
    $pdf->Cell(50, 8, 'Customer Name', 1, 0, 'L', true);
    $pdf->Cell(35, 8, 'Phone', 1, 0, 'L', true);
    $pdf->Cell(45, 8, 'Email', 1, 0, 'L', true);
    $pdf->Cell(20, 8, 'Discount', 1, 0, 'C', true);
    $pdf->Cell(18, 8, 'Staff', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(51, 65, 85);
    $sn = 1;
    foreach ($customers as $c) {
        $pdf->Cell(12, 7, $sn++, 1, 0, 'C');
        $pdf->Cell(50, 7, substr($c['name'], 0, 26), 1, 0, 'L');
        $pdf->Cell(35, 7, $c['phone'] ?: 'N/A', 1, 0, 'L');
        $pdf->Cell(45, 7, substr($c['email'] ?: 'N/A', 0, 24), 1, 0, 'L');
        $pdf->Cell(20, 7, floatval($c['discount']) > 0 ? ($c['discount'] . '%') : '0%', 1, 0, 'C');
        $pdf->Cell(18, 7, substr($c['added_by'] ?? 'Admin', 0, 9), 1, 1, 'C');
    }

    $pdf->SetY(270);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(148, 163, 184);
    $pdf->Cell(0, 4, 'RMS Restaurant Management System - Total Customers: ' . count($customers), 0, 1, 'C');

    $pdf->Output($download ? 'D' : 'I', 'Customer_Directory_' . date('Ymd') . '.pdf');
    exit();
}

// -------------------------------------------------------------
// 4. FORMAT: SUPPLIER DIRECTORY & DUES LEDGER (A4)
// -------------------------------------------------------------
if ($format === 'suppliers') {
    $suppliers = [];
    $totalDues = 0;
    if ($conn) {
        $res = $conn->query("SELECT * FROM `suppliers` ORDER BY `supplier_name` ASC");
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $suppliers[] = $row;
                $totalDues += floatval($row['dues']);
            }
        } else {
            $resLegacy = $conn->query("SELECT * FROM `supliers` ORDER BY `name` ASC");
            if ($resLegacy) {
                while ($lr = $resLegacy->fetch_assoc()) {
                    $suppliers[] = [
                        'supplier_name' => $lr['name'],
                        'phone' => $lr['phone'],
                        'item_of_supply' => $lr['item'],
                        'date' => $lr['date'],
                        'dues' => $lr['dues']
                    ];
                    $totalDues += floatval($lr['dues']);
                }
            }
        }
    }

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor(79, 70, 229);
    $pdf->Cell(110, 8, 'RMS RESTAURANT', 0, 0, 'L');
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(70, 8, 'SUPPLIER LEDGER', 0, 1, 'R');

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(110, 5, 'Official Vendor & Supplier Accounts Payable Ledger', 0, 0, 'L');
    $pdf->Cell(70, 5, 'Date: ' . date('F d, Y'), 0, 1, 'R');

    $pdf->Ln(4);
    $pdf->SetDrawColor(203, 213, 225);
    $pdf->SetLineWidth(0.4);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(6);

    // Summary Box
    $pdf->SetFillColor(254, 242, 242);
    $pdf->Rect(15, $pdf->GetY(), 180, 12, 'F');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(153, 27, 27);
    $pdf->Cell(90, 12, '   TOTAL OUTSTANDING DUES: $' . number_format($totalDues, 2), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(90, 12, 'Active Suppliers: ' . count($suppliers) . '   ', 0, 1, 'R');
    $pdf->Ln(4);

    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(12, 8, '#', 1, 0, 'C', true);
    $pdf->Cell(50, 8, 'Supplier Name', 1, 0, 'L', true);
    $pdf->Cell(35, 8, 'Phone', 1, 0, 'L', true);
    $pdf->Cell(43, 8, 'Item of Supply', 1, 0, 'L', true);
    $pdf->Cell(20, 8, 'Date', 1, 0, 'C', true);
    $pdf->Cell(20, 8, 'Dues ($)', 1, 1, 'R', true);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(51, 65, 85);
    $sn = 1;
    foreach ($suppliers as $s) {
        $pdf->Cell(12, 7, $sn++, 1, 0, 'C');
        $pdf->Cell(50, 7, substr($s['supplier_name'], 0, 26), 1, 0, 'L');
        $pdf->Cell(35, 7, $s['phone'], 1, 0, 'L');
        $pdf->Cell(43, 7, substr($s['item_of_supply'], 0, 22), 1, 0, 'L');
        $pdf->Cell(20, 7, $s['date'], 1, 0, 'C');
        $pdf->Cell(20, 7, 'Rs. ' . number_format(floatval($s['dues']), 2), 1, 1, 'R');
    }

    $pdf->SetY(270);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(148, 163, 184);
    $pdf->Cell(0, 4, 'RMS Restaurant Management System - Supplier Ledger', 0, 1, 'C');

    $pdf->Output($download ? 'D' : 'I', 'Supplier_Ledger_' . date('Ymd') . '.pdf');
    exit();
}

// -------------------------------------------------------------
// 5. FORMAT: COMPLETE FOOD MENU CATALOG (A4)
// -------------------------------------------------------------
if ($format === 'menu') {
    $menu = [];
    if ($conn) {
        $res = $conn->query("SELECT * FROM `menue` ORDER BY `catagory` ASC, `item_name` ASC");
        if ($res) {
            while ($row = $res->fetch_assoc()) $menu[] = $row;
        }
    }

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 20);
    $pdf->SetTextColor(79, 70, 229);
    $pdf->Cell(110, 8, 'RMS RESTAURANT', 0, 0, 'L');
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(70, 8, 'FOOD & BEVERAGE MENU', 0, 1, 'R');

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(110, 5, 'Master Menu Catalog & Pricing List', 0, 0, 'L');
    $pdf->Cell(70, 5, 'Generated: ' . date('F d, Y'), 0, 1, 'R');

    $pdf->Ln(4);
    $pdf->SetDrawColor(203, 213, 225);
    $pdf->SetLineWidth(0.4);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(6);

    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(12, 8, '#', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Code', 1, 0, 'C', true);
    $pdf->Cell(65, 8, 'Dish Name', 1, 0, 'L', true);
    $pdf->Cell(35, 8, 'Category', 1, 0, 'L', true);
    $pdf->Cell(20, 8, 'Price', 1, 0, 'R', true);
    $pdf->Cell(23, 8, 'Total ($)', 1, 1, 'R', true);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(51, 65, 85);
    $sn = 1;
    foreach ($menu as $m) {
        $pdf->Cell(12, 7, $sn++, 1, 0, 'C');
        $pdf->Cell(25, 7, $m['code'], 1, 0, 'C');
        $pdf->Cell(65, 7, substr($m['item_name'], 0, 36), 1, 0, 'L');
        $pdf->Cell(35, 7, substr($m['catagory'], 0, 18), 1, 0, 'L');
        $pdf->Cell(20, 7, 'Rs. ' . number_format(floatval($m['price']), 2), 1, 0, 'R');
        $pdf->Cell(23, 7, 'Rs. ' . number_format(floatval($m['total']), 2), 1, 1, 'R');
    }

    $pdf->SetY(270);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(148, 163, 184);
    $pdf->Cell(0, 4, 'RMS Restaurant Management System - Total Dishes: ' . count($menu), 0, 1, 'C');

    $pdf->Output($download ? 'D' : 'I', 'RMS_Menu_' . date('Ymd') . '.pdf');
    exit();
}

// -------------------------------------------------------------
// 6. FORMAT: KITCHEN ORDER TICKET (KOT) - 80mm THERMAL
// -------------------------------------------------------------
if ($format === 'kot') {
    $orderType = $_GET['order_type'] ?? 'Dine-In';
    $floorName = $_GET['floor'] ?? '';
    $tableNo = $_GET['table'] ?? '';
    $orderId = intval($_GET['order_id'] ?? 0);
    $orderNumber = $_GET['order_number'] ?? '';
    
    $items = [];

    // 1. If an order_id is provided, fetch order details & items from database
    if ($conn && $orderId > 0) {
        $ordRes = $conn->query("SELECT * FROM `orders` WHERE `id` = '$orderId' LIMIT 1");
        if ($ordRes && $ordRow = $ordRes->fetch_assoc()) {
            $orderNumber = $ordRow['order_number'] ?? ('ORD-' . $ordRow['id']);
            $orderType = $ordRow['order_type'] ?? $orderType;
            $floorName = $ordRow['floor_name'] ?? $floorName;
            $tableNo = $ordRow['table_no'] ?? $tableNo;
        }

        $res = $conn->query("SELECT * FROM `order_items` WHERE `order_id` = '$orderId'");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $items[] = [
                    'item_name' => $row['item_name'] ?? 'Dish Item',
                    'quantity' => intval($row['quantity'] ?? $row['qty'] ?? 1),
                    'notes' => $row['notes'] ?? ''
                ];
            }
        }
    }

    // 2. If items were passed directly from POS cart (e.g. JSON or array)
    if (empty($items) && !empty($_REQUEST['items'])) {
        $rawItems = $_REQUEST['items'];
        $decoded = is_string($rawItems) ? json_decode($rawItems, true) : $rawItems;
        if (is_array($decoded)) {
            foreach ($decoded as $it) {
                $name = $it['item_name'] ?? $it['name'] ?? '';
                if (!empty($name)) {
                    $items[] = [
                        'item_name' => $name,
                        'quantity' => intval($it['quantity'] ?? $it['qty'] ?? 1),
                        'notes' => $it['notes'] ?? ''
                    ];
                }
            }
        }
    }

    // 3. If still empty and no order_id specified, look up the latest order placed in DB
    if (empty($items) && $orderId <= 0 && $conn) {
        $lastOrdRes = $conn->query("SELECT * FROM `orders` ORDER BY `id` DESC LIMIT 1");
        if ($lastOrdRes && $lastOrd = $lastOrdRes->fetch_assoc()) {
            $orderNumber = $lastOrd['order_number'] ?? ('ORD-' . $lastOrd['id']);
            $orderType = $lastOrd['order_type'] ?? $orderType;
            $floorName = $lastOrd['floor_name'] ?? $floorName;
            $tableNo = $lastOrd['table_no'] ?? $tableNo;

            $resLastItems = $conn->query("SELECT * FROM `order_items` WHERE `order_id` = '{$lastOrd['id']}'");
            if ($resLastItems) {
                while ($row = $resLastItems->fetch_assoc()) {
                    $items[] = [
                        'item_name' => $row['item_name'] ?? 'Dish Item',
                        'quantity' => intval($row['quantity'] ?? $row['qty'] ?? 1),
                        'notes' => $row['notes'] ?? ''
                    ];
                }
            }
        }
    }

    $height = 95 + (max(1, count($items)) * 14);
    $pdf = new PDF_AutoPrint('P', 'mm', [80, max(140, $height)]);
    if ($autoprint) {
        $pdf->IncludeJS("print('true');");
    }
    $pdf->SetMargins(4, 5, 4);
    $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 13.5);
    $pdf->Cell(72, 7, '*** KITCHEN ORDER TICKET ***', 0, 1, 'C');

    if (!empty($orderNumber)) {
        $pdf->SetFont('Arial', 'B', 10.5);
        $pdf->Cell(72, 5, 'Order #' . $orderNumber, 0, 1, 'C');
    }

    // Clean table number: strip bullets, parentheses, extra symbols, duplicate words
    $cleanTable = '';
    if (!empty($tableNo)) {
        $cleanTable = preg_replace('/\s*\([^)]*\).*/', '', $tableNo);
        $cleanTable = preg_replace('/\s*-\s*(Available|Occupied|Reserved).*/i', '', $cleanTable);
        $cleanTable = str_replace(['•', 'Â', 'â€¢', '#', ':'], '', $cleanTable);
        $cleanTable = trim($cleanTable);
        if (!preg_match('/^table\s*/i', $cleanTable) && !empty($cleanTable)) {
            $cleanTable = 'Table ' . $cleanTable;
        }
    }
    $pdf->SetFont('Arial', 'B', 11.5);
    $locStr = strtoupper($orderType);
    if ($orderType === 'Dine-In') {
        $locStr .= ($floorName ? " | $floorName" : "") . ($cleanTable ? " | $cleanTable" : "");
    }
    $pdf->Cell(72, 6, $locStr, 0, 1, 'C');

    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(72, 4, 'Time: ' . date('h:i:s A') . ' | Server: Staff', 0, 1, 'C');
    $pdf->Cell(72, 3, '---------------------------------------------------------', 0, 1, 'C');

    $pdf->SetFont('Arial', 'B', 10.5);
    $pdf->Cell(14, 6, 'QTY', 0, 0, 'L');
    $pdf->Cell(58, 6, 'ITEM DESCRIPTION', 0, 1, 'L');
    $pdf->Cell(72, 2, '---------------------------------------------------------', 0, 1, 'C');

    if (empty($items)) {
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(72, 8, 'No items in this ticket.', 0, 1, 'C');
    } else {
        foreach ($items as $item) {
            $qty = $item['quantity'] ?? $item['qty'] ?? 1;
            $name = $item['item_name'] ?? 'Dish Item';
            $notes = $item['notes'] ?? '';

            $pdf->SetFont('Arial', 'B', 13);
            $pdf->Cell(14, 7, $qty . 'x', 0, 0, 'L');
            $pdf->SetFont('Arial', 'B', 11.5);
            $pdf->Cell(58, 7, substr($name, 0, 24), 0, 1, 'L');

            if (!empty($notes)) {
                $pdf->SetFont('Arial', 'B', 9.5);
                $pdf->Cell(14, 5, '', 0, 0);
                $pdf->Cell(58, 5, '>> NOTE: ' . substr($notes, 0, 28), 0, 1, 'L');
            }
        }
    }

    $pdf->Cell(72, 3, '---------------------------------------------------------', 0, 1, 'C');
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Cell(72, 5, 'RMS Kitchen Display System', 0, 1, 'C');

    $pdf->Output($download ? 'D' : 'I', 'KOT_' . date('His') . '.pdf');
    exit();
}

// -------------------------------------------------------------
// 7. FORMAT: MASTER ORDERS LEDGER (A4)
// -------------------------------------------------------------
if ($format === 'orders_list') {
    $orders = [];
    $totalRev = 0;
    if ($conn) {
        $res = $conn->query("SELECT * FROM `orders` ORDER BY `id` DESC LIMIT 100");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $orders[] = $row;
                $totalRev += floatval($row['grand_total'] ?? $row['total'] ?? 0);
            }
        }
    }

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor(79, 70, 229);
    $pdf->Cell(110, 8, 'RMS RESTAURANT', 0, 0, 'L');
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(70, 8, 'ORDERS LEDGER', 0, 1, 'R');

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(110, 5, 'Master Orders History (Recent 100 Orders)', 0, 0, 'L');
    $pdf->Cell(70, 5, 'Date: ' . date('F d, Y h:i A'), 0, 1, 'R');

    $pdf->Ln(4);
    $pdf->SetDrawColor(203, 213, 225);
    $pdf->SetLineWidth(0.4);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(6);

    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(12, 8, '#', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Order #', 1, 0, 'L', true);
    $pdf->Cell(40, 8, 'Customer', 1, 0, 'L', true);
    $pdf->Cell(25, 8, 'Type', 1, 0, 'L', true);
    $pdf->Cell(35, 8, 'Date / Time', 1, 0, 'C', true);
    $pdf->Cell(33, 8, 'Total ($)', 1, 1, 'R', true);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(51, 65, 85);
    $sn = 1;
    foreach ($orders as $o) {
        $pdf->Cell(12, 7, $sn++, 1, 0, 'C');
        $pdf->Cell(35, 7, $o['order_number'] ?: ('ORD-' . $o['id']), 1, 0, 'L');
        $pdf->Cell(40, 7, substr($o['customer_name'] ?: 'Walk-in', 0, 22), 1, 0, 'L');
        $pdf->Cell(25, 7, $o['order_type'] ?: 'Dine-In', 1, 0, 'L');
        $pdf->Cell(35, 7, date('M d, h:i A', strtotime($o['created_at'])), 1, 0, 'C');
        $pdf->Cell(33, 7, 'Rs. ' . number_format(floatval($o['grand_total'] ?? $o['total'] ?? 0), 2), 1, 1, 'R');
    }

    $pdf->SetY(270);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(148, 163, 184);
    $pdf->Cell(0, 4, 'RMS Restaurant Management System - Master Ledger', 0, 1, 'C');

    $pdf->Output($download ? 'D' : 'I', 'Orders_Ledger_' . date('Ymd') . '.pdf');
    exit();
}

// -------------------------------------------------------------
// 8. ORDER SPECIFIC FORMATS: THERMAL RECEIPT OR A4 INVOICE
// -------------------------------------------------------------
$orderId = intval($_GET['order_id'] ?? $_GET['id'] ?? 0);
$order = null;
$items = [];

if ($conn && $orderId > 0) {
    $res = $conn->query("SELECT * FROM `orders` WHERE `id` = '$orderId' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $order = $res->fetch_assoc();
        $resItems = $conn->query("SELECT * FROM `order_items` WHERE `order_id` = '$orderId'");
        if ($resItems) {
            while ($row = $resItems->fetch_assoc()) {
                $items[] = [
                    'item_name' => $row['item_name'] ?? $row['product_name'] ?? ('Product #' . ($row['product_id'] ?? 1)),
                    'quantity' => intval($row['quantity'] ?? $row['qty'] ?? 1),
                    'price' => floatval($row['price']),
                    'total' => floatval($row['total'] ?? $row['subtotal'] ?? ($row['price'] * ($row['quantity'] ?? $row['qty'] ?? 1)))
                ];
            }
        }
    }
}

// If order not found by ID or order_id wasn't provided, try fetching the most recent real order from database
if (!$order && $conn) {
    $lastRes = $conn->query("SELECT * FROM `orders` ORDER BY `id` DESC LIMIT 1");
    if ($lastRes && $lastRes->num_rows > 0) {
        $order = $lastRes->fetch_assoc();
        $resItems = $conn->query("SELECT * FROM `order_items` WHERE `order_id` = '{$order['id']}'");
        if ($resItems) {
            while ($row = $resItems->fetch_assoc()) {
                $items[] = [
                    'item_name' => $row['item_name'] ?? $row['product_name'] ?? ('Product #' . ($row['product_id'] ?? 1)),
                    'quantity' => intval($row['quantity'] ?? $row['qty'] ?? 1),
                    'price' => floatval($row['price']),
                    'total' => floatval($row['total'] ?? $row['subtotal'] ?? ($row['price'] * ($row['quantity'] ?? $row['qty'] ?? 1)))
                ];
            }
        }
    }
}

// Fallback only if database has zero orders recorded
if (!$order) {
    $order = [
        'id' => 0,
        'order_number' => 'ORD-' . date('Ymd') . '-0000',
        'order_type' => 'Dine-In',
        'floor_name' => '',
        'table_no' => '',
        'customer_name' => 'Walk-in Customer',
        'customer_phone' => '',
        'subtotal' => 0.00,
        'tax' => 0.00,
        'discount' => 0.00,
        'grand_total' => 0.00,
        'paid_amount' => 0.00,
        'change_amount' => 0.00,
        'payment_method' => 'Cash',
        'order_status' => 'Pending',
        'created_at' => date('Y-m-d H:i:s')
    ];
    $items = [];
}

$order['order_number'] = $order['order_number'] ?? ('ORD-' . $order['id']);
$order['customer_name'] = $order['customer_name'] ?? 'Walk-in Customer';
$order['order_type'] = $order['order_type'] ?? 'Dine-In';
$order['payment_method'] = $order['payment_method'] ?? 'Cash';
$order['subtotal'] = floatval($order['subtotal'] ?? 0);
$order['tax'] = floatval($order['tax'] ?? 0);
$order['discount'] = floatval($order['discount'] ?? 0);
$order['grand_total'] = floatval($order['grand_total'] ?? $order['total'] ?? 0);
$order['paid_amount'] = floatval($order['paid_amount'] ?? $order['grand_total']);
$order['change_amount'] = floatval($order['change_amount'] ?? 0);

// FORMAT 8A: 80mm THERMAL RECEIPT
if ($format === 'thermal') {
    $receiptHeight = 130 + (count($items) * 11);
    $pdf = new PDF_AutoPrint('P', 'mm', [80, max(160, $receiptHeight)]);
    if ($autoprint) {
        $pdf->IncludeJS("print('true');");
    }
    $pdf->SetMargins(4, 5, 4);
    $pdf->SetAutoPageBreak(true, 5);
    $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 14.5);
    $pdf->Cell(72, 7, 'RMS RESTAURANT', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 9.5);
    $pdf->Cell(72, 5, '123 Gourmet Blvd, Suite 100', 0, 1, 'C');
    $pdf->Cell(72, 5, 'Tel: +92 347 02320579', 0, 1, 'C');

    $pdf->Cell(72, 3, '---------------------------------------------------------', 0, 1, 'C');

    $pdf->SetFont('Arial', 'B', 10.5);
    $pdf->Cell(72, 5, 'ORDER: ' . $order['order_number'], 0, 1, 'L');
    $pdf->SetFont('Arial', '', 9.5);
    $pdf->Cell(72, 5, 'Date: ' . date('M d, Y h:i A', strtotime($order['created_at'])), 0, 1, 'L');
    $typeInfo = 'Type: ' . $order['order_type'];
    if (!empty($order['floor_name'])) $typeInfo .= ' (' . $order['floor_name'] . ')';
    if (!empty($order['table_no'])) $typeInfo .= ' • Table: ' . $order['table_no'];
    $pdf->Cell(72, 5, $typeInfo, 0, 1, 'L');
    $pdf->Cell(72, 5, 'Customer: ' . $order['customer_name'], 0, 1, 'L');

    $pdf->Cell(72, 3, '---------------------------------------------------------', 0, 1, 'C');

    $pdf->SetFont('Arial', 'B', 9.5);
    $pdf->Cell(10, 5, 'Qty', 0, 0, 'C');
    $pdf->Cell(38, 5, 'Item', 0, 0, 'L');
    $pdf->Cell(24, 5, 'Total', 0, 1, 'R');
    $pdf->Cell(72, 2, '---------------------------------------------------------', 0, 1, 'C');

    $pdf->SetFont('Arial', '', 9.5);
    if (empty($items)) {
        $pdf->Cell(72, 7, 'No items in this order', 0, 1, 'C');
    } else {
        foreach ($items as $item) {
            $pdf->SetFont('Arial', 'B', 9.5);
            $pdf->Cell(10, 5, $item['quantity'] . 'x', 0, 0, 'C');
            $pdf->Cell(38, 5, substr($item['item_name'], 0, 24), 0, 0, 'L');
            $pdf->Cell(24, 5, 'Rs. ' . number_format($item['total'], 2), 0, 1, 'R');
        }
    }

    $pdf->Cell(72, 3, '---------------------------------------------------------', 0, 1, 'C');

    $pdf->SetFont('Arial', '', 9.5);
    $pdf->Cell(42, 5, 'Subtotal:', 0, 0, 'R');
    $pdf->Cell(30, 5, 'Rs. ' . number_format($order['subtotal'], 2), 0, 1, 'R');

    if ($order['tax'] > 0) {
        $pdf->Cell(42, 5, 'Tax / GST:', 0, 0, 'R');
        $pdf->Cell(30, 5, 'Rs. ' . number_format($order['tax'], 2), 0, 1, 'R');
    }

    if ($order['discount'] > 0) {
        $pdf->Cell(42, 5, 'Discount:', 0, 0, 'R');
        $pdf->Cell(30, 5, '-Rs. ' . number_format($order['discount'], 2), 0, 1, 'R');
    }

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(42, 7, 'TOTAL DUE:', 0, 0, 'R');
    $pdf->Cell(30, 7, 'Rs. ' . number_format($order['grand_total'], 2), 0, 1, 'R');

    $pdf->SetFont('Arial', '', 9.5);
    $pdf->Cell(42, 5, 'Paid (' . $order['payment_method'] . '):', 0, 0, 'R');
    $pdf->Cell(30, 5, 'Rs. ' . number_format($order['paid_amount'], 2), 0, 1, 'R');

    $pdf->Cell(42, 5, 'Change:', 0, 0, 'R');
    $pdf->Cell(30, 5, 'Rs. ' . number_format($order['change_amount'], 2), 0, 1, 'R');

    $pdf->Cell(72, 3, '---------------------------------------------------------', 0, 1, 'C');

    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Cell(72, 5, 'Thank you for dining with us!', 0, 1, 'C');
    $pdf->Cell(72, 5, 'Please visit again soon', 0, 1, 'C');

    $filename = 'Receipt_' . $order['order_number'] . '.pdf';
    $pdf->Output($download ? 'D' : 'I', $filename);
    exit();
}

// -------------------------------------------------------------
// 10. FORMAT: RESTAURANT TABLES & FLOOR DIRECTORY (A4)
// -------------------------------------------------------------
if ($format === 'tables') {
    $floors = [];
    $tables = [];
    if ($conn) {
        $resF = $conn->query("SELECT * FROM `restaurant_floors` ORDER BY id ASC");
        if ($resF) {
            while ($rf = $resF->fetch_assoc()) $floors[] = $rf;
        }
        $resT = $conn->query("SELECT * FROM `restaurant_tables` ORDER BY floor_id ASC, table_number ASC");
        if ($resT) {
            while ($rt = $resT->fetch_assoc()) $tables[] = $rt;
        }
    }

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();

    // Header
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor(79, 70, 229);
    $pdf->Cell(110, 8, 'RMS RESTAURANT', 0, 0, 'L');

    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(70, 8, 'FLOOR PLAN & TABLES', 0, 1, 'R');

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(110, 5, 'Dining Floor & Seating Layout Directory', 0, 0, 'L');
    $pdf->Cell(70, 5, 'Date: ' . date('M d, Y h:i A'), 0, 1, 'R');

    $pdf->SetDrawColor(226, 232, 240);
    $pdf->SetLineWidth(0.4);
    $pdf->Line(15, 30, 195, 30);
    $pdf->Ln(6);

    // Summary Statistics
    $totalSeats = 0;
    $availCount = 0;
    $occCount = 0;
    foreach ($tables as $t) {
        $totalSeats += intval($t['capacity']);
        if ($t['status'] === 'Available') $availCount++;
        elseif ($t['status'] === 'Occupied') $occCount++;
    }

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(36, 7, 'Total Floors: ' . count($floors), 1, 0, 'C', true);
    $pdf->Cell(36, 7, 'Total Tables: ' . count($tables), 1, 0, 'C', true);
    $pdf->Cell(36, 7, 'Total Seats: ' . $totalSeats, 1, 0, 'C', true);
    $pdf->Cell(36, 7, 'Available: ' . $availCount, 1, 0, 'C', true);
    $pdf->Cell(36, 7, 'Occupied: ' . $occCount, 1, 1, 'C', true);
    $pdf->Ln(4);

    // Group tables by floor
    foreach ($floors as $fl) {
        $floorTables = array_filter($tables, function($tb) use ($fl) {
            return $tb['floor_id'] == $fl['id'];
        });

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(79, 70, 229);
        $pdf->Cell(0, 7, strtoupper($fl['floor_name']) . (!empty($fl['floor_code']) ? " (" . $fl['floor_code'] . ")" : "") . " - " . count($floorTables) . " Tables", 0, 1, 'L');

        // Table Header
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(248, 250, 252);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(15, 7, '#', 1, 0, 'C', true);
        $pdf->Cell(65, 7, 'Table / Cabin Name', 1, 0, 'L', true);
        $pdf->Cell(45, 7, 'Guest Capacity', 1, 0, 'C', true);
        $pdf->Cell(55, 7, 'Occupancy Status', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(30, 41, 59);

        if (empty($floorTables)) {
            $pdf->Cell(180, 6, 'No tables configured on this floor', 1, 1, 'C');
        } else {
            $idx = 1;
            foreach ($floorTables as $tb) {
                $pdf->Cell(15, 6, $idx++, 1, 0, 'C');
                $pdf->Cell(65, 6, '  ' . $tb['table_number'], 1, 0, 'L');
                $pdf->Cell(45, 6, $tb['capacity'] . ' Guests', 1, 0, 'C');
                $pdf->Cell(55, 6, $tb['status'], 1, 1, 'C');
            }
        }
        $pdf->Ln(4);
    }

    $pdf->SetY(-15);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(148, 163, 184);
    $pdf->Cell(0, 5, 'RMS Restaurant Management System - Official Floor Plan & Seating Roster', 0, 1, 'C');

    $pdf->Output($download ? 'D' : 'I', 'RMS_Floor_Plan_' . date('Ymd') . '.pdf');
    exit();
}

// FORMAT 8B: STANDARD A4 TAX INVOICE
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 20);
$pdf->SetTextColor(79, 70, 229);
$pdf->Cell(110, 10, 'RMS RESTAURANT', 0, 0, 'L');

$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(30, 41, 59);
$pdf->Cell(70, 10, 'TAX INVOICE', 0, 1, 'R');

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(110, 5, '123 Gourmet Boulevard, Food District', 0, 0, 'L');
$pdf->Cell(70, 5, 'Invoice #: ' . $order['order_number'], 0, 1, 'R');

$pdf->Cell(110, 5, 'Phone: +1 (555) 890-1234 | contact@rms-restaurant.com', 0, 0, 'L');
$pdf->Cell(70, 5, 'Date: ' . date('F d, Y h:i A', strtotime($order['created_at'])), 0, 1, 'R');

$pdf->Ln(6);
$pdf->SetDrawColor(226, 232, 240);
$pdf->SetLineWidth(0.4);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(6);

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(95, 6, 'BILL TO:', 0, 0, 'L');
$pdf->Cell(85, 6, 'ORDER DETAILS:', 0, 1, 'L');

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(71, 85, 105);
$pdf->Cell(95, 5, 'Customer: ' . $order['customer_name'], 0, 0, 'L');
$pdf->Cell(85, 5, 'Order Type: ' . $order['order_type'], 0, 1, 'L');

$pdf->Cell(95, 5, 'Phone: ' . ($order['customer_phone'] ?? 'N/A'), 0, 0, 'L');
$stationText = (!empty($order['floor_name']) ? $order['floor_name'] . ' • ' : '') . ($order['table_no'] ?? 'Counter / Takeout');
$pdf->Cell(85, 5, 'Table / Station: ' . $stationText, 0, 1, 'L');

$pdf->Cell(95, 5, 'Payment Method: ' . $order['payment_method'], 0, 0, 'L');
$pdf->Cell(85, 5, 'Order Status: Completed', 0, 1, 'L');

$pdf->Ln(8);

$pdf->SetFillColor(241, 245, 249);
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(30, 41, 59);
$pdf->Cell(15, 8, '#', 1, 0, 'C', true);
$pdf->Cell(95, 8, 'Item Description', 1, 0, 'L', true);
$pdf->Cell(25, 8, 'Qty', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Unit Price', 1, 0, 'R', true);
$pdf->Cell(20, 8, 'Total', 1, 1, 'R', true);

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(51, 65, 85);
$sn = 1;
foreach ($items as $item) {
    $pdf->Cell(15, 7, $sn++, 1, 0, 'C');
    $pdf->Cell(95, 7, $item['item_name'], 1, 0, 'L');
    $pdf->Cell(25, 7, $item['quantity'], 1, 0, 'C');
    $pdf->Cell(25, 7, 'Rs. ' . number_format($item['price'], 2), 1, 0, 'R');
    $pdf->Cell(20, 7, 'Rs. ' . number_format($item['total'], 2), 1, 1, 'R');
}

$pdf->Ln(4);

$pdf->SetX(115);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(45, 6, 'Subtotal:', 0, 0, 'R');
$pdf->Cell(35, 6, 'Rs. ' . number_format($order['subtotal'], 2), 0, 1, 'R');

if ($order['tax'] > 0) {
    $pdf->SetX(115);
    $pdf->Cell(45, 6, 'Tax / GST:', 0, 0, 'R');
    $pdf->Cell(35, 6, 'Rs. ' . number_format($order['tax'], 2), 0, 1, 'R');
}

if ($order['discount'] > 0) {
    $pdf->SetX(115);
    $pdf->Cell(45, 6, 'Discount:', 0, 0, 'R');
    $pdf->Cell(35, 6, '-Rs. ' . number_format($order['discount'], 2), 0, 1, 'R');
}

$pdf->SetX(115);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(79, 70, 229);
$pdf->Cell(45, 8, 'Grand Total:', 0, 0, 'R');
$pdf->Cell(35, 8, 'Rs. ' . number_format($order['grand_total'], 2), 0, 1, 'R');

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(100, 116, 139);
$pdf->SetX(115);
$pdf->Cell(45, 6, 'Amount Tendered:', 0, 0, 'R');
$pdf->Cell(35, 6, 'Rs. ' . number_format($order['paid_amount'], 2), 0, 1, 'R');

$pdf->SetX(115);
$pdf->Cell(45, 6, 'Change Returned:', 0, 0, 'R');
$pdf->Cell(35, 6, 'Rs. ' . number_format($order['change_amount'], 2), 0, 1, 'R');

$pdf->SetY(260);
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(148, 163, 184);
$pdf->Cell(0, 4, 'Thank you for choosing RMS Restaurant. Generated automatically by RMS POS System.', 0, 1, 'C');

$filename = 'Invoice_' . $order['order_number'] . '.pdf';
$pdf->Output($download ? 'D' : 'I', $filename);
exit();
