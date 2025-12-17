<!DOCTYPE html>
<html>  
    <head>
        <script src="script/jquery-3.7.1.min.js"></script>
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/navigation.css">
        <link rel="stylesheet" href="css/index.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <title>Detekce obrazu - Výběr stroje</title>
    </head>
    <body>   
        <header class="site-header">
            <h1>Systém Detekce Obrazu</h1>
            <p>Vyberte pracovní stanici</p>
        </header>
        
        <main>
            <?php
            // Scan for st* directories in /home/yolo
            $basePath = "/home/yolo";
            $allDirs = scandir($basePath);
            $productionMachines = array();
            $specialMachines = array();
            
            // Find all directories that match st{number}_* pattern
            foreach ($allDirs as $dir) {
                if (is_dir($basePath . "/" . $dir) && preg_match('/^st(\d+)_(.+)$/', $dir, $matches)) {
                    $machineNumber = $matches[1];
                    $machineName = $matches[2];
                    
                    $machine = array(
                        'number' => $machineNumber,
                        'name' => $machineName,
                        'fullDir' => $dir,
                        'path' => $basePath . "/" . $dir
                    );
                    
                    // Separate special machines (99 = training, 100 = test)
                    if ($machineNumber == '99' || $machineNumber == '100') {
                        $specialMachines[] = $machine;
                    } else {
                        $productionMachines[] = $machine;
                    }
                }
            }
            
            // Sort machines by number
            usort($productionMachines, function($a, $b) {
                return intval($a['number']) - intval($b['number']);
            });
            usort($specialMachines, function($a, $b) {
                return intval($a['number']) - intval($b['number']);
            });
            
            if (empty($productionMachines) && empty($specialMachines)) {
                echo '<div class="no-machines">';
                echo '<i class="fa fa-exclamation-circle" style="font-size: 64px; color: #ccc;"></i>';
                echo '<h2>Nenalezeny žádné stroje</h2>';
                echo '<p>V adresáři /home/yolo nebyly nalezeny žádné adresáře odpovídající vzoru st{cislo}_*</p>';
                echo '</div>';
            } else {
                // Display Production Machines
                if (!empty($productionMachines)) {
                    echo '<div class="section-header">';
                    echo '<h2>Produkční Stroje</h2>';
                    echo '<p>Aktivní pracovní stanice pro detekci obrazu</p>';
                    echo '</div>';
                    echo '<div class="machine-grid">';
                    
                    foreach ($productionMachines as $machine) {
                        $cisloStroj = $machine['number'];
                        $nazevStroj = str_replace('_', ' ', $machine['name']);
                        $popisStroj = "Pracovní stanice " . $machine['name'];
                        $nahledyUrl = "nahledy.php?cisloStroj=" . urlencode($cisloStroj) . 
                                      "&nazevStroj=" . urlencode($nazevStroj) . 
                                      "&popisStroj=" . urlencode($popisStroj);


                        echo '<div style="cursor: pointer;" onclick="window.location=\'' . $nahledyUrl . '\';" class="machine-card">';
                        echo '<div class="machine-header">';
                        echo '<i class="fa fa-cogs machine-icon"></i>';
                        echo '<div class="machine-number">ST' . $cisloStroj . '</div>';
                        echo '</div>';
                        
                        echo '<div class="machine-info">';
                        echo '<p><strong>Název:</strong> ' . htmlspecialchars($nazevStroj) . '</p>';
                        echo '<p><strong>Adresář:</strong> <code>' . htmlspecialchars($machine['fullDir']) . '</code></p>';
                        
                        // Check if Detekce_Obrazu exists
                        $detekceExists = is_dir($machine['path'] . '/Detekce_Obrazu');
                        if ($detekceExists) {
                            echo '<p><i class="fa fa-check-circle" style="color: #28a745;"></i> Detekce nakonfigurována</p>';
                        } else {
                            echo '<p><i class="fa fa-times-circle" style="color: #dc3545;"></i> Detekce nenalezena</p>';
                        }
                        echo '</div>';
                        
                        echo '<div class="machine-actions">';
                        
                        // Náhledy button
                        echo '<a href="' . $nahledyUrl . '" class="machine-btn btn-primary">';
                        echo '<i class="fa fa-image"></i> Náhledy';
                        echo '</a>';
                        
                        // Models button
                        $modelsUrl = "models_offer.php?cisloStroj=" . urlencode($cisloStroj) . 
                                    "&nazevStroj=" . urlencode($nazevStroj) . 
                                    "&popisStroj=" . urlencode($popisStroj);
                        echo '<a href="' . $modelsUrl . '" class="machine-btn btn-secondary">';
                        echo '<i class="fa fa-cube"></i> Výběr modelů';
                        echo '</a>';
                        
                        echo '</div>';
                        echo '</div>';
                    }
                    
                    echo '</div>'; // machine-grid
                }
                
                // Display Special Machines
                if (!empty($specialMachines)) {
                    echo '<div class="section-header special-section">';
                    echo '<h2>Speciální Pracovní Prostory</h2>';
                    echo '<p>Vývojové a testovací prostředí</p>';
                    echo '</div>';
                    echo '<div class="machine-grid">';
                    
                    foreach ($specialMachines as $machine) {
                        $cisloStroj = $machine['number'];
                        $nazevStroj = str_replace('_', ' ', $machine['name']);
                        $popisStroj = "Pracovní stanice " . $machine['name'];
                        
                        // Determine special machine type
                        $specialType = '';
                        $specialIcon = 'fa-flask';
                        $specialBadge = '';
                        if ($cisloStroj == '99') {
                            $specialType = 'training';
                            $specialIcon = 'fa-graduation-cap';
                            $specialBadge = '<span class="special-badge badge-training"><i class="fa fa-graduation-cap"></i> TRÉNINK</span>';
                        } elseif ($cisloStroj == '100') {
                            $specialType = 'test';
                            $specialIcon = 'fa-flask';
                            $specialBadge = '<span class="special-badge badge-test"><i class="fa fa-flask"></i> TEST</span>';
                        }
                        
                        echo '<div class="machine-card special-card special-' . $specialType . '">';
                        echo '<div class="machine-header">';
                        echo '<i class="fa ' . $specialIcon . ' machine-icon"></i>';
                        echo '<div class="machine-number">ST' . $cisloStroj . '</div>';
                        echo '</div>';
                        
                        echo $specialBadge;
                        
                        echo '<div class="machine-info">';
                        echo '<p><strong>Název:</strong> ' . htmlspecialchars($nazevStroj) . '</p>';
                        echo '<p><strong>Adresář:</strong> <code>' . htmlspecialchars($machine['fullDir']) . '</code></p>';
                        
                        // Check if Detekce_Obrazu exists
                        $detekceExists = is_dir($machine['path'] . '/Detekce_Obrazu');
                        if ($detekceExists) {
                            echo '<p><i class="fa fa-check-circle" style="color: #28a745;"></i> Detekce nakonfigurována</p>';
                        } else {
                            echo '<p><i class="fa fa-times-circle" style="color: #dc3545;"></i> Detekce nenalezena</p>';
                        }
                        echo '</div>';
                        
                        echo '<div class="machine-actions">';
                        
                        // For st99 (training) - show only training button with prominent style
                        if ($cisloStroj == '99') {
                            $trainUrl = "train.php?cisloStroj=" . urlencode($cisloStroj) . 
                                       "&nazevStroj=" . urlencode($nazevStroj) . 
                                       "&popisStroj=" . urlencode($popisStroj);
                            echo '<a href="' . $trainUrl . '" class="machine-btn btn-warning" style="font-size: 16px; padding: 14px 20px;">';
                            echo '<i class="fa fa-graduation-cap"></i> Zahájit Trénink Modelu';
                            echo '</a>';
                        }
                        // For st100 (test) - show test-oriented buttons
                        elseif ($cisloStroj == '100') {
                            $nahledyUrl = "nahledy.php?cisloStroj=" . urlencode($cisloStroj) . 
                                         "&nazevStroj=" . urlencode($nazevStroj) . 
                                         "&popisStroj=" . urlencode($popisStroj);
                            echo '<a href="' . $nahledyUrl . '" class="machine-btn btn-primary">';
                            echo '<i class="fa fa-image"></i> Testovací Náhledy';
                            echo '</a>';
                            
                            $modelsUrl = "models_offer.php?cisloStroj=" . urlencode($cisloStroj) . 
                                        "&nazevStroj=" . urlencode($nazevStroj) . 
                                        "&popisStroj=" . urlencode($popisStroj);
                            echo '<a href="' . $modelsUrl . '" class="machine-btn btn-secondary">';
                            echo '<i class="fa fa-cube"></i> Testovací Modely';
                            echo '</a>';
                            
                        }
                        
                        echo '</div>';
                        echo '</div>';
                    }
                    
                    echo '</div>'; // machine-grid
                }
            }
            ?>
        </main>
        
        <footer style="text-align: center; padding: 30px; color: #666; margin-top: 40px; border-top: 1px solid #e0e0e0;">
            <p><i class="fa fa-copyright"></i> 2025 Systém Detekce Obrazu</p>
        </footer>
    </body>
</html>
