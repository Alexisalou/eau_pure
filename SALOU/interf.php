<?php
class Interf {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function login($numero_de_telephone, $password) {
        $stmt = $this->conn->prepare("SELECT id, mot_de_passe FROM Technicien WHERE numero_de_telephone = ?");
        if ($stmt) {
            $stmt->bind_param('s', $numero_de_telephone);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($technicien_id, $hashed_password);
                $stmt->fetch();

                if (password_verify($password, $hashed_password)) {
                    $_SESSION['technicien_id'] = $technicien_id;
                    return ["success" => true];
                }
            }

            $stmt->close();
        }
        return ["success" => false, "message" => "Identifiants incorrects."];
    }

    public function verifierAdmin($mdp_admin) {
        $stmt = $this->conn->prepare("SELECT mdp_connect FROM Technicien WHERE mdp_connect IS NOT NULL");
        if ($stmt) {
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($mdp_connect);
                $stmt->fetch();

                if ($mdp_connect !== null && password_verify($mdp_admin, $mdp_connect)) {
                    return ["success" => true];
                }
            }

            $stmt->close();
        }
        return ["success" => false, "message" => "Mot de passe incorrect."];
    }

    public function ajoutUtilisateur($numero, $conf_numero, $password, $conf_password) {
        if ($numero !== $conf_numero) {
            return ["success" => false, "message" => "Les numéros de téléphone ne correspondent pas."];
        }

        if ($password !== $conf_password) {
            return ["success" => false, "message" => "Les mots de passe ne correspondent pas."];
        }

        if (!$this->validerMotDePasse($password)) {
            return ["success" => false, "message" => "Le mot de passe doit contenir au moins 8 caractères, une majuscule et un caractère spécial."];
        }

        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM Technicien WHERE numero_de_telephone = ?");
        $stmt->bind_param("s", $numero);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($count);
        $stmt->fetch();

        if ($count > 0) {
            return ["success" => false, "message" => "Numéro de téléphone déjà existant."];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare("INSERT INTO Technicien (numero_de_telephone, mot_de_passe) VALUES (?, ?)");
        $stmt->bind_param("ss", $numero, $hash);

        if ($stmt->execute()) {
            return ["success" => true, "message" => "Utilisateur ajouté avec succès."];
        } else {
            return ["success" => false, "message" => "Erreur lors de l'ajout de l'utilisateur."];
        }
    }

    public function modifierUtilisateur($numero, $ancien_password, $new_password, $conf_new_password) {
        if ($new_password !== $conf_new_password) {
            return ["success" => false, "message" => "Les nouveaux mots de passe ne correspondent pas."];
        }

        if (!$this->validerMotDePasse($new_password)) {
            return ["success" => false, "message" => "Le mot de passe doit contenir au moins 8 caractères, une majuscule et un caractère spécial."];
        }

        $stmt = $this->conn->prepare("SELECT mot_de_passe FROM Technicien WHERE numero_de_telephone = ?");
        $stmt->bind_param("s", $numero);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            return ["success" => false, "message" => "Identifiants incorrects."];
        }

        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        if (!password_verify($ancien_password, $hashed_password)) {
            return ["success" => false, "message" => "Identifiants incorrects."];
        }

        $stmt->close();

        $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare("UPDATE Technicien SET mot_de_passe = ? WHERE numero_de_telephone = ?");
        $stmt->bind_param("ss", $new_hash, $numero);

        if ($stmt->execute()) {
            return ["success" => true, "message" => "Mot de passe mis à jour."];
        } else {
            return ["success" => false, "message" => "Erreur lors de la mise à jour du mot de passe."];
        }
    }

    public function supprimerUtilisateur($numero, $conf_numero, $password, $conf_password) {
        if ($numero !== $conf_numero) {
            return ["success" => false, "message" => "Numéro de téléphone ou mot de passe incorrect."];
        }

        if ($password !== $conf_password) {
            return ["success" => false, "message" => "Numéro de téléphone ou mot de passe incorrect."];
        }

        $stmt = $this->conn->prepare("SELECT mot_de_passe FROM Technicien WHERE numero_de_telephone = ?");
        $stmt->bind_param("s", $numero);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            return ["success" => false, "message" => "Numéro inexistant."];
        }

        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        if (!password_verify($password, $hashed_password)) {
            return ["success" => false, "message" => "Numéro de téléphone ou mot de passe incorrect."];
        }

        $stmt->close();

        $stmt = $this->conn->prepare("DELETE FROM Technicien WHERE numero_de_telephone = ?");
        $stmt->bind_param("s", $numero);

        if ($stmt->execute()) {
            return ["success" => true, "message" => "Utilisateur supprimé avec succès."];
        } else {
            return ["success" => false, "message" => "Erreur lors de la suppression de l'utilisateur."];
        }
    }

    public function getRivieres() {
        $rivieres = [];
        $query = "SELECT id, riviere, latitude, longitude FROM Station";
        $result = $this->conn->query($query);

        if ($result) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $rivieres[] = $row;
                }
            } else {
                $rivieres[] = ["id" => "", "riviere" => "Aucune rivière", "latitude" => "", "longitude" => ""];
            }
        } else {
            die("Erreur lors de la récupération des rivières: " . $this->conn->error);
        }

        return $rivieres;
    }

    public function insertEchantillon($date, $preleveur, $technicien_id, $riviere_id) {
        $stmt = $this->conn->prepare("INSERT INTO Echantillon (date, preleveur, technicien, station_id) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('sisi', $date, $preleveur, $technicien_id, $riviere_id);
            if ($stmt->execute()) {
                return $stmt->insert_id;
            } else {
                die("Erreur d'insertion dans la table Echantillon: " . $stmt->error);
            }
            $stmt->close();
        } else {
            die("Erreur de préparation de la requête SQL pour Echantillon.");
        }
    }

    public function insertAnalyse($prelevement, $data) {
        $stmt = $this->conn->prepare("INSERT INTO Analyse (prelevement, valeur, unite, type) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            foreach ($data as $type => $info) {
                $stmt->bind_param('idss', $prelevement, $info['value'], $info['unite'], $type);
                if (!$stmt->execute()) {
                    die("Erreur d'insertion dans la table Analyse: " . $stmt->error);
                }
            }
            $stmt->close();
            return "Données insérées avec succès.";
        } else {
            return "Erreur de préparation de la requête SQL pour Analyse.";
        }
    }

    private function validerMotDePasse($password) {
        $longueurMin = 8;
        $majuscule = '/[A-Z]/';
        $caractereSpecial = '/[\W_]/';

        if (strlen($password) < $longueurMin || !preg_match($majuscule, $password) || !preg_match($caractereSpecial, $password)) {
            return false;
        }
        return true;
    }
}
    private function validerMotDePasse($password) {
        $longueurMin = 8;
        $majuscule = '/[A-Z]/';
        $caractereSpecial = '/[\W_]/';

        if (strlen($password) < $longueurMin || !preg_match($majuscule, $password) || !preg_match($caractereSpecial, $password)) {
            return false;
        }
        return true;
    }
}
?>


