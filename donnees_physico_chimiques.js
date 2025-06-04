// Ce composant React affiche les données physico-chimiques de la qualité de l'eau à l'aide d'une carte interactive (Leaflet)
// et de graphiques dynamiques (Chart.js). Il permet de :
// - Visualiser les stations sur une carte avec des popups contenant les dernières données mesurées ou analysées,
// - Sélectionner une rivière pour afficher ses données (pH, conductivité, turbidité, oxygène, DCO, hauteur d’eau, pluie),
// - Générer automatiquement des courbes temporelles pour chaque paramètre physico-chimique associé à la rivière sélectionnée.
//
// Les données sont récupérées depuis une API Node.js en backend via Axios.
// Ce composant est un point central de l’interface utilisateur pour l’analyse environnementale des rivières surveillées.


// Importation des hooks React pour la gestion des états, effets, et références
import React, { useEffect, useState, useRef } from 'react';

// Composant de graphique en ligne de Chart.js intégré à React
import { Line } from 'react-chartjs-2';

// Importation des modules nécessaires de Chart.js pour afficher un graphique en ligne
import {
  Chart as ChartJS,
  CategoryScale,  // Axe X (catégories : dates ici)
  LinearScale,    // Axe Y (valeurs numériques)
  PointElement,   // Points sur la courbe
  LineElement,    // Lignes entre les points
  Title,          // Titre du graphique
  Tooltip,        // Info-bulles au survol
  Legend          // Légende
} from 'chart.js';

// Librairie Axios pour les requêtes HTTP
import axios from 'axios';

// Librairie Leaflet pour la carte interactive
import L from 'leaflet';
import 'leaflet/dist/leaflet.css'; // Style CSS de Leaflet

// Fichier CSS local pour le composant
import './donnees_physico_chimiques_react.css';

// Enregistrement des composants nécessaires pour que Chart.js fonctionne
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
);

