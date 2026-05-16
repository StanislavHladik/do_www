# Configuration Guide

This document describes all configuration options and files in the system.

## PHP Configuration

### Upload Limits

Located in `/etc/php/8.x/apache2/php.ini`:

```ini
; Maximum file upload size
upload_max_filesize = 512M

; Maximum POST data size (should be >= upload_max_filesize)
post_max_size = 512M

; Maximum execution time for scripts
max_execution_time = 300

; Memory limit for PHP scripts
memory_limit = 256M
```

**Check current settings:**
```bash
php -r "echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . PHP_EOL;"
php -r "echo 'post_max_size: ' . ini_get('post_max_size') . PHP_EOL;"
```

Or visit: `http://your-server/check_upload_limits.php`

---

## Machine Configuration

### Directory Pattern

Machines are configured via directory naming:

```
/home/yolo/st{NUMBER}_{NAME}/
```

| Component | Description | Example |
|-----------|-------------|---------|
| `{NUMBER}` | Machine ID (1-100) | 1, 2, 99, 100 |
| `{NAME}` | Machine description | trenink, test, line1 |

### Detection Configuration

**File:** `/home/yolo/st{N}_*/Detekce_Obrazu/config/detekce_ulozeni.json`

```json
{
    "model_path": "/home/yolo/st1_example/Detekce_Obrazu/models/best.pt",
    "model_name": "best.pt",
    "last_updated": "2026-02-20 10:30:00"
}
```

| Field | Type | Description |
|-------|------|-------------|
| `model_path` | string | Full path to the .pt model file |
| `model_name` | string | Model filename |
| `last_updated` | string | Timestamp of last change |

---

## Training Configuration

### Training Config File

**File:** `/home/yolo/st99_trenink/Detekce_Obrazu/trenink.json`

```json
{
    "dataset_name": "my_dataset",
    "dataset_path": "/home/yolo/st99_trenink/Detekce_Obrazu/datasets/my_dataset",
    "epochs": 100,
    "batch_size": 16,
    "img_size": 1920,
    "model_type": "yolov5s.pt",
    "model_name": "custom_model",
    "machine_number": 99
}
```

### Training Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `epochs` | int | 100 | Number of training iterations |
| `batch_size` | int | 16 | Images per batch (reduce for less VRAM) |
| `img_size` | int | 1920 | Input image resolution |
| `model_type` | string | yolov5s.pt | Base model (yolov5s, yolov5m, yolov5l, yolov5x) |

### Base Model Options

| Model | Size | Speed | Accuracy | VRAM |
|-------|------|-------|----------|------|
| yolov5n.pt | 1.9 MB | Fastest | Lower | ~2 GB |
| yolov5s.pt | 7.2 MB | Fast | Good | ~4 GB |
| yolov5m.pt | 21 MB | Medium | Better | ~6 GB |
| yolov5l.pt | 46 MB | Slower | High | ~8 GB |
| yolov5x.pt | 86 MB | Slowest | Highest | ~12 GB |

---

## Gallery Configuration

### HTML Config

**File:** `/var/www/html/nahledy/{N}/html_config.json`

```json
{
    "refresh_interval": 5000,
    "max_images": 50,
    "display_mode": "grid"
}
```

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `refresh_interval` | int | 5000 | Auto-refresh interval (ms) |
| `max_images` | int | 50 | Maximum images to display |
| `display_mode` | string | grid | Display mode (grid/list) |

---

## Dataset Structure

Datasets must follow this structure:

```
dataset_name/
├── data.yaml          # Class definitions
├── train/
│   ├── images/        # Training images
│   │   ├── img001.jpg
│   │   └── ...
│   └── labels/        # YOLO format labels
│       ├── img001.txt
│       └── ...
└── val/
    ├── images/        # Validation images
    └── labels/        # Validation labels
```

### data.yaml Format

```yaml
train: ./train/images
val: ./val/images

nc: 2  # Number of classes
names: ['defect', 'ok']  # Class names
```

### Label Format (YOLO)

Each `.txt` file contains lines in format:
```
class_id center_x center_y width height
```

Example:
```
0 0.5 0.5 0.1 0.2
1 0.3 0.7 0.15 0.1
```

All values are normalized (0-1).

---

## Environment Variables

For shell scripts and Python:

```bash
# Base paths
export YOLO_BASE="/home/yolo"
export TRAINING_DIR="/home/yolo/st99_trenink/Detekce_Obrazu"
export VENV_PATH="/home/yolo/st99_trenink/Detekce_Obrazu/Detekce_Obrazu_venv"

# Python activation
source $VENV_PATH/bin/activate
```

---

## Logging Configuration

### Log File Locations

| Log | Path | Description |
|-----|------|-------------|
| Detection API | `/tmp/detection_api.log` | API requests |
| Training | `/home/yolo/st99_trenink/Detekce_Obrazu/log/start_training.log` | Training startup |
| Upload Errors | `/www/html/upload_dataset_errors.log` | Upload failures |
| Apache Error | `/var/log/apache2/error.log` | PHP errors |
| Apache Access | `/var/log/apache2/access.log` | HTTP requests |

### Log Rotation

Add to `/etc/logrotate.d/detection`:

```
/home/yolo/st*/Detekce_Obrazu/log/*.log {
    daily
    rotate 7
    compress
    missingok
    notifempty
}
```

---

## Security Considerations

### File Permissions

```bash
# Web files (read-only)
chmod 644 /var/www/html/*.php

# Data directories (read-write)
chmod 755 /var/www/html/nahledy
chmod 755 /var/www/html/vysledky

# Config files
chmod 600 /home/yolo/st*/Detekce_Obrazu/config/*.json
```

### Restrict Access to Sensitive Files

In `.htaccess`:

```apache
<FilesMatch "\.(json|log|sh)$">
    Require all denied
</FilesMatch>
```

---

## Related Documentation

- [INSTALLATION.md](INSTALLATION.md) - Installation guide
- [TRAINING_SYSTEM_DOCUMENTATION.md](TRAINING_SYSTEM_DOCUMENTATION.md) - Training details
- [API_REFERENCE.md](API_REFERENCE.md) - API documentation
