# PLC Integration Guide

Documentation for integrating Siemens S7 PLCs with the detection system.

## Overview

The detection system can communicate with Siemens S7 PLCs using the Snap7 library. This enables:
- Receiving trigger signals from PLC
- Sending detection results back to PLC
- Automated production line integration

---

## Supported PLCs

- Siemens S7-300
- Siemens S7-400
- Siemens S7-1200
- Siemens S7-1500

---

## Installation

### Snap7 Library

```bash
# Install snap7 Python package
pip install python-snap7

# Install system library (if needed)
sudo apt-get install libsnap7-1 libsnap7-dev
```

### Verify Installation

```python
import snap7

# Create client
client = snap7.client.Client()
print("Snap7 installed successfully")
```

---

## Configuration

### PLC Settings in detekce_ulozeni.json

```json
{
    "pouzit_plc": true,
    "adresa_plc": "192.168.45.10"
}
```

| Field | Type | Description |
|-------|------|-------------|
| `pouzit_plc` | boolean | Enable/disable PLC communication |
| `adresa_plc` | string | PLC IP address |

### PLC Side Configuration

- Enable PUT/GET communication
- Configure data blocks for exchange
- Set appropriate access permissions

---

## Connection

### Basic Connection

```python
import snap7

def connect_to_plc(ip_address, rack=0, slot=1):
    """
    Connect to Siemens S7 PLC.
    
    Args:
        ip_address: PLC IP address
        rack: CPU rack number (usually 0)
        slot: CPU slot number (varies by model)
    
    Returns:
        Connected snap7 client
    """
    client = snap7.client.Client()
    
    try:
        client.connect(ip_address, rack, slot)
        
        if client.get_connected():
            print(f"Connected to PLC at {ip_address}")
            return client
        else:
            raise ConnectionError("Connection failed")
            
    except Exception as e:
        print(f"Connection error: {e}")
        raise

# Slot numbers by model:
# S7-300: slot 2
# S7-400: slot 2 or 3
# S7-1200: slot 1
# S7-1500: slot 1
```

### Connection with Retry

```python
import time

def connect_with_retry(ip_address, rack=0, slot=1, max_retries=5, delay=2):
    """
    Connect to PLC with automatic retry.
    """
    client = snap7.client.Client()
    
    for attempt in range(max_retries):
        try:
            client.connect(ip_address, rack, slot)
            if client.get_connected():
                return client
        except Exception as e:
            print(f"Attempt {attempt + 1} failed: {e}")
            time.sleep(delay)
    
    raise ConnectionError(f"Failed to connect after {max_retries} attempts")
```

---

## Data Exchange

### Reading Data Block

```python
def read_db(client, db_number, start, size):
    """
    Read bytes from a data block.
    
    Args:
        client: Connected snap7 client
        db_number: Data block number
        start: Start byte offset
        size: Number of bytes to read
    
    Returns:
        bytearray of data
    """
    return client.db_read(db_number, start, size)

# Example: Read 10 bytes from DB1 starting at offset 0
data = read_db(client, 1, 0, 10)
```

### Writing Data Block

```python
def write_db(client, db_number, start, data):
    """
    Write bytes to a data block.
    
    Args:
        client: Connected snap7 client
        db_number: Data block number
        start: Start byte offset
        data: bytearray to write
    """
    client.db_write(db_number, start, data)

# Example: Write 2 bytes to DB1 at offset 10
data = bytearray([0x01, 0x02])
write_db(client, 1, 10, data)
```

### Reading Single Byte

```python
def read_plc_byte(client, db_number, offset):
    """
    Read single byte from PLC.
    """
    data = client.db_read(db_number, offset, 1)
    return snap7.util.get_byte(data, 0)
```

### Writing Single Byte

```python
def write_plc_byte(client, db_number, offset, value):
    """
    Write single byte to PLC.
    """
    data = bytearray(1)
    data[0] = value.to_bytes(1, byteorder='big')[0]
    client.db_write(db_number, offset, data)
```

---

## Data Types

### Snap7 Utility Functions

```python
import snap7.util as util

# Read different data types
def read_bool(data, byte_offset, bit_offset):
    return util.get_bool(data, byte_offset, bit_offset)

def read_int(data, byte_offset):
    return util.get_int(data, byte_offset)

def read_real(data, byte_offset):
    return util.get_real(data, byte_offset)

def read_string(data, byte_offset, max_length=254):
    return util.get_string(data, byte_offset, max_length)

# Write different data types
def set_bool(data, byte_offset, bit_offset, value):
    util.set_bool(data, byte_offset, bit_offset, value)

def set_int(data, byte_offset, value):
    util.set_int(data, byte_offset, value)

def set_real(data, byte_offset, value):
    util.set_real(data, byte_offset, value)
```

### Data Type Sizes

| Type | Size (bytes) | Range |
|------|--------------|-------|
| Bool | 1 bit | True/False |
| Byte | 1 | 0-255 |
| Int | 2 | -32768 to 32767 |
| DInt | 4 | -2147483648 to 2147483647 |
| Real | 4 | IEEE 754 float |
| String | 2 + length | Max 254 chars |

