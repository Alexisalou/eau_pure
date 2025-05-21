<?php
session_start();

require_once 'connexion_bdd.php';

// Récupérer les rivières (stations) depuis la base de données
$rivieres = [];
$query = "SELECT id, riviere FROM Station";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rivieres[] = $row;
    }
} else {
    $rivieres[] = ["id" => "", "riviere" => "Aucune rivière"];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'ph' => ['value' => $_POST['ph'], 'unite' => ''],
        'conductivite' => ['value' => $_POST['conductivite'], 'unite' => 'µS/cm'],
        'turbidite' => ['value' => $_POST['turbidite'], 'unite' => 'NTU'],
        'oxygene' => ['value' => $_POST['oxygene-dissous'], 'unite' => 'mg/L'],
        'dco' => ['value' => $_POST['dco'], 'unite' => 'mg/L']
    ];

    $date = $_POST['date'];
    $station_id = intval($_POST['riviere']);

    if (!isset($_SESSION['technicien_id'])) {
        die("ID du technicien non trouvé dans la session.");
    }
    $technicien_id = $_SESSION['technicien_id'];

    // Trouver le préleveur associé à la station
    $stmt = $conn->prepare("SELECT id FROM Preleveur WHERE station = ?");
    $stmt->bind_param("i", $station_id);
    $stmt->execute();
    $result_preleveur = $stmt->get_result();

    if ($result_preleveur && $result_preleveur->num_rows > 0) {
        $preleveur_data = $result_preleveur->fetch_assoc();
        $preleveur = $preleveur_data['id'];
    } else {
        die("Aucun préleveur trouvé pour la rivière sélectionnée.");
    }
    $stmt->close();

    // Insertion dans Echantillon
    $stmt = $conn->prepare("INSERT INTO Echantillon (date, preleveur) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param('si', $date, $preleveur);
        if ($stmt->execute()) {
            $prelevement = $stmt->insert_id;
        } else {
            die("Erreur d'insertion dans la table Echantillon: " . $stmt->error);
        }
        $stmt->close();
    } else {
        die("Erreur de préparation de la requête SQL pour Echantillon.");
    }

$message = "";
$insertion_success = false;

