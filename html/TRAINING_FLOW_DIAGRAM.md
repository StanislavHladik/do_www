# Training System - Visual Flow Diagram

## Complete Data Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          WEB BROWSER (User)                              │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 1. Visit train.php
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         train.php (Main Page)                            │
│  • Displays dataset cards with image counts                             │
│  • Dataset upload section                                               │
│  • Training configuration form (dynamically loaded)                      │
│  • Training progress panel                                              │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 2. Click "Konfigurovat" on dataset
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│              chooseDataset(name, path) - JavaScript                      │
│  • Creates FormData with dataset info                                   │
│  • Shows loading indicator                                              │
│  • Sends AJAX POST to load_train_config.php                             │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 3. AJAX POST
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                    load_train_config.php (Endpoint)                      │
│  • Receives dataset_name, dataset_path                                  │
│  • Calls includeWithVariables() with parameters                         │
│  • Returns HTML fragment                                                │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 4. Returns HTML
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│          train_configuration_section.php (View Fragment)                 │
│  • Shows selected dataset info                                          │
│  • Form with hidden fields (dataset_name, dataset_path)                 │
│  • Input: epochs, batch_size, img_size                                  │
│  • Dropdown: model_type (from get_weights.php)                          │
│  • Input: model_name                                                    │
│  • Submit button: "Zahájit Trénink"                                     │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 5. HTML injected into #train-config-container
                                    │ 6. attachTrainingFormHandler() called
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                    User Configures Parameters                            │
│  Epochs: 100                                                             │
│  Batch Size: 16                                                          │
│  Image Size: 1920                                                        │
│  Model Type: yolov5s.pt                                                  │
│  Model Name: custom_model                                                │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 7. Click "Zahájit Trénink"
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│            attachTrainingFormHandler() - Form Submit                     │
│  • Prevents default form submission                                     │
│  • Collects all form values                                             │
│  • Validates dataset is selected                                        │
│  • Shows confirmation dialog                                            │
│  • Creates FormData with all parameters                                 │
│  • Disables submit button                                               │
│  • Shows progress panel                                                 │
│  • Sends AJAX POST to start_training.php                                │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 8. AJAX POST with parameters
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                start_training.php (Backend Endpoint)                     │
│                                                                          │
│  Step 1: Receive and validate parameters                                │
│   ✓ dataset_name, dataset_path                                          │
│   ✓ epochs, batch_size, img_size                                        │
│   ✓ model_type, model_name                                              │
│                                                                          │
│  Step 2: Validate paths                                                 │
│   ✓ /home/yolo/st99_trenink/Detekce_Obrazu (exists)                     │
│   ✓ trenink.py (exists)                                                 │
│   ✓ Detekce_Obrazu_venv (exists)                                        │
│   ✓ venv/bin/python3 (exists)                                           │
│                                                                          │
│  Step 3: Build configuration                                            │
│   {                                                                      │
│     "imgsz": 1920,                                                       │
│     "epochs": 100,                                                       │
│     "data": "DATASET_NAME.yaml",                                         │
│     "weights": "/path/to/weights/yolov5s.pt",                            │
│     "batch_size": 16                                                     │
│   }                                                                      │
│                                                                          │
│  Step 4: Write trenink.json                                             │
│   ✓ JSON encoded with pretty print                                      │
│   ✓ Written to /home/yolo/st99_trenink/Detekce_Obrazu/trenink.json      │
│                                                                          │
│  Step 5: Build command                                                  │
│   cd /home/yolo/st99_trenink/Detekce_Obrazu &&                          │
│   nohup /home/yolo/.../Detekce_Obrazu_venv/bin/python3                  │
│   /home/yolo/.../trenink.py                                             │
│   > /tmp/training_output_2025-12-20_12-30-45.log 2>&1 &                 │
│                                                                          │
│  Step 6: Execute command                                                │
│   ✓ exec() runs command in background                                   │
│   ✓ Process detaches from web server (nohup)                            │
│   ✓ Output redirected to log file                                       │
│                                                                          │
│  Step 7: Return JSON response                                           │
│   {                                                                      │
│     "success": true,                                                     │
│     "message": "Trénink byl úspěšně zahájen",                            │
│     "config_file": "...",                                                │
│     "training_log": "/tmp/training_output_...",                          │
│     "config": {...}                                                      │
│   }                                                                      │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 9. Command executed
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│               LINUX SHELL (Background Process)                           │
│                                                                          │
│  cd /home/yolo/st99_trenink/Detekce_Obrazu                               │
│  nohup python3 trenink.py > /tmp/training_output.log 2>&1 &             │
│                                                                          │
│  Process runs in background, detached from web server                   │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 10. Python process starts
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│             trenink.py (Python Training Script)                          │
│                                                                          │
│  Step 1: Read configuration                                             │
│   config = json.load(open("trenink.json"))                              │
│                                                                          │
│  Step 2: Extract parameters                                             │
│   param_imgsz = config["imgsz"]                                          │
│   param_epochs = config["epochs"]                                        │
│   param_data = config["data"]                                            │
│   param_weights = config["weights"]                                      │
│   param_batch_size = config["batch_size"]                                │
│                                                                          │
│  Step 3: Call YOLOv5 training                                           │
│   from yolov5.train import run                                           │
│   model = run(                                                           │
│       imgsz=param_imgsz,                                                 │
│       epochs=param_epochs,                                               │
│       data=param_data,                                                   │
│       weights=param_weights,                                             │
│       batch_size=param_batch_size                                        │
│   )                                                                      │
│                                                                          │
│  All output goes to /tmp/training_output_*.log                          │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 11. Training runs (minutes to hours)
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                    YOLOv5 Training Process                               │
│                                                                          │
│  • Loads dataset                                                         │
│  • Loads/downloads weights                                              │
│  • Initializes model                                                     │
│  • Trains for specified epochs                                          │
│  • Validates after each epoch                                           │
│  • Saves checkpoints                                                     │
│  • Saves final model                                                     │
│  • Logs metrics to console (captured in log file)                       │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 12. Training completes
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                        Training Results                                  │
│                                                                          │
│  Saved in: /home/yolo/st99_trenink/Detekce_Obrazu/runs/train/exp*/      │
│   • best.pt - Best model weights                                        │
│   • last.pt - Last epoch weights                                        │
│   • results.csv - Training metrics                                      │
│   • confusion_matrix.png - Visualization                                │
│   • etc.                                                                │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                    MEANWHILE IN BROWSER...                               │
│                                                                          │
│  • User sees success message immediately after submission               │
│  • Training progress panel shows:                                       │
│    - Status: "Trénink byl úspěšně zahájen"                              │
│    - Config file path                                                   │
│    - Log file path                                                      │
│    - Configuration parameters                                           │
│  • User can monitor log file externally:                                │
│    tail -f /tmp/training_output_*.log                                   │
└─────────────────────────────────────────────────────────────────────────┘
```

## File Interactions

```
┌─────────────────┐
│   Web Browser   │
└────────┬────────┘
         │
         ├─────► train.php ────────┐
         │                         │
         │                         ▼
         │              load_train_config.php
         │                         │
         │                         ▼
         │          train_configuration_section.php ◄──── get_weights.php
         │                         │                           │
         │                         │                           ▼
         │                         │              (scans filesystem for .pt files)
         │                         │
         ▼                         ▼
    JavaScript ──────► start_training.php
    (AJAX POST)                   │
                                  │
                                  ├──► Validates paths
                                  │
                                  ├──► Writes trenink.json ───┐
                                  │                           │
                                  └──► Executes command       │
                                                              │
                                                              ▼
