<?php
/**
 * Stop Training Endpoint
 * 
 * This script handles stopping a running training process:
 * 1. Receives PID from the request (or reads from pid file)
 * 2. Kills the process using the PID
 * 3. Cleans up the PID file
 */

header('Content-Type: application/json; charset=utf-8');

// Log file for debugging
$log_file = '/home/yolo/st99_trenink/Detekce_Obrazu/log/stop_training.log';

function logMessage($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    
    // Ensure log file exists and is writable
    if (!file_exists($log_file)) {
        $dir = dirname($log_file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        touch($log_file);
        chmod($log_file, 0666);
    }
    
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    logMessage("=== Stop Training Request ===");
    
    // Get PID from POST request or from the pid file
    $pid = $_POST['pid'] ?? null;
    $pid_file = '/home/yolo/st99_trenink/Detekce_Obrazu/log/training.pid';
    
    // If no PID provided, try to read from pid file
    if (empty($pid)) {
        if (file_exists($pid_file)) {
            $pid = trim(file_get_contents($pid_file));
            logMessage("Read PID from file: $pid");
        } else {
            throw new Exception('Žádný trénink není evidován (PID soubor neexistuje)');
        }
    }
    
    logMessage("Attempting to stop process with PID: $pid");
    
    // Validate PID is numeric
    if (!is_numeric($pid) || intval($pid) <= 0) {
        throw new Exception('Neplatné PID: ' . $pid);
    }
    
    $pid = intval($pid);
    
    // Check if process exists
    $check_command = "ps -p $pid -o pid= 2>/dev/null";
    exec($check_command, $check_output, $check_return);
    
    if ($check_return !== 0 || empty($check_output)) {
        logMessage("Process $pid not found or already terminated");
        
        // Clean up PID file if it exists
        if (file_exists($pid_file)) {
            unlink($pid_file);
            logMessage("Cleaned up PID file");
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Proces již neběží nebo byl již ukončen',
            'pid' => $pid,
            'was_running' => false
        ]);
        exit;
    }
    
    // Get process info before killing
    $info_command = "ps -p $pid -o pid,ppid,cmd --no-headers 2>/dev/null";
    exec($info_command, $info_output, $info_return);
    $process_info = implode(' ', $info_output);
    logMessage("Process info: $process_info");
    
    // First try SIGTERM (graceful termination)
    $kill_command = "kill -TERM $pid 2>&1";
    exec($kill_command, $kill_output, $kill_return);
    logMessage("SIGTERM sent, return code: $kill_return");
    
    // Wait a moment for graceful shutdown
    sleep(2);
    
    // Check if process is still running
    exec($check_command, $check_output2, $check_return2);
    
    if ($check_return2 === 0 && !empty($check_output2)) {
        // Process still running, use SIGKILL (force kill)
        logMessage("Process still running after SIGTERM, sending SIGKILL");
        $kill_command = "kill -KILL $pid 2>&1";
        exec($kill_command, $kill_output, $kill_return);
        logMessage("SIGKILL sent, return code: $kill_return");
        
        // Wait a moment
        usleep(500000); // 500ms
        
        // Final check
        exec($check_command, $check_output3, $check_return3);
        if ($check_return3 === 0 && !empty($check_output3)) {
            throw new Exception("Nepodařilo se ukončit proces $pid");
        }
    }
    
    // Clean up PID file
    if (file_exists($pid_file)) {
        unlink($pid_file);
        logMessage("PID file removed");
    }
    
    logMessage("Process $pid successfully terminated");
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Trénink byl úspěšně zastaven',
        'pid' => $pid,
        'was_running' => true,
        'process_info' => $process_info
    ]);
    
    logMessage("=== Training stopped successfully ===");
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'log_file' => $log_file
    ]);
}
?>
