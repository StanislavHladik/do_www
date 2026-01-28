# Debugging detection_api.php with Xdebug in VS Code

## Step 1: Install Xdebug

```bash
# Check PHP version
php -v

# Install Xdebug for PHP (Ubuntu/Debian)
sudo apt-get update
sudo apt-get install php-xdebug

# For specific PHP version (e.g., PHP 8.1)
sudo apt-get install php8.1-xdebug

# Restart web server
sudo systemctl restart apache2
# OR for nginx with php-fpm
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx
```

## Step 2: Configure Xdebug

### For Xdebug 3.x (Latest)

Edit your php.ini file:
```bash
# Find php.ini location
php --ini

# Edit the file (usually /etc/php/8.1/apache2/php.ini or /etc/php/8.1/fpm/php.ini)
sudo nano /etc/php/8.1/apache2/php.ini
```

Add/modify these settings at the end:
```ini
[xdebug]
zend_extension=xdebug.so
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
xdebug.log=/tmp/xdebug.log
xdebug.log_level=7
xdebug.idekey=VSCODE
```

### For Xdebug 2.x (Legacy)

```ini
[xdebug]
zend_extension=xdebug.so
xdebug.remote_enable=1
xdebug.remote_autostart=1
xdebug.remote_host=127.0.0.1
xdebug.remote_port=9000
xdebug.remote_log=/tmp/xdebug.log
xdebug.idekey=VSCODE
```

After editing, restart your web server:
```bash
sudo systemctl restart apache2
# OR
sudo systemctl restart php8.1-fpm && sudo systemctl restart nginx
```

## Step 3: Verify Xdebug Installation

Visit http://localhost/check_xdebug.php in your browser to verify Xdebug is working.

Or via command line:
```bash
php -v | grep -i xdebug
```

## Step 4: Install VS Code PHP Debug Extension

1. Open VS Code
2. Press `Ctrl+Shift+X` to open Extensions
3. Search for "PHP Debug" by Felix Becker
4. Click Install

## Step 5: Set Breakpoints and Start Debugging

### Method 1: Debug with Browser Request

1. **Open detection_api.php in VS Code**
2. **Set breakpoints** by clicking to the left of the line numbers (red dots will appear)
3. **Start debugging** in VS Code:
   - Press `F5` or
   - Go to Run → Start Debugging or
   - Select "Listen for Xdebug (PHP API)" from the debug dropdown
4. **Trigger the request** from your browser or use curl:

```bash
# Test status command
curl -X POST http://localhost/detection_api.php \
  -H "Content-Type: application/json" \
  -d '{"command":"status","cisloStroj":"1"}'

# Test start command
curl -X POST http://localhost/detection_api.php \
  -H "Content-Type: application/json" \
  -d '{"command":"start","cisloStroj":"1"}'

# Test save model selection
curl -X POST http://localhost/detection_api.php \
  -H "Content-Type: application/json" \
  -d '{"action":"save_model_selection","model_path":"model.pt","machine_number":"1"}'
```

5. **VS Code will pause** at your breakpoints and you can:
   - Inspect variables
   - Step through code (F10 for step over, F11 for step into)
   - View call stack
   - Evaluate expressions in the Debug Console

### Method 2: Debug with Xdebug Browser Extension

1. Install browser extension:
   - **Chrome/Edge**: [Xdebug helper](https://chrome.google.com/webstore/detail/xdebug-helper)
   - **Firefox**: [Xdebug Helper](https://addons.mozilla.org/en-US/firefox/addon/xdebug-helper-for-firefox/)

2. Click the extension icon and select "Debug"
3. Start listening in VS Code (F5)
4. Open your page or trigger the API call from the footer buttons

### Method 3: Debug with Query Parameter

Add `?XDEBUG_SESSION_START=VSCODE` to any URL:
```
http://localhost/detection_api.php?XDEBUG_SESSION_START=VSCODE
```

## Step 6: Debugging Tips for API Calls

Since detection_api.php receives POST requests with JSON, use these approaches:

### Option A: Create a Test Page

Create `/var/www/html/test_detection_api.html`:
```html
<!DOCTYPE html>
<html>
<head>
    <title>Test Detection API</title>
</head>
<body>
    <h1>Test Detection API</h1>
    <button onclick="testCommand('status')">Test Status</button>
    <button onclick="testCommand('start')">Test Start</button>
    <button onclick="testCommand('stop')">Test Stop</button>
    <div id="result"></div>
    
    <script>
        function testCommand(cmd) {
            fetch('detection_api.php?XDEBUG_SESSION_START=VSCODE', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({command: cmd, cisloStroj: '1'})
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('result').innerText = JSON.stringify(data, null, 2);
            });
        }
    </script>
</body>
</html>
```

### Option B: Use VS Code REST Client Extension

1. Install "REST Client" extension
2. Create `/var/www/html/api-tests.http`:

```http
### Test Status Command
POST http://localhost/detection_api.php
Content-Type: application/json

{
    "command": "status",
    "cisloStroj": "1"
}

### Test Start Command
POST http://localhost/detection_api.php
Content-Type: application/json

{
    "command": "start",
    "cisloStroj": "1"
}

### Test with Xdebug Session
POST http://localhost/detection_api.php?XDEBUG_SESSION_START=VSCODE
Content-Type: application/json

{
    "command": "status",
    "cisloStroj": "1"
}
```

Click "Send Request" above each request block.

### Option C: Use Postman or curl with Xdebug Cookie

```bash
# With cookie to trigger Xdebug
curl -X POST http://localhost/detection_api.php \
  -H "Content-Type: application/json" \
  -H "Cookie: XDEBUG_SESSION=VSCODE" \
  -d '{"command":"status","cisloStroj":"1"}'
```

## Troubleshooting

### Xdebug Not Connecting

1. **Check Xdebug log**:
```bash
tail -f /tmp/xdebug.log
```

2. **Verify port is not blocked**:
```bash
sudo netstat -tlnp | grep 9003
```

3. **Check firewall**:
```bash
sudo ufw status
sudo ufw allow 9003/tcp
```

4. **Test Xdebug from command line**:
```bash
php -dxdebug.mode=debug -dxdebug.start_with_request=yes detection_api.php
```

### Path Mapping Issues

If breakpoints show as "unverified", check your pathMappings in launch.json:
- Server path: `/var/www/html`
- VS Code workspace path: Check with `pwd` in terminal

### Check PHP-FPM Configuration (if using nginx)

```bash
# Edit PHP-FPM pool configuration
sudo nano /etc/php/8.1/fpm/pool.d/www.conf

# Ensure these are set:
# listen = /run/php/php8.1-fpm.sock
# OR
# listen = 127.0.0.1:9000

# Restart
sudo systemctl restart php8.1-fpm
```

## Quick Reference: VS Code Debugging Shortcuts

- **F5**: Start/Continue debugging
- **F9**: Toggle breakpoint
- **F10**: Step over
- **F11**: Step into
- **Shift+F11**: Step out
- **Ctrl+Shift+F5**: Restart debugging
- **Shift+F5**: Stop debugging

## Useful Xdebug Commands in Debug Console

While paused at a breakpoint, use the Debug Console:
```php
print_r($input);
var_dump($cisloStroj);
get_defined_vars();
debug_backtrace();
```
