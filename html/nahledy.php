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
    <article>
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
    </article>

    <?php include 'footer.php'; ?>