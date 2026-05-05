<?php include 'header.php'; ?>

<main class="main-content">
    <div class="container">
        <h2>Dostupné AI Modely</h2>
        <p class="page-description">Vyberte dostupný .pt model pro stroj <?php echo($cisloStroj); ?></p>
        
        <!-- Service Control Panel -->
        <div class="service-control-panel">
            <h3><i class="fa fa-cogs"></i> Ovládání Služby Detekce</h3>
            <p>Restartujte službu detekce pro aplikování změn konfigurace.</p>
            <button id="restart-service-btn" class="btn btn-warning" onclick="restartDetectionService()">
                <i class="fa fa-refresh"></i> Restartovat Službu
            </button>
            <div id="service-status" class="service-status" style="display: none;">
                <div class="alert">
                    <i class="fa fa-info-circle"></i>
                    <span id="service-status-text"></span>
                </div>
            </div>
        </div>
        
        <?php
        // Build the models folder path based on machine number
        // Search for any folder that starts with st{cisloStroj}_
        $basePath = "/home/yolo";
        $searchPattern = "st{$cisloStroj}_*";
        $matchingDirs = glob($basePath . "/" . $searchPattern, GLOB_ONLYDIR);
        
        $modelsPath = null;
        $foundDir = null;
        
        if (!empty($matchingDirs)) {
            // Use the first matching directory
            $foundDir = basename($matchingDirs[0]);
            $modelsPath = $matchingDirs[0] . "/Detekce_Obrazu/models";
            
            // Show which directory was found
            echo '<div class="alert alert-info">';
            echo '<i class="fa fa-info-circle"></i> ';
            echo "Používá se adresář: <strong>{$foundDir}</strong>";
            echo '</div>';
        }
        
        // Check if we found a matching directory and if the models directory exists
        if ($foundDir === null) {
            echo '<div class="alert alert-warning">';
            echo '<i class="fa fa-exclamation-triangle"></i> ';
            echo "Nenalezen žádný adresář odpovídající vzoru 'st{$cisloStroj}_*' v {$basePath}";
            echo '</div>';
        } elseif (!is_dir($modelsPath)) {
            echo '<div class="alert alert-warning">';
            echo '<i class="fa fa-exclamation-triangle"></i> ';
            echo "Adresář modelů nenalezen v '{$foundDir}': {$modelsPath}";
            echo '</div>';
        } else {
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
            
            // Scan for subdirectory-grouped models and direct .pt files (legacy)
            $subDirs = glob($modelsPath . "/*", GLOB_ONLYDIR);
            $directPtFiles = glob($modelsPath . "/*.pt");

            if (empty($subDirs) && empty($directPtFiles)) {
                echo '<div class="alert alert-info">';
                echo '<i class="fa fa-info-circle"></i> ';
                echo "Nenalezeny žádné modely v: {$modelsPath}";
                echo '</div>';
            } else {
                echo '<div class="models-grid">';
                
                // Folder-based model groups
                foreach ($subDirs as $subDir) {
                    $folderName = basename($subDir);
                    $ptFilesInFolder = glob($subDir . "/*.pt");

                    if (empty($ptFilesInFolder)) {
                        continue;
                    }

                    // Check if any file in this folder is the current model
                    // weights_name may be stored as "SubFolder/file.pt" or legacy "file.pt"
                    $isFolderActive = false;
                    foreach ($ptFilesInFolder as $pf) {
                        $pfBase = basename($pf);
                        if ($currentModelName !== null && (
                            $pfBase === $currentModelName ||
                            $folderName . '/' . $pfBase === $currentModelName
                        )) {
                            $isFolderActive = true;
                            break;
                        }
                    }

                    $fileCount = count($ptFilesInFolder);
                    $folderTotalSize = array_sum(array_map('filesize', $ptFilesInFolder));
                    $latestMtime = max(array_map('filemtime', $ptFilesInFolder));
                    $safeFolderName = htmlspecialchars($folderName, ENT_QUOTES);

                    echo '<div class="model-card folder-model-card' . ($isFolderActive ? ' active-model' : '') . '">';
                    echo '<div class="model-header">';
                    echo '<h3><i class="fa fa-folder-open"></i> ' . htmlspecialchars($folderName);
                    if ($isFolderActive) {
                        echo ' <span class="badge badge-success"><i class="fa fa-check"></i> Aktuálně aktivní</span>';
                    }
                    echo '</h3>';
                    echo '</div>';

                    echo '<div class="model-details">';
                    echo '<p><strong>Počet modelů:</strong> ' . $fileCount . '</p>';
                    echo '<p><strong>Celková velikost:</strong> ' . formatFileSize($folderTotalSize) . '</p>';
                    echo '<p><strong>Poslední úprava:</strong> ' . date("Y-m-d H:i:s", $latestMtime) . '</p>';
                    echo '</div>';

                    echo '<div class="model-file-list">';
                    echo '<p><strong><i class="fa fa-list"></i> Verze modelu:</strong></p>';
                    foreach ($ptFilesInFolder as $idx => $ptFile) {
                        $ptName = basename($ptFile);
                        $ptSize = formatFileSize(filesize($ptFile));
                        $ptDate = date("Y-m-d H:i:s", filemtime($ptFile));
                        $isThisFileCurrent = ($currentModelName !== null && (
                            $currentModelName === $ptName ||
                            $currentModelName === $folderName . '/' . $ptName
                        ));
                        $radioId = 'radio-' . $safeFolderName . '-' . $idx;
                        $isChecked = $isThisFileCurrent;

                        echo '<label class="model-file-radio' . ($isThisFileCurrent ? ' current-file' : '') . '" for="' . $radioId . '">';
                        echo '<input type="radio" name="model_selection" id="' . $radioId . '" ';
                        echo 'value="' . htmlspecialchars($ptFile, ENT_QUOTES) . '" ';
                        echo 'data-name="' . htmlspecialchars($ptName, ENT_QUOTES) . '" ';
                        echo 'onchange="selectModel(this.dataset.name, this.value)"';
                        echo ($isChecked ? ' checked' : '') . '>';
                        echo '<span class="file-label">';
                        echo '<span class="file-label-name"><i class="fa fa-cube"></i> ' . htmlspecialchars($ptName) . '</span>';
                        if ($isThisFileCurrent) {
                            echo ' <span class="badge badge-success"><i class="fa fa-check"></i> Aktivní</span>';
                        }
                        echo '<span class="file-label-meta">' . $ptSize . ' &bull; ' . $ptDate . '</span>';
                        echo '</span>';
                        echo '</label>';
                    }
                    echo '</div>'; // end model-file-list

                    echo '</div>'; // end folder model-card
                }

                // Legacy: direct .pt files in models root
                foreach ($directPtFiles as $modelFile) {
                    $fileName = basename($modelFile);
                    $filePath = $modelFile;
                    $fileSize = filesize($modelFile);
                    $fileDate = date("Y-m-d H:i:s", filemtime($modelFile));
                    $isCurrentModel = ($currentModelName !== null && $currentModelName === $fileName);
                    $sizeFormatted = formatFileSize($fileSize);

                    echo '<div class="model-card' . ($isCurrentModel ? ' active-model' : '') . '">';
                    echo '<div class="model-header">';
                    echo '<h3><i class="fa fa-cube"></i> ' . htmlspecialchars($fileName);
                    if ($isCurrentModel) {
                        echo ' <span class="badge badge-success"><i class="fa fa-check"></i> Aktuálně vybraný</span>';
                    }
                    echo '</h3>';
                    echo '</div>';

                    echo '<div class="model-details">';
                    echo '<p><strong>Cesta k souboru:</strong><br><code>' . htmlspecialchars($filePath) . '</code></p>';
                    echo '<p><strong>Velikost:</strong> ' . $sizeFormatted . '</p>';
                    echo '<p><strong>Upraveno:</strong> ' . $fileDate . '</p>';
                    echo '</div>';

                    echo '<div class="model-actions">';
                    echo '<button class="btn btn-primary" onclick="selectModel(\'' . htmlspecialchars($fileName, ENT_QUOTES) . '\', \'' . htmlspecialchars($filePath, ENT_QUOTES) . '\')">';
                    echo '<i class="fa fa-check"></i> Vybrat Model';
                    echo '</button>';
                    echo '<button class="btn btn-info" onclick="showModelInfo(\'' . htmlspecialchars($fileName, ENT_QUOTES) . '\')">';
                    echo '<i class="fa fa-info"></i> Detaily';
                    echo '</button>';
                    echo '</div>';

                    echo '</div>';
                }
                
                echo '</div>';
                
                // Add model selection status
                echo '<div id="selection-status" class="selection-status" style="display: none;">';
                echo '<div class="alert alert-success">';
                echo '<i class="fa fa-check-circle"></i> ';
                echo '<span id="selected-model-name"></span> byl úspěšně vybrán!';
                echo '</div>';
                echo '</div>';
            }
        }
        
        // Function to format file size
        function formatFileSize($bytes) {
            if ($bytes >= 1073741824) {
                return number_format($bytes / 1073741824, 2) . ' GB';
            } elseif ($bytes >= 1048576) {
                return number_format($bytes / 1048576, 2) . ' MB';
            } elseif ($bytes >= 1024) {
                return number_format($bytes / 1024, 2) . ' KB';
            } else {
                return $bytes . ' bytes';
            }
        }
        ?>
        
        <!-- Model Information Modal -->
        <div id="modelInfoModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Informace o Modelu</h3>
                    <button class="modal-close" onclick="closeModelInfo()">&times;</button>
                </div>
                <div class="modal-body" id="modelInfoBody">
                    <!-- Model details will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// JavaScript functions for model selection and information

