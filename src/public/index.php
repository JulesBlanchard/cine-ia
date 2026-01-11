<?php

// On inclut notre classe Database
// __DIR__ est une "constante magique" qui donne le chemin du dossier actuel
require_once __DIR__ . '/../Database.php';

echo "<h1>Test de connexion MySQL</h1>";

// On crée une instance de notre classe
$database = new Database();
$pdo = $database->getConnection();

if ($pdo) {
    echo "<p style='color: green; font-weight: bold;'>✅ Succès : PHP est connecté à MySQL !</p>";
} else {
    echo "<p style='color: red;'>❌ Échec de la connexion.</p>";
}