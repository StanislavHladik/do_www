<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['command'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input - command required']);
    exit();
}

$command = htmlspecialchars($input['command']);
$cisloStroj = isset($input['cisloStroj']) ? htmlspecialchars($input['cisloStroj']) : '1';

// Log the request
$log_entry = date('Y-m-d H:i:s') . " - Command: $command, Machine: $cisloStroj\n";
file_put_contents('/tmp/detection_api.log', $log_entry, FILE_APPEND | LOCK_EX);

/**
 * Execute detection script command
 */
function executeDetectionCommand($command, $cisloStroj) {
    $script_path = '/home/yolo/st2_plasty/Detekce_Obrazu/detekce_ulozeni.py';

    $python_cmd = 'python3';

    // Check if script exists
    if (!file_exists($script_path)) {
        return [
            'success' => false, 
            'message' => 'Detection script not found at: ' . $script_path
        ];
    }

    switch($command) {
        case 'start':
            return startDetection($script_path, $python_cmd, $cisloStroj);
        
        case 'stop':
            return stopDetection();
        
        case 'status':
            return getDetectionStatus();
        
        case 'restart':
            $stop_result = stopDetection();
            if ($stop_result['success']) {
                sleep(2); // Wait a bit before starting
                return startDetection($script_path, $python_cmd, $cisloStroj);
            }
            return $stop_result;
        case 'take_photo':
            return takePhoto($cisloStroj);
        case 'save_photo':
            return savePhoto($cisloStroj);
        case 'read_value':
            return readMachineValue($cisloStroj);
            
        case 'init_file':
            return initializeMachineFile($cisloStroj);

        default:
            return ['success' => false, 'message' => 'Unknown command: ' . $command];
    }
}

/**
 * Start the detection script
 */
