<?php include 'header.php'; ?>

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
        
        <!-- Training Configuration Panel -->
        <div class="training-config-panel">
            <h3><i class="fa fa-sliders"></i> Konfigurace Tréninku</h3>
            <form id="training-config-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="epochs"><i class="fa fa-repeat"></i> Počet epoch:</label>
                        <input type="number" id="epochs" name="epochs" value="100" min="1" max="1000" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="batch-size"><i class="fa fa-th"></i> Velikost batch:</label>
                        <input type="number" id="batch-size" name="batch-size" value="16" min="1" max="128" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="img-size"><i class="fa fa-image"></i> Velikost obrázku:</label>
                        <select id="img-size" name="img-size" class="form-control">
                            <option value="640" selected>640x640</option>
                            <option value="1280">1280x1280</option>
                            <option value="1920">1920x1920</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="model-type"><i class="fa fa-cube"></i> Typ modelu:</label>
                        <select id="model-type" name="model-type" class="form-control">
                            <option value="yolov8n.pt">YOLOv8 Nano (fastest)</option>
                            <option value="yolov8s.pt">YOLOv8 Small</option>
                            <option value="yolov8m.pt" selected>YOLOv8 Medium</option>
                            <option value="yolov8l.pt">YOLOv8 Large</option>
                            <option value="yolov8x.pt">YOLOv8 XLarge (best)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="model-name"><i class="fa fa-tag"></i> Název výstupního modelu:</label>
                    <input type="text" id="model-name" name="model-name" value="custom_model" class="form-control">
                    <small>Model bude uložen jako: model-name.pt</small>
                </div>
            </form>
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
