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
        
    <div class="card">
  <img src="/nahledy/1.jpg" alt="" style="width:100%">
  <h1>Kontrola svárů</h1>
  <?php 
    $url = '/nahledy.php?cisloStroj='.$cisloStroj.'&nazevStroj='.$nazevStroj.'&popisStroj='.$popisStroj;
    echo ("<p><a href=".$url." class='button'>Přejít</a></p>"); 
   ?>
</div>

    </article>

    <?php include 'footer.php'; ?>