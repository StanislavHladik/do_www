<?php
header('Content-Type: application/json');

//$folder = 'nahledy/';
$folder = 'nahledy/';
$images = array_diff(scandir($folder), array('.', '..'));

$imageList = [];
foreach ($images as $image) {
    if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $image)) {
        $imageList[] = $folder . $image;
    }
}

echo json_encode($imageList);
?>