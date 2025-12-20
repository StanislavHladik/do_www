<?php
// Include with Variables
function includeWithVariables($filePath, $variables = array(), $print = true)
{
    $output = NULL;
    if(file_exists($filePath)){
        // Extract variables into a local scope
        extract($variables);
        // Start output buffering
        ob_start();
        // Include the file
        include $filePath;
        // End buffering and return its contents
        $output = ob_get_clean();
    }
    if ($print) {
        print $output;
    }
    return $output;
}
?>