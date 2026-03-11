# 🐛 Quick Xdebug Reference for detection_api.php

## 🚀 Quick Start (3 Steps)

### 1. Install & Configure Xdebug
```bash
sudo bash /var/www/html/setup_xdebug.sh
```

### 2. Start Debugging in VS Code
- Press **F5** or click Run → Start Debugging
- Select "Listen for Xdebug (PHP API)" from dropdown
- VS Code status bar should show "Xdebug: Listening on port 9003"

### 3. Trigger Your API
- Open: http://localhost/test_detection_api.html
- Click "Enable Xdebug Session" button
- Set breakpoints in detection_api.php (click left of line numbers)
- Click any command button

---

## 📍 Setting Breakpoints

### Good Places to Set Breakpoints in detection_api.php:

1. **Line ~23**: First JSON input check
2. **Line ~75**: Command execution start
3. **Line ~90**: executeDetectionCommand function
4. **Line ~120**: startDetection function
5. **Line ~150**: stopDetection function
6. **Line ~420**: saveModelSelection function

**To set breakpoint**: Click in the gutter (left of line number) or press F9

---

## 🎯 Common Debugging Scenarios

### Scenario 1: Debug Command Handling
```
Breakpoint → Line ~90 (executeDetectionCommand)
Trigger → Click "Check Status" in test page
Inspect → $command, $cisloStroj variables
```

### Scenario 2: Debug Process Start/Stop
```
Breakpoint → Line ~120 (startDetection) or ~150 (stopDetection)
Trigger → Click "Start Detection" or "Stop Detection"
Inspect → $script_path, $pid variables
```

### Scenario 3: Debug Model Selection
```
Breakpoint → Line ~420 (saveModelSelection)
Trigger → Enter model path, click "Save Model Selection"
Inspect → $model_path, $config variables
```

---

## 🔍 VS Code Debug Controls

| Key | Action |
|-----|--------|
| **F5** | Start/Continue |
| **F9** | Toggle Breakpoint |
| **F10** | Step Over (execute current line) |
| **F11** | Step Into (enter function) |
| **Shift+F11** | Step Out (exit function) |
| **Shift+F5** | Stop Debugging |
| **Ctrl+Shift+F5** | Restart |

---

## 📊 Debug Panels

### Variables Panel
Shows all variables in current scope:
- `$input` - JSON payload
- `$command` - Current command
- `$cisloStroj` - Machine number
- `$result` - Function results

### Watch Panel
Add expressions to monitor:
```
$input['command']
count($config)
file_exists($script_path)
```

### Call Stack
Shows function call hierarchy

### Debug Console
Execute PHP code while paused:
```php
print_r($input)
var_dump($config)
isset($input['command'])
```

---

## 🛠️ Testing Methods

### Method 1: Test HTML Page (Recommended)
```
http://localhost/test_detection_api.html
✓ Easy to use
✓ Xdebug toggle button
✓ All commands pre-configured
```

### Method 2: curl Command
```bash
# With Xdebug
curl -X POST http://localhost/detection_api.php \
  -H "Content-Type: application/json" \
  -H "Cookie: XDEBUG_SESSION=VSCODE" \
  -d '{"command":"status","cisloStroj":"1"}'
```

### Method 3: Browser Footer Buttons
```
Open nahledy.php in browser with ?XDEBUG_SESSION_START=VSCODE
Click footer control buttons
```

---

## 🔧 Troubleshooting

### ❌ Breakpoint Shows "Unverified" (Gray Circle)
**Solution**: Check path mapping in launch.json
```json
"pathMappings": {
    "/var/www/html": "${workspaceFolder}/html"
}
```

### ❌ VS Code Not Breaking at Breakpoint
**Check**:
1. Is VS Code listening? (Press F5)
2. Is Xdebug enabled in browser/request?
3. Check log: `tail -f /tmp/xdebug.log`
4. Verify: `http://localhost/check_xdebug.php`

### ❌ Connection Timeout
**Solutions**:
```bash
# Check firewall
sudo ufw allow 9003/tcp

# Check port is free
sudo netstat -tlnp | grep 9003

# Restart web server
sudo systemctl restart apache2
# OR
sudo systemctl restart php8.1-fpm nginx
```

### ❌ "Cannot find module" or Path Issues
**Solution**: Make sure you opened the correct workspace folder in VS Code:
```bash
cd /var/www
code .
```

---

## 📝 Useful Commands

```bash
# View Xdebug log in real-time
tail -f /tmp/xdebug.log

# Check if Xdebug is loaded
php -m | grep xdebug

# View Xdebug configuration
php -i | grep xdebug

# Restart Apache
sudo systemctl restart apache2

# Restart Nginx + PHP-FPM
sudo systemctl restart php8.1-fpm nginx

# Check API log
tail -f /tmp/detection_api.log

# Test API directly
curl -X POST http://localhost/detection_api.php \
  -H "Content-Type: application/json" \
  -d '{"command":"status","cisloStroj":"1"}'
```

---

## 💡 Pro Tips

1. **Use Conditional Breakpoints**: Right-click breakpoint → Edit Breakpoint
   ```php
   $command == 'start'
   $cisloStroj == '2'
   ```

2. **Log Points**: Instead of breaking, log to Debug Console
   ```php
   Command: {$command}, Machine: {$cisloStroj}
   ```

3. **Exception Breakpoints**: Break on PHP errors
   - Debug panel → Breakpoints → Check "Everything"

4. **Step Filtering**: Skip vendor files
   - Add to settings.json: `"php.debug.skipFiles": ["/vendor/**"]`

5. **Multiple Sessions**: Debug multiple requests
   - Each request gets its own debug session

---

## 📚 Additional Resources

- **Check Xdebug**: http://localhost/check_xdebug.php
- **Test API**: http://localhost/test_detection_api.html
- **Full Guide**: /var/www/html/XDEBUG_DEBUGGING_GUIDE.md
- **API Log**: /tmp/detection_api.log
- **Xdebug Log**: /tmp/xdebug.log

---

## ✅ Quick Health Check

Run these to verify everything is working:

```bash
# 1. Xdebug is loaded
php -m | grep xdebug && echo "✓ Xdebug installed" || echo "✗ Not installed"

# 2. Web server is running
systemctl is-active apache2 || systemctl is-active nginx && echo "✓ Web server running"

# 3. Port is available
! netstat -tuln | grep -q ":9003 " && echo "✓ Port 9003 available" || echo "⚠ Port in use"

# 4. API is accessible
curl -s http://localhost/detection_api.php > /dev/null 2>&1 && echo "✓ API reachable"

# 5. Test page exists
[ -f /var/www/html/test_detection_api.html ] && echo "✓ Test page ready"
```

---

**Happy Debugging! 🐛➡️✨**
