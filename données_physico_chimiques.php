<?php
// Ce fichier permet au technicien connecté de saisir des données physico-chimiques liées à une station de mesure (rivière).
// Le script effectue les actions suivantes :
// 1. Vérifie que l'utilisateur est connecté (via la session technicien).
// 2. Récupère la liste des stations disponibles pour alimenter la liste déroulante.
// 3. Si le formulaire est soumis :
//    - Récupère les valeurs saisies (pH, turbidité, conductivité, oxygène dissous, DCO).
//    - Identifie le préleveur associé à la station sélectionnée.
//    - Insère un échantillon (avec la date et le préleveur) dans la base de données.
//    - Insère chaque analyse associée à cet échantillon.
// 4. Ferme proprement la connexion à la base.
// Un message de confirmation s'affiche à l'utilisateur si tout s'est bien déroulé.

session_start();

// Vérifie que le technicien est connecté, sinon redirige vers la page de connexion
if (!isset($_SESSION['technicien_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'connexion_bdd.php';

// Récupération des stations/rivières dans la base de données
$rivieres = [];
$query = "SELECT id, riviere FROM Station";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rivieres[] = $row;
    }
} else {
    // Par défaut, affiche un message si aucune station n’est trouvée
    $rivieres[] = ["id" => "", "riviere" => "Aucune rivière"];
}

// Si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Données à insérer dans la table Analyse
    $data = [
        'ph' => ['value' => $_POST['ph'], 'unite' => ''],
        'conductivite' => ['value' => $_POST['conductivite'], 'unite' => 'µS/cm'],
        'turbidite' => ['value' => $_POST['turbidite'], 'unite' => 'NTU'],
        'oxygene' => ['value' => $_POST['oxygene-dissous'], 'unite' => 'mg/L'],
        'dco' => ['value' => $_POST['dco'], 'unite' => 'mg/L']
    ];

    $date = $_POST['date'];
    $station_id = intval($_POST['riviere']);

    // Vérifie que l’ID technicien est dans la session
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

    // Insertion de l’échantillon (prélevé à une date par un préleveur)
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

    // Insertion des analyses liées à cet échantillon
    $message = "";
    $insertion_success = false;

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

    <!-- Feuille de style Leaflet -->
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
            
            <!-- FORMULAIRE PRINCIPAL -->
            <form action="données_physico_chimiques.php" method="post" class="form-layout">
                <!-- Date de prélèvement -->
                <div class="form-group">
                    <label for="date">Date :</label>
                    <input type="datetime-local" id="date" name="date" required />
                </div>

                <!-- Choix de la rivière -->
                <div class="form-group emplacement">
                    <label for="riviere">Emplacement :</label>
                    <select id="riviere" name="riviere" required>
                        <?php foreach ($rivieres as $riviere): ?>
                            <option value="<?= htmlspecialchars($riviere['id']) ?>">
                                <?= htmlspecialchars($riviere['riviere']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Carte pour affichage de la station sélectionnée -->
                <div class="map-container">
                    <div id="map"></div>
                    <input type="hidden" name="latitude" id="latitude" />
                    <input type="hidden" name="longitude" id="longitude" />
                </div>

                <div class="form-group">
                    <label for="ph">pH :</label>
                    <div class="input-with-button">
                        <input type="number" step="0.1" id="ph" name="ph" min="0" max="14" required />
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
                        <input type="number" step="0.1" id="conductivite" name="conductivite" min="0" required />
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
                        <input type="number" step="0.1" id="turbidite" name="turbidite" min="0" required />
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
                        <input type="number" step="0.1" id="oxygene-dissous" name="oxygene-dissous" min="0" required />
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
                        <input type="number" step="0.1" id="dco" name="dco" min="0" required />
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

            <!-- Formulaire de déconnexion -->
            <form method="post" action="deconnexion.php">
                <button type="submit" class="btn-logout">Déconnexion</button>
            </form>
        </div>
    </div>

<!-- Inclusion de la bibliothèque Leaflet pour l'affichage de la carte -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    // Fonction pour afficher ou masquer dynamiquement une section d'information (ex : explication pédagogique)
    function toggleInfo(id) {
        const info = document.getElementById(id); // On récupère l'élément par son ID
        // On inverse son affichage : s'il est caché ou non défini, on l'affiche ; sinon, on le cache
        info.style.display = (info.style.display === "none" || !info.style.display) ? "block" : "none";
    }

    // On attend que tout le HTML soit prêt avant de démarrer le code
    document.addEventListener('DOMContentLoaded', function () {
        // Initialisation de la carte dans l'élément ayant l'ID "map", centrée sur la Bretagne avec un zoom de 6
        const map = L.map('map').setView([48.3, -3.1], 6);

        // Récupération du menu déroulant de sélection de rivière/station
        const select = document.getElementById('riviere');

        // Variable pour stocker le marqueur affiché sur la carte (pour pouvoir le retirer ensuite)
        let marker = null;

        // Ajout du fond de carte OpenStreetMap (carte de base)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors' // Mention légale obligatoire
        }).addTo(map);

        // Fonction appelée quand une station est sélectionnée
        function updateMapWithStation(id) {
            // Requête pour récupérer les coordonnées de la station en fonction de son ID
            fetch(`données_stations.php?id=${id}`)
                .then(response => response.json()) // On convertit la réponse en JSON
                .then(data => {
                    // On vérifie que les coordonnées sont bien présentes dans la réponse
                    if (data.latitude && data.longitude) {
                        // S'il existe déjà un marqueur, on le supprime
                        if (marker) map.removeLayer(marker);

                        // On crée un nouveau marqueur à l'emplacement de la station
                        marker = L.marker([data.latitude, data.longitude])
                            .addTo(map) // On l'ajoute sur la carte
                            .bindPopup(data.nom || 'Station sélectionnée') // On associe une infobulle avec le nom de la station
                            .openPopup(); // On affiche automatiquement l'infobulle

                        // On centre la carte sur la nouvelle position
                        map.setView([data.latitude, data.longitude], 6);
                    }
                })
                .catch(error => console.error('Erreur :', error)); // Gestion des erreurs réseau ou serveur
        }

        // Événement déclenché lorsqu'on change de station dans la liste déroulante
        select.addEventListener('change', () => {
            const stationId = select.value;
            if (stationId) updateMapWithStation(stationId); // Mise à jour de la carte
        });

        // Si une station est déjà sélectionnée au chargement de la page, on l'affiche directement
        if (select.value) updateMapWithStation(select.value);
    });
</script>


    <!-- Message de succès après insertion -->
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
            if (popup) popup.style.display = 'none';
        }
    </script>
</body>
</html>



