<?php
include "pageStart.php";
include "handlers.php";

define('TABLE', 'comment');

$verb = strtolower($_SERVER['REQUEST_METHOD']);

if ($verb == 'get') {
    handleGet(TABLE);
} else if ($verb == 'post') {
    if (isLoggedIn())
        handlePost('isValidInsert', 'insert');
    else
        echo '{}';
} else if ($verb == 'put') {
    if (isLoggedIn())
        handlePut('isValidUpdate', 'update');
    else
        echo '{}';
} else if ($verb == 'delete') {
    if (isLoggedIn())
        handleDelete(TABLE);
    else
        echo '{}';
}

# validation code for comment object on insert
function isValidInsert($comment)
{
    $message = array();

    if(!isset($comment['rating'])) {
        $message['rating'] = 'not set';
    }
    else if(!is_numeric($comment['rating']) || $comment['rating'] < 1 || $comment['rating'] > 5){
        $message['rating'] = 'invalid (must be an integer between 1 and 5)';
    }

    if(!isset($comment['text'])) {
        $message['text'] = 'not set';
    }
    else if(strlen($comment['text']) <= 0){
        $message['text'] = 'invalid (at least one character)';
    }

    if(!isset($_SESSION['UserId'])) {
        $message['account_id'] = 'user is not logged in';
    }

    if(!isset($comment['product_id'])) {
        $message['product_id'] = 'not set';
    }
    else if(!is_numeric($comment['product_id']) || $comment['product_id'] < 0){
        $message['product_id'] = 'invalid (must be an integer greater than or equal to 0)';
    }

    if(empty($message)) {
        return true;
    }
    else {
        outputJson(NULL, false, $message);   
        return false;
    }
}

# validation code for comment object on update
function isValidUpdate($comment, $id)
{
    return isValidInsert($comment) && is_numeric($id) && $id > 0;
}

# DB insert for comment
function insert($comment)
{
    $cmd = 'INSERT INTO ' . TABLE . ' (rating, text, account_id, product_id) ' .
        'VALUES (:rating, :text, :account_id, :product_id)';
    $sql = $GLOBALS['db']->prepare($cmd);
    $sql->bindValue(':rating', $comment['rating']);
    $sql->bindValue(':text', $comment['text']);
    $sql->bindValue(':account_id', $_SESSION['UserId']);
    $sql->bindValue(':product_id', $comment['product_id']);
    $sql->execute();
}

# DB update for comment
function update($comment, $id)
{
    # update the record
    $cmd = 'UPDATE ' . TABLE .
        ' SET rating = :rating, text = :text, account_id = :account_id, product_id = :product_id ' .
        'WHERE id = :id';
    $sql = $GLOBALS['db']->prepare($cmd);
    $sql->bindValue(':rating', $comment['rating']);
    $sql->bindValue(':text', $comment['text']);
    $sql->bindValue(':account_id', $_SESSION['UserId']);
    $sql->bindValue(':product_id', $comment['product_id']);
    $sql->bindValue(':id', $id);

    # execute returns true if the update worked, so we don't actually have to test
    # to see if the record exists before attempting an update.
    return $sql->execute();
}

?>