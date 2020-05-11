<?php
# Adopted from 'Example code'
# Source: eConestoga
# Project: attendancePHP
# Author: Rick Kozak
# Course: PROG8185 Web Technologies

if (isset($_SERVER['HTTP_ORIGIN'])) {
    // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
    // you want to allow, and if so:
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        // may also be using PUT, PATCH, HEAD etc
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");         

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
}

# Output JSON response
# parameters: 
# $data - not optional - data to output
# $success - optional - status of the response, pass true only if $data is null, otherwise false
# $error_message - optional - message that specifies the reason(s) for ($success = false)
# $error - optional - error information
# Any parameter that is null - excluded from the response
function outputJson($data, $success = NULL, $error_message = NULL, $error = NULL) {
    $response = new stdClass();
    if(!is_null($data)) {
        $response -> data = $data;
    }
    if(!is_null($success)) {
        if($success == '1') $success = 'true';
        else $success = 'false';
        $response -> success = $success;
    }
    if(!is_null($error_message)) {
        $response -> error_message = $error_message;
    }
    if(!is_null($error)) {
        $response -> error = $error;
    }

    echo json_encode($response);
}

function outputException($exception) {
    outputJson(NULL, false, 'exception happened', $exception);  
}

# GET /<tableName>.php
# returns JSON objects list
function handleGet($tableName, $columns = '*', $join = '', $where = '') {
    try {
        # extract the id portion of the URL
        if (isset($_SERVER['PATH_INFO'])) {
            $id = substr($_SERVER['PATH_INFO'], 1);
        }

        # were we provided with an id?
        if (isset($id) && is_numeric($id)) {
            $cmd = "SELECT $columns FROM $tableName " . 
                   $join .
                   ' WHERE id = :id';
            if (strlen($where) > 0)
                $cmd = $cmd . ' AND ' . $where;
            $sql = $GLOBALS['db']->prepare($cmd);
            $sql->bindValue(':id', $id);
        } else {
            $cmd = "SELECT $columns FROM $tableName " . $join;
            if (strlen($where) > 0)
                $cmd = $cmd . ' WHERE ' . $where;
            $sql = $GLOBALS['db']->prepare($cmd);
        }
        $sql->execute();
    } 
    catch (Exception $e) {   
        outputException($e->getMessage());     
    }

    $response = new stdClass();
    $responseHeader = getArrayNamePlural($tableName);
    $response -> $responseHeader = $sql->fetchAll(PDO::FETCH_ASSOC);
    outputJson($response);
}

function getArrayNamePlural($singular) {
    return $singular . "s";
}

# DELETE /<tableName>.php/5
# last part of URL to be ID of record to delete
# no body
# returns JSON format boolean. True if successful, false if not
function handleDelete($tableName) {
    $isSuccess = false;

    try {
        # extract the id portion of the URL
        $id = substr($_SERVER['PATH_INFO'], 1);

        # validate the received data
        if (is_numeric($id)) {
            # delete the record
            $cmd = "DELETE FROM $tableName " .
                'WHERE id = :id';
            $sql = $GLOBALS['db']->prepare($cmd);
            $sql->bindValue(':id', $id);
            # execute returns true if the update worked, so we don't actually have to test
            # to see if the record exists before attempting a delete
            $isSuccess = $sql->execute();
        }
    }
    catch (Exception $e) {
        outputException($e->getMessage());
    }
    outputJson(NULL, $isSuccess);
}

# POST /<tableName>.php
# body to contain a valid JSON object
# returns JSON format ID of new record, or zero if fail
function handlePost($isValid, $save) {
    $newId = -1;

    try {
        $post = trim(file_get_contents("php://input"));
        $data = json_decode($post, true);

        if ($isValid($data)) {
            $save($data);
            $newId = $GLOBALS['db']->lastInsertId();
        }
        else {
            return;
        }
    }
    catch (Exception $e) {
        outputException($e->getMessage());
    }

    $response = new stdClass();
    $responseHeader = 'newId';
    $response -> $responseHeader = $newId;
    outputJson($response);
}

# PUT /<tableName>.php/5
# last part of URL to be ID of record to update
# body to contain valid JSON object
# returns JSON format boolean. True if successful, false if not
function handlePut($isValid, $update) {
    $isSuccess = false;

    try {
        # extract the id portion of the URL
        $id = substr($_SERVER['PATH_INFO'], 1);
        # get the JSON format body and decode it
        $put = trim(file_get_contents("php://input"));
        $data = json_decode($put, true);

        # validate the received data
        if ($isValid($data, $id)) {
            $isSuccess = $update($data, $id);
        }
        else {
            return;
        }
    } 
    catch (Exception $e) {
        outputException($e->getMessage()); 
    }

    outputJson(NULL, $isSuccess);
}

# POST /<tableName>.php/5
# last part of URL to be ID of record to upload image
# returns JSON format boolean. True if successful, false if not
function handleUpload($isValid, $save, $image_path) {
    $isSuccess = false;

    try {
        # extract the id portion of the URL
        $id = substr($_SERVER['PATH_INFO'], 1);

        # validate the received data
        if ($isValid($image_path)) {
            $save($image_path, $id);
            $newId = $GLOBALS['db']->lastInsertId();
            $isSuccess = true;
        }
        else {
            return;
        }
    } 
    catch (Exception $e) {
        outputException($e->getMessage()); 
    }

    $response = new stdClass();
    $responseHeader = 'newId';
    $responseHeaderPath = 'imagePath';
    $response -> $responseHeader = $newId;
    $response -> $responseHeaderPath = $image_path; 
    outputJson($response, $isSuccess);
}

?>