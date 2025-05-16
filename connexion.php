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
    die(json_encode(["success" => false, "message" => "Erreur de connexion à la base de données."]));
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
                echo json_encode(["success" => false, "message" => "Action non reconnue."]);
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
                $_SESSION['technicien_id'] = $technicien_id;
                echo json_encode(["success" => true]);
                exit();
            }
        }

        echo json_encode(["success" => false, "message" => "Identifiants incorrects."]);
        $stmt->close();
    } else {
        echo json_encode(["success" => false, "message" => "Erreur de préparation de la requête SQL."]);
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
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["success" => false, "message" => "Mot de passe incorrect."]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Mot de passe incorrect."]);
        }

        $stmt->close();
    } else {
        echo json_encode(["success" => false, "message" => "Erreur de préparation de la requête SQL."]);
    }
}

function validerMotDePasse($password) {
    $longueurMin = 8;
    $majuscule = '/[A-Z]/';
    $caractereSpecial = '/[\W_]/';

    if (strlen($password) < $longueurMin || !preg_match($majuscule, $password) || !preg_match($caractereSpecial, $password)) {
        return false;
    }
    return true;
}

function ajoutUtilisateur($conn) {
    $numero = $_POST['numero'];
    $conf_numero = $_POST['conf_numero'];
    $password = $_POST['password'];
    $conf_password = $_POST['conf_password'];

    if ($numero !== $conf_numero) {
        echo json_encode(["success" => false, "message" => "Les numéros de téléphone ne correspondent pas."]);
        exit();
    }

    if ($password !== $conf_password) {
        echo json_encode(["success" => false, "message" => "Les mots de passe ne correspondent pas."]);
        exit();
    }

    if (!validerMotDePasse($password)) {
        echo json_encode(["success" => false, "message" => "Le mot de passe doit contenir au moins 8 caractères, une majuscule et un caractère spécial."]);
        exit();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) FROM Technicien WHERE numero_de_telephone = ?");
    $stmt->bind_param("s", $numero);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($count);
    $stmt->fetch();

    if ($count > 0) {
        echo json_encode(["success" => false, "message" => "Numéro de téléphone déjà existant."]);
        exit();
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO Technicien (numero_de_telephone, mot_de_passe) VALUES (?, ?)");
    $stmt->bind_param("ss", $numero, $hash);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Utilisateur ajouté avec succès."]);
    } else {
        echo json_encode(["success" => false, "message" => "Erreur lors de l'ajout de l'utilisateur."]);
    }

    $stmt->close();
}

function modifierUtilisateur($conn) {
    $numero = $_POST['numero'];
    $ancien_password = $_POST['ancien_password'];
    $new_password = $_POST['new_password'];
    $conf_new_password = $_POST['conf_new_password'];

    if ($new_password !== $conf_new_password) {
        echo json_encode(["success" => false, "message" => "Les nouveaux mots de passe ne correspondent pas."]);
        exit();
    }

    if (!validerMotDePasse($new_password)) {
        echo json_encode(["success" => false, "message" => "Le mot de passe doit contenir au moins 8 caractères, une majuscule et un caractère spécial."]);
        exit();
    }

    $stmt = $conn->prepare("SELECT mot_de_passe FROM Technicien WHERE numero_de_telephone = ?");
    $stmt->bind_param("s", $numero);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Identifiants incorrects."]);
        exit();
    }

    $stmt->bind_result($hashed_password);
    $stmt->fetch();

    if (!password_verify($ancien_password, $hashed_password)) {
        echo json_encode(["success" => false, "message" => "Identifiants incorrects."]);
        exit();
    }

    $stmt->close();

    $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE Technicien SET mot_de_passe = ? WHERE numero_de_telephone = ?");
    $stmt->bind_param("ss", $new_hash, $numero);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Mot de passe mis à jour."]);
    } else {
        echo json_encode(["success" => false, "message" => "Erreur lors de la mise à jour du mot de passe."]);
    }

    $stmt->close();
}

function supprimerUtilisateur($conn) {
    $numero = $_POST['numero'];
    $conf_numero = $_POST['conf_numero'];
    $password = $_POST['password'];
    $conf_password = $_POST['conf_password'];

    if ($numero !== $conf_numero) {
        echo json_encode(["success" => false, "message" => "Numéro de téléphone ou mot de passe incorrect."]);
        exit();
    }

    if ($password !== $conf_password) {
        echo json_encode(["success" => false, "message" => "Numéro de téléphone ou mot de passe incorrect."]);
        exit();
    }

    $stmt = $conn->prepare("SELECT mot_de_passe FROM Technicien WHERE numero_de_telephone = ?");
    $stmt->bind_param("s", $numero);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Numéro inexistant."]);
        exit();
    }

    $stmt->bind_result($hashed_password);
    $stmt->fetch();

    if (!password_verify($password, $hashed_password)) {
        echo json_encode(["success" => false, "message" => "Numéro de téléphone ou mot de passe incorrect."]);
        exit();
    }

    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM Technicien WHERE numero_de_telephone = ?");
    $stmt->bind_param("s", $numero);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Utilisateur supprimé avec succès."]);
    } else {
        echo json_encode(["success" => false, "message" => "Erreur lors de la suppression de l'utilisateur."]);
    }

    $stmt->close();
}
?>
