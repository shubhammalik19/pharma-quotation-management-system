<?php
require '../conn.php';
header('Content-Type: application/json');

$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10; $offset = ($page-1)*$limit;

$where = '';
if ($search !== '') {
  $s = $conn->real_escape_string($search);
  $where = "WHERE so.so_number LIKE '%$s%' OR so.customer_name LIKE '%$s%' OR so.quotation_number LIKE '%$s%' OR so.status LIKE '%$s%'";
}

$count = $conn->query("SELECT COUNT(*) AS tot FROM sales_orders so $where");
$total = ($count && $row = $count->fetch_assoc()) ? (int)$row['tot'] : 0;

$sql = "SELECT so.id, so.so_number, so.customer_name, so.customer_id, so.so_date, so.delivery_date,
               so.status, so.quotation_id, so.quotation_number
        FROM sales_orders so
        $where
        ORDER BY so.id DESC
        LIMIT $limit OFFSET $offset";
$res = $conn->query($sql);

$items = [];
while($r = $res->fetch_assoc()) {
  $r['so_date_formatted'] = date('d/m/Y', strtotime($r['so_date']));
  $items[] = $r;
}

echo json_encode([
  'success'=>true,
  'sales_orders'=>$items,
  'pagination'=>[
    'total_records'=>$total,
    'total_pages'=>ceil($total/$limit),
    'current_page'=>$page
  ],
  'search_term'=>$search
]);