// Select model from a folder card (reads the chosen radio button)
function selectFolderModel(folderName) {
    const radios = document.querySelectorAll('input[name="folder-' + folderName + '"]');
    let selectedRadio = null;
    for (const radio of radios) {
        if (radio.checked) {
            selectedRadio = radio;
            break;
        }
    }
    if (!selectedRadio) {
        alert('Prosím vyberte verzi modelu.');
        return;
    }
    selectModel(selectedRadio.dataset.name, selectedRadio.value);
}

function selectModel(modelName, modelPath) {
    // Show loading state
    const statusDiv = document.getElementById('selection-status');
    const statusText = document.getElementById('selected-model-name');
    
    statusText.textContent = 'Ukládání ' + modelName + '...';
    statusDiv.style.display = 'block';
    statusDiv.querySelector('.alert').className = 'alert alert-info';
    statusDiv.querySelector('.fa').className = 'fa fa-spinner fa-spin';
    
    // Save model selection via AJAX
    saveModelSelection(modelPath, modelName);
}

function showModelInfo(modelName) {
    // Display detailed information about the model
    const modalBody = document.getElementById('modelInfoBody');
    modalBody.innerHTML = `
        <h4>${modelName}</h4>
        <p><strong>Typ Modelu:</strong> YOLOv5 PyTorch Model</p>
        <p><strong>Framework:</strong> PyTorch</p>
        <p><strong>Popis:</strong> Toto je trénovaný soubor modelu pro detekci objektů pomocí architektury YOLOv5.</p>
        <p><strong>Použití:</strong> Tento model lze načíst do detekčního systému pro detekci a analýzu objektů v reálném čase.</p>
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i>
            Ujistěte se, že je model kompatibilní s aktuální konfigurací detekce před jeho výběrem.
        </div>
    `;
    
    document.getElementById('modelInfoModal').style.display = 'flex';
}

