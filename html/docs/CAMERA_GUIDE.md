# Camera Integration Guide

Documentation for integrating Basler industrial cameras with the detection system.

## Overview

The system uses Basler industrial cameras controlled via the Pylon SDK. Cameras capture images for AI-based defect detection.

---

## Supported Hardware

### Basler Cameras

The system supports Basler area scan cameras:
- **Interface**: GigE Vision, USB3 Vision
- **SDK**: Pylon Camera Software Suite
- **Python**: pypylon wrapper

### Current Installations

| Machine | Camera Serial | Interface | Purpose |
|---------|---------------|-----------|---------|
| st1_operky | 24548195 | GigE | Camera 1 |
| st1_operky | 24548200 | GigE | Camera 2 |
| st2_plasty | 25281453 | GigE | Camera 1 |

---

## Installation

### Pylon SDK

1. Download from [Basler website](https://www.baslerweb.com/en/downloads/software-downloads/)
2. Install Pylon SDK:
   ```bash
   sudo dpkg -i pylon_*.deb
   sudo apt-get install -f
   ```

### pypylon (Python)

```bash
pip install pypylon
```

### Verify Installation

```python
from pypylon import pylon

# List available cameras
tlFactory = pylon.TlFactory.GetInstance()
devices = tlFactory.EnumerateDevices()

for device in devices:
    print(f"Camera: {device.GetSerialNumber()} - {device.GetModelName()}")
```

---

## Configuration

### Camera Configuration in detekce_ulozeni.json

```json
{
    "camera_serial_numbers": [
        {
            "serial": "24548195",
            "order": 1
        },
        {
            "serial": "24548200",
            "order": 2
        }
    ]
}
```

| Field | Type | Description |
|-------|------|-------------|
| `serial` | string | Camera serial number |
| `order` | int | Capture order (1 = first) |

### Finding Serial Numbers

```bash
# Using Pylon IP Configurator
pylon-ip-configurator

# Or via Python
python3 -c "from pypylon import pylon; [print(d.GetSerialNumber()) for d in pylon.TlFactory.GetInstance().EnumerateDevices()]"
```

---

## Code Examples

### Basic Camera Setup

```python
from pypylon import pylon

def setup_camera(serial_number):
    """
    Initialize camera by serial number.
    
    Args:
        serial_number: Camera serial number string
    
    Returns:
        Configured camera instance
    """
    tlFactory = pylon.TlFactory.GetInstance()
    devices = tlFactory.EnumerateDevices()
    
    for device in devices:
        if device.GetSerialNumber() == serial_number:
            camera = pylon.InstantCamera(tlFactory.CreateDevice(device))
            camera.Open()
            
            # Configure camera settings
            camera.ExposureTime.SetValue(5000)  # microseconds
            camera.Gain.SetValue(0)
            
            return camera
    
    raise ValueError(f"Camera {serial_number} not found")
```

### Capture Single Image

```python
def capture_image(camera):
    """
    Capture single image from camera.
    
    Args:
        camera: Open pylon.InstantCamera instance
    
    Returns:
        numpy array of captured image
    """
    camera.StartGrabbing(pylon.GrabStrategy_LatestImageOnly)
    
    grabResult = camera.RetrieveResult(5000, pylon.TimeoutHandling_ThrowException)
    
    if grabResult.GrabSucceeded():
        image = grabResult.Array.copy()
        grabResult.Release()
        camera.StopGrabbing()
        return image
    else:
        grabResult.Release()
        camera.StopGrabbing()
        raise RuntimeError("Image capture failed")
```

### Multi-Camera Capture

```python
def capture_all_cameras(camera_configs):
    """
    Capture images from multiple cameras.
    
    Args:
        camera_configs: List of {'serial': str, 'order': int}
    
    Returns:
        List of (order, image) tuples
    """
    images = []
    tlFactory = pylon.TlFactory.GetInstance()
    
    for config in sorted(camera_configs, key=lambda x: x['order']):
        devices = tlFactory.EnumerateDevices()
        
        for device in devices:
            if device.GetSerialNumber() == config['serial']:
                camera = pylon.InstantCamera(tlFactory.CreateDevice(device))
                camera.Open()
                
                try:
                    image = capture_image(camera)
                    images.append((config['order'], image))
                finally:
                    camera.Close()
                break
    
    return images
```

### Continuous Capture Mode

```python
def continuous_capture(camera, callback, max_images=100):
    """
    Continuously capture images and process with callback.
    
    Args:
        camera: Open camera instance
        callback: Function to call with each image
        max_images: Maximum images to capture
    """
    camera.StartGrabbing(pylon.GrabStrategy_LatestImageOnly)
    
    count = 0
    while camera.IsGrabbing() and count < max_images:
        grabResult = camera.RetrieveResult(5000, pylon.TimeoutHandling_ThrowException)
        
        if grabResult.GrabSucceeded():
            image = grabResult.Array
            callback(image, count)
            count += 1
        
        grabResult.Release()
    
    camera.StopGrabbing()
```

---

## Camera Settings

### Common Parameters

```python
# Exposure time (microseconds)
camera.ExposureTime.SetValue(10000)

# Gain (dB)
camera.Gain.SetValue(0)

# Pixel format
camera.PixelFormat.SetValue("Mono8")  # or "RGB8"

# Image size
camera.Width.SetValue(1920)
camera.Height.SetValue(1080)

# Frame rate
camera.AcquisitionFrameRate.SetValue(30)
camera.AcquisitionFrameRateEnable.SetValue(True)
```

### Auto Features

```python
# Auto exposure
camera.ExposureAuto.SetValue("Continuous")

# Auto gain
camera.GainAuto.SetValue("Continuous")

# Auto white balance (color cameras)
camera.BalanceWhiteAuto.SetValue("Continuous")
```

---

## Network Configuration (GigE)

### IP Configuration

```bash
# Use Pylon IP Configurator
pylon-ip-configurator
```

Or via Python:
```python
from pypylon import pylon

tlFactory = pylon.TlFactory.GetInstance()
devices = tlFactory.EnumerateDevices()

for device in devices:
    if device.GetDeviceClass() == "BaslerGigE":
        # Get current IP
        print(f"Camera: {device.GetSerialNumber()}")
        print(f"IP: {device.GetIpAddress()}")
```

### Jumbo Frames

For best performance, enable jumbo frames:

```bash
# Check current MTU
ip link show eth0

# Set MTU (requires admin)
sudo ip link set eth0 mtu 9000
```

### Persistent Configuration

Edit `/etc/network/interfaces` or use NetworkManager:
```
auto eth0
iface eth0 inet static
    address 192.168.45.1
    netmask 255.255.255.0
    mtu 9000
```

---

## Troubleshooting

### Camera Not Found

1. Check physical connection
2. Verify network configuration:
   ```bash
   ping <camera_ip>
   ```
3. Check firewall:
   ```bash
   sudo ufw status
   sudo ufw allow 3956/udp  # GigE Vision
   ```
4. Run IP configurator:
   ```bash
   pylon-ip-configurator
   ```

### Timeout Errors

1. Check cable quality
2. Increase timeout:
   ```python
   grabResult = camera.RetrieveResult(10000, pylon.TimeoutHandling_ThrowException)
   ```
3. Reduce frame rate
4. Check network bandwidth

### Image Quality Issues

1. Adjust exposure time
2. Check lighting conditions
3. Verify focus
4. Clean lens

### Permission Issues

```bash
# Add user to video group
sudo usermod -aG video $USER

# Reload groups
newgrp video

# Or create udev rule
echo 'SUBSYSTEM=="usb", ATTR{idVendor}=="2676", MODE="0666"' | sudo tee /etc/udev/rules.d/99-basler.rules
sudo udevadm control --reload-rules
```

---

## Integration with Detection

### In detekce_ulozeni.py

```python
# Camera initialization from config
def init_cameras_from_config(config):
    cameras = []
    for cam_config in config['camera_serial_numbers']:
        try:
            camera = setup_camera(cam_config['serial'])
            cameras.append({
                'camera': camera,
                'order': cam_config['order']
            })
        except ValueError as e:
            LogModule.log_and_print(logger, f"Camera init failed: {e}", "ERROR")
    return cameras

# Capture and detect workflow
def capture_and_detect(cameras, model):
    for cam in sorted(cameras, key=lambda x: x['order']):
        image = capture_image(cam['camera'])
        
        # Save preview
        save_preview(image, cam['order'])
        
        # Run detection
        results = detect(model, image)
        
        # Process results
        handle_detection_results(results)
```

---

## Related Documentation

- [DETECTION_SYSTEM.md](DETECTION_SYSTEM.md) - Detection script details
- [CONFIGURATION.md](CONFIGURATION.md) - Configuration guide
- [HOME_YOLO_STRUCTURE.md](HOME_YOLO_STRUCTURE.md) - Directory structure
