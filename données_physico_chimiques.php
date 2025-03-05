<?php
$DATABASE_HOST = '10.0.14.4';  // Utilisez le nom du service Docker pour l'hôte MySQL
$DATABASE_NAME = 'eau_pure';  // Remplacez par le nom de votre base de données
$DATABASE_USER = 'root';  // Remplacez par votre nom d'utilisateur
$DATABASE_PASSWORD = 'ieufdl';  // Remplacez par votre mot de passe
$DATABASE_PORT = '9999';

// Création de la connexion à la base de données MySQL
$conn = new mysqli($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASSWORD, $DATABASE_NAME, $DATABASE_PORT);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Liste des champs et leurs unités
    $data = [
        'ph' => ['value' => $_POST['ph'], 'unite' => ''],
        'conductivite' => ['value' => $_POST['conductivite'], 'unite' => 'µS/cm'],
        'turbidite' => ['value' => $_POST['turbidite'], 'unite' => 'NTU'],
        'oxygene' => ['value' => $_POST['oxygene'], 'unite' => 'mg/L'],
        'dco' => ['value' => $_POST['dco'], 'unite' => 'mg/L']
    ];

    // Préparation de la requête SQL pour insérer les données
    $stmt = $conn->prepare("INSERT INTO Analyse (prelevement, valeur, unite, type) VALUES (1, ?, ?, ?)");
    if ($stmt) {
        foreach ($data as $type => $info) {
            $stmt->bind_param('dss', $info['value'], $info['unite'], $type);
            $stmt->execute();
        }
        $stmt->close();
        echo "Données insérées avec succès.";
    } else {
        echo "Erreur de préparation de la requête SQL.";
    }
}

$conn->close();
?>