function closeModelInfo() {
    document.getElementById('modelInfoModal').style.display = 'none';
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('modelInfoModal');
    if (event.target === modal) {
        closeModelInfo();
    }
});

// Function to restart detection service
function restartDetectionService() {
    const restartBtn = document.getElementById('restart-service-btn');
    const statusDiv = document.getElementById('service-status');
    const statusText = document.getElementById('service-status-text');
    
    // Show loading state
    restartBtn.disabled = true;
    restartBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Vytváření požadavku...';
    
    statusText.textContent = 'Vytváření požadavku na restart služby...';
    statusDiv.style.display = 'block';
    statusDiv.querySelector('.alert').className = 'alert alert-info';
    statusDiv.querySelector('.fa').className = 'fa fa-spinner fa-spin';
    
    fetch('detection_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'restart_service',
            machine_number: '<?php echo($cisloStroj); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Service restart request result:', data);
        
        const alertDiv = statusDiv.querySelector('.alert');
        const iconElement = statusDiv.querySelector('.fa');
        
        if (data.success) {
            // Success - request created
            statusText.textContent = data.message + ' - Monitorování stavu...';
            alertDiv.className = 'alert alert-success';
            iconElement.className = 'fa fa-check-circle';
            
            // Start monitoring the restart status
            startRestartStatusMonitoring();
        } else {
            // Error
            statusText.textContent = 'Chyba: ' + data.message;
            alertDiv.className = 'alert alert-danger';
            iconElement.className = 'fa fa-exclamation-triangle';
            
            // Reset button
            restartBtn.disabled = false;
            restartBtn.innerHTML = '<i class="fa fa-refresh"></i> Restartovat Službu';
        }
        
        // Scroll to the status message
        statusDiv.scrollIntoView({ 
            behavior: 'smooth' 
        });
    })
    .catch(error => {
        console.error('Error creating restart request:', error);
        
        const alertDiv = statusDiv.querySelector('.alert');
        const iconElement = statusDiv.querySelector('.fa');
        
        statusText.textContent = 'Chyba sítě při vytváření požadavku na restart';
        alertDiv.className = 'alert alert-danger';
        iconElement.className = 'fa fa-exclamation-triangle';
        
        // Reset button
        restartBtn.disabled = false;
        restartBtn.innerHTML = '<i class="fa fa-refresh"></i> Restartovat Službu';
        
        statusDiv.scrollIntoView({ 
            behavior: 'smooth' 
        });
    });
}

