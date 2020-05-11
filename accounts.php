<?php

include "pageStart.php";
include "handlers.php";

define('TABLE', 'account');
define('COLUMNS', 'id, email, username, shipping_address');

$verb = strtolower($_SERVER['REQUEST_METHOD']);

if($verb == 'post') {
    handlePost('isValidInsert','insert');
}
else if(isLoggedIn()) {
    if($verb == 'put') {
        handlePut('isValidUpdate', 'update');
    }
    else if($verb == 'get') {
        handleGet(TABLE, COLUMNS);
    }
    else if($verb == 'delete') {
        handleDelete(TABLE);
    }
}

function isValidInsert($account) {
    return isValidInput($account) && isUniqueCredentials($account['email'], $account['username']);
}

function isValidUpdate($registrationCode, $id) {
    return isValidInsert($registrationCode) && is_numeric($id) && $id > 0;
}

function isValidInput($account) {
    $message = array();

    if(!isset($account['email'])) {
        $message['email'] = 'not set';
    }
    else if(!filter_var($account['email'], FILTER_VALIDATE_EMAIL)){
        $message['email'] = 'invalid';
    }
    
    if(!isset($account['password'])) {
        $message['password'] = 'not set';
    }
    else if(!isValidPassword($account['password'])){
        $message['password'] = 'invalid';
    }

    if(!isset($account['username'])) {
        $message['username'] = 'not set';
    }
    else if(strlen($account['username']) < 4) {
        $message['username'] = 'invalid';
    }

    if(empty($message)) {
        return true;
    }
    else {
        outputJson(NULL, false, $message);   
        return false;
    }
}

function isValidPassword($password) {
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $specialChars = preg_match('@[^\w]@', $password);
    return $uppercase && $lowercase && $number && $specialChars && strlen($password) > 8;
}

function isUniqueCredentials($email, $username) {
    $message = array();

    $cmd = 'SELECT email, username FROM ' . TABLE . ' WHERE email = :email OR username = :username';
    $sql = $GLOBALS['db']->prepare($cmd);
    $sql->bindValue(':email', $email);
    $sql->bindValue(':username', $username);
    $sql->execute();

    $data = $sql->fetchAll();

    if(empty($data)) {
        return true;
    }

    foreach($data as $key => $value) {
        if($value['email'] == $email) $message['email'] = 'repeated';
        if($value['username'] == $username) $message['username'] = 'repeated';
    }

    outputJson(NULL, false, $message);   
    return false;
}

function insert($account) {
    $cmd = 'INSERT INTO ' . TABLE . ' (Email, Password, Username, Shipping_address) ' .
        'VALUES (:email, :password, :username, :shipping_address)';
    $sql = $GLOBALS['db']->prepare($cmd);
    $sql->bindValue(':email', $account['email']);
    $sql->bindValue(':password', password_hash($account['password'], PASSWORD_BCRYPT));
    $sql->bindValue(':username', $account['username']);
    $sql->bindValue(':shipping_address', $account['shipping_address']);
    $sql->execute();
}

function update($account, $id) {
    $cmd = 'UPDATE ' . TABLE .
        ' SET Email = :email, Password = :password, Username = :username, Shipping_address = :shipping_address ' .
        'WHERE ID = :id';
    $sql = $GLOBALS['db']->prepare($cmd);
    $sql->bindValue(':email', $account['email']);
    $sql->bindValue(':password', password_hash($account['password'], PASSWORD_BCRYPT));
    $sql->bindValue(':username', $account['username']);
    $sql->bindValue(':shipping_address', $account['shipping_address']);
    $sql->bindValue(':id', $id);

    return $sql->execute();
}

?>