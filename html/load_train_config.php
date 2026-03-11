<?php
require_once 'functions.php';

header('Content-Type: text/html; charset=utf-8');

// Get parameters from POST request
$datasetName = isset($_POST['dataset_name']) ? $_POST['dataset_name'] : '';
$datasetPath = isset($_POST['dataset_path']) ? $_POST['dataset_path'] : '';
$cisloStroj = isset($_POST['cislo_stroj']) ? $_POST['cislo_stroj'] : '99';

// Prepare variables to pass to the view
$variables = [
    'datasetName' => $datasetName,
    'datasetPath' => $datasetPath,
    'cisloStroj' => $cisloStroj,
    'selectedDataset' => $datasetName // For highlighting selected dataset
];

// Render the view with variables
includeWithVariables('views/train_configuration_section.php', $variables);
?>
