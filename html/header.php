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

<!DOCTYPE html>
<html>  
    <head>
        <link rel="stylesheet" href="../css/style.css">
        <title>Detekce obrazu</title>
    </head>
    <body>   
        <header class="site-header">
        <h1>Detekce obrazu <?php echo(" číslo stroje: " . $cisloStroj . " " . "název stroje: " . $nazevStroj); ?></h1>
    </header>