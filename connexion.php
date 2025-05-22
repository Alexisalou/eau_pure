<?php

// 🔐 Headers de sécurité HTTP — à mettre avant toute sortie
header("Content-Type: application/json");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; object-src 'none'; script-src-attr 'none'; script-src-elem 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: no-referrer");

// 🔐 Cookie sécurisé
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();




require_once 'connexion_bdd.php';

// Génération du token CSRF si absent
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Vérification CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(["success" => false, "message" => "Requête invalide (CSRF)."]);
        exit();
    }

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
    if (!isset($_POST['numero_de_telephone'], $_POST['password'])) {
        echo json_encode(["success" => false, "message" => "Champs manquants."]);
        exit();
    }

    $numero = $_POST['numero_de_telephone'];
    $password = $_POST['password'];

    // Limite d’essais
    $ip = $_SERVER['REMOTE_ADDR'];
    $max_attempts = 5;
    $lockout_time = 300; // 5 minutes

    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    if (!isset($_SESSION['login_attempts'][$ip])) {
        $_SESSION['login_attempts'][$ip] = ['count' => 0, 'last_attempt' => time()];
    }

    $attempts = &$_SESSION['login_attempts'][$ip];

    if ($attempts['count'] >= $max_attempts && (time() - $attempts['last_attempt']) < $lockout_time) {
        echo json_encode(["success" => false, "message" => "Trop de tentatives. Réessayez plus tard."]);
        exit();
    }

    $stmt = $conn->prepare("SELECT id, mot_de_passe FROM Technicien WHERE numero_de_telephone = ?");
    $stmt->bind_param('s', $numero);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($technicien_id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            session_regenerate_id(true); // Protection contre session fixation
            $_SESSION['technicien_id'] = $technicien_id;

            // Réinitialisation des tentatives
            unset($_SESSION['login_attempts'][$ip]);

            echo json_encode(["success" => true]);
            exit();
        }
    }

    // Échec de connexion
    $attempts['count']++;
    $attempts['last_attempt'] = time();

    echo json_encode(["success" => false, "message" => "Identifiants incorrects."]);
    $stmt->close();
}


function verifierAdmin($conn) {
    $mdp_admin = $_POST['mdp_admin'];

    $stmt = $conn->prepare("SELECT mdp_connect FROM Technicien WHERE mdp_connect IS NOT NULL");
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($mdp_connect);
        $stmt->fetch();

        if (password_verify($mdp_admin, $mdp_connect)) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Mot de passe incorrect."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Mot de passe incorrect."]);
    }

    $stmt->close();
}

function validerMotDePasse($password) {
    return strlen($password) >= 8 &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[\W_]/', $password);
}

function ajoutUtilisateur($conn) {
    if (!isset($_POST['numero'], $_POST['conf_numero'], $_POST['password'], $_POST['conf_password'])) {
        echo json_encode(["success" => false, "message" => "Champs manquants."]);
        exit();
    }

    $numero = $_POST['numero'];
    $conf_numero = $_POST['conf_numero'];
    $password = $_POST['password'];
    $conf_password = $_POST['conf_password'];

    if (!preg_match('/^0[6-7][0-9]{8}$/', $numero)) {
        echo json_encode(["success" => false, "message" => "Format du numéro invalide."]);
        exit();
    }

    if ($numero !== $conf_numero) {
        echo json_encode(["success" => false, "message" => "Les numéros de téléphone ne correspondent pas."]);
        exit();
    }

    if ($password !== $conf_password) {
        echo json_encode(["success" => false, "message" => "Les mots de passe ne correspondent pas."]);
        exit();
    }

    if (!validerMotDePasse($password)) {
        echo json_encode(["success" => false, "message" => "Mot de passe trop faible."]);
        exit();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) FROM Technicien WHERE numero_de_telephone = ?");
    $stmt->bind_param("s", $numero);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo json_encode(["success" => false, "message" => "Numéro de téléphone déjà utilisé."]);
        exit();
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO Technicien (numero_de_telephone, mot_de_passe) VALUES (?, ?)");
    $stmt->bind_param("ss", $numero, $hash);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Utilisateur ajouté avec succès."]);
    } else {
        echo json_encode(["success" => false, "message" => "Erreur lors de l'ajout."]);
    }

    $stmt->close();
}

function modifierUtilisateur($conn) {
    if (!isset($_POST['numero'], $_POST['ancien_password'], $_POST['new_password'], $_POST['conf_new_password'])) {
        echo json_encode(["success" => false, "message" => "Champs manquants."]);
        exit();
    }

    $numero = $_POST['numero'];
    $ancien_password = $_POST['ancien_password'];
    $new_password = $_POST['new_password'];
    $conf_new_password = $_POST['conf_new_password'];

    if (!preg_match('/^0[6-7][0-9]{8}$/', $numero)) {
        echo json_encode(["success" => false, "message" => "Format du numéro invalide."]);
        exit();
    }

    if ($new_password !== $conf_new_password) {
        echo json_encode(["success" => false, "message" => "Les mots de passe ne correspondent pas."]);
        exit();
    }

    if (!validerMotDePasse($new_password)) {
        echo json_encode(["success" => false, "message" => "Mot de passe trop faible."]);
        exit();
    }

    $stmt = $conn->prepare("SELECT mot_de_passe FROM Technicien WHERE numero_de_telephone = ?");
    $stmt->bind_param("s", $numero);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Utilisateur introuvable."]);
        exit();
    }

    $stmt->bind_result($hashed_password);
    $stmt->fetch();

    if (!password_verify($ancien_password, $hashed_password)) {
        echo json_encode(["success" => false, "message" => "Mot de passe actuel incorrect."]);
        exit();
    }

    $stmt->close();

    $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE Technicien SET mot_de_passe = ? WHERE numero_de_telephone = ?");
    $stmt->bind_param("ss", $new_hash, $numero);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Mot de passe mis à jour."]);
    } else {
        echo json_encode(["success" => false, "message" => "Erreur lors de la mise à jour."]);
    }

    $stmt->close();
}

function supprimerUtilisateur($conn) {
    if (!isset($_POST['numero'], $_POST['conf_numero'], $_POST['password'], $_POST['conf_password'])) {
        echo json_encode(["success" => false, "message" => "Champs manquants."]);
        exit();
    }

    $numero = $_POST['numero'];
    $conf_numero = $_POST['conf_numero'];
    $password = $_POST['password'];
    $conf_password = $_POST['conf_password'];

    if (!preg_match('/^0[6-7][0-9]{8}$/', $numero)) {
        echo json_encode(["success" => false, "message" => "Format du numéro invalide."]);
        exit();
    }

    if ($numero !== $conf_numero || $password !== $conf_password) {
        echo json_encode(["success" => false, "message" => "Numéro ou mot de passe incorrect."]);
        exit();
    }

    $stmt = $conn->prepare("SELECT mot_de_passe FROM Technicien WHERE numero_de_telephone = ?");
    $stmt->bind_param("s", $numero);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Utilisateur introuvable."]);
        exit();
    }

    $stmt->bind_result($hashed_password);
    $stmt->fetch();

    if (!password_verify($password, $hashed_password)) {
        echo json_encode(["success" => false, "message" => "Mot de passe incorrect."]);
        exit();
    }

    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM Technicien WHERE numero_de_telephone = ?");
    $stmt->bind_param("s", $numero);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Utilisateur supprimé avec succès."]);
    } else {
        echo json_encode(["success" => false, "message" => "Erreur lors de la suppression."]);
    }

    $stmt->close();
}
?>
