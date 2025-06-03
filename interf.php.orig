<?php
function connectDB() {
    $host = '10.0.14.4';
    $dbname = 'eau_pure';
    $user = 'root';
    $password = 'ieufdl';
    $port = 9999;

    $conn = new mysqli($host, $user, $password, $dbname, $port);

    if ($conn->connect_error) {
        die("Erreur de connexion : " . $conn->connect_error);
    }

    return $conn;
}

function getRivieres($conn) {
    $rivieres = [];
    $sql = "SELECT id, riviere, latitude, longitude FROM Station";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rivieres[] = $row;
        }
    } else {
        $rivieres[] = ["id" => "", "riviere" => "Aucune rivière", "latitude" => "", "longitude" => ""];
    }

    return $rivieres;
}

function insertEchantillon($conn, $date, $preleveur, $technicien_id, $station_id) {
    $stmt = $conn->prepare("INSERT INTO Echantillon (date, preleveur, station_id) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        die("Erreur préparation Echantillon : " . $conn->error);
    }

    $stmt->bind_param('sisi', $date, $preleveur, $technicien_id, $station_id);

    if (!$stmt->execute()) {
        die("Erreur insertion Echantillon : " . $stmt->error);
    }

    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}

function insertAnalyse($conn, $prelevement_id, $analyses) {
    $stmt = $conn->prepare("INSERT INTO Analyse (prelevement, valeur, unite, type) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        die("Erreur préparation Analyse : " . $conn->error);
    }

    foreach ($analyses as $type => $info) {
        $stmt->bind_param('idss', $prelevement_id, $info['value'], $info['unite'], $type);
        if (!$stmt->execute()) {
            die("Erreur insertion Analyse pour $type : " . $stmt->error);
        }
    }

    $stmt->close();
}
?>
