# Fix: "File is too large" Error for Small Files

## Problem
You're getting "File is too large" error when trying to upload a 5 MB file, even though the application allows up to 500 MB.

## Root Cause
This happens because **PHP's server configuration** has lower upload limits than the application's intended 500 MB limit. The error comes from PHP itself, not from the application code.

## Quick Diagnosis

### Step 1: Check Current PHP Limits
Visit this page in your browser:
```
http://your-server-ip/check_upload_limits.php
```

This will show you the current PHP upload configuration and whether it meets requirements.

### Step 2: Find Your PHP Configuration File
```bash
php -i | grep "Loaded Configuration File"
# or
php --ini
```

Common locations:
- `/etc/php/8.x/apache2/php.ini` (Apache)
- `/etc/php/8.x/fpm/php.ini` (PHP-FPM)
- `/etc/php/8.x/cli/php.ini` (CLI)

## Solution

### Method 1: Edit php.ini (Recommended)

1. **Find and edit php.ini:**
```bash
sudo nano /etc/php/8.x/apache2/php.ini
# or for PHP-FPM
sudo nano /etc/php/8.x/fpm/php.ini
```

2. **Find and update these lines:**
```ini
upload_max_filesize = 500M
post_max_size = 500M
max_execution_time = 300
memory_limit = 512M
```

**Important:** Make sure `post_max_size` ≥ `upload_max_filesize` and `memory_limit` > `post_max_size`

3. **Restart the web server:**

For Apache:
```bash
sudo systemctl restart apache2
```

For PHP-FPM with Nginx:
```bash
sudo systemctl restart php8.x-fpm
sudo systemctl restart nginx
```

For PHP-FPM with Apache:
```bash
sudo systemctl restart php8.x-fpm
sudo systemctl restart apache2
```

4. **Verify the changes:**
```bash
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

Or visit `http://your-server-ip/check_upload_limits.php` again.

### Method 2: .htaccess (If Method 1 doesn't work)

If you don't have access to php.ini, try creating/editing `.htaccess` in `/var/www/html/`:

```apache
php_value upload_max_filesize 500M
php_value post_max_size 500M
php_value max_execution_time 300
php_value memory_limit 512M
```

**Note:** This only works if PHP is running as Apache module, not as PHP-FPM.

### Method 3: .user.ini (For PHP-FPM)

If using PHP-FPM and can't edit php.ini, create `/var/www/html/.user.ini`:

```ini
upload_max_filesize = 500M
post_max_size = 500M
max_execution_time = 300
memory_limit = 512M
```

Then restart PHP-FPM:
```bash
sudo systemctl restart php8.x-fpm
```

## Verification

### Test 1: Check via Web Interface
Visit: `http://your-server-ip/check_upload_limits.php`

Should show:
- ✅ upload_max_filesize: 500M
- ✅ post_max_size: 500M
- ✅ memory_limit: 512M

### Test 2: Try Upload Again
Go back to the training page and try uploading your 5 MB file.

### Test 3: Check Logs
If still failing, check error logs:
```bash
# Apache error log
sudo tail -f /var/log/apache2/error.log

# PHP-FPM error log
sudo tail -f /var/log/php8.x-fpm.log

# Application upload log
tail -f /tmp/dataset_uploads.log
```

## Common Issues

### Issue 1: Changes not taking effect
**Solution:** Make sure you:
1. Edited the correct php.ini file (use `php --ini` to find it)
2. Restarted the correct service (apache2 or php-fpm)
3. Cleared browser cache
4. Waited a few seconds after restart

### Issue 2: Still getting error after increasing limits
**Possible causes:**
1. Multiple php.ini files (CLI vs Apache vs FPM)
2. .htaccess or .user.ini overriding settings
3. Nginx configuration limiting upload size
4. Firewall/proxy timeout

**For Nginx, also check:**
```nginx
# /etc/nginx/nginx.conf or site config
http {
    client_max_body_size 500M;
}
```

Then restart:
```bash
sudo systemctl restart nginx
```

### Issue 3: Hosting provider restrictions
Some shared hosting providers enforce maximum upload limits. Contact your hosting provider if:
- You've followed all steps
- Limits still don't increase
- You're on shared/managed hosting

## Quick Fix Command (Ubuntu/Debian)

Run this all-in-one command (requires sudo):

```bash
# Find PHP version
PHP_VER=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)

# Update php.ini for Apache
sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 500M/' /etc/php/$PHP_VER/apache2/php.ini
sudo sed -i 's/post_max_size = .*/post_max_size = 500M/' /etc/php/$PHP_VER/apache2/php.ini
sudo sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/$PHP_VER/apache2/php.ini
sudo sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/$PHP_VER/apache2/php.ini

# Update php.ini for FPM (if exists)
if [ -f /etc/php/$PHP_VER/fpm/php.ini ]; then
    sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 500M/' /etc/php/$PHP_VER/fpm/php.ini
    sudo sed -i 's/post_max_size = .*/post_max_size = 500M/' /etc/php/$PHP_VER/fpm/php.ini
    sudo sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/$PHP_VER/fpm/php.ini
    sudo sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/$PHP_VER/fpm/php.ini
fi

# Restart services
sudo systemctl restart apache2 2>/dev/null || true
sudo systemctl restart php$PHP_VER-fpm 2>/dev/null || true

echo "PHP upload limits updated to 500M"
echo "Please verify by visiting check_upload_limits.php"
```

## Summary

The error occurs because PHP's default upload limits (usually 2M or 8M) are lower than your file size. The application supports 500 MB, but PHP needs to be configured to allow it.

**Quick steps:**
1. Check limits: Visit `check_upload_limits.php`
2. Edit php.ini: Set `upload_max_filesize = 500M` and `post_max_size = 500M`
3. Restart: `sudo systemctl restart apache2` (or php-fpm)
4. Verify: Upload should now work

**Need help?**
- Check `/tmp/dataset_uploads.log` for application logs
- Check `/var/log/apache2/error.log` for PHP errors
- The error message now includes current PHP limits for easier debugging
