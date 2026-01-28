<?php include 'header.php'; ?>

<?php
if (isset($_GET['cisloStroj']) && isset($_GET['nazevStroj']) && isset($_GET['popisStroj'])) 
{
    $cisloStroj = htmlspecialchars($_GET['cisloStroj']);
    $nazevStroj = htmlspecialchars($_GET['nazevStroj']);
    $popisStroj = htmlspecialchars($_GET['popisStroj']);
    /*
    echo "cisloStroj: " . $cisloStroj . "<br>";
    echo "nazevStroj: " . $nazevStroj . "<br>";
    echo "popisStroj: " . $popisStroj . "<br>";
    */
} 
else 
{
    // Defaultní hodnoty
    $cisloStroj = "1";
    $nazevStroj = "testovaci_pracoviste";
    $popisStroj = "Kontrola svárů- Flídr Metal s.r.o.";
    // echo "No parameters were passed!";
}
?>

<body>
    <main>
        <div class="gallery-container">
            <div class="image-gallery" id="gallery"></div>
            
            <!-- Control Button -->
            <div style="margin: 20px 0; padding: 15px; text-align: center;">
                <button onclick="sendCombinedCommand()" class="control-btn" style="padding: 8px 16px; background-color: #FF6F00; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 42px; margin-right: 10px;">
                    Pořiď a Ulož
                </button>
                <button id="restart-service-btn" class="control-btn" onclick="restartDetectionService()" style="padding: 8px 16px; background-color: #ff9800; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 42px;">
                    <i class="fa fa-refresh"></i> Restartovat Službu
                </button>
                <div id="statusDisplay" style="margin-top: 10px; 
                                               padding: 8px; 
                                               background-color: white; 
                                               border-radius: 4px; 
                                               font-size: 13px; 
                                               min-height: 20px; 
                                               border-left: 16px solid #2196F3;
                                               color: black;">
                    <strong>Status:</strong> <span id="statusText">Ready</span>
                </div>
                <div id="service-status" style="display: none; margin-top: 10px; 
                                               padding: 8px; 
                                               background-color: white; 
                                               border-radius: 4px; 
                                               font-size: 13px; 
                                               min-height: 20px; 
                                               border-left: 16px solid #2196F3;
                                               color: black;">
                    <strong><i class="fa fa-info-circle"></i></strong> <span id="service-status-text"></span>
                </div>
            </div>
        </div>
    </main>
