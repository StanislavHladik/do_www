<?php
/**
 * Start Training Endpoint
 * 
 * This script handles training form submission:
 * 1. Receives training parameters from the form
 * 2. Writes configuration to trenink.json
 * 3. Launches trenink.py in the virtual environment
 */

header('Content-Type: application/json; charset=utf-8');

// Log file for debugging
$log_file = '/tmp/start_training.log';

function logMessage($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    logMessage("=== Start Training Request ===");
    
    // Get POST parameters
    $dataset_name = $_POST['dataset_name'] ?? '';
    $dataset_path = $_POST['dataset_path'] ?? '';
    $epochs = intval($_POST['epochs'] ?? 100);
    $batch_size = intval($_POST['batch_size'] ?? 16);
    $img_size = intval($_POST['img_size'] ?? 1920);
    $model_type = $_POST['model_type'] ?? 'yolov5s.pt';
    $model_name = $_POST['model_name'] ?? 'custom_model';
    $machine_number = intval($_POST['machine_number'] ?? 99);
    
    logMessage("Dataset: $dataset_name");
    logMessage("Path: $dataset_path");
    logMessage("Epochs: $epochs, Batch: $batch_size, ImgSize: $img_size");
    logMessage("Model Type: $model_type, Model Name: $model_name");
    logMessage("Machine: $machine_number");
    
    // Validate required parameters
    if (empty($dataset_name)) {
        throw new Exception('Dataset name is required');
    }
    
    if (empty($dataset_path) || !is_dir($dataset_path)) {
        throw new Exception('Invalid dataset path: ' . $dataset_path);
    }
    
    // Build paths based on machine number (st99_trenink is the training machine)
    $training_base = "/home/yolo/st99_trenink/Detekce_Obrazu";
    $config_file = $training_base . "/trenink.json";
    $python_script = $training_base . "/trenink.py";
    $venv_path = $training_base . "/Detekce_Obrazu_venv";
    $weights_base = $training_base . "/yolov5/weights";
    
    logMessage("Config file: $config_file");
    logMessage("Python script: $python_script");
    logMessage("Venv path: $venv_path");
    
    // Validate paths exist
    if (!is_dir($training_base)) {
        throw new Exception("Training directory not found: $training_base");
    }
    
    if (!file_exists($python_script)) {
        throw new Exception("Training script not found: $python_script");
    }
    
    if (!is_dir($venv_path)) {
        throw new Exception("Virtual environment not found: $venv_path");
    }
    
    // Build the full path to weights file
    // If model_type is just a filename, prepend the weights directory
    if (strpos($model_type, '/') === false) {
        $weights_path = $weights_base . '/' . $model_type;
    } else {
        $weights_path = $model_type;
    }
    
    logMessage("Weights path: $weights_path");
    
    // Check if weight file exists (optional - it will be downloaded if missing)
    if (!file_exists($weights_path)) {
        logMessage("Warning: Weight file not found, will be downloaded: $weights_path");
    }
    
    // Build the data YAML filename from dataset name
    // Assuming the YAML file has the same name as the dataset directory
    $data_yaml = $dataset_name . '.yaml';
    
    logMessage("Data YAML: $data_yaml");
    
    // Prepare configuration array matching trenink.json structure
    $config = [
        'imgsz' => $img_size,
        'epochs' => $epochs,
        'data' => $data_yaml,
        'weights' => $weights_path,
        'batch_size' => $batch_size
    ];
    
    logMessage("Config: " . json_encode($config));
    
    // Write configuration to trenink.json
    $json_content = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    if (file_put_contents($config_file, $json_content) === false) {
        throw new Exception("Failed to write configuration file: $config_file");
    }
    
    logMessage("Configuration written successfully to: $config_file");
    
    // Build command to run Python script in virtual environment
    // Use nohup and redirect to background so it doesn't block the web request
    $python_bin = $venv_path . '/bin/python3';
    
    if (!file_exists($python_bin)) {
        throw new Exception("Python binary not found in venv: $python_bin");
    }
    
    // Create a log file for training output
    $training_log = "/tmp/training_output_" . date('Y-m-d_H-i-s') . ".log";
    
    // Build the command
    // Use nohup to prevent termination when HTTP connection closes
    // Redirect stdout and stderr to log file
    // Run in background with &
    $command = sprintf(
        'cd %s && nohup %s %s > %s 2>&1 &',
        escapeshellarg($training_base),
        escapeshellarg($python_bin),
        escapeshellarg($python_script),
        escapeshellarg($training_log)
    );
    
    logMessage("Executing command: $command");
    
    // Execute the command
    exec($command, $output, $return_code);
    
    logMessage("Command executed with return code: $return_code");
    logMessage("Training log will be written to: $training_log");
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Trénink byl úspěšně zahájen',
        'config_file' => $config_file,
        'training_log' => $training_log,
        'config' => $config,
        'command' => $command
    ]);
    
    logMessage("=== Training started successfully ===");
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    logMessage("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_line' => $e->getLine(),
        'log_file' => $log_file
    ]);
}
?>
