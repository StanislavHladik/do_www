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

<!DOCTYPE html>
<html>  
    <head>
        <script src="script/jquery-3.7.1.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <link rel="stylesheet" href="../css/style.css">
        <link rel="stylesheet" href="css/navigation.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <title>Detekce obrazu</title>
    </head>
    <body>   
        <header class="site-header">
            <h1><?php echo($popisStroj); ?></h1>
            <p>Pracoviště č. <?php echo($cisloStroj); ?> - <?php echo($nazevStroj); ?></p>
            
            <!-- Navigation Buttons -->
            <nav class="navigation-buttons">
                <a href="index.php" class="nav-btn">
                    <i class="fa fa-th"></i> Všechny stroje
                </a>
                <a href="nahledy.php?cisloStroj=<?php echo($cisloStroj); ?>&nazevStroj=<?php echo($nazevStroj); ?>&popisStroj=<?php echo($popisStroj); ?>" class="nav-btn">
                    <i class="fa fa-home"></i> Náhledy
                </a>
                <a href="models_offer.php?cisloStroj=<?php echo($cisloStroj); ?>&nazevStroj=<?php echo($nazevStroj); ?>&popisStroj=<?php echo($popisStroj); ?>" class="nav-btn">
                    <i class="fa fa-cube"></i> Výběr modelů
                </a>
                <a href="archiv.php?cisloStroj=<?php echo($cisloStroj); ?>&nazevStroj=<?php echo($nazevStroj); ?>&popisStroj=<?php echo($popisStroj); ?>" class="nav-btn">
                    <i class="fa fa-archive"></i> Archiv
                </a>
            </nav>      
        </header>
        <script src="script/navigation.js"></script>