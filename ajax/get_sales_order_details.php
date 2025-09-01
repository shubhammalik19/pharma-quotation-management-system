<?php
include '../common/conn.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['success'=>false,'message'=>'Missing id']); exit; }

$r = $conn->query("SELECT so.*, c.email as customer_email
                   FROM sales_orders so
                   LEFT JOIN customers c ON c.id = so.customer_id
                   WHERE so.id = $id");
if (!$r || $r->num_rows==0) { echo json_encode(['success'=>false,'message'=>'SO not found']); exit; }

$so = $r->fetch_assoc();

$items_r = $conn->query("SELECT * FROM sales_order_items WHERE so_id = $id");
$items = [];
while ($row = $items_r->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode(['success'=>true,'sales_order'=>$so, 'items' => $items]);
