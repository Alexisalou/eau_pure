// Composant principal de l'application React "Eau Pure".
// Il sert de point d'entrée et regroupe les éléments clés de l'interface :
// - Affiche le titre du projet,
// - Intègre le composant `DonneesPhysicoChimiques` qui gère la visualisation des données environnementales,
// - Propose un bouton de redirection vers une interface de gestion des utilisateurs (hébergée en PHP).
//
// Ce fichier coordonne les principaux éléments de l'application et assure la navigation vers les outils de gestion.


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
    window.location.href = 'http://10.0.14.8:8080/index.php';
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

