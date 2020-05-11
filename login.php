<?php
# Adopted from 'Example code'
# Source: eConestoga
# Project: attendancePHP
# Author: Rick Kozak
# Course: PROG8185 Web Technologies

include "pageStart.php";
include "handlers.php";

define('TABLE', 'account');

$verb = strtolower($_SERVER['REQUEST_METHOD']);

if ($verb == 'post') {
    # client can post to this route to attempt to log in
    $isLoggedIn = false;

    # get the data the user sent
    $post = trim(file_get_contents("php://input"));
    $data = json_decode($post, true);

    # look in the users table for a matching email
    $cmd = 'SELECT * FROM ' . TABLE . ' WHERE email = :email';
    $sql = $GLOBALS['db']->prepare($cmd);
    $sql->bindValue(':email', $data['email']);
    $sql->execute();

    # if found, verify the password matches
    $user = $sql->fetch(PDO::FETCH_ASSOC);
    if (isset($user)){
        if (password_verify($data['password'], $user['password'])){
            $_SESSION['UserId'] = $user['id'];
            $isLoggedIn = true;

            if (isset($_SESSION["PurchaseId"])) {    
                $cmd = 'UPDATE purchase SET account_id = :account_id WHERE id = :purchase_id';
                $sql = $GLOBALS['db']->prepare($cmd);
                $sql->bindValue(':purchase_id', $_SESSION["PurchaseId"]);
                $sql->bindValue(':account_id', $_SESSION["UserId"]);
                $result = $sql->execute();
            }
        }
    }

    # provide a response
    $resp = new stdClass();
    $resp->success = $isLoggedIn;
    $resp->userId = $user['id'];
    echo json_encode($resp);

} else if ($verb == 'delete') {
    # client can delete to this route to log out
    # this will always succeed
    $_SESSION['UserId'] = null;

    if (isset($_SESSION["PurchaseId"])) {    
        $cmd = 'UPDATE purchase SET account_id = :account_id WHERE id = :purchase_id';
        $sql = $GLOBALS['db']->prepare($cmd);
        $sql->bindValue(':purchase_id', $_SESSION["PurchaseId"]);
        $sql->bindValue(':account_id', $_SESSION["UserId"]);
        $result = $sql->execute();
    }

    echo '{ "success" : "true" }';
}

?>