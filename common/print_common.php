<?php
/**
 * Common functions and styling for all print documents
 * This file contains shared functionality to make print documents DRY
 */

/**
 * Generic logging function for all document types
 * @param string $document_type - Type of document (quotation, sales_order, invoice, etc.)
 * @param string $message - Log message
 * @param int|null $document_id - ID of the document
 */
function logDocumentActivity($document_type, $message, $document_id = null) {
    $log_dir = __DIR__ . '/../storage/logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/' . $document_type . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $user_id = $_SESSION['user_id'] ?? 'guest';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $doc_id_str = $document_id ? $document_id : 'N/A';
    
    $log_entry = "[{$timestamp}] User: {$user_id} | IP: {$ip} | Doc ID: {$doc_id_str} | {$message}" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Generate common CSS styles for all print documents
 * @return string CSS styles
 */
function getPrintStyles() {
    return '
body{font:13px/1.45 "DejaVu Sans", Arial, sans-serif; color:#222; margin:0}
.wrapper{max-width:800px;margin:0 auto;padding:10mm}
h1{font-size:20px;margin:0 0 3mm}
h2{font-size:15px;margin:6mm 0 3mm;color:#0f6abf}
small{color:#666}
table{width:100%;border-collapse:collapse}
td,th{padding:6px 6px;vertical-align:top}
.kv td{border:1px solid #ddd}
.kv td:first-child{background:#f8f8f8;width:130px;font-weight:bold}
.items thead th{border-bottom:2px solid #0f6abf;background:#eef6ff;font-size:12px;text-align:left}
.items td{border-bottom:1px solid #e7e7e7}
.num{text-align:right;white-space:nowrap}
.note{font-size:12px;color:#555}
.footer{font-size:12px;border-top:1px solid #444;margin-top:6mm;padding-top:2mm}
.logo-cell{text-align:center;padding:10px;width:140px}
.company-logo{max-width:120px;max-height:90px;width:auto;height:auto;display:block;margin:0 auto;border:1px solid #e0e0e0;border-radius:4px;padding:8px;background:#fff;box-shadow:0 2px 4px rgba(0,0,0,0.1)}
.header-content{padding-left:15px}
@page { size:A4; margin:12mm }
thead{display:table-header-group}
tfoot{display:table-row-group}
tr{page-break-inside:avoid}
@media print{.no-print{display:none!important}.wrapper{max-width:100%;padding:0}}
';
}

/**
 * Generate common header with logo for all documents
 * @param array $company Company details
 * @param string $document_title Document title (e.g., "QUOTATION", "SALES ORDER")
 * @param string $document_number Document number
 * @param string $status Document status
 * @return string HTML header
 */
function getPrintHeader($company, $document_title, $document_number, $status = '') {
    $company_name = htmlspecialchars($company['name'] ?? '');
    $company_tagline = htmlspecialchars($company['tagline'] ?? 'Professional Business Solutions');
    $doc_title = htmlspecialchars($document_title);
    $doc_number = htmlspecialchars($document_number);
    $doc_status = $status ? htmlspecialchars(ucfirst($status)) : 'Draft';
    
    return '
    <!-- Header with Logo -->
    <table style="border-bottom:3px solid #0f6abf;margin-bottom:8px">
        <tr>
            <td class="logo-cell" valign="middle">
                <img src="../img/logo.png" alt="Company Logo" class="company-logo">
            </td>
            <td valign="middle" class="header-content">
                <h1 style="margin-bottom:5px;">' . $company_name . ' — ' . $doc_number . '</h1>
                <div style="color:#666;font-style:italic;"><small>' . $company_tagline . '</small></div>
            </td>
            <td width="180" align="right" valign="middle">
                <div style="font-size:22px;font-weight:800;color:#0f6abf;letter-spacing:.2px;margin-bottom:4px;">' . $doc_title . '</div>
                <div style="font-size:13px;color:#555;background:#f0f8ff;padding:4px 8px;border-radius:4px;display:inline-block;">' . $doc_status . '</div>
            </td>
        </tr>
    </table>';
}

/**
 * Generate navigation buttons for non-PDF mode
 * @param string $back_url Back button URL
 * @param int $document_id Document ID for PDF link
 * @return string HTML navigation buttons
 */
function getPrintNavigation($back_url, $document_id) {
    $back_url = htmlspecialchars($back_url);
    $doc_id = (int)$document_id;
    
    return '
    <div class="no-print" style="position:fixed;top:16px;right:16px;display:flex;gap:8px;z-index:1000">
        <a href="' . $back_url . '" style="background:#6c757d;color:#fff;text-decoration:none;padding:8px 12px;border-radius:6px">← Back</a>
        <a href="?id=' . $doc_id . '&pdf=1" target="_blank" style="background:#0f6abf;color:#fff;text-decoration:none;padding:8px 12px;border-radius:6px">Open PDF</a>
        <button onclick="window.print()" style="background:#0f766e;color:#fff;border:0;padding:8px 12px;border-radius:6px;cursor:pointer">🖨️ Print</button>
    </div>';
}

/**
 * Generate company information section
 * @param array $company Company details
 * @return string HTML company info
 */
function getCompanyInfoSection($company) {
    return '
    <h2>Company Information</h2>
    <table class="kv">
        <tr><td>Corporate Office</td><td>' . htmlspecialchars($company['corporate_office'] ?? $company['address'] ?? '') . '</td></tr>
        <tr><td>Mfg. Unit</td><td>' . htmlspecialchars($company['manufacturing_unit'] ?? '') . '</td></tr>
        <tr><td>CIN</td><td>' . htmlspecialchars($company['cin'] ?? '') . '</td></tr>
        <tr><td>GST</td><td>' . htmlspecialchars($company['gst'] ?? ($company['gstin'] ?? '')) . '</td></tr>
        <tr><td>Contact</td><td>' . htmlspecialchars($company['contact'] ?? (($company['phone'] ?? '') . ((!empty($company['email'])?' · '.$company['email']:'')))) . '</td></tr>
    </table>';
}

/**
 * Generate footer section
 * @param array $company Company details
 * @return string HTML footer
 */
function getPrintFooter($company) {
    $footer_parts = [];
    if (!empty($company['name'])) $footer_parts[] = strtoupper($company['name']);
    if (!empty($company['address'])) $footer_parts[] = $company['address'];
    if (!empty($company['cin'])) $footer_parts[] = 'CIN ' . $company['cin'];
    if (!empty($company['gstin']) || !empty($company['gst'])) {
        $footer_parts[] = 'GST ' . ($company['gstin'] ?? $company['gst']);
    }
    if (!empty($company['phone'])) $footer_parts[] = $company['phone'];
    if (!empty($company['email'])) $footer_parts[] = $company['email'];
    
    return '
    <div class="footer">
        ' . htmlspecialchars(implode(' · ', $footer_parts)) . '
    </div>';
}

/**
 * Generate standard terms and conditions
 * @param string $document_type Type of document to customize terms
 * @return string HTML terms and conditions
 */
function getTermsAndConditions($document_type = 'general') {
    $terms = [
        'quotation' => [
            'Payment Terms' => '30% advance with purchase order; 50% against material readiness; 20% after successful installation & commissioning. Payment via RTGS/NEFT only.',
            'Delivery & Installation' => 'Delivery in 16–20 weeks from order confirmation; installation & training included; standard warranty 12 months from commissioning.',
            'Technical Support' => 'Engineering drawings; O&M manual; 24/7 technical support helpline; AMC available.',
            'Exclusions' => 'Civil foundation; electrical up to panel; utilities (air/steam/water); statutory approvals/NOCs.'
        ],
        'sales_order' => [
            'Payment' => '30% advance, 50% on readiness, 20% after commissioning. Payments via RTGS/NEFT.',
            'Delivery' => 'As per agreed schedule. Includes installation & training. 1 year warranty.',
            'Jurisdiction' => 'All disputes subject to local jurisdiction.'
        ],
        'general' => [
            'Payment Terms' => '30% advance, balance before dispatch. Payment via RTGS/NEFT only.',
            'Delivery' => 'As per agreed schedule. Standard warranty applies.',
            'Terms' => 'All disputes subject to local jurisdiction.'
        ]
    ];
    
    $selected_terms = $terms[$document_type] ?? $terms['general'];
    
    $html = '<h2>Terms & Conditions</h2><table class="kv">';
    foreach ($selected_terms as $title => $content) {
        $html .= '<tr><td>' . htmlspecialchars($title) . '</td><td>' . htmlspecialchars($content) . '</td></tr>';
    }
    $html .= '</table>';
    
    if ($document_type === 'quotation') {
        $html .= '<div class="note" style="margin-top:3mm">This offer is valid for 30 days from the date of quotation. Prices are subject to change without notice.</div>';
    }
    
    return $html;
}

/**
 * Helper function for HTML escaping
 */
if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Helper function for money formatting
 */
if (!function_exists('fmt_money')) {
    function fmt_money($amount) {
        return '₹ ' . number_format((float)$amount, 2);
    }
}

/**
 * Convert number to words for Indian currency
 */
function numberToWords($num) {
    $num = (int)floor((float)$num);
    $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
    $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    
    $fmt = function($n) use($ones, $tens) {
        $s = '';
        if ($n >= 100) {
            $s .= $ones[intdiv($n, 100)] . ' Hundred';
            $n %= 100;
            if ($n) $s .= ' ';
        }
        if ($n >= 20) {
            $s .= $tens[intdiv($n, 10)];
            $n %= 10;
            if ($n) $s .= ' ' . $ones[$n];
        } elseif ($n > 0) {
            $s .= $ones[$n];
        }
        return $s;
    };
    
    if ($num === 0) return 'Zero';
    
    $parts = [];
    $crore = intdiv($num, 10000000);
    if ($crore) {
        $parts[] = $fmt($crore) . ' Crore';
        $num %= 10000000;
    }
    $lakh = intdiv($num, 100000);
    if ($lakh) {
        $parts[] = $fmt($lakh) . ' Lakh';
        $num %= 100000;
    }
    $thou = intdiv($num, 1000);
    if ($thou) {
        $parts[] = $fmt($thou) . ' Thousand';
        $num %= 1000;
    }
    $hund = $fmt($num);
    if ($hund) {
        $parts[] = $hund;
    }
    
    return trim(implode(' ', $parts));
}
