<?php
/**
 * Get Training Progress Endpoint
 * 
 * Reads the training log file and parses progress information
 * Returns JSON with epoch progress, losses, and other metrics
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // Get the log file path from request or use the most recent one
    $log_file = $_GET['log_file'] ?? null;
    $pid_file = '/home/yolo/st99_trenink/Detekce_Obrazu/log/training.pid';
    
    // If no specific log file, find the most recent one
    if (empty($log_file)) {
        $log_dir = '/home/yolo/st99_trenink/Detekce_Obrazu/log';
        $log_files = glob($log_dir . '/training_output_*.log');
        
        if (empty($log_files)) {
            echo json_encode([
                'success' => false,
                'running' => false,
                'message' => 'Žádný log soubor nenalezen'
            ]);
            exit;
        }
        
        // Sort by modification time, newest first
        usort($log_files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $log_file = $log_files[0];
    }
    
    // Check if training is still running
    $is_running = false;
    $pid = null;
    
    if (file_exists($pid_file)) {
        $pid = trim(file_get_contents($pid_file));
        if (!empty($pid) && is_numeric($pid)) {
            // Check if process is running
            exec("ps -p $pid -o pid= 2>/dev/null", $output, $return_code);
            $is_running = ($return_code === 0 && !empty($output));
        }
    }
    
    // Check if log file exists
    if (!file_exists($log_file)) {
        echo json_encode([
            'success' => false,
            'running' => $is_running,
            'pid' => $pid,
            'message' => 'Log soubor neexistuje: ' . $log_file
        ]);
        exit;
    }
    
    // Read the last portion of the log file (last 50KB should be enough)
    $file_size = filesize($log_file);
    $read_size = min($file_size, 50 * 1024); // Max 50KB
    
    $fp = fopen($log_file, 'r');
    if ($file_size > $read_size) {
        fseek($fp, -$read_size, SEEK_END);
    }
    $content = fread($fp, $read_size);
    fclose($fp);
    
    // Parse the epoch progress
    // Format: "0/699" or "123/699" - current_epoch/total_epochs
    $current_epoch = 0;
    $total_epochs = 0;
    $batch_progress = 0;
    $total_batches = 0;
    $gpu_mem = '';
    $box_loss = 0;
    $obj_loss = 0;
    $cls_loss = 0;
    
    // Look for epoch pattern like "0/699" or "123/699" at the start of a line
    // Pattern: spaces + epoch/total + spaces + GPU_mem + losses...
    // Example: "      0/699        16G     0.1144     0.2101    0.04819"
    preg_match_all('/^\s*(\d+)\/(\d+)\s+(\d+G)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)/m', $content, $epoch_matches, PREG_SET_ORDER);
    
    if (!empty($epoch_matches)) {
        // Get the last match (most recent progress)
        $last_match = end($epoch_matches);
        $current_epoch = intval($last_match[1]);
        $total_epochs = intval($last_match[2]);
        $gpu_mem = $last_match[3];
        $box_loss = floatval($last_match[4]);
        $obj_loss = floatval($last_match[5]);
        $cls_loss = floatval($last_match[6]);
    }
    
    // Look for batch progress pattern like "83%|████████▎ | 5/6"
    // or simpler: look for percentage in progress bars
    preg_match_all('/(\d+)%\|[█▎▋▌▍▏ ]*\|\s*(\d+)\/(\d+)/', $content, $batch_matches, PREG_SET_ORDER);
    
    if (!empty($batch_matches)) {
        $last_batch = end($batch_matches);
        $batch_progress = intval($last_batch[2]);
        $total_batches = intval($last_batch[3]);
    }
    
    // Calculate overall progress percentage
    $epoch_progress_pct = 0;
    if ($total_epochs > 0) {
        // Each epoch contributes equally to progress
        // Add batch progress within current epoch
        $batch_fraction = ($total_batches > 0) ? ($batch_progress / $total_batches) : 0;
        $epoch_progress_pct = (($current_epoch + $batch_fraction) / $total_epochs) * 100;
    }
    
    // Check for completion message
    $is_completed = (strpos($content, 'Results saved to') !== false) || 
                    (strpos($content, 'Training complete') !== false) ||
                    ($total_epochs > 0 && $current_epoch >= $total_epochs - 1 && $batch_progress >= $total_batches);
    
    // Check for errors
    $has_error = (strpos($content, 'Error') !== false && strpos($content, 'CUDA error') !== false) ||
                 (strpos($content, 'RuntimeError') !== false) ||
                 (strpos($content, 'OutOfMemoryError') !== false);
    
    // Get last few lines for display
    $lines = explode("\n", trim($content));
    $last_lines = array_slice($lines, -10);
    
    // Filter out empty lines and clean up
    $last_lines = array_filter($last_lines, function($line) {
        $trimmed = trim($line);
        return !empty($trimmed) && strpos($trimmed, 'FutureWarning') === false;
    });
    $last_lines = array_values($last_lines);
    
    echo json_encode([
        'success' => true,
        'running' => $is_running,
        'completed' => $is_completed,
        'has_error' => $has_error,
        'pid' => $pid,
        'log_file' => $log_file,
        'progress' => [
            'current_epoch' => $current_epoch,
            'total_epochs' => $total_epochs,
            'batch_progress' => $batch_progress,
            'total_batches' => $total_batches,
            'percentage' => round($epoch_progress_pct, 1)
        ],
        'metrics' => [
            'gpu_mem' => $gpu_mem,
            'box_loss' => $box_loss,
            'obj_loss' => $obj_loss,
            'cls_loss' => $cls_loss
        ],
        'last_lines' => array_slice($last_lines, -5)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
