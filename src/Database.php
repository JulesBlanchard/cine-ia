<?php

class Database
{
    // Propriété pour stocker l'instance de connexion (l'objet PDO)
    // ?PDO signifie que ça peut être un objet PDO ou null (si pas connecté)
    private ?PDO $pdo = null;

    public function getConnection(): ?PDO
    {
        // Si on est déjà connecté, on renvoie la connexion existante (Pattern Singleton simplifié)
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        // Configuration de la connexion (DSN = Data Source Name)
        // Note le host=db qui correspond au service docker !
        $host = 'db';
        $db   = 'cinedb';
        $user = 'devuser';
        $pass = 'devpassword';
        $charset = 'utf8mb4'; // Important pour gérer les émojis et accents correctement

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

        // Options "Deep Dive" pour la robustesse
        $options = [
            // Si une erreur survient, lance une Exception (crash propre) au lieu de juste un warning silencieux
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Récupère les données sous forme de tableau associatif par défaut (plus facile à utiliser)
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Désactive l'émulation des requêtes préparées (pour utiliser les vraies de MySQL, plus sûres)
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            // Tentative de connexion
            $this->pdo = new PDO($dsn, $user, $pass, $options);
            return $this->pdo;
        } catch (PDOException $e) {
            // En production, on loggue l'erreur dans un fichier, on ne l'affiche jamais à l'utilisateur !
            // Pour le dev, on l'affiche pour déboguer.
            die("Erreur de connexion BDD : " . $e->getMessage());
        }
    }
}