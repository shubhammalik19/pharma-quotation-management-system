<?php
include '../common/conn.php';
include '../common/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['type']) || !isset($_GET['term'])) {
    echo json_encode([]);
    exit;
}

$type = sanitizeInput($_GET['type']);
$term = sanitizeInput($_GET['term']);
$results = [];

try {
    switch ($type) {
        case 'AUTOCOMPLETE_CUSTOMERS':
            $searchTerm = "%" . $conn->real_escape_string($term) . "%";
            $sql = "SELECT id, company_name, contact_person, phone, email, city, state, entity_type 
                    FROM customers 
                    WHERE  (company_name LIKE '$searchTerm' OR contact_person LIKE '$searchTerm')
                    ORDER BY company_name ASC 
                    LIMIT 10";
                    
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $label = $row['company_name'];
                    if (!empty($row['contact_person'])) {
                        $label .= " (" . $row['contact_person'] . ")";
                    }
                    if (!empty($row['city'])) {
                        $label .= " - " . $row['city'];
                    }
                    $results[] = [
                        'id' => $row['id'],
                        'label' => $label,
                        'value' => $row['company_name'],
                        'data' => $row
                    ];
                }
            }
            break;

        case 'AUTOCOMPLETE_VENDORS':
            $searchTerm = "%" . $conn->real_escape_string($term) . "%";
            $sql = "SELECT id, company_name, contact_person, phone, email, city, state, entity_type 
                    FROM customers 
                    WHERE (entity_type = 'vendor' OR entity_type = 'both') 
                    AND (company_name LIKE '$searchTerm' OR contact_person LIKE '$searchTerm')
                    ORDER BY company_name ASC 
                    LIMIT 10";
                    
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $label = $row['company_name'];
                    if (!empty($row['contact_person'])) {
                        $label .= " (" . $row['contact_person'] . ")";
                    }
                    if (!empty($row['city'])) {
                        $label .= " - " . $row['city'];
                    }
                    $results[] = [
                        'id' => $row['id'],
                        'label' => $label,
                        'value' => $row['company_name'],
                        'data' => $row
                    ];
                }
            }
            break;

        case 'AUTOCOMPLETE_MACHINES':
            $searchTerm = "%" . $conn->real_escape_string($term) . "%";
            $sql = "SELECT m.id, m.name, m.model, m.category, m.part_code, pm.price 
                    FROM machines m 
                    LEFT JOIN price_master pm ON m.id = pm.machine_id AND pm.is_active = 1
                    WHERE (m.name LIKE '$searchTerm' OR m.model LIKE '$searchTerm' OR m.category LIKE '$searchTerm' OR m.part_code LIKE '$searchTerm') 
                    AND m.is_active = 1
                    ORDER BY m.name ASC 
                    LIMIT 10";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $label = $row['name'];
                    if (!empty($row['model'])) {
                        $label .= " (" . $row['model'] . ")";
                    }
                    if (!empty($row['category'])) {
                        $label .= " - " . $row['category'];
                    }
                    if ($row['price'] > 0) {
                        $label .= " - ₹" . number_format($row['price'], 2);
                    }
                    $results[] = [
                        'id' => $row['id'],
                        'label' => $label,
                        'value' => $row['name'],
                        'data' => $row
                    ];
                }
            }
            break;

        case 'AUTOCOMPLETE_SPARES':
            $searchTerm = "%" . $conn->real_escape_string($term) . "%";
            $sql = "SELECT s.id, s.part_name, s.part_code, s.price, m.name as machine_name 
                    FROM spares s 
                    LEFT JOIN machines m ON s.machine_id = m.id
                    WHERE (s.part_name LIKE '$searchTerm' OR s.part_code LIKE '$searchTerm' OR s.description LIKE '$searchTerm' OR m.name LIKE '$searchTerm') 
                    AND s.is_active = 1
                    ORDER BY s.part_name ASC 
                    LIMIT 10";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $label = $row['part_name'];
                    if (!empty($row['part_code'])) {
                        $label .= " (" . $row['part_code'] . ")";
                    }
                    if (!empty($row['machine_name'])) {
                        $label .= " - " . $row['machine_name'];
                    }
                    if ($row['price'] > 0) {
                        $label .= " - ₹" . number_format($row['price'], 2);
                    }
                    $results[] = [
                        'id' => $row['id'],
                        'label' => $label,
                        'value' => $row['part_name'],
                        'data' => $row
                    ];
                }
            }
            break;

        case 'AUTOCOMPLETE_CITIES':
            $searchTerm = "%" . $conn->real_escape_string($term) . "%";
            $sql = "SELECT DISTINCT city 
                    FROM customers 
                    WHERE city LIKE '$searchTerm' AND city IS NOT NULL AND city != ''
                    ORDER BY city ASC 
                    LIMIT 10";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $results[] = [
                        'label' => $row['city'],
                        'value' => $row['city']
                    ];
                }
            }
            // Add common Indian cities if no results found
            if (empty($results) && strlen($term) >= 2) {
                $commonCities = [
                    'Mumbai', 'Delhi', 'Bangalore', 'Hyderabad', 'Ahmedabad', 'Chennai', 'Kolkata', 
                    'Surat', 'Pune', 'Jaipur', 'Lucknow', 'Kanpur', 'Nagpur', 'Indore', 'Thane',
                    'Bhopal', 'Visakhapatnam', 'Pimpri-Chinchwad', 'Patna', 'Vadodara', 'Ghaziabad',
                    'Ludhiana', 'Agra', 'Nashik', 'Faridabad', 'Meerut', 'Rajkot', 'Kalyan-Dombivali',
                    'Vasai-Virar', 'Varanasi', 'Srinagar', 'Aurangabad', 'Dhanbad', 'Amritsar'
                ];
                foreach ($commonCities as $city) {
                    if (stripos($city, $term) !== false) {
                        $results[] = [
                            'label' => $city,
                            'value' => $city
                        ];
                    }
                }
            }
            break;

        case 'AUTOCOMPLETE_STATES':
            $searchTerm = "%" . $conn->real_escape_string($term) . "%";
            $sql = "SELECT DISTINCT state 
                    FROM customers 
                    WHERE state LIKE '$searchTerm' AND state IS NOT NULL AND state != ''
                    ORDER BY state ASC 
                    LIMIT 10";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $results[] = [
                        'label' => $row['state'],
                        'value' => $row['state']
                    ];
                }
            }
            // Add Indian states if no results found
            if (empty($results) && strlen($term) >= 2) {
                $indianStates = [
                    'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa',
                    'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala',
                    'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland',
                    'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura',
                    'Uttar Pradesh', 'Uttarakhand', 'West Bengal', 'Delhi', 'Puducherry',
                    'Chandigarh', 'Dadra and Nagar Haveli', 'Daman and Diu', 'Lakshadweep',
                    'Ladakh', 'Jammu and Kashmir', 'Andaman and Nicobar Islands'
                ];
                foreach ($indianStates as $state) {
                    if (stripos($state, $term) !== false) {
                        $results[] = [
                            'label' => $state,
                            'value' => $state
                        ];
                    }
                }
            }
            break;

        case 'AUTOCOMPLETE_CATEGORIES':
            $searchTerm = "%" . $conn->real_escape_string($term) . "%";
            $sql = "SELECT DISTINCT category 
                    FROM machines 
                    WHERE category LIKE '$searchTerm' AND category IS NOT NULL AND category != ''
                    ORDER BY category ASC 
                    LIMIT 10";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $results[] = [
                        'label' => $row['category'],
                        'value' => $row['category']
                    ];
                }
            }
            // Add common categories if no results found
            if (empty($results) && strlen($term) >= 2) {
                $commonCategories = [
                    'Granulation Equipment', 'Drying Equipment', 'Blending Equipment', 
                    'Sieving Equipment', 'Size Reduction', 'Material Handling',
                    'Coating Equipment', 'Compression Equipment', 'Filling Equipment',
                    'Packaging Equipment', 'Cleaning Equipment', 'Testing Equipment',
                    'Mixing Equipment', 'Tablet Equipment', 'Capsule Equipment'
                ];
                foreach ($commonCategories as $category) {
                    if (stripos($category, $term) !== false) {
                        $results[] = [
                            'label' => $category,
                            'value' => $category
                        ];
                    }
                }
            }
            break;

        case 'AUTOCOMPLETE_PO':
            $searchTerm = "%" . $conn->real_escape_string($term) . "%";
            $sql = "SELECT id, po_number, vendor_name, po_date, status, final_total
                    FROM purchase_orders 
                    WHERE po_number LIKE '$searchTerm' OR vendor_name LIKE '$searchTerm'
                    ORDER BY created_at DESC 
                    LIMIT 10";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $label = $row['po_number'] . " - " . $row['vendor_name'];
                    if (!empty($row['final_total'])) {
                        $label .= " (₹" . number_format($row['final_total'], 2) . ")";
                    }
                    $results[] = [
                        'id' => $row['id'],
                        'label' => $label,
                        'value' => $row['po_number'],
                        'data' => $row
                    ];
                }
            }
            break;

        case 'AUTOCOMPLETE_SO_NUMBERS':
            $searchTerm = "%" . $conn->real_escape_string($term) . "%";
            $sql = "SELECT id, so_number, customer_name, customer_id, so_date, delivery_date, status, notes, total_amount
                    FROM sales_orders 
                    WHERE so_number LIKE '$searchTerm' OR customer_name LIKE '$searchTerm'
                    ORDER BY created_at DESC 
                    LIMIT 10";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $label = $row['so_number'] . " - " . $row['customer_name'];
                    if (!empty($row['total_amount'])) {
                        $label .= " (₹" . number_format($row['total_amount'], 2) . ")";
                    }
                    $results[] = [
                        'id' => $row['id'],
                        'label' => $label,
                        'value' => $row['so_number'],
                        'data' => $row
                    ];
                }
            }
            break;

        case 'AUTOCOMPLETE_QUOTATIONS':
            $searchTerm = "%" . $conn->real_escape_string($term) . "%";
            $sql = "SELECT q.id, q.quotation_number, q.quotation_date, q.valid_until, q.total_amount, 
                           q.grand_total, q.discount_percentage, q.discount_amount, q.status, 
                           q.enquiry_ref, q.prepared_by, q.notes, q.customer_id, c.company_name as customer_name
                    FROM quotations q 
                    LEFT JOIN customers c ON q.customer_id = c.id
                    WHERE q.quotation_number LIKE '$searchTerm' 
                       OR c.company_name LIKE '$searchTerm'
                    ORDER BY q.created_at DESC 
                    LIMIT 10";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $amount = $row['grand_total'] ?: $row['total_amount'];
                    $label = $row['quotation_number'];
                    if ($row['customer_name']) {
                        $label .= ' - ' . $row['customer_name'];
                    }
                    if ($amount > 0) {
                        $label .= " (₹" . number_format($amount, 2) . ")";
                    }
                    $results[] = [
                        'id' => $row['id'],
                        'label' => $label,
                        'value' => $row['quotation_number'],
                        'data' => [
                            'id' => $row['id'],
                            'quotation_number' => $row['quotation_number'],
                            'customer_name' => $row['customer_name'] ?: '',
                            'customer_id' => $row['customer_id'],
                            'quotation_date' => $row['quotation_date'],
                            'valid_until' => $row['valid_until'],
                            'total_amount' => $row['total_amount'],
                            'grand_total' => $amount,
                            'discount_percentage' => $row['discount_percentage'] ?: 0,
                            'discount_amount' => $row['discount_amount'] ?: 0,
                            'status' => $row['status'],
                            'enquiry_ref' => $row['enquiry_ref'] ?: '',
                            'prepared_by' => $row['prepared_by'] ?: 'Sales Department',
                            'notes' => $row['notes'] ?: ''
                        ]
                    ];
                }
            }
            break;
          case 'AUTOCOMPLETE_PO_NUMBERS':
                $searchTerm = "%" . $conn->real_escape_string($term) . "%";
                $sql = "SELECT po.id, po.po_number as value, po.po_number as label, 
                            po.vendor_name, po.final_total, po.status
                        FROM purchase_orders po 
                        WHERE po.po_number LIKE '$searchTerm' 
                        ORDER BY po.created_at DESC 
                        LIMIT 15";
                
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $results[] = [
                            'id' => $row['id'],
                            'value' => $row['value'],
                            'label' => $row['label'] . ' - ' . $row['vendor_name'] . ' (₹' . number_format($row['final_total'], 2) . ')',
                            'data' => $row
                        ];
                    }
                }
                break;

            // For Sales Invoice autocomplete search
            case 'AUTOCOMPLETE_INVOICES':
                $searchTerm = "%" . $conn->real_escape_string($term) . "%";
                $sql = "SELECT si.id, si.invoice_number as value, si.invoice_number as label,
                            si.customer_name, si.total_amount, si.status
                        FROM sales_invoices si 
                        WHERE si.invoice_number LIKE '$searchTerm' 
                        OR si.customer_name LIKE '$searchTerm'
                        ORDER BY si.created_at DESC 
                        LIMIT 15";
                
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $results[] = [
                            'id' => $row['id'],
                            'value' => $row['value'],
                            'label' => $row['label'] . ' - ' . $row['customer_name'] . ' (₹' . number_format($row['total_amount'], 2) . ')',
                            'data' => $row
                        ];
                    }
                }
                break;

            // For Sales Orders autocomplete search (general)
            case 'AUTOCOMPLETE_SO':
                $searchTerm = "%" . $conn->real_escape_string($term) . "%";
                $sql = "SELECT so.id, so.so_number as value, so.so_number as label,
                            so.customer_name, so.total_amount, so.status
                        FROM sales_orders so 
                        WHERE so.so_number LIKE '$searchTerm' 
                        OR so.customer_name LIKE '$searchTerm'
                        ORDER BY so.created_at DESC 
                        LIMIT 15";
                
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $results[] = [
                            'id' => $row['id'],
                            'value' => $row['value'],
                            'label' => $row['label'] . ' - ' . $row['customer_name'] . ' (₹' . number_format($row['total_amount'], 2) . ')',
                            'data' => $row
                        ];
                    }
                }
                break;

            // For Credit Notes autocomplete search
            case 'AUTOCOMPLETE_CREDIT_NOTES':
                $searchTerm = "%" . $conn->real_escape_string($term) . "%";
                $sql = "SELECT cn.id, cn.credit_note_number as value, cn.credit_note_number as label,
                            cn.customer_name, cn.total_amount, cn.status
                        FROM credit_notes cn 
                        WHERE cn.credit_note_number LIKE '$searchTerm' 
                        OR cn.customer_name LIKE '$searchTerm'
                        ORDER BY cn.created_at DESC 
                        LIMIT 15";
                
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $results[] = [
                            'id' => $row['id'],
                            'value' => $row['value'],
                            'label' => $row['label'] . ' - ' . $row['customer_name'] . ' (₹' . number_format($row['total_amount'], 2) . ')',
                            'data' => $row
                        ];
                    }
                }
                break;

            case 'AUTOCOMPLETE_DEBIT_NOTES':
                $searchTerm = "%" . $conn->real_escape_string($term) . "%";
                $sql = "SELECT dn.id, dn.debit_note_number as value, dn.debit_note_number as label,
                            dn.vendor_name, dn.total_amount, dn.status
                        FROM debit_notes dn 
                        WHERE dn.debit_note_number LIKE '$searchTerm' 
                        OR dn.vendor_name LIKE '$searchTerm'
                        ORDER BY dn.created_at DESC 
                        LIMIT 15";
                
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $results[] = [
                            'id' => $row['id'],
                            'value' => $row['value'],
                            'label' => $row['label'] . ' - ' . $row['vendor_name'] . ' (₹' . number_format($row['total_amount'], 2) . ')',
                            'data' => $row
                        ];
                    }
                }
                break;

        default:
            $results = [];
            break;
    }

    echo json_encode($results);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>
