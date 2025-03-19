<?php
session_start();

$DATABASE_HOST = '10.0.14.4';
$DATABASE_NAME = 'eau_pure';
$DATABASE_USER = 'root';
$DATABASE_PASSWORD = 'ieufdl';
$DATABASE_PORT = '9999';

// Connexion à la base de données
$conn = new mysqli($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASSWORD, $DATABASE_NAME, $DATABASE_PORT);
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Vérification du type de requête
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                login($conn);
                break;
            case 'admin':
                verifierAdmin($conn);
                break;
            case 'ajout':
                ajoutUtilisateur($conn);
                break;
            case 'modifier':
                modifierUtilisateur($conn);
                break;
            case 'supprimer':
                supprimerUtilisateur($conn);
                break;
            default:
                echo "Action non reconnue.";
        }
    }
}

$conn->close();

function login($conn) {
    $numero_de_telephone = $_POST['numero_de_telephone'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, mot_de_passe FROM Technicien WHERE numero_de_telephone = ?");
    if ($stmt) {
        $stmt->bind_param('s', $numero_de_telephone);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($technicien_id, $hashed_password);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['technicien_id'] = $technicien_id; // Stocker l'ID du technicien dans la session
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

function verifierAdmin($conn) {
    $mdp_admin = $_POST['mdp_admin'];

    $stmt = $conn->prepare("SELECT mdp_connect FROM Technicien WHERE mdp_connect IS NOT NULL");
    if ($stmt) {
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($mdp_connect);
            $stmt->fetch();

            if ($mdp_connect !== null && password_verify($mdp_admin, $mdp_connect)) {
                // Réponse pour indiquer que le mot de passe admin est correct
                echo "Mot de passe admin correct";
            } else {
                echo "Mot de passe admin incorrect";
            }
        } else {
            echo "Admin non trouvé.";
        }

        $stmt->close();
    } else {
        echo "Erreur de préparation de la requête SQL.";
    }
}

function ajoutUtilisateur($conn) {
    $numero = $_POST['numero'];
    $conf_numero = $_POST['conf_numero'];
    $password = $_POST['password'];
    $conf_password = $_POST['conf_password'];

    if ($numero !== $conf_numero || $password !== $conf_password) {
        die("Les numéros de téléphone ou mots de passe ne correspondent pas.");
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO Technicien (numero_de_telephone, mot_de_passe) VALUES (?, ?)");
    $stmt->bind_param("ss", $numero, $hash);

    if ($stmt->execute()) {
        echo "Utilisateur ajouté avec succès.";
    } else {
        echo "Erreur : " . $stmt->error;
    }

    $stmt->close();
}

function modifierUtilisateur($conn) {
    $numero = $_POST['numero'];
    $ancien_password = $_POST['ancien_password'];
    $new_password = $_POST['new_password'];
    $conf_new_password = $_POST['conf_new_password'];

    if ($new_password !== $conf_new_password) {
        die("Les nouveaux mots de passe ne correspondent pas.");
    }

    $stmt = $conn->prepare("SELECT mot_de_passe FROM Technicien WHERE numero_de_telephone = ?");
    $stmt->bind_param("s", $numero);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 0) {
        die("Numéro de téléphone non trouvé.");
    }

    $stmt->bind_result($hashed_password);
    $stmt->fetch();

    if (!password_verify($ancien_password, $hashed_password)) {
        die("Mot de passe actuel incorrect.");
    }

    $stmt->close();

    $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE Technicien SET mot_de_passe = ? WHERE numero_de_telephone = ?");
    $stmt->bind_param("ss", $new_hash, $numero);

    if ($stmt->execute()) {
        echo "Mot de passe mis à jour.";
    } else {
        echo "Erreur : " . $stmt->error;
    }

    $stmt->close();
}

function supprimerUtilisateur($conn) {
    $numero = $_POST['numero'];
    $conf_numero = $_POST['conf_numero'];
    $password = $_POST['password'];
    $conf_password = $_POST['conf_password'];

    if ($numero !== $conf_numero || $password !== $conf_password) {
        die("Les informations saisies ne correspondent pas.");
    }

    $stmt = $conn->prepare("SELECT mot_de_passe FROM Technicien WHERE numero_de_telephone = ?");
    $stmt->bind_param("s", $numero);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        die("Numéro de téléphone non trouvé.");
    }

    $stmt->bind_result($hashed_password);
    $stmt->fetch();

    if (!password_verify($password, $hashed_password)) {
        die("Mot de passe incorrect.");
    }

    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM Technicien WHERE numero_de_telephone = ?");
    $stmt->bind_param("s", $numero);

    if ($stmt->execute()) {
        echo "Utilisateur supprimé avec succès.";
    } else {
        echo "Erreur : " . $stmt->error;
    }

    $stmt->close();
}
?>
