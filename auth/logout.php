<?php
require_once '../common/conn.php';
session_destroy();
redirect('auth/login.php?msg=logged_out');
exit();
?>
