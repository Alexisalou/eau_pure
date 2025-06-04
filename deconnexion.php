<?php
// Démarre la session (obligatoire pour pouvoir la manipuler)
session_start();

// Supprime toutes les variables de session
session_unset();

// Détruit complètement la session côté serveur
session_destroy();

// Redirige l'utilisateur vers la page d'accueil (ou de connexion)
header('Location: index.php');
exit(); // Arrête l'exécution du script
