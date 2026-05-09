<?php
require_once '../auth_check.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'moder') {
    header("Location: ../index.php");
    exit;
}
