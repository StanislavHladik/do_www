        <!-- Training Configuration Panel -->
        <div class="training-config-panel">
            <h3><i class="fa fa-sliders"></i> Konfigurace Tréninku</h3>
            
            <?php if (!empty($datasetName)): ?>
            <div class="alert alert-info">
                <strong><i class="fa fa-database"></i> Vybraný dataset:</strong> <?php echo htmlspecialchars($datasetName); ?><br>
                <small><strong>Cesta:</strong> <code><?php echo htmlspecialchars($datasetPath); ?></code></small>
            </div>
            <?php endif; ?>
            
            <form id="training-config-form">
                <!-- Hidden fields for dataset info -->
                <input type="hidden" id="selected-dataset-name" name="selected-dataset-name" value="<?php echo htmlspecialchars($datasetName ?? ''); ?>">
                <input type="hidden" id="selected-dataset-path" name="selected-dataset-path" value="<?php echo htmlspecialchars($datasetPath ?? ''); ?>">
                
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
                        <input type="number" id="img-size" name="img-size" value="1920" min="1" max="65536" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="model-type"><i class="fa fa-cube"></i> Typ modelu:</label>
                        <select id="model-type" name="model-type" class="form-control">
                            <?php
                            // Include the weight files logic
                            require_once __DIR__ . '/../get_weights.php';
                            
                            // Get available weight files
                            $weights_result = getWeightFiles();
                            
                            if ($weights_result['success'] && !empty($weights_result['files'])) {
                                // Use actual weight files from directory
                                $default_weight = getDefaultWeight($weights_result['files']);
                                
                                foreach ($weights_result['files'] as $weight) {
                                    $selected = ($weight['filename'] === $default_weight) ? 'selected' : '';
                                    
                                    echo '<option value="' . htmlspecialchars($weight['filename']) . '" ' . $selected . '>';
                                    echo htmlspecialchars($weight['display_name']);
                                    echo '</option>';
                                }
                            } else {
                                // Use fallback weight options
                                $fallback_weights = getFallbackWeights();
                                
                                foreach ($fallback_weights as $weight) {
                                    $selected = (!empty($weight['is_default'])) ? 'selected' : '';
                                    
                                    echo '<option value="' . htmlspecialchars($weight['filename']) . '" ' . $selected . '>';
                                    echo htmlspecialchars($weight['display_name']);
                                    echo '</option>';
                                }
                            }
                            ?>
                        </select>
                        <?php if ($weights_result['success']): ?>
                        <small><i class="fa fa-check-circle" style="color: #28a745;"></i> <?php echo htmlspecialchars($weights_result['message']); ?></small>
                        <?php else: ?>
                        <small><i class="fa fa-exclamation-triangle" style="color: #ffc107;"></i> <?php echo htmlspecialchars($weights_result['message']); ?> - použity výchozí volby</small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="model-name"><i class="fa fa-tag"></i> Název výstupního modelu:</label>
                    <input type="text" id="model-name" name="model-name" value="custom_model" class="form-control">
                    <small>Model bude uložen jako: model-name.pt</small>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-upload"></i> Zahájit Trénink
                </button>
            </form>
        </div>