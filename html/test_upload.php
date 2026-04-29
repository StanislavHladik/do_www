<?php
// Simple upload test
header('Content-Type: application/json');

// Log everything
error_log("=== Upload test started ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("CONTENT_LENGTH: " . ($_SERVER['CONTENT_LENGTH'] ?? 'not set'));
error_log("upload_max_filesize: " . ini_get('upload_max_filesize'));
error_log("post_max_size: " . ini_get('post_max_size'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Show upload form
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Test Upload</title></head>
    <body>
        <h1>Test Upload</h1>
        <p>PHP Limits:</p>
        <ul>
            <li>upload_max_filesize: <?php echo ini_get('upload_max_filesize'); ?></li>
            <li>post_max_size: <?php echo ini_get('post_max_size'); ?></li>
            <li>max_execution_time: <?php echo ini_get('max_execution_time'); ?></li>
            <li>memory_limit: <?php echo ini_get('memory_limit'); ?></li>
        </ul>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="testfile" required>
            <button type="submit">Upload</button>
        </form>
        
        <h2>Test with JavaScript (same as train.php)</h2>
        <input type="file" id="jsfile">
        <button onclick="uploadWithXHR()">Upload with XHR</button>
        <div id="status"></div>
        <div id="progress"></div>
        
        <script>
        function uploadWithXHR() {
            const file = document.getElementById('jsfile').files[0];
            if (!file) {
                alert('Select a file first');
                return;
            }
            
            const formData = new FormData();
            formData.append('testfile', file);
            
            const xhr = new XMLHttpRequest();
            xhr.timeout = 2 * 60 * 60 * 1000;
            
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const pct = Math.round((e.loaded / e.total) * 100);
                    document.getElementById('progress').textContent = pct + '%';
                }
            });
            
            xhr.addEventListener('load', function() {
                document.getElementById('status').textContent = 'Status: ' + xhr.status + ' - ' + xhr.responseText;
            });
            
            xhr.addEventListener('error', function(e) {
                document.getElementById('status').textContent = 'XHR Error! Check console.';
                console.error('XHR error:', e);
                console.error('readyState:', xhr.readyState);
                console.error('status:', xhr.status);
            });
            
            xhr.addEventListener('timeout', function() {
                document.getElementById('status').textContent = 'Timeout!';
            });
            
            xhr.open('POST', 'test_upload.php', true);
            xhr.send(formData);
            
            document.getElementById('status').textContent = 'Uploading...';
        }
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Handle POST
$result = [
    'success' => false,
    'message' => '',
    'files' => $_FILES,
    'post' => $_POST,
    'content_length' => $_SERVER['CONTENT_LENGTH'] ?? null,
];

if (empty($_FILES)) {
    $result['message'] = 'No files received. POST too large? Content-Length: ' . ($_SERVER['CONTENT_LENGTH'] ?? 'unknown');
    $result['php_limits'] = [
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
    ];
} elseif (isset($_FILES['testfile'])) {
    $file = $_FILES['testfile'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $result['success'] = true;
        $result['message'] = 'File uploaded successfully!';
        $result['file_size'] = $file['size'];
        $result['file_name'] = $file['name'];
        // Clean up temp file
        unlink($file['tmp_name']);
    } else {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File too large (upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (form MAX_FILE_SIZE)',
            UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp directory',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by extension',
        ];
        $result['message'] = $errors[$file['error']] ?? 'Unknown error: ' . $file['error'];
    }
}

echo json_encode($result, JSON_PRETTY_PRINT);
