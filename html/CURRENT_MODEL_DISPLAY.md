# Current Model Display Implementation

## Overview
The `models_offer.php` page now displays which model is currently active/selected for each machine, with visual highlighting.

## Implementation Details

### PHP Side (Server-Side)
Located in `/var/www/html/models_offer.php` around line 57-68:

```php
// Get the current model from config file
$configPath = $matchingDirs[0] . "/Detekce_Obrazu/config/detekce_ulozeni.json";
$currentModelName = null;

if (file_exists($configPath)) {
    $configContent = file_get_contents($configPath);
    $config = json_decode($configContent, true);
    if ($config !== null && isset($config['weights_name'])) {
        $currentModelName = $config['weights_name'];
    }
}
```

**How it works:**
1. Reads the `detekce_ulozeni.json` config file
2. Extracts the `weights_name` field (which contains the current model filename)
3. Stores it in `$currentModelName` variable

### Model Card Display
For each model in the loop (around line 80-88):

```php
$isCurrentModel = ($currentModelName !== null && $currentModelName === $fileName);

echo '<div class="model-card' . ($isCurrentModel ? ' active-model' : '') . '">';
echo '<div class="model-header">';
echo '<h3><i class="fa fa-cube"></i> ' . htmlspecialchars($fileName);
if ($isCurrentModel) {
    echo ' <span class="badge badge-success"><i class="fa fa-check"></i> Aktuálně vybraný</span>';
}
echo '</h3>';
```

**Features:**
- Compares each model's filename with the current model
- Adds `active-model` CSS class to the currently selected model card
- Displays a green "Aktuálně vybraný" badge next to the model name

### CSS Styling
Located in `/var/www/html/css/style.css`:

```css
/* Active Model Styling */
.model-card.active-model {
    border: 2px solid #28a745;
    background: #f0f9f4;
}

.model-card.active-model:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(40, 167, 69, 0.3);
}

.badge {
    display: inline-block;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 12px;
    margin-left: 8px;
    vertical-align: middle;
}

.badge-success {
    background-color: #28a745;
    color: white;
}
```

**Visual Effects:**
- Green border (2px solid)
- Light green background (#f0f9f4)
- Enhanced shadow on hover with green tint
- Badge with green background and white text

## JavaScript Function (Optional)
The `getActualModel()` JavaScript function is available for dynamic retrieval:

```javascript
function getActualModel() {
    fetch('detection_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'get_current_model',
            machine_number: '<?php echo($cisloStroj); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const currentModel = data.model_name;
            console.log('Aktuálně vybraný model:', currentModel);
            // Use currentModel for dynamic updates
        }
    });
}
```

**Note:** This is for dynamic JavaScript usage. The page already shows the current model on page load using PHP.

## User Experience

### Before Selection
- All model cards have standard white background
- No badges displayed

### After Selection
- Currently active model has:
  - Green border
  - Light green background
  - Green "Aktuálně vybraný" badge with checkmark icon
  - Green-tinted shadow on hover

### Benefits
1. **Immediate Visual Feedback**: Users can instantly see which model is active
2. **No Confusion**: Clear distinction between available and active models
3. **Professional Look**: Clean, modern badge design
4. **Consistent UX**: Matches the rest of the interface styling

## Data Flow

```
detekce_ulozeni.json (config file)
    ↓
PHP reads weights_name
    ↓
$currentModelName variable
    ↓
Compare with each model in loop
    ↓
Add .active-model class + badge if match
    ↓
CSS applies green styling
    ↓
User sees highlighted active model
```

## File Locations

- **PHP Logic**: `/var/www/html/models_offer.php` (lines ~57-88)
- **CSS Styling**: `/var/www/html/css/style.css` (around line 310)
- **Config Source**: `/home/yolo/st{N}_*/Detekce_Obrazu/config/detekce_ulozeni.json`
- **API Endpoint**: `/var/www/html/detection_api.php` (action: `get_current_model`)

## Troubleshooting

**Problem**: Active model not highlighted
- Check if `detekce_ulozeni.json` exists and is readable
- Verify `weights_name` field in config matches model filename exactly
- Check browser console for errors

**Problem**: Badge not showing
- Verify CSS file is loaded
- Check for CSS conflicts
- Clear browser cache

**Problem**: Wrong model highlighted
- Verify config file path is correct for the machine
- Check if `weights_name` value is up to date in config
