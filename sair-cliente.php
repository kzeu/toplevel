<?php
declare(strict_types=1);
require_once __DIR__ . '/config/store.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
unset($_SESSION['store_customer_id']);
session_regenerate_id(true);
header('Location: acesso-cliente.php');
exit;
