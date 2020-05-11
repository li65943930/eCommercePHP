<?php

include "pageStart.php";
include "handlers.php";

# list all products 
define('TABLE', 'product');

# to show details about a specific product 
define('COLUMNS', 'id, description, image_path, price, shipping_cost, product_type_id');

$verb = strtolower($_SERVER['REQUEST_METHOD']);

# To show products to any user 
if ($verb == 'get') {
	handleGet(TABLE, COLUMNS);	
}

# To add/create/update/delete a product to only authorized users like Admins, assuming that
# the Create a product, Update product, or Delete product will only 
# be available to Admins. Other users can only view a product/all products

else if ($verb == 'post') {
	handlePost('isValidInsert', 'insert');
}
else if ($verb == 'put') {
	handlePut('isValidUpdate', 'update');
} 
else if ($verb == 'delete') {
	handleDelete(TABLE);
}

# validation code for product object on insert
function isValidInsert($product) {
	return  isset($product['description']) &&
	isset($product['image_path']) &&  isset($product['price']) &&  
	isset($product['shipping_cost']) &&  isset($product['product_type_id']);		
}

# TO Do next
# validation code for product object on update
function isValidUpdate($product, $id) {
	return isValidInsert($product) && is_numeric($id) && $id > 0;
}

# DB insert for a product
function insert($product)
{
	$cmd = 'INSERT INTO ' . TABLE . ' (description, image_path, price, shipping_cost, product_type_id) ' .
		'VALUES (:description, :image_path, :price, :shipping_cost, :product_type_id)';
	$sql = $GLOBALS['db']->prepare($cmd);
	$sql->bindValue(':description', $product['description']);
	$sql->bindValue(':image_path', $product['image_path']);
	$sql->bindValue(':price', $product['price']);
	$sql->bindValue(':shipping_cost', $product['shipping_cost']);
	$sql->bindValue(':product_type_id', $product['product_type_id']);
		
	$sql->execute();
}

function update($product, $id) {
	# update the record
	$cmd = 'UPDATE ' . TABLE .  ' SET description = :description, image_path = :image_path, price = :price, shipping_cost = :shipping_cost, product_type_id = :product_type_id' .
	' WHERE ID = :id';
	$sql = $GLOBALS['db']->prepare($cmd);
	$sql->bindValue(':description', $product['description']);
	$sql->bindValue(':image_path', $product['image_path']);
	$sql->bindValue(':price', $product['price']);
	$sql->bindValue(':shipping_cost', $product['shipping_cost']);
	$sql->bindValue(':product_type_id', $product['product_type_id']);
	$sql->bindValue(':id', $id);
	# execute returns true if the update worked, so we don't actually have to test
	# to see if the record exists before attempting an update.
		
	return $sql->execute();
}
?>