function startDetection($script_path, $python_cmd, $cisloStroj) {
    // Check if already running
    $pid = getDetectionPid();
    if ($pid !== null) {
        return ['success' => false, 'message' => 'Detection is already running (PID: ' . $pid . ')'];
    }
    
    // Create command to run the script in background
    $cmd = "cd /home/yolo/st2_plasty/Detekce_Obrazu && $python_cmd detekce_ulozeni.py > /tmp/detection_output.log 2>&1 & echo $!";
    
    // Execute command and get PID
    $output = shell_exec($cmd);
    $pid = trim($output);
    
    if ($pid && is_numeric($pid)) {
        // Save PID to file
        file_put_contents('/tmp/detection.pid', $pid);
        return [
            'success' => true, 
            'message' => "Detection started successfully (PID: $pid) for machine $cisloStroj"
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to start detection script'];
    }
}

/**
 * Stop the detection script
 */
function stopDetection() {
    $pid = getDetectionPid();
    
    if ($pid === null) {
        return ['success' => true, 'message' => 'Detection is not running'];
    }
    
    // Kill the process
    $kill_result = shell_exec("kill $pid 2>&1");
    
    // Check if process was killed
    sleep(1);
    if (!isProcessRunning($pid)) {
        // Remove PID file
        if (file_exists('/tmp/detection.pid')) {
            unlink('/tmp/detection.pid');
        }
        return ['success' => true, 'message' => 'Detection stopped successfully'];
    } else {
        // Force kill if regular kill didn't work
        shell_exec("kill -9 $pid 2>&1");
        sleep(1);
        if (!isProcessRunning($pid)) {
            if (file_exists('/tmp/detection.pid')) {
                unlink('/tmp/detection.pid');
            }
            return ['success' => true, 'message' => 'Detection force-stopped successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to stop detection process'];
        }
    }
}

/**
 * Get detection status
 */
function getDetectionStatus() {
    $pid = getDetectionPid();
    
    if ($pid === null) {
        return ['success' => true, 'message' => 'Detection is not running'];
    }
    
    if (isProcessRunning($pid)) {
        // Get process info
        $ps_info = shell_exec("ps -p $pid -o pid,etime,cpu --no-headers 2>/dev/null");
        if ($ps_info) {
            $info = trim($ps_info);
            return ['success' => true, 'message' => "Detection is running (PID: $pid) - $info"];
        } else {
            return ['success' => true, 'message' => "Detection is running (PID: $pid)"];
        }
    } else {
        // PID file exists but process is not running - clean up
        if (file_exists('/tmp/detection.pid')) {
            unlink('/tmp/detection.pid');
        }
        return ['success' => true, 'message' => 'Detection is not running (cleaned up stale PID)'];
    }
}

/**
 * Get the PID of running detection script
 */
function getDetectionPid() {
    if (file_exists('/tmp/detection.pid')) {
        $pid = trim(file_get_contents('/tmp/detection.pid'));
        if ($pid && is_numeric($pid)) {
            return (int)$pid;
        }
    }
    return null;
}

/**
 * Check if a process is running
 */
function isProcessRunning($pid) {
    $result = shell_exec("ps -p $pid > /dev/null 2>&1; echo $?");
    return trim($result) === '0';
}

/**
 * Trigger photo taking by writing to machine's txt file
 */
function takePhoto($cisloStroj) {
    $num_file_path = "/opt/detection_triggers/num_" . $cisloStroj . ".txt";
    
    try {
        // Ensure the /opt/detection_triggers directory is writable
        if (!is_writable('/opt/detection_triggers')) {
            return [
                'success' => false,
                'message' => "Directory /opt/detection_triggers is not writable"
            ];
        }

        // Write '1' to the txt file to trigger photo taking
        // file_put_contents will create the file if it doesn't exist
        $result = file_put_contents($num_file_path, '1', LOCK_EX);

        if ($result !== false) {
            return [
                'success' => true,
                'message' => "Photo trigger sent successfully for machine $cisloStroj (file " . (file_exists($num_file_path) ? "updated" : "created") . ")"
            ];
        } else {
            return [
                'success' => false,
                'message' => "Failed to write trigger file for machine $cisloStroj"
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Error triggering photo for machine $cisloStroj: " . $e->getMessage()
        ];
    }
}

/**
 * Read the current value from machine's txt file
 */
function readMachineValue($cisloStroj) {
    $num_file_path = "/var/tmp/num_" . $cisloStroj . ".txt";
    
    try {
        if (file_exists($num_file_path)) {
            $value = trim(file_get_contents($num_file_path));
            return [
                'success' => true,
                'value' => $value,
                'message' => "Current value for machine $cisloStroj: $value"
            ];
        } else {
            // File doesn't exist, return default value
            return [
                'success' => true,
                'value' => '0',
                'message' => "No trigger file found for machine $cisloStroj, default value: 0"
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Error reading trigger file for machine $cisloStroj: " . $e->getMessage()
        ];
    }
}

/**
 * Initialize machine's txt file with default value if it doesn't exist
 */
function initializeMachineFile($cisloStroj) {
    $num_file_path = "/var/tmp/num_" . $cisloStroj . ".txt";
    
    if (!file_exists($num_file_path)) {
        try {
            $result = file_put_contents($num_file_path, '0', LOCK_EX);
            if ($result !== false) {
                return [
                    'success' => true,
                    'message' => "Initialized trigger file for machine $cisloStroj with default value '0'"
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Failed to initialize trigger file for machine $cisloStroj"
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Error initializing trigger file for machine $cisloStroj: " . $e->getMessage()
            ];
        }
    } else {
        return [
            'success' => true,
            'message' => "Trigger file for machine $cisloStroj already exists"
        ];
    }
}

/**
 * Save photo from nahledy to archiv
 */
function savePhoto($cisloStroj) {
    $source_dir = "/var/www/html/nahledy/" . $cisloStroj;
    $dest_dir = "/media/archiv/yolo/" . $cisloStroj . "/sber";
    
    try {
        // Check if source directory exists
        if (!is_dir($source_dir)) {
            return [
                'success' => false,
                'message' => "Source directory not found: $source_dir"
            ];
        }
        
        // Create destination directory if it doesn't exist
        if (!is_dir($dest_dir)) {
            if (!mkdir($dest_dir, 0755, true)) {
                return [
                    'success' => false,
                    'message' => "Failed to create destination directory: $dest_dir"
                ];
            }
        }
        
        // Check if destination directory is writable
        if (!is_writable($dest_dir)) {
            return [
                'success' => false,
                'message' => "Destination directory is not writable: $dest_dir"
            ];
        }
        
        // Get all image files from source directory
        $image_extensions = ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'tiff'];
        $files_copied = 0;
        
        $files = scandir($source_dir);
        if ($files === false) {
            return [
                'success' => false,
                'message' => "Failed to read source directory: $source_dir"
            ];
        }
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $file_path = $source_dir . '/' . $file;
            if (!is_file($file_path)) {
                continue;
            }
            
            // Check if it's an image file
            $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($file_ext, $image_extensions)) {
                continue;
            }
            
            // Extract the first number before underscore from filename
            $first_number = '';
            if (preg_match('/^(\d+)_/', $file, $matches)) {
                $first_number = $matches[1];
            } else {
                // If no number found, skip this file or use default
                continue;
            }
            
            // Create subdirectory based on first number
            $sub_dest_dir = $dest_dir . '/' . $first_number;
            if (!is_dir($sub_dest_dir)) {
                if (!mkdir($sub_dest_dir, 0755, true)) {
                    continue; // Skip this file if can't create directory
                }
            }
            
            // Generate destination path with subdirectory
            $new_filename = $file;
            $dest_path = $sub_dest_dir . '/' . $new_filename;
            
            // Copy the file
            if (copy($file_path, $dest_path)) {
                $files_copied++;
            }
        }
        
        if ($files_copied > 0) {
            return [
                'success' => true,
                'message' => "Successfully saved $files_copied image(s) from machine $cisloStroj to archive"
            ];
        } else {
            return [
                'success' => false,
                'message' => "No image files found in source directory for machine $cisloStroj"
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Error saving photos for machine $cisloStroj: " . $e->getMessage()
        ];
    }
}

// Execute the command
try {
    // Initialize machine file with default value
    $init_result = initializeMachineFile($cisloStroj);
    
    // Execute the requested command
    $result = executeDetectionCommand($command, $cisloStroj);
    
    // Merge initialization result with command result
    $result = array_merge($init_result, $result);
    
    // Log the result
    $log_entry = date('Y-m-d H:i:s') . " - Result: " . json_encode($result) . "\n";
    file_put_contents('/tmp/detection_api.log', $log_entry, FILE_APPEND | LOCK_EX);
    
    echo json_encode($result);
} catch (Exception $e) {
    $error_result = ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
    echo json_encode($error_result);
    
    // Log the error
    $log_entry = date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n";
    file_put_contents('/tmp/detection_api.log', $log_entry, FILE_APPEND | LOCK_EX);
}
?>
