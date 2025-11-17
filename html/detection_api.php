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

// Execute the command
try {
    $result = executeDetectionCommand($command, $cisloStroj);
    
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
