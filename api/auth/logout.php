<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/bootstrap.php';
startSecureSession();
$_SESSION = [];
session_destroy();
header('Location: ' . appUrl('login'));
