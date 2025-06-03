const express = require('express');
const mysql = require('mysql');
const cors = require('cors');
const app = express();
const port = 3001;

app.use(cors());

// Configuration de la connexion à la base de données
const db = mysql.createConnection({
  host: '10.0.14.4',
  user: 'root',
  password: 'ieufdl',
  database: 'eau_pure',
  port: 9999,
});

db.connect((err) => {
  if (err) {
    console.error('Erreur de connexion à la base de données:', err);
    return;
  }
  console.log('Connecté à la base de données MySQL');
});

// Endpoint : Données d'analyse manuelle
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
    res.json(results);
  });
});

// Endpoint : Données de mesures automatiques (pluie, hauteur d'eau)
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

// Endpoint : Stations (pour affichage statique ou dropdown)
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

// Serveur
app.listen(port, () => {
  console.log(`Serveur en écoute sur le port ${port}`);
});
