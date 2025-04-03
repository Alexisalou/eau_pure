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

// Endpoint API pour récupérer les données physico-chimiques
app.get('/api/data', (req, res) => {
  const sql = `
    SELECT a.valeur, a.type, a.unite, e.date
    FROM Analyse a
    JOIN Echantillon e ON a.prelevement = e.id
    ORDER BY e.date DESC
  `;
  db.query(sql, (err, results) => {
    if (err) {
      console.error('Erreur lors de la récupération des données:', err);
      res.status(500).send('Erreur serveur');
      return;
    }
    res.json(results);
  });
});

app.listen(port, () => {
  console.log(`Serveur en écoute sur le port ${port}`);
});
