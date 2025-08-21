<?php include 'header.php'; ?>

<?php
if (isset($_GET['cisloStroj']) && isset($_GET['nazevStroj'])) 
{
    $cisloStroj = htmlspecialchars($_GET['cisloStroj']);
    $nazevStroj = htmlspecialchars($_GET['nazevStroj']);
    /*
    echo "cisloStroj: " . $cisloStroj . "<br>";
    echo "nazevStroj: " . $nazevStroj . "<br>";
    */
} 
else 
{
    // Defaultní hodnoty
    $cisloStroj = "1";
    $nazevStroj = "testovaci_pracoviste";
    // echo "No parameters were passed!";
}
?>

<body>
    <script>
        function refreshImages() {
            fetch("<?php echo $_SERVER['PHP_SELF']; ?>")
                .then(response => response.text())
                .then(html => {
                    document.getElementById("image-container").innerHTML = new DOMParser()
                        .parseFromString(html, "text/html")
                        .getElementById("image-container").innerHTML;
                });
        }

        setInterval(refreshImages, 5000); // Refresh every 1 second
    </script>
    <div id="image-container">
        <?php
        $dir = 'nahledy/'; // Directory where images are stored

        $files = scandir($dir); // Scan the directory

        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                // Display each image file
                echo ("<img src='$dir$file' alt='$file' style='max-width: 500px; margin: 10px;'>");
            }
        }
        ?>
    </div>

    <?php include 'footer.php'; ?>