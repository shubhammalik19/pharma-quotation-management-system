<?php

require '../common/conn.php';
require '../common/functions.php';

header('Content-Type: application/json');
$so_id = intval($_GET['so_id'] ?? 0);
if (!$so_id) { echo json_encode(['success'=>false,'message'=>'Missing so_id']); exit; }

$items = [];
$r = $conn->query("SELECT item_type, item_id, item_name, description, hsn_code, quantity, unit_price, total_price, sl_no, unit, rate, gst_rate, amount
                   FROM sales_order_items WHERE so_id = $so_id ORDER BY sl_no,id");
while($row = $r->fetch_assoc()){ $items[] = $row; }

echo json_encode(['success'=>true, 'items'=>$items]);
