<?php
/**
 * Ce fichier établit une connexion sécurisée à la base de données MySQL en utilisant l’extension MySQLi orientée objet.
 * Il définit les paramètres essentiels de connexion : hôte, port, nom de base, utilisateur et mot de passe.
 * Il vérifie immédiatement si la connexion échoue et interrompt l’exécution avec un message d’erreur si nécessaire.
 * Il configure ensuite l’encodage de caractères en UTF-8 (utf8mb4) pour garantir la compatibilité avec les caractères spéciaux et emojis.
 * Ce fichier est conçu pour être inclus dans les scripts qui nécessitent un accès fiable à la base de données.
 */


// Définition des paramètres de connexion à la base de données
$DATABASE_HOST = '10.0.14.4';         // Adresse IP ou nom d'hôte du serveur MySQL
$DATABASE_NAME = 'eau_pure';          // Nom de la base de données à utiliser
$DATABASE_USER = 'root';              // Nom d'utilisateur MySQL
$DATABASE_PASSWORD = 'ieufdl';        // Mot de passe MySQL pour l'utilisateur
$DATABASE_PORT = '9999';              // Port personnalisé pour la connexion à MySQL (par défaut c'est 3306)

// Création de la connexion MySQL avec l'extension MySQLi (orientée objet)
$conn = new mysqli($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASSWORD, $DATABASE_NAME, $DATABASE_PORT);

// Vérification si la connexion a échoué
if ($conn->connect_error) {
    // En cas d'erreur, on interrompt le script et on affiche le message d'erreur
    die("Connexion échouée : " . $conn->connect_error);
}

// Configuration de l'encodage de caractères utilisé par la connexion à la base
// On force l'encodage UTF-8 avec prise en charge des caractères spéciaux (utf8mb4)
// Cela permet d'éviter les problèmes d'affichage ou de corruption de données
$conn->set_charset("utf8mb4");
?>