// Déclaration du composant principal de la page
const DonneesPhysicoChimiques = () => {
  // États pour stocker les données et interactions utilisateur
  const [rawData, setRawData] = useState(null);         // Toutes les données (mesures et analyses)
  const [stations, setStations] = useState([]);         // Liste des stations récupérées
  const [selectedRiver, setSelectedRiver] = useState(''); // Nom de la rivière sélectionnée
  const [popupContent, setPopupContent] = useState(''); // Contenu HTML du popup (optionnel)
  const mapRef = useRef(null);                          // Référence vers la carte Leaflet (évite de la recréer)

  // Chargement des données au premier rendu du composant
  useEffect(() => {
    // Fonction pour récupérer les analyses et mesures (deux endpoints)
    const fetchAllData = async () => {
      try {
        const [analyseRes, mesureRes] = await Promise.all([
          axios.get('http://10.0.14.8:3001/api/analyse'),
          axios.get('http://10.0.14.8:3001/api/mesures'),
        ]);
        // Fusionne les deux types de données dans un même tableau
        setRawData([...analyseRes.data, ...mesureRes.data]);
      } catch (error) {
        console.error('Erreur lors de la récupération des données :', error);
      }
    };

    // Fonction pour récupérer la liste des stations
    const fetchStations = async () => {
      try {
        const response = await axios.get('http://10.0.14.8:3001/api/stations');
        setStations(response.data);
      } catch (error) {
        console.error('Erreur lors de la récupération des stations :', error);
      }
    };

    // Exécution des deux fonctions
    fetchAllData();
    fetchStations();
  }, []);

  // Extraction des noms de rivières sans doublons pour le menu déroulant
  const riverNames = [...new Set(stations.map(station => station.riviere))];

  // Effet qui gère l'affichage de la carte et des marqueurs
  useEffect(() => {
    // Initialisation de la carte seulement une fois
    if (!mapRef.current) {
      const map = L.map('map').setView([48.3, -3.1], 8); // Coordonnées et zoom par défaut
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);
      mapRef.current = map; // On sauvegarde l'instance de la carte
    }

    const map = mapRef.current;

    // Nettoyage des anciens marqueurs (si on change de rivière)
    map.eachLayer(layer => {
      if (layer instanceof L.Marker) {
        map.removeLayer(layer);
      }
    });

    // Création des nouveaux marqueurs à partir des stations
    if (stations.length > 0 && rawData) {
      stations.forEach(station => {
        if (station.latitude && station.longitude) {
          const isSelected = station.riviere === selectedRiver;

          // Création du marqueur avec un style personnalisé selon sélection
          const marker = L.marker([station.latitude, station.longitude], {
            icon: L.divIcon({
              className: '',
              html: `<span class="custom-marker${isSelected ? ' selected' : ''}"></span>`,
              iconSize: [16, 16],
              iconAnchor: [8, 8]
            })
          }).addTo(map);

          // Événement au clic : affiche un popup avec les dernières données
          marker.on('click', () => {
            const latestData = rawData
              .filter(data =>
                data.latitude === station.latitude &&
                data.longitude === station.longitude
              )
              .sort((a, b) => new Date(b.date) - new Date(a.date)); // Tri décroissant des données

            // Construction du contenu HTML du popup
            let popupHTML = `
              <div class="popup-content">
                <h4>${station.riviere}</h4>
                <table class="popup-table">
                  <tbody>
            `;

            // Liste des paramètres affichés
            const parameters = ['ph', 'conductivite', 'dco', 'oxygene', 'turbidite', 'hauteur_eau', 'pluie'];
            const labels = {
              ph: 'pH',
              conductivite: 'Conductivité',
              dco: 'DCO',
              oxygene: 'Oxygène',
              turbidite: 'Turbidité',
              hauteur_eau: 'Hauteur d\'eau',
              pluie: 'Pluviométrie'
            };

            // Ajout des lignes dans le tableau du popup
            parameters.forEach(param => {
              const value = latestData.find(data => data.type === param);
              if (value) {
                popupHTML += `
                  <tr>
                    <th>${labels[param]}</th>
                    <td>${value.valeur} ${value.unite}</td>
                  </tr>
                `;
              }
            });

            popupHTML += `
                  </tbody>
                </table>
              </div>
            `;

            // Mise à jour du popup sur la carte et en React
            setPopupContent(popupHTML);
            marker.bindPopup(popupHTML).openPopup();
          });
        }
      });
    }
  }, [stations, rawData, selectedRiver]); // Se relance quand une station, des données ou une rivière change

  // Fonction pour générer les données du graphique pour un type donné (ex: pH) et une rivière
  const generateChartData = (type, river) => {
    if (!rawData) return { labels: [], datasets: [] };

    const filteredData = rawData
      .filter(item => item.riviere === river && item.type === type)
      .sort((a, b) => new Date(a.date) - new Date(b.date)); // Tri croissant par date

    const labels = filteredData.map(item => item.date);      // Liste des dates
    const data = filteredData.map(item => item.valeur);      // Liste des valeurs

    return {
      labels,
      datasets: [
        {
          label: type, // Le type (ex: pH)
          data,
          borderColor: {
            'ph': 'rgba(255, 99, 132, 1)',
            'conductivite': 'rgba(54, 162, 235, 1)',
            'turbidite': 'rgba(255, 206, 86, 1)',
            'oxygene': 'rgba(75, 192, 192, 1)',
            'dco': 'rgba(153, 102, 255, 1)',
            'hauteur_eau': 'rgba(255, 159, 64, 1)',
            'pluie': 'rgba(0, 123, 255, 1)'
          }[type],
          borderWidth: 2,
          tension: 0.3,
          fill: false
        },
      ],
    };
  };

  // Fonction qui retourne les options du graphique en fonction du paramètre
  const chartOptions = (title, min, max) => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      title: {
        display: true,
        text: title,
      },
    },
    scales: {
      x: {
        title: {
          display: true,
          text: 'Date',
        },
      },
      y: {
        title: {
          display: true,
          text: title,
        },
        min,
        max,
      },
    },
  });

  // Affichage du composant
  return (
    <div>
      {/* Affichage de la carte Leaflet */}
      <div id="map" style={{ height: '500px', marginBottom: '20px' }}></div>

      {/* Menu déroulant pour sélectionner une rivière */}
      <label htmlFor="river-select" style={{ fontWeight: 'bold', display: 'block', marginBottom: '10px' }}>
        <h2>Sélectionnez une rivière pour visualiser les données physico-chimiques sous forme de graphiques</h2>
      </label>
      <select id="river-select" onChange={(e) => setSelectedRiver(e.target.value)} value={selectedRiver}>
        <option value="">Choix de la rivière</option>
        {riverNames.map((river, index) => (
          <option key={index} value={river}>{river}</option>
        ))}
      </select>

      {/* Affichage des graphiques uniquement si une rivière est sélectionnée */}
      {selectedRiver && (
        <>
          <h2>Graphiques des données physico-chimiques - {selectedRiver}</h2>

          <div className="chart-container">
            <Line data={generateChartData('ph', selectedRiver)} options={chartOptions('pH', 0, 14)} />
          </div>
          <div className="chart-container">
            <Line data={generateChartData('conductivite', selectedRiver)} options={chartOptions('Conductivité (µS/cm)', 0, 1000)} />
          </div>
          <div className="chart-container">
            <Line data={generateChartData('turbidite', selectedRiver)} options={chartOptions('Turbidité (NTU)', 0, 50)} />
          </div>
          <div className="chart-container">
            <Line data={generateChartData('oxygene', selectedRiver)} options={chartOptions('Oxygène (mg/L)', 0, 14)} />
          </div>
          <div className="chart-container">
            <Line data={generateChartData('dco', selectedRiver)} options={chartOptions('DCO (mg/L)', 0, 100)} />
          </div>
          <div className="chart-container">
            <Line data={generateChartData('hauteur_eau', selectedRiver)} options={chartOptions('Hauteur d\'eau (m)', 0, 1.5)} />
          </div>
          <div className="chart-container">
            <Line data={generateChartData('pluie', selectedRiver)} options={chartOptions('Pluviométrie (L/mm²)', 0, 25)} />
          </div>
        </>
      )}

      {/* Affichage optionnel du contenu du popup en dehors de la carte */}
      {popupContent && (
        <div style={{ marginTop: '20px' }}>
          {/* Vous pouvez aussi afficher le contenu ici si besoin */}
        </div>
      )}
    </div>
  );
};

// Exportation du composant pour qu'il soit utilisable ailleurs
export default DonneesPhysicoChimiques;
