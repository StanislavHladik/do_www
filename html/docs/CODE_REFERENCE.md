# Code Reference

This document provides code snippets and implementation details for key components.

## PHP Components

### functions.php

Utility function for including PHP files with variables:

```php
<?php
/**
 * Include a PHP file with variables available in its scope
 * 
 * @param string $filePath Path to the file to include
 * @param array $variables Variables to make available
 * @param bool $print Whether to print output
 * @return string|null Output buffer contents
 */
function includeWithVariables($filePath, $variables = array(), $print = true)
{
    $output = NULL;
    if(file_exists($filePath)){
        // Extract variables into local scope
        extract($variables);
        // Start output buffering
        ob_start();
        // Include the file
        include $filePath;
        // Get buffered content
        $output = ob_get_clean();
    }
    if ($print) {
        print $output;
    }
    return $output;
}
?>
```

### header.php Structure

Common header with parameter handling:

```php
<?php
// Get URL parameters with defaults
if (isset($_GET['cisloStroj']) && isset($_GET['nazevStroj']) && isset($_GET['popisStroj'])) 
{
    $cisloStroj = htmlspecialchars($_GET['cisloStroj']);
    $nazevStroj = htmlspecialchars($_GET['nazevStroj']);
    $popisStroj = htmlspecialchars($_GET['popisStroj']);
} 
else 
{
    // Default values
    $cisloStroj = "1";
    $nazevStroj = "testovaci_pracoviste";
    $popisStroj = "Kontrola svárů- Flídr Metal s.r.o.";
}
?>

<!DOCTYPE html>
<html>  
    <head>
        <script src="script/jquery-3.7.1.min.js"></script>
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/navigation.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <title>Detekce obrazu</title>
    </head>
    <body>   
        <header class="site-header">
            <h1><?php echo($popisStroj); ?></h1>
            <p>Pracoviště č. <?php echo($cisloStroj); ?> - <?php echo($nazevStroj); ?></p>
            
            <nav class="navigation-buttons">
                <!-- Navigation links -->
            </nav>
        </header>
```

---

## JavaScript Patterns

### API Call Pattern

```javascript
async function callDetectionAPI(action, data = {}) {
    try {
        const response = await fetch('detection_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: action,
                machine_number: window.cisloStroj,
                ...data
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            return result;
        } else {
            console.error('API Error:', result.message);
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Request failed:', error);
        throw error;
    }
}

// Usage examples
async function restartService() {
    const result = await callDetectionAPI('restart_service');
    showStatus(result.message);
}

async function getCurrentModel() {
    const result = await callDetectionAPI('get_current_model');
    return result.model_path;
}
```

### Gallery Auto-Refresh

```javascript
let previousImages = [];
const REFRESH_INTERVAL = 5000; // 5 seconds

function loadGallery() {
    const url = `get_images.php?cisloStroj=${window.cisloStroj}&nazevStroj=${window.nazevStroj}&popisStroj=${window.popisStroj}`;
    
    fetch(url)
        .then(response => response.json())
        .then(images => {
            // Check for new images
            if (JSON.stringify(images) !== JSON.stringify(previousImages)) {
                previousImages = images;
                renderGallery(images);
            }
        })
        .catch(error => console.error('Gallery load failed:', error));
}

function renderGallery(images) {
    const gallery = document.getElementById('gallery');
    gallery.innerHTML = images.map(img => `
        <div class="gallery-item">
            <img src="${img}" alt="Detection image" loading="lazy">
        </div>
    `).join('');
}

// Start auto-refresh
setInterval(loadGallery, REFRESH_INTERVAL);
loadGallery(); // Initial load
```

### Status Display Updates

```javascript
function showStatus(message, type = 'info') {
    const statusDisplay = document.getElementById('statusDisplay');
    const statusText = document.getElementById('statusText');
    
    const colors = {
        info: '#2196F3',
        success: '#4CAF50',
        warning: '#FF9800',
        error: '#f44336'
    };
    
    statusDisplay.style.borderLeftColor = colors[type] || colors.info;
    statusText.innerText = message;
}

// Usage
showStatus('Processing...', 'warning');
showStatus('Completed!', 'success');
showStatus('Error occurred', 'error');
```

---

## Python Components

### LogModule.py

Unified logging module:

