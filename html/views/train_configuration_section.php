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