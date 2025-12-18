        <!-- Dataset Upload Section -->
        <div class="upload-section">
            <h3><i class="fa fa-upload"></i> Nahrát Nový Dataset</h3>
            <p>Nahrajte .zip soubor s datasetem do adresáře pro trénink.</p>
            
            <form id="dataset-upload-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="dataset-file"><i class="fa fa-file-archive-o"></i> Vyberte .zip soubor:</label>
                    <input type="file" id="dataset-file" name="dataset-file" accept=".zip" class="form-control" required>
                    <small>Podporované formáty: .zip (maximální velikost: 500 MB)</small>
                </div>
                
                <div class="form-group">
                    <label for="dataset-upload-name"><i class="fa fa-tag"></i> Název datasetu (volitelné):</label>
                    <input type="text" id="dataset-upload-name" name="dataset-upload-name" class="form-control" placeholder="Ponechte prázdné pro použití názvu souboru">
                    <small>Pokud nevyplníte, použije se název nahraného souboru</small>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-upload"></i> Nahrát Dataset
                </button>
            </form>
            
            <div id="upload-status" class="upload-status" style="display: none;">
                <div class="alert">
                    <i class="fa fa-info-circle"></i>
                    <span id="upload-status-text"></span>
                </div>
                <div id="upload-progress-container" class="progress-bar-container" style="display: none;">
                    <div id="upload-progress-bar" class="progress-bar" style="width: 0%">0%</div>
                </div>
            </div>
        </div>