<?php
// Simple page to check PHP upload configuration
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP Upload Configuration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .info-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #007bff;
            color: white;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-top: 20px;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-top: 20px;
        }
        .code {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            margin-top: 10px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="info-box">
        <h1>📊 PHP Upload Configuration</h1>
        
        <table>
            <tr>
                <th>Setting</th>
                <th>Current Value</th>
                <th>Recommended</th>
            </tr>
            <tr>
                <td><strong>upload_max_filesize</strong></td>
                <td><?php echo ini_get('upload_max_filesize'); ?></td>
                <td>500M</td>
            </tr>
            <tr>
                <td><strong>post_max_size</strong></td>
                <td><?php echo ini_get('post_max_size'); ?></td>
                <td>500M</td>
            </tr>
            <tr>
                <td><strong>max_execution_time</strong></td>
                <td><?php echo ini_get('max_execution_time'); ?> seconds</td>
                <td>300</td>
            </tr>
            <tr>
                <td><strong>memory_limit</strong></td>
                <td><?php echo ini_get('memory_limit'); ?></td>
                <td>512M</td>
            </tr>
            <tr>
                <td><strong>max_file_uploads</strong></td>
                <td><?php echo ini_get('max_file_uploads'); ?></td>
                <td>20</td>
            </tr>
        </table>
        
        <?php
        // Convert to bytes for comparison
        function convertToBytes($val) {
            $val = trim($val);
            $last = strtolower($val[strlen($val)-1]);
            $val = (int)$val;
            switch($last) {
                case 'g': $val *= 1024;
                case 'm': $val *= 1024;
                case 'k': $val *= 1024;
            }
            return $val;
        }
        
        $upload_max_bytes = convertToBytes(ini_get('upload_max_filesize'));
        $post_max_bytes = convertToBytes(ini_get('post_max_size'));
        $required_bytes = 500 * 1024 * 1024; // 500 MB
        
        if ($upload_max_bytes < $required_bytes || $post_max_bytes < $required_bytes) {
            echo '<div class="warning">';
            echo '<strong>⚠️ Varování:</strong> Současné nastavení neumožňuje nahrávání souborů do 500 MB.<br>';
            echo 'Minimální požadované hodnoty nejsou splněny.';
            echo '</div>';
        } else {
            echo '<div class="success">';
            echo '<strong>✅ V pořádku:</strong> PHP je nakonfigurováno správně pro nahrávání velkých souborů.';
            echo '</div>';
        }
        ?>
        
        <h2>🔧 Jak zvýšit limity</h2>
        
        <h3>1. Najděte konfigurační soubor php.ini:</h3>
        <div class="code">
<?php
echo "Loaded php.ini: " . php_ini_loaded_file() . "\n";
$scanned = php_ini_scanned_files();
if ($scanned) {
    echo "Additional .ini files: " . $scanned;
}
?>
        </div>
        
        <h3>2. Upravte tyto hodnoty v php.ini:</h3>
        <div class="code">
upload_max_filesize = 500M
post_max_size = 500M
max_execution_time = 300
memory_limit = 512M
        </div>
        
        <h3>3. Restartujte webový server:</h3>
        <div class="code">
# Pro Apache:
sudo systemctl restart apache2

# Pro PHP-FPM:
sudo systemctl restart php<?php echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION; ?>-fpm

# Pro Nginx s PHP-FPM:
sudo systemctl restart php<?php echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION; ?>-fpm
sudo systemctl restart nginx
        </div>
        
        <h3>4. Ověřte změny:</h3>
        <p>Obnovte tuto stránku a zkontrolujte, zda se hodnoty změnily.</p>
    </div>
    
    <div class="info-box">
        <h2>📝 Poznámky</h2>
        <ul>
            <li><strong>post_max_size</strong> by měl být větší nebo roven <strong>upload_max_filesize</strong></li>
            <li><strong>memory_limit</strong> by měl být větší než <strong>post_max_size</strong></li>
            <li>Pro velmi velké soubory zvyšte <strong>max_execution_time</strong> na alespoň 300 sekund</li>
            <li>Některé hostingové služby mohou omezovat tyto hodnoty</li>
        </ul>
    </div>
</body>
</html>
