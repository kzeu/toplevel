<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/store.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
