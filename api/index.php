<?php
require __DIR__ . '/vendor/autoload.php';
require_once "users.php";
$userController = new Users();
$userController->processRequest();
