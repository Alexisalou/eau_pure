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
    $numero_de_telephone = $_POST['numero_de_telephone'];
    $password = $_POST['password'];

    // Préparation de la requête SQL pour obtenir le mot de passe hashé
    $stmt = $conn->prepare("SELECT mot_de_passe FROM Technicien WHERE numero_de_telephone = ?");
    if ($stmt) {
        $stmt->bind_param('s', $numero_de_telephone);
        $stmt->execute();
        $stmt->store_result();

        // Vérifier si l'utilisateur existe
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($hashed_password);
            $stmt->fetch();

            // Vérifier le mot de passe
            if (password_verify($password, $hashed_password)) {
                header("Location: données_physico_chimiques.html");
                exit();
            } else {
                echo "Mot de passe incorrect.";
            }
        } else {
            echo "Numéro de téléphone incorrect.";
        }

        $stmt->close();
    } else {
        echo "Erreur de préparation de la requête SQL.";
    }
}

$conn->close();
?>