┌────────────────────────────────────────────────────────────────────┐
│  Shell (Background Process)                                        │
│                                                                    │
│  cd /home/yolo/st99_trenink/Detekce_Obrazu                         │
│  nohup venv/bin/python3 trenink.py > /tmp/training_output.log &   │
└────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
                         ┌─────────────────┐
                         │   trenink.py    │◄──── trenink.json
                         └────────┬────────┘
                                  │
                                  ▼
                         ┌─────────────────┐
                         │ yolov5.train    │
                         └────────┬────────┘
                                  │
                                  ▼
                         ┌─────────────────┐
                         │  Training runs  │
                         └────────┬────────┘
                                  │
                                  ▼
                         ┌─────────────────┐
                         │  Model saved    │
                         │  in runs/train/ │
                         └─────────────────┘
```

## Logging Flow

```
start_training.php logs → /tmp/start_training.log
                            (configuration, validation, errors)

trenink.py output       → /tmp/training_output_2025-12-20_12-30-45.log
                            (training progress, metrics, errors)

YOLOv5 tensorboard      → /home/yolo/st99_trenink/Detekce_Obrazu/runs/train/exp*/
                            (training metrics, visualizations)
```

## Key Integration Points

1. **Dataset Selection → Config Load**
   - JavaScript `chooseDataset()` → PHP `load_train_config.php`
   - Passes dataset info via AJAX POST
   - Returns HTML fragment with form

2. **Form Submit → Training Start**
   - JavaScript form handler → PHP `start_training.php`
   - Passes all training parameters
   - Returns JSON status

3. **Config Write → Python Execution**
   - PHP writes `trenink.json`
   - PHP executes shell command
   - Python reads `trenink.json`

4. **Python → YOLOv5**
   - Python script imports `yolov5.train.run`
   - Passes parameters from config
   - YOLOv5 handles actual training

## Success Criteria

✅ User selects dataset  
✅ Form loads with dataset info  
✅ User configures parameters  
✅ Form submits successfully  
✅ Configuration written to trenink.json  
✅ Python script launched in venv  
✅ Training runs in background  
✅ Output logged to file  
✅ User receives confirmation  

## Monitoring Points

📊 **Browser Console** - JavaScript errors, AJAX requests  
📊 **Network Tab** - HTTP requests/responses  
📊 `/tmp/start_training.log` - Backend configuration logs  
📊 `/tmp/training_output_*.log` - Training process output  
📊 `ps aux | grep trenink` - Process status  
📊 `nvidia-smi` - GPU utilization (if available)  
