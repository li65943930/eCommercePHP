<?php
# Adopted from 'Example code'
# Source: eConestoga
# Project: attendancePHP
# Author: Rick Kozak
# Course: PROG8185 Web Technologies

// Connect to database
try {
    $servername = 'localhost';
    $dbname = 'ecommerce';

    $GLOBALS['db'] = new PDO('mysql:dbname='. $dbname . '; host=' . $servername . ';', 'root');
    $GLOBALS['db']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

// Start session
session_start();

function isLoggedIn() {
    // Return true for testing purposes, after adding front end - REMOVE
    return true;
    //return isset($_SESSION['UserId']) && $_SESSION['UserId'] > 0;
}

?>