<?php
include "pageStart.php";
include "handlers.php";

define('TABLE', 'product_item');
define('TABLE_PURCHASE', 'purchase');
define('TAX_RATE', 1.13);

$verb = strtolower($_SERVER['REQUEST_METHOD']);

if ($verb == 'get') {
    handleGet(TABLE);
} else if ($verb == 'post') {
    handlePost('isValidInsert', 'insert');
} else if ($verb == 'put') {
    handlePut('isValidUpdate', 'update');
} else if ($verb == 'delete') {
    handleDelete(TABLE);
}

# validation code for productItem object on insert
function isValidInsert($productItem, $validateDuplicate = true) {
    $message = array();

    if(!isset($productItem['product_id'])) {
        $message['product_id'] = 'not set';
    }
    else if(!is_numeric($productItem['product_id']) || $productItem['product_id'] < 0) {
        $message['product_id'] = 'invalid (must be an integer greater than or equals to 0)';
    }

    if(!isset($productItem['quantity'])) {
        $message['quantity'] = 'not set';
    }
    else if(!is_numeric($productItem['quantity']) || $productItem['quantity'] < 1 || $productItem['quantity'] > 100) {
        $message['quantity'] = 'invalid (must be an integer between 1 and 100)';
    }

    if ($validateDuplicate) {
        $cmd = 'SELECT * FROM product_item WHERE purchase_id = :purchase_id and product_id = :product_id';
        $sql = $GLOBALS['db']->prepare($cmd);
        $sql->bindValue(':purchase_id', $_SESSION["PurchaseId"]);
        $sql->bindValue(':product_id', $productItem["product_id"]);
        $sql->execute();

        $items = $sql->fetch(PDO::FETCH_ASSOC);

        if ($items) {
            $message['product_id'] = 'invalid (product id already exists)';
        }
    }

    if(empty($message)) {
        return true;
    }
    else {
        outputJson(NULL, false, $message);   
        return false;
    }
}

# validation code for productItem object on update
function isValidUpdate($productItem, $id) {
    return isValidInsert($productItem, false) && is_numeric($id) && $id > 0;
}

# DB insert for productItem
function insert($productItem) {
    if (!isset($_SESSION["PurchaseId"])) {
        $cmd = 'INSERT INTO ' . TABLE_PURCHASE . ' (account_id) ' .
        'VALUES (:account_id)';
        $sql = $GLOBALS['db']->prepare($cmd);
        $sql->bindValue(':account_id', (isset($_SESSION['UserId']) ? $_SESSION['UserId'] : null));
        $sql->execute();

        $_SESSION["PurchaseId"] = $GLOBALS['db']->lastInsertId();
    }

    
        $cmd = 'SELECT * FROM product WHERE id = :id';
        $sql = $GLOBALS['db']->prepare($cmd);
        $sql->bindValue(':id', $productItem['product_id']);
        $sql->execute();

        $data = $sql->fetch(PDO::FETCH_ASSOC);

        $cmd = 'INSERT INTO ' . TABLE . ' (description, price, quantity, product_id, purchase_id) ' .
            'VALUES (:description, :price, :quantity, :product_id, :purchase_id)';
        $sql = $GLOBALS['db']->prepare($cmd);
        $sql->bindValue(':description', $data['description']);
        $sql->bindValue(':price', getTotalPriceForItem($data, $productItem));
        $sql->bindValue(':quantity', $productItem['quantity']);
        $sql->bindValue(':product_id', $productItem['product_id']);
        $sql->bindValue(':purchase_id', $_SESSION["PurchaseId"]);
        $sql->execute();
    
}

# DB update for productItem
function update($productItem, $id) {
    # update the record
    $cmd = 'UPDATE ' . TABLE .
        ' SET quantity = :quantity, product_id = :product_id, purchase_id = :purchase_id ' .
        'WHERE id = :id';
    $sql = $GLOBALS['db']->prepare($cmd);
    $sql->bindValue(':quantity', $productItem['quantity']);
    $sql->bindValue(':product_id', $productItem['product_id']);
    $sql->bindValue(':purchase_id', $_SESSION["PurchaseId"]);
    $sql->bindValue(':id', $id);

    # execute returns true if the update worked, so we don't actually have to test
    # to see if the record exists before attempting an update.
    return $sql->execute();
}

function getTotalPriceForItem($product, $productItem) {
    $cmd = "SELECT is_taxable FROM product p INNER JOIN product_type pt ON p.product_type_id = pt.id WHERE p.id = :id";
    $sql = $GLOBALS['db']->prepare($cmd);
    $sql->bindValue(':id', $productItem['product_id']);
    $sql->execute();

    $data = $sql->fetch();

    $price = $product['price'];
    $price += $product['shipping_cost'];
    if($data['is_taxable']) $price*TAX_RATE;
    return $price;
}

?>