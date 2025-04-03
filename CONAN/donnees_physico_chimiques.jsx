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
        const response = await axios.get('http://localhost:3001/api/stations');
        setStations(response.data);
      } catch (error) {
        console.error('Erreur lors de la récupération des stations :', error);
      }
    };

    fetchData();
    fetchStations();
  }, []);

  useEffect(() => {
    if (!mapRef.current) {
      mapRef.current = L.map('map').setView([46.6031, 1.8883], 6);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(mapRef.current);
    }
  }, []);

  useEffect(() => {
    if (mapRef.current && stations.length > 0) {
      stations.forEach(station => {
        if (station.latitude && station.longitude) {
          const marker = L.marker([station.latitude, station.longitude]).addTo(mapRef.current);
          let popupContent = `<strong>Rivière:</strong> ${station.riviere}<br>`;
          if (station.analyses.length > 0) {
            station.analyses.forEach(analyse => {
              popupContent += `<strong>${analyse.type}:</strong> ${analyse.valeur} ${analyse.unite}<br>`;
            });
          } else {
            popupContent += 'Aucune analyse disponible.';
          }
          marker.bindPopup(popupContent);
        }
      });
    }
  }, [stations]);

  const generateChartData = (type) => {
    if (!rawData) return { labels: [], datasets: [] };
    const labels = [...new Set(rawData.map(item => item.date))].sort();
    const dataset = labels.map(date => {
      const item = rawData.find(d => d.date === date && d.type === type);
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
      <h2>Graphiques des données physico-chimiques</h2>
      <Line data={generateChartData('ph')} options={chartOptions('pH', 0, 14)} />
      <Line data={generateChartData('conductivite')} options={chartOptions('Conductivité (µS/cm)', 0.05, 10000)} />
      <Line data={generateChartData('turbidite')} options={chartOptions('Turbidité (NTU)', 0, 1000)} />
      <Line data={generateChartData('oxygene')} options={chartOptions('Oxygène (mg/L)', 0, 14)} />
      <Line data={generateChartData('dco')} options={chartOptions('DCO (mg/L)', 0, 1000)} />
      <div id="map" style={{ height: '500px', marginTop: '20px' }}></div>
      <div>
        <h2>Données JSON</h2>
        {rawData ? (
          <pre>{JSON.stringify(rawData, null, 2)}</pre>
        ) : (
          <p>Chargement des données...</p>
        )}
      </div>
    </div>
  );
};

export default DonneesPhysicoChimiques;
