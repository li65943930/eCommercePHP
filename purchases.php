<?php 

include "pageStart.php";
include "handlers.php";

define('TABLE', 'purchase');

$verb = strtolower($_SERVER['REQUEST_METHOD']);

if(isLoggedIn()) {
    if($verb == 'get') {
        handleGet(TABLE, '*', '', (isset($_SESSION['UserId']) ? 'account_id = ' . $_SESSION['UserId'] : 'account_id is null'));
    }
}