# Home Yolo Directory Structure

This document describes the complete structure of the `/home/yolo` directory, which contains all AI detection system components.

## Directory Overview

```
/home/yolo/
├── st1_operky/              # Production machine 1 (armrest detection)
├── st2_plasty/              # Production machine 2 (plastics detection)
├── st3/                     # Production machine 3
├── st99_trenink/            # Training machine (training only)
├── st100_test/              # Test machine
├── Detekce_Obrazu/          # Shared backup & datasets
├── cvat/                    # CVAT annotation tool
├── services_configuration/  # Systemd service management
├── db/                      # Database files
└── www/                     # Additional web files
```

---

## Machine Directories

### Naming Convention

```
st{NUMBER}_{NAME}/
```

| Number Range | Purpose |
|--------------|---------|
| 1-98 | Production workstations |
| 99 | Training machine (dedicated) |
| 100 | Test/development machine |

### Production Machine Structure (st1, st2, etc.)

```
st{N}_{name}/
├── Detekce_Obrazu/
│   ├── config/
│   │   └── detekce_ulozeni.json    # Main configuration
│   ├── models/
│   │   └── *.pt                    # Trained model weights
│   ├── log/
│   │   └── *.log                   # Detection logs
│   ├── connection/                 # PLC connection utilities
│   ├── yolov5/                     # YOLOv5 framework copy
│   ├── detekce_ulozeni.py          # Main detection script
│   ├── LogModule.py                # Logging utilities
│   ├── check_keyboard_press.py     # Manual trigger script
│   └── tests/                      # Test scripts
└── start_st{N}.sh                  # Startup script
```

### Training Machine Structure (st99_trenink)

```
st99_trenink/
└── Detekce_Obrazu/
    ├── config/
    │   └── detekce_ulozeni.json
    ├── datasets/                   # Training datasets
    │   ├── 0217_V5/
    │   │   ├── data.yaml
    │   │   ├── train/
    │   │   │   ├── images/
    │   │   │   └── labels/
    │   │   └── val/
    │   │       ├── images/
    │   │       └── labels/
    │   └── ...
    ├── datasets_yolo_1_1/          # Alternative dataset format
    ├── models/
    │   └── *.pt
    ├── log/
    ├── backup/
    ├── Detekce_Obrazu_venv/        # Python virtual environment
    ├── yolov5/
    │   ├── weights/
    │   │   └── yolov5s.pt          # Base weights
    │   ├── runs/
    │   │   └── train/
    │   │       └── exp{N}/         # Training experiments
    │   │           ├── weights/
    │   │           │   ├── best.pt
    │   │           │   └── last.pt
    │   │           ├── results.csv
    │   │           ├── results.png
    │   │           └── *.jpg       # Training visualizations
    │   ├── train.py
    │   └── detect.py
    ├── trenink.py                  # Training launcher
    ├── trenink.json                # Training configuration
    ├── detekce_ulozeni.py
    └── LogModule.py
```

---

## Key Configuration Files

### detekce_ulozeni.json (Machine Config)

Located at: `/home/yolo/st{N}_*/Detekce_Obrazu/config/detekce_ulozeni.json`

```json
{
    "pouzit_plc": false,
    "adresa_plc": "192.168.45.10",
    "cisloStroj": "1",
    "nazevStroj": "operky",
    "weights_name": "best_operka.pt",
    "camera_serial_numbers": [
        {
            "serial": "24548195",
            "order": 1
        },
        {
            "serial": "24548200",
            "order": 2
        }
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

| Field | Type | Description |
|-------|------|-------------|
| `pouzit_plc` | bool | Use PLC communication |
| `adresa_plc` | string | PLC IP address |
| `cisloStroj` | string | Machine number |
| `nazevStroj` | string | Machine name |
| `weights_name` | string | Model filename to use |
| `camera_serial_numbers` | array | Basler camera serials |
| `pouze_sber_foto` | bool | Photo collection only (no detection) |
| `detekovat` | bool | Enable detection |
| `archivovat` | bool | Archive images |
| `druh_detekce_volba` | int | Detection output type index |
| `druh_detekce` | array | Detection output options |
| `style_of_detection_choice` | int | Detection style index |
| `style_of_detection` | array | Detection style options |

### trenink.json (Training Config)

Located at: `/home/yolo/st99_trenink/Detekce_Obrazu/trenink.json`

```json
{
    "imgsz": 2012,
    "epochs": 700,
    "data": "0217_V5.yaml",
    "weights": "/home/yolo/st99_trenink/Detekce_Obrazu/yolov5/weights/yolov5s.pt",
    "batch_size": 4
}
```

| Field | Type | Description |
|-------|------|-------------|
| `imgsz` | int | Training image size |
| `epochs` | int | Number of training epochs |
| `data` | string | Dataset YAML filename |
| `weights` | string | Base model weights path |
| `batch_size` | int | Training batch size |

---

## Important Directories

### /home/yolo/Detekce_Obrazu/

Shared storage for backups and datasets:

```
Detekce_Obrazu/
├── backup/              # Periodic backups
└── datasets/            # Shared dataset storage
```

### /home/yolo/cvat/

CVAT (Computer Vision Annotation Tool) installation for dataset labeling:

```
cvat/
├── cvat/               # Core CVAT code
├── cvat-ui/            # Web interface
├── cvat-core/          # Core library
├── cvat-sdk/           # SDK
├── cvat-cli/           # Command line interface
├── ai-models/          # AI models for auto-annotation
└── components/         # Additional components
```

### /home/yolo/services_configuration/

Systemd service management tools:

```
services_configuration/
├── service_restart_monitor.sh     # Main monitoring script
├── install_monitor.sh             # Installation script
├── service-restart-monitor.service # Systemd service
├── test_restart.sh                # Test script
└── README.md                      # Documentation
```

---

## Python Scripts

### detekce_ulozeni.py

Main detection script that:
- Connects to Basler cameras via Pylon
- Optionally communicates with PLC via Snap7
- Runs YOLOv5 detection
- Saves results to web directory
- Archives images

**Key Dependencies:**
- `snap7` - PLC communication
- `pypylon` - Basler camera SDK
- `torch` - PyTorch for AI
- `opencv` (cv2) - Image processing
- YOLOv5 framework

### trenink.py

Training launcher script that:
- Reads `trenink.json` configuration
- Calls YOLOv5 `train.py` with parameters
- Saves trained models to `runs/train/exp{N}/weights/`

### LogModule.py

Unified logging module providing:
- File logging with rotation
- Console logging
- Timestamped log entries
- Per-module log files

---

## Startup Scripts

### start_st{N}.sh

Simple startup script for each machine:

```bash
#!/bin/bash
while true
do
    python3 Detekce_Obrazu/detekce_ulozeni.py
    sleep 5
done
```

These are typically managed by systemd services:
- `st1_operky.service`
- `st2_plasty.service`
- etc.

---

## Related Documentation

- [DETECTION_SYSTEM.md](DETECTION_SYSTEM.md) - Detection script details
- [TRAINING_SYSTEM_DOCUMENTATION.md](TRAINING_SYSTEM_DOCUMENTATION.md) - Training details
- [SERVICES.md](SERVICES.md) - Systemd service management
- [CVAT_GUIDE.md](CVAT_GUIDE.md) - Dataset annotation guide
