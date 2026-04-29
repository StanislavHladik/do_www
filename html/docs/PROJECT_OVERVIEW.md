# Project Overview - Systém Detekce Obrazu

## Description

This is a web-based **AI Image Detection System** (Systém Detekce Obrazu) built with PHP. The system manages multiple workstations (machines) for image detection, AI model training, and result visualization using YOLOv5.

## Key Features

- **Multi-machine Support**: Manage multiple detection workstations (st1, st2, st99, st100, etc.)
- **AI Model Training**: Train YOLOv5 models with custom datasets
- **Dataset Management**: Upload and manage training datasets (ZIP files)
- **Model Selection**: Choose and apply different AI models per machine
- **Live Image Gallery**: View detection results in real-time
- **Service Control**: Start, stop, and restart detection services

## Technology Stack

| Component | Technology |
|-----------|------------|
| Backend | PHP 7/8 |
| Frontend | HTML5, CSS3, JavaScript |
| JavaScript Library | jQuery 3.7.1 |
| Icons | Font Awesome 4.7.0 |
| AI Framework | YOLOv5 (Python) |
| Virtual Environment | Python venv |

## Directory Structure

```
/var/www/html/
├── index.php              # Main entry - machine selection
├── nahledy.php            # Image gallery view
├── prehled.php            # Overview page
├── train.php              # Training interface
├── models_offer.php       # Model selection page
├── detection_api.php      # Main API endpoint
├── upload_dataset.php     # Dataset upload handler
├── start_training.php     # Training launcher
├── stop_training.php      # Training stopper
├── get_*.php              # Various data endpoints
├── header.php             # Common header
├── footer.php             # Common footer with controls
├── functions.php          # Utility functions
├── css/                   # Stylesheets
├── script/                # JavaScript files
├── views/                 # PHP view partials
├── nahledy/               # Preview images storage
├── nahledy_vysledky/      # Detection results storage
├── vysledky/              # Results with labels
└── docs/                  # Documentation
```

## Machine Naming Convention

Machines are organized in `/home/yolo/` with the pattern:

```
st{number}_{name}/
├── Detekce_Obrazu/
│   ├── datasets/        # Training datasets
│   ├── models/          # Trained models (.pt files)
│   ├── config/          # Configuration files
│   ├── log/             # Log files
│   └── yolov5/          # YOLOv5 framework
```

### Special Machines

| Number | Purpose |
|--------|---------|
| 1-98 | Production workstations |
| 99 | Training workstation |
| 100 | Test workstation |

## Quick Start

1. Access the system via web browser: `http://your-server/`
2. Select a workstation from the main page
3. Use the navigation to:
   - View detection images
   - Select AI models
   - Train new models
   - Upload datasets

## Related Documentation

- [API Reference](API_REFERENCE.md)
- [Training Quick Start](TRAINING_QUICK_START.md)
- [Training System Documentation](TRAINING_SYSTEM_DOCUMENTATION.md)
