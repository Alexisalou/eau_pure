// Importe React (nécessaire pour créer des composants)
import React from 'react';

// Importe le composant qui affiche les données physico-chimiques
import DonneesPhysicoChimiques from './components/donnees_physico_chimiques';

// Importe les styles CSS globaux de l'application
import './App.css';

// Composant principal de l'application
function App() {

  // Fonction appelée lorsqu'on clique sur le bouton
  // Elle redirige vers une page PHP (probablement une interface de gestion utilisateurs)
  const handleRedirect = () => {
    window.location.href = 'http://localhost:8080/index.php';
  };

  // Rendu JSX du composant App
  return (
    <div className="App">
      {/* Titre principal */}
      <h1 className="main-title">Projet Eau Pure</h1>

      {/* Bouton qui redirige vers la page de gestion des utilisateurs */}
      <button className="redirect-button" onClick={handleRedirect}>
        Gestion de utilisateurs
      </button>

      {/* Affichage du composant des données physico-chimiques */}
      <DonneesPhysicoChimiques />
    </div>
  );
}

// Exporte le composant App pour pouvoir l'utiliser ailleurs (ex. dans index.js)
export default App;

