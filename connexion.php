<?php
/**
 * Ce fichier gère toutes les requêtes AJAX côté serveur liées à l’authentification et à la gestion des utilisateurs.
 * Il applique des en-têtes de sécurité HTTP pour protéger contre XSS, clickjacking et injection MIME.
 * Il configure les cookies de session de manière sécurisée, puis démarre une session PHP.
 * Il génère un token CSRF pour chaque session afin de sécuriser les requêtes POST.
 * Il prend en charge plusieurs actions POST : connexion utilisateur, vérification admin, ajout, modification et suppression d’utilisateur.
 * Chaque action déclenche des contrôles de validité, de sécurité et de cohérence avant de manipuler la base de données.
 * Il protège la connexion par limitation d’essais par IP et par hachage des mots de passe avec bcrypt.
 */



// Configuration des entêtes de sécurité HTTP pour protéger contre plusieurs attaques côté navigateur
header("Content-Type: application/json"); // Spécifie que la réponse sera au format JSON
header("Content-Security-Policy: default-src 'self'; script-src 'self'; object-src 'none'; script-src-attr 'none'; script-src-elem 'self';");
// Empêche le chargement de ressources externes non autorisées et de scripts inline, réduisant les risques XSS
header("X-Content-Type-Options: nosniff"); // Empêche les navigateurs de deviner le type MIME
header("X-Frame-Options: SAMEORIGIN"); // Empêche le site d’être intégré dans une iframe (clickjacking)
header("X-XSS-Protection: 1; mode=block"); // Active le filtre XSS du navigateur
header("Referrer-Policy: no-referrer"); // Le navigateur ne transmet pas l'URL de la page courante

// Configuration sécurisée des cookies de session
session_set_cookie_params([
    'lifetime' => 0, // Session supprimée à la fermeture du navigateur
    'path' => '/',
    'secure' => false, // Mettre à true en production avec HTTPS
    'httponly' => true, // Empêche l’accès au cookie via JavaScript
    'samesite' => 'Strict' // Empêche l’envoi du cookie dans les requêtes cross-site
]);

session_start(); // Démarrage ou reprise de session existante

require_once 'connexion_bdd.php'; // Inclusion du fichier de connexion à la base de données

// CSRF — Génération du token s’il est absent
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Création d’un token aléatoire sécurisé
}

// Vérification du token CSRF lors des requêtes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(["success" => false, "message" => "Requête invalide (CSRF)."]);
        exit();
    }

    // Traitement des différentes actions possibles
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login': login($conn); break;
            case 'admin': verifierAdmin($conn); break;
            case 'ajout': ajoutUtilisateur($conn); break;
            case 'modifier': modifierUtilisateur($conn); break;
            case 'supprimer': supprimerUtilisateur($conn); break;
            default:
                echo json_encode(["success" => false, "message" => "Action non reconnue."]);
        }
    }
}

$conn->close(); // Fermeture de la connexion à la base

// Fonction de connexion utilisateur avec limitation d’essais par IP
function login($conn) {
    if (!isset($_POST['numero_de_telephone'], $_POST['password'])) {
        echo json_encode(["success" => false, "message" => "Champs manquants."]);
        exit();
    }

    $numero = $_POST['numero_de_telephone'];
    $password = $_POST['password'];

    // Limitation d’essais par IP pour éviter le bruteforce
    $ip = $_SERVER['REMOTE_ADDR'];
    $max_attempts = 5;
    $lockout_time = 300; // Durée du blocage : 5 minutes

    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    if (!isset($_SESSION['login_attempts'][$ip])) {
        $_SESSION['login_attempts'][$ip] = ['count' => 0, 'last_attempt' => time()];
    }

    $attempts = &$_SESSION['login_attempts'][$ip];

    // Si nombre d’essais dépasse la limite et que le temps de blocage n’est pas écoulé
    if ($attempts['count'] >= $max_attempts && (time() - $attempts['last_attempt']) < $lockout_time) {
        echo json_encode(["success" => false, "message" => "Trop de tentatives. Réessayez plus tard."]);
        exit();
    }

    // 🔍 Recherche du technicien dans la base
    $stmt = $conn->prepare("SELECT id, mot_de_passe FROM Technicien WHERE numero_de_telephone = ?");
    $stmt->bind_param('s', $numero);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($technicien_id, $hashed_password);
        $stmt->fetch();

        // Vérification du mot de passe haché
        if (password_verify($password, $hashed_password)) {
            session_regenerate_id(true); // Renouvelle l’ID de session pour éviter fixation
            $_SESSION['technicien_id'] = $technicien_id;
            unset($_SESSION['login_attempts'][$ip]); // Réinitialisation des tentatives
            echo json_encode(["success" => true]);
            exit();
        }
    }

    // Échec — on augmente le compteur
    $attempts['count']++;
    $attempts['last_attempt'] = time();

    echo json_encode(["success" => false, "message" => "Identifiants incorrects."]);
    $stmt->close();
}

// Vérifie si le mot de passe admin est correct
function verifierAdmin($conn) {
    $mdp_admin = $_POST['mdp_admin'];

    // Récupération de l’admin (en supposant qu’il y ait un seul)
    $stmt = $conn->prepare("SELECT mdp_connect FROM Technicien WHERE mdp_connect IS NOT NULL");
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($mdp_connect);
        $stmt->fetch();

        // Vérification du mot de passe
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

// Vérifie la robustesse du mot de passe
function validerMotDePasse($password) {
    return strlen($password) >= 8 &&
           preg_match('/[A-Z]/', $password) && // Au moins une majuscule
           preg_match('/[\W_]/', $password);   // Au moins un caractère spécial
}

// Ajout d’un utilisateur après vérifications
function ajoutUtilisateur($conn) {
    if (!isset($_POST['numero'], $_POST['conf_numero'], $_POST['password'], $_POST['conf_password'])) {
        echo json_encode(["success" => false, "message" => "Champs manquants."]);
        exit();
    }

    $numero = $_POST['numero'];
    $conf_numero = $_POST['conf_numero'];
    $password = $_POST['password'];
    $conf_password = $_POST['conf_password'];

    // Format du numéro : doit commencer par 06 ou 07 suivi de 8 chiffres
    if (!preg_match('/^0[6-7][0-9]{8}$/', $numero)) {
        echo json_encode(["success" => false, "message" => "Format du numéro invalide."]);
        exit();
    }

    // Vérification de la cohérence des champs
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

    // Vérification de l’unicité du numéro
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

    // Insertion avec mot de passe haché
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

// Modification du mot de passe après vérification
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

    // Vérification de l’ancien mot de passe
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

    // Mise à jour du mot de passe
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

// Suppression d’un utilisateur après confirmation des identifiants
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

    // Vérifie que le compte existe et que le mot de passe est correct
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

    // Suppression du compte
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
