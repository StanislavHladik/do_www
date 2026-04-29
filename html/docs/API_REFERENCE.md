# API Reference

This document describes all API endpoints available in the system.

## Detection API

**File:** `detection_api.php`

Main API endpoint for detection operations and machine control.

### Endpoint

```
POST /detection_api.php
Content-Type: application/json
```

### CORS Headers

- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: POST, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type`

---

## Actions

### Save Model Selection

Saves the selected AI model for a specific machine.

**Request:**
```json
{
    "action": "save_model_selection",
    "model_path": "/path/to/model.pt",
    "machine_number": "1"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Model selection saved"
}
```

---

### Restart Detection Service

Restarts the detection service for a machine.

**Request:**
```json
{
    "action": "restart_service",
    "machine_number": "1"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Service restarted successfully"
}
```

---

### Check Restart Status

Check the status of a service restart operation.

**Request:**
```json
{
    "action": "check_restart_status"
}
```

**Response:**
```json
{
    "success": true,
    "status": "running",
    "message": "Service is running"
}
```

---

### Get Current Model

Get the currently selected model for a machine.

**Request:**
```json
{
    "action": "get_current_model",
    "machine_number": "1"
}
```

**Response:**
```json
{
    "success": true,
    "model_path": "/home/yolo/st1_example/Detekce_Obrazu/models/best.pt",
    "model_name": "best.pt"
}
```

---

### Send Command

Send a detection command to the machine.

**Request:**
```json
{
    "command": "take_photo",
    "cisloStroj": "1"
}
```

**Available Commands:**
| Command | Description |
|---------|-------------|
| `take_photo` | Capture a new photo |
| `save_photo` | Save the current photo |
| `start` | Start detection |
| `stop` | Stop detection |
| `status` | Get detection status |
| `restart` | Restart detection |

---

## Get Images API

**File:** `get_images.php`

Returns list of images for a specific machine's gallery.

### Endpoint

```
GET /get_images.php?cisloStroj={number}&nazevStroj={name}&popisStroj={description}
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `cisloStroj` | string | Machine number |
| `nazevStroj` | string | Machine name |
| `popisStroj` | string | Machine description |

### Response

```json
[
    "nahledy/1/image1.jpg",
    "nahledy/1/image2.jpg",
    "nahledy/1/image3.png"
]
```

---

## Upload Dataset API

**File:** `upload_dataset.php`

Handles dataset ZIP file uploads for training.

### Endpoint

```
POST /upload_dataset.php
Content-Type: multipart/form-data
```

### Form Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `dataset_file` | file | ZIP file containing dataset |
| `machine_number` | string | Target machine (default: 99) |
| `dataset_name` | string | Custom name for dataset |

### Response (Success)

```json
{
    "success": true,
    "message": "Dataset uploaded successfully",
    "dataset_path": "/home/yolo/st99_trenink/Detekce_Obrazu/datasets/my_dataset"
}
```

### Response (Error)

```json
{
    "success": false,
    "message": "Error description",
    "php_upload_max": "128M",
    "php_post_max": "128M"
}
```

---

## Start Training API

**File:** `start_training.php`

Launches AI model training.

### Endpoint

```
POST /start_training.php
Content-Type: application/x-www-form-urlencoded
```

### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `dataset_name` | string | required | Name of the dataset |
| `dataset_path` | string | required | Full path to dataset |
| `epochs` | int | 100 | Number of training epochs |
| `batch_size` | int | 16 | Training batch size |
| `img_size` | int | 1920 | Image size for training |
| `model_type` | string | yolov5s.pt | Base model to use |
| `model_name` | string | custom_model | Name for output model |
| `machine_number` | int | 99 | Machine number |

### Response

```json
{
    "success": true,
    "message": "Training started",
    "log_file": "/home/yolo/st99_trenink/Detekce_Obrazu/log/training.log"
}
```

---

## Stop Training API

**File:** `stop_training.php`

Stops a running training session.

### Endpoint

```
POST /stop_training.php
```

### Response

```json
{
    "success": true,
    "message": "Training stopped"
}
```

---

## Get Training Progress API

**File:** `get_training_progress.php`

Returns current training progress and status.

### Endpoint

```
GET /get_training_progress.php
```

### Response

```json
{
    "success": true,
    "status": "training",
    "progress": 45,
    "current_epoch": 45,
    "total_epochs": 100,
    "message": "Training in progress..."
}
```

---

## Get Weights API

**File:** `get_weights.php`

Returns available trained model weights.

### Endpoint

```
GET /get_weights.php?machine_number={number}
```

### Response

```json
{
    "success": true,
    "weights": [
        {
            "name": "best.pt",
            "path": "/home/yolo/st1_example/Detekce_Obrazu/models/best.pt",
            "size": 14500000,
            "modified": "2026-02-20 10:30:00"
        }
    ]
}
```

---

## Load Training Config API

**File:** `load_train_config.php`

Loads training configuration for a dataset.

### Endpoint

```
GET /load_train_config.php?dataset_path={path}
```

### Response

```json
{
    "success": true,
    "config": {
        "epochs": 100,
        "batch_size": 16,
        "img_size": 1920,
        "model_type": "yolov5s.pt"
    }
}
```

---

## Error Codes

| HTTP Code | Meaning |
|-----------|---------|
| 200 | Success |
| 400 | Bad Request - Invalid parameters |
| 405 | Method Not Allowed |
| 500 | Internal Server Error |

## Logging

API requests are logged to:
- `/tmp/detection_api.log` - Detection API calls
- `/home/yolo/st99_trenink/Detekce_Obrazu/log/` - Training logs
- `/www/html/upload_dataset_errors.log` - Upload errors
