import React, { useEffect, useState, useRef } from 'react';
import { Line } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
import axios from 'axios';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import './donnees_physico_chimiques.css';


ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
);

const DonneesPhysicoChimiques = () => {
  const [rawData, setRawData] = useState(null);
  const [stations, setStations] = useState([]);
  const [selectedRiver, setSelectedRiver] = useState('');
  const [popupContent, setPopupContent] = useState('');
  const mapRef = useRef(null);

  useEffect(() => {
  const fetchAllData = async () => {
    try {
      const [analyseRes, mesureRes] = await Promise.all([
        axios.get('http://localhost:3001/api/analyse'),
        axios.get('http://localhost:3001/api/mesures'),
      ]);
      // Fusionner les deux datasets
      setRawData([...analyseRes.data, ...mesureRes.data]);
    } catch (error) {
      console.error('Erreur lors de la récupération des données :', error);
    }
  };

  const fetchStations = async () => {
    try {
      const response = await axios.get('http://localhost:3001/api/stations');
      setStations(response.data);
    } catch (error) {
      console.error('Erreur lors de la récupération des stations :', error);
    }
  };

  fetchAllData();
  fetchStations();
}, []);


  // Récupérer les noms des rivières uniques
  const riverNames = [...new Set(stations.map(station => station.riviere))];

  useEffect(() => {
    if (!mapRef.current) {
      const map = L.map('map').setView([48.3, -3.1], 8);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);
      mapRef.current = map;
    }

    const map = mapRef.current;

    // Supprimer les anciens marqueurs
    map.eachLayer(layer => {
      if (layer instanceof L.Marker) {
        map.removeLayer(layer);
      }
    });

// Ajouter les marqueurs
if (stations.length > 0 && rawData) {
  stations.forEach(station => {
    if (station.latitude && station.longitude) {
      const isSelected = station.riviere === selectedRiver;

      const marker = L.marker([station.latitude, station.longitude], {
        icon: L.divIcon({
          className: '',
          html: `<span class="custom-marker${isSelected ? ' selected' : ''}"></span>`,
          iconSize: [16, 16],
          iconAnchor: [8, 8]
        })
      }).addTo(map);

marker.on('click', () => {
  const latestData = rawData
    .filter(data => data.latitude === station.latitude && data.longitude === station.longitude)
    .sort((a, b) => new Date(b.date) - new Date(a.date));

  // ✅ On déclare ici popupHTML
  let popupHTML = `
    <div class="popup-content">
      <h4>${station.riviere}</h4>
      <table class="popup-table">
        <tbody>
  `;

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

  setPopupContent(popupHTML);
  marker.bindPopup(popupHTML).openPopup();
});

    }
  });
}

  }, [stations, rawData, selectedRiver]); // ⬅️ important d'ajouter selectedRiver


  const generateChartData = (type, river) => {
  if (!rawData) return { labels: [], datasets: [] };

  // Filtrer les données de la rivière + type (ex : "pluie", "hauteur_eau", etc.)
  const filteredData = rawData
    .filter(item => item.riviere === river && item.type === type)
    .sort((a, b) => new Date(a.date) - new Date(b.date)); // tri par date

  const labels = filteredData.map(item => item.date);
  const data = filteredData.map(item => item.valeur);

  return {
    labels,
    datasets: [
      {
        label: type,
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
        fill: false,
      },
    ],
  };
};


const chartOptions = (title, min, max) => ({
  responsive: true,
  maintainAspectRatio: false, // <-- Important
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



  return (
    <div>
      <div id="map" style={{ height: '500px', marginBottom: '20px' }}></div>

      <label htmlFor="river-select" style={{ fontWeight: 'bold', display: 'block', marginBottom: '10px' }}>
  <h2>Sélectionnez une rivière pour visualiser les données physico-chimiques sous forme de graphiques</h2>
</label>
<select id="river-select" onChange={(e) => setSelectedRiver(e.target.value)} value={selectedRiver}>
  <option value="">Choix de la rivière</option>
  {riverNames.map((river, index) => (
    <option key={index} value={river}>{river}</option>
  ))}
</select>


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



      {/* Afficher le contenu du popup lorsque l'on clique sur un marqueur */}
      {popupContent && (
        <div style={{ marginTop: '20px' }}>
        </div>
      )}
    </div>
  );
};

export default DonneesPhysicoChimiques;


