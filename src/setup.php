<?php
// src/setup.php

// 1. On charge notre outil de connexion qu'on a créé juste avant
require_once __DIR__ . '/Database.php';

try {
    // 2. On se connecte
    $db = new Database();
    $pdo = $db->getConnection();

    // 3. La requête SQL de création de table
    // IF NOT EXISTS : Évite une erreur si on relance le script par erreur
    $sql = "
    CREATE TABLE IF NOT EXISTS movies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        director VARCHAR(255) DEFAULT NULL,
        release_year INT DEFAULT NULL,
        summary TEXT,
        user_mood VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    ";

    // 4. On envoie l'ordre à MySQL
    $pdo->exec($sql);

    echo "✅ La table 'movies' a été créée avec succès !";

} catch (PDOException $e) {
    echo "❌ Erreur lors de la création de la table : " . $e->getMessage();
}