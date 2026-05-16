# File Structure Documentation

Complete reference of all files in the project.

## Core Pages

### Main Entry Points

| File | Description |
|------|-------------|
| `index.php` | Main landing page - machine selection interface |
| `nahledy.php` | Image gallery - displays detection images for a machine |
| `prehled.php` | Overview page - quick access cards |
| `train.php` | Training interface - dataset selection and training controls |
| `models_offer.php` | Model selection - choose AI models for detection |

### Layout Components

| File | Description |
|------|-------------|
| `header.php` | Common header with navigation, included in all pages |
| `footer.php` | Footer with control panel and JavaScript functions |
| `functions.php` | PHP utility functions (e.g., `includeWithVariables()`) |

---

## API Endpoints

### Detection & Control

| File | Method | Description |
|------|--------|-------------|
| `detection_api.php` | POST | Main API - commands, model selection, service control |
| `get_images.php` | GET | Returns image list for gallery |

### Training

| File | Method | Description |
|------|--------|-------------|
| `start_training.php` | POST | Launch training session |
| `stop_training.php` | POST | Stop running training |
| `get_training_progress.php` | GET | Get training status/progress |
| `upload_dataset.php` | POST | Upload dataset ZIP file |
| `load_train_config.php` | GET | Load training configuration |

### Models & Weights

| File | Method | Description |
|------|--------|-------------|
| `get_weights.php` | GET | List available model weights |

---

## View Partials

Located in `/views/`:

| File | Description |
|------|-------------|
| `train_configuration_section.php` | Training parameters form |
| `upload_section.php` | Dataset upload interface |

---

## Static Assets

### CSS (`/css/`)

| File | Description |
|------|-------------|
| `style.css` | Main global styles |
| `navigation.css` | Navigation menu styles |
| `index.css` | Index page specific styles |
| `train.css` | Training page specific styles |

### JavaScript (`/script/`)

| File | Description |
|------|-------------|
| `jquery-3.7.1.min.js` | jQuery library |
| `navigation.js` | Navigation functionality |

---

## Data Directories

### `/nahledy/`

Preview/thumbnail images organized by machine number.

```
nahledy/
├── html_config.json
├── 1/
│   ├── html_config.json
│   └── *.jpg, *.png
├── 2/
└── 100/
```

### `/nahledy_vysledky/`

Detection result images.

```
nahledy_vysledky/
├── 1/
├── 2/
├── 100/
└── stroj_1/
```

### `/vysledky/`

Detection results with labels.

```
vysledky/
├── 1/
│   └── detekce/
│       └── labels/
├── 2/
│   └── detekce/
│       └── labels/
└── test/
```

### `/json/`

JSON configuration and data files.

```
json/
└── 1.json
```

---

## Test & Utility Files

### Test Files

| File | Description |
|------|-------------|
| `test_detection_api.html` | HTML page to test detection API |
| `test_weights.php` | Test weights retrieval |
| `test_upload_config.php` | Test upload configuration |
| `test_training_setup.sh` | Shell script to test training setup |

### Diagnostic Tools

| File | Description |
|------|-------------|
| `info.php` | PHP info page (`phpinfo()`) |
| `check_xdebug.php` | Check XDebug configuration |
| `check_upload_limits.php` | Check PHP upload limits |
| `check_log_file.sh` | Check log file status |

### Setup Scripts

| File | Description |
|------|-------------|
| `setup_xdebug.sh` | XDebug installation/setup |

---

## Old/Deprecated Files

Located in `/old/`:

| File | Description |
|------|-------------|
| `index.html` | Old static index page |
| `old_get_images.php` | Previous image getter implementation |
| `old_nahledy.php` | Previous gallery implementation |

---

## External Machine Directory Structure

On the server, each machine has its own directory:

```
/home/yolo/
├── st1_machine_name/
│   └── Detekce_Obrazu/
│       ├── config/
│       │   └── detekce_ulozeni.json
│       ├── datasets/
│       ├── models/
│       │   └── *.pt (trained models)
│       ├── log/
│       └── yolov5/
├── st99_trenink/           # Training machine
│   └── Detekce_Obrazu/
│       ├── trenink.json    # Training config
│       ├── trenink.py      # Training script
│       ├── Detekce_Obrazu_venv/  # Python venv
│       └── ...
└── st100_test/             # Test machine
```

---

## Configuration Files

### Per-Machine Config

- `/home/yolo/st{N}_*/Detekce_Obrazu/config/detekce_ulozeni.json`
  - Stores current model selection

### Training Config

- `/home/yolo/st99_trenink/Detekce_Obrazu/trenink.json`
  - Training parameters (epochs, batch size, etc.)

### Gallery Config

- `/var/www/html/nahledy/{N}/html_config.json`
  - Gallery display settings per machine
