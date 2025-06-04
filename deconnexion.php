<?php
// Ce script gère la déconnexion d'un utilisateur en supprimant toutes les données de session.
// Il est utilisé lorsqu'un utilisateur clique sur "Se déconnecter".
// Étapes du script :
// 1. Démarre la session pour pouvoir la manipuler.
// 2. Vide toutes les variables stockées dans la session.
// 3. Détruit complètement la session sur le serveur.
// 4. Redirige l'utilisateur vers la page d'accueil (ou de connexion).


// Démarre la session (obligatoire pour pouvoir la manipuler)
session_start();

// Supprime toutes les variables de session
session_unset();

// Détruit complètement la session côté serveur
session_destroy();

// Redirige l'utilisateur vers la page d'accueil (ou de connexion)
header('Location: index.php');
exit(); // Arrête l'exécution du script
