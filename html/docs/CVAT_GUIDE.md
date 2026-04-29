# CVAT Annotation Guide

Guide for using CVAT (Computer Vision Annotation Tool) for dataset creation and labeling.

## Overview

CVAT is used to annotate images for training the YOLOv5 detection models. It provides a web-based interface for creating bounding boxes and labels.

**Installation Location:** `/home/yolo/cvat/`

---

## Accessing CVAT

### Start CVAT

```bash
cd /home/yolo/cvat
docker-compose up -d
```

### Access Web Interface

Open browser: `http://localhost:8080`

Default credentials:
- Username: `admin` (or as configured)
- Password: Set during installation

---

## Workflow Overview

```
1. Capture Images (Detection System)
         │
         ▼
2. Upload to CVAT
         │
         ▼
3. Create Annotation Task
         │
         ▼
4. Draw Bounding Boxes
         │
         ▼
5. Export in YOLO Format
         │
         ▼
6. Upload to Training Machine
         │
         ▼
7. Train Model
```

---

## Creating a Task

### Step 1: Create New Task

1. Click "Create Task" or "+"
2. Fill in details:
   - **Name**: Descriptive name (e.g., "Operka_2026_02")
   - **Labels**: Define your classes

### Step 2: Define Labels

Example labels for detection:
```
defect
ok
scratch
dent
```

### Step 3: Upload Images

- Drag and drop images
- Or select from file browser
- Supported formats: JPG, PNG, BMP

### Step 4: Submit Task

Click "Submit" to create the task.

---

## Annotation Process

### Drawing Bounding Boxes

1. Select the label from dropdown
2. Choose "Rectangle" tool
3. Click and drag to draw box around object
4. Adjust corners if needed

### Keyboard Shortcuts

| Key | Action |
|-----|--------|
| `N` | Create new rectangle |
| `D` | Next frame |
| `A` | Previous frame |
| `F` | Finish annotation |
| `Del` | Delete selected |
| `Ctrl+S` | Save |
| `Ctrl+Z` | Undo |

### Best Practices

1. **Tight Boxes**: Draw boxes close to object edges
2. **Consistent Labels**: Use same label for same objects
3. **All Objects**: Annotate every instance of each class
4. **Edge Cases**: Include partially visible objects
5. **Quality Images**: Skip blurry or unusable images

---

## Exporting Annotations

### Export as YOLO Format

1. Open completed task
2. Click "Export" menu
3. Select **"YOLO 1.1"** format
4. Download ZIP file

### Export Structure

```
export.zip/
├── obj.names          # Class names
├── obj.data           # Dataset configuration
├── train.txt          # List of training images
└── obj_train_data/
    ├── image1.jpg
    ├── image1.txt
    ├── image2.jpg
    ├── image2.txt
    └── ...
```

### YOLO Label Format

Each `.txt` file contains:
```
class_id center_x center_y width height
```

Example:
```
0 0.543 0.412 0.234 0.156
1 0.234 0.678 0.123 0.089
```

Values are normalized (0-1).

---

## Preparing Dataset for Training

### Convert CVAT Export to Training Format

```bash
# Create dataset structure
mkdir -p dataset_name/{train,val}/images
mkdir -p dataset_name/{train,val}/labels

# Move images and labels
# Split 80% train, 20% validation
```

### Create data.yaml

```yaml
# dataset_name/data.yaml
train: ./train/images
val: ./val/images

nc: 2  # Number of classes
names: ['defect', 'ok']  # Class names in order
```

### Upload to Training Machine

```bash
# Copy to training machine
scp -r dataset_name yolo@server:/home/yolo/st99_trenink/Detekce_Obrazu/datasets/
```

---

## CVAT Project Structure

### Directory Structure

```
/home/yolo/cvat/
├── cvat/                 # Core CVAT code
│   ├── apps/
│   └── settings/
├── cvat-ui/              # Web interface
├── cvat-core/            # Core library
├── cvat-sdk/             # Python SDK
├── cvat-cli/             # Command line interface
├── ai-models/            # Auto-annotation models
├── docker-compose.yml    # Docker configuration
└── components/           # Additional components
```

### Docker Services

```bash
# View running services
docker-compose ps

# Common services:
# - cvat_server
# - cvat_ui
# - cvat_redis
# - cvat_db
```

---

## Auto-Annotation

CVAT supports AI-assisted annotation.

### Available Models

Located in `/home/yolo/cvat/ai-models/`:
- Pre-trained YOLO models
- Custom models can be added

### Using Auto-Annotation

1. Open annotation task
2. Click "AI Tools" or "Auto Annotate"
3. Select model
4. Review and correct annotations
5. Save

---

## Backup and Export

### Backup Tasks

```bash
# Export task with images
# Use CVAT format for full backup
```

### Backup Database

```bash
cd /home/yolo/cvat
docker-compose exec cvat_db pg_dump -U cvat cvat > backup.sql
```

---

## Troubleshooting

### CVAT Won't Start

```bash
# Check Docker status
docker-compose ps

# View logs
docker-compose logs cvat_server

# Restart services
docker-compose restart
```

### Slow Performance

1. Check available disk space
2. Reduce image resolution before upload
3. Process smaller batches
4. Check server resources

### Export Fails

1. Ensure all frames are saved
2. Check annotations are valid
3. Try different export format
4. Check disk space

---

## Integration with Training

### Automatic Dataset Preparation Script

```bash
#!/bin/bash
# prepare_dataset.sh

CVAT_EXPORT="$1"
DATASET_NAME="$2"
OUTPUT_DIR="/home/yolo/st99_trenink/Detekce_Obrazu/datasets/$DATASET_NAME"

# Extract
unzip "$CVAT_EXPORT" -d temp_export

# Create structure
mkdir -p "$OUTPUT_DIR"/{train,val}/images
mkdir -p "$OUTPUT_DIR"/{train,val}/labels

# Split and copy (80/20)
# ... (implementation)

# Create data.yaml
# ... (implementation)

echo "Dataset prepared at: $OUTPUT_DIR"
```

---

## Related Documentation

- [TRAINING_QUICK_START.md](TRAINING_QUICK_START.md) - Start training
- [TRAINING_SYSTEM_DOCUMENTATION.md](TRAINING_SYSTEM_DOCUMENTATION.md) - Training details
- [HOME_YOLO_STRUCTURE.md](HOME_YOLO_STRUCTURE.md) - Directory structure
