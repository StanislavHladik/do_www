# Development Guide

Guide for developers working on the Systém Detekce Obrazu project.

## Getting Started

### Prerequisites

- Git
- PHP 7.4+
- Apache2 or PHP built-in server
- Text editor/IDE (VS Code recommended)
- XDebug (optional, for debugging)

### Clone and Setup

```bash
# Clone repository
git clone https://github.com/StanislavHladik/do_www.git
cd do_www

# Switch to development branch
git checkout development
```

### Local Development Server

```bash
# Using PHP built-in server
php -S localhost:8000 -t /var/www/html

# Or with Apache (recommended for full testing)
sudo systemctl start apache2
```

---

## Code Structure

### PHP Files

The project uses procedural PHP with included templates:

```php
<?php include 'header.php'; ?>

<!-- Page content -->

<?php include 'footer.php'; ?>
```

### URL Parameters

Most pages accept these GET parameters:

| Parameter | Description | Example |
|-----------|-------------|---------|
| `cisloStroj` | Machine number | 1, 99, 100 |
| `nazevStroj` | Machine name | test, trenink |
| `popisStroj` | Machine description | Kontrola svárů |

### JavaScript

- jQuery 3.7.1 is loaded globally
- Machine variables are set via PHP:

```javascript
window.cisloStroj = "<?php echo $cisloStroj; ?>";
window.nazevStroj = "<?php echo $nazevStroj; ?>";
```

---

## Development Workflow

### Branch Strategy

```
master          # Production-ready code
└── development # Active development
    └── feature-* # Feature branches
```

### Making Changes

1. Create feature branch:
   ```bash
   git checkout development
   git checkout -b feature-my-feature
   ```

2. Make changes and test locally

3. Commit with descriptive message:
   ```bash
   git add .
   git commit -m "Add: description of changes"
   ```

4. Push and create PR:
   ```bash
   git push origin feature-my-feature
   ```

### Commit Message Conventions

- `Add:` New feature
- `Fix:` Bug fix
- `Update:` Changes to existing feature
- `Refactor:` Code restructuring
- `Docs:` Documentation changes

---

## Debugging

### Enable PHP Error Display

In development, edit `php.ini` or add to script:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### XDebug Setup

See [XDEBUG_DEBUGGING_GUIDE.md](XDEBUG_DEBUGGING_GUIDE.md) for full setup.

Quick VS Code launch configuration:

```json
{
    "name": "Listen for Xdebug",
    "type": "php",
    "request": "launch",
    "port": 9003
}
```

### Logging

Add debug logging:

```php
function debug_log($message, $data = null) {
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data) {
        $log .= " - " . print_r($data, true);
    }
    file_put_contents('/tmp/debug.log', $log . "\n", FILE_APPEND);
}
```

---

## API Development

### Adding New API Endpoints

1. Create new PHP file or add action to `detection_api.php`

2. Follow existing pattern:

```php
<?php
header('Content-Type: application/json');

try {
    // Validate input
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    // Process request
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Return response
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
```

3. Document in [API_REFERENCE.md](API_REFERENCE.md)

### Testing APIs

Use the test page or curl:

```bash
# Test detection API
curl -X POST http://localhost/detection_api.php \
  -H "Content-Type: application/json" \
  -d '{"action": "get_current_model", "machine_number": "1"}'
```

---

## Frontend Development

### CSS Organization

```
css/
├── style.css       # Global styles
├── navigation.css  # Navigation component
├── index.css       # Index page
└── train.css       # Training page
```

### Adding New Styles

1. Create page-specific CSS if needed
2. Include in the page's `<head>`:

```php
<link rel="stylesheet" href="css/my-page.css">
```

### JavaScript Best Practices

- Use `const` and `let` (not `var`)
- Use async/await for API calls:

```javascript
async function fetchData() {
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_data'})
        });
        const data = await response.json();
        if (data.success) {
            // Handle success
        }
    } catch (error) {
        console.error('Error:', error);
    }
}
```

---

## Adding New Features

### New Page Checklist

- [ ] Create PHP file with header/footer includes
- [ ] Add to navigation in `header.php`
- [ ] Create CSS file if needed
- [ ] Add API endpoints if needed
- [ ] Update documentation

### New API Checklist

- [ ] Add endpoint PHP file
- [ ] Add proper error handling
- [ ] Add logging
- [ ] Test with curl/browser
- [ ] Document in API_REFERENCE.md

---

## Testing

### Manual Testing

1. Test all pages with different machine numbers
2. Test API endpoints with different inputs
3. Test file uploads with various sizes
4. Test error handling

### Test Files

- `test_detection_api.html` - API testing interface
- `test_weights.php` - Test weights loading
- `test_upload_config.php` - Test upload configuration
- `test_training_setup.sh` - Test training setup

---

## Code Style

### PHP

- Use 4-space indentation
- Use `htmlspecialchars()` for output escaping
- Use prepared statements for database queries (if added)
- Follow PSR-12 where practical

### JavaScript

- Use 4-space indentation
- Use camelCase for variables/functions
- Add JSDoc comments for functions

### CSS

- Use lowercase with hyphens: `my-class-name`
- Group related properties
- Add comments for sections

---

## Related Documentation

- [XDEBUG_DEBUGGING_GUIDE.md](XDEBUG_DEBUGGING_GUIDE.md) - Debugging setup
- [API_REFERENCE.md](API_REFERENCE.md) - API documentation
- [FILE_STRUCTURE.md](FILE_STRUCTURE.md) - File organization
