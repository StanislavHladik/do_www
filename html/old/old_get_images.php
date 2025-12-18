<?php
        $dir = 'nahledy/'; // Directory where images are stored

        $files = scandir($dir); // Scan the directory

        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                // Display each image file
                echo ("<img src='$dir$file' alt='$file' style='max-width: 500px; margin: 10px;'>");
            }
        }
?>