// Function to monitor restart status
function startRestartStatusMonitoring() {
    const checkInterval = setInterval(() => {
        checkRestartStatus(checkInterval);
    }, 3000); // Check every 3 seconds
    
    // Stop monitoring after 2 minutes
    setTimeout(() => {
        clearInterval(checkInterval);
        const restartBtn = document.getElementById('restart-service-btn');
        restartBtn.disabled = false;
        restartBtn.innerHTML = '<i class="fa fa-refresh"></i> Restartovat Službu';
    }, 120000);
}

// Function to check restart status
function checkRestartStatus(intervalId) {
    fetch('detection_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'check_restart_status'
        })
    })
    .then(response => response.json())
    .then(data => {
        const statusDiv = document.getElementById('service-status');
        const statusText = document.getElementById('service-status-text');
        const alertDiv = statusDiv.querySelector('.alert');
        const iconElement = statusDiv.querySelector('.fa');
        const restartBtn = document.getElementById('restart-service-btn');
        
        if (data.success && data.status) {
            switch(data.status) {
                case 'completed':
                    statusText.textContent = 'Služba byla úspěšně restartována!';
                    alertDiv.className = 'alert alert-success';
                    iconElement.className = 'fa fa-check-circle';
                    
                    // Stop monitoring
                    clearInterval(intervalId);
                    restartBtn.disabled = false;
                    restartBtn.innerHTML = '<i class="fa fa-refresh"></i> Restartovat Službu';
                    break;
                    
                case 'failed':
                    statusText.textContent = 'Restart služby selhal. Zkuste to znovu.';
                    alertDiv.className = 'alert alert-danger';
                    iconElement.className = 'fa fa-exclamation-triangle';
                    
                    // Stop monitoring
                    clearInterval(intervalId);
                    restartBtn.disabled = false;
                    restartBtn.innerHTML = '<i class="fa fa-refresh"></i> Restartovat Službu';
                    break;
                    
                case 'processing':
                    statusText.textContent = 'Restart služby probíhá...';
                    alertDiv.className = 'alert alert-info';
                    iconElement.className = 'fa fa-spinner fa-spin';
                    break;
                    
                case 'pending':
                    statusText.textContent = 'Čeká na zpracování monitorovací službou...';
                    alertDiv.className = 'alert alert-info';
                    iconElement.className = 'fa fa-clock-o';
                    break;
                    
                case 'none':
                    // No restart request found - stop monitoring
                    clearInterval(intervalId);
                    restartBtn.disabled = false;
                    restartBtn.innerHTML = '<i class="fa fa-refresh"></i> Restartovat Službu';
                    break;
            }
        }
    })
    .catch(error => {
        console.error('Error checking restart status:', error);
    });
}

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
        console.log('Current model retrieval result:', data);
        
        if (data.success) {
            const currentModel = data.current_model;
            console.log('Aktuálně vybraný model:', currentModel);
            // You can use currentModel as needed
        } else {
            console.error('Chyba při získávání aktuálního modelu:', data.message);
        }
    })
    .catch(error => {
        console.error('Error retrieving current model:', error);
    });
}

// Function to save model selection via AJAX
function saveModelSelection(modelPath, modelName) {
    fetch('detection_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'save_model_selection',
            model_path: modelPath,
            machine_number: '<?php echo($cisloStroj); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Model selection result:', data);
        
        const statusDiv = document.getElementById('selection-status');
        const statusText = document.getElementById('selected-model-name');
        const alertDiv = statusDiv.querySelector('.alert');
        const iconElement = statusDiv.querySelector('.fa');
        
        if (data.success) {
            // Success
            statusText.textContent = modelName + ' byl úspěšně vybrán!';
            alertDiv.className = 'alert alert-success';
            iconElement.className = 'fa fa-check-circle';
        } else {
            // Error
            statusText.textContent = 'Nepodařilo se vybrat ' + modelName + ': ' + data.message;
            alertDiv.className = 'alert alert-danger';
            iconElement.className = 'fa fa-exclamation-triangle';
        }
        
        // Scroll to the status message
        statusDiv.scrollIntoView({ 
            behavior: 'smooth' 
        });
    })
    .catch(error => {
        console.error('Error saving model selection:', error);
        
        const statusDiv = document.getElementById('selection-status');
        const statusText = document.getElementById('selected-model-name');
        const alertDiv = statusDiv.querySelector('.alert');
        const iconElement = statusDiv.querySelector('.fa');
        
        statusText.textContent = 'Chyba sítě při výběru ' + modelName;
        alertDiv.className = 'alert alert-danger';
        iconElement.className = 'fa fa-exclamation-triangle';
        
        statusDiv.scrollIntoView({ 
            behavior: 'smooth' 
        });
    });
}
</script>

<?php include 'footer.php'; ?>
