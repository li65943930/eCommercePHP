<?php
include "pageStart.php";
include "handlers.php";

define('TABLE', 'comment_image');

$verb = strtolower($_SERVER['REQUEST_METHOD']);

if ($verb == 'post') {
    if (isLoggedIn()) {
        uploadFile();
    }
    else
        echo '{}';
}

function uploadFile()
{
    # test this using Postman by selecting the Body -> Form-Data option
    # and specifying a file parameter. Use the POST verb.
    $success = false;

    # for this to work, the targetDir must already exist, it will not create the directory here  
    $targetDir = "images/comments/";

    # tempnam creates a new name for the file, never use the user's provided file name as there could be collisions
    $targetFile = tempnam($targetDir, 'c_');
    $path = $_FILES['file']['name'];
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    rename($targetFile, $targetFile .= '.' . $ext);

    # delete the file (since tempnam also creates the file)
    unlink($targetFile);

    # move the file from the temporary place it was stored after upload to our chosen name
    if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
        #if successful, store the name to the database
        $image_path = $targetDir . pathinfo($targetFile, PATHINFO_FILENAME) . '.' . pathinfo($targetFile, PATHINFO_EXTENSION);
        handleUpload('isValidInsert', 'insert', $image_path);
    }
}

# validation code for image object on insert
function isValidInsert($image_path)
{
    $message = array();

    // validate $_FILES["file"]["tmp_name"]

    if(empty($message)) {
        return true;
    }
    else {
        outputJson(NULL, false, $message);   
        return false;
    }
}

# DB insert for image/comment
function insert($image_path, $id)
{
    $cmd = 'INSERT INTO ' . TABLE . ' (image_path, comment_id) ' .
        'VALUES (:image_path, :comment_id)';
    $sql = $GLOBALS['db']->prepare($cmd);
    $sql->bindValue(':image_path', $image_path);
    $sql->bindValue(':comment_id', $id);
    $sql->execute();
}

?>