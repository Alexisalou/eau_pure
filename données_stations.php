<?php
require_once 'connexion_bdd.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT id, riviere AS nom, latitude, longitude FROM Station WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $station = $result->fetch_assoc()) {
    echo json_encode($station);
} else {
    echo json_encode(['error' => 'Station non trouvée']);
}
