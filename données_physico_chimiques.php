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
        'oxygene' => ['value' => $_POST['oxygene'], 'unite' => 'mg/L'],
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
        echo "Données insérées avec succès.";
    } else {
        echo "Erreur de préparation de la requête SQL pour Analyse.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulaire de Données Physico-Chimiques</title>
	<link rel="stylesheet" href="données_physico_chimiques.css?v=<?= filemtime(__DIR__ . '/données_physico_chimiques.css') ?>">



</head>
<body>
    <div class="container">
        <h1>Formulaire de saisie des données physico-chimiques</h1>
        <form action="données_physico_chimiques.php" method="post">
            <div class="form-group">
                <label for="date">Date :</label>
                <input type="datetime-local" id="date" name="date" required>
            </div>
            <div class="form-group">
                <label for="riviere">Rivière :</label>
                <select id="riviere" name="riviere" required>
                    <?php if (empty($rivieres)): ?>
                        <option value="">Aucune rivière</option>
                    <?php else: ?>
                        <?php foreach ($rivieres as $riviere): ?>
                            <option value="<?php echo htmlspecialchars($riviere['id']); ?>"><?php echo htmlspecialchars($riviere['riviere']); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="ph">pH (0 - 14) :</label>
                <input type="number" step="1" id="ph" name="ph" min="0" max="14" required>
                <p class="info">
                    Le pH est une mesure de l'acidité ou de la basicité d'une solution.<br>
                    <strong>Plages de Mesure Typiques :</strong><br>
                    - Acides forts : 0 à 3<br>
                    - Acides faibles : 3 à 6<br>
                    - Neutre (eau pure) : 7<br>
                    - Bases faibles : 8 à 11<br>
                    - Bases fortes : 12 à 14
                </p>
            </div>
            <div class="form-group">
                <label for="conductivite">Conductivité électrique (µS/cm) (0.05 - 10 000) :</label>
                <input type="number" step="0.01" id="conductivite" name="conductivite" min="0.05" max="10000" required>
                <p class="info">
                    La conductivité électrique est une mesure de la capacité de l'eau à conduire un courant électrique.<br>
                    <strong>Plages de Mesure Typiques :</strong><br>
                    - Eau ultra-pure : 0.05 à 1 µS/cm<br>
                    - Eau de pluie : 2 à 100 µS/cm<br>
                    - Eau potable : 50 à 1500 µS/cm<br>
                    - Eau de rivière propre : 100 à 2000 µS/cm<br>
                    - Eau de mer : 30 à 50 mS/cm (30,000 à 50,000 µS/cm)<br>
                    - Eaux usées : 1000 à 10000 µS/cm
                </p>
            </div>
            <div class="form-group">
                <label for="turbidite">Turbidité (NTU) (0 - 1 000) :</label>
                <input type="number" step="1" id="turbidite" name="turbidite" min="0" max="1000" required>
                <p class="info">
                    La turbidité est une mesure de la clarté de l'eau.<br>
                    <strong>Plages de Mesure Typiques :</strong><br>
                    - Eau très claire : 0 à 1 NTU<br>
                    - Eau potable : 0 à 5 NTU<br>
                    - Eau de rivière propre : 1 à 50 NTU<br>
                    - Eau de rivière polluée : 50 à 200 NTU<br>
                    - Eau très brouillée : 200 à 1000 NTU<br>
                    - Eaux usées non traitées : 1000 NTU et plus
                </p>
            </div>
            <div class="form-group">
                <label for="oxygene">Oxygène dissous (mg/L) (0 - 14) :</label>
                <input type="number" step="1" id="oxygene" name="oxygene" min="0" max="14" required>
                <p class="info">
                    L'oxygène dissous est une mesure de la quantité d'oxygène présente dans l'eau.<br>
                    <strong>Plages de Mesure Typiques :</strong><br>
                    - Eau très propre : 8 à 14 mg/L<br>
                    - Eau potable : 6 à 12 mg/L<br>
                    - Eau de rivière propre : 6 à 12 mg/L<br>
                    - Eau de rivière polluée : 2 à 6 mg/L<br>
                    - Eaux usées : 0 à 2 mg/L
                </p>
            </div>
            <div class="form-group">
                <label for="dco">Demande chimique en oxygène (mg/L) (0 - 1 000) :</label>
                <input type="number" step="1" id="dco" name="dco" min="0" max="1000" required>
                <p class="info">
                    La Demande Chimique en Oxygène (DCO) est une mesure de la quantité d'oxygène nécessaire pour oxyder chimiquement les matières organiques et inorganiques présentes dans l'eau.<br>
                    <strong>Plages de Mesure Typiques :</strong><br>
                    - Eau très propre : 0 à 20 mg/L<br>
                    - Eau légèrement polluée : 20 à 50 mg/L<br>
                    - Eau de rivière polluée : 50 à 200 mg/L<br>
                    - Eaux usées domestiques traitées : 20 à 100 mg/L<br>
                    - Eaux usées domestiques non traitées : 200 à 600 mg/L<br>
                    - Eaux usées industrielles : 200 à 1000 mg/L ou plus
                </p>
            </div>
            <button type="submit">Envoyer</button>
        </form>
    </div>
</body>
</html>


