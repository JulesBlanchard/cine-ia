<?php

class MovieRepository
{
    // On stocke la connexion PDO ici
    private PDO $connection;

    // Constructeur : On exige une connexion PDO valide pour démarrer
    // C'est ça, l'Injection de Dépendance !
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    // Méthode 1 : Sauvegarder un film (Create)
    public function save(string $title, string $mood, string $summary): void
    {
        // 1. On prépare la requête SQL (avec des :placeholders pour la sécurité)
        $sql = "INSERT INTO movies (title, user_mood, summary) VALUES (:title, :mood, :summary)";

        // 2. On prépare l'instruction pour MySQL
        $stmt = $this->connection->prepare($sql);

        // 3. On exécute en remplaçant les placeholders par les vraies valeurs
        $stmt->execute([
            'title' => $title,
            'mood' => $mood,
            'summary' => $summary
        ]);
    }

    // Méthode 2 : Récupérer tous les films (Read)
    public function findAll(): array
    {
        // On sélectionne tout, du plus récent au plus vieux
        $sql = "SELECT * FROM movies ORDER BY created_at DESC";

        $stmt = $this->connection->query($sql);

        // fetchAll retourne un tableau associatif (grâce à notre config PDO dans Database.php)
        return $stmt->fetchAll();
    }
}