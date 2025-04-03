<?php
session_start();
require_once 'interf.php';

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

$interf = new Interf($conn);

// Vérification du type de requête
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $response = [];
        switch ($_POST['action']) {
            case 'login':
                $response = $interf->login($_POST['numero_de_telephone'], $_POST['password']);
                break;
            case 'admin':
                $response = $interf->verifierAdmin($_POST['mdp_admin']);
                break;
            case 'ajout':
                $response = $interf->ajoutUtilisateur($_POST['numero'], $_POST['conf_numero'], $_POST['password'], $_POST['conf_password']);
                break;
            case 'modifier':
                $response = $interf->modifierUtilisateur($_POST['numero'], $_POST['ancien_password'], $_POST['new_password'], $_POST['conf_new_password']);
                break;
            case 'supprimer':
                $response = $interf->supprimerUtilisateur($_POST['numero'], $_POST['conf_numero'], $_POST['password'], $_POST['conf_password']);
                break;
            default:
                $response = ["success" => false, "message" => "Action non reconnue."];
        }
        echo json_encode($response);
    }
}

$conn->close();
?>
