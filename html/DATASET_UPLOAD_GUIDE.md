# Dataset Upload Feature - Documentation

## Overview
The training page (`train.php`) now includes a file upload feature that allows users to upload .zip files containing training datasets directly through the web interface.

## Upload Location
All datasets are uploaded to:
```
/home/yolo/st99_trenink/Detekce_Obrazu/datasets_yolo_1_1/{dataset_name}/
```

**Note:** Regardless of which machine number you're viewing the training page from, uploads always go to `st99_trenink` as this is the designated training machine.

## Features

### 1. File Upload Section
- **Location**: Top of the training page, above the "Dostupné Datasety" section
- **Supported Format**: .zip files only
- **Maximum Size**: 500 MB
- **Auto-extraction**: Uploaded .zip files are automatically extracted

### 2. Upload Process

#### Step 1: Select File
- Click "Vyberte .zip soubor" to choose a .zip file from your computer
- File must be under 500 MB
- Only .zip format is accepted

#### Step 2: Name Dataset (Optional)
- Enter a custom name for the dataset in "Název datasetu" field
- If left empty, the filename (without .zip extension) will be used
- Invalid characters are automatically replaced with underscores

#### Step 3: Upload
- Click "Nahrát Dataset" button
- Progress bar shows upload progress
- Status messages inform you of the upload state

### 3. Upload States

#### During Upload
- Blue info alert: "Nahrávání datasetu... X%"
- Progress bar shows percentage
- Spinner icon indicates activity

#### Success
- Green success alert: "Dataset '{name}' byl úspěšně nahrán a rozbalen"
- Checkmark icon
- Page automatically reloads after 2 seconds to show new dataset

#### Error
- Red error alert with specific error message
- Warning icon
- Form remains filled for retry

## Backend Implementation

### PHP Script: `upload_dataset.php`

**Key Functions:**

1. **File Validation**
   - Checks file type (.zip only)
   - Validates file size (max 500 MB)
   - Verifies upload success

2. **Name Sanitization**
   - Removes invalid characters from dataset names
   - Prevents directory traversal attacks
   - Ensures unique dataset names

3. **Directory Management**
   - Creates dataset directory
   - Checks write permissions
   - Prevents overwriting existing datasets

4. **ZIP Extraction**
   - Automatically extracts uploaded .zip files
   - Removes .zip file after successful extraction
   - Keeps .zip file if extraction fails

5. **Logging**
   - All uploads are logged to `/tmp/dataset_uploads.log`
   - Includes timestamp, dataset name, size, and path

### Security Features

1. **File Type Validation**: Only .zip files accepted
2. **Size Limits**: 500 MB maximum
3. **Name Sanitization**: Removes dangerous characters
4. **Path Validation**: Prevents directory traversal
5. **Duplicate Prevention**: Won't overwrite existing datasets
6. **Permission Checks**: Verifies write access before upload

## Expected Dataset Structure

For proper training, uploaded .zip files should contain:

```
dataset_name/
├── train/
│   ├── images/
│   │   ├── image1.jpg
│   │   ├── image2.jpg
│   │   └── ...
│   └── labels/
│       ├── image1.txt
│       ├── image2.txt
│       └── ...
└── val/
    ├── images/
    │   ├── image1.jpg
    │   └── ...
    └── labels/
        ├── image1.txt
        └── ...
```

## Frontend Implementation

### HTML Form
```html
<form id="dataset-upload-form" enctype="multipart/form-data">
    <input type="file" accept=".zip">
    <input type="text" placeholder="Dataset name (optional)">
    <button type="submit">Upload</button>
</form>
```

### JavaScript Features

1. **File Validation**
   - Client-side size check (500 MB)
   - Extension validation (.zip only)

2. **Progress Tracking**
   - Real-time upload progress via XMLHttpRequest
   - Visual progress bar
   - Percentage display

3. **Status Updates**
   - Dynamic status messages
   - Color-coded alerts (info, success, error)
   - Animated icons