```python
#!/usr/bin/python3
import os
import sys
import logging
from logging.handlers import TimedRotatingFileHandler

def get_script_path():
    """Get directory of the running script"""
    return os.path.dirname(os.path.realpath(sys.argv[0]))

def setup_logger(logger_name: str) -> logging.Logger:
    """
    Set up logger with file handler.
    
    Args:
        logger_name: Name for the logger (typically module name)
    
    Returns:
        Configured logger instance
    """
    # Suppress noisy loggers
    logging.getLogger("opcua").setLevel(logging.ERROR)
    
    logger = logging.getLogger(logger_name)
    
    # Create log file path
    log_file = f"{get_script_path()}/log/{logger_name}.log"
    
    # Configure file handler
    file_handler = logging.FileHandler(log_file, "wt", "utf-8")
    file_handler.setFormatter(
        logging.Formatter("%(asctime)s - %(name)s - %(levelname)s - %(message)s")
    )
    
    logger.setLevel(logging.DEBUG)
    logger.addHandler(file_handler)
    
    return logger

def log_and_print(logger, message, type_of_log="INFO"):
    """
    Log message and print to console.
    
    Args:
        logger: Logger instance
        message: Message to log
        type_of_log: Log level (INFO, WARNING, ERROR, DEBUG)
    """
    print(f"[{type_of_log}] {message}")
    
    if type_of_log == "INFO":
        logger.info(message)
    elif type_of_log == "WARNING":
        logger.warning(message)
    elif type_of_log == "ERROR":
        logger.error(message)
    elif type_of_log == "DEBUG":
        logger.debug(message)
```

### trenink.py (Training Script)

```python
#!/usr/bin/python3
from yolov5.train import run
import json
import sys
import os

def get_script_path():
    """Get directory of the running script"""
    return os.path.dirname(os.path.realpath(sys.argv[0]))

def train(param_imgsz, param_epochs, param_data, param_weights, param_batch_size):
    """
    Run YOLOv5 training with specified parameters.
    
    Args:
        param_imgsz: Image size for training
        param_epochs: Number of training epochs
        param_data: Path to data.yaml
        param_weights: Path to base weights
        param_batch_size: Training batch size
    """
    model = run(
        imgsz=param_imgsz,
        epochs=param_epochs,
        data=param_data,
        weights=param_weights,
        batch_size=param_batch_size
    )
    return model

if __name__ == "__main__":
    # Load configuration
    config_path = get_script_path() + "/trenink.json"
    
    with open(config_path, "r") as f:
        config = json.load(f)
    
    # Extract parameters
    train(
        param_imgsz=config["imgsz"],
        param_epochs=config["epochs"],
        param_data=config["data"],
        param_weights=config["weights"],
        param_batch_size=config["batch_size"]
    )
```

### PLC Communication Helpers

```python
import snap7

def connect_plc(ip_address, rack=0, slot=1):
    """
    Connect to Siemens S7 PLC.
    
    Args:
        ip_address: PLC IP address
        rack: PLC rack number
        slot: PLC slot number
    
    Returns:
        snap7.client.Client instance
    """
    client = snap7.client.Client()
    client.connect(ip_address, rack, slot)
    return client

def read_plc_byte(client, db_number, offset):
    """
    Read single byte from PLC data block.
    
    Args:
        client: Connected Snap7 client
        db_number: Data block number
        offset: Byte offset in data block
    
    Returns:
        Byte value as integer
    """
    data = client.db_read(db_number, offset, 1)
    return snap7.util.get_byte(data, 0)

def write_plc_byte(client, db_number, offset, value):
    """
    Write single byte to PLC data block.
    
    Args:
        client: Connected Snap7 client
        db_number: Data block number
        offset: Byte offset in data block
        value: Byte value to write (0-255)
    """
    data = bytearray(1)
    data[0] = value.to_bytes(1, byteorder='big')[0]
    client.db_write(db_number, offset, data)

def disconnect_plc(client):
    """Disconnect from PLC"""
    client.disconnect()
```

### Camera Initialization (Basler Pylon)

