// Ce serveur Express sert d'API backend pour une application de suivi de la qualité de l'eau.
// Il se connecte à une base de données MySQL et expose plusieurs points d'accès :
// - /api/analyse : récupère les données issues des analyses manuelles d'échantillons.
// - /api/mesures : récupère les mesures automatiques issues des capteurs (pluviomètres, limnimètres).
// - /api/stations : récupère les coordonnées géographiques et noms des rivières associées aux stations.
// L'API utilise CORS pour accepter les requêtes du frontend (ex. React) et écoute sur le port 3001.


// Import des modules nécessaires
const express = require('express');       // Framework web minimal
const mysql = require('mysql');           // Client MySQL pour Node.js
const cors = require('cors');             // Middleware pour autoriser les requêtes depuis d'autres origines

const app = express();                    // Initialisation de l'application Express
const port = 3001;                        // Port d'écoute du serveur

// Active CORS pour permettre les requêtes depuis le frontend (React)
app.use(cors());

// Configuration de la base de données MySQL
const db = mysql.createConnection({
  host: '192.168.0.193',       // Adresse IP du serveur MySQL
  user: 'root',            // Nom d'utilisateur
  password: 'ieufdl',      // Mot de passe
  database: 'eau_pure',    // Nom de la base de données
  port: 9999               // Port MySQL personnalisé
});

// Connexion à la base MySQL
db.connect((err) => {
  if (err) {
    console.error('Erreur de connexion à la base de données:', err);
    return;
  }
  console.log('Connecté à la base de données MySQL');
});


// 1 : Récupérer les données d'analyse manuelle
app.get('/api/analyse', (req, res) => {
  const sql = `
    SELECT 
      a.valeur, 
      a.type, 
      a.unite, 
      e.date,
      st.latitude,
      st.longitude,
      st.riviere,
      'analyse_manuelle' AS source
    FROM Analyse a
    JOIN Echantillon e ON a.prelevement = e.id
    JOIN Preleveur p ON e.preleveur = p.id
    JOIN Station st ON p.station = st.id
    ORDER BY e.date DESC
  `;
  
  db.query(sql, (err, results) => {
    if (err) {
      console.error('Erreur lors de la récupération des données d\'analyse:', err);
      res.status(500).send('Erreur serveur');
      return;
    }
    res.json(results); // Renvoie un tableau JSON des résultats
  });
});


// 2 : Récupérer les données des capteurs automatiques (pluie, hauteur d'eau)
app.get('/api/mesures', (req, res) => {
  const sql = `
    SELECT 
      m.valeur,
      CASE 
        WHEN LOWER(c.reference) = 'limnimètre' THEN 'hauteur_eau'
        WHEN LOWER(c.reference) = 'pluviomètre' THEN 'pluie'
        ELSE 'inconnu'
      END AS type,
      m.unite,
      m.date,
      s.latitude,
      s.longitude,
      s.riviere,
      'mesure_auto' AS source
    FROM Mesure m
    JOIN Capteur c ON m.capteur = c.id
    JOIN Station s ON c.station = s.id
    WHERE LOWER(c.reference) IN ('limnimètre', 'pluviomètre')
    ORDER BY m.date DESC
  `;

  db.query(sql, (err, results) => {
    if (err) {
      console.error('Erreur lors de la récupération des mesures:', err);
      res.status(500).send('Erreur serveur');
      return;
    }
    res.json(results);
  });
});


// 3 : Récupérer les informations des stations (lat/lon/rivière)
app.get('/api/stations', (req, res) => {
  const sql = `
    SELECT latitude, longitude, riviere
    FROM Station
  `;
  db.query(sql, (err, results) => {
    if (err) {
      console.error('Erreur lors de la récupération des stations:', err);
      res.status(500).send('Erreur serveur');
      return;
    }
    res.json(results);
  });
});


// Lancement du serveur sur le port défini
app.listen(port, () => {
  console.log(`Serveur en écoute sur le port ${port}`);
});

