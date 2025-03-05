<?php
// Fonction pour hacher un mot de passe
function hashPassword($password) {
    // Utilise la fonction password_hash() pour hacher le mot de passe
    // PASSWORD_DEFAULT utilise l'algorithme de hachage par défaut (actuellement BCRYPT)
    return password_hash($password, PASSWORD_DEFAULT);
}

// Exemple d'utilisation
$motDePasse = "toto";
$motDePasseHache = hashPassword($motDePasse);

// Affiche le mot de passe haché
echo "Mot de passe haché : " . $motDePasseHache;
?>
