<?php
$target_dir = "files/";
if (!file_exists($target_dir)) mkdir($target_dir);
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);

if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
    header("Location: index.php");
    exit;
} else {
    echo "Upload failed.";
}
