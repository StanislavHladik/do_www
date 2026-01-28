<?php
/**
 * Xdebug Configuration Checker
 * Access this file via browser to see Xdebug status
 */

echo "<h1>Xdebug Configuration Status</h1>";

// Check if Xdebug is loaded
if (extension_loaded('xdebug')) {
    echo "<p style='color: green; font-weight: bold;'>✓ Xdebug is INSTALLED and LOADED</p>";
    
    echo "<h2>Xdebug Version</h2>";
    echo "<p>" . phpversion('xdebug') . "</p>";
    
    echo "<h2>Xdebug Configuration</h2>";
    echo "<pre>";
    
    // Display relevant Xdebug settings
    $settings = [
        'xdebug.mode',
        'xdebug.start_with_request',
        'xdebug.client_host',
        'xdebug.client_port',
        'xdebug.log',
        'xdebug.log_level',
        'xdebug.idekey',
        'xdebug.remote_handler',
        'xdebug.max_nesting_level'
    ];
    
    foreach ($settings as $setting) {
        $value = ini_get($setting);
        echo str_pad($setting, 35) . " = " . ($value !== false ? $value : 'not set') . "\n";
    }
    
    echo "</pre>";
    
    echo "<h2>Full Xdebug Info</h2>";
    ob_start();
    xdebug_info();
    $xdebug_info = ob_get_clean();
    echo $xdebug_info;
    
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ Xdebug is NOT installed</p>";
    echo "<h2>Installation Instructions</h2>";
    echo "<pre>";
    echo "For Ubuntu/Debian:\n";
    echo "sudo apt-get update\n";
    echo "sudo apt-get install php-xdebug\n\n";
    
    echo "For other systems, check PHP version first:\n";
    echo "php -v\n";
    echo "</pre>";
}

echo "<h2>PHP Info (Full)</h2>";
echo "<details><summary>Click to view full phpinfo()</summary>";
phpinfo();
echo "</details>";
?>
