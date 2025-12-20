<?php
/**
 * Get available weight files from the weights directory
 * 
 * @return array Array of weight files with their information
 */
function getWeightFiles() {
    $weights_path = "/home/yolo/st99_trenink/Detekce_Obrazu/yolov5/weights";
    $weight_files = [];
    
    if (!is_dir($weights_path)) {
        return [
            'success' => false,
            'message' => 'Adresář weights nenalezen',
            'path' => $weights_path,
            'files' => []
        ];
    }
    
    // Get all .pt files
    $pt_files = glob($weights_path . "/*.pt");
    
    if (empty($pt_files)) {
        return [
            'success' => false,
            'message' => 'Žádné weight soubory nenalezeny',
            'path' => $weights_path,
            'files' => []
        ];
    }
    
    // Sort files alphabetically
    sort($pt_files);
    
    // Build array with file information
    foreach ($pt_files as $file_path) {
        $filename = basename($file_path);
        $filesize = filesize($file_path);
        $filesize_mb = round($filesize / 1048576, 2); // Convert to MB
        $modified = filemtime($file_path);
        
        $weight_files[] = [
            'filename' => $filename,
            'path' => $file_path,
            'size_bytes' => $filesize,
            'size_mb' => $filesize_mb,
            'size_formatted' => $filesize_mb . ' MB',
            'modified' => $modified,
            'modified_formatted' => date('Y-m-d H:i:s', $modified),
            'display_name' => $filename . ' (' . $filesize_mb . ' MB)'
        ];
    }
    
    return [
        'success' => true,
        'message' => count($weight_files) . ' weight souborů nalezeno',
        'path' => $weights_path,
        'count' => count($weight_files),
        'files' => $weight_files
    ];
}

/**
 * Get default weight file
 * 
 * @param array $weight_files Array of weight files from getWeightFiles()
 * @return string|null Filename of default weight or null
 */
function getDefaultWeight($weight_files) {
    if (empty($weight_files)) {
        return null;
    }
    
    // Priority order for default selection
    $preferred_defaults = ['yolov8m.pt', 'yolov5m.pt', 'yolov8s.pt', 'yolov5s.pt'];
    
    foreach ($preferred_defaults as $preferred) {
        foreach ($weight_files as $file) {
            if ($file['filename'] === $preferred) {
                return $preferred;
            }
        }
    }
    
    // If no preferred default found, return first file
    return $weight_files[0]['filename'];
}

/**
 * Get fallback/default weight options
 * Used when no weight files are found in the directory
 * 
 * @return array Array of fallback weight options
 */
function getFallbackWeights() {
    return [
        [
            'filename' => 'yolov8n.pt',
            'display_name' => 'YOLOv8 Nano (fastest)',
            'is_fallback' => true
        ],
        [
            'filename' => 'yolov8s.pt',
            'display_name' => 'YOLOv8 Small',
            'is_fallback' => true
        ],
        [
            'filename' => 'yolov8m.pt',
            'display_name' => 'YOLOv8 Medium',
            'is_fallback' => true,
            'is_default' => true
        ],
        [
            'filename' => 'yolov8l.pt',
            'display_name' => 'YOLOv8 Large',
            'is_fallback' => true
        ],
        [
            'filename' => 'yolov8x.pt',
            'display_name' => 'YOLOv8 XLarge (best)',
            'is_fallback' => true
        ]
    ];
}
?>
