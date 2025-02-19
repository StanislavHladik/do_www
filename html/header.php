<?php
if (isset($_GET['cisloStroj']) && isset($_GET['nazevStroj'])) 
{
    $cisloStroj = htmlspecialchars($_GET['cisloStroj']);
    $nazevStroj = htmlspecialchars($_GET['nazevStroj']);
    $popisStroj = htmlspecialchars($_GET['popisStroj']);
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
    $popisStroj = "Kontrola svárů- Flídr Metal s.r.o.";
    // echo "No parameters were passed!";
}
?>

<!DOCTYPE html>
<html>  
    <head>
        <link rel="stylesheet" href="../css/style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <title>Detekce obrazu</title>
    </head>
    <body>   
        <header class="site-header">
        <h1><?php echo($popisStroj); ?></h1>
    </header>