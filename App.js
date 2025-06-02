import React from 'react';
import DonneesPhysicoChimiques from './components/donnees_physico_chimiques';
import './App.css';


function App() {
  const handleRedirect = () => {
    window.location.href = 'http://localhost:8080/index.php';
  };

  return (
    <div className="App">
      <h1 className="main-title">Projet Eau Pure</h1>

      {/* Bouton de redirection */}
      <button className="redirect-button" onClick={handleRedirect}>
        Gestion de utilisateurs
      </button>

      <DonneesPhysicoChimiques />
    </div>
  );
}

export default App;

