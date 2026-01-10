# Training System - Quick Start Guide

## 🚀 Quick Start

### 1. Test the Setup
```bash
# Run the configuration test
/var/www/html/test_training_setup.sh
```

### 2. Access Training Page
Open in browser: `http://your-server/train.php`

### 3. Select a Dataset
- Click **"Konfigurovat"** button on any dataset card
- The training form will reload with dataset info

### 4. Configure Training
Adjust parameters as needed:
- **Epochs**: Number of training iterations (default: 100)
- **Batch Size**: Images per batch (default: 16)
- **Image Size**: Training image resolution (default: 1920)
- **Model Type**: Base weight file to start from
- **Model Name**: Name for output model

### 5. Start Training
- Click **"Zahájit Trénink"** button
- Confirm in the dialog
- Training starts in background

### 6. Monitor Training
```bash
# Find the latest training log
ls -lt /tmp/training_output_*.log | head -1

# Watch training progress in real-time
tail -f /tmp/training_output_2025-12-20_10-30-45.log
```

## 📋 What Happens Behind the Scenes

1. **Form Submit** → JavaScript captures form data
2. **AJAX Request** → POST to `start_training.php`
3. **Write Config** → Creates/updates `trenink.json`:
   ```json
   {
       "imgsz": 1920,
       "epochs": 100,
       "data": "DATASET_NAME.yaml",
       "weights": "/path/to/weights/yolov5s.pt",
       "batch_size": 16
   }
   ```
4. **Launch Python** → Runs in virtual environment:
   ```bash
   nohup /path/to/venv/bin/python3 trenink.py > /tmp/training_output.log 2>&1 &
   ```
5. **Background Training** → Python script reads config and starts training
6. **Monitor Progress** → Check log file for updates

## 🔍 Monitoring Commands

### Check if training is running
```bash
ps aux | grep trenink.py
```

### View current configuration
```bash
cat /home/yolo/st99_trenink/Detekce_Obrazu/trenink.json
```

### List all training logs
```bash
ls -lh /tmp/training_output_*.log
```

### View training output
```bash
tail -100 /tmp/training_output_2025-12-20_10-30-45.log
```

### Real-time monitoring
```bash
tail -f /tmp/training_output_2025-12-20_10-30-45.log
```

### Check GPU usage (if available)
```bash
nvidia-smi
watch -n 1 nvidia-smi  # Updates every second
```

## 🛠️ Troubleshooting

### Training doesn't start
```bash
# Check start_training.php log
tail -50 /tmp/start_training.log

# Check permissions
ls -la /home/yolo/st99_trenink/Detekce_Obrazu/

# Test Python in venv
/home/yolo/st99_trenink/Detekce_Obrazu/Detekce_Obrazu_venv/bin/python3 --version
```

### Form doesn't submit
- Open browser console (F12 → Console tab)
- Check for JavaScript errors
- Verify dataset is selected (must click "Konfigurovat" first)

### Training fails immediately
```bash
# Check the training output log
tail -100 /tmp/training_output_*.log

# Common issues:
# - Dataset YAML not found
# - Weight file missing (should auto-download)
# - Insufficient GPU memory (reduce batch_size or imgsz)
```

### Configuration not saved
```bash
# Check file permissions
ls -l /home/yolo/st99_trenink/Detekce_Obrazu/trenink.json

# Fix if needed
chmod 644 /home/yolo/st99_trenink/Detekce_Obrazu/trenink.json
```

## 📁 Important Paths

| Path | Description |
|------|-------------|
| `/var/www/html/train.php` | Main training page |
| `/var/www/html/start_training.php` | Backend training endpoint |
| `/home/yolo/st99_trenink/Detekce_Obrazu/trenink.py` | Python training script |
| `/home/yolo/st99_trenink/Detekce_Obrazu/trenink.json` | Training configuration |
| `/home/yolo/st99_trenink/Detekce_Obrazu/Detekce_Obrazu_venv` | Virtual environment |
| `/home/yolo/st99_trenink/Detekce_Obrazu/yolov5/weights/` | Weight files directory |
| `/tmp/start_training.log` | Backend endpoint log |
| `/tmp/training_output_*.log` | Training process output |

## 🎯 Default Values

| Parameter | Default | Range |
|-----------|---------|-------|
| Epochs | 100 | 1 - 1000 |
| Batch Size | 16 | 1 - 128 |
| Image Size | 1920 | 1 - 65536 |
| Model Type | yolov8m.pt | Any .pt file |
| Model Name | custom_model | Any string |

## 📊 Expected Training Time

Approximate times (varies by GPU, dataset size, parameters):

| Dataset Size | Epochs | Estimated Time |
|--------------|--------|----------------|
| 100 images | 100 | ~30 minutes |
| 500 images | 100 | ~2 hours |
| 1000 images | 100 | ~4 hours |
| 5000 images | 100 | ~20 hours |

**Note:** These are rough estimates. Actual time depends on:
- GPU performance (or CPU if no GPU)
- Image size
- Batch size
- Model complexity
- Dataset complexity

## ✅ Checklist Before Training

- [ ] Dataset is properly structured (train/images, train/labels, val/images, val/labels)
- [ ] Dataset YAML file exists in datasets directory
- [ ] Virtual environment is set up and working
- [ ] Weight files are available (or will auto-download)
- [ ] Sufficient disk space for results
- [ ] GPU is available and working (optional but recommended)
- [ ] No other training is currently running (if GPU memory limited)

## 🎓 Tips for Better Results

1. **Start Small**: Test with few epochs first to verify setup
2. **Monitor GPU**: Use `nvidia-smi` to check GPU utilization
3. **Adjust Batch Size**: Reduce if getting out-of-memory errors
4. **Image Size**: Higher = better accuracy but slower training
5. **Epochs**: More epochs usually = better results (to a point)
6. **Validation Set**: Ensure you have enough validation images (10-20% of total)

## 📚 Further Reading

- **Full Documentation**: `/var/www/html/TRAINING_SYSTEM_DOCUMENTATION.md`
- **Test Script**: `/var/www/html/test_training_setup.sh`
- **YOLOv5 Docs**: https://docs.ultralytics.com/yolov5/
- **YOLOv8 Docs**: https://docs.ultralytics.com/

## 🆘 Getting Help

If training fails:
1. Check `/tmp/start_training.log` for configuration errors
2. Check `/tmp/training_output_*.log` for training errors
3. Run `/var/www/html/test_training_setup.sh` to verify setup
4. Check browser console (F12) for JavaScript errors
5. Verify dataset structure and YAML file

---

**Ready to train?** Visit `http://your-server/train.php` and get started! 🚀