4. **Auto-reload**
   - Page reloads 2 seconds after successful upload
   - Shows newly uploaded dataset immediately

## Usage Example

### Via Web Interface

1. Navigate to Training page (train.php)
2. Find "Nahrát Nový Dataset" section at top
3. Click "Vyberte .zip soubor"
4. Select your prepared dataset .zip file
5. (Optional) Enter custom dataset name
6. Click "Nahrát Dataset"
7. Wait for upload and extraction
8. Page reloads showing new dataset

### Preparing Dataset for Upload

```bash
# Create dataset structure
mkdir -p my_dataset/train/{images,labels}
mkdir -p my_dataset/val/{images,labels}

# Add your images and labels
cp train_images/* my_dataset/train/images/
cp train_labels/* my_dataset/train/labels/
cp val_images/* my_dataset/val/images/
cp val_labels/* my_dataset/val/labels/

# Create zip file
cd my_dataset
zip -r ../my_dataset.zip .
cd ..

# Now upload my_dataset.zip via web interface
```

## Error Handling

### Common Errors

1. **"File is too large"**
   - Solution: Reduce dataset size or split into smaller datasets

2. **"Only .zip files are supported"**
   - Solution: Convert your archive to .zip format

3. **"Dataset already exists"**
   - Solution: Choose a different name or delete existing dataset first

4. **"Target directory not writable"**
   - Solution: Check permissions on `/home/yolo/st99_trenink/Detekce_Obrazu/datasets_yolo_1_1/`

5. **"Upload error"**
   - Check PHP upload settings in php.ini:
     - `upload_max_filesize`
     - `post_max_size`
     - `max_file_uploads`

## PHP Configuration

Ensure your PHP configuration supports large uploads:

```ini
# /etc/php/8.x/apache2/php.ini or /etc/php/8.x/fpm/php.ini
upload_max_filesize = 500M
post_max_size = 500M
max_execution_time = 300
memory_limit = 512M
```

After changing, restart web server:
```bash
sudo systemctl restart apache2
# or
sudo systemctl restart php8.x-fpm
```

## Logs and Troubleshooting

### Upload Log
```bash
tail -f /tmp/dataset_uploads.log
```

### Apache/PHP Error Log
```bash
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/php8.x-fpm.log
```

### Check Permissions
```bash
ls -la /home/yolo/st99_trenink/Detekce_Obrazu/datasets_yolo_1_1/
```

### Test Upload Manually
```bash
# Create test zip
echo "test" > test.txt
zip test.zip test.txt

# Test upload with curl
curl -X POST \
  -F "dataset_file=@test.zip" \
  -F "dataset_name=test_dataset" \
  -F "machine_number=99" \
  http://localhost/upload_dataset.php
```

## File Structure

```
/var/www/html/
├── train.php               # Main training page with upload UI
├── upload_dataset.php      # Backend upload handler
└── css/
    └── train.css          # Styling for upload section

/home/yolo/st99_trenink/Detekce_Obrazu/datasets_yolo_1_1/
├── dataset1/              # Uploaded and extracted dataset
├── dataset2/
└── ...

/tmp/
└── dataset_uploads.log    # Upload activity log
```

## Future Enhancements

Potential improvements:
1. Dataset preview before training
2. Validation of dataset structure
3. Multi-file upload support
4. Resume interrupted uploads
5. Delete/manage uploaded datasets via UI
6. Dataset statistics and visualization
7. Compression options
8. Cloud storage integration

## Best Practices

1. **Name datasets descriptively**: Use meaningful names like `ST2_0812_V5` instead of `dataset1`
2. **Organize before upload**: Ensure correct directory structure before zipping
3. **Test with small datasets first**: Verify process works before uploading large files
4. **Monitor disk space**: Check available space before large uploads
5. **Keep backups**: Maintain copies of important datasets elsewhere
6. **Document datasets**: Include README with dataset information
