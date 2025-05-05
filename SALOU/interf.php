<?php
session_start();
require_once 'interf.php';

$conn = connectDB();

// Récupération des rivières
$rivieres = getRivieres($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['technicien_id'])) {
        die("ID du technicien non trouvé dans la session.");
    }

    $analyses = [
        'ph' => ['value' => $_POST['ph'], 'unite' => ''],
        'conductivite' => ['value' => $_POST['conductivite'], 'unite' => 'µS/cm'],
        'turbidite' => ['value' => $_POST['turbidite'], 'unite' => 'NTU'],
        'oxygene' => ['value' => $_POST['oxygene'], 'unite' => 'mg/L'],
        'dco' => ['value' => $_POST['dco'], 'unite' => 'mg/L']
    ];

    $date = $_POST['date'];
    $preleveur = 2;
    $technicien_id = $_SESSION['technicien_id'];
    $riviere_id = $_POST['riviere'];

    // Insertion dans les tables via les fonctions
    $prelevement_id = insertEchantillon($conn, $date, $preleveur, $technicien_id, $riviere_id);
    insertAnalyse($conn, $prelevement_id, $analyses);

    echo "Données insérées avec succès.";
}

$conn->close();
?>