// Insertion dans Analyse
$stmt = $conn->prepare("INSERT INTO Analyse (prelevement, valeur, unite, type) VALUES (?, ?, ?, ?)");
if ($stmt) {
    foreach ($data as $type => $info) {
        $stmt->bind_param('idss', $prelevement, $info['value'], $info['unite'], $type);
        if (!$stmt->execute()) {
            die("Erreur d'insertion dans la table Analyse: " . $stmt->error);
        }
    }
    $stmt->close();
    $insertion_success = true;
    $message = "Données insérées avec succès !";
}
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Formulaire de Données Physico-Chimiques</title>
    <link rel="stylesheet" href="données_physico_chimiques.css?v=<?= filemtime(__DIR__ . '/données_physico_chimiques.css') ?>" />

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map {
            height: 200px;
            width: 100%;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <u><h1>Formulaire de saisie des données physico-chimiques</h1></u><br>
            <form action="données_physico_chimiques.php" method="post" class="form-layout">
                <div class="form-group">
                    <label for="date">Date :</label>
                    <input type="datetime-local" id="date" name="date" required />
                </div>

                <div class="form-group emplacement">
                    <label for="riviere">Emplacement :</label>
                    <select id="riviere" name="riviere" required>
                        <?php if (empty($rivieres)): ?>
                            <option value="">Aucune rivière</option>
                        <?php else: ?>
                            <?php foreach ($rivieres as $riviere): ?>
                                <option value="<?php echo htmlspecialchars($riviere['id']); ?>">
                                    <?php echo htmlspecialchars($riviere['riviere']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="map-container">
                    <div id="map"></div>
                    <input type="hidden" name="latitude" id="latitude" />
                    <input type="hidden" name="longitude" id="longitude" />
                </div>

                <div class="form-group">
                    <label for="ph">pH (0 - 14) :</label>
                    <div class="input-with-button">
                        <input type="number" step="1" id="ph" name="ph" min="0" max="14" required />
                        <button type="button" class="info-btn" onclick="toggleInfo('info-ph')">ℹ️</button>
                    </div>
                    <p class="info" id="info-ph" style="display: none; white-space: pre-line;">
                        <strong>pH</strong><br>
                        Le pH mesure l’acidité ou la basicité d’une solution sur une échelle de 0 à 14.<br>
                        = 7 : neutre<br>
                        &lt; 7 : acide<br>
                        &gt; 7 : basique<br><br>
                        <strong>Plages typiques :</strong><br>
                        - Acides forts : 0 à 3<br>
                        - Acides faibles : 3 à 6<br>
                        - Neutre (eau pure) : 7<br>
                        - Bases faibles : 8 à 11<br>
                        - Bases fortes : 12 à 14<br><br>
                        <strong>Pourquoi c’est important ?</strong><br>
                        - Indique la qualité de l’eau potable (un pH trop bas ou trop haut peut être dangereux)<br>
                        - Affecte la survie des organismes aquatiques<br>
                        - Important pour les réactions chimiques en industrie<br>
                        - Influence la disponibilité des nutriments en agriculture<br><br>
                        <strong>Comment on mesure ?</strong><br>
                        Avec un pH-mètre qui utilise une électrode pour mesurer la tension électrique liée à l’acidité.
                    </p>
                </div>

                <div class="form-group">
                    <label for="conductivite">Conductivité électrique (µS/cm) :</label>
                    <div class="input-with-button">
                        <input type="number" step="0.01" id="conductivite" name="conductivite" min="0.05" required />
                        <button type="button" class="info-btn" onclick="toggleInfo('info-conductivite')">ℹ️</button>
                    </div>
                    <p class="info" id="info-conductivite" style="display: none; white-space: pre-line;">
                        <strong>Conductivité électrique</strong><br>
                        La conductivité mesure la capacité de l’eau à conduire un courant électrique.<br>
                        Elle s’exprime en microsiemens par centimètre (µS/cm) ou millisiemens par centimètre (mS/cm).<br>
                        <strong>Plages de mesure typiques :</strong><br>
                        - Eau ultra-pure : 0,05 à 1 µS/cm<br>
                        - Eau de pluie : 2 à 100 µS/cm<br>
                        - Eau potable : 50 à 1500 µS/cm (selon normes locales)<br>
                        - Eau de rivière propre : 100 à 2000 µS/cm<br>
                        - Eau de mer : 30 000 à 50 000 µS/cm (30 à 50 mS/cm)<br>
                        - Eaux usées : 1 000 à 10 000 µS/cm<br>
                        <strong>Pourquoi c’est important ?</strong><br>
                        - Indique la présence de sels dissous et d’impuretés<br>
                        - Surveille la qualité de l’eau potable<br>
                        - Détecte les pollutions dans les milieux naturels<br>
                        - Contrôle les procédés industriels utilisant l’eau<br>
                        <strong>Comment on mesure ?</strong><br>
                        Avec un conductimètre qui applique une tension entre deux électrodes plongées dans l’eau et mesure le courant électrique qui en résulte.
                    </p>
                </div>

                <div class="form-group">
                    <label for="turbidite">Turbidité (NTU) :</label>
                    <div class="input-with-button">
                        <input type="number" step="1" id="turbidite" name="turbidite" min="0" required />
                        <button type="button" class="info-btn" onclick="toggleInfo('info-turbidite')">ℹ️</button>
                    </div>
                    <p class="info" id="info-turbidite" style="display: none; white-space: pre-line;">
                        <strong>Turbidité</strong><br>
                        La turbidité mesure la clarté de l’eau, c’est-à-dire la présence de particules en suspension.<br>
                        Elle s’exprime en unités de turbidité néphélométrique (NTU).<br>
                        <strong>Plages de mesure typiques :</strong><br>
                        - Eau très claire : 0 à 1 NTU<br>
                        - Eau potable : 0 à 5 NTU (recommandé < 1 NTU par l’OMS)<br>
                        - Eau de rivière propre : 1 à 50 NTU<br>
                        - Eau de rivière polluée : 50 à 200 NTU<br>
                        - Eau très trouble (après fortes pluies) : 200 à 1000 NTU<br>
                        - Eaux usées non traitées : > 1000 NTU<br>
                        <strong>Pourquoi c’est important ?</strong><br>
                        - Indique la présence de particules et contaminants<br>
                        - Affecte l’efficacité de la désinfection de l’eau potable<br>
                        - Impacte la photosynthèse et la vie aquatique<br>
                        - Contrôle qualité dans certains processus industriels<br>
                        <strong>Comment on mesure ?</strong><br>
                        Avec un turbidimètre qui envoie de la lumière dans l’eau et mesure la diffusion causée par les particules en suspension.
                    </p>
                </div>

                <div class="form-group">
                    <label for="oxygene-dissous">Oxygène Dissous (mg/L) :</label>
                    <div class="input-with-button">
                        <input type="number" step="1" id="oxygene-dissous" name="oxygene-dissous" min="0" required />
                        <button type="button" class="info-btn" onclick="toggleInfo('info-oxygene-dissous')">ℹ️</button>
                    </div>
                    <p class="info" id="info-oxygene-dissous" style="display: none; white-space: pre-line;">
                        <strong>Oxygène Dissous (OD)</strong><br>
                        L’oxygène dissous mesure la quantité d’oxygène présente dans l’eau, exprimée en mg/L.<br>
                        <strong>Plages de mesure typiques :</strong><br>
                        - Eau très propre (montagne, rivières non polluées) : 8 à 14 mg/L<br>
                        - Eau potable : 6 à 12 mg/L (selon normes locales)<br>
                        - Eau de rivière propre : 6 à 12 mg/L<br>
                        - Eau de rivière polluée : 2 à 6 mg/L<br>
                        - Eaux usées : 0 à 2 mg/L<br>
                        <strong>Pourquoi c’est important ?</strong><br>
                        - Essentiel pour la santé des organismes aquatiques<br>
                        - Indicateur de la qualité de l’eau potable<br>
                        - Permet de contrôler le traitement biologique des eaux usées<br>
                        <strong>Comment on mesure ?</strong><br>
                        Avec un oxymètre ou une sonde électrochimique qui mesure la concentration d’oxygène dissous dans l’eau.
                    </p>
                </div>

                <div class="form-group">
                    <label for="dco">Demande Chimique en Oxygène (DCO) (mg/L) :</label>
                    <div class="input-with-button">
                        <input type="number" step="1" id="dco" name="dco" min="0" required />
                        <button type="button" class="info-btn" onclick="toggleInfo('info-dco')">ℹ️</button>
                    </div>
                    <p class="info" id="info-dco" style="display: none; white-space: pre-line;">
                        <strong>Demande Chimique en Oxygène (DCO)</strong><br>
                        La DCO mesure la quantité d’oxygène nécessaire pour oxyder chimiquement les matières organiques et inorganiques présentes dans l’eau, en mg/L.<br><br>
                        <strong>Plages de mesure typiques :</strong><br>
                        - Eau très propre (eau potable, eaux de surface non polluées) : 0 à 20 mg/L<br>
                        - Eau légèrement polluée : 20 à 50 mg/L<br>
                        - Eau de rivière polluée : 50 à 200 mg/L<br>
                        - Eaux usées domestiques traitées : 20 à 100 mg/L<br>
                        - Eaux usées domestiques non traitées : 200 à 600 mg/L<br>
                        - Eaux usées industrielles : 200 à 1000 mg/L ou plus<br>
                        <strong>Pourquoi c’est important ?</strong><br>
                        - Indique la présence de polluants organiques et inorganiques<br>
                        - Permet de surveiller la pollution et la charge organique de l’eau<br>
                        - Sert à évaluer l’efficacité des traitements d’eaux usées<br><br>
                        <strong>Comment on mesure ?</strong><br>
                        Par méthode chimique, souvent avec du dichromate de potassium et un catalyseur, mesurant la quantité d’oxygène consommée lors de l’oxydation.
                    </p>
                </div>

                <button type="submit" class="btn-submit">Envoyer</button>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        function toggleInfo(id) {
            const info = document.getElementById(id);
            if (info.style.display === "none" || info.style.display === "") {
                info.style.display = "block";
            } else {
                info.style.display = "none";
            }
        }
    </script>
    

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const map = L.map('map').setView([48.3, -3.1], 6);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
      }).addTo(map);

      // Récupérer les données depuis le fichier PHP
      fetch('get_stations.php')
        .then(response => response.json())
        .then(data => {
          data.forEach(station => {
            if (station.latitude && station.longitude) {
              L.marker([station.latitude, station.longitude])
                .addTo(map)
                .bindPopup(station.nom ? `<strong>${station.nom}</strong>` : 'Station');
            }
          });
        })
        .catch(error => {
          console.error('Erreur lors du chargement des données des stations :', error);
        });
    });
  </script>
<?php if (!empty($message)): ?>
    <div class="message-success" id="popup-success">
        <span class="close-btn" onclick="closePopup()">&times;</span>
        <p><?= htmlspecialchars($message) ?></p>
        <button class="ok-btn" onclick="closePopup()">OK</button>
    </div>
<?php endif; ?>

<script>
function closePopup() {
    const popup = document.getElementById('popup-success');
    if (popup) {
        popup.style.display = 'none';
    }
}
</script>

</body>
</html>


