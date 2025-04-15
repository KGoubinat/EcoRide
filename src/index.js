// src/index.js
import React from 'react';
import ReactDOM from 'react-dom';
import App from './App';

// Utilisation de ReactDOM pour rendre le composant App
ReactDOM.render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
  document.getElementById('root') // L'élément dans le HTML où React va être rendu
);
