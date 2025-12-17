<?php
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Check if file was uploaded
if (!isset($_FILES['dataset_file']) || $_FILES['dataset_file']['error'] !== UPLOAD_ERR_OK) {
    $error_message = 'No file uploaded';
    if (isset($_FILES['dataset_file']['error'])) {
        // Get PHP upload limits for better error messages
        $upload_max = ini_get('upload_max_filesize');
        $post_max = ini_get('post_max_size');
        
        switch ($_FILES['dataset_file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $error_message = "Soubor je příliš velký. PHP limit upload_max_filesize je nastaven na: {$upload_max}. Kontaktujte správce pro zvýšení limitu.";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = "Soubor je příliš velký. PHP limit post_max_size je nastaven na: {$post_max}. Kontaktujte správce pro zvýšení limitu.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = 'Soubor byl nahrán pouze částečně. Zkuste to znovu.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = 'Nebyl vybrán žádný soubor';
                break;
            default:
                $error_message = 'Chyba nahrávání: ' . $_FILES['dataset_file']['error'];
        }
    }
    echo json_encode([
        'success' => false, 
        'message' => $error_message,
        'php_upload_max' => ini_get('upload_max_filesize'),
        'php_post_max' => ini_get('post_max_size')
    ]);
    exit();
}

// Get parameters
$machine_number = isset($_POST['machine_number']) ? $_POST['machine_number'] : '99';
$custom_name = isset($_POST['dataset_name']) ? trim($_POST['dataset_name']) : '';

// Get file information
$uploaded_file = $_FILES['dataset_file'];
$original_filename = basename($uploaded_file['name']);
$temp_path = $uploaded_file['tmp_name'];
$file_size = $uploaded_file['size'];

// Validate file extension
$file_ext = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
if ($file_ext !== 'zip') {
    echo json_encode(['success' => false, 'message' => 'Only .zip files are supported']);
    exit();
}

// Validate file size (500 MB limit)
$max_size = 500 * 1024 * 1024; // 500 MB
if ($file_size > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 500 MB']);
    exit();
}

// Also check PHP's upload_max_filesize and post_max_size limits
$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');
error_log("Upload limits - upload_max_filesize: {$upload_max}, post_max_size: {$post_max}, file_size: {$file_size} bytes");

// Determine dataset name
if (!empty($custom_name)) {
    // Use custom name (sanitize it)
    $dataset_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $custom_name);
} else {
    // Use filename without extension
    $dataset_name = pathinfo($original_filename, PATHINFO_FILENAME);
    $dataset_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $dataset_name);
}

// Build the target path - always use st99_trenink for training
$base_path = "/home/yolo/st99_trenink/Detekce_Obrazu/datasets_yolo_1_1";

// Check if base directory exists
if (!is_dir($base_path)) {
    echo json_encode([
        'success' => false, 
        'message' => "Cílový adresář nenalezen: {$base_path}"
    ]);
    exit();
}

// Check if directory is writable
if (!is_writable($base_path)) {
    echo json_encode([
        'success' => false, 
        'message' => "Cílový adresář není zapisovatelný: {$base_path}"
    ]);
    exit();
}

// Create target directory for the dataset
$target_dir = $base_path . '/' . $dataset_name;

// Check if dataset already exists
if (is_dir($target_dir)) {
    echo json_encode([
        'success' => false, 
        'message' => "Dataset '{$dataset_name}' již existuje. Vyberte jiný název."
    ]);
    exit();
}

// Create the target directory
if (!mkdir($target_dir, 0755, true)) {
    echo json_encode([
        'success' => false, 
        'message' => "Nepodařilo se vytvořit adresář: {$target_dir}"
    ]);
    exit();
}

// Move uploaded file to target directory
$zip_path = $target_dir . '/' . $original_filename;
if (!move_uploaded_file($temp_path, $zip_path)) {
    // Clean up - remove created directory
    rmdir($target_dir);
    echo json_encode([
        'success' => false, 
        'message' => 'Nepodařilo se přesunout nahraný soubor'
    ]);
    exit();
}

// Try to extract the zip file
$zip = new ZipArchive();
$extract_result = $zip->open($zip_path);

if ($extract_result === TRUE) {
    // Extract to the target directory
    $zip->extractTo($target_dir);
    $zip->close();
    
    // Optionally remove the zip file after extraction
    unlink($zip_path);
    
    echo json_encode([
        'success' => true,
        'message' => "Dataset '{$dataset_name}' byl úspěšně nahrán a rozbalen",
        'dataset_name' => $dataset_name,
        'target_path' => $target_dir,
        'file_size' => formatFileSize($file_size)
    ]);
} else {
    // If extraction fails, keep the zip file
    echo json_encode([
        'success' => true,
        'message' => "Dataset '{$dataset_name}' byl nahrán, ale nepodařilo se jej rozbalit. ZIP soubor byl zachován.",
        'dataset_name' => $dataset_name,
        'target_path' => $target_dir,
        'file_size' => formatFileSize($file_size),
        'warning' => 'Extraction failed - ZIP file preserved'
    ]);
}

// Log the upload
$log_entry = date('Y-m-d H:i:s') . " - Dataset uploaded: {$dataset_name} ({$file_size} bytes) to {$target_dir}\n";
file_put_contents('/tmp/dataset_uploads.log', $log_entry, FILE_APPEND | LOCK_EX);

/**
 * Format file size in human-readable format
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
