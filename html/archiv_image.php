<?php
/**
 * archiv_image.php — secure proxy for serving archived images
 * Images live outside the web root at /media/archiv/yolo/{machine}/sber/{batch}/{file}
 */

// --- Validate all parameters strictly ---
$cisloStroj = isset($_GET['cisloStroj']) ? $_GET['cisloStroj'] : '';
$batch      = isset($_GET['batch'])      ? $_GET['batch']      : '';
$img        = isset($_GET['img'])        ? $_GET['img']        : '';

if (!preg_match('/^\d+$/', $cisloStroj)) {
    http_response_code(400); exit('Invalid machine number');
}
if (!preg_match('/^\w+$/', $batch)) {
    http_response_code(400); exit('Invalid batch name');
}
// Allow only safe filename characters (letters, digits, underscores, hyphens, dots)
$img = basename($img); // strip any directory components
if (!preg_match('/^[\w\-\.]+\.(jpg|jpeg|png|JPG|JPEG|PNG)$/i', $img)) {
    http_response_code(400); exit('Invalid image filename');
}

// --- Build and verify path ---
$allowedBase = '/media/archiv/yolo';
$filePath    = $allowedBase . '/' . $cisloStroj . '/sber/' . $batch . '/' . $img;
$realPath    = realpath($filePath);
$realBase    = realpath($allowedBase);

if ($realPath === false || $realBase === false || strpos($realPath, $realBase . '/') !== 0) {
    http_response_code(403); exit('Access denied');
}
if (!is_file($realPath) || !is_readable($realPath)) {
    http_response_code(404); exit('File not found');
}

// --- Serve the image ---
$ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
$mimeTypes = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
];
$mime = $mimeTypes[$ext] ?? 'image/jpeg';

header('Content-Type: '   . $mime);
header('Content-Length: ' . filesize($realPath));
header('Cache-Control: max-age=3600, private');
header('X-Content-Type-Options: nosniff');
readfile($realPath);
exit;
