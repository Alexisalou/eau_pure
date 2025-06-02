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
    const fetchData = async () => {
      try {
        const response = await axios.get('http://localhost:3001/api/data');
        setRawData(response.data);
      } catch (error) {
        console.error('Erreur lors de la récupération des données :', error);
      }
    };

    const fetchStations = async () => {
      try {
        const response = await axios.get('http://localhost:3001/api');
        setStations(response.data);
      } catch (error) {
        console.error('Erreur lors de la récupération des stations :', error);
      }
    };

    fetchData();
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

    // Supprimer tous les anciens marqueurs à chaque rendu
    map.eachLayer(layer => {
      if (layer instanceof L.Marker) {
        map.removeLayer(layer);
      }
    });

    // Ajouter les marqueurs à la carte en fonction des stations et des données
    if (stations.length > 0 && rawData) {
      stations.forEach(station => {
        if (station.latitude && station.longitude) {
          const marker = L.marker([station.latitude, station.longitude], {
            icon: L.icon({
              iconUrl: 'https://cdn-icons-png.flaticon.com/32/684/684908.png',
              iconSize: [32, 32],
              iconAnchor: [16, 32],
              popupAnchor: [0, -32],
            })
          }).addTo(map);

          marker.on('click', () => {
            const latestData = rawData.filter(data => data.latitude === station.latitude && data.longitude === station.longitude)
              .sort((a, b) => new Date(b.date) - new Date(a.date));

            // Trouver les dernières données pour chaque paramètre
            let popupContent = `<strong>Rivière:</strong> ${station.riviere}<br>`;
            const parameters = ['ph', 'conductivite', 'dco', 'oxygene', 'turbidite'];
            parameters.forEach(param => {
              const value = latestData.find(data => data.type === param);
              if (value) {
                popupContent += `<strong>${param.toUpperCase()}:</strong> ${value.valeur} ${value.unite}<br>`;
              }
            });

            // Afficher les données dans le popup
            setPopupContent(popupContent);
            marker.bindPopup(popupContent).openPopup();
          });
        }
      });
    }
  }, [stations, rawData]);  // Effect se déclenche à chaque changement de données

  const generateChartData = (type, river) => {
    if (!rawData) return { labels: [], datasets: [] };
    const filteredData = rawData.filter(item => item.riviere === river);
    const labels = [...new Set(filteredData.map(item => item.date))].sort();
    const dataset = labels.map(date => {
      const item = filteredData.find(d => d.date === date && d.type === type);
      return item ? item.valeur : null;
    });
    return {
      labels,
      datasets: [
        {
          label: type,
          data: dataset,
          borderColor: {
            'ph': 'rgba(255, 99, 132, 1)',
            'conductivite': 'rgba(54, 162, 235, 1)',
            'turbidite': 'rgba(255, 206, 86, 1)',
            'oxygene': 'rgba(75, 192, 192, 1)',
            'dco': 'rgba(153, 102, 255, 1)'
          }[type],
          borderWidth: 2,
        },
      ],
    };
  };

  const chartOptions = (title, min, max) => ({
    responsive: true,
    plugins: {
      title: {
        display: true,
        text: title,
      },
    },
    scales: {
      x: {
        type: 'category',
        title: {
          display: true,
          text: 'Date',
        },
      },
      y: {
        type: 'linear',
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

      <h2>Sélectionnez une rivière pour voir les graphiques</h2>
      <select onChange={(e) => setSelectedRiver(e.target.value)} value={selectedRiver}>
        <option value="">Sélectionner une rivière</option>
        {riverNames.map((river, index) => (
          <option key={index} value={river}>{river}</option>
        ))}
      </select>

      {selectedRiver && (
        <>
          <h2>Graphiques des données physico-chimiques - {selectedRiver}</h2>
          <Line data={generateChartData('ph', selectedRiver)} options={chartOptions('pH', 0, 14)} />
          <Line data={generateChartData('conductivite', selectedRiver)} options={chartOptions('Conductivité (µS/cm)', 0.05, 10000)} />
          <Line data={generateChartData('turbidite', selectedRiver)} options={chartOptions('Turbidité (NTU)', 0, 1000)} />
          <Line data={generateChartData('oxygene', selectedRiver)} options={chartOptions('Oxygène (mg/L)', 0, 14)} />
          <Line data={generateChartData('dco', selectedRiver)} options={chartOptions('DCO (mg/L)', 0, 1000)} />
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


