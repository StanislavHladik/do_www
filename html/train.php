<?php include 'header.php'; ?>

<?php require 'functions.php'; ?>

<link rel="stylesheet" href="css/train.css">

<main class="main-content">
    <div class="container">
        <h2><i class="fa fa-graduation-cap"></i> Trénink AI Modelu</h2>
        <p class="page-description">Trénování modelu pro stroj <?php echo($cisloStroj); ?> - <?php echo($nazevStroj); ?></p>
        
        <!-- Training Status Panel -->
        <div class="training-status-panel">
            <h3><i class="fa fa-info-circle"></i> Stav Tréninku</h3>
            <div id="training-status" class="status-box">
                <p><i class="fa fa-circle" style="color: #ccc;"></i> Žádný trénink neprobíhá</p>
            </div>
        </div>

        <?php
        // Build the datasets folder path based on machine number
        $basePath = "/home/yolo";
        $searchPattern = "st{$cisloStroj}_*";
        $matchingDirs = glob($basePath . "/" . $searchPattern, GLOB_ONLYDIR);
        
        $datasetsPath = null;
        $foundDir = null;
        
        if (!empty($matchingDirs)) {
            // Use the first matching directory
            $foundDir = basename($matchingDirs[0]);
            $datasetsPath = $matchingDirs[0] . "/Detekce_Obrazu/datasets";
            
            // Show which directory was found
            echo '<div class="alert alert-info">';
            echo '<i class="fa fa-info-circle"></i> ';
            echo "Používá se adresář: <strong>{$foundDir}</strong>";
            echo '</div>';
        }

        // Dataset Upload Section
        include 'views/upload_section.php';

        // Check if we found a matching directory and if the datasets directory exists
        if ($foundDir === null) {
            echo '<div class="alert alert-warning">';
            echo '<i class="fa fa-exclamation-triangle"></i> ';
            echo "Nenalezen žádný adresář odpovídající vzoru 'st{$cisloStroj}_*' v {$basePath}";
            echo '</div>';
        } elseif (!is_dir($datasetsPath)) {
            echo '<div class="alert alert-warning">';
            echo '<i class="fa fa-exclamation-triangle"></i> ';
            echo "Adresář datasetů nenalezen v '{$foundDir}': {$datasetsPath}";
            echo '</div>';
        } else {
            // Get all dataset directories
            $datasets = array_filter(glob($datasetsPath . "/*"), 'is_dir');
            
            if (empty($datasets)) {
                echo '<div class="alert alert-info">';
                echo '<i class="fa fa-info-circle"></i> ';
                echo "Nenalezeny žádné datasety v: {$datasetsPath}";
                echo '</div>';
            } else {
                echo '<div class="datasets-section">';
                echo '<h3><i class="fa fa-database"></i> Dostupné Datasety</h3>';
                echo '<div class="datasets-grid">';
                
                foreach ($datasets as $datasetDir) {
                    $datasetName = basename($datasetDir);
                    
                    // Count images in train and val directories
                    $trainImages = 0;
                    $valImages = 0;
                    
                    if (is_dir($datasetDir . "/train/images")) {
                        $trainImages = count(glob($datasetDir . "/train/images/*.{jpg,jpeg,png}", GLOB_BRACE));
                    }
                    if (is_dir($datasetDir . "/val/images")) {
                        $valImages = count(glob($datasetDir . "/val/images/*.{jpg,jpeg,png}", GLOB_BRACE));
                    }
                    
                    $totalImages = $trainImages + $valImages;
                    
                    echo '<div class="dataset-card">';
                    echo '<div class="dataset-header">';
                    echo '<h4><i class="fa fa-folder-open"></i> ' . htmlspecialchars($datasetName) . '</h4>';
                    echo '</div>';
                    
                    echo '<div class="dataset-details">';
                    echo '<p><strong>Cesta:</strong><br><code>' . htmlspecialchars($datasetDir) . '</code></p>';
                    echo '<p><strong>Trénovací obrázky:</strong> ' . $trainImages . '</p>';
                    echo '<p><strong>Validační obrázky:</strong> ' . $valImages . '</p>';
                    echo '<p><strong>Celkem:</strong> ' . $totalImages . ' obrázků</p>';
                    echo '</div>';
                    
                    echo '<div class="dataset-actions">';
                    
                    echo '<button class="btn btn-primary" onclick="chooseDataset(\'' . htmlspecialchars($datasetName) . '\', \'' . htmlspecialchars($datasetDir) . '\')">';
                    echo '<i class="fa fa-cog"></i> Konfigurovat';
                    echo '</button>';
                    
                    echo '<button class="btn btn-info" onclick="showDatasetInfo(\'' . htmlspecialchars($datasetName) . '\')">';
                    echo '<i class="fa fa-info"></i> Detaily';
                    echo '</button>';
                    echo '</div>';
                    
                    echo '</div>';
                }
                
                echo '</div>'; // datasets-grid
                echo '</div>'; // datasets-section
            }
        }
        ?>
        
        <!-- Training Configuration Panel (reloadable) -->
        <div id="train-config-container">
            <?php 
            includeWithVariables('views/train_configuration_section.php', [
                'datasetName' => '',
                'datasetPath' => '',
                'cisloStroj' => $cisloStroj,
                'selectedDataset' => ''
            ]); 
            ?>
        </div>

        <!-- Training Progress (hidden by default) -->
        <div id="training-progress" class="training-progress-panel" style="display: none;">
            <h3><i class="fa fa-tasks"></i> Průběh Tréninku</h3>
            <div class="progress-bar-container">
                <div id="progress-bar" class="progress-bar" style="width: 0%">0%</div>
            </div>
            <div id="training-log" class="training-log">
                <p>Čekání na start tréninku...</p>
            </div>
            <button class="btn btn-danger" onclick="stopTraining()">
                <i class="fa fa-stop"></i> Zastavit Trénink
            </button>
        </div>
        
    </div>
