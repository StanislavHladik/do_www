# Installation Guide

This guide covers the installation and setup of the Systém Detekce Obrazu.

## Prerequisites

### System Requirements

- **OS**: Linux (Ubuntu/Debian recommended)
- **Web Server**: Apache with PHP support
- **PHP**: Version 7.4 or higher
- **Python**: Version 3.8 or higher
- **GPU**: NVIDIA GPU with CUDA support (for training)

### Required PHP Extensions

- `json`
- `fileinfo`
- `zip`

### Required Software

- Apache2
- PHP-FPM or mod_php
- Python3 with pip
- Git

---

## Installation Steps

### 1. Clone Repository

```bash
cd /var/www
git clone https://github.com/StanislavHladik/do_www.git html
```

### 2. Set Permissions

```bash
# Set web server ownership
sudo chown -R www-data:www-data /var/www/html

# Set directory permissions
sudo chmod -R 755 /var/www/html

# Make data directories writable
sudo chmod -R 777 /var/www/html/nahledy
sudo chmod -R 777 /var/www/html/nahledy_vysledky
sudo chmod -R 777 /var/www/html/vysledky
```

### 3. Configure Apache

Create virtual host configuration:

```apache
<VirtualHost *:80>
    ServerName your-server.local
    DocumentRoot /var/www/html
    
    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/detection_error.log
    CustomLog ${APACHE_LOG_DIR}/detection_access.log combined
</VirtualHost>
```

Enable the site:

```bash
sudo a2ensite detection.conf
sudo systemctl reload apache2
```

### 4. Configure PHP Upload Limits

Edit `/etc/php/8.x/apache2/php.ini`:

```ini
upload_max_filesize = 512M
post_max_size = 512M
max_execution_time = 300
memory_limit = 256M
```

Restart Apache:

```bash
sudo systemctl restart apache2
```

### 5. Setup Machine Directories

Create directories for each machine:

```bash
# Training machine (st99)
sudo mkdir -p /home/yolo/st99_trenink/Detekce_Obrazu/{datasets,models,config,log}

# Test machine (st100)
sudo mkdir -p /home/yolo/st100_test/Detekce_Obrazu/{datasets,models,config,log}

# Production machines (st1, st2, etc.)
sudo mkdir -p /home/yolo/st1_machine1/Detekce_Obrazu/{datasets,models,config,log}

# Set permissions
sudo chown -R www-data:www-data /home/yolo/st*/
```

### 6. Setup Python Virtual Environment

For the training machine:

```bash
cd /home/yolo/st99_trenink/Detekce_Obrazu

# Create virtual environment
python3 -m venv Detekce_Obrazu_venv

# Activate and install dependencies
source Detekce_Obrazu_venv/bin/activate
pip install torch torchvision
pip install ultralytics  # For YOLOv5
pip install numpy pandas opencv-python
```

### 7. Clone YOLOv5

```bash
cd /home/yolo/st99_trenink/Detekce_Obrazu
git clone https://github.com/ultralytics/yolov5.git

# Download base weights
cd yolov5
mkdir weights
wget https://github.com/ultralytics/yolov5/releases/download/v7.0/yolov5s.pt -O weights/yolov5s.pt
wget https://github.com/ultralytics/yolov5/releases/download/v7.0/yolov5m.pt -O weights/yolov5m.pt
```

---

## Verification

### Check Web Server

```bash
curl http://localhost/
```

### Check PHP Info

Visit `http://your-server/info.php` in browser.

### Check Upload Limits

Visit `http://your-server/check_upload_limits.php`

### Test Detection API

```bash
curl -X POST http://localhost/detection_api.php \
  -H "Content-Type: application/json" \
  -d '{"command": "status", "cisloStroj": "1"}'
```

---

## Troubleshooting

### Permission Denied Errors

```bash
# Check Apache user
ps aux | grep apache

# Ensure www-data owns the files
sudo chown -R www-data:www-data /var/www/html
sudo chown -R www-data:www-data /home/yolo/st*/
```

### Upload Failures

1. Check PHP limits: `php -i | grep upload`
2. Check disk space: `df -h`
3. Check error logs: `tail -f /var/log/apache2/error.log`

### Training Not Starting

1. Check Python virtual environment exists
2. Verify `trenink.py` has execute permissions
3. Check log files: `tail -f /home/yolo/st99_trenink/Detekce_Obrazu/log/*.log`

---

## Optional: XDebug Setup

For development/debugging, see [XDEBUG_DEBUGGING_GUIDE.md](XDEBUG_DEBUGGING_GUIDE.md)

```bash
# Quick setup
./setup_xdebug.sh
```

---

## Next Steps

1. Read [PROJECT_OVERVIEW.md](PROJECT_OVERVIEW.md) for system overview
2. Check [API_REFERENCE.md](API_REFERENCE.md) for API documentation
3. See [TRAINING_QUICK_START.md](TRAINING_QUICK_START.md) to start training models