```python
from pypylon import pylon

def initialize_cameras(serial_numbers):
    """
    Initialize Basler cameras by serial number.
    
    Args:
        serial_numbers: List of dicts with 'serial' and 'order' keys
    
    Returns:
        List of camera instances
    """
    cameras = []
    
    # Get transport layer factory
    tlFactory = pylon.TlFactory.GetInstance()
    devices = tlFactory.EnumerateDevices()
    
    for cam_config in serial_numbers:
        serial = cam_config['serial']
        
        # Find device by serial number
        for device in devices:
            if device.GetSerialNumber() == serial:
                camera = pylon.InstantCamera(tlFactory.CreateDevice(device))
                camera.Open()
                cameras.append({
                    'camera': camera,
                    'serial': serial,
                    'order': cam_config['order']
                })
                break
    
    # Sort by order
    cameras.sort(key=lambda x: x['order'])
    
    return cameras

def capture_image(camera):
    """
    Capture single image from camera.
    
    Args:
        camera: Pylon InstantCamera instance
    
    Returns:
        numpy array of image
    """
    camera.StartGrabbing(pylon.GrabStrategy_LatestImageOnly)
    
    with camera.RetrieveResult(5000) as result:
        if result.GrabSucceeded():
            image = result.Array
            return image
    
    return None

def close_cameras(cameras):
    """Close all camera connections"""
    for cam in cameras:
        cam['camera'].Close()
```

---

## Shell Scripts

### start_st{N}.sh

```bash
#!/bin/bash
# Startup script for detection machine

SCRIPT_DIR="/home/yolo/st1_operky/Detekce_Obrazu"

cd "$SCRIPT_DIR"

while true
do
    echo "$(date): Starting detection script..."
    python3 detekce_ulozeni.py
    
    EXIT_CODE=$?
    echo "$(date): Script exited with code $EXIT_CODE"
    
    # Wait before restart
    sleep 5
done
```

### service_restart_monitor.sh (Excerpt)

```bash
#!/bin/bash

CHECK_INTERVAL=2
LOG_FILE="/var/log/service_restart_monitor.log"
REQUEST_FILE="/home/yolo/services_configuration/restart_service.json"

log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

process_restart_request() {
    local json_file="$1"
    
    # Read request details
    local service_name=$(jq -r '.service_name' "$json_file")
    local status=$(jq -r '.status' "$json_file")
    
    if [ "$status" = "pending" ]; then
        log_message "Processing restart for: $service_name"
        
        # Update status to processing
        jq '.status = "processing"' "$json_file" > tmp.json && mv tmp.json "$json_file"
        
        # Restart service
        if systemctl restart "$service_name"; then
            jq '.status = "completed"' "$json_file" > tmp.json && mv tmp.json "$json_file"
            log_message "Successfully restarted: $service_name"
        else
            jq '.status = "failed"' "$json_file" > tmp.json && mv tmp.json "$json_file"
            log_message "Failed to restart: $service_name"
        fi
    fi
}

# Main loop
while true; do
    if [ -f "$REQUEST_FILE" ]; then
        process_restart_request "$REQUEST_FILE"
    fi
    sleep $CHECK_INTERVAL
done
```

---

## CSS Patterns

### Common Button Styles

```css
.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s;
}

.btn-primary {
    background-color: #2196F3;
    color: white;
}

.btn-primary:hover {
    background-color: #1976D2;
}

.btn-success {
    background-color: #4CAF50;
    color: white;
}

.btn-warning {
    background-color: #FF9800;
    color: white;
}

.btn-danger {
    background-color: #f44336;
    color: white;
}
```

### Alert/Status Box Styles

```css
.alert {
    padding: 15px;
    margin: 10px 0;
    border-radius: 4px;
    border-left: 4px solid;
}

.alert-info {
    background-color: #e3f2fd;
    border-left-color: #2196F3;
    color: #1565C0;
}

.alert-success {
    background-color: #e8f5e9;
    border-left-color: #4CAF50;
    color: #2E7D32;
}

.alert-warning {
    background-color: #fff3e0;
    border-left-color: #FF9800;
    color: #E65100;
}

.alert-error {
    background-color: #ffebee;
    border-left-color: #f44336;
    color: #C62828;
}
```

---

## Related Documentation

- [API_REFERENCE.md](API_REFERENCE.md) - API endpoints
- [DEVELOPMENT.md](DEVELOPMENT.md) - Development guide
- [FILE_STRUCTURE.md](FILE_STRUCTURE.md) - Project structure
