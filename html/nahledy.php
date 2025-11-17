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
        </div>
    </main>
<script>
        // Make PHP variables available globally for footer
        window.cisloStroj = "<?php echo $cisloStroj; ?>";
        window.nazevStroj = "<?php echo $nazevStroj; ?>";
        window.popisStroj = "<?php echo $popisStroj; ?>";
        
        let previousImages = [];

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
            fetch('nahledy/html_config.json')
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
                div.style.width = '50%';

                div.innerHTML = `<img src="${src}" alt="Image" style="max-width: 800px; width:80%; margin: 10px;">`;
                gallery.appendChild(div);

                i++;
            });

            // Přiřazení background color podle toho jestli je vada nebo ne
            fetch('nahledy/html_config.json')
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
        // setInterval(fetchImages, 4000); // 4000 milliseconds = 4 seconds
        // setInterval(refresh, 1);
        fetchImages(); // Initial load
        //window.onload = takeScreenshot;
    </script>

    <?php include 'footer.php'; ?>