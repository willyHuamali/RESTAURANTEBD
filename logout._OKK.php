<?php
require_once 'includes/auth.php';

session_start();
session_unset();
session_destroy();

header("Location: login.php");
exit();
?>