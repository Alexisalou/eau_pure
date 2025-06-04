<?php
// Connexion à la base de données (fichier externe qui contient $conn)
require_once 'connexion_bdd.php';

// Indique que la réponse sera envoyée en JSON
header('Content-Type: application/json');

// Vérifie que le paramètre 'id' est bien présent dans l'URL et que c'est un nombre
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Si l'ID est manquant ou invalide, on renvoie une erreur au format JSON
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

// Convertit l'ID en entier pour éviter les injections ou erreurs de type
$id = intval($_GET['id']);

// Prépare une requête SQL sécurisée pour récupérer les infos d'une station
$stmt = $conn->prepare("SELECT id, riviere AS nom, latitude, longitude FROM Station WHERE id = ?");
$stmt->bind_param("i", $id); // Liaison du paramètre (entier)
$stmt->execute(); // Exécution de la requête
$result = $stmt->get_result(); // Récupère le résultat sous forme de jeu de données

// Si la station existe, on renvoie ses données en JSON
if ($result && $station = $result->fetch_assoc()) {
    echo json_encode($station);
} else {
    // Sinon, on renvoie une erreur indiquant que la station n'existe pas
    echo json_encode(['error' => 'Station non trouvée']);
}
