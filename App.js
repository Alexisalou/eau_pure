import React, { useState } from 'react';
import DonneesPhysicoChimiques from './components/donnees_physico_chimiques';
import './App.css';

function App() {
  const [jsonData, setJsonData] = useState(null);

  return (
    <div className="App">
      <header className="App-header">
        <h1>Application Eau Pure</h1>
      </header>
      <main>
        <DonneesPhysicoChimiques onDataLoaded={setJsonData} />
        <div>
          <h2>Données JSON</h2>
          {jsonData ? <pre>{JSON.stringify(jsonData, null, 2)}</pre> : <p>Chargement des données...</p>}
        </div>
      </main>
    </div>
  );
}

export default App;
