<?php
require '../conn.php'; // and your mail helper
header('Content-Type: application/json');

$so_id = intval($_POST['quotation_id'] ?? 0); // reusing field from modal
$to = trim($_POST['recipient_email'] ?? '');
$ccs = trim($_POST['additional_emails'] ?? '');
$msg = trim($_POST['custom_message'] ?? '');

if (!$so_id || !$to) { echo json_encode(['success'=>false,'message'=>'Missing fields']); exit; }

$r = $conn->query("SELECT so_number FROM sales_orders WHERE id = $so_id");
if (!$r || $r->num_rows==0) { echo json_encode(['success'=>false,'message'=>'SO not found']); exit; }
$so = $r->fetch_assoc();

$subject = "Sales Order: {$so['so_number']}";
$body = nl2br("Dear Customer,\n\nPlease find the Sales Order {$so['so_number']} attached/linked.\n\n".$msg."\n\nThanks,\n".$_SESSION['company_name']);

$ok = send_mail($to, $subject, $body, $ccs); // <-- your existing helper that you used for quotation
echo json_encode($ok ? ['success'=>true,'message'=>'Email sent successfully!'] :
                      ['success'=>false,'message'=>'Failed to send email']);
