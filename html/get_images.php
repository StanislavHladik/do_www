<?php
header('Content-Type: application/json');

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

//$folder = 'nahledy/';
$folder = 'nahledy/' . $cisloStroj . '/';
$images = array_diff(scandir($folder), array('.', '..'));

$imageList = [];
foreach ($images as $image) {
    if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $image)) {
        $imageList[] = $folder . $image;
    }
}

echo json_encode($imageList);
?>