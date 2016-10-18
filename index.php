<?php
header("Content-Type:text/html; charset=utf-8");
define("APP_PATH",dirname(__FILE__)."/genealogy");
define("APP_DEBUG",2);
session_start();
require '../../phpframe/PL.php';
?>