---

## Integration Pattern

### Detection System Integration

```python
class PLCInterface:
    """
    PLC interface for detection system.
    """
    
    def __init__(self, config):
        self.enabled = config.get('pouzit_plc', False)
        self.address = config.get('adresa_plc', '')
        self.client = None
        
        # Data block configuration
        self.db_number = 1
        self.trigger_offset = 0
        self.result_offset = 2
        self.status_offset = 4
        
    def connect(self):
        if not self.enabled:
            return False
            
        self.client = snap7.client.Client()
        self.client.connect(self.address, 0, 1)
        return self.client.get_connected()
    
    def disconnect(self):
        if self.client:
            self.client.disconnect()
    
    def wait_for_trigger(self, timeout=5.0):
        """
        Wait for trigger signal from PLC.
        
        Returns:
            True if trigger received, False on timeout
        """
        if not self.enabled:
            return False
            
        start_time = time.time()
        
        while time.time() - start_time < timeout:
            trigger = read_plc_byte(self.client, self.db_number, self.trigger_offset)
            
            if trigger == 1:
                # Reset trigger
                write_plc_byte(self.client, self.db_number, self.trigger_offset, 0)
                return True
            
            time.sleep(0.01)
        
        return False
    
    def send_result(self, is_ok, defect_count=0):
        """
        Send detection result to PLC.
        
        Args:
            is_ok: True if no defects found
            defect_count: Number of defects found
        """
        if not self.enabled:
            return
            
        # Result: 1 = OK, 2 = NOK
        result = 1 if is_ok else 2
        write_plc_byte(self.client, self.db_number, self.result_offset, result)
        
        # Defect count
        write_plc_byte(self.client, self.db_number, self.result_offset + 1, defect_count)
    
    def set_status(self, status):
        """
        Set system status in PLC.
        
        Status codes:
        0 = Idle
        1 = Processing
        2 = Error
        """
        if not self.enabled:
            return
            
        write_plc_byte(self.client, self.db_number, self.status_offset, status)
```

### Usage in Detection Loop

```python
def main_detection_loop(config):
    # Initialize PLC
    plc = PLCInterface(config)
    
    if config.get('pouzit_plc'):
        plc.connect()
        plc.set_status(0)  # Idle
    
    try:
        while True:
            # Wait for trigger (PLC or manual)
            if plc.wait_for_trigger():
                plc.set_status(1)  # Processing
                
                # Capture image
                image = capture_image()
                
                # Run detection
                results = detect(image)
                
                # Send results
                is_ok = len(results['defects']) == 0
                plc.send_result(is_ok, len(results['defects']))
                
                plc.set_status(0)  # Idle
                
    except Exception as e:
        plc.set_status(2)  # Error
        raise
    finally:
        plc.disconnect()
```

---

## Data Block Structure Example

### PLC Data Block (DB1)

| Offset | Name | Type | Description |
|--------|------|------|-------------|
| 0 | Trigger | Byte | 1 = Trigger detection |
| 1 | Ack | Byte | Acknowledgment |
| 2 | Result | Byte | 1 = OK, 2 = NOK |
| 3 | DefectCount | Byte | Number of defects |
| 4 | Status | Byte | 0=Idle, 1=Processing, 2=Error |
| 5 | ErrorCode | Int | Error code |
| 10 | CycleCount | DInt | Total cycles |

---

## Troubleshooting

### Connection Failed

1. **Check network connectivity:**
   ```bash
   ping 192.168.45.10
   ```

2. **Verify PLC is in RUN mode**

3. **Check PUT/GET communication is enabled:**
   - TIA Portal: Device configuration → Protection
   - Enable "Permit access with PUT/GET"

4. **Verify rack/slot numbers:**
   - S7-1200/1500: Usually 0/1
   - S7-300/400: Usually 0/2

### Read/Write Errors

1. **Check data block exists and is accessible**

2. **Verify offsets are correct**

3. **Check data block optimized access:**
   - For optimized DBs, use absolute addresses
   - Or disable "Optimized block access"

4. **Verify user permissions**

### Timeout Errors

1. **Increase timeout:**
   ```python
   client.set_connection_params(ip, 102, 10000)  # 10 second timeout
   ```

2. **Check network quality**

3. **Reduce data block size per read**

---

## Security Considerations

1. **Network Isolation:**
   - Use separate VLAN for PLC network
   - Implement firewall rules

2. **Access Control:**
   - Use strong passwords
   - Limit access permissions

3. **Monitoring:**
   - Log all PLC communications
   - Monitor for unusual activity

---

## Related Documentation

- [DETECTION_SYSTEM.md](DETECTION_SYSTEM.md) - Detection script details
- [CONFIGURATION.md](CONFIGURATION.md) - Configuration guide
- [CAMERA_GUIDE.md](CAMERA_GUIDE.md) - Camera integration
