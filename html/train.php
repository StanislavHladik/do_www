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
                    echo '<button class="btn btn-success" onclick="startTraining(\'' . htmlspecialchars($datasetName) . '\', \'' . htmlspecialchars($datasetDir) . '\')">';
                    echo '<i class="fa fa-play"></i> Zahájit Trénink';
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

        <!-- Dataset Upload Section -->
        <?php include 'views/upload_section.php'; ?>
        
        <!-- Training Configuration Panel -->
        <?php include 'views/train_configuration_section.php'; ?>

        <?php includeWithVariables('views/train_configuration_section.php', array('test' => 'Ahoj')); ?>

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

    function startTraining(datasetName, datasetPath) {
        // Get configuration values
        const epochs = document.getElementById('epochs').value;
        const batchSize = document.getElementById('batch-size').value;
        const imgSize = document.getElementById('img-size').value;
        const modelType = document.getElementById('model-type').value;
        const modelName = document.getElementById('model-name').value;
        
        // Show confirmation
        if (!confirm(`Zahájit trénink modelu?\n\nDataset: ${datasetName}\nEpochy: ${epochs}\nBatch: ${batchSize}\nVelikost: ${imgSize}x${imgSize}`)) {
            return;
        }
        
        // Show progress panel
        document.getElementById('training-progress').style.display = 'block';
        document.getElementById('training-status').innerHTML = '<p><i class="fa fa-spinner fa-spin"></i> Trénink probíhá...</p>';
        
        // TODO: Implement actual training call via AJAX
        console.log('Starting training with:', {
            dataset: datasetName,
            path: datasetPath,
            epochs: epochs,
            batchSize: batchSize,
            imgSize: imgSize,
            modelType: modelType,
            modelName: modelName
        });
        
        // Simulate training progress (replace with actual implementation)
        simulateTraining();
    }
    
    function stopTraining() {
        if (confirm('Opravdu chcete zastavit trénink?')) {
            // TODO: Implement training stop via AJAX
            document.getElementById('training-progress').style.display = 'none';
            document.getElementById('training-status').innerHTML = '<p><i class="fa fa-circle" style="color: #dc3545;"></i> Trénink zastaven</p>';
        }
    }
    
    function showDatasetInfo(datasetName) {
        alert(`Detaily datasetu: ${datasetName}\n\nTato funkce bude implementována.`);
        // TODO: Show detailed dataset information in modal
    }
    
    // Simulation function (replace with actual implementation)
    function simulateTraining() {
        let progress = 0;
        const interval = setInterval(() => {
            progress += 1;
            if (progress > 100) {
                clearInterval(interval);
                document.getElementById('training-status').innerHTML = '<p><i class="fa fa-check-circle" style="color: #28a745;"></i> Trénink dokončen</p>';
                return;
            }
            
            document.getElementById('progress-bar').style.width = progress + '%';
            document.getElementById('progress-bar').textContent = progress + '%';
            
            // Add log message
            const log = document.getElementById('training-log');
            const message = `Epoch ${Math.floor(progress/10)}/10 - Progress: ${progress}%`;
            log.innerHTML += `<p>${message}</p>`;
            log.scrollTop = log.scrollHeight;
        }, 200);
    }
</script>

<?php include 'footer.php'; ?>