</main>

<script>
    // Store the current training PID
    let currentTrainingPID = null;
    
    // Store the current training log file
    let currentTrainingLog = null;
    
    // Progress polling interval
    let progressPollingInterval = null;
    
    // Start polling for training progress
    function startProgressPolling() {
        // Clear any existing interval
        if (progressPollingInterval) {
            clearInterval(progressPollingInterval);
        }
        
        // Poll every 2 seconds
        progressPollingInterval = setInterval(updateTrainingProgress, 2000);
        
        // Also update immediately
        updateTrainingProgress();
    }
    
    // Stop polling for training progress
    function stopProgressPolling() {
        if (progressPollingInterval) {
            clearInterval(progressPollingInterval);
            progressPollingInterval = null;
        }
    }
    
    // Update training progress from server
    function updateTrainingProgress() {
        let url = 'get_training_progress.php';
        if (currentTrainingLog) {
            url += '?log_file=' + encodeURIComponent(currentTrainingLog);
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update progress bar
                    const progressBar = document.getElementById('progress-bar');
                    const percentage = data.progress.percentage || 0;
                    progressBar.style.width = percentage + '%';
                    progressBar.textContent = percentage.toFixed(1) + '%';
                    
                    // Update training status
                    const statusHtml = data.running 
                        ? '<p><i class="fa fa-circle" style="color: #28a745;"></i> Trénink probíhá' +
                          (data.pid ? ' (PID: ' + data.pid + ')' : '') + '</p>'
                        : (data.completed 
                            ? '<p><i class="fa fa-check-circle" style="color: #28a745;"></i> Trénink dokončen</p>'
                            : '<p><i class="fa fa-circle" style="color: #ccc;"></i> Trénink neprobíhá</p>');
                    
                    document.getElementById('training-status').innerHTML = statusHtml;
                    
                    // Update training log display
                    const log = document.getElementById('training-log');
                    let logHtml = '<p><strong>Průběh:</strong> Epocha ' + 
                        data.progress.current_epoch + ' / ' + data.progress.total_epochs + '</p>';
                    
                    if (data.progress.total_batches > 0) {
                        logHtml += '<p><strong>Batch:</strong> ' + 
                            data.progress.batch_progress + ' / ' + data.progress.total_batches + '</p>';
                    }
                    
                    if (data.metrics.gpu_mem) {
                        logHtml += '<p><strong>GPU paměť:</strong> ' + data.metrics.gpu_mem + '</p>';
                        logHtml += '<p><strong>Ztráty:</strong> box=' + data.metrics.box_loss.toFixed(4) + 
                            ', obj=' + data.metrics.obj_loss.toFixed(4) + 
                            ', cls=' + data.metrics.cls_loss.toFixed(4) + '</p>';
                    }
                    
                    if (data.last_lines && data.last_lines.length > 0) {
                        logHtml += '<hr><pre style="font-size: 11px; max-height: 150px; overflow-y: auto;">' + 
                            data.last_lines.join('\n') + '</pre>';
                    }
                    
                    log.innerHTML = logHtml;
                    
                    // Store PID if available
                    if (data.pid) {
                        currentTrainingPID = data.pid;
                    }
                    
                    // If training completed or has error, stop polling
                    if (data.completed || data.has_error || !data.running) {
                        stopProgressPolling();
                        
                        if (data.completed) {
                            document.getElementById('training-status').innerHTML = 
                                '<p><i class="fa fa-check-circle" style="color: #28a745;"></i> Trénink úspěšně dokončen!</p>';
                        } else if (data.has_error) {
                            document.getElementById('training-status').innerHTML = 
                                '<p><i class="fa fa-exclamation-triangle" style="color: #dc3545;"></i> Trénink skončil s chybou</p>';
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching progress:', error);
            });
    }

    // Handle dataset upload form submission
    document.getElementById('dataset-upload-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fileInput = document.getElementById('dataset-file');
        const datasetName = document.getElementById('dataset-upload-name').value;
        const file = fileInput.files[0];
        
        if (!file) {
            alert('Prosím vyberte soubor k nahrání');
            return;
        }
        
        // Check file size (500 MB limit)
        const maxSize = 500 * 1024 * 1024; // 500 MB in bytes
        if (file.size > maxSize) {
            alert('Soubor je příliš velký. Maximální velikost je 500 MB.');
            return;
        }
        
        // Check file extension
        if (!file.name.endsWith('.zip')) {
            alert('Pouze .zip soubory jsou podporovány');
            return;
        }
        
        uploadDataset(file, datasetName);
    });
    
    function uploadDataset(file, customName) {
        const formData = new FormData();
        formData.append('dataset_file', file);
        formData.append('dataset_name', customName);
        formData.append('machine_number', '<?php echo($cisloStroj); ?>');
        
        const statusDiv = document.getElementById('upload-status');
        const statusText = document.getElementById('upload-status-text');
        const progressContainer = document.getElementById('upload-progress-container');
        const progressBar = document.getElementById('upload-progress-bar');
        
        // Show status
        statusDiv.style.display = 'block';
        progressContainer.style.display = 'block';
        statusText.textContent = 'Nahrávání datasetu...';
        statusDiv.querySelector('.alert').className = 'alert alert-info';
        statusDiv.querySelector('.fa').className = 'fa fa-spinner fa-spin';
        
        // Create XMLHttpRequest for progress tracking
        const xhr = new XMLHttpRequest();
        
        // Track upload progress
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percentComplete + '%';
                progressBar.textContent = percentComplete + '%';
                statusText.textContent = `Nahrávání datasetu... ${percentComplete}%`;
            }
        });
        
        // Handle completion
        xhr.addEventListener('load', function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        statusText.textContent = response.message;
                        statusDiv.querySelector('.alert').className = 'alert alert-success';
                        statusDiv.querySelector('.fa').className = 'fa fa-check-circle';
                        
                        // Reset form
                        document.getElementById('dataset-upload-form').reset();
                        
                        // Reload page after 2 seconds to show new dataset
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        statusText.textContent = 'Chyba: ' + response.message;
                        statusDiv.querySelector('.alert').className = 'alert alert-danger';
                        statusDiv.querySelector('.fa').className = 'fa fa-exclamation-triangle';
                        progressContainer.style.display = 'none';
                    }
                } catch (e) {
                    statusText.textContent = 'Chyba při zpracování odpovědi serveru';
                    statusDiv.querySelector('.alert').className = 'alert alert-danger';
                    statusDiv.querySelector('.fa').className = 'fa fa-exclamation-triangle';
                    progressContainer.style.display = 'none';
                }
            } else {
                // Try to parse error response for more details
                let errorMsg = 'Chyba sítě: ' + xhr.status;
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMsg = 'Chyba ' + xhr.status + ': ' + response.message;
                        if (response.error_line) {
                            errorMsg += ' (řádek ' + response.error_line + ')';
                        }
                    }
                } catch (e) {
                    // If response is not JSON, show raw text (truncated)
                    if (xhr.responseText && xhr.responseText.length > 0) {
                        errorMsg += ' - ' + xhr.responseText.substring(0, 200);
                    }
                }
                
                statusText.textContent = errorMsg;
                statusDiv.querySelector('.alert').className = 'alert alert-danger';
                statusDiv.querySelector('.fa').className = 'fa fa-exclamation-triangle';
                progressContainer.style.display = 'none';
                
                // Log full error to console for debugging
                console.error('Upload error details:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    response: xhr.responseText
                });
            }
        });
        
        // Handle errors
        xhr.addEventListener('error', function() {
            statusText.textContent = 'Chyba při nahrávání souboru';
            statusDiv.querySelector('.alert').className = 'alert alert-danger';
            statusDiv.querySelector('.fa').className = 'fa fa-exclamation-triangle';
            progressContainer.style.display = 'none';
        });
        
        // Send request
        xhr.open('POST', 'upload_dataset.php', true);
        xhr.send(formData);
    }

    function chooseDataset(datasetName, datasetPath) {
        // Load the training configuration section with selected dataset parameters
        const formData = new FormData();
        formData.append('dataset_name', datasetName);
        formData.append('dataset_path', datasetPath);
        formData.append('cislo_stroj', '<?php echo($cisloStroj); ?>');
        
        // Show loading indicator
        const container = document.getElementById('train-config-container');
        const originalContent = container.innerHTML;
        container.innerHTML = '<div class="alert alert-info"><i class="fa fa-spinner fa-spin"></i> Načítání konfigurace...</div>';
        
        fetch('load_train_config.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            // Update the container with new content
            container.innerHTML = html;
            
            // Attach form submit handler to the newly loaded form
            attachTrainingFormHandler();
            
            // Scroll to the configuration section
            container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Show success message temporarily
            const successMsg = document.createElement('div');
            successMsg.className = 'alert alert-success';
            successMsg.innerHTML = '<i class="fa fa-check-circle"></i> Dataset "' + datasetName + '" byl vybrán pro konfiguraci';
            successMsg.style.marginBottom = '15px';
            container.insertBefore(successMsg, container.firstChild);
            
            setTimeout(() => {
                successMsg.remove();
            }, 3000);
        })
        .catch(error => {
            console.error('Error loading configuration:', error);
            container.innerHTML = originalContent;
            alert('Chyba při načítání konfigurace: ' + error.message);
        });
    }
    
    // Attach training form submit handler
    function attachTrainingFormHandler() {
        const form = document.getElementById('training-config-form');
        if (!form) return;
        
        // Remove any existing event listeners
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        
        newForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values
            const datasetName = document.getElementById('selected-dataset-name').value;
            const datasetPath = document.getElementById('selected-dataset-path').value;
            const epochs = document.getElementById('epochs').value;
            const batchSize = document.getElementById('batch-size').value;
            const imgSize = document.getElementById('img-size').value;
            const modelType = document.getElementById('model-type').value;
            const modelName = document.getElementById('model-name').value;
            
            // Validate dataset is selected
            if (!datasetName || !datasetPath) {
                alert('Prosím nejprve vyberte dataset pomocí tlačítka "Konfigurovat"');
                return;
            }
            
            // Show confirmation dialog
            const confirmMsg = `Zahájit trénink modelu?\n\n` +
                `Dataset: ${datasetName}\n` +
                `Epochy: ${epochs}\n` +
                `Batch Size: ${batchSize}\n` +
                `Velikost obrázku: ${imgSize}x${imgSize}\n` +
                `Model: ${modelType}\n` +
                `Název výstupu: ${modelName}`;
            
            if (!confirm(confirmMsg)) {
                return;
            }
            
            // Prepare form data
            const formData = new FormData();
            formData.append('dataset_name', datasetName);
            formData.append('dataset_path', datasetPath);
            formData.append('epochs', epochs);
            formData.append('batch_size', batchSize);
            formData.append('img_size', imgSize);
            formData.append('model_type', modelType);
            formData.append('model_name', modelName);
            formData.append('machine_number', '<?php echo($cisloStroj); ?>');
            
            // Show progress panel immediately
            document.getElementById('training-progress').style.display = 'block';
            document.getElementById('training-status').innerHTML = '<p><i class="fa fa-spinner fa-spin"></i> Inicializace tréninku...</p>';
            
            // Disable submit button to prevent double submission
            const submitBtn = newForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Zahajování...';
            
            // Send request to start training
            fetch('start_training.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Store the PID for later use (stop training)
                    currentTrainingPID = data.pid;
                    
                    // Store the training log file path
                    currentTrainingLog = data.training_log;
                    
                    // Show success message
                    document.getElementById('training-status').innerHTML = 
                        '<p><i class="fa fa-check-circle" style="color: #28a745;"></i> ' + data.message + '</p>' +
                        (data.pid ? '<p><small>PID procesu: <strong>' + data.pid + '</strong></small></p>' : '');
                    
                    // Update training log
                    const log = document.getElementById('training-log');
                    log.innerHTML = '<p><strong>Trénink zahájen úspěšně!</strong></p>' +
                        '<p>Konfigurační soubor: <code>' + data.config_file + '</code></p>' +
                        '<p>Log soubor: <code>' + data.training_log + '</code></p>' +
                        (data.pid ? '<p>PID procesu: <code>' + data.pid + '</code></p>' : '') +
                        '<p>Parametry:</p>' +
                        '<pre>' + JSON.stringify(data.config, null, 2) + '</pre>' +
                        '<p><em>Načítání průběhu tréninku...</em></p>';
                    
                    // Start polling for progress updates
                    startProgressPolling();
                    
                    // Re-enable button with different text
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa fa-check"></i> Trénink spuštěn';
                    
                } else {
                    // Show error
                    document.getElementById('training-status').innerHTML = 
                        '<p><i class="fa fa-exclamation-triangle" style="color: #dc3545;"></i> Chyba při zahájení tréninku</p>';
                    
                    const log = document.getElementById('training-log');
                    log.innerHTML = '<p><strong style="color: #dc3545;">Chyba:</strong> ' + data.message + '</p>' +
                        (data.log_file ? '<p>Pro více informací viz: <code>' + data.log_file + '</code></p>' : '');
                    
                    alert('Chyba při zahájení tréninku:\n' + data.message);
                    
                    // Re-enable button
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa fa-upload"></i> Zahájit Trénink';
                }
            })
            .catch(error => {
                console.error('Training error:', error);
                
                document.getElementById('training-status').innerHTML = 
                    '<p><i class="fa fa-exclamation-triangle" style="color: #dc3545;"></i> Chyba sítě</p>';
                
                const log = document.getElementById('training-log');
                log.innerHTML = '<p><strong style="color: #dc3545;">Chyba sítě:</strong> ' + error.message + '</p>';
                
                alert('Chyba při komunikaci se serverem:\n' + error.message);
                
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa fa-upload"></i> Zahájit Trénink';
            });
        });
    }
    
    // Attach handler on page load for the initial form
    document.addEventListener('DOMContentLoaded', function() {
        attachTrainingFormHandler();
    });

    function stopTraining() {
        if (confirm('Opravdu chcete zastavit trénink?')) {
            // Stop progress polling
            stopProgressPolling();
            
            // Show stopping status
            document.getElementById('training-status').innerHTML = 
                '<p><i class="fa fa-spinner fa-spin"></i> Zastavování tréninku...</p>';
            
            // Prepare form data with PID
            const formData = new FormData();
            if (currentTrainingPID) {
                formData.append('pid', currentTrainingPID);
            }
            
            // Send request to stop training
            fetch('stop_training.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear stored PID and log file
                    currentTrainingPID = null;
                    currentTrainingLog = null;
                    
                    // Hide progress panel
                    document.getElementById('training-progress').style.display = 'none';
                    
                    // Show stopped status
                    document.getElementById('training-status').innerHTML = 
                        '<p><i class="fa fa-circle" style="color: #dc3545;"></i> ' + data.message + '</p>' +
                        (data.pid ? '<p><small>PID: ' + data.pid + ' byl ukončen</small></p>' : '');
                    
                    alert(data.message + (data.was_running ? '' : '\n(Proces již neběžel)'));
                } else {
                    document.getElementById('training-status').innerHTML = 
                        '<p><i class="fa fa-exclamation-triangle" style="color: #dc3545;"></i> Chyba: ' + data.message + '</p>';
                    alert('Chyba při zastavování tréninku:\n' + data.message);
                }
            })
            .catch(error => {
                console.error('Stop training error:', error);
                document.getElementById('training-status').innerHTML = 
                    '<p><i class="fa fa-exclamation-triangle" style="color: #dc3545;"></i> Chyba sítě</p>';
                alert('Chyba při komunikaci se serverem:\n' + error.message);
            });
        }
    }
    
    function showDatasetInfo(datasetName) {
        alert(`Detaily datasetu: ${datasetName}\n\nTato funkce bude implementována.`);
        // TODO: Show detailed dataset information in modal
    }
</script>

<?php include 'footer.php'; ?>