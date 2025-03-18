<?php
$DATABASE_HOST = '10.0.14.4';
$DATABASE_NAME = 'eau_pure';
$DATABASE_USER = 'root';
$DATABASE_PASSWORD = 'ieufdl';
$DATABASE_PORT = '9999';

$conn = new mysqli($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASSWORD, $DATABASE_NAME, $DATABASE_PORT);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Vérifiez si la colonne 'date' existe déjà
$checkColumnSQL = "SHOW COLUMNS FROM Analyse LIKE 'date'";
$result = $conn->query($checkColumnSQL);
if ($result->num_rows === 0) {
    // Ajouter la colonne 'date' si elle n'existe pas
    $alterTableSQL = "ALTER TABLE Analyse ADD COLUMN date DATETIME";
    if ($conn->query($alterTableSQL) !== TRUE) {
        die("Erreur lors de l'ajout de la colonne 'date': " . $conn->error);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'ph' => ['value' => $_POST['ph'], 'unite' => ''],
        'conductivite' => ['value' => $_POST['conductivite'], 'unite' => 'µS/cm'],
        'turbidite' => ['value' => $_POST['turbidite'], 'unite' => 'NTU'],
        'oxygene' => ['value' => $_POST['oxygene'], 'unite' => 'mg/L'],
        'dco' => ['value' => $_POST['dco'], 'unite' => 'mg/L']
    ];

    $prelevement = 1;
    $stmt = $conn->prepare("SELECT id FROM Echantillon WHERE id = ?");
    $stmt->bind_param('i', $prelevement);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        $stmt->close();
        $date = date('Y-m-d H:i:s'); // Obtenez la date et l'heure actuelles
        $stmt = $conn->prepare("INSERT INTO Echantillon (id, date) VALUES (?, ?)");
        $stmt->bind_param('is', $prelevement, $date);
        if (!$stmt->execute()) {
            die("Erreur d'insertion dans la table Echantillon: " . $stmt->error);
        }
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO Analyse (prelevement, valeur, unite, type, date) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $date = date('Y-m-d H:i:s');
        foreach ($data as $type => $info) {
            $stmt->bind_param('idsss', $prelevement, $info['value'], $info['unite'], $type, $date);
            if (!$stmt->execute()) {
                die("Erreur d'insertion dans la table Analyse: " . $stmt->error);
            }
        }
        $stmt->close();
        echo "Données insérées avec succès.";
    } else {
        echo "Erreur de préparation de la requête SQL.";
    }
}

$conn->close();
?>
