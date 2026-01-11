<?php

class MovieRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Récupérer tout (avec tri par date d'ajout décroissante)
    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM movies ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // SAUVEGARDE INTELLIGENTE (Gère l'IA et le Manuel)
    public function save(array $data): void
    {
        // Génération automatique du lien Letterboxd si non fourni
        $letterboxd = $data['letterboxd_url'] ?? '';
        if (empty($letterboxd) && !empty($data['title'])) {
            // Astuce : Letterboxd utilise un format slug (ex: the-matrix)
            // On fait un lien de recherche simple pour être sûr que ça marche
            $slug = urlencode($data['title']);
            $letterboxd = "https://letterboxd.com/search/$slug/";
        }

        $sql = "INSERT INTO movies 
                (title, director, release_year, summary, user_mood, letterboxd_url, source_type, is_seen) 
                VALUES 
                (:title, :director, :year, :summary, :mood, :letterboxd, :source, :seen)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':title' => $data['title'],
            ':director' => $data['director'] ?? 'Inconnu',
            ':year' => $data['year'] ?? 0,
            ':summary' => $data['summary'] ?? '',
            ':mood' => $data['mood'] ?? null,
            ':letterboxd' => $letterboxd,
            ':source' => $data['source'] ?? 'AI',
            ':seen' => $data['is_seen'] ?? 0
        ]);
    }

    // SUPPRESSION (Feature demandée)
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM movies WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    // MARQUER COMME VU / NOTER (On prépare le terrain)
    public function updateStatus(int $id, bool $isSeen, int $rating): void
    {
        $stmt = $this->pdo->prepare("UPDATE movies SET is_seen = :seen, personal_rating = :rating WHERE id = :id");
        $stmt->execute([':seen' => $isSeen, ':rating' => $rating, ':id' => $id]);
    }

    // --- NOUVELLE FONCTION : MISE A JOUR ---
    public function update(int $id, int $rating, bool $isSeen): void
    {
        $sql = "UPDATE movies SET personal_rating = :rating, is_seen = :seen WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':rating' => $rating,
            ':seen' => $isSeen ? 1 : 0,
            ':id' => $id
        ]);
    }
}