<script>
        // Make PHP variables available globally for footer
        window.cisloStroj = "<?php echo $cisloStroj; ?>";
        window.nazevStroj = "<?php echo $nazevStroj; ?>";
        window.popisStroj = "<?php echo $popisStroj; ?>";
        
        let previousImages = [];

        function sendCombinedCommand() {
            console.log('Sending combined command: take_photo + save_photo');
            // Update status immediately
            document.getElementById('statusText').innerText = 'Pořizování a ukládání snímku...';
            document.getElementById('statusDisplay').style.borderLeftColor = '#FF6F00';
            
            // Get machine number if available (from PHP variables)
            const cisloStroj = typeof window.cisloStroj !== 'undefined' ? window.cisloStroj : '1';
            
            // First, send take_photo command
            fetch('detection_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    command: 'take_photo',
                    cisloStroj: cisloStroj,
                    timestamp: Date.now()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('statusText').innerText = 'Snímek pořízen, ukládání...';
                    
                    // Wait a moment for the photo to be taken, then send save_photo
                    setTimeout(() => {
                        fetch('detection_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                command: 'save_photo',
                                cisloStroj: cisloStroj,
                                timestamp: Date.now()
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('statusText').innerText = 'Snímek pořízen a uložen úspěšně!';
                                document.getElementById('statusDisplay').style.borderLeftColor = '#4CAF50';
                            } else {
                                document.getElementById('statusText').innerText = `Chyba při ukládání: ${data.message}`;
                                document.getElementById('statusDisplay').style.borderLeftColor = '#f44336';
                            }
                        })
                        .catch(error => {
                            console.error('Error saving photo:', error);
                            document.getElementById('statusText').innerText = `Chyba při ukládání: ${error.message}`;
                            document.getElementById('statusDisplay').style.borderLeftColor = '#f44336';
                        });
                    }, 2000); // Wait 2000ms (2 seconds) between commands
                } else {
                    document.getElementById('statusText').innerText = `Chyba při pořízení: ${data.message}`;
                    document.getElementById('statusDisplay').style.borderLeftColor = '#f44336';
                }
            })
            .catch(error => {
                console.error('Error taking photo:', error);
                document.getElementById('statusText').innerText = `Chyba připojení: ${error.message}`;
                document.getElementById('statusDisplay').style.borderLeftColor = '#f44336';
            });
        }

        /*
        function refresh() {
            fetch('nahledy/html_refresh.json')
                .then(response => response.json())
                .then(config => {
                    const refreshValue = config.refresh;
                    console.log("Refresh value:", refreshValue);

                    // Do something with refreshValue, for example:
                        if (refreshValue === 1) {
                        fetchImages(); // Reload the page
                    }

                })
                .catch(error => {
                    console.error("Error loading config:", error);
                });
        }
        */               

        function getTimestampFilename() {
            const now = new Date();
            const pad = (n, width = 2) => n.toString().padStart(width, "0");

            const Y = now.getFullYear();
            const m = pad(now.getMonth() + 1);
            const d = pad(now.getDate());
            const H = pad(now.getHours());
            const M = pad(now.getMinutes());
            const S = pad(now.getSeconds());
            const f = pad(now.getMilliseconds(), 3); // ms (closest to microseconds)

            return `${Y}_${m}_${d}__${H}_${M}_${S}_${f}.png`;
        }

       async function obnova_stranky() {
            console.log("Obnovuji stránku...");
            await sleep(1000); // Wait for 1 second
            location.reload(); // Reload the page
        }

        async function takeScreenshot() {
            await sleep(1000);
            
            const element = document.getElementById('gallery');
            //const element = document.body;

            console.log("Taking screenshot of element:", element);

            html2canvas(element).then(canvas => {
                // Append canvas to body (preview)
                //document.body.appendChild(canvas);

                // Or download the image automatically
                const filename = getTimestampFilename();

                let link = document.createElement("a");
                link.download = filename;
                link.href = canvas.toDataURL("image/png");
                link.click();
            });
            }

        // Funkce pro zpoždění                 

       function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
        }

       function refresh() {          
            // Use PHP variables in JavaScript
            const cisloStroj = "<?php echo $cisloStroj; ?>";
            const nazevStroj = "<?php echo $nazevStroj; ?>";
            const popisStroj = "<?php echo $popisStroj; ?>";

            // Create a URL with query parameters
            const params = new URLSearchParams({
                cisloStroj: cisloStroj,
                nazevStroj: nazevStroj,
                popisStroj: popisStroj
            });

            // Use the URL with query parameters in fetch
            fetch(`get_images.php?${params.toString()}`)
                .then(response => response.json())
                .then(images => {    
                    //console.log("aktuální obrázky - " + JSON.stringify(images));
                    //console.log("předchozí obrázky - " + JSON.stringify(previousImages));          
                    if (JSON.stringify(images) !== JSON.stringify(previousImages)) {    
                        console.log("Obrázky se změnily.");
                        //location.reload(); // Reload the page if images have changed  
                        obnova_stranky(); // Call the function to reload the page after a delay                              
                    }
                })
                .catch(error => console.error('Error fetching images:', error));
        }

        function fetchImages() {
            // Use PHP variables in JavaScript
            const cisloStroj = "<?php echo $cisloStroj; ?>";
            const nazevStroj = "<?php echo $nazevStroj; ?>";
            const popisStroj = "<?php echo $popisStroj; ?>";

            // Create a URL with query parameters
            const params = new URLSearchParams({
                cisloStroj: cisloStroj,
                nazevStroj: nazevStroj,
                popisStroj: popisStroj
            });

            // Use the URL with query parameters in fetch
            fetch(`get_images.php?${params.toString()}`)
                .then(response => response.json())
                .then(images => {                
                    if (JSON.stringify(images) !== JSON.stringify(previousImages)) {    
                        //console.log(images);
                        //console.log(previousImages);                                       
                        updateGallery(images);
                        previousImages = images;                                   
                    }
                })
                .catch(error => console.error('Error fetching images:', error));
        }

        /*
        function updateGallery(images) {
            console.log('updateGallery');
            const gallery = document.getElementById('gallery');
            gallery.innerHTML = ''; // Clear previous images
            //$('.image-container').remove();
            const div = document.createElement('div');
            div.classList.add('image-container');
            var img = "";
            images.forEach(src => {
                //const div = document.createElement('div');
                //div.classList.add('image-container');
                //div.innerHTML = `<img src="${src}" alt="Image" style="max-width: 500px; margin: 10px;">`;
                //gallery.appendChild(div);
                img = img + `<img src="${src}" alt="Image" style="max-width: 700px; margin: 10px;">`;
            div.innerHTML = img;
            gallery.appendChild(div);
            });

            // Přiřazení background color podle toho jestli je vada nebo ne
            const cisloStroj = window.cisloStroj || '1';
            fetch(`nahledy/${cisloStroj}/html_config.json`)
            .then(response => response.json())
            .then(configArray => {
                var back_color = null;

                for (const config of configArray) {
                    console.log(`Camera ${config.camera_index} background color: ${config.background_color}`);
                    back_color = config.background_color;

                    if (back_color === "#FF0000"){
                        break;
                    }
                }

                const gallery = document.querySelector('.image-gallery');
                if (gallery) {
                gallery.style.backgroundColor = back_color;
                console.log("Gallery background color set to:", back_color);
                } else {
                console.warn("No element with class 'image_gallery' found.");
                }
            })
            .catch(error => {
                console.error("Error loading config:", error);
      });
        }
      */

        function updateGallery(images) {
            console.log('updateGallery');
            const gallery = document.getElementById('gallery');
            gallery.innerHTML = ''; // Clear previous images
            //$('.image-container').remove();
            
            i = 0;

            images.forEach(src => {
                const div = document.createElement('div');
                div.classList.add(i);

                div.style.float = 'left'; 
                //div.style.width = '50%';

                div.innerHTML = `<img src="${src}" alt="Image" style="max-width: 800px; width:80%; margin: 10px;">`;
                gallery.appendChild(div);

                i++;
            });

            // Přiřazení background color podle toho jestli je vada nebo ne
            const cisloStroj = window.cisloStroj || '1';
            fetch(`nahledy/${cisloStroj}/html_config.json`)
            .then(response => response.json())
            .then(configArray => {
                var back_color = null;

                j = 0;

                for (const config of configArray) {
                    console.log(`Camera ${config.camera_index} background color: ${config.background_color}`);
                    back_color = config.background_color;

                    const gallery = document.getElementsByClassName(j)[0];
                    if (gallery) {
                    gallery.style.backgroundColor = back_color;
                    console.log("Gallery background color set to:", back_color);
                    } else {
                    console.warn("No element with class 'image_gallery' found.");
                    }

                    j++;
                }
            })
            .catch(error => {
                console.error("Error loading config:", error);
      });
        }

        // Fetch images every 5 seconds
        setInterval(refresh, 1000); 
        //setInterval(fetchImages, 1000); // 4000 milliseconds = 4 seconds
        // setInterval(refresh, 1);
        fetchImages(); // Initial load
        //window.onload = takeScreenshot;

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
            statusDiv.style.borderLeftColor = '#2196F3';
            
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
                
                if (data.success) {
                    // Success - request created
                    statusText.textContent = data.message + ' - Monitorování stavu...';
                    statusDiv.style.borderLeftColor = '#4CAF50';
                    
                    // Start monitoring the restart status
                    startRestartStatusMonitoring();
                } else {
                    // Error
                    statusText.textContent = 'Chyba: ' + data.message;
                    statusDiv.style.borderLeftColor = '#f44336';
                    
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
                
                statusText.textContent = 'Chyba sítě při vytváření požadavku na restart';
                statusDiv.style.borderLeftColor = '#f44336';
                
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
                const restartBtn = document.getElementById('restart-service-btn');
                
                if (data.success && data.status) {
                    switch(data.status) {
                        case 'completed':
                            statusText.textContent = 'Služba byla úspěšně restartována!';
                            statusDiv.style.borderLeftColor = '#4CAF50';
                            
                            // Stop monitoring
                            clearInterval(intervalId);
                            restartBtn.disabled = false;
                            restartBtn.innerHTML = '<i class="fa fa-refresh"></i> Restartovat Službu';
                            break;
                            
                        case 'failed':
                            statusText.textContent = 'Restart služby selhal. Zkuste to znovu.';
                            statusDiv.style.borderLeftColor = '#f44336';
                            
                            // Stop monitoring
                            clearInterval(intervalId);
                            restartBtn.disabled = false;
                            restartBtn.innerHTML = '<i class="fa fa-refresh"></i> Restartovat Službu';
                            break;
                            
                        case 'processing':
                            statusText.textContent = 'Restart služby probíhá...';
                            statusDiv.style.borderLeftColor = '#2196F3';
                            break;
                            
                        case 'pending':
                            statusText.textContent = 'Požadavek na restart čeká na zpracování...';
                            statusDiv.style.borderLeftColor = '#ff9800';
                            break;
                            
                        case 'none':
                            // Stop monitoring
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
    </script>

    <?php include 'footer.php'; ?>