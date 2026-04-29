# Detection System Documentation

Complete documentation for the Python-based detection system.

## Overview

The detection system (`detekce_ulozeni.py`) is the core component that:
- Captures images from industrial Basler cameras
- Runs YOLOv5 AI detection
- Communicates with PLCs (Siemens S7)
- Archives results for the web interface

---

## System Architecture

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│  Basler Camera  │────▶│  Detection      │────▶│  Web Interface  │
│  (Pylon SDK)    │     │  Script         │     │  (PHP/HTML)     │
└─────────────────┘     └─────────────────┘     └─────────────────┘
                               │
                               ▼
                        ┌─────────────────┐
                        │  PLC (Snap7)    │
                        │  Optional       │
                        └─────────────────┘
```

---

## Dependencies

### Python Packages

```
torch              # PyTorch AI framework
opencv-python      # Image processing (cv2)
Pillow             # Image handling (PIL)
pypylon            # Basler camera SDK
snap7              # Siemens PLC communication
numpy              # Numerical operations
```

### External Requirements

- **Basler Pylon SDK** - For camera communication
- **NVIDIA CUDA** - For GPU acceleration
- **YOLOv5** - Object detection framework

---

## Configuration

### Configuration File

**Path:** `/home/yolo/st{N}_*/Detekce_Obrazu/config/detekce_ulozeni.json`

```json
{
    "pouzit_plc": false,
    "adresa_plc": "192.168.45.10",
    "cisloStroj": "1",
    "nazevStroj": "operky",
    "weights_name": "best_operka.pt",
    "camera_serial_numbers": [
        {"serial": "24548195", "order": 1},
        {"serial": "24548200", "order": 2}
    ],
    "pouze_sber_foto": false,
    "detekovat": true,
    "archivovat": true,
    "druh_detekce_volba": 1,
    "druh_detekce": ["txt", "plc", "io"],
    "style_of_detection_choice": 0,
    "style_of_detection": ["operka", "konektory"]
}
```

### Configuration Options

#### PLC Settings

| Field | Type | Description |
|-------|------|-------------|
| `pouzit_plc` | boolean | Enable/disable PLC communication |
| `adresa_plc` | string | PLC IP address (e.g., "192.168.45.10") |

#### Machine Identity

| Field | Type | Description |
|-------|------|-------------|
| `cisloStroj` | string | Machine number (1-100) |
| `nazevStroj` | string | Machine name/description |

#### Camera Settings

| Field | Type | Description |
|-------|------|-------------|
| `camera_serial_numbers` | array | List of camera configurations |
| `camera_serial_numbers[].serial` | string | Camera serial number |
| `camera_serial_numbers[].order` | int | Camera order (1, 2, ...) |

#### Detection Settings

| Field | Type | Description |
|-------|------|-------------|
| `weights_name` | string | Model file to use (e.g., "best.pt") |
| `detekovat` | boolean | Enable AI detection |
| `pouze_sber_foto` | boolean | Photo collection only mode |
| `archivovat` | boolean | Save images to archive |

#### Detection Output Type

| Field | Description |
|-------|-------------|
| `druh_detekce_volba` | Index into `druh_detekce` array |
| `druh_detekce` | Available output modes |

**Output Modes:**
- `txt` - Save results to text file
- `plc` - Send results to PLC
- `io` - Digital I/O output

#### Detection Style

| Field | Description |
|-------|-------------|
| `style_of_detection_choice` | Index into `style_of_detection` array |
| `style_of_detection` | Available detection styles |

**Detection Styles:**
- `operka` - Armrest detection
- `konektory` - Connector detection

---

## Output Paths

### Web Display Paths

```python
aktualni_nahledy_path = '/var/www/html/nahledy'
aktualni_vysledky_path = '/var/www/html/vysledky'
aktualni_nahledyvysledku_path = '/var/www/html/nahledy_vysledky'
```

### Archive Path

```python
folder_path = '/media/archiv/yolo/'
```

---

## Key Functions

### Camera Operations

```python
# Camera initialization is handled via pypylon
# Cameras are identified by serial number from config
```

### PLC Communication

```python
def read_plc(client, db_number, start_plc_offset):
    """Read a byte from PLC data block"""
    data = client.db_read(db_number, start_plc_offset, 1)
    return snap7.util.get_byte(data, 0)

def write_plc(client, db_number, start_plc_offset, value):
    """Write a byte to PLC data block"""
    data = bytearray(1)
    data[0] = value.to_bytes(1, byteorder='big')[0]
    client.db_write(db_number, start_plc_offset, data)
```

### File Management

```python
def delete_files_in_folder(zacatek, folder):
    """Delete files starting with specific prefix"""
    for filename in os.listdir(folder):
        if filename.startswith(zacatek):
            file_path = os.path.join(folder, filename)
            if os.path.isfile(file_path):
                os.unlink(file_path)
```

### Detection

```python
# YOLOv5 detection is called via:
from yolov5.detect import run

# Detection results are processed and saved
```

---

## Detection Workflow

```
1. Initialize cameras (pypylon)
2. Connect to PLC (if enabled)
3. Enter main loop:
   a. Wait for trigger (PLC or manual)
   b. Capture image(s) from camera(s)
   c. Run YOLOv5 detection
   d. Process results
   e. Output results (txt/plc/io)
   f. Save preview to web directory
   g. Archive if enabled
   h. Update web gallery config
4. Handle errors and restart
```

---

## Gallery Config Update

The script updates a JSON file for the web gallery:

```python
class EventList(list):
    def append(self, item):
        super().append(item)
        self.on_item_appended(item)

    def on_item_appended(self, item):
        with open("/var/www/html/nahledy/html_config.json", "w") as file:
            json.dump(self, file)
```

---

## Logging

Uses unified `LogModule.py`:

```python
import LogModule

# Setup logger
logger = LogModule.setup_logger(logger_name)

# Log messages
LogModule.log_and_print(logger, "Message", type_of_log="INFO")
LogModule.log_and_print(logger, "Error", type_of_log="ERROR")
```

### Log Levels

- `DEBUG` - Detailed debugging information
- `INFO` - General operational information
- `WARNING` - Warning messages
- `ERROR` - Error messages

### Log Files

Located at: `/home/yolo/st{N}_*/Detekce_Obrazu/log/`

---

## Running the Script

### Manual Start

```bash
cd /home/yolo/st1_operky/Detekce_Obrazu
python3 detekce_ulozeni.py
```

### Via Startup Script

```bash
./start_st1.sh
```

### Via Systemd Service

```bash
sudo systemctl start st1_operky.service
sudo systemctl status st1_operky.service
```

---

## Troubleshooting

### Camera Not Found

1. Check camera is connected: `pylon-ip-configurator`
2. Verify serial number in config
3. Check USB/GigE connection

### PLC Connection Failed

1. Verify IP address
2. Check network connectivity: `ping 192.168.45.10`
3. Verify PLC is in RUN mode
4. Check Snap7 permissions

### Detection Not Working

1. Check model file exists in `models/`
2. Verify `weights_name` in config
3. Check CUDA availability: `python3 -c "import torch; print(torch.cuda.is_available())"`
4. Review log files

### Permission Issues

```bash
# Ensure web directories are writable
sudo chmod 777 /var/www/html/nahledy
sudo chmod 777 /var/www/html/vysledky
```

---

## Related Documentation

- [HOME_YOLO_STRUCTURE.md](HOME_YOLO_STRUCTURE.md) - Directory structure
- [CONFIGURATION.md](CONFIGURATION.md) - Configuration guide
- [SERVICES.md](SERVICES.md) - Systemd services
