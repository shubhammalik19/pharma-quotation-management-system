<?php

require '../common/conn.php';
require '../common/functions.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['success'=>false,'message'=>'Missing id']); exit; }

$q = $conn->query("SELECT q.id, q.quotation_number, q.customer_id, c.company_name as customer_name,
                          q.total_amount, q.discount_percentage, q.discount_amount, q.grand_total
                   FROM quotations q 
                   LEFT JOIN customers c ON c.id = q.customer_id
                   WHERE q.id = $id");
if (!$q || $q->num_rows==0) { echo json_encode(['success'=>false,'message'=>'Quotation not found']); exit; }
$quotation = $q->fetch_assoc();

$items = [];
$qi = $conn->query("SELECT item_type, item_id, quantity, unit_price as unit_price,
                           total_price as total_price, sl_no, description
                    FROM quotation_items WHERE quotation_id = $id ORDER BY sl_no,id");
while($row = $qi->fetch_assoc()){
  // attempt name joins (optional)
  $name = '';
  if ($row['item_type']=='machine'){
    $m = $conn->query("SELECT name FROM machines WHERE id = ".$row['item_id']);
    if ($m && $m->num_rows) { $name = $m->fetch_assoc()['name']; }
  } else {
    $s = $conn->query("SELECT part_name FROM spares WHERE id = ".$row['item_id']);
    if ($s && $s->num_rows) { $name = $s->fetch_assoc()['part_name']; }
  }
  $row['name'] = $name;
  $items[] = $row;
}

echo json_encode(['success'=>true,'quotation'=>$quotation,'items'=>$items]);
