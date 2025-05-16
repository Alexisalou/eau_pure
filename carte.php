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

// Récupérer les données des tables Station et Analyse
$query = "
    SELECT s.id AS station_id, s.riviere, s.latitude, s.longitude, a.type, a.valeur, a.unite
    FROM Station s
    JOIN Echantillon e ON s.id = e.station_id
    JOIN Analyse a ON e.id = a.prelevement
    WHERE a.prelevement = (
        SELECT MAX(a2.prelevement)
        FROM Analyse a2
        JOIN Echantillon e2 ON a2.prelevement = e2.id
        WHERE e2.station_id = s.id AND a2.type = a.type
    )
";
$result = $conn->query($query);

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $station_id = $row['station_id'];
        if (!isset($data[$station_id])) {
            $data[$station_id] = [
                'riviere' => $row['riviere'],
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'analyses' => []
            ];
        }
        $data[$station_id]['analyses'][] = [
            'type' => $row['type'],
            'valeur' => $row['valeur'],
            'unite' => $row['unite']
        ];
    }
} else {
    echo "Aucune donnée trouvée.";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Carte Leaflet</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <style>
        #map { height: 765px; }
    </style>
</head>
<body>
    <div id="map"></div>
    <script>
        var map = L.map('map').setView([51.505, -0.09], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        var data = <?php echo json_encode(array_values($data)); ?>;
        console.log(data);  // Debug: Afficher les données dans la console

        data.forEach(function(entry) {
            if (entry.latitude && entry.longitude) { // Vérifiez que les coordonnées existent
                var popupContent = '<strong>Rivière:</strong> ' + entry.riviere + '<br>';
                if (entry.analyses.length > 0) {
                    entry.analyses.forEach(function(analyse) {
                        popupContent += '<strong>Type:</strong> ' + analyse.type + '<br>';
                        popupContent += '<strong>Valeur:</strong> ' + analyse.valeur + ' ' + analyse.unite + '<br><br>';
                    });
                } else {
                    popupContent += 'Aucune analyse disponible.<br>';
                }

                L.marker([entry.latitude, entry.longitude]).addTo(map)
                    .bindPopup(popupContent);
            } else {
                console.warn('Missing coordinates for entry:', entry);
            }
        });
    </script>
</body>
</html>
