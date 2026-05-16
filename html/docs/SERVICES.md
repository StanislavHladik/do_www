# Systemd Services Documentation

This document describes the systemd service management for the detection system.

## Overview

Each detection machine runs as a systemd service for:
- Automatic startup on boot
- Process monitoring and restart
- Centralized logging
- Remote management via web interface

---

## Service Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     systemd                                  │
│                                                              │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │ st1_operky      │  │ st2_plasty      │  │ st99_trenink│ │
│  │ .service        │  │ .service        │  │ .service    │ │
│  └────────┬────────┘  └────────┬────────┘  └──────┬──────┘ │
│           │                    │                   │         │
└───────────┼────────────────────┼───────────────────┼─────────┘
            │                    │                   │
            ▼                    ▼                   ▼
    ┌───────────────┐    ┌───────────────┐   ┌───────────────┐
    │detekce_ulozeni│    │detekce_ulozeni│   │detekce_ulozeni│
    │     .py       │    │     .py       │   │     .py       │
    └───────────────┘    └───────────────┘   └───────────────┘
```

---

## Service Files

### Location

```
/etc/systemd/system/st{N}_{name}.service
```

### Example Service File

**File:** `/etc/systemd/system/st1_operky.service`

```ini
[Unit]
Description=Detection Service for Machine 1 (Operky)
After=network.target

[Service]
Type=simple
User=yolo
Group=yolo
WorkingDirectory=/home/yolo/st1_operky/Detekce_Obrazu
ExecStart=/usr/bin/python3 /home/yolo/st1_operky/Detekce_Obrazu/detekce_ulozeni.py
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

### Service Configuration Options

| Option | Value | Description |
|--------|-------|-------------|
| `Type` | simple | Direct script execution |
| `User` | yolo | Run as yolo user |
| `WorkingDirectory` | ... | Script working directory |
| `Restart` | always | Auto-restart on failure |
| `RestartSec` | 5 | Wait 5 seconds before restart |

---

## Service Management

### Basic Commands

```bash
# Start service
sudo systemctl start st1_operky.service

# Stop service
sudo systemctl stop st1_operky.service

# Restart service
sudo systemctl restart st1_operky.service

# Check status
sudo systemctl status st1_operky.service

# View logs
journalctl -u st1_operky.service -f
```

### Enable/Disable Auto-start

```bash
# Enable on boot
sudo systemctl enable st1_operky.service

# Disable on boot
sudo systemctl disable st1_operky.service
```

### List All Detection Services

```bash
systemctl list-units --type=service | grep st
```

---

## Service Restart Monitor

The system includes a web-triggered restart mechanism.

### Components

**Location:** `/home/yolo/services_configuration/`

| File | Description |
|------|-------------|
| `service_restart_monitor.sh` | Main monitoring script |
| `service-restart-monitor.service` | Systemd service definition |
| `install_monitor.sh` | Installation script |
| `test_restart.sh` | Test script |

### How It Works

```
1. Web interface creates restart_service.json
2. Monitor service detects the file
3. Monitor restarts the requested service
4. Monitor updates status in JSON
5. Web interface polls for completion
```

### Restart Request JSON

**Path:** `/home/yolo/services_configuration/restart_service.json`

```json
{
    "timestamp": "2026-02-22 10:30:00",
    "machine_number": "1",
    "service_name": "st1_operky.service",
    "directory_name": "st1_operky",
    "requested_by": "web_interface",
    "status": "pending"
}
```

### Status Values

| Status | Description |
|--------|-------------|
| `pending` | Waiting for processing |
| `processing` | Restart in progress |
| `completed` | Successfully restarted |
| `failed` | Restart failed |

### Install Monitor Service

```bash
cd /home/yolo/services_configuration
sudo ./install_monitor.sh
```

### Monitor Logs

```bash
# Systemd journal
journalctl -u service-restart-monitor -f

# Log file
tail -f /var/log/service_restart_monitor.log
```

---

## Creating New Service

### Step 1: Create Machine Directory

```bash
mkdir -p /home/yolo/st{N}_{name}/Detekce_Obrazu
# Copy necessary files from existing machine
```

### Step 2: Create Service File

```bash
sudo nano /etc/systemd/system/st{N}_{name}.service
```

```ini
[Unit]
Description=Detection Service for Machine {N}
After=network.target

[Service]
Type=simple
User=yolo
Group=yolo
WorkingDirectory=/home/yolo/st{N}_{name}/Detekce_Obrazu
ExecStart=/usr/bin/python3 /home/yolo/st{N}_{name}/Detekce_Obrazu/detekce_ulozeni.py
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

### Step 3: Enable and Start

```bash
sudo systemctl daemon-reload
sudo systemctl enable st{N}_{name}.service
sudo systemctl start st{N}_{name}.service
```

---

## Troubleshooting

### Service Won't Start

```bash
# Check service status
sudo systemctl status st1_operky.service

# View detailed logs
journalctl -u st1_operky.service --no-pager -n 50

# Check for syntax errors
sudo systemd-analyze verify /etc/systemd/system/st1_operky.service
```

### Service Keeps Restarting

1. Check detection script for errors
2. Review logs: `journalctl -u st1_operky.service -f`
3. Test script manually:
   ```bash
   cd /home/yolo/st1_operky/Detekce_Obrazu
   python3 detekce_ulozeni.py
   ```

### Permission Issues

```bash
# Check file ownership
ls -la /home/yolo/st1_operky/

# Fix ownership if needed
sudo chown -R yolo:yolo /home/yolo/st1_operky/
```

### Web Restart Not Working

1. Check monitor service is running:
   ```bash
   systemctl status service-restart-monitor
   ```

2. Check JSON file permissions:
   ```bash
   ls -la /home/yolo/services_configuration/restart_service.json
   ```

3. Check monitor logs:
   ```bash
   tail -f /var/log/service_restart_monitor.log
   ```

---

## Security Considerations

### Service User

- Services run as `yolo` user (not root)
- Limited system access
- Can only write to designated directories

### Monitor Service

- Runs as root (required for systemctl)
- Only restarts services matching `st*_*.service` pattern
- Validates service exists before restart

### Web Interface

- Cannot directly execute system commands
- Uses JSON file as intermediary
- Monitor validates all requests

---

## Related Documentation

- [DETECTION_SYSTEM.md](DETECTION_SYSTEM.md) - Detection script details
- [HOME_YOLO_STRUCTURE.md](HOME_YOLO_STRUCTURE.md) - Directory structure
- [API_REFERENCE.md](API_REFERENCE.md) - Web API documentation
